<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>429 · Meu Cash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center px-3">
        <div class="display-5 fw-bold">429</div>
        <p class="text-muted-2 mt-2">{{ 'Muitas tentativas seguidas. Aguarde um minuto e tente de novo.' }}</p>
        <a href="{{ url()->previous() }}" class="btn btn-primary">Voltar</a>
    </div>
</body>
</html>
