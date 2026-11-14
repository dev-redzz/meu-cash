<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sale;
use App\Services\Fiscal\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function store(Sale $sale): RedirectResponse
    {
        try {
            $invoice = $this->invoices->emit($sale);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(...match ($invoice->status) {
            'autorizado' => ['success', "{$invoice->numberLabel()} autorizada."],
            'pendente' => ['success', 'Nota enviada para a SEFAZ. Em alguns segundos ela aparece como autorizada.'],
            default => ['error', "A nota foi {$invoice->label()}: {$invoice->message}"],
        });
    }

    public function refresh(Invoice $invoice): RedirectResponse
    {
        try {
            $this->invoices->refresh($invoice);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:15', 'max:255']], [
            'reason.min' => 'A SEFAZ pede um motivo com pelo menos 15 letras.',
        ]);

        try {
            $this->invoices->cancel($invoice, $data['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$invoice->numberLabel()} cancelada na SEFAZ.");
    }

    public function download(Invoice $invoice, string $kind): Response
    {
        abort_unless(in_array($kind, ['pdf', 'xml'], true) && $invoice->provider_id && in_array($invoice->status, ['autorizado', 'cancelado'], true), 404);

        try {
            $content = $this->invoices->download($invoice, $kind);
        } catch (\Throwable $e) {
            return back()->with('error', 'Não foi possível baixar agora: '.$e->getMessage());
        }

        $name = 'nfce-'.$invoice->series.'-'.$invoice->number.'.'.$kind;

        return response($content, 200, [
            'Content-Type' => $kind === 'pdf' ? 'application/pdf' : 'application/xml',
            'Content-Disposition' => ($kind === 'pdf' ? 'inline' : 'attachment').'; filename="'.$name.'"',
        ]);
    }
}
