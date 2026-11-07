<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'plan', 'amount', 'status', 'preference_id',
        'provider_id', 'method', 'period_start', 'period_end', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reference(): string
    {
        return 'assinatura-'.$this->id;
    }

    public function planLabel(): string
    {
        return config("billing.plans.{$this->plan}.label", ucfirst($this->plan));
    }
}
