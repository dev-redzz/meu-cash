<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.theme-head')
    <meta name="theme-color" content="#0f1b2d">
    <title>@yield('title', 'Meu Cash')</title>
    <meta name="description" content="@yield('description', 'Sistema para lojas que vendem parcelado: vendas, parcelas, estoque, caixa e área do cliente. Funciona no celular e no computador.')">
    <meta property="og:title" content="@yield('title', 'Meu Cash')">
    <meta property="og:description" content="@yield('description', 'Sistema para lojas que vendem parcelado: vendas, parcelas, estoque, caixa e área do cliente.')">
    <meta property="og:image" content="{{ asset('img/site/painel.webp') }}">
    @include('partials.head-assets')
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/site.css') }}?v=3" rel="stylesheet">
</head>
<body class="site-body">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
    <div class="wrap site-header-inner">
        <a href="{{ route('home') }}" class="site-logo" aria-label="Meu Cash, página inicial">
            <img src="{{ asset('img/logo-icon.png') }}" alt="" width="32" height="32"> Meu Cash
        </a>
        <nav class="site-nav" id="site-nav" aria-label="Principal">
            <a href="{{ route('home') }}#por-dentro">O sistema</a>
            <a href="{{ route('home') }}#area-do-cliente">Área do cliente</a>
            @if (\App\Models\Company::billingEnabled())<a href="{{ route('home') }}#planos">Planos</a>@endif
            <a href="{{ route('home') }}#duvidas">Dúvidas</a>
            <span class="site-nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="s-btn s-btn-primary">Abrir o painel</a>
                @else
                    <a href="{{ route('login') }}" class="s-btn s-btn-ghost">Entrar</a>
                    <a href="{{ route('register') }}" class="s-btn s-btn-primary">Criar conta</a>
                @endauth
            </span>
        </nav>
        <button type="button" class="site-menu-btn" aria-label="Abrir menu" aria-controls="site-nav" aria-expanded="false" data-site-menu>
            <span></span><span></span>
        </button>
    </div>
</header>

<main id="conteudo">@yield('content')</main>

<footer class="site-footer">
    <div class="wrap">
        <div class="site-footer-top">
            <div class="site-footer-brand">
                <a href="{{ route('home') }}" class="site-logo"><img src="{{ asset('img/logo-icon.png') }}" alt="" width="32" height="32"> Meu Cash</a>
                <p>Vendas, parcelas, estoque e caixa para lojas que vendem no crediário.</p>
            </div>
            <nav class="site-footer-links" aria-label="Rodapé">
                <a href="{{ route('login') }}">Entrar</a>
                <a href="{{ route('register') }}">Criar conta</a>
                <a href="{{ route('portal.login') }}">Área do cliente</a>
                <a href="{{ route('terms') }}">Termos de uso</a>
                <a href="{{ route('privacy') }}">Privacidade</a>
                @if (config('meucash.support_email'))<a href="mailto:{{ config('meucash.support_email') }}">Contato</a>@endif
            </nav>
        </div>
        <div class="site-footer-bottom">
            <span>© {{ date('Y') }} {{ config('meucash.legal_name') }}@if (config('meucash.legal_document')), {{ config('meucash.legal_document') }}@endif</span>
            <div class="theme-switch theme-switch-sm" role="group" aria-label="Tema">
                <button type="button" data-theme-set="light" aria-label="Tema claro"><x-icon name="sun" width="15" height="15" /></button>
                <button type="button" data-theme-set="dark" aria-label="Tema escuro"><x-icon name="moon" width="15" height="15" /></button>
                <button type="button" data-theme-set="auto" aria-label="Seguir o aparelho"><x-icon name="monitor" width="15" height="15" /></button>
            </div>
        </div>
    </div>
</footer>

<script src="{{ asset('js/site.js') }}?v=2" defer></script>
@include('partials.cookie-notice')
</body>
</html>
