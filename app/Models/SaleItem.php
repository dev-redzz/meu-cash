<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'description', 'quantity', 'unit_price', 'unit_cost', 'total'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'unit_cost' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function profit(): float
    {
        return round((float) $this->total - (float) $this->unit_cost * $this->quantity, 2);
    }
}
