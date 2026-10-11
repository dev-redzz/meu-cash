<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use ZipArchive;

class NovasFuncoesTest extends TestCase
{
    use RefreshDatabase;

    private function venda(User $user, array $extra = []): Sale
    {
        tenant()->set($user->company);
        $customer = Customer::create(['name' => 'Cliente Recibo', 'whatsapp' => '99988887777']);
        $product = Product::create(['name' => 'Fone X', 'barcode' => '789100', 'purchase_price' => 50, 'sale_price' => 200, 'initial_quantity' => 5, 'quantity' => 5, 'status' => 'disponivel']);
        tenant()->set(null);

        $this->actingAs($user)->post('/sales', array_merge([
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '200']],
            'payment_method' => 'pix',
        ], $extra))->assertSessionHasNoErrors();

        tenant()->set($user->company);
        $sale = Sale::latest('id')->first();
        tenant()->set(null);

        return $sale;
    }

    public function test_painel_da_plataforma_so_para_o_dono(): void
    {
        $dono = User::factory()->admin()->create(['email' => 'dono@meucash.com']);
        $cliente = User::factory()->admin()->create();
        config(['meucash.platform_admins' => ['dono@meucash.com']]);

        $this->actingAs($cliente)->get('/plataforma')->assertNotFound();
        $this->actingAs($dono)->get('/plataforma')->assertOk()->assertSee($cliente->company->name);

        $this->actingAs($dono)->patch("/plataforma/empresas/{$cliente->company_id}")->assertRedirect();
        $this->assertFalse($cliente->company->fresh()->active);
        $this->actingAs($cliente->fresh())->get('/dashboard')->assertRedirect(route('login'));

        $this->actingAs($dono)->patch("/plataforma/empresas/{$dono->company_id}")->assertSessionHas('error');
        $this->assertTrue($dono->company->fresh()->active);
    }

    public function test_recibo_pdf_link_publico_e_whatsapp(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = $this->venda($admin);

        $this->actingAs($admin)->get("/sales/{$sale->id}/recibo")->assertOk()->assertHeader('content-type', 'application/pdf');

        $whats = $this->actingAs($admin)->get("/sales/{$sale->id}/recibo/whatsapp");
        $whats->assertRedirectContains('wa.me/5599988887777');
        $this->assertStringContainsString(rawurlencode('/recibo/'.$sale->id), $whats->headers->get('Location'));

        auth()->logout();
        $this->get(URL::temporarySignedRoute('receipts.public', now()->addDay(), ['sale' => $sale->id]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/recibo/{$sale->id}")->assertForbidden();
    }

    public function test_exportacao_traz_so_dados_da_empresa(): void
    {
        $a = User::factory()->admin()->create();
        $b = User::factory()->admin()->create();
        tenant()->set($b->company);
        Customer::create(['name' => 'Cliente Secreto da B']);
        tenant()->set(null);
        $this->venda($a);

        $csv = $this->actingAs($a)->get('/exportar/clientes')->assertOk()->getContent();
        $this->assertStringContainsString('Cliente Recibo', $csv);
        $this->assertStringNotContainsString('Cliente Secreto da B', $csv);

        $response = $this->actingAs($a)->get('/exportar/tudo')->assertOk();
        $zip = new ZipArchive();
        $zip->open($response->baseResponse->getFile()->getPathname());
        $this->assertSame(7, $zip->numFiles);
        $this->assertStringContainsString('V00001', $zip->getFromName('vendas.csv'));
        $this->assertStringContainsString('400,00', $zip->getFromName('vendas.csv'));
        $zip->close();

        $this->actingAs(User::factory()->create(['company_id' => $a->company_id]))->get('/exportar/tudo')->assertForbidden();
    }

    public function test_importa_planilha_e_rejeita_tudo_se_houver_erro(): void
    {
        $admin = User::factory()->admin()->create();

        $ok = "\xEF\xBB\xBFnome;categoria;codigo_barras;quantidade;preco_compra;preco_venda;data_compra\r\n"
            ."Capinha;Acessórios Novos;111;10;8,00;25,00;05/10/2026\r\n"
            ."Cabo USB-C;;;;1.200,00;1.500,00;\r\n";
        $this->actingAs($admin)->post('/products/importar', ['file' => UploadedFile::fake()->createWithContent('p.csv', $ok)])
            ->assertRedirect(route('products.index'))->assertSessionHas('success');

        tenant()->set($admin->company);
        $this->assertSame(2, Product::count());
        $cabo = Product::firstWhere('name', 'Cabo USB-C');
        $this->assertEquals(1500, $cabo->sale_price);
        $this->assertSame(1, $cabo->quantity);
        $this->assertSame('Acessórios Novos', Product::firstWhere('barcode', '111')->category->name);
        tenant()->set(null);

        $ruim = "nome;preco_venda;codigo_barras\nBom;10;222\n;10;\nDuplicado;5;111\n";
        $this->actingAs($admin)->post('/products/importar', ['file' => UploadedFile::fake()->createWithContent('r.csv', $ruim)])
            ->assertSessionHas('import_errors', [3 => 'nome vazio', 4 => 'código de barras 111 já cadastrado']);

        tenant()->set($admin->company);
        $this->assertSame(2, Product::count());
        tenant()->set(null);

        $this->actingAs($admin)->get('/products/importar/modelo')->assertOk()->assertSee('preco_venda');
    }

    public function test_codigo_de_barras_unico_por_empresa(): void
    {
        $a = User::factory()->admin()->create();
        $b = User::factory()->admin()->create();
        $dados = ['name' => 'Item', 'barcode' => '555', 'purchase_price' => '1', 'sale_price' => '2', 'quantity' => 1, 'status' => 'disponivel'];

        $this->actingAs($a)->post('/products', $dados)->assertSessionHasNoErrors();
        $this->actingAs($a)->post('/products', $dados)->assertSessionHasErrors('barcode');
        $this->actingAs($b)->post('/products', $dados)->assertSessionHasNoErrors();
        $this->actingAs($a)->get('/products?q=555')->assertSee('Item');
    }

    public function test_comissao_gravada_e_painel_de_vendedores(): void
    {
        $admin = User::factory()->admin()->create(['monthly_goal' => 1000]);
        $vendedor = User::factory()->create(['company_id' => $admin->company_id, 'commission_rate' => 5, 'monthly_goal' => 800, 'name' => 'Rafa Vendedor']);

        $sale = $this->venda($admin, ['user_id' => $vendedor->id]);
        $this->assertSame($vendedor->id, $sale->user_id);
        $this->assertEquals(20, $sale->commission);

        $this->actingAs($admin)->get('/vendedores')->assertOk()->assertSee('Rafa Vendedor')->assertSee('R$ 20,00')->assertSee('50%');

        $this->actingAs($admin)->patch("/equipe/{$vendedor->id}", ['commission_rate' => '7,5', 'monthly_goal' => '2.000,00'])->assertSessionHasNoErrors();
        $vendedor->refresh();
        $this->assertEquals(7.5, $vendedor->commission_rate);
        $this->assertEquals(2000, $vendedor->monthly_goal);
        $this->assertEquals(20, $sale->fresh()->commission);

        $this->actingAs($vendedor)->get('/dashboard')->assertOk()->assertSee('Sua meta neste mês');
        $this->actingAs($vendedor)->get('/vendedores')->assertForbidden();
    }

    public function test_funcionario_nao_escolhe_outro_vendedor(): void
    {
        $admin = User::factory()->admin()->create();
        $func = User::factory()->create(['company_id' => $admin->company_id]);

        $sale = $this->venda($func, ['user_id' => $admin->id]);
        $this->assertSame($func->id, $sale->user_id);
    }

    public function test_vendedor_de_outra_empresa_e_recusado(): void
    {
        $admin = User::factory()->admin()->create();
        $estranho = User::factory()->create();

        tenant()->set($admin->company);
        $product = Product::create(['name' => 'P', 'purchase_price' => 1, 'sale_price' => 2, 'initial_quantity' => 1, 'quantity' => 1, 'status' => 'disponivel']);
        tenant()->set(null);

        $this->actingAs($admin)->post('/sales', [
            'sale_date' => now()->toDateString(), 'user_id' => $estranho->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '2']], 'payment_method' => 'pix',
        ])->assertSessionHasErrors('user_id');
    }
}
