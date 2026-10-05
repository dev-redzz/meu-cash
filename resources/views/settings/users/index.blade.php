@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">Usuários</h2>
        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">Novo usuário</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th></th></tr></thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}@if ($user->is(auth()->user())) <span class="small text-muted-2">(você)</span>@endif</td>
                        <td>{{ $user->email }}</td>
                        <td><x-status :value="$user->role" /></td>
                        <td>{!! $user->active ? '<span class="badge text-bg-success">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' !!}</td>
                        <td class="text-end"><a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-light">Editar</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-body border-top small text-muted-2">
        Administrador acessa tudo. Funcionário registra vendas, clientes, produtos, serviços e recebimentos, mas não acessa financeiro, relatórios, configurações nem exclui registros.
    </div>
</div>
@endsection
