<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyInvitation extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'email', 'role', 'invited_by', 'accepted_at'];

    protected function casts(): array
    {
        return ['role' => UserRole::class, 'accepted_at' => 'datetime'];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at');
    }

    public static function pendingFor(string $email): ?self
    {
        return static::withoutGlobalScope('company')
            ->whereNull('accepted_at')
            ->where('email', mb_strtolower($email))
            ->latest('id')
            ->first();
    }
}
