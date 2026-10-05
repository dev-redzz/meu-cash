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

        return view('settings.edit', compact('settings'));
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::put($key, (string) $value);
        }

        AuditService::log('configuracoes', 'Configurações gerais atualizadas');

        return back()->with('success', 'Configurações salvas.');
    }
}
