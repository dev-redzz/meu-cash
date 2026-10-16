<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'document', 'phone', 'whatsapp', 'email', 'city', 'address', 'notes'];

    /** Letras e números fáceis de ler (sem 0/O, 1/I/L). */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->access_code ??= static::newAccessCode();
        });
    }

    /** Código único (entre todas as empresas) para o cliente entrar na Área do cliente. Ex.: K7QM-4XPA */
    public static function newAccessCode(): string
    {
        do {
            $raw = '';
            for ($i = 0; $i < 8; $i++) {
                $raw .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
            $code = substr($raw, 0, 4).'-'.substr($raw, 4);
        } while (static::withoutGlobalScopes()->where('access_code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(?string $input): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $input));

        return strlen($clean) === 8 ? substr($clean, 0, 4).'-'.substr($clean, 4) : $clean;
    }

    public function ensureAccessCode(): string
    {
        if (! $this->access_code) {
            $this->forceFill(['access_code' => static::newAccessCode()])->save();
        }

        return $this->access_code;
    }

    public function regenerateAccessCode(): string
    {
        $this->forceFill(['access_code' => static::newAccessCode()])->save();

        return $this->access_code;
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function openInstallments(): HasMany
    {
        return $this->installments()->whereIn('status', InstallmentStatus::open());
    }

    public function whatsappNumber(): ?string
    {
        $number = only_digits($this->whatsapp ?: $this->phone);

        return $number !== '' ? $number : null;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('document', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('whatsapp', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
