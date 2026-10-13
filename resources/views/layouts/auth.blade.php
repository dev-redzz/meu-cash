<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.theme-head')
    <title>@yield('title', 'Entrar') · Meu Cash</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=7" rel="stylesheet">
</head>
<body>
<div class="auth-split">
    <section class="auth-brand">
        <div class="auth-brand-inner">
            <img src="{{ asset('img/logo-full.png') }}" alt="Meu Cash" class="auth-brand-logo">
            <p class="auth-brand-tagline">Vendas, estoque e financeiro da sua loja em um só lugar.</p>
        </div>
    </section>
    <main class="auth-panel">
        <div class="auth-box">
            <img src="{{ asset('img/logo.png') }}" alt="Meu Cash" class="auth-mobile-logo">
            <h1 class="auth-title">@yield('heading')</h1>
            @hasSection('subtitle')<p class="auth-subtitle">@yield('subtitle')</p>@endif

            @foreach (['error' => 'danger', 'success' => 'success'] as $key => $class)
                @if (session($key))
                    <div class="alert alert-{{ $class }} small py-2">{{ session($key) }}</div>
                @endif
            @endforeach

            @yield('content')
        </div>
        <nav class="auth-legal">
            <a href="{{ route('terms') }}">Termos de uso</a>
            <a href="{{ route('privacy') }}">Privacidade</a>
        </nav>
    </main>
</div>
<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.getElementById(button.dataset.togglePassword);
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Ocultar' : 'Mostrar';
        button.setAttribute('aria-pressed', show);
    });
});
document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
        var button = form.querySelector('[type="submit"]');
        if (button) setTimeout(function () { button.disabled = true; }, 0);
    });
});
</script>
@include('partials.cookie-notice')
</body>
</html>
