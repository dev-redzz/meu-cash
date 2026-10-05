@extends('layouts.app')

@section('title', 'Produtos')

@section('actions')
    <a href="{{ route('categories.index') }}" class="btn btn-light">Categorias</a>
    <a href="{{ route('products.create') }}" class="btn btn-primary">Novo produto</a>
@endsection

@section('content')
<div class="panel">
    @include('products._filters')
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Compra</th>
                    <th>Status</th>
                    <th class="num">Qtd.</th>
                    <th class="num">Total investido</th>
                    <th class="num">Preço de venda</th>
                    <th class="num">Lucro esperado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $product) }}" class="fw-semibold">{{ $product->fullName() }}</a>
                            @if ($product->brand)<div class="small text-muted-2">{{ $product->brand }}</div>@endif
                        </td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td>{{ date_br($product->purchase_date) }}</td>
                        <td><x-status :value="$product->status" /></td>
                        <td class="num">{{ $product->quantity }}</td>
                        <td class="num">{{ money($product->totalInvested()) }}</td>
                        <td class="num">{{ money($product->sale_price) }}</td>
                        <td class="num {{ $product->expectedProfit() >= 0 ? 'text-positive' : 'text-negative' }}">{{ money($product->expectedProfit()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty message="Nenhum produto encontrado." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $products->links() }}</div>
@endsection
