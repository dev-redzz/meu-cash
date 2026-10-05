@extends('layouts.app')

@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')

@section('content')
@include('settings._tabs')
<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="panel" style="max-width: 640px;">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="panel-body">
        <x-input name="name" label="Nome" :value="$user->name" required maxlength="255" />
        <x-input name="email" type="email" label="E-mail" :value="$user->email" required autocomplete="off" />
        <x-select name="role" label="Perfil" :options="\App\Enums\UserRole::options()" :value="$user->role" required :disabled="$user->is(auth()->user())" />
        @if ($user->is(auth()->user()))<input type="hidden" name="role" value="admin">@endif
        <div class="row gx-3">
            <div class="col-md-6"><x-input name="password" type="password" :label="$user->exists ? 'Nova senha (opcional)' : 'Senha'" :required="! $user->exists" autocomplete="new-password" help="Mínimo de 8 caracteres." /></div>
            <div class="col-md-6"><x-input name="password_confirmation" type="password" label="Confirmar senha" autocomplete="new-password" /></div>
        </div>
        <div class="form-check">
            <input type="hidden" name="active" value="0">
            <input class="form-check-input" type="checkbox" name="active" id="active" value="1" @checked(old('active', $user->active ?? true)) @disabled($user->is(auth()->user()))>
            <label class="form-check-label" for="active">Usuário ativo (pode entrar no sistema)</label>
        </div>
    </div>
    <div class="panel-header border-top border-bottom-0 justify-content-end">
        <a href="{{ route('users.index') }}" class="btn btn-light">Cancelar</a>
        <button class="btn btn-primary">{{ $user->exists ? 'Salvar alterações' : 'Criar usuário' }}</button>
    </div>
</form>
@endsection
