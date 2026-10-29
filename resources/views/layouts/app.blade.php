<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.theme-head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1426">
    <title>@yield('title', 'Painel') · Meu Cash</title>
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
    @include('partials.head-assets')
    @stack('styles')
</head>
<body class="app-body">
@php
    $user = auth()->user();
    $groups = [
        'Operação' => [
            ['Dashboard', 'dashboard', 'dashboard', 'home', false],
            ['Venda rápida', 'sales.pos', 'sales.pos', 'bolt', false],
            ['Vendas', 'sales.index', 'sales.index|sales.create|sales.show|sales.edit', 'cart', false],
            ['Clientes', 'customers.index', 'customers.*', 'users', false],
        ],
        'Estoque' => [
            ['Produtos', 'products.index', 'products.*|categories.*', 'box', false],
            ['Estoque', 'stock.index', 'stock.*', 'layers', false],
        ],
        'Gestão' => [
            ['Vendedores', 'sellers.index', 'sellers.*', 'team', true],
            ['Financeiro', 'finance.index', 'finance.*', 'wallet', true],
            ['Relatórios', 'reports.index', 'reports.*', 'chart', true],
        ],
        'Sistema' => [
            ['Notificações', 'notifications.index', 'notifications.*', 'bell', false],
            ['Configurações', 'settings.edit', 'settings.*|users.*|audit.*|export.*', 'gear', true],
        ],
    ];
    $tabs = [
        ['Início', 'dashboard', 'dashboard', 'home'],
        ['Vendas', 'sales.index', 'sales.*', 'cart'],
        ['Produtos', 'products.index', 'products.*|categories.*|stock.*', 'box'],
        ['Clientes', 'customers.index', 'customers.*', 'users'],
    ];
@endphp
<aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menu">
    <div class="sidebar-head">
        <a href="{{ route('dashboard') }}" class="brand"><img src="{{ asset('img/logo-icon.png') }}" alt=""> <span>Meu Cash</span></a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Fechar menu"></button>
    </div>

    <nav class="side-nav" aria-label="Principal">
        @foreach ($groups as $group => $items)
            @php $visible = collect($items)->reject(fn ($i) => $i[4] && ! $user->isAdmin()); @endphp
            @continue($visible->isEmpty())
            <div class="side-group">
                <div class="side-group-title">{{ $group }}</div>
                <ul class="nav flex-column">
                    @foreach ($visible as [$label, $route, $pattern, $icon])
                        <li class="nav-item">
                            <a href="{{ route($route) }}" @class(['nav-link', 'active' => request()->routeIs(...explode('|', $pattern))])>
                                <x-icon :name="$icon" />
                                <span>{{ $label }}</span>
                                @if ($route === 'notifications.index' && $unreadNotifications > 0)
                                    <span class="nav-count ms-auto">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

</aside>

<div class="app-main">
    <header class="app-topbar">
        <div class="topbar-inner">
            <button class="btn btn-ghost btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menu">
                <x-icon name="menu" />
            </button>
            <a href="{{ route('dashboard') }}" class="topbar-logo d-lg-none" aria-label="Início"><img src="{{ asset('img/logo-icon.png') }}" alt=""></a>

            <form action="{{ route('search') }}" method="GET" class="topbar-search" role="search" data-no-lock>
                <x-icon name="search" width="17" height="17" />
                <input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Buscar cliente, produto ou venda" aria-label="Buscar" autocomplete="off">
                <kbd class="d-none d-xl-inline">/</kbd>
            </form>

            <div class="topbar-actions">
                <a href="{{ route('search') }}" class="btn btn-icon btn-ghost d-md-none" aria-label="Buscar"><x-icon name="search" /></a>
                <a href="{{ route('notifications.index') }}" class="btn btn-icon btn-ghost position-relative" aria-label="Notificações">
                    <x-icon name="bell" />
                    @if ($unreadNotifications > 0)
                        <span class="dot-count">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                    @endif
                </a>
                <div class="dropdown">
                    <button class="user-chip" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Minha conta">
                        @include('partials.avatar', ['person' => $user, 'size' => 34])
                        <span class="user-chip-text d-none d-md-flex">
                            <span class="user-chip-name">{{ $user->firstName() }}</span>
                            <span class="user-chip-role">{{ $user->role->label() }}</span>
                        </span>
                        <x-icon name="chevron-down" width="16" height="16" class="d-none d-md-inline text-muted-2" />
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end user-menu">
                        <li class="user-menu-head">
                            @include('partials.avatar', ['person' => $user, 'size' => 40])
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                                <div class="small text-muted-2 text-truncate">{{ $user->email }}</div>
                                <div class="small text-muted-2 text-truncate">{{ $user->role->label() }} · {{ $companyName }}</div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="px-3 pt-1 pb-2">
                            <div class="small text-muted-2 fw-semibold mb-1">Tema</div>
                            <div class="theme-switch" role="group" aria-label="Tema">
                                <button type="button" data-theme-set="light"><x-icon name="sun" width="15" height="15" /> Claro</button>
                                <button type="button" data-theme-set="dark"><x-icon name="moon" width="15" height="15" /> Escuro</button>
                                <button type="button" data-theme-set="auto"><x-icon name="monitor" width="15" height="15" /> Auto</button>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#shortcutsModal"><x-icon name="monitor" width="16" height="16" /> Atalhos do teclado <kbd class="ms-auto">?</kbd></button></li>
                        @can('admin')
                            <li><a class="dropdown-item" href="{{ route('users.index') }}"><x-icon name="team" width="16" height="16" /> Equipe</a></li>
                        @endcan
                        @if ($user->isPlatformAdmin())
                            <li><a class="dropdown-item" href="{{ route('platform.index') }}"><x-icon name="layers" width="16" height="16" /> Painel da plataforma</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-negative"><x-icon name="logout" width="16" height="16" /> Sair</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="app-content">
        <div class="page-head">
            <div class="min-w-0">
                <h1 class="page-title">@hasSection('heading') @yield('heading') @else @yield('title') @endif</h1>
                @hasSection('subtitle')<p class="page-subtitle">@yield('subtitle')</p>@endif
            </div>
            <div class="page-actions">@yield('actions')</div>
        </div>
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<nav class="tabbar d-lg-none" aria-label="Atalhos">
    @foreach ($tabs as [$label, $route, $pattern, $icon])
        <a href="{{ route($route) }}" @class(['tabbar-item', 'active' => request()->routeIs(...explode('|', $pattern))])>
            <x-icon :name="$icon" width="22" height="22" />
            <span>{{ $label }}</span>
        </a>
    @endforeach
    <button type="button" class="tabbar-item" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
        <x-icon name="menu" width="22" height="22" />
        <span>Menu</span>
    </button>
