@php $pct = min(100, $goal->progress); $tone = $goal->progress >= 100 ? 'is-done' : ($goal->progress >= 60 ? 'is-good' : ''); @endphp
<div class="goal">
    <div class="d-flex justify-content-between align-items-baseline mb-1">
        <span class="fw-semibold">{{ $title }}</span>
        <span class="num fw-bold {{ $goal->progress >= 100 ? 'text-positive' : '' }}">{{ number_format($goal->progress, 0, ',', '.') }}%</span>
    </div>
    <div class="goal-track" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"><div class="goal-fill {{ $tone }}" style="width: {{ $pct }}%"></div></div>
    <div class="d-flex justify-content-between small text-muted-2 mt-1">
        <span>{{ money($goal->done) }} de {{ money($goal->goal) }}</span>
        <span>
            @if ($goal->missing > 0)
                Faltam {{ money($goal->missing) }} · {{ money($goal->per_day) }}/dia
            @else
                Meta batida
            @endif
        </span>
    </div>
</div>
