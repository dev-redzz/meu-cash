<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->input('q'));
        $results = ['customers' => collect(), 'products' => collect(), 'sales' => collect()];

        if (mb_strlen($term) >= 2) {
            $results['customers'] = Customer::search($term)->orderBy('name')->limit(8)->get();
            $results['products'] = Product::filter(['q' => $term])->with('category')->orderBy('name')->limit(8)->get();
            $results['sales'] = Sale::with('customer')
                ->where(fn ($q) => $q->where('code', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")))
                ->latest('sale_date')->limit(8)->get();
        }

        return view('search', [
            'term' => $term,
            'results' => $results,
            'total' => collect($results)->sum->count(),
        ]);
    }
}
