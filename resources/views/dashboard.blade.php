@extends('layouts.app')

@section('title', 'Dashboard')

@section('actions')
    <x-period :period="$period" />
@endsection

@section('content')
@php $s = $summary; @endphp

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-4">
        <x-stat class="stat-balance" label="Saldo atual" :value="money($s['balance'])" hint="Dinheiro disponível em caixa" />
    </div>
    <div class="col-12 col-xl-8">
        <div class="row g-3 h-100">
            <div class="col-6 col-md-4"><x-stat label="Faturamento" :value="money($s['revenue'])" :hint="$s['sales_count'].' vendas · '.$s['services_count'].' serviços'" /></div>
            <div class="col-6 col-md-4"><x-stat label="Recebido" :value="money($s['received'])" tone="positive" hint="Entradas no período" /></div>
            <div class="col-6 col-md-4"><x-stat label="A receber" :value="money($s['receivable'])" tone="warning" hint="Parcelas em aberto" /></div>
            <div class="col-6 col-md-6"><x-stat label="Gastos" :value="money($s['spent'])" tone="negative" hint="Saídas no período" /></div>
            <div class="col-12 col-md-6"><x-stat label="Lucro" :value="money($s['profit'])" :tone="$s['profit'] >= 0 ? 'positive' : 'negative'" hint="Vendas e serviços no período" /></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header">
                <h2 class="panel-title">Vendas recentes</h2>
                <a href="{{ route('sales.index') }}" class="small">Ver todas</a>
            </div>
            @forelse ($recentSales as $sale)
                <a href="{{ route('sales.show', $sale) }}" class="list-row list-link">
                    <div>
                        <div class="fw-semibold">{{ $sale->code }} · {{ $sale->customerName() }}</div>
                        <div class="small text-muted-2">{{ $sale->sale_date->format('d/m/Y') }} · {{ $sale->payment_method->label() }}</div>
                    </div>
                    <div class="text-end">
                        <div class="num fw-semibold">{{ money($sale->total) }}</div>
                        <x-status :value="$sale->status" />
                    </div>
                </a>
            @empty
                <x-empty message="Nenhuma venda registrada." />
            @endforelse
        </div>
    </div>

    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Parcelas vencidas</h2></div>
            @forelse ($overdue as $installment)
                <a href="{{ $installment->originUrl() }}" class="list-row list-link">
                    <div>
                        <div class="fw-semibold">{{ $installment->customer?->name ?? 'Sem cliente' }}</div>
                        <div class="small text-muted-2">Parcela {{ $installment->label() }} · venceu em {{ $installment->due_date->format('d/m/Y') }}</div>
                    </div>
                    <div class="num fw-semibold text-negative">{{ money($installment->amount) }}</div>
                </a>
            @empty
                <x-empty message="Nenhuma parcela vencida." />
            @endforelse
        </div>
    </div>

    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header"><h2 class="panel-title">Próximos vencimentos</h2></div>
            @forelse ($upcoming as $installment)
                <a href="{{ $installment->originUrl() }}" class="list-row list-link">
                    <div>
                        <div class="fw-semibold">{{ $installment->customer?->name ?? 'Sem cliente' }}</div>
                        <div class="small text-muted-2">Parcela {{ $installment->label() }} · {{ $installment->originLabel() }}</div>
                    </div>
                    <div class="text-end">
                        <div class="num fw-semibold">{{ money($installment->amount) }}</div>
                        <div class="small {{ $installment->due_date->isToday() ? 'text-negative fw-semibold' : 'text-muted-2' }}">{{ $installment->due_date->isToday() ? 'Vence hoje' : $installment->due_date->format('d/m/Y') }}</div>
                    </div>
                </a>
            @empty
                <x-empty message="Nenhuma parcela a vencer." />
            @endforelse
        </div>
    </div>

    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-header">
                <h2 class="panel-title">Últimas movimentações</h2>
                @can('admin')<a href="{{ route('finance.index') }}" class="small">Fluxo de caixa</a>@endcan
            </div>
            @forelse ($transactions as $t)
                <div class="list-row">
                    <div class="text-truncate">
                        <div class="fw-semibold text-truncate">{{ $t->description }}</div>
                        <div class="small text-muted-2">{{ $t->date->format('d/m/Y') }} · {{ $t->originLabel() }}</div>
                    </div>
                    <div class="num fw-semibold {{ $t->type->value === 'entrada' ? 'text-positive' : 'text-negative' }}">
                        {{ $t->type->value === 'entrada' ? '+' : '−' }} {{ money($t->amount) }}
                    </div>
                </div>
            @empty
                <x-empty message="Nenhuma movimentação ainda." />
            @endforelse
        </div>
    </div>

    <div class="col-12">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Produtos vendidos no período</h2></div>
            @forelse ($topProducts as $item)
                <div class="list-row">
                    <span class="text-truncate">{{ $item->description }}</span>
                    <span class="num fw-semibold">{{ (int) $item->qty }} un.</span>
                </div>
            @empty
                <x-empty message="Nenhum produto vendido no período." />
            @endforelse
        </div>
    </div>
</div>
@endsection
