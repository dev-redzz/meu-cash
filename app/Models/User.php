<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'email', 'google_id', 'avatar', 'password', 'role', 'active'];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function firstName(): string
    {
        return (string) str($this->name)->explode(' ')->first();
    }

    public function initials(): string
    {
        $parts = str($this->name)->explode(' ')->filter()->values();

        return mb_strtoupper(mb_substr($parts->first() ?? '?', 0, 1).mb_substr($parts->count() > 1 ? $parts->last() : '', 0, 1));
    }
}
