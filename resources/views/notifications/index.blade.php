@extends('layouts.app')

@section('title', 'Notificações')

@section('actions')
    <form method="POST" action="{{ route('notifications.refresh') }}">@csrf<button class="btn btn-light">Verificar vencimentos</button></form>
    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline-primary">Marcar todas como lidas</button></form>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header flex-wrap">
        <ul class="nav nav-tabs-plain">
            <li class="nav-item"><a class="nav-link {{ request('filtro') !== 'nao_lidas' ? 'active' : '' }}" href="{{ route('notifications.index', array_filter(['tipo' => request('tipo')])) }}">Todas</a></li>
            <li class="nav-item"><a class="nav-link {{ request('filtro') === 'nao_lidas' ? 'active' : '' }}" href="{{ route('notifications.index', array_filter(['filtro' => 'nao_lidas', 'tipo' => request('tipo')])) }}">Não lidas</a></li>
        </ul>
        <form method="GET" data-no-lock>
            <input type="hidden" name="filtro" value="{{ request('filtro') }}">
            <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Tipo">
                <option value="">Todos os tipos</option>
                @foreach ($types as $key => [$label])
                    <option value="{{ $key }}" @selected(request('tipo') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
    @forelse ($notifications as $notification)
        <a href="{{ route('notifications.open', $notification) }}" class="list-row text-decoration-none text-body {{ $notification->read_at ? '' : 'bg-light' }}">
            <div>
                <div class="{{ $notification->read_at ? '' : 'fw-bold' }}">{{ $notification->title }}</div>
                @if ($notification->message)<div class="small text-muted-2">{{ $notification->message }}</div>@endif
            </div>
            <div class="text-end flex-shrink-0">
                <span class="badge text-bg-{{ $notification->typeColor() }}">{{ $notification->typeLabel() }}</span>
                <div class="small text-muted-2 mt-1">{{ $notification->created_at->format('d/m H:i') }}</div>
            </div>
        </a>
    @empty
        <x-empty message="Nenhuma notificação." />
    @endforelse
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
