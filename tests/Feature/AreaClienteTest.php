<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaClienteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = app(CompanyProvisioner::class)->create(['name' => 'Loja Portal'], ['name' => 'Dono', 'email' => 'dono@gmail.com']);
    }

    private function venderPara(string $nome, string $produto): array
    {
        [$customer, $product] = tenant()->run($this->admin->company, fn () => [
            Customer::create(['name' => $nome, 'whatsapp' => '99999990000']),
            Product::create(['name' => $produto, 'purchase_price' => 100, 'sale_price' => 300, 'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel']),
        ]);

        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '300']],
            'payment_method' => 'parcelado',
            'down_payment' => '100',
            'down_payment_method' => 'pix',
            'installments_count' => 2,
            'first_due_date' => now()->addMonth()->toDateString(),
        ])->assertSessionHasNoErrors();

        auth()->logout();

        return [$customer->fresh(), tenant()->run($this->admin->company, fn () => Sale::where('customer_id', $customer->id)->first())];
    }

    public function test_cliente_entra_com_codigo_e_ve_so_o_que_e_dele(): void
    {
        [$ana, $saleAna] = $this->venderPara('Ana Cliente', 'iPhone da Ana');
        [$bia, $saleBia] = $this->venderPara('Bia Cliente', 'Samsung da Bia');

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ana->access_code);
        $this->assertNotSame($ana->access_code, $bia->access_code);

        $this->post('/cliente', ['code' => strtolower(str_replace('-', '', $ana->access_code))])->assertRedirect(route('portal.home'));

        $this->get('/cliente/minha-area')->assertOk()
            ->assertSee('Olá, Ana')
            ->assertSee('iPhone da Ana')
            ->assertSee($saleAna->code)
            ->assertDontSee('Samsung da Bia')
            ->assertDontSee('Bia Cliente');

        $this->get("/cliente/compras/{$saleAna->id}/comprovante")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/cliente/compras/{$saleBia->id}/comprovante")->assertNotFound();
    }

    public function test_codigo_errado_e_limite_de_tentativas(): void
    {
        $this->get('/cliente/minha-area')->assertRedirect(route('portal.login'));

        foreach (range(1, 5) as $i) {
            $this->post('/cliente', ['code' => 'AAAA-AAAA'])->assertSessionHasErrors('code');
        }
        $this->post('/cliente', ['code' => 'AAAA-AAAA'])->assertSessionHasErrorsIn('default', ['code']);
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('code'));
    }

    public function test_novo_codigo_derruba_acesso_antigo(): void
    {
        [$ana] = $this->venderPara('Ana Cliente', 'iPhone');
        $old = $ana->access_code;

        $this->post('/cliente', ['code' => $old]);
        $this->get('/cliente/minha-area')->assertOk();

        $this->actingAs($this->admin)->post("/customers/{$ana->id}/codigo")->assertRedirect();
        auth()->logout();

        $this->get('/cliente/minha-area')->assertRedirect(route('portal.login'));
        $this->post('/cliente', ['code' => $old])->assertSessionHasErrors('code');
    }

    public function test_empresa_suspensa_bloqueia_area_do_cliente(): void
    {
        [$ana] = $this->venderPara('Ana Cliente', 'iPhone');
        $this->admin->company->update(['active' => false]);

        $this->post('/cliente', ['code' => $ana->access_code])->assertSessionHasErrors('code');
    }

    public function test_codigo_aparece_na_ficha_e_no_comprovante(): void
    {
        [$ana, $sale] = $this->venderPara('Ana Cliente', 'iPhone');

        $this->actingAs($this->admin)->get("/customers/{$ana->id}")->assertOk()->assertSee($ana->access_code);
        $this->actingAs($this->admin)->get("/customers/{$ana->id}/acesso/whatsapp")->assertRedirectContains('wa.me/55');
    }
}
