@extends('layouts.app')

@section('title', 'Vendas')

@section('actions')
    <a href="{{ route('sales.create') }}" class="btn btn-primary">Nova venda</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat label="Vendas concluídas" :value="$totals['count']" :hint="$period->label()" /></div>
    <div class="col-md-4"><x-stat label="Faturamento" :value="money($totals['revenue'])" /></div>
    <div class="col-md-4"><x-stat label="Lucro" :value="money($totals['profit'])" :tone="$totals['profit'] >= 0 ? 'positive' : 'negative'" /></div>
</div>

<div class="panel">
    <div class="panel-header flex-wrap">
        <x-period :period="$period" :keep="['q', 'status', 'payment_method']" />
        <form method="GET" class="d-flex flex-wrap gap-2" data-no-lock>
            @foreach ($period->query() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-auto" placeholder="Código, cliente ou item">
            <select name="status" class="form-select form-select-sm w-auto" aria-label="Status">
                <option value="">Todos os status</option>
                @foreach (\App\Enums\SaleStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="payment_method" class="form-select form-select-sm w-auto" aria-label="Forma de pagamento">
                <option value="">Todas as formas</option>
                @foreach (\App\Enums\PaymentMethod::options() as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th class="num">Total</th>
                    <th class="num">Lucro</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                        <td><a href="{{ route('sales.show', $sale) }}" class="fw-semibold">{{ $sale->code }}</a></td>
                        <td>{{ $sale->customerName() }}</td>
                        <td>{{ $sale->payment_method->label() }}@if ($sale->installments_count) <span class="small text-muted-2">· {{ $sale->installments_count }}x</span>@endif</td>
                        <td><x-status :value="$sale->status" /></td>
                        <td class="num">{{ money($sale->total) }}</td>
                        <td class="num {{ $sale->profit >= 0 ? 'text-positive' : 'text-negative' }}">{{ money($sale->profit) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty message="Nenhuma venda neste período." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection
