@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Olá, '.auth()->user()->firstName())
@section('subtitle', ucfirst(now()->locale('pt_BR')->translatedFormat('l, j \\d\\e F')).' · '.$period->label())

@section('actions')
    <x-period :period="$period" />
@endsection

@php
    $s = $summary;
    $ticket = $s['sales_count'] > 0 ? $s['revenue'] / $s['sales_count'] : 0;
    $margin = $s['revenue'] > 0 ? $s['profit'] / $s['revenue'] * 100 : null;
    $hasSeries = collect($series)->contains(fn ($p) => $p['revenue'] > 0 || $p['profit'] != 0);
    $methodsTotal = $methods->sum('total');
    $soldMax = max(1, $sold->max('revenue') ?? 0);
    $change = fn ($value, $inverse = false) => $value === null ? null : ['value' => $value, 'good' => $inverse ? $value <= 0 : $value >= 0];
@endphp

@section('content')
<div class="dash2">

    {{-- Desempenho + saldo --}}
    <div class="dash2-top">
        <section class="panel dash2-perf">
            <div class="dash2-perf-head">
                <div class="min-w-0">
                    <div class="dash2-eyebrow">Faturamento no período</div>
                    <div class="dash2-hero">
                        <span class="dash2-hero-value">{{ money($s['revenue']) }}</span>
                        @if ($c = $change($changes['revenue']))
                            <span @class(['dash2-delta', 'is-up' => $c['good'], 'is-down' => ! $c['good']])>
                                <x-icon :name="$c['value'] >= 0 ? 'trend-up' : 'trend-down'" width="14" height="14" />
                                {{ $c['value'] >= 0 ? '+' : '' }}{{ number_format($c['value'], 1, ',', '.') }}%
                            </span>
                        @endif
                    </div>
                    <div class="dash2-hero-note">
                        @if ($today['count'] > 0)
                            Hoje: <strong>{{ money($today['total']) }}</strong> em {{ $today['count'] }} {{ $today['count'] === 1 ? 'venda' : 'vendas' }}
                        @else
                            Nenhuma venda hoje ainda
                        @endif
                    </div>
                </div>
                <a href="{{ route('sales.create') }}" class="btn btn-cta d-none d-md-inline-flex"><span class="btn-cta-icon"><x-icon name="plus" width="16" height="16" /></span>Nova venda</a>
            </div>

            <dl class="dash2-metrics">
                <div><dt>Vendas</dt><dd>{{ $s['sales_count'] }}</dd></div>
                <div><dt>Ticket médio</dt><dd>{{ money($ticket) }}</dd></div>
                <div>
                    <dt>Lucro</dt>
                    <dd @class(['text-positive' => $s['profit'] > 0, 'text-negative' => $s['profit'] < 0])>{{ money($s['profit']) }}</dd>
                </div>
                <div><dt>Margem</dt><dd>{{ $margin === null ? '—' : number_format($margin, 1, ',', '.').'%' }}</dd></div>
            </dl>

            <div class="dash2-chart-wrap">
                <div class="dash2-legend" aria-hidden="true">
                    <span><i class="is-revenue"></i>Faturamento</span>
                    <span><i class="is-profit"></i>Lucro</span>
                </div>
                @if ($hasSeries)
                    <div class="dash2-chart" data-dash-chart='@json($series)' role="img" aria-label="Gráfico de faturamento e lucro por {{ count($series) > 0 && str_contains($series[0]['label'], '/') ? 'dia' : 'mês' }} no período"></div>
                    <table class="visually-hidden">
                        <caption>Faturamento e lucro no período</caption>
                        <thead><tr><th>Data</th><th>Faturamento</th><th>Lucro</th><th>Vendas</th></tr></thead>
                        <tbody>
                            @foreach ($series as $point)
                                <tr><td>{{ $point['title'] }}</td><td>{{ money($point['revenue']) }}</td><td>{{ money($point['profit']) }}</td><td>{{ $point['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="dash2-chart-empty">
                        <x-icon name="chart" width="26" height="26" />
                        <span>As vendas do período aparecem aqui em forma de gráfico.</span>
                    </div>
                @endif
            </div>
        </section>

        <div class="dash2-side">
            <section class="dash2-balance">
                <img src="{{ asset('img/logo-icon.png') }}" alt="" class="dash2-balance-mark">
                <div class="dash2-balance-label">Saldo em caixa</div>
                <div class="dash2-balance-value">{{ money($s['balance']) }}</div>
                <div class="dash2-balance-rows">
                    <div><span>Entrou no período</span><strong>+ {{ money($s['received']) }}</strong></div>
                    <div><span>Saiu no período</span><strong>− {{ money($s['spent']) }}</strong></div>
                </div>
            </section>

            <section class="panel dash2-receivable">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="dash2-eyebrow">A receber</div>
                        <div class="dash2-receivable-value">{{ money($s['receivable']) }}</div>
                    </div>
                    <span class="stat-icon accent-amber"><x-icon name="clock" width="18" height="18" /></span>
                </div>
                @if ($overdueCount > 0)
                    <div class="dash2-split" aria-hidden="true">
                        <span class="is-late" style="width: {{ $s['receivable'] > 0 ? max(4, min(100, round($overdueTotal / $s['receivable'] * 100))) : 0 }}%"></span>
                    </div>
                    <div class="small mt-2"><span class="text-negative fw-semibold">{{ money($overdueTotal) }} atrasado</span> <span class="text-muted-2">em {{ $overdueCount }} {{ $overdueCount === 1 ? 'parcela' : 'parcelas' }}</span></div>
                @else
                    <div class="small text-muted-2 mt-2">Nenhuma parcela atrasada.</div>
                @endif
            </section>

            @if ($companyGoal || $myGoal)
                <section class="panel dash2-goals">
                    @foreach (array_filter([['Meta da loja', $companyGoal], ['Sua meta', $myGoal]], fn ($g) => $g[1]) as [$title, $goal])
                        @php $pct = min(100, $goal->progress); $deg = round($pct * 3.6); @endphp
                        <div class="dash2-goal">
                            <div class="dash2-ring" style="--deg: {{ $deg }}deg" role="img" aria-label="{{ $title }}: {{ number_format($goal->progress, 0, ',', '.') }}%">
                                <span>{{ number_format($goal->progress, 0, ',', '.') }}%</span>
                            </div>
                            <div class="min-w-0">
                                <div class="fw-semibold">{{ $title }} no mês</div>
                                <div class="small text-muted-2">{{ money($goal->done) }} de {{ money($goal->goal) }}</div>
                                <div class="small {{ $goal->missing > 0 ? 'text-muted-2' : 'text-positive fw-semibold' }}">{{ $goal->missing > 0 ? 'Faltam '.money($goal->missing).' · '.money($goal->per_day).'/dia' : 'Meta batida' }}</div>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif
        </div>
    </div>

    @if ($lowStockCount > 0)
        <div class="notice-bar is-warning" role="status">
            <x-icon name="box" width="18" height="18" />
            <span>
                <strong>{{ $lowStockCount === 1 ? '1 produto precisa' : $lowStockCount.' produtos precisam' }} de reposição:</strong>
                {{ $lowStock->map(fn ($p) => $p->fullName().' ('.$p->quantity.')')->join(', ') }}{{ $lowStockCount > $lowStock->count() ? '…' : '' }}
            </span>
            <a href="{{ route('products.index', ['repor' => 1]) }}" class="btn btn-sm btn-light">Ver lista</a>
        </div>
    @endif

    {{-- Cobranças, mais vendidos, formas de pagamento --}}
    <div class="dash2-row dash2-row-3">
        <section class="panel dash2-card" data-tabs>
            <header class="dash2-card-head">
                <h2 class="panel-title">Cobranças</h2>
                <div class="dash2-tabs" role="tablist">
                    <button type="button" role="tab" class="active" aria-selected="true" data-tab="late">Atrasadas @if ($overdueCount)<span class="is-late">{{ $overdueCount }}</span>@endif</button>
                    <button type="button" role="tab" aria-selected="false" data-tab="next">Próximas</button>
                </div>
            </header>
            <div data-tab-panel="late">
                @forelse ($overdue as $installment)
                    <div class="dash2-line">
                        <span class="dash2-avatar is-late">{{ mb_strtoupper(mb_substr($installment->customer?->name ?? '?', 0, 1)) }}</span>
                        <a href="{{ $installment->originUrl() }}" class="min-w-0 flex-grow-1 text-body">
                            <span class="dash2-line-title">{{ $installment->customer?->name ?? 'Sem cliente' }}</span>
                            <span class="dash2-line-sub">{{ $installment->sale?->code }} · parcela {{ $installment->label() }}</span>
                        </a>
                        <span class="dash2-line-end">
                            <span class="dash2-line-value">{{ money($installment->amount) }}</span>
                            <span class="dash2-line-late">{{ $installment->daysOverdue() }} {{ $installment->daysOverdue() === 1 ? 'dia' : 'dias' }}</span>
                        </span>
                        @if ($installment->customer)
                            <a href="{{ route('installments.whatsapp', $installment) }}" target="_blank" rel="noopener" class="icon-btn icon-btn-whatsapp" title="Cobrar no WhatsApp" aria-label="Cobrar {{ $installment->customer->name }} no WhatsApp"><x-icon name="whatsapp" width="16" height="16" /></a>
                        @endif
                    </div>
                @empty
                    <x-empty icon="check" message="Nenhuma parcela atrasada." />
                @endforelse
            </div>
            <div data-tab-panel="next" hidden>
                @forelse ($upcoming as $installment)
                    @php $days = (int) now()->startOfDay()->diffInDays($installment->due_date, false); @endphp
                    <div class="dash2-line">
                        <span class="dash2-date"><strong>{{ $installment->due_date->format('d') }}</strong>{{ $installment->due_date->translatedFormat('M') }}</span>
                        <a href="{{ $installment->originUrl() }}" class="min-w-0 flex-grow-1 text-body">
                            <span class="dash2-line-title">{{ $installment->customer?->name ?? 'Sem cliente' }}</span>
                            <span class="dash2-line-sub">{{ $installment->sale?->code }} · parcela {{ $installment->label() }}</span>
                        </a>
                        <span class="dash2-line-end">
                            <span class="dash2-line-value">{{ money($installment->amount) }}</span>
                            <span @class(['dash2-line-due', 'is-soon' => $days <= 1])>{{ $days === 0 ? 'hoje' : ($days === 1 ? 'amanhã' : 'em '.$days.' dias') }}</span>
                        </span>
                    </div>
                @empty
                    <x-empty icon="calendar" message="Nenhuma parcela a vencer." />
                @endforelse
            </div>
        </section>

        <section class="panel dash2-card">
            <header class="dash2-card-head">
                <h2 class="panel-title">Mais vendidos</h2>
                <a href="{{ route('products.index') }}" class="small fw-semibold">Produtos</a>
            </header>
            @forelse ($sold as $i => $item)
                <div class="dash2-rank">
                    <span class="dash2-rank-pos">{{ $i + 1 }}</span>
                    @if ($item->product && $item->product->photos)
                        <img src="{{ $item->product->photoUrls()[0] }}" alt="" class="thumb" onerror="this.classList.add('img-missing')">
                    @else
                        <span class="thumb thumb-empty"><x-icon name="box" width="16" height="16" /></span>
                    @endif
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex justify-content-between gap-2">
                            @if ($item->product)
                                <a href="{{ route('products.show', $item->product) }}" class="dash2-line-title text-body">{{ $item->description }}</a>
                            @else
                                <span class="dash2-line-title">{{ $item->description }}</span>
                            @endif
                            <span class="dash2-line-value">{{ money($item->revenue) }}</span>
                        </div>
                        <div class="dash2-bar"><span style="width: {{ max(3, round($item->revenue / $soldMax * 100)) }}%"></span></div>
                        <div class="dash2-line-sub">{{ $item->qty }} {{ $item->qty === 1 ? 'unidade' : 'unidades' }}</div>
                    </div>
                </div>
            @empty
                <x-empty icon="box" message="Nenhum produto vendido no período." />
            @endforelse
        </section>

        <section class="panel dash2-card">
            <header class="dash2-card-head">
                <h2 class="panel-title">Formas de pagamento</h2>
            </header>
            @forelse ($methods as $method)
                @php $share = $methodsTotal > 0 ? $method->total / $methodsTotal * 100 : 0; @endphp
                <div class="dash2-method">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-semibold">{{ $method->label }}</span>
                        <span class="dash2-line-value">{{ number_format($share, 0, ',', '.') }}%</span>
                    </div>
                    <div class="dash2-bar"><span style="width: {{ max(2, round($share)) }}%"></span></div>
                    <div class="dash2-line-sub">{{ money($method->total) }} · {{ $method->count }} {{ $method->count === 1 ? 'venda' : 'vendas' }}</div>
                </div>
            @empty
                <x-empty icon="wallet" message="Sem vendas no período." />
            @endforelse
        </section>
    </div>

    {{-- Vendas recentes e movimentações --}}
    <div class="dash2-row dash2-row-2">
        <section class="panel dash2-card">
            <header class="dash2-card-head">
                <h2 class="panel-title">Vendas recentes</h2>
                <a href="{{ route('sales.index') }}" class="small fw-semibold">Ver todas</a>
            </header>
            @forelse ($recentSales as $sale)
                <a href="{{ route('sales.show', $sale) }}" class="dash2-line is-link">
                    <span class="dash2-avatar">{{ mb_strtoupper(mb_substr($sale->customerName(), 0, 1)) }}</span>
                    <span class="min-w-0 flex-grow-1">
                        <span class="dash2-line-title">{{ $sale->customerName() }}</span>
                        <span class="dash2-line-sub">{{ $sale->code }} · {{ $sale->sale_date->format('d/m/Y') }} · {{ (int) $sale->units }} {{ (int) $sale->units === 1 ? 'item' : 'itens' }}</span>
                    </span>
                    @if ($sale->status->value !== 'concluida')<x-status :value="$sale->status" />@endif
                    <span class="dash2-line-value">{{ money($sale->total) }}</span>
                    <x-icon name="chevron-right" width="16" height="16" class="text-muted-2 flex-none" />
                </a>
            @empty
                <x-empty icon="cart" message="Nenhuma venda registrada.">
                    <a href="{{ route('sales.create') }}" class="btn btn-cta btn-cta-sm mt-2"><span class="btn-cta-icon"><x-icon name="plus" width="14" height="14" /></span>Registrar a primeira venda</a>
                </x-empty>
            @endforelse
        </section>

        <section class="panel dash2-card">
            <header class="dash2-card-head">
                <h2 class="panel-title">Movimentações</h2>
                @can('admin')<a href="{{ route('finance.index') }}" class="small fw-semibold">Fluxo de caixa</a>@endcan
            </header>
            @forelse ($transactions as $t)
                @php $in = $t->type === $entrada; @endphp
                <div class="dash2-line">
                    <span class="timeline-icon {{ $in ? 'is-in' : 'is-out' }}"><x-icon :name="$in ? 'arrow-in' : 'arrow-out'" width="14" height="14" /></span>
                    <span class="min-w-0 flex-grow-1">
                        <span class="dash2-line-title">{{ $t->description }}</span>
                        <span class="dash2-line-sub">{{ $t->date->format('d/m/Y') }} · {{ $t->originLabel() }}</span>
                    </span>
                    <span class="dash2-line-value {{ $in ? 'text-positive' : 'text-negative' }}">{{ $in ? '+' : '−' }} {{ money($t->amount) }}</span>
                </div>
            @empty
                <x-empty icon="wallet" message="Nenhuma movimentação ainda." />
            @endforelse
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard.js') }}?v=1" defer></script>
@endpush
