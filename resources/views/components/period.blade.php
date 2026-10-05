@props(['period', 'keep' => []])
<form method="GET" class="d-flex flex-wrap gap-2 align-items-center no-print" data-no-lock>
    @foreach ($keep as $name)
        @if (filled(request($name)))
            <input type="hidden" name="{{ $name }}" value="{{ request($name) }}">
        @endif
    @endforeach
    <select name="periodo" class="form-select form-select-sm w-auto" data-period-select aria-label="Período">
        @foreach (\App\Support\Period::OPTIONS as $key => $label)
            <option value="{{ $key }}" @selected($period->key === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <input type="date" name="inicio" value="{{ $period->startDate() }}" class="form-control form-control-sm w-auto" data-period-custom aria-label="Início">
    <input type="date" name="fim" value="{{ $period->endDate() }}" class="form-control form-control-sm w-auto" data-period-custom aria-label="Fim">
    <button class="btn btn-sm btn-outline-secondary" data-period-custom>Aplicar</button>
    {{ $slot }}
</form>
