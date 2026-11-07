<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\OnlinePayment;
use App\Services\AuditService;
use App\Services\Payments\PixService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSettingController extends Controller
{
    public function edit(PixService $pix): View
    {
        return view('settings.payments', [
            'connected' => $pix->enabled(),
            'account' => $pix->account(),
            'recent' => OnlinePayment::with(['installment.sale', 'installment.customer'])->whereNotNull('provider_id')->latest('id')->limit(15)->get(),
        ]);
    }

    public function update(Request $request, PixService $pix): RedirectResponse
    {
        $data = $request->validate([
            'access_token' => ['required', 'string', 'max:200', 'regex:/^(APP_USR|TEST)-[A-Za-z0-9\-]+$/'],
        ], [
            'access_token.required' => 'Cole o Access Token do Mercado Pago.',
            'access_token.regex' => 'O Access Token começa com APP_USR- (produção) ou TEST- (teste).',
        ]);

        try {
            $account = $pix->connect(trim($data['access_token']));
        } catch (\Throwable $e) {
            return back()->withErrors(['access_token' => 'O Mercado Pago recusou este token. Confira se copiou o Access Token inteiro.']);
        }

        AuditService::log('configuracoes', 'Conta Mercado Pago conectada para receber Pix na área do cliente');

        return back()->with('success', 'Mercado Pago conectado'.(isset($account['email']) ? ' ('.$account['email'].')' : '').'. Seus clientes já podem pagar as parcelas pelo Pix.');
    }

    public function destroy(PixService $pix): RedirectResponse
    {
        $pix->disconnect();
        AuditService::log('configuracoes', 'Conta Mercado Pago desconectada');

        return back()->with('success', 'Mercado Pago desconectado. O botão de Pix sumiu da área do cliente.');
    }
}
