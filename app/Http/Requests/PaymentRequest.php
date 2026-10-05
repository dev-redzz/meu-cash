<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_at' => ['required', 'date', 'before_or_equal:'.now()->addDay()->toDateString()],
            'payment_method' => ['required', Rule::in(array_keys(PaymentMethod::receiving()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
