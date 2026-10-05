@extends('layouts.app')

@section('title', 'Clientes')

@section('actions')
    <a href="{{ route('customers.create') }}" class="btn btn-primary">Novo cliente</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-header">
        <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width: 420px;" data-no-lock>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Buscar por nome, CPF/CNPJ, telefone ou e-mail">
            <button class="btn btn-sm btn-outline-secondary">Buscar</button>
        </form>
        <span class="small text-muted-2">{{ $customers->total() }} clientes</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>WhatsApp / Telefone</th>
                    <th>Cidade</th>
                    <th class="num">Em aberto</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <a href="{{ route('customers.show', $customer) }}" class="fw-semibold">{{ $customer->name }}</a>
                            @if ($customer->document)<div class="small text-muted-2">{{ $customer->document }}</div>@endif
                        </td>
                        <td>{{ $customer->whatsapp ?: $customer->phone ?: '—' }}</td>
                        <td>{{ $customer->city ?: '—' }}</td>
                        <td class="num {{ $customer->open_amount > 0 ? 'text-negative fw-semibold' : 'text-muted-2' }}">{{ money($customer->open_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty message="Nenhum cliente encontrado." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $customers->links() }}</div>
@endsection
