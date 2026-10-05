<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialTransaction extends Model
{
    protected $fillable = ['type', 'origin', 'category', 'description', 'amount', 'date', 'payment_method', 'source_type', 'source_id', 'user_id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'type' => TransactionType::class,
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originLabel(): string
    {
        return config("meucash.origins.{$this->origin}", $this->origin);
    }

    public function categoryLabel(): string
    {
        $categories = config('meucash.transaction_categories.'.$this->type->value, []);

        return $categories[$this->category]
            ?? config("meucash.product_expense_categories.{$this->category}")
            ?? config("meucash.payable_categories.{$this->category}")
            ?? ($this->category ?: '—');
    }

    public function isManual(): bool
    {
        return $this->origin === 'manual';
    }

    public function scopeBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }
}
