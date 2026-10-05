<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 · Meu Cash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center px-3">
        <div class="display-5 fw-bold">419</div>
        <p class="text-muted-2 mt-2">{{ 'A sessão expirou. Volte e tente de novo.' }}</p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary">Ir para o dashboard</a>
    </div>
</body>
</html>
