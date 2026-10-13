<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Installment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\CompanyProvisioner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportacaoAntigaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legado' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false]]);
        DB::purge('legado');
        $this->criarBancoAntigo();
    }

    private function criarBancoAntigo(): void
    {
        $s = Schema::connection('legado');
        $s->create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email'); $t->string('role'); $t->timestamps(); });
        $s->create('settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->text('value')->nullable(); $t->timestamps(); });
        $s->create('categories', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('type'); $t->timestamps(); });
        $s->create('customers', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('whatsapp')->nullable(); $t->timestamps(); });
        $s->create('products', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('category_id')->nullable(); $t->string('name'); $t->decimal('purchase_price'); $t->decimal('sale_price'); $t->integer('initial_quantity'); $t->integer('quantity'); $t->string('status'); $t->json('photos')->nullable(); $t->timestamps(); });
        $s->create('product_expenses', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('product_id'); $t->string('description'); $t->string('category'); $t->decimal('amount'); $t->date('date'); $t->timestamps(); });
        $s->create('sales', function (Blueprint $t) { $t->id(); $t->string('code')->nullable(); $t->unsignedBigInteger('customer_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->date('sale_date'); $t->decimal('subtotal'); $t->decimal('total'); $t->decimal('cost_total'); $t->decimal('profit'); $t->decimal('down_payment'); $t->string('payment_method'); $t->integer('installments_count'); $t->string('status'); $t->timestamps(); });
        $s->create('sale_items', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('sale_id'); $t->unsignedBigInteger('product_id')->nullable(); $t->string('description'); $t->integer('quantity'); $t->decimal('unit_price'); $t->decimal('unit_cost'); $t->decimal('total'); $t->timestamps(); });
        $s->create('installments', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('sale_id')->nullable(); $t->unsignedBigInteger('service_id')->nullable(); $t->unsignedBigInteger('customer_id')->nullable(); $t->integer('number'); $t->integer('total_count'); $t->decimal('amount'); $t->date('due_date'); $t->string('status'); $t->date('paid_at')->nullable(); $t->string('payment_method')->nullable(); $t->timestamps(); });
        $s->create('payments', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('installment_id')->nullable(); $t->unsignedBigInteger('sale_id')->nullable(); $t->unsignedBigInteger('service_id')->nullable(); $t->unsignedBigInteger('customer_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->decimal('amount'); $t->date('paid_at'); $t->string('payment_method'); $t->timestamps(); });
        $s->create('financial_transactions', function (Blueprint $t) { $t->id(); $t->string('type'); $t->string('origin'); $t->string('description'); $t->decimal('amount'); $t->date('date'); $t->string('payment_method')->nullable(); $t->string('source_type')->nullable(); $t->unsignedBigInteger('source_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps(); });

        $db = DB::connection('legado');
        $now = now();
        $db->table('users')->insert(['id' => 1, 'name' => 'Admin', 'email' => 'admin@fah.com', 'role' => 'admin']);
        $db->table('settings')->insert([['key' => 'initial_balance', 'value' => '500'], ['key' => 'company_name', 'value' => 'FAH']]);
        $db->table('categories')->insert([['id' => 1, 'name' => 'Celulares', 'type' => 'produto'], ['id' => 2, 'name' => 'Conserto', 'type' => 'servico']]);
        $db->table('customers')->insert(['id' => 7, 'name' => 'Cliente Antigo', 'whatsapp' => '99999999999', 'created_at' => $now]);
        $db->table('products')->insert(['id' => 3, 'category_id' => 1, 'name' => 'iPhone 11', 'purchase_price' => 1000, 'sale_price' => 1600, 'initial_quantity' => 1, 'quantity' => 0, 'status' => 'vendido', 'photos' => '["produtos/x.jpg"]']);
        $db->table('product_expenses')->insert(['product_id' => 3, 'description' => 'Tela', 'category' => 'peca', 'amount' => 100, 'date' => '2026-09-01']);
        $db->table('sales')->insert(['id' => 5, 'code' => 'V00001', 'customer_id' => 7, 'user_id' => 1, 'sale_date' => '2026-09-10', 'subtotal' => 1600, 'total' => 1600, 'cost_total' => 1100, 'profit' => 500, 'down_payment' => 600, 'payment_method' => 'parcelado', 'installments_count' => 2, 'status' => 'concluida']);
        $db->table('sale_items')->insert(['sale_id' => 5, 'product_id' => 3, 'description' => 'iPhone 11', 'quantity' => 1, 'unit_price' => 1600, 'unit_cost' => 1100, 'total' => 1600]);
        $db->table('installments')->insert([
            ['id' => 1, 'sale_id' => 5, 'service_id' => null, 'customer_id' => 7, 'number' => 1, 'total_count' => 2, 'amount' => 500, 'due_date' => '2026-10-10', 'status' => 'pago', 'paid_at' => '2026-10-10', 'payment_method' => 'pix'],
            ['id' => 2, 'sale_id' => 5, 'service_id' => null, 'customer_id' => 7, 'number' => 2, 'total_count' => 2, 'amount' => 500, 'due_date' => '2026-11-10', 'status' => 'pendente', 'paid_at' => null, 'payment_method' => null],
            ['id' => 3, 'sale_id' => null, 'service_id' => 9, 'customer_id' => 7, 'number' => 1, 'total_count' => 1, 'amount' => 80, 'due_date' => '2026-10-01', 'status' => 'pendente', 'paid_at' => null, 'payment_method' => null],
        ]);
        $db->table('payments')->insert(['id' => 4, 'installment_id' => 1, 'sale_id' => 5, 'customer_id' => 7, 'user_id' => 1, 'amount' => 500, 'paid_at' => '2026-10-10', 'payment_method' => 'pix']);
        $db->table('financial_transactions')->insert([
            ['type' => 'entrada', 'origin' => 'venda', 'description' => 'Entrada V00001', 'amount' => 600, 'date' => '2026-09-10', 'source_type' => 'App\\Models\\Sale', 'source_id' => 5, 'user_id' => 1],
            ['type' => 'entrada', 'origin' => 'parcela', 'description' => 'Parcela 1/2', 'amount' => 500, 'date' => '2026-10-10', 'source_type' => 'App\\Models\\Payment', 'source_id' => 4, 'user_id' => 1],
            ['type' => 'entrada', 'origin' => 'servico', 'description' => 'Serviço', 'amount' => 80, 'date' => '2026-09-12', 'source_type' => 'App\\Models\\Service', 'source_id' => 9, 'user_id' => 1],
        ]);
    }

    public function test_importa_dados_do_sistema_antigo(): void
    {
        $admin = app(CompanyProvisioner::class)->create(['name' => 'Loja Nova'], ['name' => 'Marcs', 'email' => 'marcs@gmail.com']);

        tenant()->set($admin->company);
        $atual = Customer::create(['name' => 'Cliente Novo']);
        $produto = Product::create(['name' => 'Capa', 'purchase_price' => 5, 'sale_price' => 20, 'initial_quantity' => 5, 'quantity' => 5, 'status' => 'disponivel']);
        tenant()->set(null);
        $this->actingAs($admin)->post('/sales', [
            'customer_id' => $atual->id, 'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $produto->id, 'quantity' => 1, 'unit_price' => '20']],
            'payment_method' => 'pix',
        ])->assertSessionHasNoErrors();

        $this->artisan('meucash:importar-antigo', ['email' => 'Marcs@gmail.com'])
            ->expectsConfirmation('Importar esses dados para a empresa acima?', 'yes')
            ->assertSuccessful();

        tenant()->set($admin->company);
        $sale = Sale::where('total', 1600)->firstOrFail();
        $this->assertSame('V00002', $sale->code);
        $this->assertSame('Cliente Antigo', $sale->customer->name);
        $this->assertSame($admin->id, $sale->user_id);
        $this->assertCount(1, $sale->items);
        $this->assertSame('iPhone 11', $sale->items->first()->product->name);
        $this->assertNull($sale->items->first()->product->photos);
        $this->assertSame(2, Installment::count());
        $this->assertSame(1, $sale->payments()->count());
        $this->assertEquals(500, (float) Setting::get('initial_balance'));
        $this->assertEquals(500 + 20 + 600 + 500 + 80, app(\App\Services\FinanceService::class)->balance());
        $this->assertSame(0, DB::table('categories')->where('company_id', $admin->company_id)->where('type', 'servico')->count());
        tenant()->set(null);

        $this->actingAs($admin)->get("/sales/{$sale->id}")->assertOk()->assertSee('iPhone 11');

        $this->artisan('meucash:importar-antigo', ['email' => 'marcs@gmail.com'])->assertFailed();
    }

    public function test_recusa_email_que_nao_e_administrador(): void
    {
        $this->artisan('meucash:importar-antigo', ['email' => 'ninguem@gmail.com'])->assertFailed();
    }
}
