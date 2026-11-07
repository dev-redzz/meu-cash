<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\OnlinePayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PagamentosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://meucash.test', 'meucash.platform_admins' => ['dono@plataforma.com'], 'billing.enabled' => false]);
        $this->admin = app(CompanyProvisioner::class)->create(['name' => 'Loja Pix'], ['name' => 'Dona', 'email' => 'dona@loja.com']);
    }

    private function venda(): array
    {
        [$customer, $product] = tenant()->run($this->admin->company, fn () => [
            Customer::create(['name' => 'Carla Cliente', 'whatsapp' => '99999990000', 'email' => 'carla@gmail.com']),
            Product::create(['name' => 'Fone', 'purchase_price' => 50, 'sale_price' => 200, 'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel']),
        ]);

        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '200']],
            'payment_method' => 'parcelado',
            'down_payment' => '0',
            'down_payment_method' => 'pix',
            'installments_count' => 2,
            'first_due_date' => now()->addDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();

        $installment = tenant()->run($this->admin->company, fn () => Installment::where('customer_id', $customer->id)->orderBy('number')->first());

        return [$customer->fresh(), $installment];
    }

    private function conectarLoja(): void
    {
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 1, 'first_name' => 'Loja', 'last_name' => 'Pix', 'email' => 'mp@loja.com'])]);

        $this->actingAs($this->admin)->put('/configuracoes/recebimentos', ['access_token' => 'APP_USR-123-abc'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
    }

    private function entrarNoPortal(Customer $customer): void
    {
        auth()->logout();
        $this->post('/cliente', ['code' => $customer->access_code])->assertRedirect(route('portal.home'));
    }

    public function test_loja_conecta_mercado_pago_e_token_fica_criptografado(): void
    {
        $this->conectarLoja();

        $raw = tenant()->run($this->admin->company, fn () => \App\Models\Setting::query()->where('key', 'mp_access_token')->value('value'));
        $this->assertNotEmpty($raw);
        $this->assertStringNotContainsString('APP_USR', $raw);

        $this->get('/configuracoes/recebimentos')->assertOk()->assertSee('Conectado')->assertSee('mp@loja.com')->assertDontSee('APP_USR-123-abc');

        $this->put('/configuracoes/recebimentos', ['access_token' => 'qualquer'])->assertSessionHasErrors('access_token');
    }

    public function test_trocar_token_descarta_pix_gerado_com_token_antigo(): void
    {
        [$customer, $installment] = $this->venda();
        $this->conectarLoja();
        $this->entrarNoPortal($customer);

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::sequence()
                ->push(['id' => 1, 'status' => 'pending', 'point_of_interaction' => ['transaction_data' => ['qr_code' => 'PIX-TESTE']]], 201)
                ->push(['id' => 2, 'status' => 'pending', 'point_of_interaction' => ['transaction_data' => ['qr_code' => 'PIX-PRODUCAO']]], 201),
            'api.mercadopago.com/users/me' => Http::response(['id' => 1, 'email' => 'mp@loja.com']),
        ]);
        $this->post("/cliente/parcelas/{$installment->id}/pix");

        auth('web')->logout();
        $this->actingAs($this->admin)->put('/configuracoes/recebimentos', ['access_token' => 'APP_USR-novo-token'])->assertSessionHasNoErrors();

        $this->entrarNoPortal($customer);
        $this->post("/cliente/parcelas/{$installment->id}/pix");
        $this->get("/cliente/parcelas/{$installment->id}/pix")->assertSee('PIX-PRODUCAO')->assertDontSee('PIX-TESTE');
    }

    public function test_token_recusado_nao_conecta(): void
    {
        Http::fake(['api.mercadopago.com/*' => Http::response(['message' => 'invalid'], 401)]);

        $this->actingAs($this->admin)->put('/configuracoes/recebimentos', ['access_token' => 'APP_USR-999'])->assertSessionHasErrors('access_token');
        $this->get('/configuracoes/recebimentos')->assertDontSee('Conectado');
    }

    public function test_cliente_paga_parcela_com_pix_e_baixa_e_automatica(): void
    {
        [$customer, $installment] = $this->venda();

        $this->entrarNoPortal($customer);
        $this->get('/cliente/minha-area')->assertOk()->assertDontSee('Pagar com Pix');

        $this->conectarLoja();
        $this->entrarNoPortal($customer);
        $this->get('/cliente/minha-area')->assertOk()->assertSee('Pagar com Pix');

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 555, 'status' => 'pending',
                'point_of_interaction' => ['transaction_data' => ['qr_code' => '00020126PIXCOPIAECOLA', 'qr_code_base64' => base64_encode('png'), 'ticket_url' => 'https://mp/ticket']],
            ], 201),
            'api.mercadopago.com/v1/payments/555' => Http::sequence()
                ->push(['id' => 555, 'status' => 'pending', 'external_reference' => 'parcela-1', 'transaction_amount' => 100.0])
                ->push(['id' => 555, 'status' => 'approved', 'external_reference' => 'parcela-1', 'transaction_amount' => 100.0, 'date_approved' => now()->toIso8601String()]),
        ]);

        $this->post("/cliente/parcelas/{$installment->id}/pix")->assertRedirect(route('portal.pix.show', $installment->id));
        $this->post("/cliente/parcelas/{$installment->id}/pix")->assertRedirect();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.mercadopago.com/v1/payments'
            && $r['payment_method_id'] === 'pix'
            && $r['transaction_amount'] == 100
            && $r['payer']['email'] === 'carla@gmail.com'
            && $r['notification_url'] === 'https://meucash.test/webhooks/mercadopago/loja/'.$this->admin->company_id
            && $r->hasHeader('X-Idempotency-Key')
            && $r->hasHeader('Authorization', 'Bearer APP_USR-123-abc'));

        $this->get("/cliente/parcelas/{$installment->id}/pix")->assertOk()->assertSee('00020126PIXCOPIAECOLA')->assertSee('Aguardando pagamento');

        $this->getJson("/cliente/parcelas/{$installment->id}/pix/situacao")->assertJson(['status' => 'pendente', 'paid' => false]);

        // Aviso do Mercado Pago: o sistema confere na API e dá baixa.
        $this->postJson('/webhooks/mercadopago/loja/'.$this->admin->company_id, ['type' => 'payment', 'data' => ['id' => '555']])->assertOk();

        tenant()->run($this->admin->company, function () use ($installment) {
            $fresh = Installment::find($installment->id);
            $this->assertSame('pago', $fresh->status->value);
            $this->assertSame('pix', $fresh->payment_method->value);
            $this->assertSame('pago', OnlinePayment::first()->status);
            $this->assertSame(1, \App\Models\Payment::where('installment_id', $installment->id)->count());
            $this->assertSame(1, \App\Models\FinancialTransaction::where('origin', 'parcela')->count());
        });

        // Aviso repetido não paga duas vezes.
        $this->postJson('/webhooks/mercadopago/loja/'.$this->admin->company_id, ['type' => 'payment', 'data' => ['id' => '555']])->assertOk();
        tenant()->run($this->admin->company, fn () => $this->assertSame(1, \App\Models\Payment::where('installment_id', $installment->id)->count()));

        $this->getJson("/cliente/parcelas/{$installment->id}/pix/situacao")->assertJson(['paid' => true]);
    }

    public function test_aviso_falso_ou_valor_diferente_nao_da_baixa(): void
    {
        [$customer, $installment] = $this->venda();
        $this->conectarLoja();
        $this->entrarNoPortal($customer);

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response(['id' => 777, 'status' => 'pending', 'point_of_interaction' => ['transaction_data' => ['qr_code' => 'X']]], 201),
            'api.mercadopago.com/v1/payments/777' => Http::response(['id' => 777, 'status' => 'approved', 'external_reference' => 'parcela-1', 'transaction_amount' => 1.0]),
            'api.mercadopago.com/v1/payments/888' => Http::response(['id' => 888, 'status' => 'approved', 'external_reference' => 'parcela-1', 'transaction_amount' => 100.0]),
        ]);
        $this->post("/cliente/parcelas/{$installment->id}/pix");

        $this->postJson('/webhooks/mercadopago/loja/'.$this->admin->company_id, ['type' => 'payment', 'data' => ['id' => '777']])->assertOk();
        $this->postJson('/webhooks/mercadopago/loja/'.$this->admin->company_id, ['type' => 'payment', 'data' => ['id' => '888']])->assertOk();
        $this->postJson('/webhooks/mercadopago/loja/'.$this->admin->company_id, ['type' => 'payment', 'data' => ['id' => 'abc']])->assertOk();

        tenant()->run($this->admin->company, fn () => $this->assertTrue(Installment::find($installment->id)->isOpen()));
    }

    public function test_cliente_nao_gera_pix_de_parcela_de_outro(): void
    {
        [$customer, $installment] = $this->venda();
        $this->conectarLoja();

        $outro = tenant()->run($this->admin->company, fn () => Customer::create(['name' => 'Outro', 'whatsapp' => '99999991111']));
        tenant()->run($this->admin->company, fn () => $outro->forceFill(['access_code' => 'ABCD-EFGH'])->save());
        $this->entrarNoPortal($outro->fresh());

        $this->post("/cliente/parcelas/{$installment->id}/pix")->assertNotFound();
    }

    /* ---------- Assinatura ---------- */

    private function ligarCobranca(): void
    {
        config(['billing.enabled' => true, 'billing.mercadopago_token' => 'APP_USR-plataforma', 'billing.starts_at' => null, 'billing.trial_days' => 14, 'billing.grace_days' => 5]);
    }

    public function test_cobranca_desligada_nao_muda_nada(): void
    {
        $this->travel(90)->days();
        $this->actingAs($this->admin)->get('/dashboard')->assertOk()->assertDontSee('teste grátis');
        $this->get('/assinatura')->assertOk()->assertSee('Grátis durante a versão beta');
    }

    public function test_teste_gratis_tolerancia_e_bloqueio(): void
    {
        $this->ligarCobranca();

        $this->actingAs($this->admin)->get('/assinatura')->assertOk()->assertSee('Teste grátis até');

        $this->travel(11)->days();
        $this->get('/dashboard')->assertSee('Seu teste grátis termina');

        $this->travel(5)->days();
        $this->get('/dashboard')->assertSee('Sua assinatura venceu');
        $this->post('/customers', ['name' => 'Na tolerância'])->assertSessionHasNoErrors();

        $this->travel(10)->days();
        $this->get('/dashboard')->assertOk()->assertSee('sistema só para consulta');
        $this->get('/customers')->assertOk();
        $this->post('/customers', ['name' => 'Bloqueado'])->assertRedirect(route('billing.show'));
        $this->assertSame(0, tenant()->run($this->admin->company, fn () => Customer::where('name', 'Bloqueado')->count()));
        $this->get('/exportar')->assertOk();
    }

    public function test_assinatura_paga_libera_e_soma_periodo(): void
    {
        $this->ligarCobranca();
        $this->travel(30)->days();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'pref-1', 'init_point' => 'https://mercadopago.com.br/checkout/pref-1'], 201),
            'api.mercadopago.com/v1/payments/9001' => Http::response(['id' => 9001, 'status' => 'approved', 'external_reference' => 'assinatura-1', 'transaction_amount' => 39.90, 'payment_method_id' => 'pix']),
        ]);

        $this->actingAs($this->admin)->post('/assinatura/pagar', ['plan' => 'mensal'])->assertRedirect('https://mercadopago.com.br/checkout/pref-1');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'checkout/preferences') && $r['external_reference'] === 'assinatura-1' && $r['items'][0]['unit_price'] == 39.9 && $r->hasHeader('Authorization', 'Bearer APP_USR-plataforma'));

        $this->postJson('/webhooks/mercadopago/plataforma', ['type' => 'payment', 'data' => ['id' => '9001']])->assertOk();

        $company = $this->admin->company->fresh();
        $this->assertSame(today()->addMonthNoOverflow()->subDay()->toDateString(), $company->paid_until->toDateString());
        $this->assertSame('pago', SubscriptionPayment::first()->status);

        // Volta do Mercado Pago com o mesmo pagamento: não soma de novo.
        $this->actingAs($this->admin->fresh())->get('/assinatura/retorno?payment_id=9001&status=approved')->assertRedirect(route('billing.show'));
        $this->assertSame($company->paid_until->toDateString(), $company->fresh()->paid_until->toDateString());

        $this->actingAs($this->admin->fresh())->get('/assinatura')->assertSee('Assinatura ativa até')->assertSee('Plano Mensal');
        $this->post('/customers', ['name' => 'Liberado'])->assertSessionHasNoErrors();
    }

    public function test_plataforma_da_cortesia_e_isenta(): void
    {
        $this->ligarCobranca();
        $owner = app(CompanyProvisioner::class)->create(['name' => 'Plataforma'], ['name' => 'Dono', 'email' => 'dono@plataforma.com']);
        $this->travel(60)->days();

        $this->actingAs($owner)->post('/customers', ['name' => 'Dono nunca paga'])->assertSessionHasNoErrors();
        $this->get('/plataforma')->assertOk()->assertSee('Isenta')->assertSee('Bloqueada');

        $this->post("/plataforma/empresas/{$this->admin->company_id}/cortesia", ['days' => 30])->assertSessionHas('success');
        $this->assertSame(today()->addDays(29)->toDateString(), $this->admin->company->fresh()->paid_until->toDateString());

        $this->patch("/plataforma/empresas/{$this->admin->company_id}/isencao")->assertSessionHas('success');
        $this->assertTrue($this->admin->company->fresh()->billing_exempt);
    }
}
