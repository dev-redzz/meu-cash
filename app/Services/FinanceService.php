<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Enums\TransactionType;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Support\Period;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FinanceService
{
    public function record(
        TransactionType $type,
        string $origin,
        string $description,
        float $amount,
        mixed $date,
        ?string $method = null,
        ?string $category = null,
        ?Model $source = null,
    ): ?FinancialTransaction {
        if ($amount <= 0) {
            return null;
        }

        return FinancialTransaction::create([
            'type' => $type,
            'origin' => $origin,
            'category' => $category,
            'description' => mb_substr($description, 0, 255),
            'amount' => round($amount, 2),
            'date' => Carbon::parse($date)->toDateString(),
            'payment_method' => $method,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'user_id' => auth()->id(),
        ]);
    }

    public function removeFor(Model $source): void
    {
        FinancialTransaction::where('source_type', $source->getMorphClass())->where('source_id', $source->getKey())->delete();
    }

    public function balance(): float
    {
        $in = (float) FinancialTransaction::where('type', TransactionType::Entrada->value)->sum('amount');
        $out = (float) FinancialTransaction::where('type', TransactionType::Saida->value)->sum('amount');

        return round((float) Setting::get('initial_balance', 0) + $in - $out, 2);
    }

    public function totalsBetween(Carbon $start, Carbon $end): array
    {
        $rows = FinancialTransaction::between($start, $end)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $in = round((float) ($rows[TransactionType::Entrada->value] ?? 0), 2);
        $out = round((float) ($rows[TransactionType::Saida->value] ?? 0), 2);

        return ['in' => $in, 'out' => $out, 'net' => round($in - $out, 2)];
    }

    public function stockTotals(): array
    {
        $invested = 0;
        $saleValue = 0;
        $count = 0;

        Product::inStock()->get()->each(function (Product $product) use (&$invested, &$saleValue, &$count) {
            $invested += $product->stockInvested();
            $saleValue += $product->stockSaleValue();
            $count += $product->quantity;
        });

        return [
            'invested' => round($invested, 2),
            'sale_value' => round($saleValue, 2),
            'potential_profit' => round($saleValue - $invested, 2),
            'units' => $count,
        ];
    }

    public function receivableTotal(): float
    {
        return round((float) Installment::open()->sum('amount'), 2);
    }

    public function summary(Period $period): array
    {
        $sales = Sale::completed()->whereBetween('sale_date', [$period->startDate(), $period->endDate()]);
        $cash = $this->totalsBetween($period->start, $period->end);

        return [
            'balance' => $this->balance(),
            'revenue' => round((float) (clone $sales)->sum('total'), 2),
            'received' => $cash['in'],
            'spent' => $cash['out'],
            'net' => $cash['net'],
            'receivable' => $this->receivableTotal(),
            'profit' => round((float) (clone $sales)->sum('profit'), 2),
            'sales_count' => (clone $sales)->count(),
            'stock' => $this->stockTotals(),
        ];
    }

    public function compare(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.01) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    public function topExpenses(Period $period, int $limit = 3): array
    {
        return FinancialTransaction::between($period->start, $period->end)
            ->where('type', TransactionType::Saida->value)
            ->selectRaw('origin, category, SUM(amount) as total')
            ->groupBy('origin', 'category')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->category
                    ? (config('meucash.product_expense_categories.'.$row->category) ?? config('meucash.transaction_categories.saida.'.$row->category) ?? config('meucash.origins.'.$row->origin))
                    : config('meucash.origins.'.$row->origin, $row->origin),
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }

    public static function receivedStatusValue(): string
    {
        return InstallmentStatus::Pago->value;
    }
}
