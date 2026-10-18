<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Product;
use App\Models\ProductExpense;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;

class ProductExpenseController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function store(ExpenseRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except('register_cash');
        $this->products->addExpense($product, $data, $request->boolean('register_cash'));

        return redirect()->route('products.show', $product)->with('success', 'Despesa registrada. O custo do produto foi atualizado.');
    }

    public function destroy(Product $product, ProductExpense $expense): RedirectResponse
    {
        abort_unless($expense->product_id === $product->id, 404);
        $snapshot = $this->products->removeExpense($expense);
        app(\App\Services\UndoService::class)->flash($snapshot, 'Despesa removida.');

        return redirect()->route('products.show', $product);
    }
}
