@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Pessoas com acesso</h2><span class="small text-muted-2">{{ $users->count() }}</span></div>
            <div class="table-responsive">
                <table class="table align-middle team-table">
                    <thead><tr><th>Pessoa</th><th>Perfil</th><th>Comissão</th><th>Meta mensal</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php $self = $user->is(auth()->user()); $form = 'user-'.$user->id; @endphp
                            <tr>
                                <td>
                                    <form method="POST" action="{{ route('users.update', $user) }}" id="{{ $form }}">@csrf @method('PATCH')</form>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('partials.avatar', ['person' => $user, 'size' => 32])
                                        <div>
                                            <div class="fw-semibold">{{ $user->name }}@if ($self) <span class="small text-muted-2">(você)</span>@endif</div>
                                            <div class="small text-muted-2">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($self)
                                        <x-status :value="$user->role" />
                                    @else
                                        <select name="role" form="{{ $form }}" class="form-select form-select-sm" aria-label="Perfil de {{ $user->name }}">
                                            @foreach (\App\Enums\UserRole::options() as $value => $label)
                                                <option value="{{ $value }}" @selected($user->role->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </td>
                                <td>
                                    <div class="input-group input-group-sm" style="width: 96px">
                                        <input type="text" name="commission_rate" form="{{ $form }}" inputmode="decimal" class="form-control" value="{{ number_format((float) $user->commission_rate, 2, ',', '') }}" aria-label="Comissão de {{ $user->name }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm" style="width: 140px">
                                        <span class="input-group-text">R$</span>
                                        <input type="text" name="monthly_goal" form="{{ $form }}" inputmode="decimal" class="form-control" value="{{ number_format((float) $user->monthly_goal, 2, ',', '.') }}" aria-label="Meta de {{ $user->name }}">
                                    </div>
                                </td>
                                <td><span class="pill {{ $user->active ? 'pill-success' : 'pill-secondary' }}">{{ $user->active ? 'Ativo' : 'Desativado' }}</span></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="submit" form="{{ $form }}" class="btn btn-sm btn-light">Salvar</button>
                                        @unless ($self)
                                            <form method="POST" action="{{ route('users.update', $user) }}" data-confirm="{{ $user->active ? 'Desativar o acesso de '.$user->name.'?' : 'Reativar o acesso de '.$user->name.'?' }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="active" value="{{ $user->active ? 0 : 1 }}">
                                                <input type="hidden" name="commission_rate" value="{{ $user->commission_rate }}">
                                                <input type="hidden" name="monthly_goal" value="{{ $user->monthly_goal }}">
                                                <button class="btn btn-sm {{ $user->active ? 'btn-outline-danger' : 'btn-outline-primary' }}">{{ $user->active ? 'Desativar' : 'Reativar' }}</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="panel-body border-top small text-muted-2">A comissão é calculada sobre o total de cada venda e fica gravada na venda. Mudar o percentual vale só para as próximas vendas.</div>
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

    <div class="col-xl-4">
        <form method="POST" action="{{ route('users.invite') }}" class="panel">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Convidar pessoa</h2></div>
            <div class="panel-body">
                <x-input name="email" type="email" label="E-mail da pessoa" required placeholder="nome@empresa.com" />
                <x-select name="role" label="Perfil" :options="\App\Enums\UserRole::options()" value="funcionario" required />
                <button class="btn btn-primary w-100">Liberar acesso</button>
                <p class="small text-muted-2 mt-3 mb-0">A pessoa acessa <strong>{{ url('/login') }}</strong> e entra com o Google ou clica em <strong>Criar conta</strong> usando esse e-mail. Ela já cai nesta empresa.</p>
            </div>
            <div class="panel-body border-top small text-muted-2">
                <strong>Administrador</strong> acessa tudo. <strong>Funcionário</strong> registra vendas, clientes, produtos e recebimentos, mas não vê financeiro, relatórios e configurações, nem exclui registros.
            </div>
        </form>
    </div>
</div>
@endsection
