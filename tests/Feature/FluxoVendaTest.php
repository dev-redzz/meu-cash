<?php

namespace Tests\Feature;

use App\Enums\InstallmentStatus;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Product;
use App\Models\User;
use App\Services\FinanceService;
use App\Support\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FluxoVendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_e_dashboard(): void
    {
        $user = User::factory()->admin()->create(['password' => 'senha12345']);

        $this->post('/login', ['email' => $user->email, 'password' => 'senha12345'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Saldo atual');
    }

    public function test_venda_parcelada_separa_faturamento_recebido_e_a_receber(): void
    {
        $admin = User::factory()->admin()->create();
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

        $summary = app(FinanceService::class)->summary(Period::fromRequest(new Request()));

        $this->assertEquals(1500, $summary['revenue']);
        $this->assertEquals(500, $summary['received']);
        $this->assertEquals(1000, $summary['receivable']);
        $this->assertEquals(500, $summary['profit']);
        $this->assertEquals('vendido', $product->fresh()->status->value);

        $installment = Installment::first();
        $this->post("/installments/{$installment->id}/pay", ['paid_at' => now()->toDateString(), 'payment_method' => 'pix'])->assertRedirect();

        $this->assertEquals(InstallmentStatus::Pago, $installment->fresh()->status);
        $this->assertEquals(1000, app(FinanceService::class)->balance());
    }

    public function test_funcionario_nao_acessa_financeiro(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/financeiro')->assertForbidden();
        $this->actingAs($user)->get('/sales')->assertOk();
    }
}
