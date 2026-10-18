<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\OnlinePayment;
use App\Services\OnlinePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class BillingController extends Controller
{
    public function __construct(private readonly OnlinePaymentService $payments)
    {
    }

    public function index(): View
    {
        $company = tenant()->company();

        return view('billing.index', [
            'company' => $company,
            'enabled' => Company::billingEnabled(),
            'plans' => config('billing.plans'),
            'history' => OnlinePayment::where('company_id', $company->id)->where('kind', 'plano')
                ->where('status', '!=', 'criado')->latest('id')->limit(12)->get(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate(['period' => ['required', 'in:'.implode(',', array_keys(config('billing.plans')))]]);

        if (! Company::billingEnabled()) {
            return back()->with('error', 'A cobrança do plano ainda não está disponível.');
        }

        try {
            $url = $this->payments->startPlanCheckout(tenant()->company(), $request->input('period'), $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /** O Mercado Pago devolve a pessoa para cá depois de pagar. */
    public function return(Request $request): RedirectResponse
    {
        $id = (string) ($request->query('payment_id') ?: $request->query('collection_id'));
        $payment = null;

        if ($id !== '' && $id !== 'null' && Company::billingEnabled()) {
            try {
                $payment = $this->payments->handlePlatformNotification($id);
            } catch (RuntimeException $e) {
                Log::warning('Retorno do plano: '.$e->getMessage());
            }
        }

        if ($payment && (int) $payment->company_id !== (int) tenant_id()) {
            $payment = null;
        }

        return redirect()->route('billing.index')->with(...match ($payment?->status) {
            'aprovado' => ['success', 'Pagamento aprovado! Seu plano foi renovado.'],
            'pendente' => ['success', 'Pagamento em processamento. Assim que o Mercado Pago confirmar, o plano é liberado sozinho.'],
            'recusado' => ['error', 'O pagamento não foi aprovado. Tente outra forma de pagamento.'],
            default => ['success', 'Se você concluiu o pagamento, ele aparece aqui em instantes.'],
        });
    }
}
