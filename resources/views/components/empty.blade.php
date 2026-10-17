@props(['message' => 'Nada por aqui ainda.', 'icon' => 'box'])
<div class="empty-state">
    <span class="empty-state-icon"><x-icon :name="$icon" width="22" height="22" /></span>
    <div>{{ $message }}</div>
    @if ($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
