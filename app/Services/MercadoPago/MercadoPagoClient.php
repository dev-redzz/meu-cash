<?php

namespace App\Services\MercadoPago;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MercadoPagoClient
{
    public function __construct(private readonly string $accessToken)
    {
    }

    public function createPreference(array $data): array
    {
        $response = $this->http()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post('/checkout/preferences', $data);

        if ($response->failed() || ! $response->json('init_point')) {
            report(new RuntimeException('Mercado Pago (preferência): '.$response->status().' '.Str::limit($response->body(), 500)));

            throw new RuntimeException('Não foi possível abrir o pagamento no Mercado Pago agora. Tente de novo em alguns minutos.');
        }

        return $response->json();
    }

    public function payment(string $id): ?array
    {
        $response = $this->http()->get('/v1/payments/'.rawurlencode($id));

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new RuntimeException('Mercado Pago (pagamento): '.$response->status());
        }

        return $response->json();
    }

    /** Confere se o token é válido e devolve o nome/e-mail da conta. */
    public function account(): ?array
    {
        $response = $this->http()->get('/users/me');

        return $response->successful() ? $response->json() : null;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl('https://api.mercadopago.com')
            ->withToken($this->accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->connectTimeout(8);
    }
}
