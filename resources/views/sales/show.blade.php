@extends('layouts.app')

@section('title', 'Venda '.$sale->code)

@section('actions')
    <a href="{{ route('sales.edit', $sale) }}" class="btn btn-light">Editar</a>
    <button type="button" class="btn btn-light" onclick="window.print()">Imprimir</button>
    @can('admin')
        @if ($sale->status->value === 'concluida')
            <form method="POST" action="{{ route('sales.cancel', $sale) }}" data-confirm="Cancelar esta venda? O estoque volta e as parcelas em aberto serão canceladas.">
                @csrf
                <button class="btn btn-outline-danger">Cancelar venda</button>
            </form>
        @endif
    @endcan
@endsection

@section('content')
@php $received = $sale->payments->sum('amount'); $open = $sale->installments->filter->isOpen()->sum('amount'); @endphp
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Total da venda" :value="money($sale->total)" :hint="$sale->discount > 0 ? 'Desconto de '.money($sale->discount) : null" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Recebido" :value="money($received)" tone="positive" /></div>
    <div class="col-6 col-lg-3"><x-stat label="A receber" :value="money($open)" :tone="$open > 0 ? 'warning' : null" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Lucro" :value="money($sale->profit)" :tone="$sale->profit >= 0 ? 'positive' : 'negative'" :hint="'Custo '.money($sale->cost_total)" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Dados da venda</h2><x-status :value="$sale->status" /></div>
            <div class="panel-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted-2 fw-normal">Código</dt><dd class="col-7">{{ $sale->code }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Data</dt><dd class="col-7">{{ $sale->sale_date->format('d/m/Y') }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Cliente</dt>
                    <dd class="col-7">
                        @if ($sale->customer)<a href="{{ route('customers.show', $sale->customer) }}">{{ $sale->customer->name }}</a>@else Consumidor final @endif
                    </dd>
                    <dt class="col-5 text-muted-2 fw-normal">Pagamento</dt><dd class="col-7">{{ $sale->payment_method->label() }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Entrada</dt><dd class="col-7">{{ money($sale->down_payment) }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Parcelas</dt><dd class="col-7">{{ $sale->installments_count ?: 'À vista' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Vendedor</dt><dd class="col-7">{{ $sale->user?->name ?? '—' }}</dd>
                </dl>
                @if ($sale->notes)<hr><p class="mb-0" style="white-space: pre-line">{{ $sale->notes }}</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-header"><h2 class="panel-title">Itens</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Item</th><th class="num">Qtd.</th><th class="num">Preço un.</th><th class="num">Custo un.</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>@if ($item->product)<a href="{{ route('products.show', $item->product) }}">{{ $item->description }}</a>@else {{ $item->description }} @endif</td>
                                <td class="num">{{ $item->quantity }}</td>
                                <td class="num">{{ money($item->unit_price) }}</td>
                                <td class="num text-muted-2">{{ money($item->unit_cost) }}</td>
                                <td class="num">{{ money($item->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end">Subtotal</td><td class="num">{{ money($sale->subtotal) }}</td></tr>
                        @if ($sale->discount > 0)<tr><td colspan="4" class="text-end">Desconto</td><td class="num">− {{ money($sale->discount) }}</td></tr>@endif
                        <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="num">{{ money($sale->total) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($sale->installments->isNotEmpty())
            <div class="panel mb-3">
                <div class="panel-header"><h2 class="panel-title">Parcelas</h2></div>
                @include('partials.installments-table', ['installments' => $sale->installments->each->setRelation('customer', $sale->customer)])
            </div>
        @endif

        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Pagamentos recebidos</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Referente a</th><th>Forma</th><th class="num">Valor</th></tr></thead>
                    <tbody>
                        @forelse ($sale->payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_at->format('d/m/Y') }}</td>
                                <td>{{ $payment->installment ? 'Parcela '.$payment->installment->label() : 'Entrada / à vista' }}</td>
                                <td>{{ $payment->payment_method->label() }}</td>
                                <td class="num text-positive">{{ money($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty message="Nenhum pagamento recebido ainda." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('partials.pay-modal')
@include('partials.due-modal')
@endsection
