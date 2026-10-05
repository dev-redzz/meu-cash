@extends('layouts.app')

@section('title', 'Serviços')

@section('actions')
    <a href="{{ route('services.create') }}" class="btn btn-primary">Novo serviço</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat label="Serviços faturados" :value="$totals['count']" :hint="$period->label()" /></div>
    <div class="col-md-4"><x-stat label="Valor" :value="money($totals['amount'])" /></div>
    <div class="col-md-4"><x-stat label="Lucro" :value="money($totals['profit'])" :tone="$totals['profit'] >= 0 ? 'positive' : 'negative'" /></div>
</div>

<div class="panel">
    <div class="panel-header flex-wrap">
        <x-period :period="$period" :keep="['q', 'status', 'category_id']" />
        <form method="GET" class="d-flex flex-wrap gap-2" data-no-lock>
            @foreach ($period->query() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-auto" placeholder="Serviço ou cliente">
            <select name="status" class="form-select form-select-sm w-auto" aria-label="Status">
                <option value="">Todos os status</option>
                @foreach (\App\Enums\ServiceStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category_id" class="form-select form-select-sm w-auto" aria-label="Categoria">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Data</th><th>Serviço</th><th>Cliente</th><th>Status</th><th class="num">Valor</th><th class="num">Despesas</th><th class="num">Lucro</th></tr></thead>
            <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td>{{ $service->date->format('d/m/Y') }}</td>
                        <td><a href="{{ route('services.show', $service) }}" class="fw-semibold">{{ $service->name }}</a>@if ($service->category)<div class="small text-muted-2">{{ $service->category->name }}</div>@endif</td>
                        <td>{{ $service->customer?->name ?? '—' }}</td>
                        <td><x-status :value="$service->status" /></td>
                        <td class="num">{{ money($service->amount) }}</td>
                        <td class="num text-muted-2">{{ money($service->expenses_total) }}</td>
                        <td class="num {{ $service->profit() >= 0 ? 'text-positive' : 'text-negative' }}">{{ money($service->profit()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty message="Nenhum serviço neste período." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $services->links() }}</div>
@endsection
