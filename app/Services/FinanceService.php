<?php

namespace App\Services;

use App\Enums\InstallmentStatus;
use App\Enums\TransactionType;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
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
        $start = $period->startDate();
        $end = $period->endDate();

        $sales = Sale::completed()->whereBetween('sale_date', [$start, $end]);
        $services = Service::billable()->whereBetween('date', [$start, $end]);

        $salesRevenue = (float) (clone $sales)->sum('total');
        $salesProfit = (float) (clone $sales)->sum('profit');
        $serviceRevenue = (float) (clone $services)->sum('amount');
        $serviceProfit = (float) (clone $services)->selectRaw('COALESCE(SUM(amount - expenses_total), 0) as p')->value('p');

        $cash = $this->totalsBetween($period->start, $period->end);

        return [
            'balance' => $this->balance(),
            'revenue' => round($salesRevenue + $serviceRevenue, 2),
            'sales_revenue' => round($salesRevenue, 2),
            'service_revenue' => round($serviceRevenue, 2),
            'received' => $cash['in'],
            'spent' => $cash['out'],
            'net' => $cash['net'],
            'receivable' => $this->receivableTotal(),
            'profit' => round($salesProfit + $serviceProfit, 2),
            'sales_profit' => round($salesProfit, 2),
            'service_profit' => round($serviceProfit, 2),
            'sales_count' => (clone $sales)->count(),
            'services_count' => (clone $services)->count(),
        ] + ['stock' => $this->stockTotals()];
    }

    public function monthlySeries(int $months = 12): array
    {
        $start = Carbon::today()->startOfMonth()->subMonths($months - 1);
        $keys = [];
        $labels = [];
        $names = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $keys[] = $month->format('Y-m');
            $labels[] = $names[$month->month - 1].'/'.$month->format('y');
        }

        $empty = array_fill_keys($keys, 0.0);
        $revenue = $profit = $in = $out = $empty;

        Sale::completed()->where('sale_date', '>=', $start->toDateString())->get(['sale_date', 'total', 'profit'])
            ->each(function ($sale) use (&$revenue, &$profit) {
                $key = $sale->sale_date->format('Y-m');
                if (isset($revenue[$key])) {
                    $revenue[$key] += (float) $sale->total;
                    $profit[$key] += (float) $sale->profit;
                }
            });

        Service::billable()->where('date', '>=', $start->toDateString())->get(['date', 'amount', 'expenses_total'])
            ->each(function ($service) use (&$revenue, &$profit) {
                $key = $service->date->format('Y-m');
                if (isset($revenue[$key])) {
                    $revenue[$key] += (float) $service->amount;
                    $profit[$key] += $service->profit();
                }
            });

        FinancialTransaction::where('date', '>=', $start->toDateString())->get(['date', 'type', 'amount'])
            ->each(function ($t) use (&$in, &$out) {
                $key = $t->date->format('Y-m');
                if (! isset($in[$key])) {
                    return;
                }
                if ($t->type === TransactionType::Entrada) {
                    $in[$key] += (float) $t->amount;
                } else {
                    $out[$key] += (float) $t->amount;
                }
            });

        $round = fn ($arr) => array_map(fn ($v) => round($v, 2), array_values($arr));

        return [
            'labels' => $labels,
            'revenue' => $round($revenue),
            'profit' => $round($profit),
            'in' => $round($in),
            'out' => $round($out),
        ];
    }

    public function receivableBreakdown(): array
    {
        $today = Carbon::today()->toDateString();

        return [
            'Vencidas' => round((float) Installment::open()->where('due_date', '<', $today)->sum('amount'), 2),
            'Vencem hoje' => round((float) Installment::open()->whereDate('due_date', $today)->sum('amount'), 2),
            'Próximos 30 dias' => round((float) Installment::open()->where('due_date', '>', $today)->where('due_date', '<=', Carbon::today()->addDays(30)->toDateString())->sum('amount'), 2),
            'Depois de 30 dias' => round((float) Installment::open()->where('due_date', '>', Carbon::today()->addDays(30)->toDateString())->sum('amount'), 2),
        ];
    }

    public static function receivedStatusValue(): string
    {
        return InstallmentStatus::Pago->value;
    }
}
