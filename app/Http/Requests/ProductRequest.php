<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;

class ProductRequest extends MoneyRequest
{
    protected array $moneyFields = ['purchase_price', 'sale_price'];

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'register_purchase' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
        ];
    }
}
