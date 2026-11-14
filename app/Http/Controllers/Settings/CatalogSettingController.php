<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogSettingController extends Controller
{
    public function edit(CatalogService $catalog): View
    {
        $company = tenant()->company();
        $settings = $catalog->settings();

        return view('settings.catalog', [
            's' => $settings,
            'slug' => $settings['slug'] ?: $catalog->suggestSlug($company),
            'count' => $catalog->products()->count(),
            'hidden' => \App\Models\Product::sellable()->where('in_catalog', false)->count(),
            'withoutPhoto' => $catalog->products()->filter(fn ($p) => empty($p->photos))->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = tenant()->company();
        $request->merge(['slug' => Str::slug((string) $request->input('slug'))]);

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'slug' => ['required', 'string', 'min:3', 'max:60', Rule::unique('companies', 'catalog_slug')->ignore($company->id), Rule::notIn(['admin', 'plataforma', 'login', 'api'])],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'intro' => ['nullable', 'string', 'max:300'],
            'show_stock' => ['nullable', 'boolean'],
        ], [
            'slug.unique' => 'Esse endereço já é de outra loja. Escolha outro.',
        ]);

        $company->update(['catalog_slug' => $data['slug']]);
        Setting::put('catalog_enabled', ! empty($data['enabled']) ? '1' : '0');
        Setting::put('catalog_whatsapp', $data['whatsapp'] ?? '');
        Setting::put('catalog_intro', $data['intro'] ?? '');
        Setting::put('catalog_show_stock', ! empty($data['show_stock']) ? '1' : '0');

        AuditService::log('configuracoes', 'Catálogo online '.(! empty($data['enabled']) ? 'ligado' : 'desligado'));

        return back()->with('success', ! empty($data['enabled']) ? 'Catálogo publicado. Copie o link e divulgue.' : 'Catálogo salvo (desligado).');
    }
}
