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
        $filters = $request->only(['q', 'category_id', 'brand', 'status', 'min_price', 'max_price', 'date_from', 'date_to']);

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

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleItems()->exists()) {
            return back()->with('error', 'Este produto já aparece em vendas. Altere o status para "Cancelado" em vez de excluir.');
        }

        $this->products->delete($product);

        return redirect()->route('products.index')->with('success', 'Produto excluído.');
    }
}
