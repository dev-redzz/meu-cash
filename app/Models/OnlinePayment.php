<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePayment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'installment_id', 'customer_id', 'provider_id', 'status', 'amount',
        'qr_code', 'qr_code_base64', 'ticket_url', 'expires_at', 'paid_at', 'checked_at',
    ];

    protected $hidden = ['qr_code_base64'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pendente' && $this->expires_at?->isFuture();
    }

    public function isPaid(): bool
    {
        return $this->status === 'pago';
    }
}
