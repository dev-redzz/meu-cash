<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscaTest extends TestCase
{
    use RefreshDatabase;

    public function test_busca_so_na_propria_empresa(): void
    {
        $a = User::factory()->admin()->create();
        $b = User::factory()->admin()->create();

        tenant()->run($a->company, function () {
            Customer::create(['name' => 'Joana Teste']);
            Product::create(['name' => 'iPhone Joana', 'purchase_price' => 1, 'sale_price' => 2, 'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel']);
        });

        $this->actingAs($a)->get('/buscar?q=joana')->assertOk()->assertSee('Joana Teste')->assertSee('iPhone Joana');
        $this->actingAs($b)->get('/buscar?q=joana')->assertOk()->assertDontSee('Joana Teste')->assertSee('Nada encontrado');
        $this->actingAs($a)->get('/buscar')->assertOk();
    }
}
