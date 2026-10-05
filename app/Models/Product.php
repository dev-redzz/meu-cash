<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'brand', 'model', 'purchase_date', 'purchase_price', 'expenses_total',
        'sale_price', 'initial_quantity', 'quantity', 'status', 'notes', 'photos',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'status' => ProductStatus::class,
            'photos' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProductExpense::class)->latest('date');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function totalInvested(): float
    {
        return round((float) $this->purchase_price * max(1, $this->initial_quantity) + (float) $this->expenses_total, 2);
    }

    public function unitCost(): float
    {
        return round($this->totalInvested() / max(1, $this->initial_quantity), 2);
    }

    public function expectedProfit(): float
    {
        return round((float) $this->sale_price * max(1, $this->initial_quantity) - $this->totalInvested(), 2);
    }

    public function stockInvested(): float
    {
        return round($this->unitCost() * $this->quantity, 2);
    }

    public function stockSaleValue(): float
    {
        return round((float) $this->sale_price * $this->quantity, 2);
    }

    public function photoUrls(): array
    {
        return array_map(fn ($path) => asset('storage/'.$path), $this->photos ?? []);
    }

    public function fullName(): string
    {
        return trim($this->name.' '.($this->model && ! str_contains($this->name, $this->model) ? $this->model : ''));
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereIn('status', ProductStatus::inStock())->where('quantity', '>', 0);
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query->whereIn('status', [ProductStatus::Disponivel->value, ProductStatus::Reservado->value])->where('quantity', '>', 0);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('model', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%")))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['brand'] ?? null, fn ($q, $v) => $q->where('brand', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(to_decimal($filters['min_price'] ?? null), fn ($q, $v) => $q->where('sale_price', '>=', $v))
            ->when(to_decimal($filters['max_price'] ?? null), fn ($q, $v) => $q->where('sale_price', '<=', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('purchase_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('purchase_date', '<=', $v));
    }
}
