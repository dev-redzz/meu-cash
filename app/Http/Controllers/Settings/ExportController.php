<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __construct(private readonly ExportService $export)
    {
    }

    public function index(): View
    {
        return view('settings.export', ['datasets' => ExportService::DATASETS]);
    }

    public function all(): Response
    {
        try {
            $path = $this->export->zip();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $name = 'meu-cash-'.Str::slug(tenant()->company()->name).'-'.now()->format('Y-m-d').'.zip';

        return response()->download($path, $name)->deleteFileAfterSend();
    }

    public function one(string $dataset): Response
    {
        abort_unless(array_key_exists($dataset, ExportService::DATASETS), 404);

        return response($this->export->csv($dataset), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$dataset.'-'.now()->format('Y-m-d').'.csv"',
        ]);
    }
}
