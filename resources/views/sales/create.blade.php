@extends('layouts.app')

@section('title', 'Nova venda')

@section('content')
@php
    $oldItems = old('items');
    if (! $oldItems) {
        $oldItems = [['product_id' => $selectedProduct, 'description' => '', 'quantity' => 1, 'unit_price' => '']];
    }
@endphp
<form method="POST" action="{{ route('sales.store') }}" x-data="saleForm()" x-init="init()" @submit="submitting = true">
    @csrf
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="panel mb-3">
                <div class="panel-header"><h2 class="panel-title">Cliente</h2></div>
                <div class="panel-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label" for="customer_id">Cliente</label>
                            <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" x-model="customer">
                                <option value="">Consumidor final (sem cadastro)</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $selectedCustomer) === (string) $customer->id)>{{ $customer->name }}{{ $customer->whatsapp ? ' · '.$customer->whatsapp : '' }}</option>
                                @endforeach
                            </select>
                            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('customers.create', ['redirect' => 'sale']) }}" class="btn btn-outline-secondary w-100">Cadastrar cliente</a>
                        </div>
                        <div class="col-md-4">
                            <x-input name="sale_date" type="date" label="Data da venda" :value="now()->toDateString()" required class="mb-0" />
                        </div>
                    </div>
                    <div class="form-text" x-show="remaining() > 0 && !customer">Para vender parcelado, selecione um cliente cadastrado para poder cobrar as parcelas.</div>
                </div>
            </div>

            <div class="panel mb-3">
                <div class="panel-header">
                    <h2 class="panel-title">Produtos e itens</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem()">Adicionar item</button>
                </div>
                <div class="panel-body">
                    @error('items')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="border rounded p-2 mb-2">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Produto do estoque</label>
                                    <select class="form-select form-select-sm" :name="`items[${index}][product_id]`" x-model="item.product_id" @change="pickProduct(item)">
                                        <option value="">Item avulso (digitar)</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="String(p.id)" x-text="`${p.name} · ${brl(p.price)} · ${p.stock} em estoque`" :selected="String(p.id) === String(item.product_id)"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="col-md-3" x-show="!item.product_id">
                                    <label class="form-label">Descrição</label>
                                    <input type="text" class="form-control form-control-sm" :name="`items[${index}][description]`" x-model="item.description" maxlength="255" placeholder="Ex.: capinha">
                                </div>
                                <div class="col-4 col-md-1">
                                    <label class="form-label">Qtd.</label>
                                    <input type="number" min="1" class="form-control form-control-sm" :name="`items[${index}][quantity]`" x-model.number="item.quantity" :max="maxQty(item)">
                                </div>
                                <div class="col-8 col-md-2">
                                    <label class="form-label">Preço un.</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" :name="`items[${index}][unit_price]`" x-model="item.unit_price" placeholder="0,00">
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-sm btn-link text-danger" @click="removeItem(index)" x-show="items.length > 1" aria-label="Remover item">Remover</button>
                                </div>
                            </div>
                            <div class="small text-muted-2 mt-1" x-show="item.product_id">
                                Subtotal <span x-text="brl(lineTotal(item))"></span> · lucro estimado <span x-text="brl(lineTotal(item) - costOf(item))"></span>
                            </div>
                            @foreach (array_keys(old('items', [])) as $i)
                                @error("items.$i.product_id")<div class="text-danger small" x-show="index === {{ $i }}">{{ $message }}</div>@enderror
                                @error("items.$i.description")<div class="text-danger small" x-show="index === {{ $i }}">{{ $message }}</div>@enderror
                            @endforeach
                        </div>
                    </template>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><h2 class="panel-title">Pagamento</h2></div>
                <div class="panel-body">
                    <div class="row gx-3">
                        <div class="col-md-4">
                            <label class="form-label" for="payment_method">Forma de pagamento</label>
                            <select name="payment_method" id="payment_method" class="form-select" x-model="method" @change="methodChanged()">
                                @foreach ($methods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <x-input name="discount" label="Desconto" inputmode="decimal" placeholder="0,00" x-model="discount" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="down_payment">Valor pago agora (entrada)</label>
                            <input type="text" name="down_payment" id="down_payment" inputmode="decimal" class="form-control @error('down_payment') is-invalid @enderror" x-model="down" placeholder="0,00">
                            @error('down_payment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                <a href="#" @click.prevent="down = format(total())">Recebi o total</a> ·
                                <a href="#" @click.prevent="down = '0,00'">Nada agora</a>
                            </div>
                        </div>
                        <div class="col-md-4" x-show="toNumber(down) > 0 && method === 'parcelado'">
                            <label class="form-label" for="down_payment_method">Entrada recebida em</label>
                            <select name="down_payment_method" id="down_payment_method" class="form-select">
                                @foreach ($receivingMethods as $value => $label)
                                    <option value="{{ $value }}" @selected($value === 'pix')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="border rounded p-3 bg-light" x-show="remaining() > 0">
                        <div class="fw-semibold mb-2">Restante de <span x-text="brl(remaining())"></span> em parcelas</div>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label" for="installments_count">Parcelas</label>
                                <input type="number" min="1" max="60" name="installments_count" id="installments_count" class="form-control" x-model.number="count">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="first_due_date">1º vencimento</label>
                                <input type="date" name="first_due_date" id="first_due_date" class="form-control @error('first_due_date') is-invalid @enderror" x-model="firstDue">
                                @error('first_due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="interval_days">Intervalo</label>
                                <select name="interval_days" id="interval_days" class="form-select" x-model="interval">
                                    <option value="0">Mensal (mesmo dia)</option>
                                    <option value="7">Semanal</option>
                                    <option value="15">Quinzenal</option>
                                    <option value="30">A cada 30 dias</option>
                                </select>
                            </div>
                        </div>
                        <div class="small text-muted-2 mt-2" x-text="`${count}x de ${brl(remaining() / Math.max(1, count || 1))}`"></div>
                    </div>

                    <x-textarea name="notes" label="Observações" rows="2" class="mt-3" />
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="panel position-sticky" style="top: 80px;">
                <div class="panel-header"><h2 class="panel-title">Resumo</h2></div>
                <div class="panel-body">
                    <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span class="num" x-text="brl(subtotal())"></span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Desconto</span><span class="num" x-text="'− ' + brl(toNumber(discount))"></span></div>
                    <div class="d-flex justify-content-between fs-5 fw-bold border-top pt-2 mt-2"><span>Total</span><span class="num" x-text="brl(total())"></span></div>
                    <div class="d-flex justify-content-between mt-2"><span>Recebido agora</span><span class="num text-positive" x-text="brl(Math.min(toNumber(down), total()))"></span></div>
                    <div class="d-flex justify-content-between"><span>A receber</span><span class="num text-negative" x-text="brl(remaining())"></span></div>
                    <div class="d-flex justify-content-between small text-muted-2 mt-2"><span>Lucro estimado</span><span class="num" x-text="brl(total() - totalCost())"></span></div>
                </div>
                <div class="panel-header border-top border-bottom-0">
                    <a href="{{ route('sales.index') }}" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-primary" :disabled="submitting || total() <= 0">Confirmar venda</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function saleForm() {
    return {
        products: @json($products),
        items: @json(array_values($oldItems)).map((item, i) => ({
            key: i + 1,
            product_id: item.product_id ? String(item.product_id) : '',
            description: item.description || '',
            quantity: Number(item.quantity) || 1,
            unit_price: item.unit_price ?? '',
        })),
        customer: @json((string) old('customer_id', $selectedCustomer ?? '')),
        method: @json(old('payment_method', 'pix')),
        discount: @json(old('discount', '')),
        down: @json(old('down_payment', '')),
        count: Number(@json(old('installments_count', 1))) || 1,
        firstDue: @json(old('first_due_date', now()->addMonthNoOverflow()->toDateString())),
        interval: @json((string) old('interval_days', '0')),
        submitting: false,
        nextKey: 100,
        init() {
            this.items.forEach(item => { if (item.product_id && item.unit_price === '') this.pickProduct(item); });
            if (this.down === '' || this.down === null) this.down = this.method === 'parcelado' ? '0,00' : this.format(this.total());
            this.$watch('items', () => this.syncDown(), { deep: true });
            this.$watch('discount', () => this.syncDown());
        },
        brl(v) { return window.brl(v); },
        toNumber(v) { return window.parseMoney(v); },
        format(v) { return (Number(v) || 0).toFixed(2).replace('.', ','); },
        product(id) { return this.products.find(p => String(p.id) === String(id)); },
        pickProduct(item) {
            const p = this.product(item.product_id);
            if (p) { item.unit_price = this.format(p.price); item.description = ''; item.quantity = Math.min(item.quantity || 1, p.stock); }
        },
        maxQty(item) { const p = this.product(item.product_id); return p ? p.stock : 10000; },
        lineTotal(item) { return this.toNumber(item.unit_price) * (Number(item.quantity) || 0); },
        costOf(item) { const p = this.product(item.product_id); return p ? p.cost * (Number(item.quantity) || 0) : 0; },
        subtotal() { return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0); },
        totalCost() { return this.items.reduce((sum, item) => sum + this.costOf(item), 0); },
        total() { return Math.max(0, this.subtotal() - this.toNumber(this.discount)); },
        remaining() { return Math.max(0, Math.round((this.total() - this.toNumber(this.down)) * 100) / 100); },
        addItem() { this.items.push({ key: this.nextKey++, product_id: '', description: '', quantity: 1, unit_price: '' }); },
        removeItem(i) { this.items.splice(i, 1); },
        methodChanged() { this.down = this.method === 'parcelado' ? '0,00' : this.format(this.total()); },
        syncDown() { if (this.method !== 'parcelado') this.down = this.format(this.total()); },
    };
}
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush
