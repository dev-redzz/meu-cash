<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use BelongsToCompany;

    protected $table = 'notifications';

    protected $fillable = ['key', 'type', 'title', 'message', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public const TYPES = [
        'parcela_proxima' => ['Parcela próxima', 'warning'],
        'parcela_vencida' => ['Parcela vencida', 'danger'],
        'pagamento_recebido' => ['Pagamento recebido', 'success'],
        'venda_concluida' => ['Venda concluída', 'success'],
        'produto_vendido' => ['Produto vendido', 'info'],
        'produto_manutencao' => ['Produto em manutenção', 'secondary'],
        'estoque_baixo' => ['Estoque baixo', 'warning'],
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
