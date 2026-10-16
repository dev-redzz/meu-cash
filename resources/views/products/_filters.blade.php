<form method="GET" class="panel-body border-bottom filter-bar" data-no-lock>
    <div class="row g-2 g-lg-3 align-items-end">
        <div class="col-12 col-lg-4">
            <label class="form-label" for="f_q">Buscar</label>
            <div class="input-icon">
                <x-icon name="search" width="16" height="16" />
                <input type="search" name="q" id="f_q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nome, modelo, marca ou código de barras">
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <label class="form-label" for="f_cat">Categoria</label>
            <select name="category_id" id="f_cat" class="form-select">
                <option value="">Todas</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <label class="form-label" for="f_brand">Marca</label>
            <select name="brand" id="f_brand" class="form-select">
                <option value="">Todas</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}" @selected(($filters['brand'] ?? '') === $brand)>{{ $brand }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label" for="f_status">Status</label>
            <select name="status" id="f_status" class="form-select">
                <option value="">Todos</option>
                @foreach (\App\Enums\ProductStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label">Preço de venda</label>
            <div class="range-field">
                <input type="text" name="min_price" value="{{ $filters['min_price'] ?? '' }}" class="form-control" placeholder="R$ mín." inputmode="decimal" aria-label="Preço mínimo">
                <span>até</span>
                <input type="text" name="max_price" value="{{ $filters['max_price'] ?? '' }}" class="form-control" placeholder="R$ máx." inputmode="decimal" aria-label="Preço máximo">
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-5">
            <label class="form-label">Data da compra</label>
            <div class="range-field">
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" aria-label="De">
                <span>até</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" aria-label="Até">
            </div>
        </div>
        <div class="col-12 col-lg-3 d-flex gap-2 justify-content-lg-end">
            <a href="{{ url()->current() }}" class="btn btn-light flex-fill flex-lg-grow-0">Limpar</a>
            <button class="btn btn-primary flex-fill flex-lg-grow-0">Filtrar</button>
        </div>
    </div>
</form>
