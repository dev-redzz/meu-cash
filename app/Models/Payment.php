<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['installment_id', 'sale_id', 'service_id', 'customer_id', 'user_id', 'amount', 'paid_at', 'payment_method', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'date', 'payment_method' => PaymentMethod::class];
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
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

    public function description(): string
    {
        $origin = $this->sale_id ? 'Venda '.($this->sale?->code ?? '') : 'Serviço '.($this->service?->name ?? '');

        return $this->installment_id ? "Parcela {$this->installment?->label()} · {$origin}" : "Entrada · {$origin}";
    }
}
