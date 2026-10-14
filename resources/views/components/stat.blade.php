@props(['label', 'value', 'hint' => null, 'tone' => null, 'change' => null, 'inverse' => false, 'icon' => null, 'accent' => 'blue'])
<div {{ $attributes->merge(['class' => 'stat'.($tone ? ' tone-'.$tone : '')]) }}>
    <div class="stat-top">
        <div class="stat-label">{{ $label }}</div>
        @if ($icon)
            <span class="stat-icon accent-{{ $accent }}"><x-icon :name="$icon" width="18" height="18" /></span>
        @endif
    </div>
    <div class="stat-value">{{ $value }}</div>
    @if ($change !== null)
        @php $good = $inverse ? $change <= 0 : $change >= 0; @endphp
        <div class="stat-change {{ $good ? 'is-good' : 'is-bad' }}">
            <x-icon :name="$change >= 0 ? 'trend-up' : 'trend-down'" width="14" height="14" />
            {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 1, ',', '.') }}% vs. período anterior
        </div>
    @endif
    @if ($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
    {{ $slot }}
</div>
