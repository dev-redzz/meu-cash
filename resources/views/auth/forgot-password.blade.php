@extends('layouts.auth')

@section('title', 'Esqueci minha senha')
@section('heading', 'Esqueci minha senha')
@section('subtitle', 'Informe seu e-mail e enviaremos um link para criar uma nova senha.')

@section('content')
<form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf
    <x-input name="email" type="email" label="E-mail" autocomplete="email" autofocus required />
    <button type="submit" class="btn btn-primary btn-lg w-100">Enviar link</button>
</form>

<p class="auth-switch"><a href="{{ route('login') }}">Voltar para o login</a></p>
@endsection
