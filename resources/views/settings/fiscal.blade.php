@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')

<div class="fiscal-status panel mb-3">
    <div class="panel-body d-flex flex-wrap gap-3 align-items-start">
        <span class="stat-icon accent-{{ $missing ? 'amber' : 'green' }}"><x-icon name="{{ $missing ? 'alert' : 'check' }}" width="18" height="18" /></span>
        <div class="flex-grow-1 min-w-0">
            @if ($missing)
                <div class="fw-bold">Falta pouco para emitir NFC-e</div>
                <ul class="fiscal-missing">@foreach ($missing as $item)<li>{{ $item }}</li>@endforeach</ul>
            @else
                <div class="fw-bold">Pronto para emitir NFC-e
                    <span class="pill {{ $s['environment'] === 'producao' ? 'pill-success' : 'pill-warning' }} ms-1">{{ $s['environment'] === 'producao' ? 'Produção (vale de verdade)' : 'Homologação (teste, sem valor fiscal)' }}</span>
                </div>
                <p class="small text-muted-2 mb-0">Na página de cada venda aparece o botão <strong>Emitir NFC-e</strong>{{ $s['auto'] ? ', e as vendas novas já saem com nota automaticamente' : '' }}.</p>
            @endif
        </div>
    </div>
</div>

@unless ($platformReady)
    <div class="notice-bar is-warning mb-3"><x-icon name="alert" width="18" height="18" /><span>A emissão de nota fiscal ainda não foi ligada pela plataforma. Você já pode preencher os dados; eles serão usados assim que for ligada.</span></div>
@endunless

