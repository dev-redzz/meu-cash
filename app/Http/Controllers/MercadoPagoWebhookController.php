<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\OnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Avisos do Mercado Pago. Nunca confiamos no conteúdo do aviso:
 * buscamos o pagamento direto na API com o token certo antes de fazer qualquer coisa.
 */
class MercadoPagoWebhookController extends Controller
{
    public function __construct(private readonly OnlinePaymentService $payments)
    {
    }

    public function platform(Request $request): Response
    {
        $id = $this->paymentId($request);

        if (! $id || ! Company::billingEnabled()) {
            return response('ok');
        }

        try {
            $this->payments->handlePlatformNotification($id);
        } catch (Throwable $e) {
            Log::error('Webhook Mercado Pago (plano) '.$id.': '.$e->getMessage());

            return response('erro', 500);
        }

        return response('ok');
    }

    public function store(Request $request, string $key): Response
    {
        $company = Company::where('mp_webhook_key', $key)->first();
        $id = $this->paymentId($request);

        if (! $company || ! $id || ! $company->acceptsOnlinePayments()) {
            return response('ok');
        }

        try {
            $this->payments->handleStoreNotification($company, $id);
        } catch (Throwable $e) {
            Log::error('Webhook Mercado Pago (loja '.$company->id.') '.$id.': '.$e->getMessage());

            return response('erro', 500);
        }

        return response('ok');
    }

    private function paymentId(Request $request): ?string
    {
        $type = $request->input('type') ?? $request->query('topic') ?? $request->input('topic');

        if ($type && ! in_array($type, ['payment'], true)) {
            return null;
        }

        $id = $request->input('data.id') ?? $request->query('data_id') ?? $request->query('id') ?? $request->input('id');

        return is_scalar($id) && preg_match('/^\d{1,30}$/', (string) $id) ? (string) $id : null;
    }
}
