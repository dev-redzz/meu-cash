@extends('layouts.app')

@section('title', $customer->exists ? 'Editar cliente' : 'Novo cliente')

@section('content')
<form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="panel" style="max-width: 860px;">
    @csrf
    @if ($customer->exists) @method('PUT') @endif
    @if (request('redirect'))<input type="hidden" name="redirect" value="{{ request('redirect') }}">@endif
    <div class="panel-body">
        <div class="row gx-3">
            <div class="col-md-8"><x-input name="name" label="Nome" :value="$customer->name" required maxlength="255" /></div>
            <div class="col-md-4"><x-input name="document" label="CPF/CNPJ" :value="$customer->document" maxlength="20" /></div>
            <div class="col-md-4"><x-input name="whatsapp" label="WhatsApp" :value="$customer->whatsapp" placeholder="(99) 99999-9999" maxlength="20" /></div>
            <div class="col-md-4"><x-input name="phone" label="Telefone" :value="$customer->phone" maxlength="20" /></div>
            <div class="col-md-4"><x-input name="email" type="email" label="E-mail" :value="$customer->email" /></div>
            <div class="col-md-4"><x-input name="city" label="Cidade" :value="$customer->city" maxlength="100" /></div>
            <div class="col-md-8"><x-input name="address" label="Endereço" :value="$customer->address" maxlength="255" /></div>
            <div class="col-12"><x-textarea name="notes" label="Observações" :value="$customer->notes" /></div>
        </div>
    </div>
    <div class="panel-header border-top border-bottom-0 justify-content-end">
        <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-primary">{{ $customer->exists ? 'Salvar alterações' : 'Cadastrar cliente' }}</button>
    </div>
</form>
@endsection
