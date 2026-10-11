@php $size = $size ?? 34; @endphp
@if ($person->avatar)
    <img src="{{ $person->avatar }}" alt="" class="avatar" width="{{ $size }}" height="{{ $size }}" referrerpolicy="no-referrer">
@else
    <span class="avatar avatar-initials" style="width: {{ $size }}px; height: {{ $size }}px;">{{ $person->initials() }}</span>
@endif
