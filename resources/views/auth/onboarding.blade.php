@extends('layouts.auth')

@section('title', 'Cadastrar empresa')
@section('heading', 'Cadastre sua empresa')
@section('subtitle')Conectado como {{ $profile['email'] }}@endsection

@section('content')
<form method="POST" action="{{ route('onboarding.store') }}" novalidate>
    @csrf
    <x-input name="name" label="Nome da empresa" required maxlength="120" autofocus />
    <div class="row gx-3">
        <div class="col-sm-6"><x-input name="document" label="CPF/CNPJ (opcional)" maxlength="20" /></div>
        <div class="col-sm-6"><x-input name="phone" label="WhatsApp (opcional)" maxlength="20" /></div>
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" value="1">
        <label class="form-check-label small" for="terms">Li e aceito os <a href="{{ route('terms') }}" target="_blank">termos de uso</a> e a <a href="{{ route('privacy') }}" target="_blank">política de privacidade</a></label>
        @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Criar empresa e entrar</button>
</form>
<p class="auth-switch"><a href="{{ route('login') }}">Usar outra conta</a></p>
@endsection
