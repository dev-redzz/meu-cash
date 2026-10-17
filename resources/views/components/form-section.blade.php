@props(['title', 'icon' => null, 'description' => null, 'accent' => 'blue'])
<section {{ $attributes->merge(['class' => 'panel form-section']) }}>
    <header class="form-section-head">
        @if ($icon)<span class="stat-icon accent-{{ $accent }}"><x-icon :name="$icon" width="18" height="18" /></span>@endif
        <div class="min-w-0">
            <h2 class="panel-title">{{ $title }}</h2>
            @if ($description)<p class="form-section-desc">{{ $description }}</p>@endif
        </div>
        @isset($aside)<div class="ms-auto">{{ $aside }}</div>@endisset
    </header>
    <div class="panel-body">{{ $slot }}</div>
</section>
