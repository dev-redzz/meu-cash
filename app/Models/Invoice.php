<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use BelongsToCompany;

    public const LABELS = [
        'pendente' => 'Processando',
        'autorizado' => 'Autorizada',
        'rejeitado' => 'Rejeitada',
        'denegado' => 'Denegada',
        'erro' => 'Erro',
        'cancelado' => 'Cancelada',
    ];

    protected $fillable = [
        'sale_id', 'model', 'environment', 'series', 'number', 'status', 'provider_id',
        'access_key', 'protocol', 'total', 'message', 'authorized_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'authorized_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }

    public function pillClass(): string
    {
        return match ($this->status) {
            'autorizado' => 'pill-success',
            'pendente' => 'pill-info',
            'cancelado' => 'pill-secondary',
            default => 'pill-danger',
        };
    }

    public function isAuthorized(): bool
    {
        return $this->status === 'autorizado';
    }

    /** Rejeitada ou com erro: dá para corrigir e transmitir de novo com o mesmo número. */
    public function canRetry(): bool
    {
        return in_array($this->status, ['rejeitado', 'erro'], true);
    }

    public function isTest(): bool
    {
        return $this->environment !== 'producao';
    }

    public function numberLabel(): string
    {
        return 'NFC-e '.str_pad((string) $this->number, 6, '0', STR_PAD_LEFT).' · série '.$this->series;
    }
}
