<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** Cópia temporária do que foi excluído, para o botão "Desfazer". */
class UndoSnapshot extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'user_id', 'token', 'label', 'payload', 'files', 'expires_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'files' => 'array', 'expires_at' => 'datetime'];
    }
}
