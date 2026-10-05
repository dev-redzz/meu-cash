@extends('layouts.app')

@section('title', 'Estoque')

@section('actions')
    <a href="{{ route('products.create') }}" class="btn btn-primary">Novo produto</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Unidades em estoque" :value="$totals['units']" hint="Disponível, reservado e manutenção" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Total investido" :value="money($totals['invested'])" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Valor estimado de venda" :value="money($totals['sale_value'])" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Lucro potencial" :value="money($totals['potential'])" tone="positive" /></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach ($byStatus as $value => $info)
        <a href="{{ route('stock.index', ['status' => $value]) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === $value ? 'btn-dark' : 'btn-light' }}">
            {{ $info['status']->label() }} <span class="badge text-bg-{{ $info['status']->color() }} ms-1">{{ $info['units'] }}</span>
        </a>
    @endforeach
</div>

<div class="panel">
    @include('products._filters')
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th class="num">Qtd.</th>
                    <th class="num">Custo un.</th>
                    <th class="num">Preço un.</th>
                    <th class="num">Investido</th>
                    <th class="num">Venda estimada</th>
                    <th class="num">Lucro potencial</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><a href="{{ route('products.show', $product) }}" class="fw-semibold">{{ $product->fullName() }}</a>@if ($product->brand)<div class="small text-muted-2">{{ $product->brand }}</div>@endif</td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td><x-status :value="$product->status" /></td>
                        <td class="num">{{ $product->quantity }}</td>
                        <td class="num">{{ money($product->unitCost()) }}</td>
                        <td class="num">{{ money($product->sale_price) }}</td>
                        <td class="num">{{ money($product->stockInvested()) }}</td>
                        <td class="num">{{ money($product->stockSaleValue()) }}</td>
                        <td class="num text-positive">{{ money($product->stockSaleValue() - $product->stockInvested()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty message="Nenhum produto neste filtro." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
