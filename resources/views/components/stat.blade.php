@props(['label', 'value', 'hint' => null, 'tone' => null, 'change' => null, 'inverse' => false])
<div {{ $attributes->merge(['class' => 'stat'.($tone ? ' tone-'.$tone : '')]) }}>
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">
        {{ $value }}
        @if ($change !== null)
            @php $good = $inverse ? $change <= 0 : $change >= 0; @endphp
            <x-icon :name="$change >= 0 ? 'trend-up' : 'trend-down'" width="18" height="18" class="{{ $good ? 'text-positive' : 'text-negative' }}" />
        @endif
    </div>
    @if ($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
    @if ($change !== null)
        <div class="stat-change {{ ($inverse ? $change <= 0 : $change >= 0) ? 'text-positive' : 'text-negative' }}">{{ $change >= 0 ? '+' : '' }}{{ number_format($change, 1, ',', '.') }}% vs. período anterior</div>
    @endif
    {{ $slot }}
</div>
