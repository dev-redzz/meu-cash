<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['company_id', 'name', 'email', 'email_verified_at', 'google_id', 'avatar', 'password', 'role', 'active', 'commission_rate', 'monthly_goal'];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
            'commission_rate' => 'decimal:2',
            'monthly_goal' => 'decimal:2',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPlatformAdmin(): bool
    {
        return in_array(mb_strtolower($this->email), config('meucash.platform_admins'), true);
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
