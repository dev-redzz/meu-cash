@extends('layouts.app')

@section('title', 'Plataforma')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-2"><x-stat label="Empresas" :value="$stats['companies']" /></div>
    <div class="col-6 col-lg-2"><x-stat label="Ativas" :value="$stats['active']" tone="positive" /></div>
    <div class="col-6 col-lg-2"><x-stat label="Novas no mês" :value="$stats['new_month']" /></div>
    <div class="col-6 col-lg-2"><x-stat label="Usaram nos últimos 7 dias" :value="$stats['active_week']" /></div>
    <div class="col-6 col-lg-2"><x-stat label="Usuários" :value="$stats['users']" /></div>
    <div class="col-6 col-lg-2"><x-stat label="Vendas no mês" :value="$stats['sales_month']" hint="Todas as empresas" /></div>
</div>

<div class="panel">
    <div class="panel-header flex-wrap">
        <h2 class="panel-title">Empresas cadastradas</h2>
        <form method="GET" class="d-flex flex-wrap gap-2" data-no-lock>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-auto" placeholder="Empresa, CNPJ ou e-mail">
            <select name="situacao" class="form-select form-select-sm w-auto" aria-label="Situação">
                <option value="">Todas</option>
                <option value="ativas" @selected(request('situacao') === 'ativas')>Ativas</option>
                <option value="suspensas" @selected(request('situacao') === 'suspensas')>Suspensas</option>
            </select>
            <button class="btn btn-sm btn-light">Filtrar</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Responsável</th>
                    <th class="num">Usuários</th>
                    <th class="num">Produtos</th>
                    <th class="num">Vendas</th>
                    <th class="num">Faturado no mês</th>
                    <th>Último uso</th>
                    <th>Cadastro</th>
                    <th>Situação</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $company->name }}</div>
                            <div class="small text-muted-2">#{{ $company->id }}{{ $company->document ? ' · '.$company->document : '' }}{{ $company->phone ? ' · '.$company->phone : '' }}</div>
                        </td>
                        <td class="small">{{ $company->owner_email ?? '—' }}</td>
                        <td class="num">{{ $company->users_count }}</td>
                        <td class="num">{{ $company->products_count }}</td>
                        <td class="num">{{ $company->sales_count }}</td>
                        <td class="num">{{ money($company->month_revenue) }}</td>
                        <td class="small">{{ $company->last_activity ? \Illuminate\Support\Carbon::parse($company->last_activity)->diffForHumans() : 'Nunca' }}</td>
                        <td class="small">{{ $company->created_at->format('d/m/Y') }}</td>
                        <td><span class="pill {{ $company->active ? 'pill-success' : 'pill-danger' }}">{{ $company->active ? 'Ativa' : 'Suspensa' }}</span></td>
                        <td class="text-end">
                            @if ($company->id !== auth()->user()->company_id)
                                <form method="POST" action="{{ route('platform.toggle', $company) }}" data-confirm="{{ $company->active ? 'Suspender o acesso de '.$company->name.'? Ninguém da empresa conseguirá entrar.' : 'Reativar '.$company->name.'?' }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $company->active ? 'btn-outline-danger' : 'btn-outline-primary' }}">{{ $company->active ? 'Suspender' : 'Reativar' }}</button>
                                </form>
                            @else
                                <span class="small text-muted-2">Sua empresa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10"><x-empty message="Nenhuma empresa encontrada." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $companies->links() }}</div>
@endsection
