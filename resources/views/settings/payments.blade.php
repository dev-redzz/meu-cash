@extends('layouts.app')

@section('title', 'Configurações')

@section('content')
@include('settings._tabs')
<div class="row g-3">
    <div class="col-lg-6">
        <section class="panel">
            <header class="form-section-head">
                <span class="stat-icon accent-{{ $connected ? 'green' : 'blue' }}"><x-icon name="pix" width="18" height="18" /></span>
                <div class="min-w-0">
                    <h2 class="panel-title">Pix na área do cliente</h2>
                    <p class="form-section-desc">Seus clientes pagam as parcelas pelo Pix e a baixa acontece sozinha. O dinheiro cai na sua conta do Mercado Pago.</p>
                </div>
            </header>
            <div class="panel-body">
                @if ($connected)
                    <div class="system-status mb-3">
                        <strong class="text-positive">Conectado{{ ($account['test'] ?? false) ? ' (modo de teste)' : '' }}</strong>
                        <span>{{ $account['name'] ?? 'Conta Mercado Pago' }}@if (! empty($account['email'])) · {{ $account['email'] }}@endif</span>
                        @if (! empty($account['at']))<span class="small text-muted-2">Desde {{ \Illuminate\Support\Carbon::parse($account['at'])->format('d/m/Y H:i') }}</span>@endif
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.payments.update') }}">
                    @csrf @method('PUT')
                    <x-input name="access_token" type="password" :label="$connected ? 'Trocar o Access Token' : 'Access Token do Mercado Pago'" placeholder="APP_USR-..." autocomplete="off" required
                             help="Fica guardado criptografado. Ninguém da equipe consegue ver depois de salvo." />
                    <button class="btn btn-primary">{{ $connected ? 'Trocar token' : 'Conectar' }}</button>
                </form>

                @if ($connected)
                    <form method="POST" action="{{ route('settings.payments.destroy') }}" class="mt-3" data-confirm="Desconectar o Mercado Pago? O botão de Pix some da área do cliente.">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Desconectar</button>
                    </form>
                @endif
            </div>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Como pegar o Access Token</h2></div>
            <div class="panel-body">
                <ol class="howto">
                    <li>Entre em <a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank" rel="noopener">mercadopago.com.br/developers</a> com a conta que vai receber o dinheiro.</li>
                    <li>Clique em <strong>Criar aplicação</strong>, dê um nome (ex.: "Meu Cash") e escolha <strong>Pagamentos online</strong>.</li>
                    <li>No menu da aplicação, abra <strong>Credenciais de produção</strong> e copie o <strong>Access Token</strong> (começa com <code>APP_USR-</code>).</li>
                    <li>Cole aqui e clique em <strong>Conectar</strong>.</li>
                </ol>
                <p class="small text-muted-2 mb-0">Para testar sem dinheiro de verdade, use o token das <strong>Credenciais de teste</strong> (começa com <code>TEST-</code>). Precisa ter uma chave Pix cadastrada na conta do Mercado Pago. O Mercado Pago cobra uma taxa por Pix recebido; confira o valor no site deles.</p>
            </div>
        </section>
    </div>

    @if ($recent->isNotEmpty())
        <div class="col-12">
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Últimos Pix gerados pelos clientes</h2></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Cliente</th><th>Parcela</th><th class="num">Valor</th><th>Gerado</th><th>Situação</th></tr></thead>
                        <tbody>
                            @foreach ($recent as $op)
                                <tr>
                                    <td>{{ $op->installment?->customer?->name ?? '—' }}</td>
                                    <td class="small">@if ($op->installment)<a href="{{ $op->installment->originUrl() }}">{{ $op->installment->label() }} · {{ $op->installment->originLabel() }}</a>@endif</td>
                                    <td class="num">{{ money($op->amount) }}</td>
                                    <td class="small">{{ $op->created_at->format('d/m H:i') }}</td>
                                    <td>
                                        @php $st = $op->status === 'pendente' && $op->expires_at?->isPast() ? 'expirado' : $op->status; @endphp
                                        <span class="pill {{ ['pago' => 'pill-success', 'pendente' => 'pill-warning', 'cancelado' => 'pill-danger'][$st] ?? 'pill-secondary' }}">{{ ['pago' => 'Pago', 'pendente' => 'Aguardando', 'expirado' => 'Expirou', 'cancelado' => 'Cancelado', 'erro' => 'Erro'][$st] ?? $st }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif
</div>
@endsection
