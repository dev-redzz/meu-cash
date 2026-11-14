<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotaFiscalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'fiscal.client_id' => 'cid', 'fiscal.client_secret' => 'secret', 'fiscal.sandbox' => true,
            'billing.enabled' => false,
        ]);
        $this->admin = app(CompanyProvisioner::class)->create(['name' => 'Loja Fiscal'], ['name' => 'Dona', 'email' => 'dona@loja.com']);
    }

    private function fakeApi(array $extra = []): void
    {
        Http::fake($extra + [
            'auth.nuvemfiscal.com.br/*' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'api.sandbox.nuvemfiscal.com.br/cep/*' => Http::response(['codigo_ibge' => '2103208', 'municipio' => 'Codó', 'uf' => 'MA']),
            'api.sandbox.nuvemfiscal.com.br/empresas/*/nfce' => Http::response(['ambiente' => 'homologacao']),
            'api.sandbox.nuvemfiscal.com.br/empresas/*/certificado' => Http::response(['nome_razao_social' => 'LOJA FISCAL LTDA', 'not_valid_after' => now()->addYear()->toIso8601String()]),
            'api.sandbox.nuvemfiscal.com.br/empresas/*' => Http::response(['error' => ['message' => 'not found']], 404),
            'api.sandbox.nuvemfiscal.com.br/empresas' => Http::response(['cpf_cnpj' => '11222333000181'], 201),
        ]);
    }

    private function configurar(bool $auto = false): void
    {
        $this->fakeApi();

        $this->actingAs($this->admin)->put('/configuracoes/nota-fiscal', [
            'legal_name' => 'Loja Fiscal Ltda', 'trade_name' => 'Loja Fiscal', 'cnpj' => '11.222.333/0001-81', 'ie' => '12.345.678-9', 'crt' => '1',
            'email' => 'fiscal@loja.com', 'cep' => '65400-000', 'street' => 'Rua A', 'number' => '10', 'district' => 'Centro', 'city' => 'Codó', 'uf' => 'MA',
            'environment' => 'homologacao', 'series' => 1, 'next_number' => 5, 'csc_id' => 1, 'csc' => 'ABCDEF123456',
            'nature' => 'Venda de mercadoria', 'cfop' => '5102', 'csosn' => '102', 'auto' => $auto ? '1' : '0',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->post('/configuracoes/nota-fiscal/certificado', [
            'certificate' => UploadedFile::fake()->createWithContent('cert.pfx', 'PFXDATA'),
            'password' => '1234',
        ])->assertSessionHas('success');
    }

    private function venda(array $override = []): Sale
    {
        [$customer, $product] = tenant()->run($this->admin->company, fn () => [
            Customer::create(['name' => 'Carla', 'document' => '529.982.247-25']),
            Product::create(['name' => 'Fone', 'barcode' => '7891234567895', 'ncm' => '85183000', 'purchase_price' => 50, 'sale_price' => 100, 'initial_quantity' => 5, 'quantity' => 5, 'status' => 'disponivel']),
        ]);

        $this->actingAs($this->admin)->post('/sales', $override + [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '100']],
            'discount' => '10',
            'payment_method' => 'pix',
        ])->assertSessionHasNoErrors();

        return tenant()->run($this->admin->company, fn () => Sale::latest('id')->first());
    }

    public function test_configura_empresa_na_nuvem_fiscal(): void
    {
        $this->configurar();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.sandbox.nuvemfiscal.com.br/empresas' && $r->method() === 'POST'
            && $r['cpf_cnpj'] === '11222333000181' && $r['endereco']['codigo_municipio'] === '2103208' && $r['inscricao_estadual'] === '123456789');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/empresas/11222333000181/nfce') && $r['sefaz']['csc'] === 'ABCDEF123456' && $r['CRT'] === 1);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/certificado') && $r['certificado'] === base64_encode('PFXDATA') && $r['password'] === '1234');

        $raw = tenant()->run($this->admin->company, fn () => Setting::query()->where('key', 'fiscal_csc')->value('value'));
        $this->assertStringNotContainsString('ABCDEF123456', $raw);

        $this->get('/configuracoes/nota-fiscal')->assertOk()->assertSee('Pronto para emitir NFC-e')->assertDontSee('ABCDEF123456');
        $this->put('/configuracoes/nota-fiscal', ['cnpj' => '11.111.111/1111-11'])->assertSessionHasErrors('cnpj');
    }

    public function test_emite_nfce_da_venda_com_dados_corretos(): void
    {
        $this->configurar();
        $sale = $this->venda();

        $this->fakeApi([
            'api.sandbox.nuvemfiscal.com.br/nfce' => Http::response([
                'id' => 'nfc_1', 'status' => 'autorizado', 'numero' => 5, 'serie' => 1, 'chave' => str_repeat('2', 44),
                'autorizacao' => ['codigo_status' => 100, 'motivo_status' => 'Autorizado o uso da NF-e', 'numero_protocolo' => '321000000000001'],
            ]),
        ]);

        $this->get("/sales/{$sale->id}")->assertSee('Emitir NFC-e');
        $this->post("/sales/{$sale->id}/nota")->assertSessionHas('success');

        Http::assertSent(function (Request $r) {
            if ($r->url() !== 'https://api.sandbox.nuvemfiscal.com.br/nfce') {
                return false;
            }
            $inf = $r['infNFe'];

            return $r['ambiente'] === 'homologacao'
                && $inf['ide']['mod'] === 65 && $inf['ide']['nNF'] === 5 && $inf['ide']['cUF'] === 21 && $inf['ide']['cMunFG'] === '2103208'
                && $inf['emit']['CNPJ'] === '11222333000181'
                && $inf['det'][0]['prod']['NCM'] === '85183000' && $inf['det'][0]['prod']['cEAN'] === '7891234567895'
                && $inf['det'][0]['prod']['vProd'] == 200 && $inf['det'][0]['prod']['vDesc'] == 10
                && str_contains($inf['det'][0]['prod']['xProd'], 'HOMOLOGACAO')
                && $inf['det'][0]['imposto']['ICMS']['ICMSSN102']['CSOSN'] === '102'
                && $inf['total']['ICMSTot']['vNF'] == 190 && $inf['total']['ICMSTot']['vDesc'] == 10
                && $inf['pag']['detPag'][0]['tPag'] === '17' && $inf['pag']['detPag'][0]['vPag'] == 190
                && $inf['dest']['CPF'] === '52998224725';
        });

        tenant()->run($this->admin->company, function () {
            $inv = Invoice::first();
            $this->assertSame('autorizado', $inv->status);
            $this->assertSame(5, $inv->number);
            $this->assertSame('6', Setting::get('fiscal_next_homologacao'));
        });

        $this->get("/sales/{$sale->id}")->assertSee('NFC-e 000005')->assertSee('DANFE');

        // Não emite duas vezes.
        $this->post("/sales/{$sale->id}/nota");
        $this->assertSame(1, collect(Http::recorded())->filter(fn ($p) => $p[0]->url() === 'https://api.sandbox.nuvemfiscal.com.br/nfce')->count());
        $this->assertSame(1, tenant()->run($this->admin->company, fn () => Invoice::count()));

        // Excluir venda com nota autorizada é bloqueado.
        $this->delete("/sales/{$sale->id}")->assertSessionHas('error');
    }

    public function test_rejeitada_reaproveita_o_numero_e_parcelado_usa_credito_loja(): void
    {
        $this->configurar();
        $sale = $this->venda([
            'payment_method' => 'parcelado', 'down_payment' => '50', 'down_payment_method' => 'dinheiro',
            'installments_count' => 2, 'first_due_date' => now()->addMonth()->toDateString(), 'discount' => '0',
        ]);

        $this->fakeApi([
            'api.sandbox.nuvemfiscal.com.br/nfce' => Http::sequence()
                ->push(['id' => 'nfc_a', 'status' => 'rejeitado', 'autorizacao' => ['codigo_status' => 778, 'motivo_status' => 'NCM inexistente']])
                ->push(['id' => 'nfc_b', 'status' => 'autorizado', 'chave' => str_repeat('3', 44)]),
        ]);

        $this->post("/sales/{$sale->id}/nota")->assertSessionHas('error');
        $this->get("/sales/{$sale->id}")->assertSee('NCM inexistente')->assertSee('transmitir de novo');

        $this->post("/sales/{$sale->id}/nota")->assertSessionHas('success');

        $nfce = collect(Http::recorded())->map(fn ($p) => $p[0])->filter(fn ($r) => $r->url() === 'https://api.sandbox.nuvemfiscal.com.br/nfce')->values();
        $this->assertCount(2, $nfce);
        $this->assertSame(5, $nfce[0]['infNFe']['ide']['nNF']);
        $this->assertSame(5, $nfce[1]['infNFe']['ide']['nNF']);
        $pag = $nfce[1]['infNFe']['pag']['detPag'];
        $this->assertSame(['01', '05'], array_column($pag, 'tPag'));
        $this->assertEquals([50, 150], array_column($pag, 'vPag'));

        tenant()->run($this->admin->company, fn () => $this->assertSame('autorizado', Invoice::first()->status));
    }

    public function test_emissao_automatica_e_cancelamento_junto_com_a_venda(): void
    {
        $this->configurar(auto: true);

        $this->fakeApi([
            'api.sandbox.nuvemfiscal.com.br/nfce' => Http::response(['id' => 'nfc_auto', 'status' => 'autorizado', 'chave' => str_repeat('4', 44)]),
            'api.sandbox.nuvemfiscal.com.br/nfce/nfc_auto/cancelamento' => Http::response(['status' => 'registrado']),
        ]);

        $sale = $this->venda();
        tenant()->run($this->admin->company, fn () => $this->assertSame('autorizado', Invoice::where('sale_id', $sale->id)->value('status')));

        $this->post("/sales/{$sale->id}/cancel")->assertSessionMissing('error');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/nfce/nfc_auto/cancelamento') && strlen($r['justificativa']) >= 15);
        tenant()->run($this->admin->company, fn () => $this->assertSame('cancelado', Invoice::first()->status));
    }

    public function test_produto_sem_ncm_bloqueia_e_falha_automatica_vira_notificacao(): void
    {
        $this->configurar(auto: true);
        $this->fakeApi();

        $product = tenant()->run($this->admin->company, fn () => Product::create(['name' => 'Capinha', 'purchase_price' => 5, 'sale_price' => 20, 'initial_quantity' => 3, 'quantity' => 3, 'status' => 'disponivel']));
        $this->actingAs($this->admin)->post('/sales', [
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '20']],
            'payment_method' => 'dinheiro',
        ])->assertSessionHasNoErrors();

        $sale = tenant()->run($this->admin->company, fn () => Sale::latest('id')->first());
        $this->get("/sales/{$sale->id}")->assertSee('está sem NCM');
        $this->assertSame(0, tenant()->run($this->admin->company, fn () => Invoice::count()));
        $this->assertTrue(tenant()->run($this->admin->company, fn () => \App\Models\AppNotification::where('type', 'nota_fiscal')->exists()));
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/nfce'));
    }

    public function test_produto_salva_dados_fiscais(): void
    {
        $this->actingAs($this->admin)->post('/products', [
            'name' => 'Carregador', 'purchase_price' => '10', 'sale_price' => '30', 'quantity' => 2, 'status' => 'disponivel',
            'ncm' => '8504.40.10', 'cfop' => '5405', 'csosn' => '500', 'origin' => '2', 'unit' => 'un',
        ])->assertSessionHasNoErrors();

        $p = tenant()->run($this->admin->company, fn () => Product::where('name', 'Carregador')->first());
        $this->assertSame(['85044010', '5405', '500', 2, 'UN'], [$p->ncm, $p->cfop, $p->csosn, $p->origin, $p->unit]);

        $this->post('/products', ['name' => 'X', 'purchase_price' => '1', 'sale_price' => '2', 'quantity' => 1, 'status' => 'disponivel', 'ncm' => '123'])->assertSessionHasErrors('ncm');
    }
}
