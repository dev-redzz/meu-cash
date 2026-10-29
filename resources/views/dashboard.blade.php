@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Olá, '.auth()->user()->firstName())
@section('subtitle', ucfirst(now()->locale('pt_BR')->translatedFormat('l, j \\d\\e F')).' · '.$period->label())

@section('actions')
    <x-period :period="$period" />
@endsection

@section('content')
@php $s = $summary; @endphp
<div class="dash">

<div class="row g-3 mb-3">
    <div class="col-12 col-xl-4">
        <div class="stat-balance h-100">
            <img src="{{ asset('img/logo-icon.png') }}" alt="" class="stat-balance-mark">
            <div class="stat-balance-label">Saldo atual</div>
            <div class="stat-balance-value">{{ money($s['balance']) }}</div>
            <div class="stat-balance-hint">Dinheiro disponível em caixa</div>
        </div>
    </div>
    <div class="col-12 col-xl-8">
        <div class="row g-3 h-100">
            <div class="col-6 col-md-4">
                <x-stat label="Faturamento" icon="dollar" accent="blue" :value="money($s['revenue'])" :hint="$s['sales_count'].' '.($s['sales_count'] === 1 ? 'venda' : 'vendas').' no período'" />
            </div>
            <div class="col-6 col-md-4">
                <x-stat label="Recebido" icon="arrow-in" accent="green" :value="money($s['received'])" hint="Entradas no período" />
            </div>
            <div class="col-12 col-md-4">
                <x-stat label="A receber" icon="clock" accent="amber" :value="money($s['receivable'])" hint="Parcelas em aberto" />
            </div>
            <div class="col-sm-6">
                <x-stat label="Gastos" icon="arrow-out" accent="rose" :value="money($s['spent'])" :change="$changes['spent']" :inverse="true"
                    :hint="$topExpenses ? 'Maiores: '.collect($topExpenses)->map(fn ($e) => $e['label'].' ('.money($e['total']).')')->implode(', ') : 'Saídas no período'" />
            </div>
            <div class="col-sm-6">
                <x-stat label="Lucro" icon="trend-up" accent="violet" :value="money($s['profit'])" :tone="$s['profit'] >= 0 ? 'positive' : 'negative'" :change="$changes['profit']" hint="Vendas no período" />
            </div>
        </div>
    </div>
</div>

@if ($companyGoal || $myGoal)
    <div class="panel mb-3">
        <div class="panel-body">
            <div class="row g-4">
                @if ($companyGoal)
                    <div class="{{ $myGoal ? 'col-lg-6' : 'col-12' }}">@include('partials.goal-bar', ['goal' => $companyGoal, 'title' => 'Meta da empresa neste mês'])</div>
                @endif
                @if ($myGoal)
                    <div class="{{ $companyGoal ? 'col-lg-6' : 'col-12' }}">@include('partials.goal-bar', ['goal' => $myGoal, 'title' => 'Sua meta neste mês'])</div>
                @endif
            </div>
        </div>
    </div>
@endif

@if ($lowStockCount > 0)
    <div class="notice-bar is-warning mb-3" role="status">
        <x-icon name="box" width="18" height="18" />
        <span>
            <strong>{{ $lowStockCount === 1 ? '1 produto precisa' : $lowStockCount.' produtos precisam' }} de reposição:</strong>
            {{ $lowStock->map(fn ($p) => $p->fullName().' ('.$p->quantity.')')->join(', ') }}{{ $lowStockCount > $lowStock->count() ? '…' : '' }}
        </span>
        <a href="{{ route('products.index', ['repor' => 1]) }}" class="btn btn-sm btn-light">Ver lista</a>
    </div>
