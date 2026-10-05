@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $class)
    @if (session($key))
        <div class="alert alert-{{ $class }} alert-dismissible fade show" role="alert">
            {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
@endforeach
@if ($errors->any() && ! session('error'))
    <div class="alert alert-danger" role="alert">
        Revise os campos destacados.
        @if ($errors->count() <= 4)
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
