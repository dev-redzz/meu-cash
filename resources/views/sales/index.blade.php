@extends('layouts.app')

@section('title', 'Vendas')

@section('actions')
    <a href="{{ route('sales.create') }}" class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="plus" width="16" height="16" /></span>Nova venda</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4"><x-stat label="Vendas concluídas" icon="cart" accent="blue" :value="$totals['count']" :hint="$period->label()" /></div>
    <div class="col-6 col-md-4"><x-stat label="Faturamento" icon="dollar" accent="green" :value="money($totals['revenue'])" /></div>
    <div class="col-12 col-md-4"><x-stat label="Lucro" icon="trend-up" accent="violet" :value="money($totals['profit'])" :tone="$totals['profit'] >= 0 ? 'positive' : 'negative'" /></div>
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
                    <th data-stack-title>Código</th>
                    <th>Cliente</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th class="num">Total</th>
                    <th class="num">Lucro</th>
                    <th></th>
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
                        <td class="text-end text-nowrap">
                            <a href="{{ route('sales.show', $sale) }}" class="icon-btn" title="Ver venda"><x-icon name="eye" width="16" height="16" /></a>
                            @can('admin')
                                <form method="POST" action="{{ route('sales.destroy', $sale) }}" class="d-inline" data-confirm="Excluir a venda {{ $sale->code }} de vez? O estoque volta, o dinheiro dela sai do caixa e ela some do sistema. Não dá para desfazer.">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-btn icon-btn-danger border-0 bg-transparent" title="Excluir"><x-icon name="trash" width="16" height="16" /></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty message="Nenhuma venda neste período." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection
