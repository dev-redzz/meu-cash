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
                    <div class="col-12">
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="in_catalog" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="in_catalog" id="in_catalog" value="1" @checked(old('in_catalog', ($product->in_catalog ?? true) ? '1' : '0') === '1')>
                            <label class="form-check-label" for="in_catalog">Mostrar no catálogo online</label>
                        </div>
                    </div>
                    <div class="col-12"><x-textarea name="notes" label="Observações" :value="$product->notes" rows="3" placeholder="Estado, saúde da bateria, IMEI, detalhes..." /></div>
                </div>
            </x-form-section>

            <x-form-section title="Dados fiscais" icon="receipt" accent="amber" description="Usados na nota fiscal (NFC-e). O NCM é obrigatório para emitir; os outros campos podem ficar no padrão. Na dúvida, pergunte ao seu contador.">
                <div class="row gx-3">
                    <div class="col-sm-6 col-xl-3"><x-input name="ncm" label="NCM" :value="$product->ncm" maxlength="10" inputmode="numeric" placeholder="Ex.: 85171300" help="Classificação do produto." /></div>
                    <div class="col-sm-6 col-xl-3"><x-input name="cfop" label="CFOP" :value="$product->cfop" maxlength="4" inputmode="numeric" placeholder="Padrão da loja" /></div>
                    <div class="col-sm-6 col-xl-3"><x-input name="cest" label="CEST" :value="$product->cest" maxlength="9" inputmode="numeric" placeholder="Se tiver" /></div>
                    <div class="col-sm-6 col-xl-3"><x-input name="unit" label="Unidade" :value="$product->unit ?? 'UN'" maxlength="6" placeholder="UN" /></div>
                    <div class="col-md-6"><x-select name="csosn" label="Situação do ICMS (CSOSN)" :options="\App\Services\Fiscal\InvoiceService::CSOSN" :value="$product->csosn" placeholder="Padrão da loja" /></div>
                    <div class="col-md-6"><x-select name="origin" label="Origem" :options="\App\Services\Fiscal\InvoiceService::ORIGIN" :value="$product->origin ?? 0" /></div>
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
                <div class="suggest" id="price-suggest">
                    <div class="suggest-head">
                        <span class="suggest-title"><x-icon name="tag" width="15" height="15" /> Preço sugerido</span>
                        <label class="suggest-markup">
                            lucro de
                            <input type="number" id="suggest_markup" min="0" max="1000" step="1" value="{{ (int) \App\Models\Setting::get('target_markup', 40) }}" aria-label="Lucro desejado sobre o custo, em porcentagem">
                            %
                        </label>
                    </div>
                    <div class="suggest-body">
                        <strong id="suggest-value">—</strong>
                        <button type="button" class="btn btn-sm btn-light" id="suggest-use" disabled>Usar este preço</button>
                    </div>
                    <p class="suggest-note" id="suggest-note">Informe o valor de compra para ver a sugestão.</p>
                </div>
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

    // Preço sugerido: custo por unidade (compra + despesas divididas) + lucro desejado, arredondado para ,90
    const markup = document.getElementById('suggest_markup');
    const out = document.getElementById('suggest-value');
    const note = document.getElementById('suggest-note');
    const use = document.getElementById('suggest-use');
    let suggested = 0;
    function suggest() {
        const q = Math.max(1, initialQty || parseInt(qty.value || '1', 10));
        const cost = parseMoney(purchase.value) + expenses / q;
        const pct = Math.max(0, parseFloat(markup.value || '0'));
        if (cost <= 0) {
            suggested = 0; out.textContent = '—'; use.disabled = true;
            note.textContent = 'Informe o valor de compra para ver a sugestão.';
            note.className = 'suggest-note';
            return;
        }
        const raw = cost * (1 + pct / 100);
        suggested = raw < 10 ? Math.ceil(raw * 10) / 10 : Math.ceil(raw - 0.9) + 0.9;
        out.textContent = brl(suggested);
        use.disabled = false;
        const price = parseMoney(sale.value);
        if (price > 0) {
            const real = (price - cost) / cost * 100;
            note.textContent = price < cost
                ? 'Atenção: o preço atual fica abaixo do custo (' + brl(cost) + ').'
                : 'Com o preço atual, seu lucro é de ' + real.toLocaleString('pt-BR', { maximumFractionDigits: 0 }) + '% sobre o custo de ' + brl(cost) + '.';
            note.className = 'suggest-note ' + (price < cost ? 'is-bad' : (real + 0.5 < pct ? 'is-warn' : 'is-good'));
        } else {
            note.textContent = 'Custo por unidade: ' + brl(cost) + '.';
            note.className = 'suggest-note';
        }
    }
    [purchase, sale, qty, markup].forEach(el => el.addEventListener('input', suggest));
    use.addEventListener('click', () => {
        sale.value = suggested.toFixed(2).replace('.', ',');
        sale.dispatchEvent(new Event('input', { bubbles: true }));
    });
    suggest();
})();
</script>
@endpush
