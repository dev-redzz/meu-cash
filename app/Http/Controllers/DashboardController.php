<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\FinanceService;
use App\Services\SellerPerformanceService;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FinanceService $finance, SellerPerformanceService $performance): View
    {
        $period = Period::fromRequest($request);
        $previous = $period->previous();

        $summary = $finance->summary($period);
        $before = $finance->summary($previous);

        $sold = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'concluida')
            ->whereBetween('sales.sale_date', [$period->startDate(), $period->endDate()])
            ->select('sale_items.product_id', 'sale_items.description', DB::raw('SUM(sale_items.quantity) as qty'), DB::raw('SUM(sale_items.total) as revenue'))
            ->groupBy('sale_items.product_id', 'sale_items.description')
            ->orderByDesc('qty')
            ->limit(6)
            ->get();

        $products = Product::with('category')->whereIn('id', $sold->pluck('product_id')->filter())->get()->keyBy('id');

        $completed = Sale::completed()->whereBetween('sale_date', [$period->startDate(), $period->endDate()]);
        $methods = (clone $completed)
            ->selectRaw('payment_method, COUNT(*) as n, SUM(total) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => (object) [
                'label' => $row->payment_method?->label() ?? 'Outro',
                'count' => (int) $row->n,
                'total' => (float) $row->total,
            ]);

        $today = Carbon::today();
        $todaySales = Sale::completed()->whereDate('sale_date', $today);

        return view('dashboard', [
            'period' => $period,
            'summary' => $summary,
            'changes' => [
                'revenue' => $finance->compare($summary['revenue'], $before['revenue']),
                'received' => $finance->compare($summary['received'], $before['received']),
                'spent' => $finance->compare($summary['spent'], $before['spent']),
                'profit' => $finance->compare($summary['profit'], $before['profit']),
            ],
            'topExpenses' => $finance->topExpenses($period),
            'lowStock' => \App\Models\Product::lowStock()->orderBy('quantity')->limit(4)->get(['id', 'name', 'model', 'quantity', 'min_stock']),
            'lowStockCount' => \App\Models\Product::lowStock()->count(),
            'recentSales' => Sale::with('customer')->withSum('items as units', 'quantity')->latest('sale_date')->latest('id')->limit(6)->get(),
            'overdue' => Installment::with(['customer', 'sale'])->open()->where('due_date', '<', $today->toDateString())->orderBy('due_date')->limit(6)->get(),
            'overdueCount' => Installment::open()->where('due_date', '<', $today->toDateString())->count(),
            'overdueTotal' => (float) Installment::open()->where('due_date', '<', $today->toDateString())->sum('amount'),
            'upcoming' => Installment::with(['customer', 'sale'])->open()->where('due_date', '>=', $today->toDateString())->orderBy('due_date')->limit(6)->get(),
            'methods' => $methods,
            'series' => $this->series($period),
            'today' => ['total' => (float) (clone $todaySales)->sum('total'), 'count' => (clone $todaySales)->count()],
            'transactions' => FinancialTransaction::latest('date')->latest('id')->limit(6)->get(),
            'sold' => $sold->map(fn ($row) => (object) [
                'product' => $products->get($row->product_id),
                'description' => $row->description,
                'qty' => (int) $row->qty,
                'revenue' => (float) $row->revenue,
            ]),
            'entrada' => TransactionType::Entrada,
            'companyGoal' => $request->user()->isAdmin() ? $performance->companyGoal() : null,
            'myGoal' => $performance->userGoal($request->user()),
        ]);
    }

    /**
     * Faturamento e lucro do período para o gráfico: por dia até 62 dias, por mês acima disso.
     * Dias sem venda entram com zero para o gráfico não pular datas.
     */
    private function series(Period $period): array
    {
        $rows = Sale::completed()
            ->whereBetween('sale_date', [$period->startDate(), $period->endDate()])
            ->selectRaw('sale_date as day, SUM(total) as revenue, SUM(profit) as profit, COUNT(*) as n')
            ->groupBy('sale_date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        $start = $period->start->copy()->startOfDay();
        $end = $period->end->copy()->startOfDay()->min(Carbon::today()->max($start));
        $monthly = $start->diffInDays($end) > 62;
        $buckets = [];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $key = $monthly ? $cursor->format('Y-m') : $cursor->toDateString();
            $buckets[$key] ??= [
                'label' => $monthly ? ucfirst($cursor->translatedFormat('M')) : $cursor->format('d/m'),
                'title' => $monthly ? ucfirst($cursor->translatedFormat('F \\d\\e Y')) : ucfirst($cursor->translatedFormat('D, d/m')),
                'revenue' => 0.0, 'profit' => 0.0, 'count' => 0,
            ];
            if ($row = $rows->get($cursor->toDateString())) {
                $buckets[$key]['revenue'] += (float) $row->revenue;
                $buckets[$key]['profit'] += (float) $row->profit;
                $buckets[$key]['count'] += (int) $row->n;
            }
        }

        return array_values(array_map(fn ($b) => array_merge($b, ['revenue' => round($b['revenue'], 2), 'profit' => round($b['profit'], 2)]), $buckets));
    }
}
