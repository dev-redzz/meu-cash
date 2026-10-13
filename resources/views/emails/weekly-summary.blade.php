@php
    $change = function (?float $value) {
        if ($value === null) return '';
        $color = $value >= 0 ? '#15803d' : '#c0362c';
        $arrow = $value >= 0 ? '▲' : '▼';
        return '<span style="color:'.$color.';font-size:12px;font-weight:600;">'.$arrow.' '.number_format(abs($value), 1, ',', '.').'% vs. semana anterior</span>';
    };
    $font = "font-family:Inter,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;";
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resumo da semana</title>
</head>
<body style="margin:0;padding:0;background:#f2f4f7;{{ $font }}color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f4f7;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;">

    <tr><td style="background:#0d1b33;border-radius:14px 14px 0 0;padding:22px 28px;">
        <img src="{{ asset('img/logo-full.png') }}" alt="Meu Cash" height="48" style="display:block;height:48px;width:auto;">
    </td></tr>

    <tr><td style="background:#ffffff;padding:28px;">
        <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">{{ $s['company'] }}</p>
        <h1 style="margin:0 0 6px;font-size:22px;color:#111827;">Resumo da semana</h1>
        <p style="margin:0 0 24px;font-size:14px;color:#6b7280;">Olá, {{ $user->firstName() }}! Veja como foi de {{ $s['start']->format('d/m') }} a {{ $s['end']->format('d/m/Y') }}.</p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="50%" style="padding:0 6px 12px 0;vertical-align:top;">
                    <div style="border:1px solid #e5e8ee;border-radius:12px;padding:16px;">
                        <div style="font-size:12px;color:#6b7280;font-weight:600;">Faturamento</div>
                        <div style="font-size:22px;font-weight:700;color:#111827;margin:4px 0;">{{ money($s['revenue']) }}</div>
                        {!! $change($s['revenue_change']) !!}
                    </div>
                </td>
                <td width="50%" style="padding:0 0 12px 6px;vertical-align:top;">
                    <div style="border:1px solid #e5e8ee;border-radius:12px;padding:16px;">
                        <div style="font-size:12px;color:#6b7280;font-weight:600;">Lucro</div>
                        <div style="font-size:22px;font-weight:700;color:{{ $s['profit'] < 0 ? '#c0362c' : '#15803d' }};margin:4px 0;">{{ money($s['profit']) }}</div>
                        {!! $change($s['profit_change']) !!}
                    </div>
                </td>
            </tr>
            <tr>
                <td width="50%" style="padding:0 6px 12px 0;vertical-align:top;">
                    <div style="border:1px solid #e5e8ee;border-radius:12px;padding:16px;">
                        <div style="font-size:12px;color:#6b7280;font-weight:600;">Vendas</div>
                        <div style="font-size:22px;font-weight:700;color:#111827;margin:4px 0;">{{ $s['sales_count'] }}</div>
                        <span style="font-size:12px;color:#6b7280;">{{ $s['sales_count_before'] }} na semana anterior</span>
                    </div>
                </td>
                <td width="50%" style="padding:0 0 12px 6px;vertical-align:top;">
                    <div style="border:1px solid #e5e8ee;border-radius:12px;padding:16px;">
                        <div style="font-size:12px;color:#6b7280;font-weight:600;">Entrou no caixa</div>
                        <div style="font-size:22px;font-weight:700;color:#111827;margin:4px 0;">{{ money($s['received']) }}</div>
                        <span style="font-size:12px;color:#6b7280;">Saídas: {{ money($s['spent']) }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <div style="background:#155eef;border-radius:12px;padding:16px 18px;margin:4px 0 22px;color:#ffffff;">
            <span style="font-size:12px;opacity:.85;font-weight:600;">Saldo atual do caixa</span><br>
            <span style="font-size:24px;font-weight:700;">{{ money($s['balance']) }}</span>
        </div>

        @if ($s['company_goal'])
            @php $goal = $s['company_goal']; $pct = min(100, $goal->progress); @endphp
            <h2 style="font-size:15px;margin:0 0 8px;color:#111827;">Meta do mês</h2>
            <div style="background:#e8ecf2;border-radius:999px;height:10px;overflow:hidden;">
                <div style="background:{{ $goal->progress >= 100 ? '#16a34a' : '#155eef' }};width:{{ $pct }}%;height:10px;"></div>
            </div>
            <p style="font-size:13px;color:#6b7280;margin:8px 0 22px;">
                {{ money($goal->done) }} de {{ money($goal->goal) }} ({{ number_format($goal->progress, 0, ',', '.') }}%).
                @if ($goal->missing > 0) Faltam {{ money($goal->missing) }}, cerca de {{ money($goal->per_day) }} por dia. @else Meta batida! @endif
            </p>
        @endif

        @if ($s['overdue_count'] > 0)
            <h2 style="font-size:15px;margin:0 0 8px;color:#c0362c;">{{ $s['overdue_count'] }} {{ $s['overdue_count'] === 1 ? 'parcela atrasada' : 'parcelas atrasadas' }} · {{ money($s['overdue_total']) }}</h2>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;margin-bottom:6px;">
                @foreach ($s['overdue_list'] as $installment)
                    <tr>
                        <td style="padding:7px 0;border-bottom:1px solid #eef0f4;">{{ $installment->customer?->name ?? 'Sem cliente' }}</td>
                        <td style="padding:7px 0;border-bottom:1px solid #eef0f4;color:#6b7280;">venceu {{ $installment->due_date->format('d/m') }}</td>
                        <td align="right" style="padding:7px 0;border-bottom:1px solid #eef0f4;font-weight:600;">{{ money($installment->amount) }}</td>
                    </tr>
                @endforeach
            </table>
            @if ($s['overdue_count'] > 5)
                <p style="font-size:12px;color:#6b7280;margin:0 0 6px;">e mais {{ $s['overdue_count'] - 5 }}.</p>
            @endif
            <div style="height:16px;"></div>
        @endif

        @if ($s['upcoming_count'] > 0)
            <p style="font-size:14px;margin:0 0 22px;background:#fdf3dc;color:#92400e;border-radius:10px;padding:12px 14px;">
                <strong>{{ $s['upcoming_count'] }} {{ $s['upcoming_count'] === 1 ? 'parcela vence' : 'parcelas vencem' }}</strong> até {{ $s['upcoming_end']->format('d/m') }}, somando {{ money($s['upcoming_total']) }}.
            </p>
        @endif

        @if ($s['top_products']->isNotEmpty())
            <h2 style="font-size:15px;margin:0 0 8px;color:#111827;">Mais vendidos da semana</h2>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;margin-bottom:22px;">
                @foreach ($s['top_products'] as $item)
                    <tr>
                        <td style="padding:7px 0;border-bottom:1px solid #eef0f4;">{{ $item->description }}</td>
                        <td style="padding:7px 0;border-bottom:1px solid #eef0f4;color:#6b7280;">{{ (int) $item->quantity }} un.</td>
                        <td align="right" style="padding:7px 0;border-bottom:1px solid #eef0f4;font-weight:600;">{{ money($item->total) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if ($s['best_seller'])
            <p style="font-size:14px;margin:0 0 22px;">🏆 Destaque da semana: <strong>{{ $s['best_seller']->user->name }}</strong>, com {{ $s['best_seller']->sales }} {{ $s['best_seller']->sales === 1 ? 'venda' : 'vendas' }} ({{ money($s['best_seller']->revenue) }}).</p>
        @endif

        <table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="background:#155eef;border-radius:10px;">
            <a href="{{ url('/dashboard') }}" style="display:inline-block;padding:12px 22px;color:#ffffff;font-weight:600;font-size:14px;text-decoration:none;">Abrir o Meu Cash</a>
        </td></tr></table>
    </td></tr>

    <tr><td style="background:#ffffff;border-top:1px solid #e5e8ee;border-radius:0 0 14px 14px;padding:16px 28px;font-size:12px;color:#8a93a2;">
        Você recebe este e-mail por ser administrador de {{ $s['company'] }}. Para parar, desative em Configurações &gt; Empresa &gt; Resumo semanal por e-mail.
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