@endif
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="panel h-100">
            <div class="panel-header">
                <h2 class="panel-title">Vendas recentes</h2>
                <a href="{{ route('sales.index') }}" class="small fw-semibold">Ver todas</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th data-stack-title>Cliente</th><th>Itens</th><th class="num">Total</th><th>Status</th><th class="text-end"><span class="visually-hidden">Ações</span></th></tr></thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr>
                                <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                                <td><span class="cell-truncate">{{ $sale->customerName() }}</span></td>
                                <td>{{ (int) $sale->units }} {{ (int) $sale->units === 1 ? 'item' : 'itens' }}</td>
                                <td class="num fw-semibold">{{ money($sale->total) }}</td>
                                <td><x-status :value="$sale->status" /></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('sales.show', $sale) }}" class="icon-btn" title="Ver venda"><x-icon name="eye" width="16" height="16" /></a>
                                    <a href="{{ route('sales.edit', $sale) }}" class="icon-btn" title="Editar"><x-icon name="pencil" width="16" height="16" /></a>
                                    @can('admin')
                                        <form method="POST" action="{{ route('sales.destroy', $sale) }}" class="d-inline" data-confirm="Excluir a venda {{ $sale->code }} de vez? O estoque volta, o dinheiro dela sai do caixa e ela some do sistema. Não dá para desfazer.">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="icon-btn icon-btn-danger border-0 bg-transparent" title="Excluir"><x-icon name="trash" width="16" height="16" /></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty message="Nenhuma venda registrada." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Parcelas vencidas</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Cliente</th><th class="num">Valor</th><th>Atraso</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($overdue as $installment)
                            <tr>
                                <td>
                                    <span class="cell-truncate fw-semibold">{{ $installment->customer?->name ?? 'Sem cliente' }}</span>
                                    <div class="small text-muted-2">{{ $installment->sale?->code }} · parcela {{ $installment->label() }}</div>
                                </td>
                                <td class="num fw-semibold">{{ money($installment->amount) }}</td>
                                <td><span class="pill pill-danger">{{ $installment->daysOverdue() }} {{ $installment->daysOverdue() === 1 ? 'dia' : 'dias' }}</span></td>
                                <td class="text-end"><a href="{{ $installment->originUrl() }}" class="btn btn-sm btn-light">Ver</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty message="Nenhuma parcela vencida." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Próximos vencimentos</h2></div>
            <div class="table-responsive">
                <table class="table table-compact align-middle">
                    <thead><tr><th>Cliente</th><th class="num">Valor</th><th>Vence</th></tr></thead>
                    <tbody>
                        @forelse ($upcoming as $installment)
                            @php $days = (int) now()->startOfDay()->diffInDays($installment->due_date, false); @endphp
                            <tr>
                                <td>
                                    <a href="{{ $installment->originUrl() }}" class="text-body fw-semibold cell-truncate">{{ $installment->customer?->name ?? 'Sem cliente' }}</a>
                                    <div class="small text-muted-2">{{ $installment->sale?->code }} · parcela {{ $installment->label() }}</div>
                                </td>
                                <td class="num">{{ money($installment->amount) }}</td>
                                <td>
                                    <span class="pill {{ $days === 0 ? 'pill-danger' : ($days <= 3 ? 'pill-warning' : 'pill-secondary') }}">
                                        {{ $days === 0 ? 'Hoje' : ($days === 1 ? 'Amanhã' : 'Em '.$days.' dias') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-empty message="Nenhuma parcela a vencer." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="panel h-100 d-flex flex-column">
            <div class="panel-header"><h2 class="panel-title">Últimas movimentações</h2></div>
            <div class="timeline flex-grow-1">
                @forelse ($transactions as $t)
                    @php $in = $t->type === $entrada; @endphp
                    <div class="timeline-item">
                        <span class="timeline-icon {{ $in ? 'is-in' : 'is-out' }}"><x-icon :name="$in ? 'arrow-in' : 'arrow-out'" width="14" height="14" /></span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate">{{ $t->description }}</div>
                            <div class="small text-muted-2">{{ $t->date->format('d/m/Y') }} · {{ $t->originLabel() }}</div>
                        </div>
                        <div class="num fw-semibold {{ $in ? 'text-positive' : 'text-negative' }}">{{ $in ? '+' : '−' }} {{ money($t->amount) }}</div>
                    </div>
                @empty
                    <x-empty message="Nenhuma movimentação ainda." />
                @endforelse
            </div>
            @can('admin')
                <div class="panel-footer"><a href="{{ route('finance.index') }}" class="btn btn-sm btn-light">Ver fluxo de caixa</a></div>
            @endcan
        </div>
    </div>

    <div class="col-xl-6">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Produtos vendidos no período</h2></div>
            <div class="table-responsive">
                <table class="table table-compact align-middle">
                    <thead><tr><th>Produto</th><th class="num">Qtd.</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @forelse ($sold as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($item->product && $item->product->photos)
                                            <img src="{{ $item->product->photoUrls()[0] }}" alt="" class="thumb" onerror="this.classList.add('img-missing')">
                                        @else
                                            <span class="thumb thumb-empty"><x-icon name="box" width="16" height="16" /></span>
                                        @endif
                                        <div class="min-w-0">
                                            @if ($item->product)
                                                <a href="{{ route('products.show', $item->product) }}" class="text-body fw-semibold cell-truncate">{{ $item->description }}</a>
                                                <div class="small text-muted-2">{{ $item->product->category?->name ?? 'Sem categoria' }}</div>
                                            @else
                                                <span class="fw-semibold cell-truncate">{{ $item->description }}</span>
                                                <div class="small text-muted-2">Item avulso</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="num">{{ $item->qty }}</td>
                                <td class="num fw-semibold">{{ money($item->revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><x-empty message="Nenhum produto vendido no período." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
