<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        private readonly InstallmentService $installments,
        private readonly NotificationService $notifications,
    ) {
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $lines = [];
            $subtotal = 0;
            $cost = 0;

            foreach ($data['items'] as $index => $item) {
                $quantity = max(1, (int) $item['quantity']);
                $price = round((float) $item['unit_price'], 2);
                $product = null;
                $unitCost = round((float) ($item['unit_cost'] ?? 0), 2);

                if (! empty($item['product_id'])) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (! $product || ! in_array($product->status, [ProductStatus::Disponivel, ProductStatus::Reservado], true) || $product->quantity < $quantity) {
                        throw ValidationException::withMessages([
                            "items.{$index}.product_id" => 'Produto indisponível ou sem estoque suficiente'.($product ? " ({$product->fullName()}: {$product->quantity} em estoque)." : '.'),
                        ]);
                    }

                    $unitCost = $product->unitCost();
                }

                $lines[] = [
                    'product' => $product,
                    'description' => $product ? $product->fullName() : trim($item['description']),
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'unit_cost' => $unitCost,
                    'total' => round($price * $quantity, 2),
                ];

                $subtotal += $price * $quantity;
                $cost += $unitCost * $quantity;
            }

            $subtotal = round($subtotal, 2);
            $discount = min(round((float) ($data['discount'] ?? 0), 2), $subtotal);
            $total = round($subtotal - $discount, 2);
            $down = min(round((float) ($data['down_payment'] ?? 0), 2), $total);
            $remaining = round($total - $down, 2);
            $count = $remaining > 0 ? max(1, (int) ($data['installments_count'] ?? 1)) : 0;

            $sale = Sale::create([
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => auth()->id(),
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'cost_total' => round($cost, 2),
                'profit' => round($total - $cost, 2),
                'down_payment' => $down,
                'payment_method' => $data['payment_method'],
                'installments_count' => $count,
                'status' => SaleStatus::Concluida,
                'notes' => $data['notes'] ?? null,
            ]);

            $sale->update(['code' => 'V'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($lines as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']?->id,
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'unit_cost' => $line['unit_cost'],
                    'total' => $line['total'],
                ]);

                if ($line['product']) {
                    $this->decrementStock($line['product'], $line['quantity'], $sale);
                }
            }

            $downMethod = $data['down_payment_method'] ?? $data['payment_method'];
            $this->installments->registerDownPayment($sale, $down, $sale->sale_date, $downMethod === 'parcelado' ? 'outro' : $downMethod);

            if ($remaining > 0) {
                $this->installments->generate(
                    $sale,
                    $sale->customer_id,
                    $remaining,
                    $count,
                    $data['first_due_date'] ?? Carbon::parse($sale->sale_date)->addMonthNoOverflow(),
                    (int) ($data['interval_days'] ?? 0),
                );
            }

            $this->notifications->notify('venda_concluida', "Venda {$sale->code} concluída", $sale->customerName().' · '.money($total), route('sales.show', $sale));
            AuditService::log('venda_criada', "Venda {$sale->code} de ".money($total).' para '.$sale->customerName(), $sale);

            return $sale;
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        $sale->update($data);

        if (array_key_exists('customer_id', $data)) {
            $sale->installments()->update(['customer_id' => $data['customer_id']]);
            $sale->payments()->update(['customer_id' => $data['customer_id']]);
        }

        AuditService::log('venda_editada', "Venda {$sale->code} editada", $sale);

        return $sale;
    }

    public function cancel(Sale $sale): void
    {
        if ($sale->status === SaleStatus::Cancelada) {
            return;
        }

        DB::transaction(function () use ($sale) {
            foreach ($sale->items()->with('product')->get() as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                    if ($item->product->status === ProductStatus::Vendido) {
                        $item->product->update(['status' => ProductStatus::Disponivel]);
                    }
                }
            }

            $this->installments->cancelOpen($sale);
            $sale->update(['status' => SaleStatus::Cancelada]);

            AuditService::log('venda_cancelada', "Venda {$sale->code} cancelada", $sale);
        });
    }

    private function decrementStock(Product $product, int $quantity, Sale $sale): void
    {
        $product->decrement('quantity', $quantity);
        $product->refresh();

        if ($product->quantity <= 0) {
            $product->update(['status' => ProductStatus::Vendido]);
        }

        $this->notifications->notify('produto_vendido', "Produto vendido · {$product->fullName()}", "Venda {$sale->code} · {$quantity} un.", route('products.show', $product));
        AuditService::log('produto_vendido', "{$quantity}x {$product->fullName()} na venda {$sale->code}", $product);
    }
}
