<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') · Meu Cash</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=3" rel="stylesheet">
    @stack('styles')
</head>
<body>
@php
    $nav = [
        ['Dashboard', 'dashboard', 'dashboard', 'home', false],
        ['Vendas', 'sales.index', 'sales.*', 'cart', false],
        ['Produtos', 'products.index', 'products.*|categories.*', 'box', false],
        ['Estoque', 'stock.index', 'stock.*', 'layers', false],
        ['Clientes', 'customers.index', 'customers.*', 'users', false],
        ['Serviços', 'services.index', 'services.*', 'wrench', false],
        ['Financeiro', 'finance.index', 'finance.*', 'wallet', true],
        ['Relatórios', 'reports.index', 'reports.*', 'chart', true],
        ['Notificações', 'notifications.index', 'notifications.*', 'bell', false],
        ['Configurações', 'settings.edit', 'settings.*|users.*|audit.*|backup.*', 'gear', true],
    ];
@endphp
<aside class="app-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menu">
    <div class="d-flex align-items-center justify-content-between gap-2 px-3 pt-4 pb-3">
        <a href="{{ route('dashboard') }}" class="brand"><img src="{{ asset('img/logo-white.png') }}" alt=""> Meu Cash</a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Fechar menu"></button>
    </div>
    <nav class="side-nav px-2 pb-4">
        <ul class="nav flex-column gap-1 mt-2">
            @foreach ($nav as [$label, $route, $pattern, $icon, $adminOnly])
                @continue($adminOnly && ! auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="{{ route($route) }}" @class(['nav-link', 'active' => request()->routeIs(...explode('|', $pattern))])>
                        <x-icon :name="$icon" />
                        <span>{{ $label }}</span>
                        @if ($route === 'notifications.index' && $unreadNotifications > 0)
                            <span class="badge rounded-pill text-bg-warning ms-auto">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</aside>

<div class="app-main">
    <header class="app-topbar">
        <div class="container-fluid px-3 px-lg-4 py-2 d-flex align-items-center gap-2">
            <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menu">
                <x-icon name="menu" />
            </button>
            <span class="fw-bold text-truncate">{{ $companyName }}</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light position-relative" aria-label="Notificações">
                    <x-icon name="bell" />
                    @if ($unreadNotifications > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    @endif
                </a>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted-2">{{ auth()->user()->email }}<br>{{ auth()->user()->role->label() }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Sair</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid px-3 px-lg-4 py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="page-title">@hasSection('heading') @yield('heading') @else @yield('title') @endif</h1>
            <div class="d-flex flex-wrap gap-2">@yield('actions')</div>
        </div>
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=3"></script>
@stack('scripts')
</body>
</html>
