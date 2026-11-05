<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sugestões e problemas enviados pelos usuários durante a versão beta. Só o dono da plataforma vê. */
class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = ['company_id', 'user_id', 'type', 'message', 'page', 'read_at'];

    public const TYPES = [
        'sugestao' => ['Sugestão', 'info'],
        'problema' => ['Problema', 'danger'],
        'elogio' => ['Elogio', 'success'],
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeColor(): string
    {
        return self::TYPES[$this->type][1] ?? 'secondary';
    }
}
