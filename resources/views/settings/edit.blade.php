@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<form method="POST" action="{{ route('settings.update') }}" class="row g-3">
    @csrf @method('PUT')
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Empresa e financeiro</h2></div>
            <div class="panel-body">
                <x-input name="company_name" label="Nome que aparece no sistema e nas mensagens" :value="$settings['company_name']" required maxlength="100" />
                <x-input name="company_phone" label="Telefone / WhatsApp da empresa" :value="$settings['company_phone']" maxlength="20" />
                <x-input name="initial_balance" label="Saldo inicial do caixa" :value="number_format((float) $settings['initial_balance'], 2, ',', '.')" inputmode="decimal" help="Dinheiro que você já tinha antes de começar a usar o Meu Cash." required />
                <x-input name="due_reminder_days" type="number" min="0" max="30" label="Avisar vencimentos com quantos dias de antecedência" :value="$settings['due_reminder_days']" required />
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
@endsection
