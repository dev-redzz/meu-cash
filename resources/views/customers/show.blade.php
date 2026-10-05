@extends('layouts.app')

@section('title', $customer->name)

@section('actions')
    <a href="{{ route('sales.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">Nova venda</a>
    <a href="{{ route('services.create', ['customer_id' => $customer->id]) }}" class="btn btn-outline-primary">Novo serviço</a>
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-light">Editar</a>
    @can('admin')
        <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="Excluir este cliente?">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">Excluir</button>
        </form>
    @endcan
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Total de compras" :value="money($totals['purchases'])" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Total pago" :value="money($totals['paid'])" tone="positive" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Em aberto" :value="money($totals['open'])" tone="warning" :hint="$installments->count().' parcelas pendentes'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Vencido" :value="money($totals['overdue'])" :tone="$totals['overdue'] > 0 ? 'negative' : null" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Dados do cliente</h2></div>
            <div class="panel-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted-2 fw-normal">CPF/CNPJ</dt><dd class="col-7">{{ $customer->document ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">WhatsApp</dt>
                    <dd class="col-7">
                        @if ($customer->whatsappNumber())
                            <a href="https://wa.me/{{ app(\App\Services\WhatsAppService::class)->normalizePhone($customer->whatsappNumber()) }}" target="_blank" rel="noopener">{{ $customer->whatsapp ?: $customer->phone }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-5 text-muted-2 fw-normal">Telefone</dt><dd class="col-7">{{ $customer->phone ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">E-mail</dt><dd class="col-7 text-break">{{ $customer->email ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Cidade</dt><dd class="col-7">{{ $customer->city ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Endereço</dt><dd class="col-7">{{ $customer->address ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Cliente desde</dt><dd class="col-7">{{ $customer->created_at->format('d/m/Y') }}</dd>
                </dl>
                @if ($customer->notes)
                    <hr><p class="mb-0" style="white-space: pre-line">{{ $customer->notes }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-header"><h2 class="panel-title">Parcelas pendentes e vencidas</h2></div>
            @include('partials.installments-table', ['installments' => $installments, 'showOrigin' => true])
        </div>

        <div class="panel mb-3">
            <div class="panel-header"><h2 class="panel-title">Histórico de vendas</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Venda</th><th>Pagamento</th><th>Status</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                                <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->code }}</a></td>
                                <td>{{ $sale->payment_method->label() }}</td>
                                <td><x-status :value="$sale->status" /></td>
                                <td class="num">{{ money($sale->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty message="Nenhuma venda para este cliente." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel mb-3">
            <div class="panel-header"><h2 class="panel-title">Histórico de serviços</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Serviço</th><th>Status</th><th class="num">Valor</th></tr></thead>
                    <tbody>
                        @forelse ($services as $service)
                            <tr>
                                <td>{{ $service->date->format('d/m/Y') }}</td>
                                <td><a href="{{ route('services.show', $service) }}">{{ $service->name }}</a></td>
                                <td><x-status :value="$service->status" /></td>
                                <td class="num">{{ money($service->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty message="Nenhum serviço para este cliente." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Histórico de pagamentos</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Referente a</th><th>Forma</th><th class="num">Valor</th></tr></thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_at->format('d/m/Y') }}</td>
                                <td>{{ $payment->description() }}</td>
                                <td>{{ $payment->payment_method->label() }}</td>
                                <td class="num text-positive">{{ money($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty message="Nenhum pagamento registrado." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('partials.pay-modal', ['receivingMethods' => \App\Enums\PaymentMethod::receiving()])
@include('partials.due-modal')
@endsection
