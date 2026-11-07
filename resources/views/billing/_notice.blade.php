@if (isset($plan) && in_array($plan['state'], ['trial', 'active', 'grace', 'expired'], true))
    @php
        $isAdmin = auth()->user()?->isAdmin();
        $days = $plan['days'];
        $show = match ($plan['state']) {
            'trial' => $days <= 5,
            'active' => $days <= 3,
            default => true,
        };
        $inDays = $days === 0 ? 'hoje' : ($days === 1 ? 'amanhã' : 'em '.$days.' dias');
    @endphp
    @if ($show && ! request()->routeIs('billing.*'))
        <div class="notice-bar {{ in_array($plan['state'], ['grace', 'expired'], true) ? 'is-danger' : 'is-warning' }}" role="status">
            <x-icon name="{{ $plan['state'] === 'expired' ? 'lock' : 'clock' }}" width="18" height="18" />
            <span>
                @switch($plan['state'])
                    @case('trial') <strong>Seu teste grátis termina {{ $inDays }}.</strong> Assine para continuar usando sem interrupção. @break
                    @case('active') <strong>Sua assinatura vence {{ $inDays }}.</strong> Renove para não ficar sem acesso. @break
                    @case('grace') <strong>Sua assinatura venceu.</strong> O sistema fica só para consulta {{ $inDays }}. @break
                    @case('expired') <strong>Assinatura vencida: sistema só para consulta.</strong> Você vê e exporta seus dados, mas não consegue vender nem cadastrar. @break
                @endswitch
                @unless ($isAdmin) Fale com o administrador da sua empresa. @endunless
            </span>
            @if ($isAdmin)
                <a href="{{ route('billing.show') }}" class="btn btn-sm btn-light">{{ $plan['state'] === 'trial' ? 'Assinar' : 'Renovar' }}</a>
            @endif
        </div>
    @endif
@endif
