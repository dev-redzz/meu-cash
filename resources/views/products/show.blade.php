@extends('layouts.app')

@section('title', $product->fullName())

@section('actions')
    @if (in_array($product->status->value, ['disponivel', 'reservado']) && $product->quantity > 0)
        <a href="{{ route('sales.create', ['product_id' => $product->id]) }}" class="btn btn-primary">Vender</a>
    @endif
    <a href="{{ route('products.edit', $product) }}" class="btn btn-light">Editar</a>
    @can('admin')
        <form method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Excluir este produto e suas despesas?">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">Excluir</button>
        </form>
    @endcan
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Total investido" :value="money($product->totalInvested())" :hint="'Compra '.money((float) $product->purchase_price * $product->initial_quantity).' + despesas '.money($product->expenses_total)" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Preço de venda" :value="money($product->sale_price)" :hint="$product->initial_quantity > 1 ? 'por unidade · custo un. '.money($product->unitCost()) : null" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Lucro esperado" :value="money($product->expectedProfit())" :tone="$product->expectedProfit() >= 0 ? 'positive' : 'negative'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Em estoque" :value="$product->quantity.' un.'" :hint="'de '.$product->initial_quantity.' compradas'" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="panel mb-3">
            <div class="panel-header"><h2 class="panel-title">Detalhes</h2><x-status :value="$product->status" /></div>
            <div class="panel-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted-2 fw-normal">Categoria</dt><dd class="col-7">{{ $product->category?->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Marca</dt><dd class="col-7">{{ $product->brand ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Modelo</dt><dd class="col-7">{{ $product->model ?: '—' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Data da compra</dt><dd class="col-7">{{ date_br($product->purchase_date) }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Valor de compra</dt><dd class="col-7">{{ money($product->purchase_price) }} / un.</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Despesas</dt><dd class="col-7">{{ money($product->expenses_total) }}</dd>
                </dl>
                @if ($product->notes)
                    <hr><p class="mb-0" style="white-space: pre-line">{{ $product->notes }}</p>
                @endif
            </div>
        </div>
        @if ($product->photos)
            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">Fotos</h2></div>
                <div class="panel-body d-flex flex-wrap gap-2">
                    @foreach ($product->photoUrls() as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" class="photo-thumb" alt="Foto de {{ $product->name }}"></a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-7">
        <div class="panel mb-3">
            <div class="panel-header"><h2 class="panel-title">Despesas do produto</h2></div>
            <form method="POST" action="{{ route('products.expenses.store', $product) }}" class="panel-body border-bottom">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="e_desc">Descrição</label>
                        <input type="text" name="description" id="e_desc" value="{{ old('description') }}" class="form-control form-control-sm @error('description') is-invalid @enderror" required maxlength="255" placeholder="Ex.: troca de tela">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="e_cat">Categoria</label>
                        <select name="category" id="e_cat" class="form-select form-select-sm">
                            @foreach (config('meucash.product_expense_categories') as $key => $label)
                                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="e_amount">Valor</label>
                        <input type="text" name="amount" id="e_amount" value="{{ old('amount') }}" class="form-control form-control-sm @error('amount') is-invalid @enderror" inputmode="decimal" required placeholder="0,00">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="e_date">Data</label>
                        <input type="date" name="date" id="e_date" value="{{ old('date', now()->toDateString()) }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-9">
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control form-control-sm" placeholder="Observação (opcional)" maxlength="2000" aria-label="Observação">
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button class="btn btn-sm btn-primary w-100">Adicionar despesa</button>
                    </div>
                    <div class="col-12">
                        <div class="form-check small">
                            <input type="hidden" name="register_cash" value="0">
                            <input class="form-check-input" type="checkbox" name="register_cash" id="register_cash" value="1" checked>
                            <label class="form-check-label" for="register_cash">Lançar como saída no caixa</label>
                        </div>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th class="num">Valor</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($product->expenses as $expense)
                            <tr>
                                <td>{{ $expense->date->format('d/m/Y') }}</td>
                                <td>{{ $expense->description }}@if ($expense->notes)<div class="small text-muted-2">{{ $expense->notes }}</div>@endif</td>
                                <td>{{ $expense->categoryLabel() }}</td>
                                <td class="num">{{ money($expense->amount) }}</td>
                                <td class="text-end">
                                    @can('admin')
                                        <form method="POST" action="{{ route('products.expenses.destroy', [$product, $expense]) }}" data-confirm="Remover esta despesa?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-link text-danger p-0">Remover</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty message="Nenhuma despesa registrada." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Vendas deste produto</h2></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Data</th><th>Venda</th><th>Cliente</th><th class="num">Qtd.</th><th class="num">Total</th><th class="num">Lucro</th></tr></thead>
                    <tbody>
                        @forelse ($product->saleItems as $item)
                            <tr>
                                <td>{{ $item->sale->sale_date->format('d/m/Y') }}</td>
                                <td><a href="{{ route('sales.show', $item->sale) }}">{{ $item->sale->code }}</a> <x-status :value="$item->sale->status" /></td>
                                <td>{{ $item->sale->customerName() }}</td>
                                <td class="num">{{ $item->quantity }}</td>
                                <td class="num">{{ money($item->total) }}</td>
                                <td class="num">{{ money($item->profit()) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty message="Este produto ainda não foi vendido." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
