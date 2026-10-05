<form method="GET" class="panel-body border-bottom filter-bar" data-no-lock>
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label" for="f_q">Buscar</label>
            <input type="search" name="q" id="f_q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Nome, modelo ou marca">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="f_cat">Categoria</label>
            <select name="category_id" id="f_cat" class="form-select form-select-sm">
                <option value="">Todas</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="f_brand">Marca</label>
            <select name="brand" id="f_brand" class="form-select form-select-sm">
                <option value="">Todas</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}" @selected(($filters['brand'] ?? '') === $brand)>{{ $brand }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="f_status">Status</label>
            <select name="status" id="f_status" class="form-select form-select-sm">
                <option value="">Todos</option>
                @foreach (\App\Enums\ProductStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Preço de venda</label>
            <div class="input-group input-group-sm">
                <input type="text" name="min_price" value="{{ $filters['min_price'] ?? '' }}" class="form-control" placeholder="mín." inputmode="decimal" aria-label="Preço mínimo">
                <input type="text" name="max_price" value="{{ $filters['max_price'] ?? '' }}" class="form-control" placeholder="máx." inputmode="decimal" aria-label="Preço máximo">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label">Data da compra</label>
            <div class="input-group input-group-sm">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" aria-label="De">
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" aria-label="Até">
            </div>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
            <a href="{{ url()->current() }}" class="btn btn-sm btn-link">Limpar</a>
        </div>
    </div>
</form>
