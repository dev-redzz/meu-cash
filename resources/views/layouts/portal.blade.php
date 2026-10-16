<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.theme-head')
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0b1426">
    <title>@yield('title', 'Área do cliente') · {{ isset($portalCompany) ? $portalCompany->name : 'Meu Cash' }}</title>
    @include('partials.head-assets')
</head>
<body class="portal-body">
@isset($portalCustomer)
    <header class="portal-top">
        <div class="portal-wrap portal-top-inner">
            <div class="portal-store">
                <span class="company-mark">{{ mb_strtoupper(mb_substr($portalCompany->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <div class="portal-store-name text-truncate">{{ $portalCompany->name }}</div>
                    <div class="portal-store-sub">Área do cliente</div>
                </div>
            </div>
            <form method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button class="btn btn-sm portal-logout"><x-icon name="logout" width="16" height="16" /> Sair</button>
            </form>
        </div>
    </header>
@endisset

<main class="@isset($portalCustomer) portal-wrap portal-main @else portal-center @endisset">
    @yield('content')
</main>

<footer class="portal-foot">
    <img src="{{ asset('img/logo-icon.png') }}" alt="" width="18" height="18"> Feito com Meu Cash ·
    <a href="{{ route('privacy') }}">Privacidade</a>
</footer>
</body>
</html>
