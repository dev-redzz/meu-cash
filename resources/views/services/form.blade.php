@extends('layouts.app')

@section('title', $service->exists ? 'Editar serviço' : 'Novo serviço')

@section('content')
<form method="POST" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}" class="row g-3">
    @csrf
    @if ($service->exists) @method('PUT') @endif
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Serviço</h2></div>
            <div class="panel-body">
                <div class="row gx-3">
                    <div class="col-md-8"><x-input name="name" label="Nome do serviço" :value="$service->name" required maxlength="255" placeholder="Ex.: Site institucional" /></div>
                    <div class="col-md-4"><x-select name="category_id" label="Categoria" :options="$categories->pluck('name', 'id')" :value="$service->category_id" placeholder="Sem categoria" /></div>
                    <div class="col-md-8"><x-select name="customer_id" label="Cliente" :options="$customers->pluck('name', 'id')" :value="$service->customer_id" placeholder="Sem cliente" /></div>
                    <div class="col-md-4"><x-input name="date" type="date" label="Data" :value="$service->date?->toDateString()" required /></div>
                    <div class="col-md-6"><x-select name="status" label="Status" :options="\App\Enums\ServiceStatus::options()" :value="$service->status" required /></div>
                    <div class="col-12"><x-textarea name="notes" label="Observações" :value="$service->notes" /></div>
                </div>
                <div class="form-text">Serviços com status "Orçamento" ou "Cancelado" não entram no faturamento.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header"><h2 class="panel-title">Valor e pagamento</h2></div>
            <div class="panel-body">
                @if ($service->exists)
                    <p class="mb-1">Valor: <strong>{{ money($service->amount) }}</strong></p>
                    <p class="small text-muted-2 mb-0">O valor e as parcelas não mudam pela edição. Para corrigir, cancele o serviço e registre outro.</p>
                @else
                    <x-input name="amount" label="Valor do serviço" inputmode="decimal" placeholder="0,00" required />
                    <x-select name="payment_method" label="Forma de pagamento" :options="$methods" value="pix" required />
                    <x-input name="down_payment" label="Valor pago agora (entrada)" inputmode="decimal" placeholder="0,00" help="Em branco: à vista recebe o valor total; parcelado não recebe nada agora. Digite 0 para cobrar tudo depois." />
                    <div class="row gx-3">
                        <div class="col-5"><x-input name="installments_count" type="number" min="1" max="60" label="Parcelas" :value="1" /></div>
                        <div class="col-7"><x-input name="first_due_date" type="date" label="1º vencimento" :value="now()->addMonthNoOverflow()->toDateString()" /></div>
                    </div>
                    <div class="form-text mt-0">Parcelas são criadas só se sobrar valor depois da entrada.</div>
                @endif
            </div>
            <div class="panel-header border-top border-bottom-0 justify-content-end">
                <a href="{{ $service->exists ? route('services.show', $service) : route('services.index') }}" class="btn btn-light">Cancelar</a>
                <button class="btn btn-primary">{{ $service->exists ? 'Salvar alterações' : 'Registrar serviço' }}</button>
            </div>
        </div>
    </div>
</form>
@endsection
