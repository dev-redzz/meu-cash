<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Erro registrado pelo monitoramento. Fica fora do filtro de empresa: só o dono da plataforma vê. */
class ErrorReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fingerprint', 'class', 'message', 'file', 'line', 'url', 'method', 'user_id', 'company_id',
        'trace', 'count', 'first_seen_at', 'last_seen_at', 'notified_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shortClass(): string
    {
        return class_basename($this->class);
    }

    public function shortFile(): string
    {
        return $this->file ? str_replace(base_path().DIRECTORY_SEPARATOR, '', $this->file) : '—';
    }
}
