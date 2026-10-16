@extends('layouts.portal')

@section('title', 'Entrar')

@section('content')
<div class="portal-login">
    <div class="portal-login-mark">
        <img src="{{ asset('img/logo-icon.png') }}" alt="">
    </div>
    <h1 class="portal-login-title">Acompanhe suas compras</h1>
    <p class="portal-login-text">Digite o código de acesso que está no seu comprovante de compra para ver suas parcelas, pagamentos e comprovantes.</p>

    @if (session('success'))<div class="alert alert-success small py-2">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger small py-2">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('portal.authenticate') }}" class="portal-login-form" novalidate>
        @csrf
        <label for="code" class="form-label">Código de acesso</label>
        <input type="text" id="code" name="code" value="{{ old('code', $code) }}" class="form-control portal-code-input @error('code') is-invalid @enderror"
               placeholder="XXXX-XXXX" maxlength="9" autocomplete="off" autocapitalize="characters" spellcheck="false" inputmode="text" autofocus required>
        @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">Entrar</button>
    </form>

    <p class="portal-login-help">Não encontrou o código? Peça para a loja reenviar pelo WhatsApp.</p>
</div>

<script>
(function () {
    var input = document.getElementById('code');
    input.addEventListener('input', function () {
        var raw = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
        input.value = raw.length > 4 ? raw.slice(0, 4) + '-' + raw.slice(4) : raw;
    });
    input.form.addEventListener('submit', function () {
        var button = input.form.querySelector('[type="submit"]');
        setTimeout(function () { button.disabled = true; }, 0);
    });
})();
</script>
@endsection
