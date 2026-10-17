@extends('layouts.app')

@section('title', 'Editar venda '.$sale->code)
@section('subtitle', 'Troque o cliente, a data ou as observações desta venda.')

@section('content')
<form method="POST" action="{{ route('sales.update', $sale) }}" class="form-page">
    @csrf @method('PUT')
    <div class="form-grid">
        <div class="form-main">
            <div class="callout">
                <x-icon name="info" width="18" height="18" />
                <span>Itens, valores e parcelas não são alterados por aqui. Para corrigir valores, cancele a venda e registre outra.</span>
            </div>
            <x-form-section title="Dados da venda" icon="cart">
                <div class="row gx-3">
                    <div class="col-md-8"><x-select name="customer_id" label="Cliente" :options="$customers->pluck('name', 'id')" :value="$sale->customer_id" placeholder="Consumidor final" /></div>
                    <div class="col-md-4"><x-input name="sale_date" type="date" label="Data da venda" :value="$sale->sale_date->toDateString()" required /></div>
                    <div class="col-12"><x-textarea name="notes" label="Observações" :value="$sale->notes" rows="3" /></div>
                </div>
            </x-form-section>
        </div>
    </div>
    <div class="form-footer">
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-light">Cancelar</a>
        <button class="btn btn-cta"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>Salvar alterações</button>
    </div>
</form>
@endsection
