<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaleRequest extends MoneyRequest
{
    protected array $moneyFields = ['discount', 'down_payment'];

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && (filled($item['product_id'] ?? null) || filled($item['description'] ?? null)))
            ->map(fn ($item) => [
                'product_id' => filled($item['product_id'] ?? null) ? (int) $item['product_id'] : null,
                'description' => $item['description'] ?? null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => to_decimal($item['unit_price'] ?? 0),
                'unit_cost' => to_decimal($item['unit_cost'] ?? 0),
            ])
            ->values()
            ->all();

        $subtotal = collect($items)->sum(fn ($i) => (float) $i['unit_price'] * $i['quantity']);
        $total = max(0, $subtotal - (float) ($this->input('discount') ?? 0));
        $down = $this->input('down_payment');

        if ($down === null) {
            $down = $this->input('payment_method') === 'parcelado' ? 0 : $total;
        }

        $this->merge(['items' => $items, 'discount' => $this->input('discount') ?? 0, 'down_payment' => $down]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'sale_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.description' => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'down_payment_method' => ['nullable', Rule::in(array_keys(PaymentMethod::receiving()))],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'installments_count' => ['nullable', 'integer', 'min:1', 'max:60'],
            'first_due_date' => ['nullable', 'date'],
            'interval_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $subtotal = collect($this->input('items', []))->sum(fn ($i) => (float) ($i['unit_price'] ?? 0) * (int) ($i['quantity'] ?? 1));
                $total = $subtotal - (float) $this->input('discount', 0);

                if ($total < 0) {
                    $validator->errors()->add('discount', 'O desconto não pode ser maior que o subtotal.');
                }

                if ((float) $this->input('down_payment', 0) > $total + 0.001) {
                    $validator->errors()->add('down_payment', 'A entrada não pode ser maior que o total da venda.');
                }

                if ($total - (float) $this->input('down_payment', 0) > 0.009 && blank($this->input('first_due_date'))) {
                    $validator->errors()->add('first_due_date', 'Informe o vencimento da primeira parcela.');
                }
            },
        ];
    }
}
