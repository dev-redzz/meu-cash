@extends('layouts.app')

@section('title', 'Etiquetas')

@section('subtitle', 'Escolha os produtos e quantas etiquetas de cada. Produto sem código de barras ganha um código da loja na hora.')

@section('content')
<form method="GET" action="{{ route('labels.print') }}" target="_blank" class="row g-3" data-labels>
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-toolbar">
                <div class="pdv-search flex-grow-1">
                    <x-icon name="search" width="18" height="18" />
                    <input type="search" class="form-control" placeholder="Filtrar por nome ou código" data-labels-filter autocomplete="off">
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-light" data-labels-fill="stock">Quantidade = estoque</button>
                    <button type="button" class="btn btn-sm btn-light" data-labels-fill="one">1 de cada</button>
                    <button type="button" class="btn btn-sm btn-light" data-labels-fill="zero">Zerar</button>
                </div>
            </div>
            <div class="table-responsive labels-table">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Produto</th><th>Código</th><th class="num">Preço</th><th class="num">Estoque</th><th class="num">Etiquetas</th></tr></thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php $pre = in_array($product->id, $selected, true) ? max(1, min(100, $product->quantity)) : 0; @endphp
                            <tr data-label-row data-search="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($product->fullName().' '.$product->barcode)) }}" data-stock="{{ max(0, $product->quantity) }}">
                                <td class="fw-semibold">{{ $product->fullName() }}</td>
                                <td class="small">{!! $product->barcode ? e($product->barcode) : '<span class="text-muted-2">será gerado</span>' !!}</td>
                                <td class="num">{{ money($product->sale_price) }}</td>
                                <td class="num">{{ $product->quantity }}</td>
                                <td class="num"><input type="number" min="0" max="500" name="qty[{{ $product->id }}]" value="{{ $pre }}" class="form-control form-control-sm labels-qty" aria-label="Etiquetas de {{ $product->fullName() }}"></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty message="Cadastre produtos para imprimir etiquetas." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <aside class="panel labels-aside">
            <div class="panel-body">
                <x-select name="layout" label="Modelo da etiqueta" :options="collect($layouts)->map(fn ($l) => $l['label'])->all()" :value="$layout" />
                <div class="form-check mb-2">
                    <input type="hidden" name="price" value="0">
                    <input class="form-check-input" type="checkbox" name="price" value="1" id="lp" checked>
                    <label class="form-check-label" for="lp">Mostrar o preço</label>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="store" value="0">
                    <input class="form-check-input" type="checkbox" name="store" value="1" id="ls" checked>
                    <label class="form-check-label" for="ls">Mostrar o nome da loja</label>
                </div>
                <div class="labels-count"><strong data-labels-total>0</strong> etiquetas</div>
                <button class="btn btn-cta w-100 justify-content-start mt-2" data-labels-submit><span class="btn-cta-icon"><x-icon name="printer" width="16" height="16" /></span>Gerar etiquetas</button>
                <p class="small text-muted-2 mt-3 mb-0">Abre uma página pronta para imprimir. Na impressora térmica, escolha o tamanho do papel igual ao da etiqueta e margens "Nenhuma".</p>
            </div>
        </aside>
    </div>
</form>

<script>
(function () {
    var root = document.querySelector('[data-labels]');
    if (!root) return;
    var rows = Array.prototype.slice.call(root.querySelectorAll('[data-label-row]'));
    var total = root.querySelector('[data-labels-total]');
    var submit = root.querySelector('[data-labels-submit]');
    var visible = function (r) { return !r.hidden; };
    var sum = function () {
        var n = rows.reduce(function (s, r) { return s + (parseInt(r.querySelector('input').value, 10) || 0); }, 0);
        total.textContent = n;
        submit.disabled = n === 0;
    };
    root.addEventListener('input', sum);
    var norm = function (s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); };
    root.querySelector('[data-labels-filter]').addEventListener('input', function (e) {
        var q = norm(e.target.value.trim());
        rows.forEach(function (r) { r.hidden = q && r.getAttribute('data-search').indexOf(q) === -1; });
    });
    root.querySelectorAll('[data-labels-fill]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var mode = btn.getAttribute('data-labels-fill');
            rows.filter(visible).forEach(function (r) {
                var input = r.querySelector('input');
                input.value = mode === 'zero' ? 0 : mode === 'one' ? 1 : Math.min(500, parseInt(r.getAttribute('data-stock'), 10) || 0);
            });
            sum();
        });
    });
    root.addEventListener('submit', function () {
        rows.forEach(function (r) { var i = r.querySelector('input'); i.disabled = !(parseInt(i.value, 10) > 0); });
        setTimeout(function () { rows.forEach(function (r) { r.querySelector('input').disabled = false; }); }, 50);
    });
    sum();
})();
</script>
@endsection
