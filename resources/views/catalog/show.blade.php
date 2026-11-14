<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.theme-head')
    <title>{{ $company->name }} · Catálogo</title>
    <meta name="description" content="{{ $settings['intro'] ?: 'Veja os produtos de '.$company->name.' e faça seu pedido pelo WhatsApp.' }}">
    <meta property="og:title" content="{{ $company->name }}">
    <meta property="og:description" content="{{ $settings['intro'] ?: 'Veja os produtos e faça seu pedido pelo WhatsApp.' }}">
    @php $cover = collect($products)->first(fn ($p) => $p['photos'])['photos'][0] ?? null; @endphp
    @if ($cover)<meta property="og:image" content="{{ $cover }}">@endif
    <meta name="theme-color" content="#0b1426">
    @include('partials.head-assets')
    <link href="{{ asset('css/catalog.css') }}?v=2" rel="stylesheet">
</head>
<body class="shop-body">
<header class="shop-header">
    <div class="shop-wrap shop-header-inner">
        <div class="shop-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}</div>
        <div class="min-w-0">
            <h1 class="shop-title">{{ $company->name }}</h1>
            @if ($settings['intro'])<p class="shop-intro">{{ $settings['intro'] }}</p>@endif
        </div>
        @if ($whatsapp)
            <a class="shop-wa" href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Olá! Vi o catálogo de '.$company->name.' e queria tirar uma dúvida.') }}" target="_blank" rel="noopener" aria-label="Falar no WhatsApp"><x-icon name="whatsapp" width="20" height="20" /></a>
        @endif
    </div>
</header>

<main class="shop-wrap shop-main" data-catalog data-store="{{ $company->name }}" data-wa="{{ $whatsapp }}" data-key="mc-cart-{{ $company->catalog_slug }}" data-stock="{{ $settings['show_stock'] ? 1 : 0 }}">
    <div class="shop-tools">
        <div class="shop-search">
            <x-icon name="search" width="18" height="18" />
            <input type="search" placeholder="Buscar produto" data-shop-search aria-label="Buscar produto" autocomplete="off">
        </div>
        @if ($categories->count() > 1)
            <nav class="shop-chips" aria-label="Categorias">
                <button type="button" class="shop-chip active" data-shop-filter="">Tudo</button>
                @foreach ($categories as $category)
                    <button type="button" class="shop-chip" data-shop-filter="{{ $category }}">{{ $category }}</button>
                @endforeach
            </nav>
        @endif
    </div>

    <div class="shop-grid" data-shop-grid></div>
    <p class="shop-empty" data-shop-empty hidden>Nenhum produto encontrado.</p>
</main>

<div class="shop-bar" data-shop-bar hidden>
    <button type="button" class="shop-bar-btn" data-shop-open>
        <span class="shop-bar-count" data-shop-count>0</span>
        <span>Ver pedido</span>
        <strong data-shop-total>R$ 0,00</strong>
    </button>
</div>

<div class="shop-sheet" data-shop-sheet hidden role="dialog" aria-modal="true" aria-labelledby="shop-sheet-title">
    <div class="shop-sheet-backdrop" data-shop-close></div>
    <div class="shop-sheet-panel">
        <div class="shop-sheet-head">
            <h2 id="shop-sheet-title">Seu pedido</h2>
            <button type="button" class="shop-x" data-shop-close aria-label="Fechar">×</button>
        </div>
        <div class="shop-sheet-body" data-shop-items></div>
        <div class="shop-sheet-foot">
            <label class="form-label small fw-semibold" for="shop-name">Seu nome</label>
            <input type="text" id="shop-name" class="form-control mb-2" maxlength="60" placeholder="Para a loja saber quem é" autocomplete="name">
            <label class="form-label small fw-semibold" for="shop-note">Observação</label>
            <input type="text" id="shop-note" class="form-control mb-3" maxlength="200" placeholder="Ex.: tamanho, cor, entrega ou retirada">
            <div class="shop-sum"><span>Total</span><strong data-shop-total>R$ 0,00</strong></div>
            <button type="button" class="shop-send" data-shop-send @disabled(! $whatsapp)><x-icon name="whatsapp" width="18" height="18" /> Enviar pedido pelo WhatsApp</button>
            @unless ($whatsapp)<p class="small text-muted-2 mt-2 mb-0">A loja ainda não informou o WhatsApp.</p>@endunless
        </div>
    </div>
</div>

<div class="shop-sheet" data-shop-detail hidden role="dialog" aria-modal="true">
    <div class="shop-sheet-backdrop" data-shop-close></div>
    <div class="shop-sheet-panel shop-detail" data-shop-detail-body></div>
</div>

<footer class="shop-foot">
    <span>Catálogo feito com</span> <a href="{{ route('home') }}" target="_blank" rel="noopener">Meu Cash</a>
</footer>

<script>window.CATALOG = @json($products);</script>
<script src="{{ asset('js/catalog.js') }}?v=2" defer></script>
</body>
</html>
