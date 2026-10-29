<?php

namespace Tests\Feature;

use App\Enums\InstallmentStatus;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\FinanceService;
use App\Support\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FluxoVendaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'id-teste',
            'services.google.client_secret' => 'segredo-teste',
        ]);
    }

    private function fakeGoogle(string $email, string $sub = '123', string $name = 'Maria Souza'): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'token']),
            'openidconnect.googleapis.com/*' => Http::response([
                'sub' => $sub, 'email' => $email, 'email_verified' => true, 'name' => $name, 'picture' => null,
            ]),
        ]);
    }

    private function googleCallback()
    {
        return $this->withSession(['google_state' => 'estado'])
            ->get('/auth/google/callback?code=abc&state=estado');
    }

    public function test_primeiro_login_google_cadastra_empresa(): void
    {
        $this->fakeGoogle('dono@gmail.com');

        $this->googleCallback()->assertRedirect(route('onboarding.create'));

        $this->post('/comecar', ['name' => 'Loja do João', 'terms' => '1'])->assertRedirect(route('dashboard'));

        $user = User::firstWhere('email', 'dono@gmail.com');
        $this->assertTrue($user->isAdmin());
        $this->assertSame('Loja do João', $user->company->name);
        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')->assertOk()->assertSee('Saldo em caixa')->assertSee('Loja do João');
    }

    public function test_estado_invalido_bloqueia_login(): void
    {
        $this->withSession(['google_state' => 'certo'])
            ->get('/auth/google/callback?code=abc&state=errado')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_convite_coloca_funcionario_na_empresa_certa(): void
    {
        $dono = User::factory()->admin()->create();

        $this->actingAs($dono)->post('/equipe/convites', ['email' => 'Ana@Gmail.com', 'role' => 'funcionario'])->assertRedirect();
        $this->assertDatabaseHas('company_invitations', ['email' => 'ana@gmail.com', 'company_id' => $dono->company_id]);

        auth()->logout();
        $this->fakeGoogle('ana@gmail.com', '999', 'Ana');
        $this->googleCallback()->assertRedirect(route('dashboard'));

        $ana = User::firstWhere('email', 'ana@gmail.com');
        $this->assertSame($dono->company_id, $ana->company_id);
        $this->assertFalse($ana->isAdmin());
        $this->assertNotNull(CompanyInvitation::withoutGlobalScopes()->first()->accepted_at);
    }

    public function test_empresas_nao_veem_dados_uma_da_outra(): void
    {
        $a = User::factory()->admin()->create();
        $b = User::factory()->admin()->create();

        tenant()->set($a->company);
        $cliente = Customer::create(['name' => 'Cliente da empresa A']);
        $produto = Product::create(['name' => 'Celular A', 'purchase_price' => 100, 'sale_price' => 200, 'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel']);
        tenant()->set(null);

        $this->actingAs($b)->get('/customers')->assertOk()->assertDontSee('Cliente da empresa A');
        $this->actingAs($b)->get("/customers/{$cliente->id}")->assertNotFound();
        $this->actingAs($b)->get("/products/{$produto->id}")->assertNotFound();

        $this->actingAs($b)->post('/sales', [
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $produto->id, 'quantity' => 1, 'unit_price' => '200']],
            'payment_method' => 'pix',
        ])->assertSessionHasErrors('items.0.product_id');

        $this->actingAs($a)->get('/customers')->assertSee('Cliente da empresa A');
    }

    public function test_venda_parcelada_separa_faturamento_recebido_e_a_receber(): void
    {
        $admin = User::factory()->admin()->create();
        tenant()->set($admin->company);
        $customer = Customer::create(['name' => 'Cliente Teste', 'whatsapp' => '99988887777']);
        $product = Product::create([
            'name' => 'Celular', 'purchase_price' => 1000, 'sale_price' => 1500,
            'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel',
        ]);

        $this->actingAs($admin)->post('/sales', [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '1.500,00']],
            'payment_method' => 'parcelado',
            'down_payment' => '500,00',
            'down_payment_method' => 'pix',
            'installments_count' => 2,
            'first_due_date' => now()->addMonth()->toDateString(),
        ])->assertRedirect();

        tenant()->set($admin->company);
        $summary = app(FinanceService::class)->summary(Period::fromRequest(new Request()));

        $this->assertEquals(1500, $summary['revenue']);
        $this->assertEquals(500, $summary['received']);
        $this->assertEquals(1000, $summary['receivable']);
        $this->assertEquals(500, $summary['profit']);
        $this->assertSame('V00001', Sale::first()->code);
        $this->assertEquals('vendido', $product->fresh()->status->value);

        $installment = Installment::first();
        $this->post("/installments/{$installment->id}/pay", ['paid_at' => now()->toDateString(), 'payment_method' => 'pix'])->assertRedirect();

        tenant()->set($admin->company);
        $this->assertEquals(InstallmentStatus::Pago, $installment->fresh()->status);
        $this->assertEquals(1000, app(FinanceService::class)->balance());
    }

    public function test_funcionario_nao_acessa_area_administrativa(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/financeiro')->assertForbidden();
        $this->actingAs($user)->get('/equipe')->assertForbidden();
        $this->actingAs($user)->get('/sales')->assertOk();
    }

    public function test_empresa_suspensa_nao_entra(): void
    {
        $user = User::factory()->admin()->create();
        $user->company->update(['active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
