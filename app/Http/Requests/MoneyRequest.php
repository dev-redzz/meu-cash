<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class MoneyRequest extends FormRequest
{
    protected array $moneyFields = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        foreach ($this->moneyFields as $field) {
            if ($this->has($field)) {
                $data[$field] = to_decimal($this->input($field));
            }
        }

        $this->merge($data);
    }
}
