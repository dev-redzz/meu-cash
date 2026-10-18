@php
    $notes = collect(['success' => ['success', 'check'], 'warning' => ['warning', 'alert'], 'error' => ['danger', 'alert']])
        ->filter(fn ($v, $key) => session($key));
@endphp
@if ($notes->isNotEmpty() || session('undo'))
    <div class="toast-stack" role="status" aria-live="polite">
        @if ($undo = session('undo'))
            <div class="toast-note tone-success toast-undo" data-undo-toast>
                <span class="toast-note-icon"><x-icon name="trash" width="18" height="18" /></span>
                <div class="toast-note-text">{{ $undo['message'] }}</div>
                <form method="POST" action="{{ route('undo', $undo['token']) }}" class="m-0">
                    @csrf
                    <button type="submit" class="toast-undo-btn" title="Atalho: Ctrl+Z"><x-icon name="arrow-left" width="15" height="15" /> Desfazer</button>
                </form>
                <button type="button" class="toast-note-close" data-toast-close aria-label="Fechar">&times;</button>
                <span class="toast-undo-timer" aria-hidden="true"></span>
            </div>
        @endif
        @foreach ($notes as $key => [$tone, $icon])
            <div class="toast-note tone-{{ $tone }}" @if ($key === 'success') data-autohide @endif>
                <span class="toast-note-icon"><x-icon :name="$icon" width="18" height="18" /></span>
                <div class="toast-note-text">{{ session($key) }}</div>
                <button type="button" class="toast-note-close" data-toast-close aria-label="Fechar">&times;</button>
            </div>
        @endforeach
    </div>
@endif
@if ($errors->any() && ! session('error'))
    <div class="form-alert" role="alert">
        <span class="toast-note-icon"><x-icon name="alert" width="18" height="18" /></span>
        <div>
            <div class="fw-semibold">Revise os campos destacados.</div>
            @if ($errors->count() <= 4)
                <ul class="mb-0 mt-1 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
