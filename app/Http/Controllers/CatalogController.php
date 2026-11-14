<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CatalogService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Página pública do catálogo da loja. */
class CatalogController extends Controller
{
    public function show(string $slug, CatalogService $catalog): Response
    {
        $company = $this->company($slug);

        return tenant()->run($company, function () use ($company, $catalog, $slug) {
            abort_unless(Setting::get('catalog_enabled', '0') === '1', 404);

            $settings = $catalog->settings();
            $products = $catalog->products();

            return response(view('catalog.show', [
                'company' => $company,
                'settings' => $settings,
                'whatsapp' => $catalog->whatsappNumber($settings['whatsapp']),
                'categories' => $products->pluck('category.name')->filter()->unique()->sort()->values(),
                'products' => $products->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->fullName(),
                    'brand' => $p->brand,
                    'category' => $p->category?->name,
                    'price' => (float) $p->sale_price,
                    'stock' => $p->quantity,
                    'photos' => collect($p->photos ?? [])->keys()->map(fn ($i) => route('catalog.photo', [$slug, $p->id, $i]))->all(),
                ])->values(),
            ])->render());
        });
    }

    public function photo(string $slug, int $product, int $index): Response
    {
        $company = $this->company($slug);

        return tenant()->run($company, function () use ($product, $index) {
            abort_unless(Setting::get('catalog_enabled', '0') === '1', 404);
            $item = Product::sellable()->where('in_catalog', true)->findOrFail($product);
            $path = ($item->photos ?? [])[$index] ?? null;
            abort_unless($path && Storage::disk('public')->exists($path), 404);

            return response()->file(Storage::disk('public')->path($path), ['Cache-Control' => 'public, max-age=86400']);
        });
    }

    private function company(string $slug): Company
    {
        $company = Company::where('catalog_slug', strtolower($slug))->where('active', true)->first();
        abort_unless($company, 404);

        return $company;
    }
}
