@props(['value'])
@if ($value)
<span {{ $attributes->merge(['class' => 'pill pill-'.$value->color()]) }}>{{ $value->label() }}</span>
@endif
