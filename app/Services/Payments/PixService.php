<?php

namespace App\Services\Payments;

use App\Models\Company;
use App\Models\Installment;
use App\Models\OnlinePayment;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\InstallmentService;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pix das parcelas pela área do cliente.
 * O dinheiro cai na conta Mercado Pago da LOJA (token salvo em Configurações > Recebimentos).
 */
class PixService
{
    public const EXPIRES_HOURS = 24;

    public function __construct(
        private readonly InstallmentService $installments,
        private readonly NotificationService $notifications,
    ) {
    }

    /* ---------- Token da loja ---------- */

    public function token(): ?string
    {
        $value = Setting::get('mp_access_token');
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function enabled(): bool
    {
        return (bool) $this->token();
    }

    public function connect(string $token): array
    {
        $account = MercadoPago::make($token)->account();

        $this->discardPending();
        Setting::put('mp_access_token', Crypt::encryptString($token));
        Setting::put('mp_account', json_encode([
            'id' => $account['id'] ?? null,
            'name' => trim(($account['first_name'] ?? '').' '.($account['last_name'] ?? '')) ?: ($account['nickname'] ?? null),
            'email' => $account['email'] ?? null,
            'test' => str_starts_with($token, 'TEST-'),
            'at' => now()->toIso8601String(),
        ]));

        return $account;
    }

    public function disconnect(): void
    {
        $this->discardPending();
        Setting::put('mp_access_token', '');
        Setting::put('mp_account', '');
    }

    /** Pix gerados com o token antigo deixam de valer (ex.: troca do token de teste pelo de produção). */
    private function discardPending(): void
    {
        OnlinePayment::where('status', 'pendente')->update(['status' => 'cancelado']);
    }

    public function account(): ?array
    {
        $json = Setting::get('mp_account');

        return $json ? json_decode($json, true) : null;
    }

    /* ---------- Cobrança ---------- */

    /** Devolve o Pix em aberto da parcela ou cria um novo. */
    public function forInstallment(Installment $installment): OnlinePayment
    {
        if (! $installment->isOpen()) {
            throw new RuntimeException('Esta parcela já foi paga.');
        }

        $current = OnlinePayment::where('installment_id', $installment->id)
            ->where('status', 'pendente')
            ->where('expires_at', '>', now()->addMinutes(10))
            ->where('amount', $installment->amount)
            ->latest('id')
            ->first();

        if ($current) {
            return $current;
        }

        $customer = $installment->customer;
        $company = $installment->company ?? tenant()->company();
        $expires = Carbon::now()->addHours(self::EXPIRES_HOURS);

        $payment = OnlinePayment::create([
            'installment_id' => $installment->id,
            'customer_id' => $installment->customer_id,
            'amount' => $installment->amount,
            'status' => 'pendente',
            'expires_at' => $expires,
        ]);

        try {
            $pix = MercadoPago::make($this->token())->createPix([
                'amount' => $installment->amount,
                'description' => "Parcela {$installment->label()} · {$installment->originLabel()} · {$company->name}",
                'reference' => 'parcela-'.$payment->id,
                'idempotency_key' => 'meucash-parcela-'.$payment->id,
                'notification_url' => public_url(route('webhooks.mercadopago.store', $company->id, false)),
                'expires_at' => $expires,
                'payer_email' => filter_var($customer?->email, FILTER_VALIDATE_EMAIL) ?: 'cliente'.$installment->customer_id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
                'payer_name' => $customer ? str($customer->name)->explode(' ')->first() : null,
            ]);
        } catch (\Throwable $e) {
            $payment->update(['status' => 'erro']);
            throw $e;
        }

        $payment->update([
            'provider_id' => $pix['id'],
            'qr_code' => $pix['qr_code'],
            'qr_code_base64' => $pix['qr_code_base64'],
            'ticket_url' => $pix['ticket_url'],
        ]);

        return $payment;
    }

    /** Pergunta ao Mercado Pago se o Pix foi pago (no máximo a cada 5 segundos). */
    public function refresh(OnlinePayment $payment, bool $force = false): OnlinePayment
    {
        if (! $payment->provider_id || $payment->status !== 'pendente') {
            return $payment;
        }

        if (! $force && $payment->checked_at && $payment->checked_at->gt(now()->subSeconds(5))) {
            return $payment;
        }

        $payment->update(['checked_at' => now()]);
        $data = MercadoPago::make($this->token())->payment($payment->provider_id);

        return $this->apply($payment, $data);
    }

    /** Aviso do Mercado Pago (webhook) para uma loja. */
    public function handleNotification(Company $company, string $providerId): void
    {
        tenant()->run($company, function () use ($providerId) {
            $payment = OnlinePayment::where('provider_id', $providerId)->first();
            if ($payment) {
                $this->refresh($payment, true);
            }
        });
    }

    /** Aplica a resposta do Mercado Pago: só dá baixa se estiver aprovado e o valor bater. */
    private function apply(OnlinePayment $payment, array $data): OnlinePayment
    {
        $status = $data['status'] ?? null;

        if (in_array($status, ['cancelled', 'rejected', 'refunded', 'charged_back'], true)) {
            $payment->update(['status' => 'cancelado']);

            return $payment;
        }

        if ($status !== 'approved') {
            if ($payment->expires_at?->isPast()) {
                $payment->update(['status' => 'expirado']);
            }

            return $payment;
        }

        $sameRef = ($data['external_reference'] ?? null) === 'parcela-'.$payment->id;
        $sameAmount = abs((float) ($data['transaction_amount'] ?? 0) - (float) $payment->amount) < 0.01;

        if (! $sameRef || ! $sameAmount) {
            report(new RuntimeException("Pix {$payment->provider_id} aprovado com dados diferentes (ref/valor)."));

            return $payment;
        }

        return DB::transaction(function () use ($payment, $data) {
            $payment = OnlinePayment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->isPaid()) {
                return $payment;
            }

            $paidAt = isset($data['date_approved']) ? Carbon::parse($data['date_approved'])->timezone(config('app.timezone')) : now();
            $payment->update(['status' => 'pago', 'paid_at' => $paidAt]);

            $installment = Installment::with(['customer', 'sale'])->find($payment->installment_id);

            if ($installment && $installment->isOpen()) {
                $this->installments->pay($installment, $paidAt, 'pix', 'Pago pelo cliente na área do cliente (Pix Mercado Pago nº '.$payment->provider_id.')');
            } else {
                $this->notifications->notify(
                    'pagamento_recebido',
                    'Pix recebido para parcela que já estava paga',
                    'O cliente pagou '.money($payment->amount).' pelo Pix (Mercado Pago nº '.$payment->provider_id.'), mas a parcela já tinha baixa. Confira e devolva se for o caso.',
                    $installment?->originUrl(),
                );
                AuditService::log('pagamento_registrado', 'Pix recebido em duplicidade · Mercado Pago nº '.$payment->provider_id, $installment);
            }

            return $payment;
        });
    }
}
