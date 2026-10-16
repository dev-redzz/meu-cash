@extends('layouts.portal')

@section('title', 'Minhas compras')

@section('content')
@php
    $firstName = str($customer->name)->explode(' ')->first();
    $storePhone = app(\App\Services\WhatsAppService::class)->normalizePhone($company->phone);
    $days = $next ? (int) today()->diffInDays($next->due_date, false) : null;
@endphp

<div class="portal-hello">
    <h1>Olá, {{ $firstName }}</h1>
    <p>Aqui estão suas compras na {{ $company->name }}.</p>
</div>

@if ($totals['overdue_count'] > 0)
    <section class="portal-hero is-late">
        <div class="portal-hero-label">{{ $totals['overdue_count'] === 1 ? 'Você tem 1 parcela atrasada' : 'Você tem '.$totals['overdue_count'].' parcelas atrasadas' }}</div>
        <div class="portal-hero-value">{{ money($totals['overdue']) }}</div>
        <div class="portal-hero-text">Fale com a loja para combinar o pagamento.</div>
    </section>
@elseif ($next)
    <section class="portal-hero">
        <div class="portal-hero-label">Próxima parcela · {{ $next->sale?->code }} · {{ $next->label() }}</div>
        <div class="portal-hero-value">{{ money($next->amount) }}</div>
        <div class="portal-hero-text">
            Vence {{ $days === 0 ? 'hoje' : ($days === 1 ? 'amanhã' : 'em '.$next->due_date->format('d/m/Y')) }}
            @if ($days > 1) <span class="portal-hero-chip">em {{ $days }} dias</span> @endif
        </div>
    </section>
@elseif ($sales->isNotEmpty())
    <section class="portal-hero is-done">
        <div class="portal-hero-label">Tudo em dia</div>
        <div class="portal-hero-value">Nenhuma parcela em aberto</div>
        <div class="portal-hero-text">Obrigado por comprar com a gente.</div>
    </section>
@endif

<div class="portal-stats">
    <div class="portal-stat"><span>Total comprado</span><strong>{{ money($totals['purchased']) }}</strong></div>
    <div class="portal-stat"><span>Já pago</span><strong class="text-positive">{{ money($totals['paid']) }}</strong></div>
    <div class="portal-stat"><span>Em aberto</span><strong>{{ money($totals['open']) }}</strong><small>{{ $totals['open_count'] }} {{ $totals['open_count'] === 1 ? 'parcela' : 'parcelas' }}</small></div>
</div>

@if ($storePhone)
    <a class="btn btn-cta btn-cta-green w-100 justify-content-start mb-4" target="_blank" rel="noopener"
       href="https://wa.me/{{ $storePhone }}?text={{ rawurlencode('Olá! Sou '.$customer->name.' (código '.$customer->access_code.'). Gostaria de falar sobre minhas parcelas.') }}">
        <span class="btn-cta-icon"><x-icon name="whatsapp" width="16" height="16" /></span>Falar com a loja no WhatsApp
    </a>
@endif

<h2 class="portal-section-title">Suas compras</h2>

@forelse ($sales as $sale)
    @php
        $paid = (float) $sale->payments->sum('amount');
        $pct = $sale->total > 0 ? min(100, round($paid / $sale->total * 100)) : 100;
        $openSale = $sale->installments->filter->isOpen()->sum('amount');
    @endphp
    <article class="portal-sale">
        <header class="portal-sale-head">
            <div>
                <div class="portal-sale-code">Compra {{ $sale->code }}</div>
                <div class="portal-sale-date">{{ $sale->sale_date->format('d/m/Y') }} · {{ $sale->payment_method->label() }}@if ($sale->installments_count) em {{ $sale->installments_count }}x @endif</div>
            </div>
            <div class="text-end">
                <div class="portal-sale-total">{{ money($sale->total) }}</div>
                @if ($openSale > 0)
                    <span class="pill pill-warning">Em aberto</span>
                @else
                    <span class="pill pill-success">Quitada</span>
                @endif
            </div>
        </header>

        <ul class="portal-items">
            @foreach ($sale->items as $item)
                <li><span>{{ $item->quantity }}× {{ $item->description }}</span><span>{{ money($item->total) }}</span></li>
            @endforeach
        </ul>

        <div class="portal-progress">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted-2">Pago {{ money($paid) }}</span>
                <span class="fw-semibold">{{ $pct }}%</span>
            </div>
            <div class="goal-track goal-track-sm"><div class="goal-fill {{ $pct >= 100 ? 'is-done' : 'is-good' }}" style="width: {{ $pct }}%"></div></div>
        </div>

        @if ($sale->installments->isNotEmpty())
            <details class="portal-installments" @if ($openSale > 0) open @endif>
                <summary>Parcelas ({{ $sale->installments->count() }})</summary>
                <ol>
                    @foreach ($sale->installments as $installment)
                        @php $late = $installment->isOpen() && $installment->due_date->lt(today()); @endphp
                        <li class="{{ $late ? 'is-late' : '' }}">
                            <div>
                                <div class="fw-semibold">Parcela {{ $installment->label() }}</div>
                                <div class="small text-muted-2">
                                    @if ($installment->paid_at)
                                        Paga em {{ $installment->paid_at->format('d/m/Y') }}
                                    @else
                                        Vence em {{ $installment->due_date->format('d/m/Y') }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold">{{ money($installment->amount) }}</div>
                                @if ($late)
                                    <span class="pill pill-danger">Atrasada</span>
                                @else
                                    <x-status :value="$installment->status" />
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </details>
        @endif

        <a href="{{ route('portal.receipt', $sale->id) }}" target="_blank" class="btn btn-cta btn-cta-light w-100 justify-content-start mt-3">
            <span class="btn-cta-icon"><x-icon name="receipt" width="16" height="16" /></span>Baixar comprovante
        </a>
    </article>
@empty
    <div class="portal-empty">Você ainda não tem compras registradas nesta loja.</div>
@endforelse

<p class="portal-note">Seu código de acesso é <strong>{{ $customer->access_code }}</strong>. Não compartilhe com outras pessoas.</p>
@endsection
