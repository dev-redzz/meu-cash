@extends('layouts.app')

@section('title', 'Categorias')

@section('subtitle')
    <a href="{{ route('products.index') }}" class="back-link"><x-icon name="arrow-left" width="15" height="15" /> Produtos</a>
@endsection

@section('content')
<div class="form-grid has-aside cat-layout">
    <div class="form-main">
        @foreach ($types as $type)
            @php $list = $categories[$type->value] ?? collect(); @endphp
            <section class="panel">
                <header class="form-section-head">
                    <span class="stat-icon accent-blue"><x-icon name="tag" width="18" height="18" /></span>
                    <div class="min-w-0">
                        <h2 class="panel-title">{{ $type->label() }}</h2>
                        <p class="form-section-desc">{{ $list->count() }} {{ $list->count() === 1 ? 'categoria' : 'categorias' }} · {{ $list->sum('products_count') }} produtos</p>
                    </div>
                </header>
                @if ($list->isEmpty())
                    <x-empty icon="tag" message="Nenhuma categoria ainda. Crie a primeira ao lado." />
                @else
                    <div class="cat-grid">
                        @foreach ($list as $category)
                            <div @class(['cat-card', 'is-editing' => $editing?->is($category)])>
                                <span class="cat-card-icon">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                                <div class="min-w-0 flex-grow-1">
                                    @if ($type->value === 'produto')
                                        <a href="{{ route('products.index', ['category_id' => $category->id]) }}" class="cat-card-name">{{ $category->name }}</a>
                                    @else
                                        <span class="cat-card-name">{{ $category->name }}</span>
                                    @endif
                                    <div class="small text-muted-2">{{ $category->products_count }} {{ $category->products_count === 1 ? 'produto' : 'produtos' }}</div>
                                </div>
                                <div class="row-actions">
                                    <a href="{{ route('categories.edit', $category) }}" class="icon-btn" title="Editar" aria-label="Editar {{ $category->name }}"><x-icon name="pencil" width="15" height="15" /></a>
                                    @can('admin')
                                        <form method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm="Excluir a categoria {{ $category->name }}? Os produtos dela ficam sem categoria.">
                                            @csrf @method('DELETE')
                                            <button class="icon-btn icon-btn-danger" title="Excluir" aria-label="Excluir {{ $category->name }}"><x-icon name="trash" width="15" height="15" /></button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    <aside class="form-aside">
        <form method="POST" action="{{ $editing ? route('categories.update', $editing) : route('categories.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <x-form-section :title="$editing ? 'Editar categoria' : 'Nova categoria'" :icon="$editing ? 'pencil' : 'plus'" :accent="$editing ? 'amber' : 'green'" :description="$editing ? 'Altere o nome de '.$editing->name.'.' : 'Agrupe seus produtos para filtrar mais rápido.'">
                <input type="hidden" name="type" value="{{ $editing?->type->value ?? 'produto' }}">
                <x-input name="name" label="Nome" :value="$editing?->name" required maxlength="100" placeholder="Ex.: Celulares" :autofocus="(bool) $editing" />
                <div class="aside-actions">
                    <button class="btn btn-cta w-100"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>{{ $editing ? 'Salvar alterações' : 'Criar categoria' }}</button>
                    @if ($editing)<a href="{{ route('categories.index') }}" class="btn btn-light w-100">Cancelar</a>@endif
                </div>
            </x-form-section>
        </form>
    </aside>
</div>
@endsection
