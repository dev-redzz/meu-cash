@extends('layouts.app')

@section('title', $product->exists ? 'Editar produto' : 'Novo produto')

@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="row g-3">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Dados do produto</h2></div>
            <div class="panel-body">
                <div class="row gx-3">
                    <div class="col-md-8"><x-input name="name" label="Nome" :value="$product->name" required maxlength="255" placeholder="Ex.: iPhone 13 128GB" /></div>
                    <div class="col-md-4">
                        <x-select name="category_id" label="Categoria" :options="$categories->pluck('name', 'id')" :value="$product->category_id" placeholder="Sem categoria" />
                    </div>
                    <div class="col-md-4"><x-input name="brand" label="Marca" :value="$product->brand" maxlength="100" list="brands" /></div>
                    <div class="col-md-4"><x-input name="model" label="Modelo" :value="$product->model" maxlength="100" /></div>
                    <div class="col-md-4"><x-input name="purchase_date" type="date" label="Data da compra" :value="$product->purchase_date?->toDateString()" /></div>
                    <div class="col-md-4"><x-select name="status" label="Status" :options="\App\Enums\ProductStatus::options()" :value="$product->status" required /></div>
                    <div class="col-md-4"><x-input name="quantity" type="number" min="0" label="Quantidade em estoque" :value="$product->quantity ?? 1" required /></div>
                    <div class="col-12"><x-textarea name="notes" label="Observações" :value="$product->notes" placeholder="Estado, saúde da bateria, IMEI, detalhes..." /></div>
                </div>
            </div>
        </div>

        <div class="panel mt-3">
            <div class="panel-header"><h2 class="panel-title">Fotos</h2></div>
            <div class="panel-body">
                @if ($product->exists && $product->photos)
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        @foreach ($product->photos as $path)
                            <label class="text-center small">
                                <img src="{{ asset('storage/'.$path) }}" class="photo-thumb d-block mb-1" alt="Foto do produto">
                                <input type="checkbox" name="remove_photos[]" value="{{ $path }}" class="form-check-input"> remover
                            </label>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror">
                @error('photos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('photos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Até 6 fotos, JPG, PNG ou WEBP, máximo 4 MB cada.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Valores</h2></div>
            <div class="panel-body">
                <x-input name="purchase_price" label="Valor de compra (unitário)" :value="$product->exists ? number_format((float) $product->purchase_price, 2, ',', '.') : ''" inputmode="decimal" placeholder="0,00" required data-calc />
                <x-input name="sale_price" label="Preço de venda (unitário)" :value="$product->exists ? number_format((float) $product->sale_price, 2, ',', '.') : ''" inputmode="decimal" placeholder="0,00" required data-calc />

                <div class="border rounded p-3 small bg-light">
                    <div class="d-flex justify-content-between"><span>Despesas adicionais</span><span class="num">{{ money($product->expenses_total ?? 0) }}</span></div>
                    <div class="d-flex justify-content-between"><span>Total investido</span><span class="num fw-semibold" id="calc-invested">—</span></div>
                    <div class="d-flex justify-content-between"><span>Lucro esperado</span><span class="num fw-semibold" id="calc-profit">—</span></div>
                    @unless ($product->exists)
                        <div class="text-muted-2 mt-2">Despesas como peças e manutenção são adicionadas na página do produto depois de cadastrar.</div>
                    @endunless
                </div>

                @unless ($product->exists)
                    <div class="form-check mt-3">
                        <input type="hidden" name="register_purchase" value="0">
                        <input class="form-check-input" type="checkbox" name="register_purchase" id="register_purchase" value="1" @checked(old('register_purchase', '1') == '1')>
                        <label class="form-check-label" for="register_purchase">Lançar a compra como saída no caixa</label>
                    </div>
                @endunless
            </div>
            <div class="panel-header border-top border-bottom-0 justify-content-end">
                <a href="{{ $product->exists ? route('products.show', $product) : route('products.index') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary">{{ $product->exists ? 'Salvar alterações' : 'Cadastrar produto' }}</button>
            </div>
        </div>
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
        el.className = 'num fw-semibold ' + (profit >= 0 ? 'text-positive' : 'text-negative');
    }
    [purchase, sale, qty].forEach(el => el.addEventListener('input', update));
    update();
})();
</script>
@endpush
