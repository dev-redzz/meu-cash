@extends('layouts.app')

@section('title', 'Vendedores')

@section('actions')
    <x-period :period="$period" />
    <a href="{{ route('users.index') }}" class="btn btn-cta btn-cta-light"><span class="btn-cta-icon"><x-icon name="team" width="16" height="16" /></span>Definir comissões e metas</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat label="Vendas no período" icon="cart" accent="blue" :value="$totals['sales']" :hint="$period->label()" /></div>
    <div class="col-md-4"><x-stat label="Faturamento" icon="dollar" accent="green" :value="money($totals['revenue'])" /></div>
    <div class="col-md-4"><x-stat label="Comissões a pagar" icon="team" accent="violet" :value="money($totals['commission'])" hint="Soma das comissões das vendas concluídas" /></div>
</div>

@if ($companyGoal)
    <div class="panel mb-3"><div class="panel-body">@include('partials.goal-bar', ['goal' => $companyGoal, 'title' => 'Meta da empresa neste mês'])</div></div>
@endif

<div class="panel">
    <div class="panel-header"><h2 class="panel-title">Desempenho por vendedor</h2></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Vendedor</th>
                    <th class="num">Vendas</th>
                    <th class="num">Faturamento</th>
                    <th class="num">Lucro</th>
                    <th class="num">Comissão</th>
                    <th style="min-width: 220px">Meta do mês</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @include('partials.avatar', ['person' => $row->user, 'size' => 30])
                                <div>
                                    <div class="fw-semibold">{{ $row->user->name }}</div>
                                    <div class="small text-muted-2">{{ (float) $row->user->commission_rate > 0 ? number_format((float) $row->user->commission_rate, 2, ',', '.').'% de comissão' : 'Sem comissão' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="num">{{ $row->sales }}</td>
                        <td class="num fw-semibold">{{ money($row->revenue) }}</td>
                        <td class="num">{{ money($row->profit) }}</td>
                        <td class="num text-positive fw-semibold">{{ money($row->commission) }}</td>
                        <td>
                            @if ($row->progress !== null)
                                <div class="goal-track goal-track-sm"><div class="goal-fill {{ $row->progress >= 100 ? 'is-done' : ($row->progress >= 60 ? 'is-good' : '') }}" style="width: {{ min(100, $row->progress) }}%"></div></div>
                                <div class="small text-muted-2 mt-1">{{ number_format($row->progress, 0, ',', '.') }}% · {{ money($row->month_revenue) }} de {{ money($row->goal) }}</div>
                            @else
                                <span class="small text-muted-2">Sem meta</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty message="Nenhum vendedor encontrado." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
