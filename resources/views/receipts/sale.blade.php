<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Recibo {{ $sale->code }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0; color: #0d1b33; }
        .muted { color: #6b7280; }
        .head { width: 100%; border-bottom: 3px solid #155eef; padding-bottom: 12px; margin-bottom: 16px; }
        .head td { vertical-align: top; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 10px; font-weight: bold; font-size: 10px; }
        .ok { background: #e7f6ec; color: #166534; }
        .open { background: #fdf3dc; color: #92400e; }
        .cancel { background: #fde8e6; color: #b42318; }
        .box { width: 100%; margin-bottom: 14px; }
        .box td { vertical-align: top; width: 50%; }
        h2 { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin: 18px 0 6px; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { background: #f2f4f7; text-align: left; padding: 6px; font-size: 9.5px; color: #4b5563; border-bottom: 1px solid #dde1e7; }
        table.list td { padding: 6px; border-bottom: 1px solid #eceff3; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 45%; margin-left: 55%; margin-top: 8px; border-collapse: collapse; }
        .totals td { padding: 4px 6px; }
        .totals .grand td { font-size: 13px; font-weight: bold; border-top: 2px solid #0d1b33; padding-top: 7px; }
        .footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    @php
        $received = $sale->payments->sum('amount');
        $open = $sale->installments->filter->isOpen()->sum('amount');
    @endphp
    <table class="head">
        <tr>
            <td>
                <h1>{{ $company->name }}</h1>
                <div class="muted">
                    @if ($company->document) CPF/CNPJ {{ $company->document }} @endif
                    @if ($company->document && $company->phone) · @endif
                    @if ($company->phone) {{ $company->phone }} @endif
                </div>
            </td>
            <td style="text-align: right;">
                <div style="font-size: 14px; font-weight: bold;">RECIBO DE VENDA</div>
                <div>{{ $sale->code }} · {{ $sale->sale_date->format('d/m/Y') }}</div>
                <div style="margin-top: 5px;">
                    @if ($sale->status->value === 'cancelada')
                        <span class="badge cancel">CANCELADA</span>
                    @elseif ($open > 0)
                        <span class="badge open">EM ABERTO</span>
                    @else
                        <span class="badge ok">QUITADA</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="box">
        <tr>
            <td>
                <h2 style="margin-top: 0;">Cliente</h2>
                <strong>{{ $sale->customerName() }}</strong><br>
                @if ($sale->customer?->document) {{ $sale->customer->document }}<br> @endif
                @if ($sale->customer?->whatsapp || $sale->customer?->phone) {{ $sale->customer->whatsapp ?: $sale->customer->phone }}<br> @endif
                @if ($sale->customer?->address) {{ $sale->customer->address }}{{ $sale->customer->city ? ' · '.$sale->customer->city : '' }} @endif
            </td>
            <td>
                <h2 style="margin-top: 0;">Pagamento</h2>
                {{ $sale->payment_method->label() }}
                @if ($sale->installments_count) · {{ $sale->installments_count }}x @endif<br>
                @if ($sale->user) Vendedor: {{ $sale->user->name }} @endif
            </td>
        </tr>
    </table>

    <h2>Itens</h2>
    <table class="list">
        <thead><tr><th>Descrição</th><th class="num">Qtd.</th><th class="num">Preço un.</th><th class="num">Total</th></tr></thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ money($item->unit_price) }}</td>
                    <td class="num">{{ money($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">{{ money($sale->subtotal) }}</td></tr>
        @if ($sale->discount > 0)<tr><td>Desconto</td><td class="num">− {{ money($sale->discount) }}</td></tr>@endif
        <tr class="grand"><td>Total</td><td class="num">{{ money($sale->total) }}</td></tr>
        <tr><td>Pago</td><td class="num">{{ money($received) }}</td></tr>
        @if ($open > 0)<tr><td>Em aberto</td><td class="num">{{ money($open) }}</td></tr>@endif
    </table>

    @if ($sale->installments->isNotEmpty())
        <h2>Parcelas</h2>
        <table class="list">
            <thead><tr><th>Parcela</th><th>Vencimento</th><th>Situação</th><th>Pago em</th><th class="num">Valor</th></tr></thead>
            <tbody>
                @foreach ($sale->installments as $installment)
                    <tr>
                        <td>{{ $installment->label() }}</td>
                        <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                        <td>{{ $installment->status->label() }}</td>
                        <td>{{ date_br($installment->paid_at) }}</td>
                        <td class="num">{{ money($installment->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($sale->notes)
        <h2>Observações</h2>
        <div>{{ $sale->notes }}</div>
    @endif

    <div class="footer">Emitido em {{ now()->format('d/m/Y H:i') }} · Este documento não substitui nota fiscal · Gerado pelo Meu Cash</div>
</body>
</html>
