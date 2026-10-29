@extends('layouts.app')

@section('title', $product->exists ? 'Editar produto' : 'Novo produto')

@section('subtitle', $product->exists ? 'Atualize os dados, fotos e valores do produto.' : 'Cadastre o produto com os valores de compra e venda para acompanhar o lucro.')

@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="form-page">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="form-grid has-aside">
        <div class="form-main">
            <x-form-section title="Dados do produto" icon="box" description="Nome, categoria e identificação do item.">
                <div class="row gx-3">
                    <div class="col-md-8"><x-input name="name" label="Nome" :value="$product->name" required maxlength="255" placeholder="Ex.: iPhone 13 128GB" autofocus /></div>
                    <div class="col-md-4">
                        <x-select name="category_id" label="Categoria" :options="$categories->pluck('name', 'id')" :value="$product->category_id" placeholder="Sem categoria" />
                    </div>
                    <div class="col-md-4"><x-input name="brand" label="Marca" :value="$product->brand" maxlength="100" list="brands" placeholder="Ex.: Apple" /></div>
                    <div class="col-md-4"><x-input name="model" label="Modelo" :value="$product->model" maxlength="100" placeholder="Ex.: A2633" /></div>
                    <div class="col-md-4"><x-input name="barcode" label="Código de barras" :value="$product->barcode" maxlength="50" placeholder="Bipe ou digite" /></div>
                </div>
            </x-form-section>

            <x-form-section title="Estoque" icon="layers" accent="violet" description="Situação e quantidade disponível para venda.">
                <div class="row gx-3">
                    <div class="col-md-6 col-xl-3"><x-input name="purchase_date" type="date" label="Data da compra" :value="$product->purchase_date?->toDateString()" /></div>
                    <div class="col-md-6 col-xl-3"><x-select name="status" label="Status" :options="\App\Enums\ProductStatus::options()" :value="$product->status" required /></div>
                    <div class="col-6 col-xl-3"><x-input name="quantity" type="number" min="0" label="Quantidade" :value="$product->quantity ?? 1" required /></div>
                    <div class="col-6 col-xl-3"><x-input name="min_stock" type="number" min="0" label="Estoque mínimo" :value="$product->min_stock" placeholder="Ex.: 3" help="Avisamos quando chegar nisso." /></div>
                    <div class="col-12"><x-textarea name="notes" label="Observações" :value="$product->notes" rows="3" placeholder="Estado, saúde da bateria, IMEI, detalhes..." /></div>
                </div>
            </x-form-section>

            <x-form-section title="Fotos" icon="image" accent="green" description="Até 6 fotos, JPG, PNG ou WEBP, com no máximo 4 MB cada.">
                @if ($product->exists && $product->photos)
                    <div class="photo-grid mb-3">
                        @foreach ($product->photos as $path)
                            <label class="photo-tile">
                                <img src="{{ route('photos.show', $path) }}" alt="Foto do produto" onerror="this.classList.add('img-missing')">
                                <span class="photo-tile-remove"><input type="checkbox" name="remove_photos[]" value="{{ $path }}" class="form-check-input"> Remover</span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <label class="dropzone @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror">
                    <span class="stat-icon accent-blue"><x-icon name="image" width="18" height="18" /></span>
                    <span class="dropzone-text"><strong>Escolher fotos</strong><span data-dropzone-label>Clique aqui ou arraste as imagens</span></span>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" data-dropzone>
                </label>
                @error('photos')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('photos.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </x-form-section>
        </div>

        <aside class="form-aside">
            <x-form-section title="Valores" icon="dollar" description="Valores por unidade.">
                <x-input name="purchase_price" label="Valor de compra" :value="$product->exists ? number_format((float) $product->purchase_price, 2, ',', '.') : ''" inputmode="decimal" placeholder="0,00" required data-calc />
                <x-input name="sale_price" label="Preço de venda" :value="$product->exists ? number_format((float) $product->sale_price, 2, ',', '.') : ''" inputmode="decimal" placeholder="0,00" required data-calc />

                <div class="calc-card">
                    <div class="calc-row"><span>Despesas adicionais</span><span class="num">{{ money($product->expenses_total ?? 0) }}</span></div>
                    <div class="calc-row"><span>Total investido</span><span class="num fw-semibold" id="calc-invested">—</span></div>
                    <div class="calc-row calc-row-total"><span>Lucro esperado</span><span class="num" id="calc-profit">—</span></div>
                </div>
                @unless ($product->exists)
                    <p class="form-text mt-2 mb-0">Despesas como peças e manutenção são adicionadas na página do produto depois de cadastrar.</p>
                    <div class="form-check form-switch mt-3">
                        <input type="hidden" name="register_purchase" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="register_purchase" id="register_purchase" value="1" @checked(old('register_purchase', '1') == '1')>
                        <label class="form-check-label" for="register_purchase">Lançar a compra como saída no caixa</label>
                    </div>
                @endunless

                <div class="aside-actions">
                    <button type="submit" class="btn btn-cta w-100"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>{{ $product->exists ? 'Salvar alterações' : 'Cadastrar produto' }}</button>
                    <a href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}" class="btn btn-light w-100">Cancelar</a>
                </div>
            </x-form-section>
        </aside>
    </div>

</form>
<datalist id="brands">
    @foreach (\App\Models\Product::whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand') as $brand)
        <option value="{{ $brand }}">
    @endforeach
</datalist>
@endsection

@push('scripts')
<script>
(function () {
    const expenses = {{ (float) ($product->expenses_total ?? 0) }};
    const purchase = document.getElementById('purchase_price');
    const sale = document.getElementById('sale_price');
    const qty = document.getElementById('quantity');
    const initialQty = {{ (int) ($product->initial_quantity ?? 0) }};
    function update() {
        const q = Math.max(1, initialQty || parseInt(qty.value || '1', 10));
        const invested = parseMoney(purchase.value) * q + expenses;
        const profit = parseMoney(sale.value) * q - invested;
        document.getElementById('calc-invested').textContent = brl(invested);
        const el = document.getElementById('calc-profit');
        el.textContent = brl(profit);
        el.className = 'num ' + (profit >= 0 ? 'text-positive' : 'text-negative');
    }
    [purchase, sale, qty].forEach(el => el.addEventListener('input', update));
    update();
})();
</script>
@endpush
