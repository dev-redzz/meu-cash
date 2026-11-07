<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cliente simples da API do Mercado Pago (https://www.mercadopago.com.br/developers).
 * Cada loja usa o próprio token (parcelas); a assinatura usa o token da plataforma.
 */
class MercadoPago
{
    public const API = 'https://api.mercadopago.com';

    public function __construct(private readonly string $token)
    {
    }

    public static function make(?string $token): self
    {
        if (! $token) {
            throw new RuntimeException('Mercado Pago não configurado.');
        }

        return new self($token);
    }

    /** Dados da conta dona do token (serve para testar a conexão). */
    public function account(): array
    {
        return $this->get('/users/me');
    }

    /** Cria uma cobrança Pix e devolve o QR Code e o "copia e cola". */
    public function createPix(array $data): array
    {
        $body = array_filter([
            'transaction_amount' => round((float) $data['amount'], 2),
            'description' => Str::limit($data['description'], 120, ''),
            'payment_method_id' => 'pix',
            'external_reference' => $data['reference'],
            'notification_url' => self::publicUrl($data['notification_url'] ?? null),
            'date_of_expiration' => $data['expires_at']->format('Y-m-d\TH:i:s.vP'),
            'payer' => array_filter([
                'email' => $data['payer_email'],
                'first_name' => $data['payer_name'] ?? null,
            ]),
        ], fn ($v) => $v !== null);

        $payment = $this->post('/v1/payments', $body, $data['idempotency_key'] ?? (string) Str::uuid());
        $pix = $payment['point_of_interaction']['transaction_data'] ?? [];

        return [
            'id' => (string) $payment['id'],
            'status' => $payment['status'] ?? 'pending',
            'qr_code' => $pix['qr_code'] ?? null,
            'qr_code_base64' => $pix['qr_code_base64'] ?? null,
            'ticket_url' => $pix['ticket_url'] ?? null,
        ];
    }

    /** Página de pagamento do Mercado Pago (Pix, cartão ou boleto). */
    public function createCheckout(array $data): array
    {
        $body = array_filter([
            'items' => [[
                'id' => $data['reference'],
                'title' => $data['title'],
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round((float) $data['amount'], 2),
            ]],
            'external_reference' => $data['reference'],
            'payer' => ['email' => $data['payer_email']],
            'notification_url' => self::publicUrl($data['notification_url'] ?? null),
            'back_urls' => [
                'success' => $data['back_url'],
                'pending' => $data['back_url'],
                'failure' => $data['back_url'],
            ],
            'auto_return' => str_starts_with($data['back_url'], 'https://') ? 'approved' : null,
            'statement_descriptor' => 'MEUCASH',
        ], fn ($v) => $v !== null);

        $preference = $this->post('/checkout/preferences', $body);

        return [
            'id' => (string) $preference['id'],
            'url' => str_starts_with($this->token, 'TEST-') ? ($preference['sandbox_init_point'] ?? $preference['init_point']) : $preference['init_point'],
        ];
    }

    /** Consulta o pagamento direto no Mercado Pago. Nunca confiamos só no aviso (webhook). */
    public function payment(string $id): array
    {
        return $this->get('/v1/payments/'.rawurlencode($id));
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->token)->acceptJson()->asJson()->timeout(20)->connectTimeout(8);
    }

    private function get(string $path): array
    {
        return $this->check($this->http()->get(self::API.$path));
    }

    private function post(string $path, array $body, ?string $idempotency = null): array
    {
        $request = $this->http();
        if ($idempotency) {
            $request = $request->withHeaders(['X-Idempotency-Key' => $idempotency]);
        }

        return $this->check($request->post(self::API.$path, $body));
    }

    private function check($response): array
    {
        if ($response->failed()) {
            $msg = $response->json('message') ?? $response->json('error') ?? 'erro '.$response->status();
            if ($response->status() === 401) {
                $msg = 'token inválido ou sem permissão';
            }

            throw new RuntimeException('Mercado Pago: '.$msg, $response->status());
        }

        return $response->json() ?? [];
    }

    /** O Mercado Pago só aceita endereço de aviso público em https. */
    private static function publicUrl(?string $url): ?string
    {
        return $url && str_starts_with($url, 'https://') ? $url : null;
    }
}
