<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'document', 'phone', 'active',
        'plan_period', 'plan_expires_at', 'plan_exempt',
        'mp_access_token', 'mp_account', 'mp_webhook_key',
    ];

    protected $hidden = ['mp_access_token', 'mp_webhook_key'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'plan_exempt' => 'boolean',
            'plan_expires_at' => 'datetime',
            'mp_access_token' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (! $company->plan_expires_at) {
                $company->plan_period ??= 'teste';
                $company->plan_expires_at = now()->addDays(config('billing.trial_days', 14));
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function onlinePayments(): HasMany
    {
        return $this->hasMany(OnlinePayment::class);
    }

    /* ---------- Plano do sistema ---------- */

    public static function billingEnabled(): bool
    {
        return config('billing.enabled') && filled(config('billing.mp_access_token'));
    }

    public function onTrial(): bool
    {
        return $this->plan_period === 'teste';
    }

    /** Último dia de uso liberado, já com a tolerância. */
    public function planGraceEndsAt(): ?Carbon
    {
        return $this->plan_expires_at?->copy()->addDays(config('billing.grace_days', 3));
    }

    public function planActive(): bool
    {
        if ($this->plan_exempt || ! static::billingEnabled()) {
            return true;
        }

        return $this->plan_expires_at === null || $this->planGraceEndsAt()->isFuture();
    }

    public function planExpired(): bool
    {
        return ! $this->plan_exempt && $this->plan_expires_at !== null && $this->plan_expires_at->isPast();
    }

    public function planDaysLeft(): ?int
    {
        return $this->plan_expires_at ? (int) floor(now()->diffInDays($this->plan_expires_at, false)) : null;
    }

    public function planLabel(): string
    {
        return match (true) {
            $this->plan_exempt => 'Isento',
            $this->onTrial() => 'Teste grátis',
            default => config("billing.plans.{$this->plan_period}.label", 'Plano'),
        };
    }

    /* ---------- Mercado Pago da loja (recebe as parcelas) ---------- */

    public function acceptsOnlinePayments(): bool
    {
        return filled($this->mp_access_token);
    }

    public function ensureWebhookKey(): string
    {
        if (! $this->mp_webhook_key) {
            $this->forceFill(['mp_webhook_key' => Str::random(40)])->save();
        }

        return $this->mp_webhook_key;
    }
}
