<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Services\ExchangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExchangeController extends Controller
{
    public function create(Sale $sale): View|RedirectResponse
    {
        if ($sale->status !== SaleStatus::Concluida) {
            return redirect()->route('sales.show', $sale)->with('error', 'Só dá para trocar itens de venda concluída.');
        }

        $sale->load(['items.product', 'customer', 'installments']);

        return view('sales.exchange', [
            'sale' => $sale,
            'products' => Product::sellable()->orderBy('name')->get()->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->fullName(),
                'price' => (float) $p->sale_price,
                'stock' => $p->quantity,
                'barcode' => $p->barcode,
            ]),
            'saleItems' => $sale->items->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->description,
                'qty' => $i->quantity,
                'price' => (float) $i->unit_price,
                'back' => (int) old('returns.'.$i->id, 0),
            ])->values(),
            'methods' => PaymentMethod::receiving(),
            'hasOpen' => $sale->installments->contains(fn ($i) => $i->isOpen()),
            'hasInvoice' => Invoice::where('sale_id', $sale->id)->where('status', 'autorizado')->exists(),
        ]);
    }

    public function store(Request $request, Sale $sale, ExchangeService $exchanges): RedirectResponse
    {
        $request->merge([
            'added' => collect($request->input('added', []))
                ->filter(fn ($line) => ! empty($line['product_id']))
                ->map(fn ($line) => array_merge($line, ['unit_price' => to_decimal($line['unit_price'] ?? '0')]))
                ->values()->all(),
        ]);

        $data = $request->validate([
            'returns' => ['nullable', 'array'],
            'returns.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'added' => ['nullable', 'array', 'max:50'],
            'added.*.product_id' => ['required', 'integer'],
            'added.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'added.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'settlement' => ['required', Rule::in(['agora', 'parcelas'])],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethod::receiving()))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $exchange = $exchanges->exchange($sale, $data['returns'] ?? [], $data['added'] ?? [], $data['settlement'], $data['payment_method'] ?? null, $data['notes'] ?? null);

        return redirect()->route('sales.show', $sale)->with('success', 'Troca registrada. '.$exchange->settlementLabel().'.');
    }
}
