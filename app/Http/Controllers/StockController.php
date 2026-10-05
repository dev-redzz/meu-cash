<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->only(['q', 'category_id', 'brand', 'status', 'min_price', 'max_price', 'date_from', 'date_to']);

        $products = Product::with('category')->filter($filters)
            ->when(empty($filters['status']), fn ($q) => $q->where('status', '!=', ProductStatus::Cancelado->value))
            ->orderByRaw("CASE status WHEN 'disponivel' THEN 1 WHEN 'reservado' THEN 2 WHEN 'manutencao' THEN 3 WHEN 'vendido' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->get();

        $byStatus = [];
        foreach (ProductStatus::cases() as $status) {
            $group = $products->filter(fn ($p) => $p->status === $status);
            $byStatus[$status->value] = [
                'status' => $status,
                'items' => $group->count(),
                'units' => $status === ProductStatus::Vendido ? $group->sum(fn ($p) => $p->initial_quantity - $p->quantity) : $group->sum('quantity'),
            ];
        }

        $inStock = $products->filter(fn ($p) => in_array($p->status->value, ProductStatus::inStock(), true) && $p->quantity > 0);
        $totals = [
            'units' => $inStock->sum('quantity'),
            'invested' => $inStock->sum(fn ($p) => $p->stockInvested()),
            'sale_value' => $inStock->sum(fn ($p) => $p->stockSaleValue()),
        ];
        $totals['potential'] = $totals['sale_value'] - $totals['invested'];

        return view('stock.index', [
            'products' => $products,
            'byStatus' => $byStatus,
            'totals' => $totals,
            'filters' => $filters,
            'categories' => Category::ofType(CategoryType::Produto)->get(),
            'brands' => Product::whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
        ]);
    }
}
