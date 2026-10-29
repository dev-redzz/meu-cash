<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LojaTest extends TestCase
{
    use RefreshDatabase;

    private function product(User $user, array $data = []): Product
    {
        return tenant()->run($user->company, fn () => Product::create($data + [
            'name' => 'Capinha', 'barcode' => '789100', 'purchase_price' => 10, 'sale_price' => 30,
            'initial_quantity' => 5, 'quantity' => 5, 'min_stock' => 3, 'status' => 'disponivel',
        ]));
    }

    public function test_venda_baixa_estoque_e_avisa_estoque_baixo(): void
    {
        $func = User::factory()->create();
        $product = $this->product($func);

        $this->actingAs($func)->post('/sales', [
            'sale_date' => now()->toDateString(), 'payment_method' => 'pix', 'discount' => '0',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '30.00', 'unit_cost' => '10.00']],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        tenant()->run($func->company, function () use ($product) {
            $this->assertSame(3, $product->fresh()->quantity);
            $this->assertTrue($product->fresh()->isLowStock());
            $this->assertSame(1, AppNotification::where('type', 'estoque_baixo')->count());
            $this->assertSame(0, AppNotification::where('type', 'produto_vendido')->count());
            $this->assertEquals(60, (float) FinancialTransaction::sum('amount'));
        });
    }

    public function test_filtro_repor_e_aviso_no_painel(): void
    {
        $admin = User::factory()->admin()->create();
        $this->product($admin, ['name' => 'Fone', 'barcode' => '1', 'quantity' => 1]);
        $this->product($admin, ['name' => 'Cabo', 'barcode' => '2', 'quantity' => 10]);

        $this->actingAs($admin)->get('/products?repor=1')->assertOk()->assertSee('Fone')->assertDontSee('>Cabo<', false);
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('precisa de reposição');
    }

    public function test_reposicao_soma_estoque_e_recalcula_custo_medio(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->product($admin, ['quantity' => 0, 'status' => 'vendido']);

        $this->actingAs($admin)->post("/products/{$product->id}/repor", ['quantity' => 5, 'unit_cost' => '20,00', 'register_cash' => '1'])
            ->assertRedirect("/products/{$product->id}");

        tenant()->run($admin->company, function () use ($product) {
            $p = $product->fresh();
            $this->assertSame(5, $p->quantity);
            $this->assertSame(10, $p->initial_quantity);
            $this->assertEquals(15, (float) $p->purchase_price);
            $this->assertSame('disponivel', $p->status->value);
            $this->assertEquals(100, (float) FinancialTransaction::where('type', 'saida')->sum('amount'));
        });
    }
}
