<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(CategoryType::class)],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories')->where('type', $this->input('type'))->ignore($this->route('category')),
            ],
        ];
    }
}
