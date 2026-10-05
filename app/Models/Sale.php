<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'code', 'customer_id', 'user_id', 'sale_date', 'subtotal', 'discount', 'total', 'cost_total',
        'profit', 'down_payment', 'payment_method', 'installments_count', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'cost_total' => 'decimal:2',
            'profit' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => SaleStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function receivedAmount(): float
    {
        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function openAmount(): float
    {
        return round((float) $this->installments()->whereIn('status', InstallmentStatus::open())->sum('amount'), 2);
    }

    public function customerName(): string
    {
        return $this->customer?->name ?? 'Consumidor final';
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', SaleStatus::Concluida->value);
    }
}