</nav>

@php
    $isAdmin = $user->isAdmin();
    $shortcutGo = array_filter([
        'd' => ['Dashboard', route('dashboard')],
        'v' => ['Vendas', route('sales.index')],
        'c' => ['Clientes', route('customers.index')],
        'p' => ['Produtos', route('products.index')],
        'e' => ['Estoque', route('stock.index')],
        'f' => $isAdmin ? ['Financeiro', route('finance.index')] : null,
        'r' => $isAdmin ? ['Relatórios', route('reports.index')] : null,
        'n' => ['Notificações', route('notifications.index')],
        'o' => $isAdmin ? ['Configurações', route('settings.edit')] : null,
    ]);
    $shortcutNew = [
        'r' => ['Venda rápida', route('sales.pos')],
        'n' => ['Nova venda', route('sales.create')],
        'c' => ['Novo cliente', route('customers.create')],
        'p' => ['Novo produto', route('products.create')],
    ];
@endphp
<script type="application/json" id="shortcuts-data">{!! json_encode(['search' => route('search'), 'go' => array_map(fn ($i) => $i[1], $shortcutGo), 'new' => array_map(fn ($i) => $i[1], $shortcutNew)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}</script>
<div class="modal fade" id="shortcutsModal" tabindex="-1" aria-labelledby="shortcutsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-head">
                <span class="stat-icon accent-blue"><x-icon name="monitor" width="18" height="18" /></span>
                <div class="min-w-0">
                    <h2 class="panel-title mb-0" id="shortcutsTitle">Atalhos do teclado</h2>
                    <p class="form-section-desc mb-0">Funcionam quando você não está digitando em um campo.</p>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body shortcuts-body">
                <section>
                    <h3>Criar</h3>
                    @foreach ($shortcutNew as $key => [$label])
                        <div class="shortcut-row"><span>{{ $label }}</span><span><kbd>{{ strtoupper($key) }}</kbd></span></div>
                    @endforeach
                    <h3 class="mt-4">Geral</h3>
                    <div class="shortcut-row"><span>Buscar</span><span><kbd>/</kbd> ou <kbd>Ctrl</kbd> <kbd>K</kbd></span></div>
                    <div class="shortcut-row"><span>Desfazer exclusão</span><span><kbd>Ctrl</kbd> <kbd>Z</kbd></span></div>
                    <div class="shortcut-row"><span>Fechar janela</span><span><kbd>Esc</kbd></span></div>
                    <div class="shortcut-row"><span>Ver esta lista</span><span><kbd>?</kbd></span></div>
                </section>
                <section>
                    <h3>Ir para</h3>
                    @foreach ($shortcutGo as $key => [$label])
                        <div class="shortcut-row"><span>{{ $label }}</span><span><kbd>G</kbd> <kbd>{{ strtoupper($key) }}</kbd></span></div>
                    @endforeach
                </section>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/pt.js"></script>
<script src="{{ asset('js/app.js') }}?v=14"></script>
@stack('scripts')
@include('partials.cookie-notice')
</body>
</html>
