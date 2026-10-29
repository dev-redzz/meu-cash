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
        $today = Carbon::today()->toDateString();

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
            'recentSales' => Sale::with('customer')->withSum('items as units', 'quantity')->latest('sale_date')->latest('id')->limit(5)->get(),
            'overdue' => Installment::with(['customer', 'sale'])->open()->where('due_date', '<', $today)->orderBy('due_date')->limit(5)->get(),
            'upcoming' => Installment::with(['customer', 'sale'])->open()->where('due_date', '>=', $today)->orderBy('due_date')->limit(5)->get(),
            'transactions' => FinancialTransaction::latest('date')->latest('id')->limit(5)->get(),
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
}
