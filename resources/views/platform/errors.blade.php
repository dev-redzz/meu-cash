@extends('layouts.app')

@section('title', 'Erros do sistema')

@section('subtitle')
    <a href="{{ route('platform.index') }}" class="back-link"><x-icon name="arrow-left" width="15" height="15" /> Plataforma</a>
@endsection

@section('actions')
    @if ($resolvedCount)
        <form method="POST" action="{{ route('platform.errors.clear') }}" data-confirm="Apagar todos os {{ $resolvedCount }} erros resolvidos?">
            @csrf @method('DELETE')
            <button class="btn btn-cta btn-cta-light btn-cta-warn"><span class="btn-cta-icon"><x-icon name="trash" width="16" height="16" /></span>Apagar resolvidos</button>
        </form>
    @endif
@endsection

@section('content')
<div class="panel">
    <div class="panel-toolbar">
        <nav class="status-chips" aria-label="Filtrar erros">
            <a href="{{ route('platform.errors') }}" @class(['status-chip', 'dot-danger', 'active' => ! $resolved])>Em aberto <span>{{ $openCount }}</span></a>
            <a href="{{ route('platform.errors', ['ver' => 'resolvidos']) }}" @class(['status-chip', 'dot-success', 'active' => $resolved])>Resolvidos <span>{{ $resolvedCount }}</span></a>
        </nav>
    </div>

    @if ($reports->isEmpty())
        <x-empty icon="check" :message="$resolved ? 'Nenhum erro resolvido guardado.' : 'Nenhum erro em aberto. Tudo funcionando.'" />
    @else
        <ul class="error-list">
            @foreach ($reports as $report)
                <li>
                    <details>
                        <summary>
                            <span class="error-count" title="Ocorrências">{{ $report->count }}×</span>
                            <span class="min-w-0 flex-grow-1">
                                <span class="error-message">{{ $report->message }}</span>
                                <span class="error-meta">{{ $report->shortClass() }} · {{ $report->shortFile() }}:{{ $report->line }}</span>
                            </span>
                            <span class="error-when">
                                <span>{{ $report->last_seen_at->diffForHumans() }}</span>
                                <small>desde {{ $report->first_seen_at->format('d/m H:i') }}</small>
                            </span>
                        </summary>
                        <div class="error-detail">
                            <dl class="spec-grid">
                                <div><dt>Página</dt><dd title="{{ $report->url }}">{{ $report->method }} {{ $report->url ?: '—' }}</dd></div>
                                <div><dt>Empresa</dt><dd>{{ $report->company?->name ?? '—' }}</dd></div>
                                <div><dt>Usuário</dt><dd>{{ $report->user?->email ?? '—' }}</dd></div>
                            </dl>
                            <pre class="error-trace">{{ $report->trace }}</pre>
                            @unless ($report->resolved_at)
                                <form method="POST" action="{{ route('platform.errors.resolve', $report) }}" class="mt-3">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-cta btn-cta-sm"><span class="btn-cta-icon"><x-icon name="check" width="14" height="14" /></span>Marcar como resolvido</button>
                                </form>
                            @endunless
                        </div>
                    </details>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@if ($reports->hasPages())
    <div class="mt-3">{{ $reports->links() }}</div>
@endif
@endsection
