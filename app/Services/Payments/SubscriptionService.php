<?php

namespace App\Services\Payments;

use App\Models\Company;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Assinatura das lojas: teste grátis, pagamento mensal ou anual pelo Mercado Pago
 * (conta do dono da plataforma) e modo somente leitura quando vence.
 */
class SubscriptionService
{
    public function enabled(): bool
    {
        return (bool) config('billing.enabled');
    }

    public function plans(): array
    {
        return config('billing.plans');
    }

    /**
     * Situação da assinatura.
     * state: free (cobrança desligada), exempt, trial, active, grace (venceu, ainda em tolerância) ou expired.
     */
    public function status(Company $company): array
    {
        if (! $this->enabled()) {
            return ['state' => 'free', 'ends' => null, 'days' => null, 'blocked' => false];
        }

        if ($company->billing_exempt || $this->isPlatformCompany($company)) {
            return ['state' => 'exempt', 'ends' => null, 'days' => null, 'blocked' => false];
        }

        $today = Carbon::today();
        $trialEnd = $this->trialEnd($company);
        $paid = $company->paid_until;

        if ($paid && $paid->gte($today)) {
            return $this->result('active', $paid);
        }

        if ($trialEnd->gte($today)) {
            return $this->result('trial', $trialEnd);
        }

        $end = $paid && $paid->gt($trialEnd) ? $paid : $trialEnd;
        $graceEnd = $end->copy()->addDays(config('billing.grace_days'));

        if ($graceEnd->gte($today)) {
            return $this->result('grace', $graceEnd, $end);
        }

        return ['state' => 'expired', 'ends' => $end, 'days' => 0, 'blocked' => true];
    }

    public function trialEnd(Company $company): Carbon
    {
        $start = Carbon::parse($company->created_at)->startOfDay();
        $billingStart = config('billing.starts_at') ? Carbon::parse(config('billing.starts_at'))->startOfDay() : null;

        if ($billingStart && $billingStart->gt($start)) {
            $start = $billingStart;
        }

        return $start->addDays(config('billing.trial_days'));
    }

    /** Cria o pagamento e devolve o link da página do Mercado Pago. */
    public function checkout(Company $company, User $user, string $plan): string
    {
        $config = $this->plans()[$plan] ?? throw new RuntimeException('Plano inválido.');

        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'plan' => $plan,
            'amount' => $config['price'],
            'status' => 'pendente',
        ]);

        $checkout = MercadoPago::make(config('billing.mercadopago_token'))->createCheckout([
            'reference' => $payment->reference(),
            'title' => "Meu Cash · plano {$config['label']} · {$company->name}",
            'amount' => $config['price'],
            'payer_email' => $user->email,
            'back_url' => public_url(route('billing.return', [], false)),
            'notification_url' => public_url(route('webhooks.mercadopago.platform', [], false)),
        ]);

        $payment->update(['preference_id' => $checkout['id']]);

        return $checkout['url'];
    }

    /** Confere um pagamento no Mercado Pago e, se aprovado, libera o período. */
    public function confirm(string $providerId): ?SubscriptionPayment
    {
        $data = MercadoPago::make(config('billing.mercadopago_token'))->payment($providerId);

        $ref = (string) ($data['external_reference'] ?? '');
        if (! preg_match('/^assinatura-(\d+)$/', $ref, $m)) {
            return null;
        }

        $payment = SubscriptionPayment::with('company')->find((int) $m[1]);
        if (! $payment) {
            return null;
        }

        $status = $data['status'] ?? null;

        if ($status !== 'approved') {
            if ($payment->status === 'pendente' && in_array($status, ['rejected', 'cancelled'], true)) {
                $payment->update(['status' => 'recusado', 'provider_id' => $providerId]);
            }

            return $payment;
        }

        if ((float) ($data['transaction_amount'] ?? 0) + 0.009 < (float) $payment->amount) {
            report(new RuntimeException("Assinatura {$ref} paga com valor menor que o plano."));

            return $payment;
        }

        return DB::transaction(function () use ($payment, $providerId, $data) {
            $payment = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === 'pago') {
                return $payment;
            }

            $company = Company::whereKey($payment->company_id)->lockForUpdate()->first();
            [$start, $end] = $this->extend($company, (int) config("billing.plans.{$payment->plan}.months", 1));

            $payment->update([
                'status' => 'pago',
                'provider_id' => $providerId,
                'method' => $data['payment_method_id'] ?? null,
                'period_start' => $start,
                'period_end' => $end,
                'paid_at' => now(),
            ]);

            return $payment;
        });
    }

    /** Soma meses à assinatura, a partir do fim do período atual (ou de hoje, se já venceu). */
    public function extend(Company $company, int $months = 0, int $days = 0): array
    {
        $today = Carbon::today();
        $base = $today->copy();

        if ($company->paid_until && $company->paid_until->gte($base)) {
            $base = $company->paid_until->copy()->addDay();
        }

        $trialEnd = $this->trialEnd($company);
        if (! $company->paid_until && $trialEnd->gte($today)) {
            $base = $trialEnd->copy()->addDay();
        }

        $end = $base->copy()->addMonthsNoOverflow($months)->addDays($days)->subDay();
        $company->update(['paid_until' => $end->toDateString()]);

        return [$base, $end];
    }

    public function courtesy(Company $company, int $days): void
    {
        $this->extend($company, 0, $days);
        AuditService::log('configuracoes', "Cortesia de {$days} dias para {$company->name} (#{$company->id}) pela plataforma");
    }

    public function isPlatformCompany(Company $company): bool
    {
        static $cache = [];
        $admins = config('meucash.platform_admins');

        if (! $admins) {
            return false;
        }

        return $cache[$company->id.'|'.implode(',', $admins)] ??= User::query()->where('company_id', $company->id)->whereIn('email', $admins)->exists();
    }

    private function result(string $state, Carbon $ends, ?Carbon $expiredAt = null): array
    {
        return [
            'state' => $state,
            'ends' => $ends,
            'expired_at' => $expiredAt,
            'days' => (int) Carbon::today()->diffInDays($ends, false),
            'blocked' => false,
        ];
    }
}
