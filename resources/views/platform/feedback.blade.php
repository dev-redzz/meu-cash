@extends('layouts.app')

@section('title', 'Sugestões da versão beta')

@section('subtitle')
    <a href="{{ route('platform.index') }}" class="back-link"><x-icon name="arrow-left" width="15" height="15" /> Plataforma</a>
@endsection

@section('actions')
    @if ($unread)
        <form method="POST" action="{{ route('platform.feedback.read') }}">
            @csrf
            <button class="btn btn-cta btn-cta-light"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>Marcar tudo como lido</button>
        </form>
    @endif
@endsection

@section('content')
<section class="panel">
    @if ($items->isEmpty())
        <x-empty icon="note" message="Nenhuma sugestão ainda. Elas chegam pelo botão Enviar sugestão, dentro do sistema." />
    @else
        <ul class="entry-list">
            @foreach ($items as $item)
                <li @class(['feedback-item', 'is-unread' => ! $item->read_at])>
                    <span class="pill pill-{{ $item->typeColor() }} flex-none">{{ $item->typeLabel() }}</span>
                    <div class="min-w-0 flex-grow-1">
                        <div class="feedback-message">{{ $item->message }}</div>
                        <div class="small text-muted-2 mt-1">
                            {{ $item->user?->name ?? 'Usuário removido' }}@if ($item->user) · <a href="mailto:{{ $item->user->email }}">{{ $item->user->email }}</a>@endif
                            · {{ $item->company?->name ?? '—' }} · {{ $item->created_at->diffForHumans() }}
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
@if ($items->hasPages())
    <div class="mt-3">{{ $items->links() }}</div>
@endif
@endsection
