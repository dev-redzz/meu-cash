<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\Installment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\FinanceService;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FinanceService $finance): View
    {
        $period = Period::fromRequest($request);
        $today = Carbon::today()->toDateString();

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'concluida')
            ->whereBetween('sales.sale_date', [$period->startDate(), $period->endDate()])
            ->select('sale_items.description', DB::raw('SUM(sale_items.quantity) as qty'))
            ->groupBy('sale_items.description')
            ->orderByDesc('qty')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'period' => $period,
            'summary' => $finance->summary($period),
            'topProducts' => $topProducts,
            'recentSales' => Sale::with('customer')->latest('sale_date')->latest('id')->limit(6)->get(),
            'upcoming' => Installment::with(['customer', 'sale', 'service'])->open()->where('due_date', '>=', $today)->orderBy('due_date')->limit(6)->get(),
            'overdue' => Installment::with(['customer', 'sale', 'service'])->open()->where('due_date', '<', $today)->orderBy('due_date')->limit(6)->get(),
            'transactions' => FinancialTransaction::latest('date')->latest('id')->limit(8)->get(),
        ]);
    }
}