<div class="row g-3">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('settings.fiscal.update') }}" class="panel">
            @csrf @method('PUT')
            <div class="panel-header"><h2 class="panel-title">Empresa emitente</h2></div>
            <div class="panel-body">
                <div class="row gx-3">
                    <div class="col-md-6"><x-input name="legal_name" label="Razão social" :value="$s['legal_name']" maxlength="60" required /></div>
                    <div class="col-md-6"><x-input name="trade_name" label="Nome fantasia" :value="$s['trade_name']" maxlength="60" /></div>
                    <div class="col-md-4"><x-input name="cnpj" label="CNPJ" :value="$s['cnpj']" maxlength="18" inputmode="numeric" required /></div>
                    <div class="col-md-4"><x-input name="ie" label="Inscrição Estadual" :value="$s['ie']" maxlength="20" required /></div>
                    <div class="col-md-4"><x-select name="crt" label="Regime tributário" :options="\App\Services\Fiscal\InvoiceService::CRT" :value="$s['crt']" required /></div>
                    <div class="col-md-6"><x-input name="email" type="email" label="E-mail da empresa" :value="$s['email']" maxlength="120" /></div>
                    <div class="col-md-6"><x-input name="phone" label="Telefone" :value="$s['phone']" maxlength="20" /></div>
                </div>

                <h3 class="fiscal-sub">Endereço</h3>
                <div class="row gx-3">
                    <div class="col-md-3"><x-input name="cep" label="CEP" :value="$s['cep']" maxlength="9" inputmode="numeric" required /></div>
                    <div class="col-md-7"><x-input name="street" label="Rua" :value="$s['street']" maxlength="60" required /></div>
                    <div class="col-md-2"><x-input name="number" label="Número" :value="$s['number']" maxlength="60" placeholder="S/N" /></div>
                    <div class="col-md-4"><x-input name="complement" label="Complemento" :value="$s['complement']" maxlength="60" /></div>
                    <div class="col-md-4"><x-input name="district" label="Bairro" :value="$s['district']" maxlength="60" required /></div>
                    <div class="col-md-4"><x-input name="city" label="Cidade" :value="$s['city']" maxlength="60" required /></div>
                    <div class="col-md-4"><x-select name="uf" label="Estado" :options="array_combine(array_keys(\App\Services\Fiscal\InvoiceService::UF), array_keys(\App\Services\Fiscal\InvoiceService::UF))" :value="$s['uf']" placeholder="Escolha" required /></div>
                    <div class="col-md-4"><x-input name="city_code" label="Código do município (IBGE)" :value="$s['city_code']" maxlength="7" inputmode="numeric" help="Deixe vazio: buscamos pelo CEP." /></div>
                </div>

                <h3 class="fiscal-sub">NFC-e</h3>
                <div class="row gx-3">
                    <div class="col-md-4"><x-select name="environment" label="Ambiente" :options="['homologacao' => 'Homologação (teste)', 'producao' => 'Produção (vale de verdade)']" :value="$s['environment']" required /></div>
                    <div class="col-6 col-md-4"><x-input name="series" type="number" min="1" max="999" label="Série" :value="$s['series']" required /></div>
                    <div class="col-6 col-md-4"><x-input name="next_number" type="number" min="1" label="Próximo número" :value="$s['next_number']" help="Se já emitia NFC-e em outro sistema, continue a numeração." /></div>
                    <div class="col-md-4"><x-input name="csc_id" type="number" min="1" label="ID do CSC (token)" :value="$s['csc_id']" placeholder="Ex.: 1" /></div>
                    <div class="col-md-8"><x-input name="csc" type="password" label="CSC (código de segurança)" autocomplete="off" maxlength="36" :placeholder="$s['has_csc'] ? 'Guardado. Preencha só para trocar.' : 'Gerado no site da SEFAZ do seu estado'" /></div>
                    <div class="col-md-6"><x-input name="nature" label="Natureza da operação" :value="$s['nature']" maxlength="60" required /></div>
                    <div class="col-md-6"><x-input name="cfop" label="CFOP padrão" :value="$s['cfop']" maxlength="4" inputmode="numeric" required help="5102 para revenda de mercadoria." /></div>
                    <div class="col-12"><x-select name="csosn" label="Situação do ICMS padrão (CSOSN)" :options="\App\Services\Fiscal\InvoiceService::CSOSN" :value="$s['csosn']" required /></div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="auto" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="auto" id="auto" value="1" @checked(old('auto', $s['auto'] ? '1' : '0') === '1')>
                    <label class="form-check-label fw-semibold" for="auto">Emitir a NFC-e automaticamente ao concluir cada venda</label>
                    <div class="small text-muted-2">Desligado, a nota sai pelo botão <strong>Emitir NFC-e</strong> na página da venda.</div>
                </div>

                <button class="btn btn-primary">Salvar dados fiscais</button>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        <form method="POST" action="{{ route('settings.fiscal.certificate') }}" enctype="multipart/form-data" class="panel">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Certificado digital A1</h2></div>
            <div class="panel-body">
                @if ($s['certificate'])
                    @php $until = $s['certificate']['valid_until'] ? \Illuminate\Support\Carbon::parse($s['certificate']['valid_until']) : null; @endphp
                    <div class="system-status mb-3">
                        <strong class="{{ $until && $until->isPast() ? 'text-negative' : 'text-positive' }}">{{ $until && $until->isPast() ? 'Vencido' : 'Enviado' }}</strong>
                        <span>{{ $s['certificate']['subject'] ?? 'Certificado A1' }}</span>
                        @if ($until)<span class="small text-muted-2">Válido até {{ $until->format('d/m/Y') }}{{ $until->isFuture() && now()->diffInDays($until) < 30 ? ' · renove logo' : '' }}</span>@endif
                    </div>
                @endif
                <x-input name="certificate" type="file" label="Arquivo .pfx ou .p12" accept=".pfx,.p12" required />
                <x-input name="password" type="password" label="Senha do certificado" autocomplete="off" required />
                <p class="small text-muted-2">O certificado vai direto para a Nuvem Fiscal, que assina as notas. Ele não fica guardado no Meu Cash.</p>
                <button class="btn btn-primary" @disabled(! $s['cnpj'])>{{ $s['certificate'] ? 'Trocar certificado' : 'Enviar certificado' }}</button>
                @unless ($s['cnpj'])<div class="small text-muted-2 mt-2">Salve os dados da empresa primeiro.</div>@endunless
            </div>
        </form>

        <section class="panel mt-3">
            <div class="panel-header"><h2 class="panel-title">Onde consigo cada coisa</h2></div>
            <div class="panel-body">
                <ul class="howto">
                    <li><strong>Certificado A1:</strong> compre em uma certificadora (Serasa, Certisign, Valid...). Vem um arquivo .pfx e uma senha.</li>
                    <li><strong>CSC:</strong> gere no portal da SEFAZ do seu estado, na área de NFC-e. Homologação e produção têm CSC diferentes.</li>
                    <li><strong>Credenciamento:</strong> a empresa precisa estar habilitada para emitir NFC-e na SEFAZ.</li>
                    <li><strong>NCM, CFOP e CSOSN:</strong> confirme com o seu contador.</li>
                </ul>
            </div>
        </section>
    </div>

    @if ($recent->isNotEmpty())
        <div class="col-12">
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Últimas notas</h2></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Nota</th><th>Venda</th><th class="num">Valor</th><th>Data</th><th>Situação</th></tr></thead>
                        <tbody>
                            @foreach ($recent as $inv)
                                <tr>
                                    <td class="small">{{ $inv->numberLabel() }}@if ($inv->isTest()) <span class="text-muted-2">· teste</span>@endif</td>
                                    <td class="small">@if ($inv->sale)<a href="{{ route('sales.show', $inv->sale) }}">{{ $inv->sale->code }}</a> · {{ $inv->sale->customerName() }}@else — @endif</td>
                                    <td class="num">{{ money($inv->total) }}</td>
                                    <td class="small">{{ $inv->created_at->format('d/m H:i') }}</td>
                                    <td><span class="pill {{ $inv->pillClass() }}">{{ $inv->label() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</div>
@endsection
