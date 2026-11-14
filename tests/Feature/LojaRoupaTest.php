<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleExchange;
use App\Models\User;
use App\Services\CompanyProvisioner;
use App\Support\Barcode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LojaRoupaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['billing.enabled' => false, 'fiscal.client_id' => null]);
        $this->admin = app(CompanyProvisioner::class)->create(['name' => 'Boutique da Ana'], ['name' => 'Ana', 'email' => 'ana@loja.com']);
    }

    private function in(callable $fn): mixed
    {
        return tenant()->run($this->admin->company, $fn);
    }

    private function produto(string $name, float $price, int $qty = 5, array $extra = []): Product
    {
        return $this->in(fn () => Product::create($extra + ['name' => $name, 'purchase_price' => $price / 2, 'sale_price' => $price, 'initial_quantity' => $qty, 'quantity' => $qty, 'status' => 'disponivel']));
    }

    private function vender(array $items, array $extra = []): Sale
    {
        $this->actingAs($this->admin)->post('/sales', $extra + [
            'sale_date' => now()->toDateString(),
            'items' => $items,
            'payment_method' => 'pix',
        ])->assertSessionHasNoErrors();

        return $this->in(fn () => Sale::with('items')->latest('id')->first());
    }

    /* ---------- Troca ---------- */

    public function test_troca_de_tamanho_com_cliente_pagando_diferenca(): void
    {
        $m = $this->produto('Vestido M', 100);
        $g = $this->produto('Vestido G', 120);
        $sale = $this->vender([['product_id' => $m->id, 'quantity' => 1, 'unit_price' => '100']]);

        $this->get("/sales/{$sale->id}/troca")->assertOk()->assertSee('O que o cliente devolveu')->assertSee('Vestido M');

        $this->post("/sales/{$sale->id}/troca", [
            'returns' => [$sale->items[0]->id => 1],
            'added' => [['product_id' => $g->id, 'quantity' => 1, 'unit_price' => '120,00']],
            'settlement' => 'agora',
            'payment_method' => 'dinheiro',
            'notes' => 'Trocou M pelo G',
        ])->assertRedirect(route('sales.show', $sale))->assertSessionHas('success');

        $this->in(function () use ($sale, $m, $g) {
            $sale->refresh()->load('items');
            $this->assertEquals(120, (float) $sale->total);
            $this->assertEquals(60, (float) $sale->profit);
            $this->assertSame(['Vestido G'], $sale->items->pluck('description')->all());
            $this->assertSame(5, $m->fresh()->quantity);
            $this->assertSame(4, $g->fresh()->quantity);
            $this->assertEquals(120, (float) Payment::where('sale_id', $sale->id)->sum('amount'));
            $this->assertEquals(20, (float) FinancialTransaction::where('type', 'entrada')->where('description', 'like', 'Diferença da troca%')->sum('amount'));
            $this->assertSame(1, SaleExchange::count());
        });

        $this->get("/sales/{$sale->id}")->assertSee('Trocas')->assertSee('Cliente pagou R$ 20,00')->assertSee('Trocou M pelo G');
    }

    public function test_troca_com_devolucao_de_dinheiro_e_cancelamento_desfaz_tudo(): void
    {
        $blusa = $this->produto('Blusa', 80);
        $saia = $this->produto('Saia', 50);
        $sale = $this->vender([['product_id' => $blusa->id, 'quantity' => 2, 'unit_price' => '80']]);

        $this->post("/sales/{$sale->id}/troca", [
            'returns' => [$sale->items[0]->id => 1],
            'added' => [['product_id' => $saia->id, 'quantity' => 1, 'unit_price' => '50']],
            'settlement' => 'agora',
            'payment_method' => 'pix',
        ])->assertSessionHas('success');

        $this->in(function () use ($sale, $blusa) {
            $sale->refresh();
            $this->assertEquals(130, (float) $sale->total);
            $this->assertSame(1, $sale->items()->where('description', 'Blusa')->value('quantity'));
            $this->assertSame(4, $blusa->fresh()->quantity);
            $this->assertEquals(-30, (float) Payment::where('sale_id', $sale->id)->where('notes', 'Devolução da troca')->value('amount'));
            $this->assertEquals(30, (float) FinancialTransaction::where('type', 'saida')->sum('amount'));
        });

        // Cancelar a venda devolve tudo ao estoque e zera o caixa da venda.
        $this->post("/sales/{$sale->id}/cancel")->assertSessionHas('success');
        $this->in(function () use ($blusa, $saia) {
            $this->assertSame(5, $blusa->fresh()->quantity);
            $this->assertSame(5, $saia->fresh()->quantity);
            $this->assertSame(0, FinancialTransaction::count());
        });
    }

    public function test_troca_no_crediario_abate_ou_soma_nas_parcelas(): void
    {
        $customer = $this->in(fn () => Customer::create(['name' => 'Clara']));
        $calca = $this->produto('Calça 40', 150);
        $short = $this->produto('Short 40', 60);
        $jaqueta = $this->produto('Jaqueta', 200);

        $sale = $this->vender([['product_id' => $calca->id, 'quantity' => 1, 'unit_price' => '150']], [
            'customer_id' => $customer->id, 'payment_method' => 'parcelado', 'down_payment' => '0', 'down_payment_method' => 'dinheiro',
            'installments_count' => 3, 'first_due_date' => now()->addMonth()->toDateString(),
        ]);

        // Troca por algo mais barato: abate R$ 90 das parcelas, a partir da última.
        $this->post("/sales/{$sale->id}/troca", [
            'returns' => [$sale->items[0]->id => 1],
            'added' => [['product_id' => $short->id, 'quantity' => 1, 'unit_price' => '60']],
            'settlement' => 'parcelas',
        ])->assertSessionHas('success');

        $this->in(function () use ($sale) {
            $amounts = Installment::where('sale_id', $sale->id)->orderBy('number')->get()->map(fn ($i) => [(float) $i->amount, $i->status->value])->all();
            $this->assertEquals([[50, 'pendente'], [10, 'pendente'], [0, 'cancelado']], $amounts);
            $this->assertEquals(60, (float) Sale::find($sale->id)->total);
            $this->assertSame(0, FinancialTransaction::count());
        });

        // Troca por algo mais caro: soma na última parcela em aberto.
        $sale->refresh()->load('items');
        $this->post("/sales/{$sale->id}/troca", [
            'returns' => [$sale->items[0]->id => 1],
            'added' => [['product_id' => $jaqueta->id, 'quantity' => 1, 'unit_price' => '200']],
            'settlement' => 'parcelas',
        ])->assertSessionHas('success');

        $this->in(function () use ($sale) {
            $open = Installment::where('sale_id', $sale->id)->where('status', 'pendente')->orderBy('number')->pluck('amount')->map(fn ($v) => (float) $v)->all();
            $this->assertEquals([50, 150], $open);
            $this->assertEquals(200, (float) Sale::find($sale->id)->openAmount());
        });
    }

    public function test_troca_valida_quantidades_e_estoque(): void
    {
        $a = $this->produto('Camisa', 70, 1);
        $b = $this->produto('Boné', 40, 0, ['status' => 'vendido']);
        $sale = $this->vender([['product_id' => $a->id, 'quantity' => 1, 'unit_price' => '70']]);
        $item = $sale->items[0]->id;

        $this->post("/sales/{$sale->id}/troca", ['returns' => [$item => 2], 'settlement' => 'agora'])->assertSessionHasErrors();
        $this->post("/sales/{$sale->id}/troca", ['returns' => [$item => 0], 'settlement' => 'agora'])->assertSessionHasErrors('returns');
        $this->post("/sales/{$sale->id}/troca", ['added' => [['product_id' => $b->id, 'quantity' => 1, 'unit_price' => '40']], 'settlement' => 'agora'])->assertSessionHasErrors('added.0.product_id');
        $this->post("/sales/{$sale->id}/troca", ['returns' => [$item => 1], 'settlement' => 'agora'])->assertSessionHasErrors('returns');
        $this->assertSame(0, $this->in(fn () => SaleExchange::count()));
    }

    /* ---------- Etiquetas ---------- */

    public function test_code128_e_ean_interno(): void
    {
        $this->assertSame([104, 48, 42, 42, 17, 18, 19, 35, 55, 106], Barcode::codes('PJJ123C'));
        $this->assertSame([105, 12, 34, 56, 78, 47, 106], Barcode::codes('12345678'));
        $ean = Barcode::internalEan(7, 42);
        $this->assertMatchesRegularExpression('/^2\d{12}$/', $ean);
        $this->assertStringContainsString('<svg', Barcode::svg($ean));
    }

    public function test_etiquetas_geram_codigo_e_pagina_de_impressao(): void
    {
        $com = $this->produto('Blusa Floral', 89.9, 3, ['barcode' => '7891000100103']);
        $sem = $this->produto('Saia Jeans', 119.9, 2);

        $this->actingAs($this->admin)->get('/etiquetas?ids='.$sem->id)->assertOk()->assertSee('Saia Jeans')->assertSee('será gerado');

        $res = $this->get('/etiquetas/imprimir?'.http_build_query(['qty' => [$com->id => 2, $sem->id => 1], 'layout' => 'a4-21', 'price' => 1, 'store' => 1]));
        $res->assertOk()->assertSee('Imprimir 3 etiquetas')->assertSee('R$ 89,90')->assertSee('7891000100103')->assertSee('Boutique da Ana')->assertSee('1 produto(s) ganharam');
        $this->assertSame(3, substr_count($res->getContent(), 'class="label"'));

        $code = $this->in(fn () => $sem->fresh()->barcode);
        $this->assertMatchesRegularExpression('/^2\d{12}$/', $code);
        $res->assertSee($code);

        $this->get('/etiquetas/imprimir?'.http_build_query(['qty' => [$com->id => 0]]))->assertStatus(422);
    }

    /* ---------- Catálogo ---------- */

    public function test_catalogo_publico_mostra_so_produtos_disponiveis(): void
    {
        $this->produto('Vestido Longo', 199.9, 2);
        $this->produto('Esgotado', 50, 0, ['status' => 'vendido']);
        $this->produto('Escondido', 30, 3, ['in_catalog' => false]);
        $this->produto('Sem Preço', 0, 3);

        $this->get('/loja/boutique-da-ana')->assertNotFound();

        $this->actingAs($this->admin)->get('/configuracoes/catalogo')->assertOk()->assertSee('boutique-da-ana');
        $this->put('/configuracoes/catalogo', ['enabled' => '1', 'slug' => 'Boutique da Ana', 'whatsapp' => '(99) 98888-7777', 'intro' => 'Entregamos em Codó'])->assertSessionHas('success');
        $this->assertSame('boutique-da-ana', $this->admin->company->fresh()->catalog_slug);

        auth()->logout();
        $page = $this->get('/loja/boutique-da-ana')->assertOk()->assertSee('Boutique da Ana')->assertSee('Entregamos em Codó')->assertSee('Vestido Longo')->assertSee('wa.me/5599988887777', false);
        $page->assertDontSee('Esgotado')->assertDontSee('Escondido')->assertDontSee('Sem Preço')->assertDontSee('purchase_price');

        $outra = app(CompanyProvisioner::class)->create(['name' => 'Outra'], ['name' => 'Bia', 'email' => 'bia@x.com']);
        $this->actingAs($outra)->put('/configuracoes/catalogo', ['enabled' => '1', 'slug' => 'boutique-da-ana', 'whatsapp' => '99999999999'])->assertSessionHasErrors('slug');
    }

    public function test_produto_pode_sair_do_catalogo(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'Peça interna', 'purchase_price' => '10', 'sale_price' => '30', 'quantity' => 2, 'status' => 'disponivel', 'in_catalog' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($this->in(fn () => Product::where('name', 'Peça interna')->first()->in_catalog));
    }
}
