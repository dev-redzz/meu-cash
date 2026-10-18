<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Product;
use App\Models\UndoSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesfazerTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        return session('undo')['token'];
    }

    public function test_desfaz_exclusao_de_produto_com_despesas_e_fotos(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/products', [
            'name' => 'Moto G', 'purchase_price' => '500,00', 'sale_price' => '800,00', 'quantity' => 1, 'status' => 'disponivel',
            'register_purchase' => '0', 'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertSessionHasNoErrors();

        $product = tenant()->run($admin->company, fn () => Product::first());
        $this->actingAs($admin)->post("/products/{$product->id}/expenses", ['description' => 'Tela', 'category' => 'pecas', 'amount' => '100', 'date' => now()->toDateString(), 'register_cash' => '0']);
        $photo = tenant()->run($admin->company, fn () => Product::first()->photos[0]);

        $this->actingAs($admin)->delete("/products/{$product->id}")->assertSessionHas('undo');
        $token = $this->token();
        $this->assertSame(0, tenant()->run($admin->company, fn () => Product::count()));
        Storage::disk('public')->assertExists($photo);

        $this->actingAs($admin)->get('/products')->assertSee('Desfazer');
        $this->actingAs($admin)->post("/desfazer/{$token}")->assertSessionHas('success');

        tenant()->run($admin->company, function () use ($product) {
            $back = Product::find($product->id);
            $this->assertNotNull($back);
            $this->assertSame('Moto G', $back->name);
            $this->assertCount(1, $back->expenses);
            $this->assertEquals(100, (float) $back->expenses_total);
        });
        $this->assertSame(0, UndoSnapshot::withoutGlobalScopes()->count());

        // Só funciona uma vez
        $this->actingAs($admin)->post("/desfazer/{$token}")->assertSessionHas('error');
    }

    public function test_fotos_apagadas_quando_o_prazo_vence(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/products', [
            'name' => 'X', 'purchase_price' => '1', 'sale_price' => '2', 'quantity' => 1, 'status' => 'disponivel', 'register_purchase' => '0',
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ]);
        $product = tenant()->run($admin->company, fn () => Product::first());
        $photo = $product->photos[0];

        $this->actingAs($admin)->delete("/products/{$product->id}");
        $token = $this->token();

        $this->travel(11)->minutes();
        $this->actingAs($admin)->post("/desfazer/{$token}")->assertSessionHas('error');
        app(\App\Services\UndoService::class)->purgeExpired();
        Storage::disk('public')->assertMissing($photo);
        $this->assertSame(0, UndoSnapshot::withoutGlobalScopes()->count());
    }

    public function test_desfaz_categoria_e_devolve_aos_produtos(): void
    {
        $admin = User::factory()->admin()->create();
        [$cat, $product] = tenant()->run($admin->company, function () {
            $cat = Category::create(['name' => 'Celulares', 'type' => 'produto']);
            $p = Product::create(['name' => 'A', 'category_id' => $cat->id, 'purchase_price' => 1, 'sale_price' => 2, 'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel']);

            return [$cat, $p];
        });

        $this->actingAs($admin)->delete("/categories/{$cat->id}");
        $this->assertNull(tenant()->run($admin->company, fn () => $product->fresh()->category_id));

        $this->actingAs($admin)->post('/desfazer/'.$this->token());
        $this->assertSame($cat->id, tenant()->run($admin->company, fn () => $product->fresh()->category_id));
    }

    public function test_desfaz_despesa_e_devolve_saida_do_caixa(): void
    {
        $admin = User::factory()->admin()->create();
        $product = tenant()->run($admin->company, fn () => Product::create(['name' => 'A', 'purchase_price' => 1, 'sale_price' => 2, 'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel']));
        $this->actingAs($admin)->post("/products/{$product->id}/expenses", ['description' => 'Frete', 'category' => 'frete', 'amount' => '30', 'date' => now()->toDateString(), 'register_cash' => '1']);
        $expense = tenant()->run($admin->company, fn () => $product->expenses()->first());

        $this->actingAs($admin)->delete("/products/{$product->id}/expenses/{$expense->id}");
        $this->assertSame(0, tenant()->run($admin->company, fn () => FinancialTransaction::count()));

        $this->actingAs($admin)->post('/desfazer/'.$this->token());
        tenant()->run($admin->company, function () use ($product) {
            $this->assertSame(1, FinancialTransaction::count());
            $this->assertEquals(30, (float) $product->fresh()->expenses_total);
        });
    }

    public function test_desfaz_cliente_e_so_quem_excluiu_pode_desfazer(): void
    {
        $admin = User::factory()->admin()->create();
        $outro = User::factory()->admin()->create(['company_id' => $admin->company_id]);
        $estranho = User::factory()->admin()->create();
        $customer = tenant()->run($admin->company, fn () => Customer::create(['name' => 'Joana']));

        $this->actingAs($admin)->delete("/customers/{$customer->id}");
        $token = $this->token();

        $this->actingAs($estranho)->post("/desfazer/{$token}")->assertSessionHas('error');
        $this->actingAs($outro)->post("/desfazer/{$token}")->assertSessionHas('error');
        $this->assertNull(tenant()->run($admin->company, fn () => Customer::find($customer->id)));

        $this->actingAs($admin)->post("/desfazer/{$token}")->assertSessionHas('success');
        $this->assertSame('Joana', tenant()->run($admin->company, fn () => Customer::find($customer->id)->name));
    }
}
