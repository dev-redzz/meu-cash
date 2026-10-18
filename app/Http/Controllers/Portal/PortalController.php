<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InstallmentStatus;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReceiptController;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\Setting;
use App\Services\OnlinePaymentService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PortalController extends Controller
{
    public function login(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('portal.customer_id')) {
            return redirect()->route('portal.home');
        }

        return view('portal.login', ['code' => Customer::normalizeCode($request->query('codigo'))]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:20']], ['code.required' => 'Digite o seu código de acesso.']);

        $key = 'portal-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Muitas tentativas. Tente de novo em '.RateLimiter::availableIn($key).' segundos.',
            ]);
        }

        $code = Customer::normalizeCode($request->input('code'));
        $customer = strlen($code) === 9
            ? Customer::withoutGlobalScopes()->with('company')->where('access_code', $code)->first()
            : null;

        if (! $customer || ! $customer->company?->active) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['code' => 'Código não encontrado. Confira no seu comprovante de compra.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put([
            'portal.customer_id' => $customer->id,
            'portal.code_hash' => hash('sha256', $customer->access_code),
        ]);

        return redirect()->route('portal.home');
    }

    public function home(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');

        $sales = $customer->sales()
            ->where('status', SaleStatus::Concluida->value)
            ->with(['items', 'installments' => fn ($q) => $q->orderBy('number'), 'payments'])
            ->latest('sale_date')
            ->latest('id')
            ->get();

        $installments = $sales->flatMap->installments;
        $open = $installments->filter->isOpen();
        $overdue = $open->filter(fn ($i) => $i->due_date->lt(today()));

        return view('portal.home', [
            'customer' => $customer,
            'company' => $customer->company,
            'canPay' => self::canPayOnline($customer),
            'sales' => $sales,
            'next' => $open->sortBy('due_date')->first(),
            'totals' => [
                'purchased' => (float) $sales->sum('total'),
                'paid' => (float) $sales->flatMap->payments->sum('amount'),
                'open' => (float) $open->sum('amount'),
                'overdue' => (float) $overdue->sum('amount'),
                'overdue_count' => $overdue->count(),
                'open_count' => $open->count(),
            ],
        ]);
    }

    public function receipt(Request $request, int $sale, ReceiptController $receipts): Response
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');
        $sale = $customer->sales()->findOrFail($sale);

        return $receipts->stream($sale);
    }

    public static function canPayOnline(Customer $customer): bool
    {
        return $customer->company->acceptsOnlinePayments() && Setting::get('portal_payments', '1') === '1';
    }

    public function pay(Request $request, int $installment, OnlinePaymentService $payments): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');
        $installment = Installment::where('customer_id', $customer->id)->findOrFail($installment);

        if (! self::canPayOnline($customer)) {
            return back()->with('error', 'Esta loja não recebe pagamentos pela internet. Fale com a loja.');
        }

        try {
            $url = $payments->startInstallmentCheckout($installment, $customer);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    public function paymentReturn(Request $request, OnlinePaymentService $payments): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');
        $id = (string) ($request->query('payment_id') ?: $request->query('collection_id'));
        $payment = null;

        if ($id !== '' && $id !== 'null' && $customer->company->acceptsOnlinePayments()) {
            try {
                $payment = $payments->handleStoreNotification($customer->company, $id);
            } catch (RuntimeException $e) {
                Log::warning('Retorno do portal: '.$e->getMessage());
            }
        }

        if ($payment && (int) $payment->customer_id !== (int) $customer->id) {
            $payment = null;
        }

        return redirect()->route('portal.home')->with(...match ($payment?->status) {
            'aprovado' => ['success', 'Pagamento aprovado! Sua parcela já aparece como paga.'],
            'pendente' => ['success', 'Pagamento em processamento. Assim que for confirmado, a parcela é baixada sozinha.'],
            'recusado' => ['error', 'O pagamento não foi aprovado. Tente de novo ou use outra forma de pagamento.'],
            default => ['success', 'Se você concluiu o pagamento, ele aparece aqui em instantes.'],
        });
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['portal.customer_id', 'portal.code_hash']);
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('success', 'Você saiu da sua área.');
    }
}
