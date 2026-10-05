@extends('layouts.app')

@section('title', 'Editar venda '.$sale->code)

@section('content')
<form method="POST" action="{{ route('sales.update', $sale) }}" class="panel" style="max-width: 720px;">
    @csrf @method('PUT')
    <div class="panel-body">
        <p class="small text-muted-2">Itens, valores e parcelas não são alterados por aqui. Para corrigir valores, cancele a venda e registre outra.</p>
        <x-select name="customer_id" label="Cliente" :options="$customers->pluck('name', 'id')" :value="$sale->customer_id" placeholder="Consumidor final" />
        <x-input name="sale_date" type="date" label="Data da venda" :value="$sale->sale_date->toDateString()" required />
        <x-textarea name="notes" label="Observações" :value="$sale->notes" />
    </div>
    <div class="panel-header border-top border-bottom-0 justify-content-end">
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-light">Cancelar</a>
        <button class="btn btn-primary">Salvar alterações</button>
    </div>
</form>
@endsection
