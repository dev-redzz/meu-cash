@extends('layouts.app')

@section('title', 'Categorias')

@section('actions')
    <a href="{{ route('products.index') }}" class="btn btn-light">Voltar para produtos</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <form method="POST" action="{{ $editing ? route('categories.update', $editing) : route('categories.store') }}" class="panel">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div class="panel-header"><h2 class="panel-title">{{ $editing ? 'Editar categoria' : 'Nova categoria' }}</h2></div>
            <div class="panel-body">
                <x-select name="type" label="Usada em" :options="\App\Enums\CategoryType::options()" :value="$editing?->type ?? request('type', 'produto')" required />
                <x-input name="name" label="Nome" :value="$editing?->name" required maxlength="100" />
                <div class="d-flex gap-2 justify-content-end">
                    @if ($editing)<a href="{{ route('categories.index') }}" class="btn btn-light">Cancelar</a>@endif
                    <button class="btn btn-primary">{{ $editing ? 'Salvar' : 'Criar categoria' }}</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-8">
        @foreach ($types as $type)
            <div class="panel mb-3">
                <div class="panel-header"><h2 class="panel-title">{{ $type->label() }}</h2></div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Nome</th><th class="num">Itens</th><th class="text-end">Ações</th></tr></thead>
                        <tbody>
                            @forelse ($categories[$type->value] ?? [] as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td class="num">{{ $type->value === 'produto' ? $category->products_count : $category->services_count }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-light">Editar</a>
                                            @can('admin')
                                                <form method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm="Excluir a categoria {{ $category->name }}?">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><x-empty message="Nenhuma categoria." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
