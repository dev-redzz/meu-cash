<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Http\Requests\SaleRequest;
use App\Http\Requests\SaleUpdateRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $sales)
    {
    }

    public function index(Request $request): View
    {
        $period = Period::fromRequest($request, 'mes');

        $query = Sale::with('customer')
            ->whereBetween('sale_date', [$period->startDate(), $period->endDate()])
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('payment_method'), fn ($q, $v) => $q->where('payment_method', $v))
            ->when($request->input('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('code', 'like', "%{$term}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                ->orWhereHas('items', fn ($i) => $i->where('description', 'like', "%{$term}%"))));

        $completed = (clone $query)->where('status', SaleStatus::Concluida->value);
        $totals = [
            'count' => (clone $completed)->count(),
            'revenue' => (float) (clone $completed)->sum('total'),
            'profit' => (float) (clone $completed)->sum('profit'),
        ];

        return view('sales.index', [
            'sales' => $query->latest('sale_date')->latest('id')->paginate(20)->withQueryString(),
            'period' => $period,
            'totals' => $totals,
        ]);
    }

    public function create(Request $request): View
    {
        $products = Product::sellable()->orderBy('name')->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->fullName(),
            'price' => (float) $p->sale_price,
            'stock' => $p->quantity,
            'cost' => $p->unitCost(),
        ]);

        return view('sales.create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'whatsapp', 'phone']),
            'products' => $products,
            'methods' => PaymentMethod::options(),
            'receivingMethods' => PaymentMethod::receiving(),
            'selectedCustomer' => $request->integer('customer_id') ?: null,
            'selectedProduct' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $sale = $this->sales->create($request->validated());

        return redirect()->route('sales.show', $sale)->with('success', "Venda {$sale->code} registrada.");
    }

    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'user', 'items.product', 'installments', 'payments.installment']);

        return view('sales.show', ['sale' => $sale, 'receivingMethods' => PaymentMethod::receiving()]);
    }

    public function edit(Sale $sale): View
    {
        return view('sales.edit', ['sale' => $sale, 'customers' => Customer::orderBy('name')->get(['id', 'name'])]);
    }

    public function update(SaleUpdateRequest $request, Sale $sale): RedirectResponse
    {
        $this->sales->update($sale, $request->validated());

        return redirect()->route('sales.show', $sale)->with('success', 'Venda atualizada.');
    }

    public function cancel(Sale $sale): RedirectResponse
    {
        $this->sales->cancel($sale);

        return redirect()->route('sales.show', $sale)->with('success', 'Venda cancelada. O estoque foi devolvido e as parcelas em aberto foram canceladas. Se houve devolução de dinheiro, registre uma saída no Financeiro.');
    }
}
