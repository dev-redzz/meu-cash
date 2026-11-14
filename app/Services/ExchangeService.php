<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Enums\ProductStatus;
use App\Enums\SaleStatus;
use App\Enums\TransactionType;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleExchange;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Troca: o cliente devolve itens da venda e leva outros.
 * A venda passa a mostrar o que o cliente ficou de fato, o estoque e o caixa acompanham.
 */
class ExchangeService
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly FinanceService $finance,
    ) {
    }

    /**
     * @param  array  $returns  [sale_item_id => quantidade devolvida]
     * @param  array  $added  [['product_id' => , 'quantity' => , 'unit_price' => ], ...]
     * @param  string  $settlement  'agora' (paga/devolve na hora) ou 'parcelas' (soma/abate nas parcelas em aberto)
     */
    public function exchange(Sale $sale, array $returns, array $added, string $settlement, ?string $method, ?string $notes = null): SaleExchange
    {
        if ($sale->status !== SaleStatus::Concluida) {
            throw ValidationException::withMessages(['returns' => 'Só dá para trocar itens de venda concluída.']);
        }

        return DB::transaction(function () use ($sale, $returns, $added, $settlement, $method, $notes) {
            Company::whereKey(tenant_id())->lockForUpdate()->first();
            $sale = Sale::with('items.product')->lockForUpdate()->findOrFail($sale->id);
            $oldTotal = (float) $sale->total;

            $returnedRows = [];
            foreach ($returns as $itemId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $item = $sale->items->firstWhere('id', (int) $itemId);
                if (! $item || $qty > $item->quantity) {
                    throw ValidationException::withMessages(["returns.{$itemId}" => 'Quantidade devolvida maior que a vendida.']);
                }

                $returnedRows[] = [
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $qty,
                    'unit_price' => (float) $item->unit_price,
                    'total' => round($qty * (float) $item->unit_price, 2),
                ];

                if ($item->product) {
                    $item->product->increment('quantity', $qty);
                    if ($item->product->status === ProductStatus::Vendido) {
                        $item->product->update(['status' => ProductStatus::Disponivel]);
                    }
                }

                if ($qty === (int) $item->quantity) {
                    $item->delete();
                } else {
                    $left = $item->quantity - $qty;
                    $item->update(['quantity' => $left, 'total' => round($left * (float) $item->unit_price, 2)]);
                }
            }

            $addedRows = [];
            foreach ($added as $index => $line) {
                $qty = max(1, (int) ($line['quantity'] ?? 1));
                $product = Product::lockForUpdate()->find($line['product_id'] ?? null);

                if (! $product || ! in_array($product->status, [ProductStatus::Disponivel, ProductStatus::Reservado], true) || $product->quantity < $qty) {
                    throw ValidationException::withMessages([
                        "added.{$index}.product_id" => 'Produto indisponível ou sem estoque suficiente'.($product ? " ({$product->fullName()}: {$product->quantity} em estoque)." : '.'),
                    ]);
                }

                $price = round((float) ($line['unit_price'] ?? $product->sale_price), 2);
                $sale->items()->create([
                    'product_id' => $product->id,
                    'description' => $product->fullName(),
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'unit_cost' => $product->unitCost(),
                    'total' => round($price * $qty, 2),
                ]);
                $this->sales->decrementStock($product, $qty, $sale);

                $addedRows[] = ['product_id' => $product->id, 'description' => $product->fullName(), 'quantity' => $qty, 'unit_price' => $price, 'total' => round($price * $qty, 2)];
            }

            if (! $returnedRows && ! $addedRows) {
                throw ValidationException::withMessages(['returns' => 'Escolha o que o cliente devolveu ou o que vai levar.']);
            }

            $items = $sale->items()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['returns' => 'A venda não pode ficar sem itens. Para devolver tudo, cancele a venda.']);
            }

            $subtotal = round((float) $items->sum('total'), 2);
            $discount = min((float) $sale->discount, $subtotal);
            $total = round($subtotal - $discount, 2);
            $cost = round((float) $items->sum(fn ($i) => $i->unit_cost * $i->quantity), 2);
            $difference = round($total - $oldTotal, 2);

            $sale->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'cost_total' => $cost,
                'profit' => round($total - $cost, 2),
                'commission' => round($total * (float) $sale->commission_rate / 100, 2),
            ]);

            $open = $sale->installments()->whereIn('status', [InstallmentStatus::Pendente->value, InstallmentStatus::Vencido->value])->reorder('number', 'desc')->get();
            if (abs($difference) < 0.005) {
                $settlement = 'sem_diferenca';
            } elseif ($settlement === 'parcelas' && $open->isEmpty()) {
                $settlement = 'agora';
            }

            $exchange = SaleExchange::create([
                'sale_id' => $sale->id,
                'user_id' => auth()->id(),
                'returned' => $returnedRows,
                'added' => $addedRows,
                'returned_total' => round(array_sum(array_column($returnedRows, 'total')), 2),
                'added_total' => round(array_sum(array_column($addedRows, 'total')), 2),
                'difference' => $difference,
                'settlement' => $settlement,
                'payment_method' => $settlement === 'agora' ? ($method ?: 'dinheiro') : null,
                'notes' => $notes,
            ]);

            if ($settlement === 'agora') {
                $this->settleNow($sale, $exchange, $difference, $method ?: 'dinheiro');
            } elseif ($settlement === 'parcelas') {
                $this->settleOnInstallments($sale, $open, $difference);
            }

            AuditService::log('venda_editada', "Troca na venda {$sale->code}: ".$exchange->settlementLabel(), $sale);

            return $exchange;
        });
    }

    private function settleNow(Sale $sale, SaleExchange $exchange, float $difference, string $method): void
    {
        $payment = Payment::create([
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'user_id' => auth()->id(),
            'amount' => $difference,
            'paid_at' => now()->toDateString(),
            'payment_method' => $method,
            'notes' => $difference > 0 ? 'Diferença da troca' : 'Devolução da troca',
        ]);

        $this->finance->record(
            $difference > 0 ? TransactionType::Entrada : TransactionType::Saida,
            'venda',
            ($difference > 0 ? 'Diferença da troca' : 'Devolução da troca')." · venda {$sale->code} · ".$sale->customerName(),
            abs($difference),
            now(),
            $method,
            $difference > 0 ? null : 'Devoluções',
            $payment,
        );
    }

    /** Soma a diferença na última parcela em aberto, ou abate das parcelas (da última para a primeira). */
    private function settleOnInstallments(Sale $sale, $open, float $difference): void
    {
        if ($difference > 0) {
            $last = $open->first();
            $last->update(['amount' => round((float) $last->amount + $difference, 2)]);

            return;
        }

        $left = abs($difference);
        foreach ($open as $installment) {
            if ($left <= 0.004) {
                break;
            }
            $cut = min($left, (float) $installment->amount);
            $amount = round((float) $installment->amount - $cut, 2);
            $left = round($left - $cut, 2);

            $amount <= 0.004
                ? $installment->update(['amount' => 0, 'status' => InstallmentStatus::Cancelado->value, 'notes' => 'Abatida na troca'])
                : $installment->update(['amount' => $amount]);
        }

        if ($left > 0.004) {
            // Sobrou crédito além das parcelas em aberto: devolve na hora.
            $this->settleNow($sale, SaleExchange::where('sale_id', $sale->id)->latest('id')->first(), -$left, 'dinheiro');
        }
    }
}
