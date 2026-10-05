<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2733; }
        h1 { font-size: 16px; margin: 0; }
        .muted { color: #667085; }
        .head { border-bottom: 2px solid #0b1f3a; padding-bottom: 8px; margin-bottom: 10px; }
        .totals { margin: 8px 0 12px; }
        .totals span { display: inline-block; margin-right: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f2f4f6; text-align: left; font-weight: bold; padding: 5px; border-bottom: 1px solid #d8dde3; }
        td { padding: 4px 5px; border-bottom: 1px solid #eceff2; }
        .num { text-align: right; white-space: nowrap; }
        .footer { position: fixed; bottom: -12px; left: 0; right: 0; font-size: 8px; color: #98a2b3; }
    </style>
</head>
<body>
    <div class="head">
        <h1>{{ $report['title'] }}</h1>
        <div class="muted">{{ $company }} · {{ $report['type'] === 'estoque' ? 'Posição atual do estoque' : $report['period'] }}</div>
    </div>
    <div class="totals">
        @foreach ($report['totals'] as $label => $value)
            <span><span class="muted">{{ $label }}:</span> <strong>{{ $value }}</strong></span>
        @endforeach
    </div>
    @include('reports._table')
    <div class="footer">Gerado em {{ now()->format('d/m/Y H:i') }} · Meu Cash</div>
</body>
</html>
