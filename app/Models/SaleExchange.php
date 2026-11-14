<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleExchange extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'sale_id', 'user_id', 'returned', 'added', 'returned_total', 'added_total',
        'difference', 'settlement', 'payment_method', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'returned' => 'array',
            'added' => 'array',
            'returned_total' => 'decimal:2',
            'added_total' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function settlementLabel(): string
    {
        $diff = (float) $this->difference;
        $method = $this->payment_method ? \App\Enums\PaymentMethod::tryFrom($this->payment_method)?->label() : null;

        return match ($this->settlement) {
            'agora' => $diff > 0 ? 'Cliente pagou '.money($diff).($method ? " no {$method}" : '') : 'Loja devolveu '.money(abs($diff)).($method ? " no {$method}" : ''),
            'parcelas' => $diff > 0 ? money($diff).' somados às parcelas em aberto' : money(abs($diff)).' abatidos das parcelas em aberto',
            default => 'Sem diferença de valor',
        };
    }
}
