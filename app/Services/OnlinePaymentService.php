<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\OnlinePayment;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OnlinePaymentService
{
    public function __construct(
        private readonly InstallmentService $installments,
        private readonly NotificationService $notifications,
    ) {
    }

    /* ================= Plano do sistema (dinheiro vai para a plataforma) ================= */

    public function platformClient(): MercadoPagoClient
    {
        $token = config('billing.mp_access_token');

        if (blank($token)) {
            throw new RuntimeException('A cobrança do plano ainda não foi configurada.');
        }

        return new MercadoPagoClient($token);
    }

    /** Cria o pagamento do plano e devolve o link do Mercado Pago. */
    public function startPlanCheckout(Company $company, string $period, User $user): string
    {
        $plan = config("billing.plans.{$period}");

        if (! $plan) {
            throw new RuntimeException('Plano inválido.');
        }

        $payment = OnlinePayment::create([
            'company_id' => $company->id,
            'kind' => 'plano',
            'external_reference' => 'mc-plano-'.Str::lower(Str::random(24)),
            'user_id' => $user->id,
            'period' => $period,
            'amount' => $plan['price'],
        ]);

        $preference = $this->platformClient()->createPreference($this->preferenceData(
            payment: $payment,
            title: 'Meu Cash · Plano '.$plan['label'].' · '.$company->name,
            returnUrl: route('billing.return'),
            notificationUrl: route('webhooks.mp.platform'),
            payerEmail: $user->email,
        ));

        $payment->update(['preference_id' => $preference['id'] ?? null]);

        return $preference['init_point'];
    }

    public function handlePlatformNotification(string $mpPaymentId): ?OnlinePayment
    {
        $data = $this->platformClient()->payment($mpPaymentId);

        return $data ? $this->sync($data, 'plano') : null;
    }

    /* ================= Parcelas (dinheiro vai direto para a loja) ================= */

    public function storeClient(Company $company): MercadoPagoClient
    {
        if (! $company->acceptsOnlinePayments()) {
            throw new RuntimeException('Esta loja ainda não recebe pagamentos online.');
        }

        return new MercadoPagoClient($company->mp_access_token);
    }

    public function startInstallmentCheckout(Installment $installment, Customer $customer): string
    {
        $company = $customer->company;

        if ((int) $installment->customer_id !== (int) $customer->id || (int) $installment->company_id !== (int) $company->id) {
            throw new RuntimeException('Parcela não encontrada.');
        }

        if (! $installment->isOpen()) {
            throw new RuntimeException('Esta parcela já foi paga.');
        }

        $payment = OnlinePayment::create([
            'company_id' => $company->id,
            'kind' => 'parcela',
            'external_reference' => 'mc-parcela-'.Str::lower(Str::random(24)),
            'installment_id' => $installment->id,
            'customer_id' => $customer->id,
            'amount' => $installment->amount,
        ]);

        $preference = $this->storeClient($company)->createPreference($this->preferenceData(
            payment: $payment,
            title: 'Parcela '.$installment->label().' · '.$installment->originLabel().' · '.$company->name,
            returnUrl: route('portal.payment.return'),
            notificationUrl: route('webhooks.mp.store', $company->ensureWebhookKey()),
            payerEmail: $customer->email ?? null,
        ));

        $payment->update(['preference_id' => $preference['id'] ?? null]);

        return $preference['init_point'];
    }

    public function handleStoreNotification(Company $company, string $mpPaymentId): ?OnlinePayment
    {
        $data = $this->storeClient($company)->payment($mpPaymentId);

        return $data ? $this->sync($data, 'parcela', $company) : null;
    }

    /* ================= Comum ================= */

    /**
     * Atualiza o pagamento com os dados que vieram do Mercado Pago e,
     * se aprovado, libera o plano ou dá baixa na parcela. Pode ser chamado
     * várias vezes para o mesmo pagamento sem aplicar duas vezes.
     */
    public function sync(array $data, string $kind, ?Company $store = null): ?OnlinePayment
    {
        $reference = (string) ($data['external_reference'] ?? '');

        $payment = OnlinePayment::query()
            ->where('external_reference', $reference)
            ->where('kind', $kind)
            ->when($store, fn ($q) => $q->where('company_id', $store->id))
            ->first();

        if (! $payment) {
            return null;
        }

        return DB::transaction(function () use ($payment, $data) {
            $payment = OnlinePayment::query()->lockForUpdate()->find($payment->id);

            $status = $this->mapStatus((string) ($data['status'] ?? ''));
            $amountOk = abs((float) ($data['transaction_amount'] ?? 0) - (float) $payment->amount) < 0.01
                && ($data['currency_id'] ?? 'BRL') === 'BRL';

            if ($status === 'aprovado' && ! $amountOk) {
                $status = 'divergente';
            }

            if ($payment->processed_at && $payment->status === 'aprovado' && $status === 'aprovado') {
                return $payment;
            }

            $payment->fill([
                'mp_payment_id' => (string) ($data['id'] ?? $payment->mp_payment_id),
                'status' => $payment->processed_at ? $payment->status : $status,
                'method' => $this->mapMethod($data),
                'paid_at' => isset($data['date_approved']) ? Carbon::parse($data['date_approved']) : $payment->paid_at,
            ]);

            if ($payment->processed_at && $status === 'estornado') {
                $payment->status = 'estornado';
            }

            if ($status === 'aprovado' && ! $payment->processed_at) {
                $payment->kind === 'plano' ? $this->applyPlan($payment) : $this->applyInstallment($payment);
                $payment->processed_at = now();
                $payment->status = 'aprovado';
            }

            $payment->save();

            return $payment;
        });
    }

    private function applyPlan(OnlinePayment $payment): void
    {
        $company = Company::query()->lockForUpdate()->find($payment->company_id);
        $months = (int) config("billing.plans.{$payment->period}.months", 1);
        $base = $company->plan_expires_at && $company->plan_expires_at->isFuture() ? $company->plan_expires_at : now();

        $company->update([
            'plan_period' => $payment->period,
            'plan_expires_at' => $base->copy()->addMonthsNoOverflow($months)->endOfDay(),
        ]);

        tenant()->run($company, fn () => $this->notifications->notify(
            'plano',
            'Plano renovado',
            'Pagamento de '.money($payment->amount).' aprovado. Liberado até '.$company->plan_expires_at->format('d/m/Y').'.',
            route('billing.index'),
        ));
    }

    private function applyInstallment(OnlinePayment $payment): void
    {
        $company = $payment->company;

        tenant()->run($company, function () use ($payment) {
            $installment = Installment::query()->find($payment->installment_id);

            if (! $installment) {
                return;
            }

            if (! $installment->isOpen()) {
                $this->notifications->notify(
                    'pagamento_recebido',
                    'Parcela paga duas vezes',
                    'O cliente pagou pelo Mercado Pago a parcela '.$installment->label().' que já estava baixada. Confira e devolva pelo Mercado Pago se for o caso.',
                    $installment->originUrl(),
                );

                return;
            }

            $method = match ($payment->method) {
                'pix' => 'pix',
                'credito' => 'credito',
                'debito' => 'debito',
                default => 'outro',
            };

            $this->installments->pay(
                $installment,
                ($payment->paid_at ?? now())->toDateString(),
                $method,
                'Pago pelo cliente na área do cliente (Mercado Pago #'.$payment->mp_payment_id.')',
            );
        });
    }

    private function preferenceData(OnlinePayment $payment, string $title, string $returnUrl, string $notificationUrl, ?string $payerEmail): array
    {
        $data = [
            'items' => [[
                'id' => $payment->external_reference,
                'title' => Str::limit($title, 250, ''),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round((float) $payment->amount, 2),
            ]],
            'external_reference' => $payment->external_reference,
            'back_urls' => ['success' => $returnUrl, 'pending' => $returnUrl, 'failure' => $returnUrl],
            'statement_descriptor' => 'MEUCASH',
            'payment_methods' => ['installments' => 1],
        ];

        if ($payerEmail) {
            $data['payer'] = ['email' => $payerEmail];
        }

        // O Mercado Pago só aceita endereços públicos (https) para retorno automático e avisos.
        if (str_starts_with($notificationUrl, 'https://')) {
            $data['notification_url'] = $notificationUrl;
        }

        if (str_starts_with($returnUrl, 'https://')) {
            $data['auto_return'] = 'approved';
        }

        return $data;
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'approved' => 'aprovado',
            'pending', 'in_process', 'authorized', 'in_mediation' => 'pendente',
            'rejected', 'cancelled' => 'recusado',
            'refunded', 'charged_back' => 'estornado',
            default => 'criado',
        };
    }

    private function mapMethod(array $data): ?string
    {
        if (($data['payment_method_id'] ?? null) === 'pix') {
            return 'pix';
        }

        return match ($data['payment_type_id'] ?? null) {
            'credit_card' => 'credito',
            'debit_card', 'prepaid_card' => 'debito',
            'ticket' => 'boleto',
            'bank_transfer' => 'pix',
            'account_money' => 'saldo',
            null => null,
            default => 'outro',
        };
    }
}
