<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Enums\ProductStatus;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category_id', 'brand', 'status', 'min_price', 'max_price', 'date_from', 'date_to', 'repor']);

        $view = $request->query('view') === 'grade' ? 'grade' : 'lista';

        $products = Product::with('category')->filter($filters)->latest('id')->paginate($view === 'grade' ? 24 : 20)->withQueryString();

        $statusCounts = Product::filter(collect($filters)->except('status')->all())
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $inStock = Product::inStock()->get(['purchase_price', 'expenses_total', 'sale_price', 'initial_quantity', 'quantity']);

        return view('products.index', [
            'products' => $products,
            'filters' => $filters,
            'view' => $view,
            'statusCounts' => $statusCounts,
            'lowStockCount' => Product::lowStock()->count(),
            'stats' => [
                'products' => Product::count(),
                'units' => (int) $inStock->sum('quantity'),
                'invested' => $inStock->sum(fn ($p) => $p->stockInvested()),
                'sale_value' => $inStock->sum(fn ($p) => $p->stockSaleValue()),
            ],
            'categories' => Category::ofType(CategoryType::Produto)->get(),
            'brands' => Product::whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(['status' => ProductStatus::Disponivel, 'quantity' => 1, 'purchase_date' => now()]),
            'categories' => Category::ofType(CategoryType::Produto)->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated(), $request->file('photos', []));

        return redirect()->route('products.show', $product)->with('success', 'Produto cadastrado.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'expenses', 'saleItems.sale.customer']);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::ofType(CategoryType::Produto)->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated(), $request->file('photos', []), $request->input('remove_photos', []));

        return redirect()->route('products.show', $product)->with('success', 'Produto atualizado.');
    }

    public function restock(Request $request, Product $product): RedirectResponse
    {
        $request->merge(['unit_cost' => to_decimal($request->input('unit_cost'))]);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'register_cash' => ['nullable', 'boolean'],
        ], [], ['quantity' => 'quantidade', 'unit_cost' => 'custo por unidade']);

        $this->products->restock($product, (int) $data['quantity'], (float) $data['unit_cost'], $request->boolean('register_cash'));

        return redirect()->route('products.show', $product)->with('success', "Entrada de {$data['quantity']} un. registrada.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleItems()->exists()) {
            return back()->with('error', 'Este produto já aparece em vendas. Altere o status para "Cancelado" em vez de excluir.');
        }

        $snapshot = $this->products->delete($product);
        app(\App\Services\UndoService::class)->flash($snapshot, 'Produto excluído.');

        return redirect()->route('products.index');
    }
}
