<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'document', 'phone', 'whatsapp', 'email', 'city', 'address', 'notes'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
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
