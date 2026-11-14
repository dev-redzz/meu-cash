<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Payments\PixService;
use App\Services\Payments\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Avisos do Mercado Pago. O aviso só diz "o pagamento X mudou": os dados são sempre
 * consultados na API, então um aviso falso não consegue dar baixa em nada.
 */
class MercadoPagoWebhookController extends Controller
{
    public function store(Request $request, Company $company, PixService $pix): Response
    {
        if ($id = $this->paymentId($request)) {
            try {
                $pix->handleNotification($company, $id);
            } catch (\Throwable $e) {
                if ($this->notFound($e)) {
                    return response('ignorado');
                }
                report($e);

                return response('erro', 500);
            }
        }

        return response('ok');
    }

    public function platform(Request $request, SubscriptionService $billing): Response
    {
        if ($id = $this->paymentId($request)) {
            try {
                $billing->confirm($id);
            } catch (\Throwable $e) {
                if ($this->notFound($e)) {
                    return response('ignorado');
                }
                report($e);

                return response('erro', 500);
            }
        }

        return response('ok');
    }

    /**
     * Pagamento que não existe nesta conta: aviso de teste do painel do Mercado Pago,
     * pagamento de outra integração ou de outro token. Não é erro do sistema.
     */
    private function notFound(\Throwable $e): bool
    {
        return $e->getCode() === 404 || str_contains($e->getMessage(), 'not found');
    }

    private function paymentId(Request $request): ?string
    {
        $type = $request->input('type') ?? $request->query('topic') ?? $request->query('type');
        $id = $request->input('data.id') ?? $request->query('data_id') ?? $request->query('id');

        if (! in_array($type, ['payment', null], true) || ! $id) {
            return null;
        }

        $id = (string) $id;

        return ctype_digit($id) && strlen($id) <= 30 ? $id : null;
    }
}
