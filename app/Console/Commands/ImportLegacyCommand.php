<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacyCommand extends Command
{
    protected $signature = 'meucash:importar-antigo
        {email : E-mail do administrador da empresa que vai receber os dados}
        {--conexao=legado : Conexão do banco antigo (config/database.php)}
        {--empresa-antiga= : ID da empresa no banco antigo, se ele já tiver várias empresas}
        {--repetir : Permite importar de novo para a mesma empresa}';

    protected $description = 'Copia clientes, produtos, vendas, parcelas e caixa do Meu Cash antigo (localhost) para uma empresa';

    private const TABLES = [
        'categories' => 'Categorias',
        'customers' => 'Clientes',
        'products' => 'Produtos',
        'product_expenses' => 'Despesas de produtos',
        'sales' => 'Vendas',
        'sale_items' => 'Itens vendidos',
        'installments' => 'Parcelas',
        'payments' => 'Pagamentos',
        'financial_transactions' => 'Movimentações do caixa',
    ];

    private const COPIED_SETTINGS = [
        'initial_balance', 'due_reminder_days',
        'whatsapp_template_lembrete', 'whatsapp_template_vence_hoje',
        'whatsapp_template_atrasada', 'whatsapp_template_pagamento',
    ];

    private Connection $source;

    private ?int $legacyCompany = null;

    private int $companyId;

    private array $columns = [];

    private array $map = [];

    public function handle(): int
    {
        $this->source = DB::connection($this->option('conexao'));

        try {
            $this->source->getPdo();
        } catch (\Throwable $e) {
            $this->error('Não consegui abrir o banco antigo. O MySQL do XAMPP está ligado? Confira LEGACY_DB_DATABASE no .env.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        if (! $this->source->getSchemaBuilder()->hasTable('sales')) {
            $this->error('O banco "'.$this->source->getDatabaseName().'" não parece ser do Meu Cash (tabela sales não encontrada).');

            return self::FAILURE;
        }

        $admin = User::where('email', mb_strtolower(trim($this->argument('email'))))->first();

        if (! $admin || ! $admin->company_id || $admin->role !== UserRole::Admin) {
            $this->error('Esse e-mail não é administrador de nenhuma empresa no Meu Cash online. Entre no sistema com ele primeiro.');

            return self::FAILURE;
        }

        $this->companyId = $admin->company_id;

        if (! $this->resolveLegacyCompany()) {
            return self::FAILURE;
        }

        $already = DB::table('settings')->where('company_id', $this->companyId)->where('key', 'legacy_import_at')->value('value');
        if ($already && ! $this->option('repetir')) {
            $this->error("Essa empresa já recebeu uma importação em {$already}. Para importar de novo (vai duplicar os dados), use --repetir.");

            return self::FAILURE;
        }

        $this->info('Banco antigo: '.$this->source->getDatabaseName());
        $this->info('Destino: '.$admin->company->name.' ('.$admin->email.')');
        $this->table(['O que', 'Quantidade'], collect(self::TABLES)->map(
            fn ($label, $table) => [$label, $this->source->getSchemaBuilder()->hasTable($table) ? $this->legacy($table)->count() : 0]
        )->values()->all());

        if (! $this->confirm('Importar esses dados para a empresa acima?', true)) {
            $this->line('Nada foi importado.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($admin) {
            $this->mapUsers($admin);
            $this->importCategories();
            $this->copy('customers');
            $this->copy('products', fn ($row) => [
                'category_id' => $this->ref('categories', $row->category_id),
                'photos' => null,
            ]);
            $this->copy('product_expenses', fn ($row) => ['product_id' => $this->ref('products', $row->product_id)], 'product_id');
            $this->importSales();
            $this->copy('sale_items', fn ($row) => [
                'sale_id' => $this->ref('sales', $row->sale_id),
                'product_id' => $this->ref('products', $row->product_id),
            ], 'sale_id');
            $this->copy('installments', fn ($row) => [
                'sale_id' => $this->ref('sales', $row->sale_id),
                'customer_id' => $this->ref('customers', $row->customer_id),
            ], 'sale_id');
            $this->copy('payments', fn ($row) => [
                'installment_id' => $this->ref('installments', $row->installment_id ?? null),
                'sale_id' => $this->ref('sales', $row->sale_id ?? null),
                'customer_id' => $this->ref('customers', $row->customer_id ?? null),
                'user_id' => $this->ref('users', $row->user_id ?? null),
            ], fn ($row) => empty($row->service_id) && (empty($row->sale_id) || $this->ref('sales', $row->sale_id)));
            $this->copy('financial_transactions', fn ($row) => $this->transactionSource($row) + [
                'user_id' => $this->ref('users', $row->user_id ?? null),
            ]);
            $this->importSettings();
        });

        $this->newLine();
        $this->info('Importação concluída.');
        $this->table(['O que', 'Importado'], collect(self::TABLES)->map(fn ($label, $table) => [$label, count($this->map[$table] ?? [])])->values()->all());
        $this->line('As fotos dos produtos não são copiadas: cadastre-as de novo no sistema online.');

        return self::SUCCESS;
    }

    private function resolveLegacyCompany(): bool
    {
        if (! $this->source->getSchemaBuilder()->hasColumn('sales', 'company_id')) {
            return true;
        }

        $companies = $this->source->table('companies')->orderBy('id')->get(['id', 'name']);

        if ($this->option('empresa-antiga')) {
            $this->legacyCompany = (int) $this->option('empresa-antiga');
        } elseif ($companies->count() === 1) {
            $this->legacyCompany = $companies->first()->id;
        } elseif ($companies->count() > 1) {
            $this->error('O banco antigo tem várias empresas. Escolha uma com --empresa-antiga=ID:');
            $this->table(['ID', 'Empresa'], $companies->map(fn ($c) => [$c->id, $c->name])->all());

            return false;
        }

        return true;
    }

    private function legacy(string $table)
    {
        $query = $this->source->table($table);

        if ($this->legacyCompany && $this->source->getSchemaBuilder()->hasColumn($table, 'company_id')) {
            $query->where('company_id', $this->legacyCompany);
        }

        return $query;
    }

    private function columns(string $table): array
    {
        return $this->columns[$table] ??= array_values(array_diff(Schema::getColumnListing($table), ['id', 'company_id']));
    }

    private function ref(string $table, mixed $oldId): ?int
    {
        return $oldId === null ? null : ($this->map[$table][$oldId] ?? null);
    }

    private function insert(string $table, object $row, array $overrides = []): int
    {
        $data = array_intersect_key((array) $row, array_flip($this->columns($table)));
        $data = array_merge($data, $overrides, ['company_id' => $this->companyId]);

        $id = DB::table($table)->insertGetId($data);
        $this->map[$table][$row->id] = $id;

        return $id;
    }

    /**
     * @param  callable|null  $overrides  valores trocados em cada linha (ids novos)
     * @param  string|callable|null  $required  coluna que precisa ter sido importada, ou filtro
     */
    private function copy(string $table, ?callable $overrides = null, string|callable|null $required = null): void
    {
        if (! $this->source->getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $this->map[$table] ??= [];

        $this->legacy($table)->orderBy('id')->get()->each(function ($row) use ($table, $overrides, $required) {
            if (! empty($row->service_id)) {
                return;
            }
            if (is_string($required) && ! $this->ref($this->tableFor($required), $row->{$required})) {
                return;
            }
            if (is_callable($required) && ! $required($row)) {
                return;
            }

            $this->insert($table, $row, $overrides ? $overrides($row) : []);
        });
    }

    private function tableFor(string $column): string
    {
        return ['sale_id' => 'sales', 'product_id' => 'products', 'customer_id' => 'customers'][$column];
    }

    private function mapUsers(User $admin): void
    {
        $this->map['users'] = [];

        $targets = User::where('company_id', $this->companyId)->pluck('id', 'email');

        $this->legacy('users')->get(['id', 'email'])->each(function ($user) use ($targets, $admin) {
            $this->map['users'][$user->id] = $targets[mb_strtolower((string) $user->email)] ?? $admin->id;
        });
    }

    private function importCategories(): void
    {
        $this->map['categories'] = [];

        $this->legacy('categories')->where('type', '!=', 'servico')->orderBy('id')->get()->each(function ($row) {
            $existing = DB::table('categories')
                ->where('company_id', $this->companyId)
                ->where('name', $row->name)
                ->where('type', $row->type)
                ->value('id');

            if ($existing) {
                $this->map['categories'][$row->id] = $existing;
            } else {
                $this->insert('categories', $row);
            }
        });
    }

    private function importSales(): void
    {
        $this->map['sales'] = [];
        $next = DB::table('sales')->where('company_id', $this->companyId)->count();

        $this->legacy('sales')->orderBy('id')->get()->each(function ($row) use (&$next) {
            $next++;
            $this->insert('sales', $row, [
                'code' => 'V'.str_pad((string) $next, 5, '0', STR_PAD_LEFT),
                'customer_id' => $this->ref('customers', $row->customer_id),
                'user_id' => $this->ref('users', $row->user_id),
            ]);
        });
    }

    private function transactionSource(object $row): array
    {
        $tables = [
            'Sale' => 'sales',
            'Payment' => 'payments',
            'ProductExpense' => 'product_expenses',
            'Product' => 'products',
        ];

        $table = $tables[class_basename((string) ($row->source_type ?? ''))] ?? null;
        $id = $table ? $this->ref($table, $row->source_id) : null;

        return ['source_type' => $id ? $row->source_type : null, 'source_id' => $id];
    }

    private function importSettings(): void
    {
        $legacy = $this->legacy('settings')->whereIn('key', self::COPIED_SETTINGS)->pluck('value', 'key');

        foreach ($legacy as $key => $value) {
            $current = DB::table('settings')->where('company_id', $this->companyId)->where('key', $key)->value('value');
            $isDefault = $current === null || $current === (Setting::DEFAULTS[$key] ?? null) || ($key === 'initial_balance' && (float) $current == 0.0);

            if ($isDefault && $value !== null) {
                DB::table('settings')->updateOrInsert(
                    ['company_id' => $this->companyId, 'key' => $key],
                    ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
                );
                Cache::forget('setting.'.$this->companyId.'.'.$key);
            }
        }

        DB::table('settings')->updateOrInsert(
            ['company_id' => $this->companyId, 'key' => 'legacy_import_at'],
            ['value' => now()->format('d/m/Y H:i'), 'updated_at' => now(), 'created_at' => now()],
        );
    }
}
