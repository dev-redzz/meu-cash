<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccountPayable extends Model
{
    protected $table = 'accounts_payable';

    protected $fillable = ['description', 'category', 'amount', 'due_date', 'paid_at', 'status', 'payment_method', 'notes'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
            'status' => InstallmentStatus::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function categoryLabel(): string
    {
        return config("meucash.payable_categories.{$this->category}", $this->category);
    }

    public function isOpen(): bool
    {
        return in_array($this->status->value, InstallmentStatus::open(), true);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', InstallmentStatus::open());
    }
}
