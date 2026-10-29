@extends('layouts.app')

@section('title', 'Plataforma')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg"><x-stat label="Empresas" icon="layers" accent="blue" :value="$stats['companies']" :hint="$stats['new_month'].' novas no mês'" /></div>
    <div class="col-6 col-lg"><x-stat label="Ativas" icon="check" accent="green" :value="$stats['active']" /></div>
    <div class="col-6 col-lg"><x-stat label="Usuários" icon="team" accent="violet" :value="$stats['users']" /></div>
    <div class="col-6 col-lg"><x-stat label="Usaram em 7 dias" icon="users" accent="slate" :value="$stats['active_week']" /></div>
    <div class="col-6 col-lg"><x-stat label="Vendas no mês" icon="cart" accent="blue" :value="$stats['sales_month']" hint="Todas as empresas" /></div>
</div>

@php
    $backupAt = $backup ? \Illuminate\Support\Carbon::parse($backup['at']) : null;
@endphp
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <section class="panel h-100 system-card">
            <header class="form-section-head">
                <span class="stat-icon accent-{{ ! $backup ? 'slate' : ($backup['ok'] ? 'green' : 'rose') }}"><x-icon name="download" width="18" height="18" /></span>
                <div class="min-w-0">
                    <h2 class="panel-title">Backup diário</h2>
                    <p class="form-section-desc">Cópia completa do banco, todo dia depois das 3h, enviada por e-mail.</p>
                </div>
            </header>
            <div class="panel-body">
                <div class="system-status">
                    @if (! $backup)
                        <strong>Nenhum backup ainda</strong>
                        <span>O primeiro sai hoje depois das 3h ou agora, pelo botão abaixo.</span>
                    @else
                        <strong @class(['text-negative' => ! $backup['ok']])>{{ $backup['ok'] ? 'Último backup' : 'O último backup falhou' }} · {{ $backupAt->format('d/m/Y H:i') }}</strong>
                        <span>{{ $backup['message'] }}@if ($backup['size']) · {{ number_format($backup['size'] / 1024 / 1024, 2, ',', '.') }} MB @endif</span>
                    @endif
                    <span class="small text-muted-2">Destino: {{ $backupRecipients ? implode(', ', $backupRecipients) : 'nenhum e-mail definido (BACKUP_EMAIL ou OWNER_EMAIL)' }}</span>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <form method="POST" action="{{ route('platform.backup') }}">
                        @csrf
                        <button class="btn btn-cta btn-cta-sm"><span class="btn-cta-icon"><x-icon name="check" width="14" height="14" /></span>Fazer backup agora</button>
                    </form>
                    <a href="{{ route('platform.backup.download') }}" class="btn btn-cta btn-cta-light btn-cta-sm"><span class="btn-cta-icon"><x-icon name="download" width="14" height="14" /></span>Baixar backup</a>
                </div>
            </div>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel h-100 system-card">
            <header class="form-section-head">
                <span class="stat-icon accent-{{ $openErrors ? 'rose' : 'green' }}"><x-icon name="alert" width="18" height="18" /></span>
                <div class="min-w-0">
                    <h2 class="panel-title">Monitoramento de erros</h2>
                    <p class="form-section-desc">Todo erro do sistema fica registrado aqui, e você recebe um e-mail quando aparece um novo.</p>
                </div>
            </header>
            <div class="panel-body">
                <div class="system-status">
                    @if ($openErrors)
                        <strong class="text-negative">{{ $openErrors === 1 ? '1 erro em aberto' : $openErrors.' erros em aberto' }}</strong>
                        <span>{{ (int) $errorsToday }} {{ (int) $errorsToday === 1 ? 'ocorrência' : 'ocorrências' }} hoje</span>
                    @else
                        <strong class="text-positive">Nenhum erro em aberto</strong>
                        <span>Tudo funcionando.</span>
                    @endif
                </div>
                <div class="mt-3">
                    <a href="{{ route('platform.errors') }}" class="btn btn-cta btn-cta-light btn-cta-sm"><span class="btn-cta-icon"><x-icon name="eye" width="14" height="14" /></span>Ver erros</a>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="panel">
    <div class="panel-header flex-wrap">
        <h2 class="panel-title">Empresas cadastradas</h2>
        <form method="GET" class="d-flex flex-wrap gap-2" data-no-lock>
            <div class="input-icon toolbar-search">
                <x-icon name="search" width="16" height="16" />
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Empresa, CNPJ ou e-mail">
            </div>
            <select name="situacao" class="form-select w-auto" aria-label="Situação">
                <option value="">Todas</option>
                <option value="ativas" @selected(request('situacao') === 'ativas')>Ativas</option>
                <option value="suspensas" @selected(request('situacao') === 'suspensas')>Suspensas</option>
            </select>
            <button class="btn btn-primary">Filtrar</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle platform-table">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Responsável</th>
                    <th class="num">Usuários</th>
                    <th class="num">Produtos</th>
                    <th class="num">Vendas</th>
                    <th class="num">Faturado no mês</th>
                    <th>Último uso</th>
                    <th>Situação</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($companies as $company)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $company->name }}</div>
                            <div class="small text-muted-2">#{{ $company->id }} · desde {{ $company->created_at->format('d/m/Y') }}</div>
                        </td>
                        <td class="small">{{ $company->owner_email ?? '—' }}</td>
                        <td class="num">{{ $company->users_count }}</td>
                        <td class="num">{{ $company->products_count }}</td>
                        <td class="num">{{ $company->sales_count }}</td>
                        <td class="num">{{ money($company->month_revenue) }}</td>
                        <td class="small">{{ $company->last_activity ? \Illuminate\Support\Carbon::parse($company->last_activity)->diffForHumans() : 'Nunca' }}</td>
                        <td><span class="pill {{ $company->active ? 'pill-success' : 'pill-danger' }}">{{ $company->active ? 'Ativa' : 'Suspensa' }}</span></td>
                        <td class="text-end">
                            @if ($company->id !== auth()->user()->company_id)
                                <form method="POST" action="{{ route('platform.toggle', $company) }}" data-confirm="{{ $company->active ? 'Suspender o acesso de '.$company->name.'? Ninguém da empresa conseguirá entrar.' : 'Reativar '.$company->name.'?' }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $company->active ? 'btn-outline-danger' : 'btn-outline-primary' }}">{{ $company->active ? 'Suspender' : 'Reativar' }}</button>
                                </form>
                            @else
                                <span class="small text-muted-2">Você</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty message="Nenhuma empresa encontrada." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $companies->links() }}</div>
@endsection
