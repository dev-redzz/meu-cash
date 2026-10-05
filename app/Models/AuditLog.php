<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'description', 'auditable_type', 'auditable_id', 'ip_address', 'user_agent'];

    public const ACTIONS = [
        'login' => 'Login',
        'logout' => 'Logout',
        'venda_criada' => 'Venda criada',
        'venda_editada' => 'Venda editada',
        'venda_cancelada' => 'Venda cancelada',
        'pagamento_registrado' => 'Pagamento registrado',
        'parcela_alterada' => 'Parcela alterada',
        'produto_cadastrado' => 'Produto cadastrado',
        'produto_editado' => 'Produto editado',
        'produto_excluido' => 'Produto excluído',
        'produto_vendido' => 'Produto vendido',
        'despesa_registrada' => 'Despesa registrada',
        'despesa_removida' => 'Despesa removida',
        'cliente_cadastrado' => 'Cliente cadastrado',
        'cliente_editado' => 'Cliente editado',
        'cliente_excluido' => 'Cliente excluído',
        'servico_criado' => 'Serviço criado',
        'servico_editado' => 'Serviço editado',
        'servico_cancelado' => 'Serviço cancelado',
        'conta_criada' => 'Conta a pagar criada',
        'conta_paga' => 'Conta paga',
        'conta_editada' => 'Conta editada',
        'conta_excluida' => 'Conta excluída',
        'lancamento_criado' => 'Lançamento manual',
        'lancamento_excluido' => 'Lançamento excluído',
        'usuario_criado' => 'Usuário criado',
        'usuario_editado' => 'Usuário editado',
        'configuracoes' => 'Configurações alteradas',
        'backup_gerado' => 'Backup gerado',
        'backup_restaurado' => 'Backup restaurado',
        'whatsapp' => 'Mensagem WhatsApp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
