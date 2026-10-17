<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutosTelasTest extends TestCase
{
    use RefreshDatabase;

    public function test_telas_de_produtos_abrem(): void
    {
        $user = User::factory()->admin()->create();

        $product = tenant()->run($user->company, function () {
            $cat = Category::create(['name' => 'Celulares', 'type' => 'produto']);
            Product::create(['name' => 'Moto G', 'category_id' => $cat->id, 'purchase_price' => 500, 'sale_price' => 800, 'initial_quantity' => 2, 'quantity' => 2, 'status' => 'disponivel']);
            Product::create(['name' => 'Galaxy A', 'purchase_price' => 400, 'sale_price' => 600, 'initial_quantity' => 1, 'quantity' => 0, 'status' => 'vendido']);

            return Product::where('name', 'Moto G')->first();
        });

        $this->actingAs($user)->get('/products')->assertOk()->assertSee('Moto G')->assertSee('Galaxy A')->assertSee('Lucro potencial');
        $this->actingAs($user)->get('/products?view=grade')->assertOk()->assertSee('product-card', false);
        $this->actingAs($user)->get('/products?status=vendido')->assertOk()->assertSee('Galaxy A')->assertDontSee('Moto G');
        $this->actingAs($user)->get('/products/'.$product->id)->assertOk()->assertSee('Despesas do produto')->assertSee('Adicionar fotos');
        $this->actingAs($user)->get('/products/create')->assertOk()->assertSee('aside-actions', false)->assertDontSee('form-footer', false);
        $this->actingAs($user)->get('/categories')->assertOk()->assertSee('Celulares');
        $this->actingAs($user)->get('/products/importar')->assertOk()->assertSee('Colunas da planilha');
        $this->actingAs($user)->get('/estoque')->assertOk();
    }
}
