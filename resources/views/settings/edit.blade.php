@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<form method="POST" action="{{ route('settings.update') }}" class="row g-3">
    @csrf @method('PUT')
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Dados da empresa</h2></div>
            <div class="panel-body">
                <x-input name="company_name" label="Nome da empresa" :value="$company->name" required maxlength="120" help="Aparece no sistema, nos relatórios e nas mensagens." />
                <div class="row gx-3">
                    <div class="col-sm-6"><x-input name="company_document" label="CPF/CNPJ" :value="$company->document" maxlength="20" /></div>
                    <div class="col-sm-6"><x-input name="company_phone" label="Telefone / WhatsApp" :value="$company->phone" maxlength="20" /></div>
                </div>
                <x-input name="initial_balance" label="Saldo inicial do caixa" :value="number_format((float) $settings['initial_balance'], 2, ',', '.')" inputmode="decimal" help="Dinheiro que você já tinha antes de começar a usar o Meu Cash." required />
                <x-input name="monthly_goal" label="Meta de vendas da empresa por mês" :value="number_format((float) $settings['monthly_goal'], 2, ',', '.')" inputmode="decimal" help="Deixe 0 para não usar meta." required />
                <x-input name="due_reminder_days" type="number" min="0" max="30" label="Avisar vencimentos com quantos dias de antecedência" :value="$settings['due_reminder_days']" required />
            </div>
        </div>
        <div class="panel mt-3">
            <div class="panel-header"><h2 class="panel-title">Resumo semanal por e-mail</h2></div>
            <div class="panel-body">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="weekly_summary" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="weekly_summary" id="weekly_summary" value="1" @checked(old('weekly_summary', $settings['weekly_summary']) === '1')>
                    <label class="form-check-label fw-semibold" for="weekly_summary">Enviar toda segunda-feira</label>
                </div>
                <p class="small text-muted-2 mb-3">Os administradores recebem faturamento, lucro, parcelas atrasadas, mais vendidos e o andamento da meta da semana anterior.</p>
                <button type="submit" form="summary-preview-form" class="btn btn-light btn-sm">Enviar uma prévia para mim</button>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Mensagens do WhatsApp</h2></div>
            <div class="panel-body">
                <p class="small text-muted-2">Use as variáveis {cliente}, {valor}, {vencimento}, {parcela} e {empresa}.</p>
                <x-textarea name="whatsapp_template_lembrete" label="Lembrete de parcela próxima" :value="$settings['whatsapp_template_lembrete']" rows="2" required />
                <x-textarea name="whatsapp_template_vence_hoje" label="Parcela vencendo hoje" :value="$settings['whatsapp_template_vence_hoje']" rows="2" required />
                <x-textarea name="whatsapp_template_atrasada" label="Parcela atrasada" :value="$settings['whatsapp_template_atrasada']" rows="2" required />
                <x-textarea name="whatsapp_template_pagamento" label="Confirmação de pagamento" :value="$settings['whatsapp_template_pagamento']" rows="2" required />
            </div>
        </div>
    </div>
    <div class="col-12 text-end">
        <button class="btn btn-primary">Salvar configurações</button>
    </div>
</form>
<form method="POST" action="{{ route('settings.summary.preview') }}" id="summary-preview-form" data-no-lock>@csrf</form>
@endsection
