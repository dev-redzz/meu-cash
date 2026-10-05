@props(['label', 'value', 'hint' => null, 'tone' => null])
<div {{ $attributes->merge(['class' => 'stat'.($tone ? ' tone-'.$tone : '')]) }}>
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    @if ($hint)
        <div class="stat-hint">{{ $hint }}</div>
    @endif
</div>
