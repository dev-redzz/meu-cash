@extends('layouts.auth')

@section('title', 'Nova senha')
@section('heading', 'Criar nova senha')
@section('subtitle', 'Escolha uma senha com pelo menos 8 caracteres, letras e números.')

@section('content')
<form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-input name="email" type="email" label="E-mail" :value="$email" autocomplete="email" required />
    @include('partials.password-input', ['name' => 'password', 'label' => 'Nova senha', 'autocomplete' => 'new-password'])
    @include('partials.password-input', ['name' => 'password_confirmation', 'label' => 'Confirmar nova senha', 'autocomplete' => 'new-password'])
    <button type="submit" class="btn btn-primary btn-lg w-100">Salvar senha</button>
</form>
@endsection
