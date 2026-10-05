@props(['value'])
@if ($value)
<span {{ $attributes->merge(['class' => 'badge text-bg-'.$value->color()]) }}>{{ $value->label() }}</span>
@endif
