@php
    $withStatus = $withStatus ?? true;
    $advancedKeys = array_merge(['category_id', 'brand', 'min_price', 'max_price', 'date_from', 'date_to'], $withStatus ? ['status'] : []);
    $advancedCount = collect($filters)->only($advancedKeys)->filter(fn ($v) => filled($v))->count();
    $filterId = 'adv-filters-'.uniqid();
@endphp
<form method="GET" class="filter-toolbar" data-no-lock>
    @if (! $withStatus && filled($filters['status'] ?? null))
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
    @endif
    @if (isset($view) && $view !== 'lista')
        <input type="hidden" name="view" value="{{ $view }}">
    @endif

    <div class="filter-toolbar-main">
        <div class="input-icon flex-grow-1">
            <x-icon name="search" width="16" height="16" />
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Buscar por nome, modelo, marca ou código de barras" aria-label="Buscar">
        </div>
        <button type="button" @class(['btn btn-light filter-toggle', 'is-active' => $advancedCount > 0]) data-bs-toggle="collapse" data-bs-target="#{{ $filterId }}" aria-expanded="{{ $advancedCount ? 'true' : 'false' }}" aria-controls="{{ $filterId }}">
            <x-icon name="filter" width="16" height="16" />
            <span class="d-none d-sm-inline">Filtros</span>
            @if ($advancedCount)<span class="filter-count">{{ $advancedCount }}</span>@endif
        </button>
        <button class="btn btn-primary">Buscar</button>
    </div>

    <div @class(['collapse', 'show' => $advancedCount > 0]) id="{{ $filterId }}">
        <div class="filter-advanced">
            <div class="row g-2 g-lg-3 align-items-end">
                <div class="col-6 col-lg-{{ $withStatus ? 3 : 4 }}">
                    <label class="form-label" for="{{ $filterId }}-cat">Categoria</label>
                    <select name="category_id" id="{{ $filterId }}-cat" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-{{ $withStatus ? 3 : 4 }}">
                    <label class="form-label" for="{{ $filterId }}-brand">Marca</label>
                    <select name="brand" id="{{ $filterId }}-brand" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand }}" @selected(($filters['brand'] ?? '') === $brand)>{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($withStatus)
                    <div class="col-12 col-lg-3">
                        <label class="form-label" for="{{ $filterId }}-status">Status</label>
                        <select name="status" id="{{ $filterId }}-status" class="form-select">
                            <option value="">Todos</option>
                            @foreach (\App\Enums\ProductStatus::options() as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md-6 col-lg-{{ $withStatus ? 3 : 4 }}">
                    <label class="form-label">Preço de venda</label>
                    <div class="range-field">
                        <input type="text" name="min_price" value="{{ $filters['min_price'] ?? '' }}" class="form-control" placeholder="R$ mín." inputmode="decimal" aria-label="Preço mínimo">
                        <span>até</span>
                        <input type="text" name="max_price" value="{{ $filters['max_price'] ?? '' }}" class="form-control" placeholder="R$ máx." inputmode="decimal" aria-label="Preço máximo">
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-6">
                    <label class="form-label">Data da compra</label>
                    <div class="range-field">
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" aria-label="De">
                        <span>até</span>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" aria-label="Até">
                    </div>
                </div>
                <div class="col-12 col-lg-6 d-flex gap-2 justify-content-lg-end">
                    <a href="{{ url()->current() }}{{ isset($view) && $view !== 'lista' ? '?view='.$view : '' }}" class="btn btn-light flex-fill flex-lg-grow-0"><x-icon name="x" width="15" height="15" /> Limpar filtros</a>
                    <button class="btn btn-primary flex-fill flex-lg-grow-0">Aplicar</button>
                </div>
            </div>
        </div>
    </div>
</form>
