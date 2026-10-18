<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pagamento feito pelo Mercado Pago.
 * kind "plano": a empresa pagando o Meu Cash. kind "parcela": o cliente pagando a loja.
 * Não usa o filtro automático de empresa porque o aviso do Mercado Pago chega sem ninguém logado.
 */
class OnlinePayment extends Model
{
    protected $fillable = [
        'company_id', 'kind', 'external_reference', 'preference_id', 'mp_payment_id', 'installment_id',
        'customer_id', 'user_id', 'period', 'amount', 'status', 'method', 'paid_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class)->withoutGlobalScopes();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'aprovado' => 'Aprovado',
            'pendente' => 'Aguardando',
            'recusado' => 'Recusado',
            'estornado' => 'Estornado',
            'divergente' => 'Valor diferente',
            default => 'Não concluído',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'aprovado' => 'success',
            'pendente' => 'warning',
            'recusado', 'estornado', 'divergente' => 'danger',
            default => 'secondary',
        };
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'pix' => 'Pix',
            'credito' => 'Cartão de crédito',
            'debito' => 'Cartão de débito',
            'boleto' => 'Boleto',
            null => '—',
            default => 'Outro',
        };
    }
}
