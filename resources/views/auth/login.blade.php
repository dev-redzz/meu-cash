@extends('layouts.auth')

@section('title', 'Entrar')
@section('heading', 'Entrar')
@section('subtitle', 'Acesse a conta da sua empresa.')

@section('content')
<form method="POST" action="{{ route('login.store') }}" novalidate>
    @csrf
    <x-input name="email" type="email" label="E-mail" autocomplete="username" autofocus required />
    @include('partials.password-input', [
        'name' => 'password',
        'label' => 'Senha',
        'aside' => '<a href="'.route('password.request').'" class="small fw-semibold">Esqueci minha senha</a>',
    ])
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" checked>
        <label class="form-check-label small" for="remember">Manter conectado</label>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Entrar</button>
</form>

@include('partials.google-button')

<p class="auth-switch">Ainda não tem conta? <a href="{{ route('register') }}">Criar conta</a></p>
@endsection
