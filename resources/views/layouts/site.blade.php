<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.theme-head')
    <meta name="theme-color" content="#0b1426">
    <title>@yield('title', 'Meu Cash')</title>
    <meta name="description" content="@yield('description', 'Sistema de vendas, estoque, parcelas e financeiro para lojas. Funciona no celular e no computador.')">
    <meta property="og:title" content="@yield('title', 'Meu Cash')">
    <meta property="og:description" content="@yield('description', 'Sistema de vendas, estoque, parcelas e financeiro para lojas.')">
    <meta property="og:image" content="{{ asset('img/logo-full.png') }}">
    @include('partials.head-assets')
    <link href="{{ asset('css/site.css') }}?v=1" rel="stylesheet">
</head>
<body class="site-body">
<header class="site-header">
    <div class="site-container site-header-inner">
        <a href="{{ route('home') }}" class="site-logo" aria-label="Meu Cash, página inicial">
            <img src="{{ asset('img/logo-icon.png') }}" alt=""> <span>Meu Cash</span>
        </a>
        <nav class="site-nav" id="site-nav" aria-label="Principal">
            <a href="{{ route('home') }}#recursos">Recursos</a>
            <a href="{{ route('home') }}#como-funciona">Como funciona</a>
            <a href="{{ route('home') }}#area-do-cliente">Área do cliente</a>
            @if (\App\Models\Company::billingEnabled())<a href="{{ route('home') }}#planos">Planos</a>@endif
            <a href="{{ route('home') }}#duvidas">Dúvidas</a>
            <div class="site-nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-cta btn-cta-sm"><span class="btn-cta-icon"><x-icon name="home" width="14" height="14" /></span>Abrir o painel</a>
                @else
                    <a href="{{ route('login') }}" class="site-nav-login">Entrar</a>
                    <a href="{{ route('register') }}" class="btn btn-cta btn-cta-sm"><span class="btn-cta-icon"><x-icon name="chevron-right" width="14" height="14" /></span>Criar conta</a>
                @endauth
            </div>
        </nav>
        <button type="button" class="site-menu-btn" aria-label="Abrir menu" aria-controls="site-nav" aria-expanded="false" data-site-menu>
            <x-icon name="menu" width="22" height="22" />
        </button>
    </div>
</header>

<main>@yield('content')</main>

<footer class="site-footer">
    <div class="site-container">
        <div class="site-footer-grid">
            <div>
                <a href="{{ route('home') }}" class="site-logo"><img src="{{ asset('img/logo-icon.png') }}" alt=""> <span>Meu Cash</span></a>
                <p class="site-footer-text">Vendas, estoque, parcelas e financeiro da sua loja em um só lugar.</p>
            </div>
            <div>
                <div class="site-footer-title">Produto</div>
                <a href="{{ route('home') }}#recursos">Recursos</a>
                <a href="{{ route('home') }}#como-funciona">Como funciona</a>
                <a href="{{ route('home') }}#duvidas">Dúvidas</a>
            </div>
            <div>
                <div class="site-footer-title">Acesso</div>
                <a href="{{ route('login') }}">Entrar</a>
                <a href="{{ route('register') }}">Criar conta</a>
                <a href="{{ route('portal.login') }}">Área do cliente</a>
            </div>
            <div>
                <div class="site-footer-title">Legal</div>
                <a href="{{ route('terms') }}">Termos de uso</a>
                <a href="{{ route('privacy') }}">Política de privacidade</a>
                @if (config('meucash.support_email'))<a href="mailto:{{ config('meucash.support_email') }}">Fale conosco</a>@endif
            </div>
        </div>
        <div class="site-footer-bottom">
            <span>© {{ date('Y') }} {{ config('meucash.legal_name') }}@if (config('meucash.legal_document')) · {{ config('meucash.legal_document') }}@endif</span>
            <div class="theme-switch theme-switch-sm" role="group" aria-label="Tema">
                <button type="button" data-theme-set="light" aria-label="Tema claro"><x-icon name="sun" width="15" height="15" /></button>
                <button type="button" data-theme-set="dark" aria-label="Tema escuro"><x-icon name="moon" width="15" height="15" /></button>
                <button type="button" data-theme-set="auto" aria-label="Tema automático"><x-icon name="monitor" width="15" height="15" /></button>
            </div>
        </div>
    </div>
</footer>

<script src="{{ asset('js/site.js') }}?v=1" defer></script>
@include('partials.cookie-notice')
</body>
</html>
