<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiquetas · {{ $store }}</title>
    @php
        $w = $layout['w']; $h = $layout['h']; $cols = $layout['cols']; $sheet = $layout['sheet'] ?? false;
        $small = $h < 26;
    @endphp
    <style>
        @page { size: {{ $sheet ? 'A4' : ($w * $cols + ($cols - 1) * 2).'mm '.$h.'mm' }}; margin: {{ $sheet ? '10.7mm 7.2mm' : '0' }}; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; background: #e9edf3; }
        .bar { position: sticky; top: 0; z-index: 2; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding: 12px 16px; background: #0b1426; color: #fff; font-size: 14px; }
        .bar button { border: 0; border-radius: 999px; padding: 9px 18px; font-weight: 700; font-size: 14px; background: #2563eb; color: #fff; cursor: pointer; }
        .bar span { opacity: .8; }
        .sheet { display: grid; grid-template-columns: repeat({{ $cols }}, {{ $w }}mm); gap: {{ $sheet ? '0 2.5mm' : '0 2mm' }}; justify-content: start; padding: 16px; }
        .label { width: {{ $w }}mm; height: {{ $h }}mm; overflow: hidden; background: #fff; padding: {{ $small ? '1mm 1.5mm' : '1.8mm 2.4mm' }}; display: flex; flex-direction: column; justify-content: space-between; page-break-inside: avoid; break-inside: avoid; outline: 1px dashed #c7cfdb; }
        .store { font-size: {{ $small ? '5.5pt' : '6.5pt' }}; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .name { font-size: {{ $small ? '6.5pt' : '8pt' }}; font-weight: 700; line-height: 1.12; max-height: 2.3em; overflow: hidden; }
        .top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5mm; }
        .min { min-width: 0; flex: 1; }
        .price { font-size: {{ $small ? '8.5pt' : ($h > 35 ? '15pt' : '11.5pt') }}; font-weight: 800; white-space: nowrap; letter-spacing: -.02em; line-height: 1; }
        .code { text-align: center; }
        .barcode { display: block; width: 100%; height: {{ $small ? '6mm' : ($h > 35 ? '12mm' : '9mm') }}; }
        .digits { font-size: {{ $small ? '5pt' : '6pt' }}; letter-spacing: .08em; margin-top: .3mm; }
        @media print {
            body { background: #fff; }
            .bar { display: none; }
            .sheet { padding: 0; }
            .label { outline: none; }
        }
    </style>
</head>
<body>
<div class="bar">
    <button type="button" onclick="window.print()">Imprimir {{ count($labels) }} etiquetas</button>
    <span>{{ $layout['label'] }}</span>
    @if ($generated)<span>· {{ $generated }} produto(s) ganharam código de barras da loja</span>@endif
</div>
<div class="sheet">
    @foreach ($labels as $label)
        @php $p = $label['product']; @endphp
        <div class="label">
            <div class="top">
                <div class="min">
                    @if ($showStore)<div class="store">{{ $store }}</div>@endif
                    <div class="name">{{ $p->fullName() }}</div>
                </div>
                @if ($showPrice)<div class="price">{{ money($p->sale_price) }}</div>@endif
            </div>
            <div class="code">{!! $label['svg'] !!}<div class="digits">{{ $p->barcode }}</div></div>
        </div>
    @endforeach
</div>
</body>
</html>
