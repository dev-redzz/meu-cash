@extends('layouts.app')

@section('title', 'Relatórios')

@section('actions')
    <a href="{{ route('reports.pdf', ['tipo' => $report['type']] + $period->query()) }}" class="btn btn-primary">Baixar PDF</a>
    <button type="button" class="btn btn-light" onclick="window.print()">Imprimir</button>
@endsection

@section('content')
<div class="panel mb-3 no-print">
    <div class="panel-body d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center" data-no-lock>
            @foreach ($period->query() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <select name="tipo" class="form-select form-select-sm w-auto" onchange="this.form.submit()" aria-label="Relatório">
                @foreach ($types as $key => $label)
                    <option value="{{ $key }}" @selected($report['type'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <x-period :period="$period" :keep="['tipo']" />
    </div>
</div>

<div class="panel">
    <div class="panel-header flex-wrap">
        <div>
            <h2 class="panel-title">{{ $report['title'] }}</h2>
            <div class="small text-muted-2">{{ $report['type'] === 'estoque' ? 'Posição atual do estoque' : $report['period'] }}</div>
        </div>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($report['totals'] as $label => $value)
                <div class="text-end"><div class="small text-muted-2">{{ $label }}</div><div class="fw-bold num">{{ $value }}</div></div>
            @endforeach
        </div>
    </div>
    <div class="table-responsive">
        @include('reports._table')
    </div>
</div>
@endsection
