@extends('layouts.app')

@section('title', $product->fullName())

@section('subtitle')
    <a href="{{ route('products.index') }}" class="back-link"><x-icon name="arrow-left" width="15" height="15" /> Produtos</a>
@endsection

@section('actions')
    @if (in_array($product->status->value, ['disponivel', 'reservado']) && $product->quantity > 0)
        <a href="{{ route('sales.create', ['product_id' => $product->id]) }}" class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="cart" width="16" height="16" /></span>Vender</a>
    @endif
    <button type="button" class="btn btn-cta btn-cta-light" data-bs-toggle="modal" data-bs-target="#restockModal"><span class="btn-cta-icon"><x-icon name="box" width="16" height="16" /></span>Repor estoque</button>
    <a href="{{ route('products.edit', $product) }}" class="btn btn-cta btn-cta-light"><span class="btn-cta-icon"><x-icon name="pencil" width="16" height="16" /></span>Editar</a>
    @can('admin')
        <form method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Excluir este produto e suas despesas?">
            @csrf @method('DELETE')
            <button class="btn btn-cta btn-cta-light btn-cta-warn"><span class="btn-cta-icon"><x-icon name="trash" width="16" height="16" /></span>Excluir</button>
        </form>
    @endcan
@endsection

@php
    $photos = $product->photoUrls();
    $profit = $product->expectedProfit();
    $margin = $product->totalInvested() > 0 ? $profit / $product->totalInvested() * 100 : null;
    $sold = max(0, $product->initial_quantity - $product->quantity);
    $soldPct = $product->initial_quantity > 0 ? round($sold / $product->initial_quantity * 100) : 0;
    $expenseOpen = $errors->hasAny(['description', 'amount', 'date', 'category']);
@endphp

