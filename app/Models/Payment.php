<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['installment_id', 'sale_id', 'customer_id', 'user_id', 'amount', 'paid_at', 'payment_method', 'notes'];

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function description(): string
    {
        $origin = 'Venda '.($this->sale?->code ?? '');

        return $this->installment_id ? "Parcela {$this->installment?->label()} · {$origin}" : "Entrada · {$origin}";
    }
}
