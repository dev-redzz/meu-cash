<div class="auth-divider"><span>ou</span></div>
<a href="{{ route('auth.google') }}" class="btn btn-google w-100">
    @include('partials.google-mark')
    {{ $label ?? 'Entrar com Google' }}
</a>
