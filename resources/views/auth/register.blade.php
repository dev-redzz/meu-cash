@extends('layouts.auth')

@section('title', 'Criar conta')
@section('heading', 'Criar conta')
@section('subtitle', 'Cadastre sua empresa e comece a usar agora.')

@section('content')
<form method="POST" action="{{ route('register.store') }}" novalidate>
    @csrf
    <x-input name="name" label="Seu nome" autocomplete="name" autofocus required maxlength="120" />
    <x-input name="company" label="Nome da empresa" maxlength="120" help="Foi convidado por uma empresa? Pode deixar em branco: você entra direto nela." />
    <x-input name="email" type="email" label="E-mail" autocomplete="email" required />
    @include('partials.password-input', ['name' => 'password', 'label' => 'Senha', 'autocomplete' => 'new-password', 'help' => 'Mínimo de 8 caracteres, com letras e números.'])
    @include('partials.password-input', ['name' => 'password_confirmation', 'label' => 'Confirmar senha', 'autocomplete' => 'new-password'])
    <div class="form-check mb-4">
        <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" value="1" @checked(old('terms'))>
        <label class="form-check-label small" for="terms">Li e aceito os <a href="{{ route('terms') }}" target="_blank">termos de uso</a> e a <a href="{{ route('privacy') }}" target="_blank">política de privacidade</a></label>
        @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Criar conta</button>
</form>

@include('partials.google-button', ['label' => 'Criar conta com Google'])

<p class="auth-switch">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
@endsection
