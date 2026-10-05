<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ExpenseRequest extends MoneyRequest
{
    protected array $moneyFields = ['amount'];

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'category' => [$this->routeIs('products.*') ? 'required' : 'nullable', Rule::in(array_keys(config('meucash.product_expense_categories')))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'register_cash' => ['nullable', 'boolean'],
        ];
    }
}
