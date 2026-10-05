<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Installment extends Model
{
    protected $fillable = [
        'sale_id', 'service_id', 'customer_id', 'number', 'total_count', 'amount', 'due_date',
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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
        if ($this->sale_id) {
            return 'Venda '.($this->sale?->code ?? '#'.$this->sale_id);
        }

        return 'Serviço: '.($this->service?->name ?? '#'.$this->service_id);
    }

    public function originUrl(): ?string
    {
        if ($this->sale_id) {
            return route('sales.show', $this->sale_id);
        }

        return $this->service_id ? route('services.show', $this->service_id) : null;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', InstallmentStatus::open());
    }
}
