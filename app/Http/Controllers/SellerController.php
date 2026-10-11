<?php

namespace App\Http\Controllers;

use App\Services\SellerPerformanceService;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function __invoke(Request $request, SellerPerformanceService $performance): View
    {
        $period = Period::fromRequest($request, 'mes');
        $rows = $performance->forPeriod($period);

        return view('sellers.index', [
            'period' => $period,
            'rows' => $rows,
            'companyGoal' => $performance->companyGoal(),
            'totals' => [
                'sales' => $rows->sum('sales'),
                'revenue' => $rows->sum('revenue'),
                'commission' => $rows->sum('commission'),
            ],
        ]);
    }
}
