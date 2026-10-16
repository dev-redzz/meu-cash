<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Comprovante {{ $sale->code }}</title>
    @php
        $customer = $sale->customer;
        $received = (float) $sale->payments->sum('amount');
        $open = (float) $sale->installments->filter->isOpen()->sum('amount');
        $cancelled = $sale->status->value === 'cancelada';
        $parcelado = $sale->installments->isNotEmpty();
        $title = $parcelado ? 'Comprovante de venda e confissão de dívida' : 'Comprovante de venda';
        $place = $customer?->city ?: null;
        $dateLong = $sale->sale_date->copy()->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y');
        $statusText = $cancelled ? 'Cancelada' : ($open > 0 ? 'Em aberto' : 'Quitada');
        $statusClass = $cancelled ? 'cancel' : ($open > 0 ? 'open' : 'ok');
        $dash = '—';
    @endphp
    <style>
        @page { margin: 26px 34px 40px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.2px; color: #1f2937; line-height: 1.38; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .muted { color: #6b7280; }
        .strong { font-weight: bold; color: #0e1726; }
        .num { text-align: right; white-space: nowrap; }

        .head { border-bottom: 3px solid #155eef; padding-bottom: 8px; }
        .brand { font-size: 17px; font-weight: bold; color: #0e1726; }
        .doc-title { font-size: 10.5px; font-weight: bold; color: #0e1726; text-transform: uppercase; letter-spacing: .3px; }
        .doc-no { font-size: 15px; font-weight: bold; color: #155eef; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9px; font-weight: bold; font-size: 9px; }
        .ok { background: #e7f6ec; color: #166534; }
        .open { background: #fdf3dc; color: #92400e; }
        .cancel { background: #fde8e6; color: #b42318; }

        .section { margin-top: 9px; }
        .section-title { font-size: 9px; font-weight: bold; color: #155eef; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid #dbe3f3; padding-bottom: 3px; margin-bottom: 6px; }

        .parties td.party { width: 50%; padding: 6px 9px; border: 1px solid #e5e8ee; background: #f9fafc; }
        .parties td.gap { width: 10px; border: 0; background: none; }
        .party-role { font-size: 8.5px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
        .party-name { font-size: 11.5px; font-weight: bold; color: #0e1726; margin: 1px 0 3px; }
        .kv td { padding: 1px 0; }
        .kv td.k { color: #6b7280; width: 70px; }

        table.list th { background: #f2f5fa; text-align: left; padding: 4px 6px; font-size: 8.5px; color: #4b5563; border-bottom: 1px solid #dde1e7; text-transform: uppercase; letter-spacing: .3px; }
        table.list td { padding: 3px 6px; border-bottom: 1px solid #eceff3; }
        table.list th.num { text-align: right; }

        .summary td { padding: 2px 6px; }
        .summary .grand td { font-size: 12px; font-weight: bold; color: #0e1726; border-top: 2px solid #0e1726; padding-top: 6px; }

        .cond td.cell { width: 25%; padding: 5px 8px; border: 1px solid #e5e8ee; }
        .cond .label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; }
        .cond .value { font-size: 11px; font-weight: bold; color: #0e1726; margin-top: 1px; }

        ol.terms { margin: 0; padding-left: 16px; }
        ol.terms li { margin-bottom: 1px; text-align: justify; }

        .access { border: 1.5px dashed #155eef; background: #f3f7ff; padding: 6px 12px; margin-top: 10px; }
        .access td { vertical-align: middle; }
        .keep { page-break-inside: avoid; }
        .access-code { font-size: 17px; font-weight: bold; letter-spacing: 3px; color: #0e1726; }

        .signs { margin-top: 10px; }
        .signs td { width: 50%; text-align: center; padding: 0 18px; }
        .sign-line { border-top: 1px solid #1f2937; padding-top: 4px; margin-top: 28px; }

        .footer { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 8px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 5px; }
        .watermark { position: fixed; top: 38%; left: 0; right: 0; text-align: center; font-size: 70px; color: #fde8e6; transform: rotate(-24deg); z-index: -1; }
    </style>
</head>
<body>
@if ($cancelled)<div class="watermark">CANCELADA</div>@endif

<div class="footer">
    {{ $company->name }} · {{ $sale->code }} · Emitido em {{ now()->format('d/m/Y H:i') }} · Este documento não substitui a nota fiscal
</div>

<table class="head">
    <tr>
        <td>
            <div class="brand">{{ $company->name }}</div>
            <div class="muted">
                @if ($company->document) CPF/CNPJ {{ $company->document }} @endif
                @if ($company->document && $company->phone) &nbsp;·&nbsp; @endif
                @if ($company->phone) Tel./WhatsApp {{ $company->phone }} @endif
            </div>
        </td>
        <td style="text-align: right; width: 62%;">
            <div class="doc-title">{{ $title }}</div>
            <div class="doc-no">Nº {{ $sale->code }}</div>
            <div class="muted">Data da venda: {{ $sale->sale_date->format('d/m/Y') }}</div>
            <div style="margin-top: 3px;"><span class="badge {{ $statusClass }}">{{ mb_strtoupper($statusText) }}</span></div>
        </td>
    </tr>
</table>

<div class="section">
    <div class="section-title">Partes</div>
    <table class="parties">
        <tr>
            <td class="party">
                <div class="party-role">Vendedor</div>
                <div class="party-name">{{ $company->name }}</div>
                <table class="kv">
                    <tr><td class="k">CPF/CNPJ</td><td>{{ $company->document ?: $dash }}</td></tr>
                    <tr><td class="k">Telefone</td><td>{{ $company->phone ?: $dash }}</td></tr>
                    <tr><td class="k">Atendente</td><td>{{ $sale->user?->name ?: $dash }}</td></tr>
                </table>
            </td>
            <td class="gap"></td>
            <td class="party">
                <div class="party-role">Comprador</div>
                <div class="party-name">{{ $sale->customerName() }}</div>
                <table class="kv">
                    <tr><td class="k">CPF/CNPJ</td><td>{{ $customer?->document ?: $dash }}</td></tr>
                    <tr><td class="k">Telefone</td><td>{{ $customer ? ($customer->whatsapp ?: ($customer->phone ?: $dash)) : $dash }}</td></tr>
                    <tr><td class="k">E-mail</td><td>{{ $customer?->email ?: $dash }}</td></tr>
                    <tr><td class="k">Endereço</td><td>{{ $customer?->address ?: $dash }}{{ $customer?->city ? ' · '.$customer->city : '' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Produtos</div>
    <table class="list">
        <thead>
            <tr><th style="width: 52%;">Descrição</th><th class="num">Qtd.</th><th class="num">Valor unitário</th><th class="num">Total</th></tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>
                        <span class="strong">{{ $item->description }}</span>
                        @if ($item->product?->barcode)<br><span class="muted">Código {{ $item->product->barcode }}</span>@endif
                    </td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ money($item->unit_price) }}</td>
                    <td class="num">{{ money($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table style="margin-top: 6px;">
        <tr>
            <td style="width: 55%;"></td>
            <td>
                <table class="summary">
                    <tr><td>Subtotal</td><td class="num">{{ money($sale->subtotal) }}</td></tr>
                    @if ($sale->discount > 0)<tr><td>Desconto</td><td class="num">− {{ money($sale->discount) }}</td></tr>@endif
                    <tr class="grand"><td>Total da compra</td><td class="num">{{ money($sale->total) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Condições de pagamento</div>
    <table class="cond">
        <tr>
            <td class="cell"><div class="label">Forma</div><div class="value">{{ $sale->payment_method->label() }}{{ $sale->installments_count ? ' · '.$sale->installments_count.'x' : '' }}</div></td>
            <td class="cell"><div class="label">Entrada</div><div class="value">{{ money($sale->down_payment) }}</div></td>
            <td class="cell"><div class="label">Total pago</div><div class="value" style="color: #166534;">{{ money($received) }}</div></td>
            <td class="cell"><div class="label">Saldo em aberto</div><div class="value" style="color: {{ $open > 0 ? '#b45309' : '#0e1726' }};">{{ money($open) }}</div></td>
        </tr>
    </table>
</div>

@if ($customer?->access_code)
    <div class="access"><table>
        <tr>
            <td>
                <div class="strong" style="font-size: 10.5px;">Acompanhe suas parcelas pela internet</div>
                <div class="muted">Acesse {{ $portalUrl }} e digite o seu código. Ele é pessoal: não compartilhe.</div>
            </td>
            <td class="num" style="vertical-align: middle;">
                <div class="muted" style="font-size: 8px;">CÓDIGO DE ACESSO</div>
                <div class="access-code">{{ $customer->access_code }}</div>
            </td>
        </tr>
    </table></div>
@endif

@if ($parcelado)
    <div class="section">
        <div class="section-title">Parcelas</div>
        <table class="list">
            <thead><tr><th>Parcela</th><th>Vencimento</th><th class="num">Valor</th><th>Situação</th><th>Pago em</th><th>Forma</th></tr></thead>
            <tbody>
                @foreach ($sale->installments as $installment)
                    @php $late = $installment->isOpen() && $installment->due_date->lt(today()); @endphp
                    <tr>
                        <td class="strong">{{ $installment->label() }}</td>
                        <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                        <td class="num">{{ money($installment->amount) }}</td>
                        <td>{{ $late ? 'Atrasada' : $installment->status->label() }}</td>
                        <td>{{ $installment->paid_at ? $installment->paid_at->format('d/m/Y') : $dash }}</td>
                        <td>{{ $installment->payment_method?->label() ?? $dash }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if ($sale->payments->isNotEmpty())
    <div class="section">
        <div class="section-title">Pagamentos recebidos</div>
        <table class="list">
            <thead><tr><th>Data</th><th>Referente a</th><th>Forma</th><th class="num">Valor</th></tr></thead>
            <tbody>
                @foreach ($sale->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('d/m/Y') }}</td>
                        <td>{{ $payment->installment ? 'Parcela '.$payment->installment->label() : 'Entrada / pagamento à vista' }}</td>
                        <td>{{ $payment->payment_method->label() }}</td>
                        <td class="num">{{ money($payment->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if ($sale->notes)
    <div class="section">
        <div class="section-title">Observações</div>
        <div>{!! nl2br(e($sale->notes)) !!}</div>
    </div>
@endif

@if ($terms->isNotEmpty())
    <div class="section">
        <div class="section-title">Cláusulas</div>
        <ol class="terms">
            @foreach ($terms as $term)
                <li>{{ $term }}</li>
            @endforeach
        </ol>
    </div>
@endif



<div class="keep">
<p style="margin: 12px 0 0;">{{ $place ? $place.', ' : '' }}{{ $dateLong }}.</p>

<table class="signs">
    <tr>
        <td>
            <div class="sign-line">
                <div class="strong">{{ $company->name }}</div>
                <div class="muted">Vendedor{{ $company->document ? ' · '.$company->document : '' }}</div>
            </div>
        </td>
        <td>
            <div class="sign-line">
                <div class="strong">{{ $sale->customerName() }}</div>
                <div class="muted">Comprador{{ $customer?->document ? ' · '.$customer->document : '' }}</div>
            </div>
        </td>
    </tr>
</table>
</div>
</body>
</html>
