<ul class="nav nav-tabs-plain mb-3">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('settings.edit') ? 'active' : '' }}" href="{{ route('settings.edit') }}">Empresa</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('settings.payments') ? 'active' : '' }}" href="{{ route('settings.payments') }}">Recebimentos</a></li>
    @if (config('billing.enabled'))
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}" href="{{ route('billing.show') }}">Assinatura</a></li>
    @endif
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Equipe</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('export.*') ? 'active' : '' }}" href="{{ route('export.index') }}">Exportar dados</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">Auditoria</a></li>
</ul>
