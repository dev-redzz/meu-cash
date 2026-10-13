<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CompanyProvisioner;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExcluirVendaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(CompanyProvisioner::class)->create(['name' => 'Loja'], ['name' => 'Dono', 'email' => 'dono@gmail.com']);
        tenant()->set($this->admin->company);
        $this->product = Product::create(['name' => 'Celular', 'purchase_price' => 1000, 'sale_price' => 1500, 'initial_quantity' => 3, 'quantity' => 3, 'status' => 'disponivel']);
        tenant()->set(null);
    }

    private function vender(): Sale
    {
        $customer = tenant()->run($this->admin->company, fn () => Customer::create(['name' => 'Cliente']));

        $this->actingAs($this->admin)->post('/sales', [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => '1.500,00']],
            'payment_method' => 'parcelado',
            'down_payment' => '500,00',
            'down_payment_method' => 'pix',
            'installments_count' => 2,
            'first_due_date' => now()->addMonth()->toDateString(),
        ])->assertSessionHasNoErrors();

        $sale = tenant()->run($this->admin->company, fn () => Sale::latest('id')->first());
        $installment = tenant()->run($this->admin->company, fn () => $sale->installments()->first());
        $this->post("/installments/{$installment->id}/pay", ['paid_at' => now()->toDateString(), 'payment_method' => 'pix']);

        return $sale;
    }

    private function saldo(): float
    {
        return tenant()->run($this->admin->company, fn () => app(FinanceService::class)->balance());
    }

    public function test_cancelar_tira_o_dinheiro_do_caixa(): void
    {
        $sale = $this->vender();
        $this->assertEquals(1000, $this->saldo());

        $this->actingAs($this->admin)->post("/sales/{$sale->id}/cancel")->assertRedirect();

        $this->assertEquals(0, $this->saldo());
        tenant()->run($this->admin->company, function () use ($sale) {
            $this->assertSame(SaleStatus::Cancelada, $sale->fresh()->status);
            $this->assertSame(3, $this->product->fresh()->quantity);
            $this->assertSame(0, Installment::where('status', '!=', 'cancelado')->count());
            $this->assertSame(0, Payment::count());
        });
    }

    public function test_excluir_venda_concluida(): void
    {
        $sale = $this->vender();

        $this->actingAs($this->admin)->delete("/sales/{$sale->id}")->assertRedirect(route('sales.index'));

        $this->assertEquals(0, $this->saldo());
        tenant()->run($this->admin->company, function () use ($sale) {
            $this->assertNull(Sale::find($sale->id));
            $this->assertSame(0, Installment::count());
            $this->assertSame(0, FinancialTransaction::count());
            $this->assertSame(3, $this->product->fresh()->quantity);
        });
    }

    public function test_excluir_venda_ja_cancelada_que_deixou_dinheiro_no_caixa(): void
    {
        $sale = $this->vender();
        tenant()->run($this->admin->company, fn () => $sale->update(['status' => SaleStatus::Cancelada]));
        $this->assertEquals(1000, $this->saldo());

        $this->actingAs($this->admin)->delete("/sales/{$sale->id}")->assertRedirect();

        $this->assertEquals(0, $this->saldo());
    }

    public function test_codigo_nao_repete_depois_de_excluir(): void
    {
        $a = $this->vender();
        $b = $this->vender();
        $this->actingAs($this->admin)->delete("/sales/{$a->id}");
        $c = $this->vender();

        $this->assertSame('V00002', $b->code);
        $this->assertSame('V00003', $c->code);
    }

    public function test_funcionario_nao_exclui(): void
    {
        $sale = $this->vender();
        $func = User::factory()->create(['company_id' => $this->admin->company_id]);

        $this->actingAs($func)->delete("/sales/{$sale->id}")->assertForbidden();
    }
}
