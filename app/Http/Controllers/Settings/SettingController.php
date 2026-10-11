<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $settings = [];
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $settings[$key] = Setting::get($key);
        }

        return view('settings.edit', ['settings' => $settings, 'company' => tenant()->company()]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        tenant()->company()->update([
            'name' => $data['company_name'],
            'document' => $data['company_document'] ?? null,
            'phone' => $data['company_phone'] ?? null,
        ]);

        foreach (array_keys(Setting::DEFAULTS) as $key) {
            Setting::put($key, (string) $data[$key]);
        }

        AuditService::log('configuracoes', 'Configurações da empresa atualizadas');

        return back()->with('success', 'Configurações salvas.');
    }
}
