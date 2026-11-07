@extends('layouts.app')

@section('title', 'Assinatura')

@section('content')
@include('settings._tabs')
@php
    $state = $status['state'];
    $headline = match ($state) {
        'free' => 'Grátis durante a versão beta',
        'exempt' => 'Sua empresa não paga assinatura',
        'trial' => 'Teste grátis até '.$status['ends']->format('d/m/Y'),
        'active' => 'Assinatura ativa até '.$status['ends']->format('d/m/Y'),
        'grace' => 'Assinatura vencida em '.$status['expired_at']->format('d/m/Y'),
        'expired' => 'Assinatura vencida: sistema só para consulta',
    };
    $text = match ($state) {
        'free' => 'Aproveite: todos os recursos estão liberados sem custo.',
        'exempt' => 'Nada a pagar.',
        'trial' => 'Todos os recursos liberados. Se assinar agora, o período pago começa depois do teste.',
        'active' => 'Se renovar agora, o novo período começa depois de '.$status['ends']->format('d/m/Y').'.',
        'grace' => 'Você ainda pode usar normalmente até '.$status['ends']->format('d/m/Y').'. Depois o sistema fica só para consulta.',
        'expired' => 'Seus dados estão guardados. Assim que o pagamento for aprovado, tudo volta ao normal.',
    };
@endphp

<div class="row g-3">
    <div class="col-lg-5">
        <section class="panel h-100">
            <div class="panel-body">
                <div class="billing-state is-{{ $state }}">
                    <span class="stat-icon accent-{{ in_array($state, ['grace', 'expired'], true) ? 'rose' : ($state === 'trial' ? 'blue' : 'green') }}"><x-icon name="{{ $state === 'expired' ? 'lock' : 'check' }}" width="18" height="18" /></span>
                    <div>
                        <div class="billing-state-title">{{ $headline }}</div>
                        <p class="billing-state-text">{{ $text }}</p>
                    </div>
                </div>

                <h3 class="billing-sub">Pagamentos</h3>
                @forelse ($payments as $p)
                    <div class="billing-row">
                        <div>
                            <div class="fw-semibold">Plano {{ $p->planLabel() }}</div>
                            <div class="small text-muted-2">Pago em {{ $p->paid_at->format('d/m/Y') }} · {{ $p->period_start->format('d/m/Y') }} a {{ $p->period_end->format('d/m/Y') }}</div>
                        </div>
                        <strong>{{ money($p->amount) }}</strong>
                    </div>
                @empty
                    <p class="small text-muted-2 mb-0">Nenhum pagamento ainda.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="col-lg-7">
        @if (in_array($state, ['free', 'exempt'], true))
            <section class="panel h-100"><div class="panel-body"><x-empty message="Não há nada a pagar agora." /></div></section>
        @else
            <div class="billing-plans">
                @foreach ($plans as $key => $p)
                    @php $monthly = $p['price'] / $p['months']; $full = $plans['mensal']['price'] ?? $monthly; @endphp
                    <form method="POST" action="{{ route('billing.checkout') }}" class="billing-plan {{ $p['months'] > 1 ? 'is-featured' : '' }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $key }}">
                        <div class="billing-plan-head">
                            <span class="billing-plan-name">{{ $p['label'] }}</span>
                            @if ($p['months'] > 1 && $full > $monthly)
                                <span class="pill pill-success">Economize {{ round((1 - $monthly / $full) * 100) }}%</span>
                            @endif
                        </div>
                        <div class="billing-plan-price">{{ money($p['price']) }}<small>/{{ $p['months'] > 1 ? 'ano' : 'mês' }}</small></div>
                        <div class="billing-plan-note">{{ $p['months'] > 1 ? 'Sai por '.money($monthly).' por mês' : 'Renove quando quiser' }}</div>
                        <button class="btn btn-cta w-100 justify-content-start" @disabled(! $ready)>
                            <span class="btn-cta-icon"><x-icon name="card" width="16" height="16" /></span>Pagar {{ mb_strtolower($p['label']) }}
                        </button>
                    </form>
                @endforeach
            </div>
            <p class="small text-muted-2 mt-3 mb-0">
                <x-icon name="lock" width="14" height="14" />
                Pagamento seguro pelo Mercado Pago, com Pix, cartão ou boleto. Pelo Pix e cartão a liberação é na hora; pelo boleto, em até 2 dias úteis.
                @unless ($ready) <br><span class="text-negative">O pagamento online ainda não foi configurado pela plataforma.</span> @endunless
            </p>
        @endif
    </div>
</div>
@endsection
