@extends('layouts.app')

@section('title', $customer->exists ? 'Editar cliente' : 'Novo cliente')
@section('subtitle', $customer->exists ? 'Atualize os dados de '.$customer->name.'.' : 'Só o nome é obrigatório. O resto ajuda a cobrar e enviar comprovantes.')

@section('content')
<form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="form-page">
    @csrf
    @if ($customer->exists) @method('PUT') @endif
    @if (request('redirect'))<input type="hidden" name="redirect" value="{{ request('redirect') }}">@endif

    <div class="form-grid">
        <div class="form-main">
            <x-form-section title="Identificação" icon="user" description="Como o cliente aparece nas vendas e no comprovante.">
                <div class="row gx-3">
                    <div class="col-md-8"><x-input name="name" label="Nome completo" :value="$customer->name" required maxlength="255" placeholder="Ex.: Ana Paula Ribeiro" autofocus /></div>
                    <div class="col-md-4"><x-input name="document" label="CPF/CNPJ" :value="$customer->document" maxlength="20" placeholder="000.000.000-00" data-mask="document" inputmode="numeric" /></div>
                </div>
            </x-form-section>

            <x-form-section title="Contato" icon="phone" accent="green" description="O WhatsApp é usado para lembretes de parcela e para enviar o acesso à Área do cliente.">
                <div class="row gx-3">
                    <div class="col-md-4"><x-input name="whatsapp" label="WhatsApp" :value="$customer->whatsapp" placeholder="(99) 99999-9999" maxlength="20" data-mask="phone" inputmode="tel" /></div>
                    <div class="col-md-4"><x-input name="phone" label="Outro telefone" :value="$customer->phone" maxlength="20" placeholder="(99) 9999-9999" data-mask="phone" inputmode="tel" /></div>
                    <div class="col-md-4"><x-input name="email" type="email" label="E-mail" :value="$customer->email" placeholder="nome@email.com" /></div>
                </div>
            </x-form-section>

            <x-form-section title="Endereço" icon="map-pin" accent="amber">
                <div class="row gx-3">
                    <div class="col-md-4"><x-input name="city" label="Cidade" :value="$customer->city" maxlength="100" placeholder="Ex.: Caxias - MA" /></div>
                    <div class="col-md-8"><x-input name="address" label="Rua, número e bairro" :value="$customer->address" maxlength="255" /></div>
                </div>
            </x-form-section>

            <x-form-section title="Observações" icon="note" accent="slate">
                <x-textarea name="notes" :value="$customer->notes" rows="3" placeholder="Preferências, referências, combinados..." />
            </x-form-section>
        </div>
    </div>

    <div class="form-footer">
        <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>{{ $customer->exists ? 'Salvar alterações' : 'Cadastrar cliente' }}</button>
    </div>
</form>
@endsection
