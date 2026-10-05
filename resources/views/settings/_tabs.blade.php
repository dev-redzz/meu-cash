<ul class="nav nav-tabs-plain mb-3">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">Geral</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Usuários</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}" href="{{ route('audit.index') }}">Auditoria</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('backup.*') ? 'active' : '' }}" href="{{ route('backup.index') }}">Backup</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('categories.index') }}">Categorias</a></li>
</ul>