@section('content')
<section class="panel product-hero mb-3">
    <div class="product-gallery" data-gallery>
        @if ($photos)
            <div class="product-gallery-main">
                @foreach ($photos as $i => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" data-gallery-item @if ($i) hidden @endif>
                        <img src="{{ $url }}" alt="Foto {{ $i + 1 }} de {{ $product->name }}" onerror="this.classList.add('img-missing')">
                    </a>
                @endforeach
            </div>
            @if (count($photos) > 1)
                <div class="product-gallery-thumbs">
                    @foreach ($photos as $i => $url)
                        <button type="button" data-gallery-thumb="{{ $i }}" @class(['active' => $i === 0]) aria-label="Ver foto {{ $i + 1 }}">
                            <img src="{{ $url }}" alt="" onerror="this.classList.add('img-missing')">
                        </button>
                    @endforeach
                </div>
            @endif
        @else
            <a href="{{ route('products.edit', $product) }}" class="product-gallery-empty">
                <x-icon name="image" width="30" height="30" />
                <span>Adicionar fotos</span>
            </a>
        @endif
    </div>

    <div class="product-info">
        <div class="product-info-top">
            <span class="product-card-meta">{{ $product->category?->name ?? 'Sem categoria' }}@if ($product->brand) · {{ $product->brand }}@endif</span>
            <x-status :value="$product->status" />
        </div>
        <div class="product-info-price">
            <span>{{ money($product->sale_price) }}</span>
            @if ($product->initial_quantity > 1)<small>por unidade</small>@endif
        </div>

        <div @class(['stock-meter', 'is-low' => $product->isLowStock()])>
            <div class="d-flex justify-content-between small mb-1">
                <span>
                    <strong>{{ $product->quantity }}</strong> em estoque
                    @if ($product->min_stock !== null)
                        <span class="text-muted-2">· mínimo {{ $product->min_stock }}</span>
                    @endif
                    @if ($product->isLowStock())
                        <span class="pill pill-warning ms-1">Repor</span>
                    @endif
                </span>
                <span class="text-muted-2">{{ $sold }} de {{ $product->initial_quantity }} vendidas</span>
            </div>
            <div class="goal-track goal-track-sm"><div class="goal-fill is-good" style="width: {{ $soldPct }}%"></div></div>
        </div>

        <dl class="spec-grid">
            <div><dt>Marca</dt><dd>{{ $product->brand ?: '—' }}</dd></div>
            <div><dt>Modelo</dt><dd>{{ $product->model ?: '—' }}</dd></div>
            <div><dt>Código de barras</dt><dd>{{ $product->barcode ?: '—' }}</dd></div>
            <div><dt>Data da compra</dt><dd>{{ date_br($product->purchase_date) }}</dd></div>
            <div><dt>Compra por unidade</dt><dd>{{ money($product->purchase_price) }}</dd></div>
            <div><dt>Custo por unidade</dt><dd>{{ money($product->unitCost()) }}</dd></div>
        </dl>

        @if ($product->notes)
            <div class="callout callout-plain mt-3 mb-0">
                <x-icon name="note" width="18" height="18" />
                <div style="white-space: pre-line">{{ $product->notes }}</div>
            </div>
        @endif
    </div>
</section>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><x-stat label="Total investido" icon="arrow-out" accent="amber" :value="money($product->totalInvested())" :hint="'Compra '.money((float) $product->purchase_price * max(1, $product->initial_quantity)).' + despesas '.money($product->expenses_total)" /></div>
    <div class="col-6 col-xl-3"><x-stat label="Faturamento esperado" icon="dollar" accent="blue" :value="money((float) $product->sale_price * max(1, $product->initial_quantity))" :hint="max(1, $product->initial_quantity).' × '.money($product->sale_price)" /></div>
    <div class="col-6 col-xl-3"><x-stat label="Lucro esperado" icon="trend-up" accent="green" :value="money($profit)" :tone="$profit >= 0 ? 'positive' : 'negative'" :hint="$margin !== null ? 'Margem de '.number_format($margin, 1, ',', '.').'% sobre o custo' : null" /></div>
    <div class="col-6 col-xl-3"><x-stat label="Despesas extras" icon="wrench" accent="violet" :value="money($product->expenses_total)" :hint="$product->expenses->count().' '.($product->expenses->count() === 1 ? 'lançamento' : 'lançamentos')" /></div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <section class="panel h-100">
            <header class="form-section-head">
                <span class="stat-icon accent-violet"><x-icon name="wrench" width="18" height="18" /></span>
                <div class="min-w-0">
                    <h2 class="panel-title">Despesas do produto</h2>
                    <p class="form-section-desc">Peças, manutenção, frete e outros custos.</p>
                </div>
                <button type="button" class="btn btn-cta btn-cta-sm ms-auto" data-bs-toggle="collapse" data-bs-target="#expense-form" aria-expanded="{{ $expenseOpen ? 'true' : 'false' }}" aria-controls="expense-form">
                    <span class="btn-cta-icon"><x-icon name="plus" width="14" height="14" /></span>Nova despesa
                </button>
            </header>

            <div @class(['collapse', 'show' => $expenseOpen]) id="expense-form">
                <form method="POST" action="{{ route('products.expenses.store', $product) }}" class="panel-body inset-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="e_desc">Descrição</label>
                            <input type="text" name="description" id="e_desc" value="{{ old('description') }}" class="form-control @error('description') is-invalid @enderror" required maxlength="255" placeholder="Ex.: troca de tela">
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="e_cat">Categoria</label>
                            <select name="category" id="e_cat" class="form-select">
                                @foreach (config('meucash.product_expense_categories') as $key => $label)
                                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="e_amount">Valor</label>
                            <input type="text" name="amount" id="e_amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" inputmode="decimal" required placeholder="0,00">
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="e_date">Data</label>
                            <input type="date" name="date" id="e_date" value="{{ old('date', now()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="e_notes">Observação <span class="text-muted-2 fw-normal">(opcional)</span></label>
                            <input type="text" name="notes" id="e_notes" value="{{ old('notes') }}" class="form-control" maxlength="2000">
                        </div>
                        <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                            <div class="form-check form-switch mb-0 me-auto">
                                <input type="hidden" name="register_cash" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" name="register_cash" id="register_cash" value="1" checked>
                                <label class="form-check-label" for="register_cash">Lançar como saída no caixa</label>
                            </div>
                            <button type="button" class="btn btn-light" data-bs-toggle="collapse" data-bs-target="#expense-form">Cancelar</button>
                            <button class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>Adicionar despesa</button>
                        </div>
                    </div>
                </form>
            </div>

            @if ($product->expenses->isEmpty())
                <x-empty icon="wrench" message="Nenhuma despesa registrada para este produto." />
            @else
                <ul class="entry-list">
                    @foreach ($product->expenses as $expense)
                        <li>
                            <span class="entry-date"><strong>{{ $expense->date->format('d') }}</strong>{{ $expense->date->translatedFormat('M') }}</span>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ $expense->description }}</div>
                                <div class="small text-muted-2 text-truncate">{{ $expense->categoryLabel() }}@if ($expense->notes) · {{ $expense->notes }}@endif</div>
                            </div>
                            <span class="fw-semibold text-negative text-nowrap">− {{ money($expense->amount) }}</span>
                            @can('admin')
                                <form method="POST" action="{{ route('products.expenses.destroy', [$product, $expense]) }}" data-confirm="Remover esta despesa?">
                                    @csrf @method('DELETE')
                                    <button class="icon-btn icon-btn-danger" title="Remover" aria-label="Remover despesa {{ $expense->description }}"><x-icon name="trash" width="15" height="15" /></button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="col-xl-5">
        <section class="panel h-100">
            <header class="form-section-head">
                <span class="stat-icon accent-green"><x-icon name="cart" width="18" height="18" /></span>
                <div class="min-w-0">
                    <h2 class="panel-title">Vendas deste produto</h2>
                    <p class="form-section-desc">{{ $product->saleItems->count() }} {{ $product->saleItems->count() === 1 ? 'venda registrada' : 'vendas registradas' }}</p>
                </div>
            </header>
            @if ($product->saleItems->isEmpty())
                <x-empty icon="cart" message="Este produto ainda não foi vendido." />
            @else
                <ul class="entry-list">
                    @foreach ($product->saleItems as $item)
                        <li>
                            <span class="entry-date"><strong>{{ $item->sale->sale_date->format('d') }}</strong>{{ $item->sale->sale_date->translatedFormat('M') }}</span>
                            <div class="min-w-0 flex-grow-1">
                                <a href="{{ route('sales.show', $item->sale) }}" class="fw-semibold">{{ $item->sale->code }}</a>
                                @if ($item->sale->status->value !== 'concluida') <x-status :value="$item->sale->status" />@endif
                                <div class="small text-muted-2 text-truncate">{{ $item->sale->customerName() }} · {{ $item->quantity }} un.</div>
                            </div>
                            <div class="text-end text-nowrap">
                                <div class="fw-semibold">{{ money($item->total) }}</div>
                                <div class="small {{ $item->profit() >= 0 ? 'text-positive' : 'text-negative' }}">lucro {{ money($item->profit()) }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
<div class="modal fade" id="restockModal" tabindex="-1" aria-labelledby="restockTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('products.restock', $product) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div class="modal-head"><span class="stat-icon accent-green"><x-icon name="box" width="18" height="18" /></span><h5 class="modal-title" id="restockTitle">Repor estoque</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted-2">Some as unidades que chegaram. O custo médio do produto é recalculado sozinho.</p>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label" for="r_qty">Quantidade que chegou</label>
                        <input type="number" name="quantity" id="r_qty" min="1" value="{{ old('quantity', max(1, (int) ($product->min_stock ?? 1))) }}" class="form-control @error('quantity') is-invalid @enderror" required>
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="r_cost">Custo por unidade</label>
                        <input type="text" name="unit_cost" id="r_cost" inputmode="decimal" value="{{ old('unit_cost', number_format((float) $product->purchase_price, 2, ',', '.')) }}" class="form-control @error('unit_cost') is-invalid @enderror" required>
                        @error('unit_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 mt-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="register_cash" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="register_cash" id="r_cash" value="1" checked>
                            <label class="form-check-label" for="r_cash">Lançar a compra como saída no caixa</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>Registrar entrada</button>
            </div>
        </form>
    </div>
</div>
@if ($errors->hasAny(['quantity', 'unit_cost']))
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded', function () { bootstrap.Modal.getOrCreateInstance(document.getElementById('restockModal')).show(); });</script>
    @endpush
@endif
@endsection
