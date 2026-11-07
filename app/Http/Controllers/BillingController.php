<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Services\Payments\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly SubscriptionService $billing)
    {
    }

    public function show(): View
    {
        $company = tenant()->company();

        return view('billing.show', [
            'status' => $this->billing->status($company),
            'plans' => $this->billing->plans(),
            'company' => $company,
            'payments' => SubscriptionPayment::where('company_id', $company->id)->where('status', 'pago')->latest('id')->limit(12)->get(),
            'ready' => (bool) config('billing.mercadopago_token'),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        abort_unless($this->billing->enabled(), 404);
        $data = $request->validate(['plan' => ['required', 'in:'.implode(',', array_keys($this->billing->plans()))]]);

        try {
            $url = $this->billing->checkout(tenant()->company(), $request->user(), $data['plan']);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Não foi possível abrir o pagamento agora. Tente de novo em alguns minutos.');
        }

        return redirect()->away($url);
    }

    /** Volta do Mercado Pago: confere o pagamento na hora, sem esperar o aviso. */
    public function return(Request $request): RedirectResponse
    {
        $id = (string) ($request->query('payment_id') ?: $request->query('collection_id'));

        if ($id !== '' && $id !== 'null' && ctype_digit($id)) {
            try {
                $payment = $this->billing->confirm($id);

                if ($payment?->company_id === tenant()->id() && $payment->status === 'pago') {
                    return redirect()->route('billing.show')->with('success', 'Pagamento confirmado. Obrigado! Sua assinatura vai até '.tenant()->company()->fresh()->paid_until->format('d/m/Y').'.');
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $status = $request->query('status') ?: $request->query('collection_status');

        return redirect()->route('billing.show')->with(
            $status === 'rejected' || $status === 'failure' ? 'error' : 'success',
            match ($status) {
                'rejected', 'failure' => 'O pagamento não foi aprovado. Tente outra forma de pagamento.',
                null, 'null' => 'Pagamento não concluído.',
                default => 'Pagamento em análise. Assim que o Mercado Pago confirmar, sua assinatura é liberada sozinha.',
            }
        );
    }
}
