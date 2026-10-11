<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelasTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_as_telas_abrem(): void
    {
        $admin = app(CompanyProvisioner::class)->create(['name' => 'Loja Teste'], ['name' => 'Dono Teste', 'email' => 'dono@gmail.com']);

        tenant()->set($admin->company);
        $customer = Customer::create(['name' => 'Cliente Um', 'whatsapp' => '99988887777']);
        $product = Product::create(['name' => 'Fone', 'purchase_price' => 50, 'sale_price' => 120, 'initial_quantity' => 5, 'quantity' => 5, 'status' => 'disponivel']);
        tenant()->set(null);

        $this->actingAs($admin)->post('/sales', [
            'customer_id' => $customer->id,
            'sale_date' => now()->subDays(40)->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => '120']],
            'payment_method' => 'parcelado',
            'down_payment' => '40',
            'down_payment_method' => 'pix',
            'installments_count' => 2,
            'first_due_date' => now()->subDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();

        tenant()->set($admin->company);
        $sale = Sale::first();
        $installment = $sale->installments()->first();
        tenant()->set(null);

        $paginas = [
            '/dashboard', '/dashboard?periodo=ano', '/dashboard?periodo=personalizado&inicio=2026-01-01&fim=2026-12-31',
            '/sales', '/sales/create', "/sales/{$sale->id}", "/sales/{$sale->id}/edit",
            '/products', '/products/create', "/products/{$product->id}", "/products/{$product->id}/edit", '/products?q=nada',
            '/estoque', '/customers', '/customers/create', "/customers/{$customer->id}", "/customers/{$customer->id}/edit",
            '/categories', '/financeiro', '/notificacoes', '/configuracoes', '/equipe', '/auditoria',
        ];

        foreach (['vendas', 'lucros', 'despesas', 'estoque', 'clientes', 'receber', 'fluxo', 'produtos_vendidos'] as $tipo) {
            $paginas[] = "/relatorios?tipo={$tipo}&periodo=ano";
        }

        foreach ($paginas as $pagina) {
            $this->actingAs($admin)->get($pagina)->assertOk();
        }

        $this->actingAs($admin)->get('/dashboard?periodo=personalizado&inicio='.now()->subDays(60)->toDateString().'&fim='.now()->toDateString())
            ->assertSee('Loja Teste')
            ->assertSee('Parcelas vencidas')
            ->assertSee('Fone')
            ->assertDontSee('Serviços');

        $this->actingAs($admin)->get("/installments/{$installment->id}/whatsapp?tipo=atrasada")->assertRedirectContains('wa.me/5599988887777');
        $this->actingAs($admin)->get('/relatorios/pdf?tipo=vendas&periodo=ano')->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)->put('/configuracoes', [
            'company_name' => 'Loja Renomeada', 'initial_balance' => '100,00', 'due_reminder_days' => 3,
            'whatsapp_template_lembrete' => 'a', 'whatsapp_template_vence_hoje' => 'b',
            'whatsapp_template_atrasada' => 'c', 'whatsapp_template_pagamento' => 'd',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Loja Renomeada', $admin->company->fresh()->name);
    }

    public function test_paginas_legais_sao_publicas(): void
    {
        config(['meucash.support_email' => 'suporte@meucash.com']);

        $this->get('/privacidade')->assertOk()->assertSee('Política de Privacidade')->assertSee('suporte@meucash.com');
        $this->get('/termos')->assertOk()->assertSee('Termos de Uso');
    }

    public function test_telas_de_acesso_abrem(): void
    {
        $this->get('/login')->assertOk()->assertSee('Entrar com Google')->assertSee('Esqueci minha senha')->assertSee('Criar conta');
        $this->get('/criar-conta')->assertOk();
        $this->get('/esqueci-minha-senha')->assertOk();
        $this->get('/redefinir-senha/abc?email=a@b.com')->assertOk();
    }

    public function test_funcionario_ve_menu_reduzido(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('Financeiro</span>', false)->assertSee('Vendas</span>', false);
    }
}
