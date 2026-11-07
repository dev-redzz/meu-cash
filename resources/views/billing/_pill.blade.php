@php
    $map = [
        'free' => ['pill-secondary', 'Grátis (beta)'],
        'exempt' => ['pill-secondary', 'Isenta'],
        'trial' => ['pill-info', 'Teste'],
        'active' => ['pill-success', 'Assinante'],
        'grace' => ['pill-warning', 'Vencida'],
        'expired' => ['pill-danger', 'Bloqueada'],
    ];
    [$cls, $text] = $map[$plan['state']] ?? ['pill-secondary', $plan['state']];
@endphp
<span class="pill {{ $cls }}" @if ($plan['ends'] ?? null) title="{{ $plan['state'] === 'grace' ? 'Bloqueia em' : 'Até' }} {{ $plan['ends']->format('d/m/Y') }}" @endif>{{ $text }}@if (in_array($plan['state'], ['trial', 'active'], true) && $plan['ends']) · {{ $plan['ends']->format('d/m') }}@endif</span>
