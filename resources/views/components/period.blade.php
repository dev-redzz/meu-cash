@props(['period', 'keep' => []])
@php
    $kept = collect($keep)->mapWithKeys(fn ($name) => [$name => request($name)])->filter(fn ($v) => filled($v))->all();
    $label = $period->key === 'personalizado' ? 'Personalizado' : \App\Support\Period::OPTIONS[$period->key];
    $range = $period->start->isSameDay($period->end)
        ? $period->start->format('d/m/Y')
        : $period->start->format($period->start->year === $period->end->year ? 'd/m' : 'd/m/Y').' – '.$period->end->format('d/m/Y');
    $presets = collect(\App\Support\Period::OPTIONS)->except('personalizado');
@endphp
<div class="dropdown period-picker no-print">
    <button type="button" class="period-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <span class="period-toggle-icon"><x-icon name="calendar" width="16" height="16" /></span>
        <span class="period-toggle-text">
            <span class="period-toggle-label">{{ $label }}</span>
            <span class="period-toggle-range">{{ $range }}</span>
        </span>
        <x-icon name="chevron-down" width="16" height="16" class="period-toggle-caret" />
    </button>
    <div class="dropdown-menu dropdown-menu-end period-menu">
        <div class="period-presets">
            @foreach ($presets as $key => $text)
                <a href="{{ url()->current().'?'.http_build_query($kept + ['periodo' => $key]) }}" @class(['period-preset', 'active' => $period->key === $key])>
                    {{ $text }}
                    @if ($period->key === $key)<x-icon name="check" width="16" height="16" />@endif
                </a>
            @endforeach
        </div>
        <form method="GET" class="period-custom" data-no-lock>
            @foreach ($kept as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <input type="hidden" name="periodo" value="personalizado">
            <div class="period-custom-title">Escolher datas</div>
            <div class="period-custom-fields">
                <label><span>De</span><input type="date" name="inicio" value="{{ $period->startDate() }}" class="form-control form-control-sm" required></label>
                <label><span>Até</span><input type="date" name="fim" value="{{ $period->endDate() }}" class="form-control form-control-sm" required></label>
            </div>
            <button class="btn btn-primary btn-sm w-100 mt-2">Aplicar período</button>
        </form>
    </div>
</div>
