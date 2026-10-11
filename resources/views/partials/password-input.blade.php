@php $fieldId = $id ?? $name; @endphp
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-baseline">
        <label for="{{ $fieldId }}" class="form-label">{{ $label }}</label>
        @isset($aside){!! $aside !!}@endisset
    </div>
    <div class="input-password">
        <input type="password" name="{{ $name }}" id="{{ $fieldId }}" class="form-control @error($name) is-invalid @enderror" autocomplete="{{ $autocomplete ?? 'current-password' }}" required>
        <button type="button" class="input-password-toggle" data-toggle-password="{{ $fieldId }}" aria-pressed="false">Mostrar</button>
    </div>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @isset($help)<div class="form-text">{{ $help }}</div>@endisset
</div>
