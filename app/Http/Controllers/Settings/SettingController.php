<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Mail\WeeklySummaryMail;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\WeeklySummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
        $data['weekly_summary'] = $request->boolean('weekly_summary') ? '1' : '0';

        tenant()->company()->update([
            'name' => $data['company_name'],
            'document' => $data['company_document'] ?? null,
            'phone' => $data['company_phone'] ?? null,
        ]);

        foreach (array_keys(Setting::DEFAULTS) as $key) {
            Setting::put($key, (string) (array_key_exists($key, $data) ? ($data[$key] ?? '') : Setting::get($key)));
        }

        AuditService::log('configuracoes', 'Configurações da empresa atualizadas');

        return back()->with('success', 'Configurações salvas.');
    }

    public function previewSummary(Request $request, WeeklySummaryService $service): RedirectResponse
    {
        try {
            Mail::to($request->user()->email, $request->user()->name)->send(new WeeklySummaryMail($service->build(), $request->user()));
        } catch (\Throwable $e) {
            report($e);

            $reason = str($e->getMessage())->squish()->limit(300);
            $mailer = config('mail.default');

            return back()->with('error', "Não foi possível enviar o e-mail (envio: {$mailer}). Motivo: {$reason}");
        }

        return back()->with('success', 'Prévia enviada para '.$request->user()->email.'.');
    }
}
