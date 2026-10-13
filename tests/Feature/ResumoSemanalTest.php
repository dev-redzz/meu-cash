<?php

namespace Tests\Feature;

use App\Mail\WeeklySummaryMail;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\CompanyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResumoSemanalTest extends TestCase
{
    use RefreshDatabase;

    private function empresaComVenda(): User
    {
        $admin = app(CompanyProvisioner::class)->create(['name' => 'Loja Resumo'], ['name' => 'Dona Resumo', 'email' => 'dona@gmail.com']);

        tenant()->set($admin->company);
        $customer = Customer::create(['name' => 'Cliente Atrasado']);
        $product = Product::create(['name' => 'Capinha', 'purchase_price' => 10, 'sale_price' => 40, 'initial_quantity' => 10, 'quantity' => 10, 'status' => 'disponivel']);
        tenant()->set(null);

        $this->actingAs($admin)->post('/sales', [
            'customer_id' => $customer->id,
            'sale_date' => Carbon::today()->subWeek()->startOfWeek()->addDay()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => '40']],
            'payment_method' => 'parcelado',
            'down_payment' => '0',
            'installments_count' => 2,
            'first_due_date' => Carbon::today()->subDays(3)->toDateString(),
        ])->assertSessionHasNoErrors();

        return $admin;
    }

    public function test_envia_resumo_para_administradores(): void
    {
        Mail::fake();
        $admin = $this->empresaComVenda();
        User::factory()->create(['company_id' => $admin->company_id, 'email' => 'func@gmail.com']);

        $this->artisan('meucash:resumo-semanal')->assertSuccessful();

        Mail::assertSent(WeeklySummaryMail::class, 1);
        Mail::assertSent(WeeklySummaryMail::class, function (WeeklySummaryMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('dona@gmail.com')
                && $mail->summary['revenue'] == 120
                && $mail->summary['overdue_count'] === 1
                && str_contains($html, 'Capinha')
                && str_contains($html, 'Cliente Atrasado');
        });
    }

    public function test_respeita_desativacao_e_empresa_sem_movimento(): void
    {
        Mail::fake();
        $admin = $this->empresaComVenda();
        app(CompanyProvisioner::class)->create(['name' => 'Loja Parada'], ['name' => 'Sem Venda', 'email' => 'parada@gmail.com']);

        tenant()->run($admin->company, fn () => Setting::put('weekly_summary', '0'));

        $this->artisan('meucash:resumo-semanal')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_envia_uma_vez_por_semana_ao_abrir_o_sistema(): void
    {
        Mail::fake();
        $admin = $this->empresaComVenda();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/sales')->assertOk();
        $this->artisan('meucash:resumo-semanal')->assertSuccessful();

        Mail::assertSent(WeeklySummaryMail::class, 1);
    }

    public function test_previa_e_configuracao(): void
    {
        Mail::fake();
        $admin = $this->empresaComVenda();

        $this->actingAs($admin)->get('/configuracoes')->assertOk()->assertSee('Resumo semanal por e-mail');
        $this->actingAs($admin)->post('/configuracoes/resumo-semanal/previa')->assertRedirect();
        Mail::assertSent(WeeklySummaryMail::class, fn ($mail) => $mail->hasTo('dona@gmail.com'));

        $funcionario = User::factory()->create(['company_id' => $admin->company_id]);
        $this->actingAs($funcionario)->post('/configuracoes/resumo-semanal/previa')->assertForbidden();
    }

    public function test_tema_escuro_disponivel(): void
    {
        $admin = $this->empresaComVenda();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('data-theme-set="dark"', false)->assertSee('mc-theme', false);
        auth()->logout();
        $this->get('/login')->assertSee('mc-theme', false);
    }
}
