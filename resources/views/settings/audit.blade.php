@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<div class="panel">
    <div class="panel-header flex-wrap">
        <x-period :period="$period" :keep="['user_id', 'action']" />
        <form method="GET" class="d-flex flex-wrap gap-2" data-no-lock>
            @foreach ($period->query() as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <select name="user_id" class="form-select form-select-sm w-auto" aria-label="Usuário">
                <option value="">Todos os usuários</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <select name="action" class="form-select form-select-sm w-auto" aria-label="Ação">
                <option value="">Todas as ações</option>
                @foreach ($actions as $key => $label)
                    <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Filtrar</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Data</th><th>Hora</th><th>Usuário</th><th>Ação</th><th>Detalhes</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y') }}</td>
                        <td>{{ $log->created_at->format('H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td><span class="badge text-bg-light border">{{ $log->actionLabel() }}</span></td>
                        <td class="small">{{ $log->description }}</td>
                        <td class="small text-muted-2">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty message="Nenhum registro no período." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
