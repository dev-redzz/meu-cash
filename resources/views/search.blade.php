@extends('layouts.app')

@section('title', 'Buscar')
@section('subtitle', $term !== '' ? ($total === 0 ? 'Nada encontrado para "'.$term.'".' : $total.' '.($total === 1 ? 'resultado' : 'resultados').' para "'.$term.'"') : 'Encontre clientes, produtos e vendas pelo nome, telefone, código ou código de barras.')

@section('content')
<form method="GET" action="{{ route('search') }}" class="mb-4 d-md-none" data-no-lock>
    <div class="input-icon">
        <x-icon name="search" width="18" height="18" />
        <input type="search" name="q" value="{{ $term }}" class="form-control form-control-lg" placeholder="Buscar cliente, produto ou venda" autofocus>
    </div>
</form>

@if ($term !== '' && $total > 0)
    <div class="row g-3">
        @if ($results['customers']->isNotEmpty())
            <div class="col-lg-4">
                <div class="panel h-100">
                    <div class="panel-header"><h2 class="panel-title">Clientes</h2><span class="pill pill-secondary pill-plain">{{ $results['customers']->count() }}</span></div>
                    @foreach ($results['customers'] as $customer)
                        <a href="{{ route('customers.show', $customer) }}" class="list-row list-link">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $customer->name }}</div>
                                <div class="small text-muted-2">{{ $customer->whatsapp ?: ($customer->phone ?: 'Sem telefone') }}</div>
                            </div>
                            <x-icon name="chevron-right" width="16" height="16" class="text-muted-2" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($results['products']->isNotEmpty())
            <div class="col-lg-4">
                <div class="panel h-100">
                    <div class="panel-header"><h2 class="panel-title">Produtos</h2><span class="pill pill-secondary pill-plain">{{ $results['products']->count() }}</span></div>
                    @foreach ($results['products'] as $product)
                        <a href="{{ route('products.show', $product) }}" class="list-row list-link">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $product->fullName() }}</div>
                                <div class="small text-muted-2">{{ $product->quantity }} em estoque · {{ money($product->sale_price) }}</div>
                            </div>
                            <x-icon name="chevron-right" width="16" height="16" class="text-muted-2" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($results['sales']->isNotEmpty())
            <div class="col-lg-4">
                <div class="panel h-100">
                    <div class="panel-header"><h2 class="panel-title">Vendas</h2><span class="pill pill-secondary pill-plain">{{ $results['sales']->count() }}</span></div>
                    @foreach ($results['sales'] as $sale)
                        <a href="{{ route('sales.show', $sale) }}" class="list-row list-link">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $sale->code }} · {{ $sale->customerName() }}</div>
                                <div class="small text-muted-2">{{ $sale->sale_date->format('d/m/Y') }} · {{ money($sale->total) }}</div>
                            </div>
                            <x-status :value="$sale->status" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@elseif ($term !== '')
    <div class="panel"><x-empty message="Tente outro nome, o telefone, o código da venda (ex.: V00012) ou o código de barras." /></div>
@endif
@endsection
