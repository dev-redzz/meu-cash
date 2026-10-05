@extends('layouts.app')

@section('title', 'Financeiro')

@section('actions')
    <x-period :period="$period" :keep="['tipo', 'origem', 'q']" />
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">Novo lançamento</button>
@endsection

@section('content')
@php $s = $summary; $stock = $s['stock']; @endphp
<div class="row g-3 mb-3">
    <div class="col-12 col-xl-4"><x-stat class="stat-balance" label="Saldo atual" :value="money($s['balance'])" hint="Saldo inicial + todas as entradas − todas as saídas" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat label="Entradas" :value="money($s['received'])" tone="positive" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat label="Saídas" :value="money($s['spent'])" tone="negative" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat label="Saldo do período" :value="money($s['net'])" :tone="$s['net'] >= 0 ? 'positive' : 'negative'" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat label="Lucro" :value="money($s['profit'])" :hint="'Vendas '.money($s['sales_profit']).' · Serviços '.money($s['service_profit'])" /></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Resumo financeiro</h2></div>
            <div class="list-row"><span>Faturamento no período</span><span class="num fw-semibold">{{ money($s['revenue']) }}</span></div>
            <div class="list-row"><span>Recebido no período</span><span class="num text-positive">{{ money($s['received']) }}</span></div>
            <div class="list-row"><span>Total a receber</span><span class="num">{{ money($s['receivable']) }}</span></div>
            <div class="list-row"><span>Investido em produtos (estoque)</span><span class="num">{{ money($stock['invested']) }}</span></div>
            <div class="list-row"><span>Valor estimado de venda</span><span class="num">{{ money($stock['sale_value']) }}</span></div>
            <div class="list-row"><span>Lucro potencial do estoque</span><span class="num text-positive">{{ money($stock['potential_profit']) }}</span></div>
            @php $position = $s['balance'] + $s['receivable']; @endphp
            <div class="list-row fw-bold"><span>Situação geral (saldo + a receber)</span><span class="num {{ $position >= 0 ? 'text-positive' : 'text-negative' }}">{{ money($position) }}</span></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Por origem no período</h2></div>
            <div class="row g-0">
                @foreach (['entrada' => 'Entradas', 'saida' => 'Saídas'] as $type => $label)
                    <div class="col-md-6 {{ $type === 'entrada' ? 'border-end' : '' }}">
                        <div class="px-3 pt-3 pb-1 small fw-semibold text-muted-2">{{ $label }}</div>
                        @forelse ($byOrigin[$type] ?? [] as $row)
                            <div class="list-row"><span>{{ $origins[$row->origin] ?? $row->origin }}</span><span class="num {{ $type === 'entrada' ? 'text-positive' : 'text-negative' }}">{{ money($row->total) }}</span></div>
                        @empty
                            <div class="px-3 pb-3 small text-muted-2">Nada no período.</div>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header flex-wrap">
        <h2 class="panel-title">Fluxo de caixa</h2>
        <form method="GET" class="d-flex flex-wrap gap-2" data-no-lock>
            @foreach ($period->query() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-auto" placeholder="Descrição">
            <select name="tipo" class="form-select form-select-sm w-auto" aria-label="Tipo">
                <option value="">Entradas e saídas</option>
                <option value="entrada" @selected(request('tipo') === 'entrada')>Só entradas</option>
                <option value="saida" @selected(request('tipo') === 'saida')>Só saídas</option>
            </select>
            <select name="origem" class="form-select form-select-sm w-auto" aria-label="Origem">
                <option value="">Todas as origens</option>
                @foreach ($origins as $key => $label)
                    <option value="{{ $key }}" @selected(request('origem') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Data</th><th>Descrição</th><th>Origem</th><th>Categoria</th><th>Forma</th><th class="num">Valor</th><th></th></tr></thead>
            <tbody>
                @forelse ($transactions as $t)
                    <tr>
                        <td>{{ $t->date->format('d/m/Y') }}</td>
                        <td>{{ $t->description }}</td>
                        <td>{{ $t->originLabel() }}</td>
                        <td>{{ $t->categoryLabel() }}</td>
                        <td>{{ $t->payment_method?->label() ?? '—' }}</td>
                        <td class="num fw-semibold {{ $t->type->value === 'entrada' ? 'text-positive' : 'text-negative' }}">{{ $t->type->value === 'entrada' ? '+' : '−' }} {{ money($t->amount) }}</td>
                        <td class="text-end">
                            @if ($t->isManual())
                                <form method="POST" action="{{ route('finance.destroy', $t) }}" data-confirm="Excluir este lançamento?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger p-0">Excluir</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty message="Nenhuma movimentação no período." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->links() }}</div>

<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finance.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="transactionTitle">Novo lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted-2">Use para movimentações fora de vendas, serviços e contas: aporte, retirada, despesa avulsa, estorno.</p>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label" for="t_type">Tipo</label>
                        <select name="type" id="t_type" class="form-select" required>
                            <option value="entrada">Entrada</option>
                            <option value="saida" selected>Saída</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="t_category">Categoria</label>
                        <select name="category" id="t_category" class="form-select" required></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="t_desc">Descrição</label>
                        <input type="text" name="description" id="t_desc" class="form-control" required maxlength="255">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="t_amount">Valor</label>
                        <input type="text" name="amount" id="t_amount" class="form-control" inputmode="decimal" placeholder="0,00" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="t_date">Data</label>
                        <input type="date" name="date" id="t_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="t_method">Forma</label>
                        <select name="payment_method" id="t_method" class="form-select">
                            <option value="">—</option>
                            @foreach ($receivingMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Voltar</button>
                <button type="submit" class="btn btn-primary">Registrar lançamento</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const categories = @json($categories);
    const type = document.getElementById('t_type');
    const category = document.getElementById('t_category');
    function fill() {
        category.innerHTML = '';
        Object.entries(categories[type.value] || {}).forEach(([value, label]) => category.add(new Option(label, value)));
    }
    type.addEventListener('change', fill);
    fill();
})();
</script>
@endpush
