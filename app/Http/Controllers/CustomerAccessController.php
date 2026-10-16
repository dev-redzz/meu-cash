<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\WhatsAppMessage;
use App\Services\AuditService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;

/** Código de acesso do cliente à Área do cliente. */
class CustomerAccessController extends Controller
{
    public function whatsapp(Customer $customer, WhatsAppService $whatsapp): RedirectResponse
    {
        $phone = $whatsapp->normalizePhone($customer->whatsappNumber());

        if (! $phone) {
            return back()->with('error', 'Este cliente não tem WhatsApp ou telefone cadastrado.');
        }

        $message = self::message($customer);

        WhatsAppMessage::create([
            'customer_id' => $customer->id,
            'user_id' => auth()->id(),
            'phone' => $phone,
            'type' => 'acesso',
            'message' => $message,
            'channel' => 'link',
        ]);

        return redirect()->away($whatsapp->link($phone, $message));
    }

    public function regenerate(Customer $customer): RedirectResponse
    {
        $code = $customer->regenerateAccessCode();
        AuditService::log('cliente_codigo', "Novo código de acesso gerado para {$customer->name}", $customer);

        return back()->with('success', "Novo código gerado: {$code}. O código antigo deixou de funcionar.");
    }

    public static function message(Customer $customer): string
    {
        return 'Olá, '.str($customer->name)->explode(' ')->first().'! Acompanhe suas compras e parcelas na '.tenant()->company()->name
            .'. Acesse '.route('portal.login', ['codigo' => $customer->ensureAccessCode()]).' e use o código '.$customer->access_code.'.';
    }
}
