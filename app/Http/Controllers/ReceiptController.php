<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\WhatsAppMessage;
use App\Services\AuditService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    public function show(Sale $sale): Response
    {
        return $this->pdf($sale)->stream("recibo-{$sale->code}.pdf");
    }

    public function public(int $sale): Response
    {
        $sale = Sale::withoutGlobalScopes()->with('company')->findOrFail($sale);
        abort_unless($sale->company?->active, 404);

        return tenant()->run($sale->company, fn () => $this->pdf($sale->fresh())->stream("recibo-{$sale->code}.pdf"));
    }

    public function whatsapp(Sale $sale, WhatsAppService $whatsapp): RedirectResponse
    {
        $phone = $whatsapp->normalizePhone($sale->customer?->whatsappNumber());

        if (! $phone) {
            return back()->with('error', 'O cliente desta venda não tem WhatsApp ou telefone cadastrado.');
        }

        $message = 'Olá, '.str($sale->customer->name)->explode(' ')->first().'! Segue o recibo da sua compra '.$sale->code
            .' na '.tenant()->company()->name.', no valor de '.money($sale->total).': '.$this->publicUrl($sale);

        WhatsAppMessage::create([
            'customer_id' => $sale->customer_id,
            'user_id' => auth()->id(),
            'phone' => $phone,
            'type' => 'recibo',
            'message' => $message,
            'channel' => 'link',
        ]);

        AuditService::log('whatsapp', "Recibo da venda {$sale->code} enviado para {$sale->customer->name}", $sale);

        return redirect()->away($whatsapp->link($phone, $message));
    }

    public function publicUrl(Sale $sale): string
    {
        return URL::temporarySignedRoute('receipts.public', now()->addDays(60), ['sale' => $sale->id]);
    }

    private function pdf(Sale $sale)
    {
        $sale->load(['customer', 'user', 'items', 'installments', 'payments.installment']);

        return Pdf::loadView('receipts.sale', [
            'sale' => $sale,
            'company' => tenant()->company(),
        ])->setPaper('a4');
    }
}
