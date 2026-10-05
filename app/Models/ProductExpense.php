<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductExpense extends Model
{
    protected $fillable = ['product_id', 'description', 'category', 'amount', 'date', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'date' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function categoryLabel(): string
    {
        return config("meucash.product_expense_categories.{$this->category}", $this->category);
    }
}
