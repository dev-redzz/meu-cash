<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu Cash</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=5" rel="stylesheet">
</head>
<body>
<main class="auth-page">
    <div class="auth-card">
        <img src="{{ asset('img/logo.png') }}" alt="Meu Cash" class="auth-logo">

        @foreach (['error' => 'danger', 'success' => 'success'] as $key => $class)
            @if (session($key))
                <div class="alert alert-{{ $class }} small py-2">{{ session($key) }}</div>
            @endif
        @endforeach

        <a href="{{ route('auth.google') }}" class="btn btn-google w-100">
            @include('partials.google-mark')
            Entrar com Google
        </a>
    </div>
</main>
</body>
</html>
