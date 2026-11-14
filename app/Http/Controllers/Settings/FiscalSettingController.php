<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\AuditService;
use App\Services\Fiscal\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FiscalSettingController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function edit(): View
    {
        $settings = $this->invoices->settings();

        return view('settings.fiscal', [
            's' => $settings,
            'missing' => $this->invoices->missing($settings),
            'platformReady' => $this->invoices->platformReady(),
            'recent' => Invoice::with('sale.customer')->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cnpj' => ['required', 'string', function ($attr, $value, $fail) {
                if (! self::validCnpj($value)) {
                    $fail('CNPJ inválido.');
                }
            }],
            'ie' => ['required', 'string', 'max:20'],
            'legal_name' => ['required', 'string', 'max:60'],
            'trade_name' => ['nullable', 'string', 'max:60'],
            'crt' => ['required', Rule::in(array_keys(InvoiceService::CRT))],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'cep' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'street' => ['required', 'string', 'max:60'],
            'number' => ['nullable', 'string', 'max:60'],
            'complement' => ['nullable', 'string', 'max:60'],
            'district' => ['required', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:60'],
            'uf' => ['required', Rule::in(array_keys(InvoiceService::UF))],
            'city_code' => ['nullable', 'regex:/^\d{7}$/'],
            'environment' => ['required', Rule::in(['homologacao', 'producao'])],
            'series' => ['required', 'integer', 'min:1', 'max:999'],
            'next_number' => ['nullable', 'integer', 'min:1', 'max:999999999'],
            'csc_id' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'csc' => ['nullable', 'string', 'max:36'],
            'nature' => ['required', 'string', 'max:60'],
            'csosn' => ['required', Rule::in(array_keys(InvoiceService::CSOSN))],
            'cfop' => ['required', 'regex:/^[5]\d{3}$/'],
            'auto' => ['nullable', 'boolean'],
        ], [
            'cfop.regex' => 'Use um CFOP de venda dentro do estado (começa com 5, ex.: 5102).',
            'city_code.regex' => 'O código do município (IBGE) tem 7 números.',
        ]);

        try {
            $this->invoices->saveSettings($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Os dados foram salvos, mas a Nuvem Fiscal recusou o cadastro: '.$e->getMessage());
        }

        AuditService::log('configuracoes', 'Configurações de nota fiscal alteradas');

        return back()->with('success', 'Dados fiscais salvos.');
    }

    public function certificate(Request $request): RedirectResponse
    {
        $request->validate([
            'certificate' => ['required', 'file', 'max:100', function ($attr, $file, $fail) {
                if (! in_array(strtolower($file->getClientOriginalExtension()), ['pfx', 'p12'], true)) {
                    $fail('Envie o arquivo .pfx ou .p12 do certificado A1.');
                }
            }],
            'password' => ['required', 'string', 'max:100'],
        ]);

        try {
            $info = $this->invoices->uploadCertificate(file_get_contents($request->file('certificate')->getRealPath()), $request->input('password'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Certificado não aceito: '.$e->getMessage());
        }

        return back()->with('success', 'Certificado enviado'.(! empty($info['valid_until']) ? ', válido até '.\Illuminate\Support\Carbon::parse($info['valid_until'])->format('d/m/Y') : '').'.');
    }

    public static function validCnpj(string $value): bool
    {
        $c = preg_replace('/\D/', '', $value);
        if (strlen($c) !== 14 || preg_match('/^(\d)\1{13}$/', $c)) {
            return false;
        }

        foreach ([12, 13] as $len) {
            $sum = 0;
            $weight = $len - 7;
            for ($i = 0; $i < $len; $i++) {
                $sum += (int) $c[$i] * $weight--;
                if ($weight < 2) {
                    $weight = 9;
                }
            }
            $digit = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
            if ((int) $c[$len] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
