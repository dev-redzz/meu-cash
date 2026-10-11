@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Pessoas com acesso</h2><span class="small text-muted-2">{{ $users->count() }}</span></div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Pessoa</th><th>Perfil</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('partials.avatar', ['person' => $user, 'size' => 32])
                                        <div>
                                            <div class="fw-semibold">{{ $user->name }}@if ($user->is(auth()->user())) <span class="small text-muted-2">(você)</span>@endif</div>
                                            <div class="small text-muted-2">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><x-status :value="$user->role" /></td>
                                <td><span class="pill {{ $user->active ? 'pill-success' : 'pill-secondary' }}">{{ $user->active ? 'Ativo' : 'Desativado' }}</span></td>
                                <td class="text-end">
                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('users.update', $user) }}" class="d-inline-flex gap-1">
                                            @csrf @method('PATCH')
                                            <select name="role" class="form-select form-select-sm w-auto" aria-label="Perfil">
                                                @foreach (\App\Enums\UserRole::options() as $value => $label)
                                                    <option value="{{ $value }}" @selected($user->role->value === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="active" value="{{ $user->active ? 1 : 0 }}">
                                            <button class="btn btn-sm btn-light">Salvar</button>
                                        </form>
                                        <form method="POST" action="{{ route('users.update', $user) }}" class="d-inline" data-confirm="{{ $user->active ? 'Desativar o acesso de '.$user->name.'?' : 'Reativar o acesso de '.$user->name.'?' }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="role" value="{{ $user->role->value }}">
                                            <input type="hidden" name="active" value="{{ $user->active ? 0 : 1 }}">
                                            <button class="btn btn-sm {{ $user->active ? 'btn-outline-danger' : 'btn-outline-primary' }}">{{ $user->active ? 'Desativar' : 'Reativar' }}</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($invitations->isNotEmpty())
            <div class="panel mt-3">
                <div class="panel-header"><h2 class="panel-title">Convites pendentes</h2></div>
                @foreach ($invitations as $invitation)
                    <div class="list-row">
                        <div>
                            <div class="fw-semibold">{{ $invitation->email }}</div>
                            <div class="small text-muted-2">{{ $invitation->role->label() }} · convidado em {{ $invitation->created_at->format('d/m/Y') }}</div>
                        </div>
                        <form method="POST" action="{{ route('users.invite.cancel', $invitation) }}" data-confirm="Cancelar este convite?">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light">Cancelar</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ route('users.invite') }}" class="panel">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Convidar pessoa</h2></div>
            <div class="panel-body">
                <x-input name="email" type="email" label="E-mail da conta Google" required placeholder="nome@gmail.com" />
                <x-select name="role" label="Perfil" :options="\App\Enums\UserRole::options()" value="funcionario" required />
                <button class="btn btn-primary w-100">Liberar acesso</button>
                <p class="small text-muted-2 mt-3 mb-0">A pessoa entra em <strong>{{ url('/login') }}</strong> com essa conta Google e já cai nesta empresa.</p>
            </div>
            <div class="panel-body border-top small text-muted-2">
                <strong>Administrador</strong> acessa tudo. <strong>Funcionário</strong> registra vendas, clientes, produtos e recebimentos, mas não vê financeiro, relatórios e configurações, nem exclui registros.
            </div>
        </form>
    </div>
</div>
@endsection
