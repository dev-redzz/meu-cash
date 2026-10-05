<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use Illuminate\Validation\Rule;

class TransactionRequest extends MoneyRequest
{
    protected array $moneyFields = ['amount'];

    public function rules(): array
    {
        $categories = array_keys(config('meucash.transaction_categories.'.$this->input('type'), []));

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'category' => ['required', Rule::in($categories)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'date' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethod::receiving()))],
        ];
    }
}
