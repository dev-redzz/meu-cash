<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'customer_id', 'category_id', 'user_id', 'name', 'date', 'amount', 'expenses_total',
        'down_payment', 'payment_method', 'installments_count', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => ServiceStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ServiceExpense::class)->latest('date');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function profit(): float
    {
        return round((float) $this->amount - (float) $this->expenses_total, 2);
    }

    public function receivedAmount(): float
    {
        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function openAmount(): float
    {
        return round((float) $this->installments()->whereIn('status', InstallmentStatus::open())->sum('amount'), 2);
    }

    public function scopeBillable(Builder $query): Builder
    {
        return $query->whereIn('status', [ServiceStatus::EmAndamento->value, ServiceStatus::Concluido->value]);
    }
}
