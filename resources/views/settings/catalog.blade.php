@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
@php $url = route('catalog.show', $slug); @endphp
<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('settings.catalog.update') }}" class="panel">
            @csrf @method('PUT')
            <header class="form-section-head">
                <span class="stat-icon accent-{{ $s['enabled'] ? 'green' : 'blue' }}"><x-icon name="store" width="18" height="18" /></span>
                <div class="min-w-0">
                    <h2 class="panel-title">Catálogo online</h2>
                    <p class="form-section-desc">Uma página com seus produtos em estoque, fotos e preços. O cliente monta o pedido e envia pelo seu WhatsApp.</p>
                </div>
            </header>
            <div class="panel-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="enabled" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="enabled" id="enabled" value="1" @checked(old('enabled', $s['enabled'] ? '1' : '0') === '1')>
                    <label class="form-check-label fw-semibold" for="enabled">Catálogo publicado</label>
                </div>

                <label class="form-label" for="slug">Endereço do catálogo</label>
                <div class="input-group mb-1">
                    <span class="input-group-text small">{{ preg_replace('#^https?://#', '', rtrim(url('/loja'), '/')) }}/</span>
                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $slug) }}" maxlength="60" required>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-text mb-3">Só letras, números e hífen. Ex.: boutique-da-ana</div>

                <x-input name="whatsapp" label="WhatsApp que recebe os pedidos" :value="$s['whatsapp']" maxlength="20" placeholder="(99) 99999-9999" required />
                <x-textarea name="intro" label="Recado no topo do catálogo" :value="$s['intro']" rows="2" maxlength="300" placeholder="Ex.: Entregamos em toda a cidade. Parcelamos no crediário!" />

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="show_stock" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="show_stock" id="show_stock" value="1" @checked(old('show_stock', $s['show_stock'] ? '1' : '0') === '1')>
                    <label class="form-check-label" for="show_stock">Mostrar quantas unidades tem de cada produto</label>
                </div>

                <button class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Divulgar</h2></div>
            <div class="panel-body">
                @if ($s['enabled'] && $s['slug'])
                    <div class="catalog-link">
                        <input type="text" class="form-control" value="{{ $url }}" readonly id="catalog-url">
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button type="button" class="btn btn-sm btn-primary" data-copy="#catalog-url"><x-icon name="copy" width="14" height="14" /> Copiar link</button>
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><x-icon name="external" width="14" height="14" /> Abrir</a>
                            <a href="https://wa.me/?text={{ rawurlencode('Confira nossos produtos: '.$url) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><x-icon name="whatsapp" width="14" height="14" /> Mandar no WhatsApp</a>
                        </div>
                    </div>
                    <p class="small text-muted-2 mt-3 mb-0">Coloque o link na bio do Instagram e no status do WhatsApp.</p>
                @else
                    <p class="small text-muted-2 mb-0">Ligue o catálogo e salve para gerar o link.</p>
                @endif
            </div>
        </section>
        <section class="panel mt-3">
            <div class="panel-header"><h2 class="panel-title">O que aparece</h2></div>
            <div class="panel-body">
                <ul class="howto mb-0">
                    <li><strong>{{ $count }}</strong> produto(s) em estoque com preço.</li>
                    @if ($withoutPhoto)<li><strong>{{ $withoutPhoto }}</strong> sem foto. Produtos com foto vendem mais.</li>@endif
                    @if ($hidden)<li><strong>{{ $hidden }}</strong> escondido(s) do catálogo.</li>@endif
                    <li>Produto esgotado sai do catálogo sozinho. Para esconder um produto, desmarque <strong>Mostrar no catálogo online</strong> no cadastro dele.</li>
                    <li>O custo, o lucro e as observações do produto nunca aparecem.</li>
                </ul>
            </div>
        </section>
    </div>
</div>
<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.querySelector(btn.getAttribute('data-copy'));
        var done = function () { var t = btn.innerHTML; btn.textContent = 'Link copiado!'; setTimeout(function () { btn.innerHTML = t; }, 2000); };
        if (navigator.clipboard) navigator.clipboard.writeText(input.value).then(done, function () { input.select(); document.execCommand('copy'); done(); });
        else { input.select(); document.execCommand('copy'); done(); }
    });
});
</script>
@endsection
