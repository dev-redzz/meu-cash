<?php

namespace App\Services;

use App\Models\Product;
use App\Models\UndoSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Guarda uma cópia do que vai ser excluído por alguns minutos.
 * "Desfazer" devolve as linhas com os mesmos IDs, sem precisar de lixeira em cada tabela.
 */
class UndoService
{
    public const MINUTES = 10;

    /**
     * @param  array<string, array<int>>  $rows  tabela => ids, na ordem em que devem voltar (pais antes dos filhos)
     * @param  array<int, array{table: string, column: string}>  $relinks  colunas que o banco vai zerar ao excluir (nullOnDelete)
     * @param  array<int, string>  $files  arquivos do disco public que só podem ser apagados quando a cópia vencer
     * @param  array<int, array>  $after  ações depois de restaurar, ex.: ['recalc_product', 12]
     */
    public function capture(string $label, array $rows, array $relinks = [], array $files = [], array $after = []): UndoSnapshot
    {
        $this->purgeExpired();

        $payload = ['rows' => [], 'relinks' => [], 'after' => $after];

        foreach ($rows as $table => $ids) {
            $ids = array_values(array_filter(array_unique($ids)));
            if ($ids) {
                $payload['rows'][$table] = DB::table($table)->whereIn('id', $ids)->get()->map(fn ($row) => (array) $row)->all();
            }
        }

        foreach ($relinks as $relink) {
            $ids = DB::table($relink['table'])->where($relink['column'], $relink['value'])->pluck('id')->all();
            if ($ids) {
                $payload['relinks'][] = $relink + ['ids' => $ids];
            }
        }

        return UndoSnapshot::create([
            'user_id' => auth()->id(),
            'token' => Str::random(40),
            'label' => Str::limit($label, 250, ''),
            'payload' => $payload,
            'files' => array_values($files) ?: null,
            'expires_at' => now()->addMinutes(self::MINUTES),
        ]);
    }

    /** Mensagem de sucesso com o botão Desfazer. */
    public function flash(UndoSnapshot $snapshot, string $message): void
    {
        session()->flash('undo', ['token' => $snapshot->token, 'message' => $message]);
    }

    public function restore(string $token): string
    {
        $snapshot = UndoSnapshot::query()->where('token', $token)->first();

        if (! $snapshot || $snapshot->expires_at->isPast()) {
            throw new RuntimeException('Não dá mais para desfazer: o prazo de '.self::MINUTES.' minutos acabou.');
        }

        if ($snapshot->user_id && (int) $snapshot->user_id !== (int) auth()->id()) {
            throw new RuntimeException('Só quem excluiu pode desfazer.');
        }

        try {
            DB::transaction(function () use ($snapshot) {
                foreach ($snapshot->payload['rows'] ?? [] as $table => $rows) {
                    foreach (array_chunk($rows, 200) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                }

                foreach ($snapshot->payload['relinks'] ?? [] as $relink) {
                    DB::table($relink['table'])->whereIn('id', $relink['ids'])->whereNull($relink['column'])
                        ->update([$relink['column'] => $relink['value']]);
                }

                foreach ($snapshot->payload['after'] ?? [] as [$action, $id]) {
                    if ($action === 'recalc_product' && $product = Product::find($id)) {
                        app(ProductService::class)->recalculate($product);
                    }
                }

                $snapshot->delete();
            });
        } catch (QueryException $e) {
            report($e);
            throw new RuntimeException('Não foi possível desfazer porque algo parecido foi cadastrado depois (por exemplo, o mesmo nome ou código de barras).');
        }

        AuditService::log('exclusao_desfeita', 'Desfeito: '.$snapshot->label);

        return $snapshot->label;
    }

    /** Apaga de vez as cópias vencidas e os arquivos que estavam guardados com elas. */
    public function purgeExpired(): int
    {
        $count = 0;

        UndoSnapshot::withoutGlobalScopes()->where('expires_at', '<', now())->orderBy('id')
            ->chunkById(100, function ($snapshots) use (&$count) {
                foreach ($snapshots as $snapshot) {
                    foreach ($snapshot->files ?? [] as $path) {
                        Storage::disk('public')->delete($path);
                    }
                    $snapshot->delete();
                    $count++;
                }
            });

        return $count;
    }
}
