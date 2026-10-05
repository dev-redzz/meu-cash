@props(['message' => 'Nada por aqui ainda.'])
<div class="empty-state">
    <div>{{ $message }}</div>
    @if ($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
