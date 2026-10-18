<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSettingController extends Controller
{
    public function edit(): View
    {
        $company = tenant()->company();

        return view('settings.payments', [
            'company' => $company,
            'portalPayments' => Setting::get('portal_payments', '1') === '1',
            'recent' => OnlinePayment::where('company_id', $company->id)->where('kind', 'parcela')
                ->where('status', '!=', 'criado')->with('installment.customer', 'installment.sale')->latest('id')->limit(15)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = tenant()->company();

        $request->validate([
            'mp_access_token' => [$company->acceptsOnlinePayments() ? 'nullable' : 'required', 'string', 'max:200', 'regex:/^(APP_USR|TEST)-[A-Za-z0-9\-]+$/'],
        ], [
            'mp_access_token.required' => 'Cole o Access Token da sua conta Mercado Pago.',
            'mp_access_token.regex' => 'Esse não parece um Access Token. Ele começa com APP_USR- (produção) ou TEST- (teste).',
        ]);

        Setting::put('portal_payments', $request->boolean('portal_payments') ? '1' : '0');

        if ($token = trim((string) $request->input('mp_access_token'))) {
            $account = (new MercadoPagoClient($token))->account();

            if (! $account) {
                return back()->withErrors(['mp_access_token' => 'O Mercado Pago recusou esse token. Confira se copiou o Access Token inteiro.'])->withInput();
            }

            $company->update([
                'mp_access_token' => $token,
                'mp_account' => mb_substr(trim(($account['nickname'] ?? '').' · '.($account['email'] ?? ''), ' ·'), 0, 120) ?: 'Conta conectada',
            ]);
            $company->ensureWebhookKey();

            AuditService::log('configuracoes', 'Conta Mercado Pago conectada para receber parcelas');

            return back()->with('success', 'Mercado Pago conectado! Seus clientes já podem pagar as parcelas pela área do cliente.');
        }

        return back()->with('success', 'Configurações de pagamento salvas.');
    }

    public function destroy(): RedirectResponse
    {
        tenant()->company()->update(['mp_access_token' => null, 'mp_account' => null]);
        AuditService::log('configuracoes', 'Conta Mercado Pago desconectada');

        return back()->with('success', 'Mercado Pago desconectado. Os clientes não veem mais o botão de pagar.');
    }
}
