@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Exportar tudo</h2></div>
            <div class="panel-body">
                <p class="text-muted-2">Baixa um arquivo .zip com todas as planilhas da sua empresa: clientes, produtos, vendas, itens, parcelas, pagamentos e fluxo de caixa.</p>
                <p class="text-muted-2 small">As planilhas abrem direto no Excel e no Google Planilhas. Guarde uma cópia com frequência.</p>
                <a href="{{ route('export.all') }}" class="btn btn-primary">Baixar todos os dados (.zip)</a>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Planilhas separadas</h2></div>
            @foreach ($datasets as $key => $label)
                <div class="list-row">
                    <span class="fw-semibold">{{ $label }}</span>
                    <a href="{{ route('export.one', $key) }}" class="btn btn-sm btn-light">Baixar .csv</a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
