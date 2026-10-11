<?php

namespace App\Http\Controllers;

use App\Services\ProductImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ProductImportController extends Controller
{
    public function __construct(private readonly ProductImportService $import)
    {
    }

    public function create(): View
    {
        return view('products.import', ['columns' => ProductImportService::COLUMNS]);
    }

    public function template(): Response
    {
        return response($this->import->template(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modelo-produtos-meu-cash.csv"',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
        ], ['file.mimes' => 'Envie a planilha salva como CSV (no Excel: Salvar como > CSV).'], ['file' => 'planilha']);

        try {
            $result = $this->import->import($request->file('file')->get(), $request->boolean('register_purchase'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result['errors']) {
            return back()->with('import_errors', $result['errors'])->with('error', 'Nenhum produto foi importado. Corrija as linhas abaixo e envie de novo.');
        }

        return redirect()->route('products.index')->with('success', "{$result['created']} produtos importados.");
    }
}
