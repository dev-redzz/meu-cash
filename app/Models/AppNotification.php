<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['key', 'type', 'title', 'message', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public const TYPES = [
        'parcela_proxima' => ['Parcela próxima', 'warning'],
        'parcela_vencida' => ['Parcela vencida', 'danger'],
        'conta_proxima' => ['Conta próxima', 'warning'],
        'conta_vencida' => ['Conta vencida', 'danger'],
        'pagamento_recebido' => ['Pagamento recebido', 'success'],
        'venda_concluida' => ['Venda concluída', 'success'],
        'produto_vendido' => ['Produto vendido', 'info'],
        'produto_manutencao' => ['Produto em manutenção', 'secondary'],
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeColor(): string
    {
        return self::TYPES[$this->type][1] ?? 'secondary';
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
