<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;

class ProductRequest extends MoneyRequest
{
    protected array $moneyFields = ['purchase_price', 'sale_price'];

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $digits = [];
        foreach (['ncm', 'cest', 'cfop'] as $field) {
            if ($this->filled($field)) {
                $digits[$field] = preg_replace('/\D/', '', (string) $this->input($field));
            }
        }
        $digits['unit'] = $this->filled('unit') ? strtoupper(trim((string) $this->input('unit'))) : 'UN';
        $digits['origin'] = $this->filled('origin') ? $this->input('origin') : 0;
        $digits['in_catalog'] = $this->has('in_catalog') ? $this->boolean('in_catalog') : true;

        $this->merge($digits);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', tenant_id())],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->where('company_id', tenant_id())->ignore($this->route('product'))],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'min_stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'register_purchase' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
            'ncm' => ['nullable', 'regex:/^\d{8}$/'],
            'cest' => ['nullable', 'regex:/^\d{7}$/'],
            'cfop' => ['nullable', 'regex:/^5\d{3}$/'],
            'csosn' => ['nullable', Rule::in(array_keys(\App\Services\Fiscal\InvoiceService::CSOSN))],
            'origin' => ['nullable', 'integer', 'between:0,8'],
            'unit' => ['nullable', 'string', 'max:6'],
            'in_catalog' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'ncm.regex' => 'O NCM tem 8 números (ex.: 8517.13.00).',
            'cest.regex' => 'O CEST tem 7 números.',
            'cfop.regex' => 'Use um CFOP de venda dentro do estado (começa com 5, ex.: 5102).',
        ];
    }
}
