<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Catálogo online da loja: página pública com os produtos em estoque e pedido pelo WhatsApp. */
class CatalogService
{
    public function settings(): array
    {
        return [
            'enabled' => Setting::get('catalog_enabled', '0') === '1',
            'whatsapp' => Setting::get('catalog_whatsapp') ?: tenant()->company()?->phone,
            'intro' => Setting::get('catalog_intro'),
            'show_stock' => Setting::get('catalog_show_stock', '0') === '1',
            'slug' => tenant()->company()?->catalog_slug,
        ];
    }

    public function suggestSlug(Company $company): string
    {
        $base = Str::slug($company->name) ?: 'loja';
        $slug = $base;
        $n = 2;
        while (Company::where('catalog_slug', $slug)->where('id', '!=', $company->id)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    /** Produtos que aparecem no catálogo. */
    public function products(): Collection
    {
        return Product::sellable()
            ->where('in_catalog', true)
            ->where('sale_price', '>', 0)
            ->with('category:id,name')
            ->orderBy('name')
            ->get();
    }

    public function whatsappNumber(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (strlen($digits) < 10) {
            return null;
        }

        return strlen($digits) <= 11 ? '55'.$digits : $digits;
    }
}
