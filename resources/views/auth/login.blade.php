<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu Cash</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=3" rel="stylesheet">
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

        <form method="POST" action="{{ route('login.store') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="email" class="visually-hidden">E-mail</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="E-mail" autocomplete="username" autofocus required
                    class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="visually-hidden">Senha</label>
                <input type="password" name="password" id="password" placeholder="Senha" autocomplete="current-password" required
                    class="form-control @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label small" for="remember">Manter conectado</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>
</main>
</body>
</html>
