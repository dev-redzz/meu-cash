<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Installment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'sale_id', 'customer_id', 'number', 'total_count', 'amount', 'due_date',
        'status', 'paid_at', 'payment_method', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
            'status' => InstallmentStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status->value, InstallmentStatus::open(), true);
    }

    public function label(): string
    {
        return $this->number.'/'.$this->total_count;
    }

    public function originLabel(): string
    {
        return 'Venda '.($this->sale?->code ?? '#'.$this->sale_id);
    }

    public function originUrl(): ?string
    {
        return $this->sale_id ? route('sales.show', $this->sale_id) : null;
    }

    public function daysOverdue(): int
    {
        return max(0, (int) $this->due_date->diffInDays(now()->startOfDay(), false));
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', InstallmentStatus::open());
    }
}
