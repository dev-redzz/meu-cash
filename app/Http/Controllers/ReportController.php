<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ReportService;
use App\Support\Period;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $period = Period::fromRequest($request, 'mes');
        $type = $request->input('tipo', 'vendas');

        return view('reports.index', [
            'report' => $this->reports->build($type, $period),
            'types' => ReportService::TYPES,
            'period' => $period,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $period = Period::fromRequest($request, 'mes');
        $report = $this->reports->build($request->input('tipo', 'vendas'), $period);

        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'company' => Setting::get('company_name', 'Meu Cash'),
        ])->setPaper('a4', count($report['columns']) > 6 ? 'landscape' : 'portrait');

        return $pdf->download('relatorio-'.$report['type'].'-'.now()->format('Ymd-His').'.pdf');
    }
}
