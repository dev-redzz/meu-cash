@extends('layouts.app')

@section('title', 'Produtos')

@section('subtitle', 'Cadastre, acompanhe o lucro e controle o estoque dos seus produtos.')

@section('actions')
    <a href="{{ route('categories.index') }}" class="btn btn-cta btn-cta-light"><span class="btn-cta-icon"><x-icon name="tag" width="16" height="16" /></span>Categorias</a>
    <a href="{{ route('products.import.create') }}" class="btn btn-cta btn-cta-light"><span class="btn-cta-icon"><x-icon name="upload" width="16" height="16" /></span>Importar</a>
    <a href="{{ route('products.create') }}" class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="plus" width="16" height="16" /></span>Novo produto</a>
@endsection

@php
    $statusValue = $filters['status'] ?? '';
    $baseQuery = collect(request()->query())->except(['status', 'page'])->all();
    $allCount = $statusCounts->sum();
    $viewUrl = fn ($v) => request()->fullUrlWithQuery(['view' => $v === 'lista' ? null : $v, 'page' => null]);
@endphp

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><x-stat label="Produtos cadastrados" icon="box" accent="blue" :value="$stats['products']" /></div>
    <div class="col-6 col-xl-3"><x-stat label="Unidades em estoque" icon="layers" accent="violet" :value="$stats['units']" hint="Disponível, reservado e manutenção" /></div>
    <div class="col-6 col-xl-3"><x-stat label="Investido no estoque" icon="arrow-out" accent="amber" :value="money($stats['invested'])" /></div>
    <div class="col-6 col-xl-3"><x-stat label="Lucro potencial" icon="trend-up" accent="green" :value="money($stats['sale_value'] - $stats['invested'])" :hint="'Venda estimada '.money($stats['sale_value'])" :tone="$stats['sale_value'] - $stats['invested'] >= 0 ? 'positive' : 'negative'" /></div>
</div>

<div class="panel">
    <div class="panel-toolbar">
        <nav class="status-chips" aria-label="Filtrar por status">
            <a href="{{ route('products.index', $baseQuery) }}" @class(['status-chip', 'active' => $statusValue === ''])>Todos <span>{{ $allCount }}</span></a>
            @foreach (\App\Enums\ProductStatus::cases() as $status)
                <a href="{{ route('products.index', $baseQuery + ['status' => $status->value]) }}" @class(['status-chip', 'dot-'.$status->color(), 'active' => $statusValue === $status->value])>
                    {{ $status->label() }} <span>{{ $statusCounts[$status->value] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>
        <div class="view-toggle" role="group" aria-label="Modo de exibição">
            <a href="{{ $viewUrl('lista') }}" @class(['active' => $view === 'lista']) aria-label="Lista" title="Lista"><x-icon name="list" width="17" height="17" /></a>
            <a href="{{ $viewUrl('grade') }}" @class(['active' => $view === 'grade']) aria-label="Grade" title="Grade"><x-icon name="grid" width="17" height="17" /></a>
        </div>
    </div>

    @include('products._filters', ['withStatus' => false])

    @if ($products->isEmpty())
        <x-empty icon="box" message="{{ $filters ? 'Nenhum produto encontrado com esses filtros.' : 'Você ainda não cadastrou produtos.' }}">
            @unless ($filters)
                <a href="{{ route('products.create') }}" class="btn btn-cta mt-3"><span class="btn-cta-icon"><x-icon name="plus" width="16" height="16" /></span>Cadastrar o primeiro produto</a>
            @endunless
        </x-empty>
    @elseif ($view === 'grade')
        <div class="product-grid">
            @foreach ($products as $product)
                @php $photo = $product->photoUrls()[0] ?? null; $profit = $product->expectedProfit(); @endphp
                <a href="{{ route('products.show', $product) }}" class="product-card">
                    <div class="product-card-media">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'product-card-noimg'}))">
                        @else
                            <span class="product-card-noimg"><x-icon name="image" width="28" height="28" /></span>
                        @endif
                        <x-status :value="$product->status" class="product-card-status" />
                    </div>
                    <div class="product-card-body">
                        <div class="product-card-meta">{{ $product->category?->name ?? 'Sem categoria' }}@if ($product->brand) · {{ $product->brand }}@endif</div>
                        <div class="product-card-name">{{ $product->fullName() }}</div>
                        <div class="product-card-price">{{ money($product->sale_price) }}</div>
                        <div class="product-card-foot">
                            <span>{{ $product->quantity }} {{ $product->quantity === 1 ? 'unidade' : 'unidades' }}</span>
                            <span class="{{ $profit >= 0 ? 'text-positive' : 'text-negative' }}">{{ $profit >= 0 ? '+' : '' }}{{ money($profit) }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle product-table">
                <thead>
                    <tr>
                        <th data-stack-title>Produto</th>
                        <th>Status</th>
                        <th class="num">Estoque</th>
                        <th class="num">Investido</th>
                        <th class="num">Preço de venda</th>
                        <th class="num">Lucro esperado</th>
                        <th><span class="visually-hidden">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        @php $photo = $product->photoUrls()[0] ?? null; $profit = $product->expectedProfit(); @endphp
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $product) }}" class="product-cell">
                                    @if ($photo)
                                        <img src="{{ $photo }}" class="thumb" alt="" loading="lazy" onerror="this.classList.add('img-missing')">
                                    @else
                                        <span class="thumb thumb-empty"><x-icon name="image" width="18" height="18" /></span>
                                    @endif
                                    <span class="min-w-0">
                                        <span class="product-cell-name">{{ $product->fullName() }}</span>
                                        <span class="product-cell-meta">{{ $product->category?->name ?? 'Sem categoria' }}@if ($product->brand) · {{ $product->brand }}@endif · {{ date_br($product->purchase_date) }}</span>
                                    </span>
                                </a>
                            </td>
                            <td><x-status :value="$product->status" /></td>
                            <td class="num"><span @class(['qty-badge', 'is-empty' => $product->quantity < 1])>{{ $product->quantity }} un.</span></td>
                            <td class="num">{{ money($product->totalInvested()) }}</td>
                            <td class="num fw-semibold">{{ money($product->sale_price) }}</td>
                            <td class="num {{ $profit >= 0 ? 'text-positive' : 'text-negative' }}">{{ money($profit) }}</td>
                            <td class="text-end">
                                <div class="row-actions">
                                    @if (in_array($product->status->value, ['disponivel', 'reservado']) && $product->quantity > 0)
                                        <a href="{{ route('sales.create', ['product_id' => $product->id]) }}" class="icon-btn" title="Vender" aria-label="Vender {{ $product->name }}"><x-icon name="cart" width="16" height="16" /></a>
                                    @endif
                                    <a href="{{ route('products.edit', $product) }}" class="icon-btn" title="Editar" aria-label="Editar {{ $product->name }}"><x-icon name="pencil" width="16" height="16" /></a>
                                    <a href="{{ route('products.show', $product) }}" class="icon-btn" title="Abrir" aria-label="Abrir {{ $product->name }}"><x-icon name="chevron-right" width="16" height="16" /></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@if ($products->hasPages())
    <div class="mt-3">{{ $products->links() }}</div>
@endif
@endsection
