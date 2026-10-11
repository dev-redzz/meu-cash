<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToCompany;

    protected $fillable = ['name', 'type'];

    protected function casts(): array
    {
        return ['type' => CategoryType::class];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeOfType(Builder $query, CategoryType $type): Builder
    {
        return $query->where('type', $type->value)->orderBy('name');
    }
}
