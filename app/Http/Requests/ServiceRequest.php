<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\ServiceStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServiceRequest extends MoneyRequest
{
    protected array $moneyFields = ['amount', 'down_payment'];

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->isMethod('POST') && $this->input('down_payment') === null) {
            $this->merge(['down_payment' => $this->input('payment_method') === 'parcelado' ? 0 : (float) $this->input('amount', 0)]);
        }
    }

    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::enum(ServiceStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'amount' => [$creating ? 'required' : 'prohibited', 'numeric', 'min:0', 'max:9999999'],
            'payment_method' => [$creating ? 'required' : 'prohibited', Rule::enum(PaymentMethod::class)],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'installments_count' => ['nullable', 'integer', 'min:1', 'max:60'],
            'first_due_date' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->isMethod('POST')) {
                    return;
                }

                $remaining = (float) $this->input('amount', 0) - (float) $this->input('down_payment', 0);

                if ($remaining < -0.001) {
                    $validator->errors()->add('down_payment', 'A entrada não pode ser maior que o valor do serviço.');
                }

                if ($remaining > 0.009 && blank($this->input('first_due_date'))) {
                    $validator->errors()->add('first_due_date', 'Informe o vencimento da primeira parcela.');
                }
            },
        ];
    }
}
