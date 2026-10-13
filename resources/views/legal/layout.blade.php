<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.theme-head')
    <title>@yield('title') · Meu Cash</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=7" rel="stylesheet">
</head>
<body>
<main class="container py-5" style="max-width: 760px;">
    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 mb-4 text-body fw-bold text-decoration-none">
        <img src="{{ asset('img/logo.png') }}" alt="" width="40" height="40" style="border-radius: 10px;"> Meu Cash
    </a>
    <h1 class="page-title mb-1">@yield('title')</h1>
    <p class="text-muted-2 small mb-4">Última atualização: 11/10/2026</p>
    <div class="panel"><div class="panel-body legal-text">@yield('content')</div></div>
    <nav class="small mt-3 d-flex gap-3">
        <a href="{{ route('privacy') }}">Política de Privacidade</a>
        <a href="{{ route('terms') }}">Termos de Uso</a>
    </nav>
</main>
@include('partials.cookie-notice')
</body>
</html>
