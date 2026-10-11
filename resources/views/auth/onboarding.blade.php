<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar empresa · Meu Cash</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=5" rel="stylesheet">
</head>
<body>
<main class="auth-page">
    <div class="auth-card auth-card-wide">
        <img src="{{ asset('img/logo.png') }}" alt="Meu Cash" class="auth-logo auth-logo-sm">
        <h1 class="h5 fw-bold text-center mb-1">Cadastre sua empresa</h1>
        <p class="text-center text-muted-2 small mb-4">Conectado como {{ $profile['email'] }}</p>

        <form method="POST" action="{{ route('onboarding.store') }}" novalidate>
            @csrf
            <x-input name="name" label="Nome da empresa" required maxlength="120" autofocus />
            <div class="row gx-3">
                <div class="col-sm-6"><x-input name="document" label="CPF/CNPJ (opcional)" maxlength="20" /></div>
                <div class="col-sm-6"><x-input name="phone" label="WhatsApp (opcional)" maxlength="20" /></div>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" value="1">
                <label class="form-check-label small" for="terms">Li e aceito os <a href="{{ route('terms') }}" target="_blank">termos de uso</a> e a <a href="{{ route('privacy') }}" target="_blank">política de privacidade</a></label>
                @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-100">Criar empresa e entrar</button>
        </form>
        <div class="text-center mt-3"><a href="{{ route('login') }}" class="small text-muted-2">Usar outra conta Google</a></div>
    </div>
</main>
</body>
</html>
