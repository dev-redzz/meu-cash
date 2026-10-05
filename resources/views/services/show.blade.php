@extends('layouts.app')

@section('title', $service->name)

@section('actions')
    <a href="{{ route('services.edit', $service) }}" class="btn btn-light">Editar</a>
    @can('admin')
        @if ($service->status->value !== 'cancelado')
            <form method="POST" action="{{ route('services.cancel', $service) }}" data-confirm="Cancelar este serviço? As parcelas em aberto serão canceladas.">
                @csrf
                <button class="btn btn-outline-danger">Cancelar serviço</button>
            </form>
        @endif
    @endcan
@endsection

@section('content')
@php $received = $service->payments->sum('amount'); $open = $service->installments->filter->isOpen()->sum('amount'); @endphp
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Valor" :value="money($service->amount)" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Custo total" :value="money($service->expenses_total)" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Lucro" :value="money($service->profit())" :tone="$service->profit() >= 0 ? 'positive' : 'negative'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Recebido / a receber" :value="money($received)" :hint="money($open).' em aberto'" tone="positive" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Dados</h2><x-status :value="$service->status" /></div>
            <div class="panel-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted-2 fw-normal">Cliente</dt><dd class="col-7">@if ($service->customer)<a href="{{ route('customers.show', $service->customer) }}">{{ $service->customer->name }}</a>@else — @endif</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Categoria</dt><dd class="col-7">{{ $service->category?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Data</dt><dd class="col-7">{{ $service->date->format('d/m/Y') }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Pagamento</dt><dd class="col-7">{{ $service->payment_method->label() }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Entrada</dt><dd class="col-7">{{ money($service->down_payment) }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Parcelas</dt><dd class="col-7">{{ $service->installments_count ?: 'À vista' }}</dd>
                </dl>
                @if ($service->notes)<hr><p class="mb-0" style="white-space: pre-line">{{ $service->notes }}</p>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-header"><h2 class="panel-title">Despesas do serviço</h2></div>
            <form method="POST" action="{{ route('services.expenses.store', $service) }}" class="panel-body border-bottom">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label" for="se_desc">Descrição</label>
                        <input type="text" name="description" id="se_desc" class="form-control form-control-sm" required maxlength="255" placeholder="Ex.: domínio .com.br">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="se_amount">Valor</label>
                        <input type="text" name="amount" id="se_amount" class="form-control form-control-sm" inputmode="decimal" required placeholder="0,00">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="se_date">Data</label>
                        <input type="date" name="date" id="se_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Adicionar</button></div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Descrição</th><th class="num">Valor</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($service->expenses as $expense)
                            <tr>
                                <td>{{ $expense->date->format('d/m/Y') }}</td>
                                <td>{{ $expense->description }}</td>
                                <td class="num">{{ money($expense->amount) }}</td>
                                <td class="text-end">
                                    @can('admin')
                                        <form method="POST" action="{{ route('services.expenses.destroy', [$service, $expense]) }}" data-confirm="Remover esta despesa?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger p-0">Remover</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty message="Nenhuma despesa registrada." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($service->installments->isNotEmpty())
            <div class="panel mb-3">
                <div class="panel-header"><h2 class="panel-title">Parcelas</h2></div>
                @include('partials.installments-table', ['installments' => $service->installments->each->setRelation('customer', $service->customer)])
            </div>
        @endif

        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Pagamentos recebidos</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Referente a</th><th>Forma</th><th class="num">Valor</th></tr></thead>
                    <tbody>
                        @forelse ($service->payments as $payment)
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
