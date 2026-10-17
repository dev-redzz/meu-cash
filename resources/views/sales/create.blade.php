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
                <div class="form-section-head"><span class="stat-icon accent-blue"><x-icon name="user" width="18" height="18" /></span><div class="min-w-0"><h2 class="panel-title">Cliente</h2><p class="form-section-desc">Quem está comprando e quando.</p></div></div>
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
                            <a href="{{ route('customers.create', ['redirect' => 'sale']) }}" class="btn btn-cta btn-cta-light w-100 justify-content-start"><span class="btn-cta-icon"><x-icon name="plus" width="16" height="16" /></span>Cadastrar cliente</a>
                        </div>
                        <div class="col-md-4">
                            <x-input name="sale_date" type="date" label="Data da venda" :value="now()->toDateString()" required class="mb-0" />
                        </div>
                        @if (auth()->user()->isAdmin() && $sellers->count() > 1)
                            <div class="col-md-4">
                                <x-select name="user_id" label="Vendedor" :options="$sellers->pluck('name', 'id')" :value="auth()->id()" class="mb-0" />
                            </div>
                        @endif
                    </div>
                    <div class="form-text" x-show="remaining() > 0 && !customer">Para vender parcelado, selecione um cliente cadastrado para poder cobrar as parcelas.</div>
                </div>
            </div>

            <div class="panel mb-3">
                <div class="form-section-head"><span class="stat-icon accent-violet"><x-icon name="box" width="18" height="18" /></span><div class="min-w-0"><h2 class="panel-title">Produtos e itens</h2><p class="form-section-desc">Bipe o código de barras ou escolha da lista.</p></div>
                    <div class="ms-auto">
                    <button type="button" class="btn btn-cta btn-cta-light btn-cta-sm" @click="addItem()"><span class="btn-cta-icon"><x-icon name="plus" width="14" height="14" /></span>Adicionar item</button>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="scan-bar mb-3">
                        <label class="form-label" for="barcode_scan">Código de barras</label>
                        <div class="input-group">
                            <input type="text" id="barcode_scan" class="form-control" placeholder="Bipe o produto ou digite o código e tecle Enter" autocomplete="off"
                                x-model="scanCode" @keydown.enter.prevent="scan(scanCode)">
                            <button type="button" class="btn btn-light" x-show="cameraSupported" @click="openCamera()">Câmera</button>
                        </div>
                        <div class="form-text" :class="scanError ? 'text-negative' : 'text-positive'" x-show="scanMessage" x-text="scanMessage"></div>
                        <div class="scan-camera mt-2" x-show="cameraOpen">
                            <video x-ref="video" playsinline muted></video>
                            <button type="button" class="btn btn-sm btn-light mt-2" @click="closeCamera()">Fechar câmera</button>
                        </div>
                    </div>
                    @error('items')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="line-item">
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
                                    <button type="button" class="icon-btn icon-btn-danger line-item-remove" @click="removeItem(index)" x-show="items.length > 1" title="Remover item" aria-label="Remover item"><x-icon name="trash" width="16" height="16" /></button>
                                </div>
                            </div>
                            <div class="line-item-foot" x-show="item.product_id">
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
                <div class="form-section-head"><span class="stat-icon accent-green"><x-icon name="wallet" width="18" height="18" /></span><div class="min-w-0"><h2 class="panel-title">Pagamento</h2><p class="form-section-desc">Forma de pagamento, entrada e parcelas.</p></div></div>
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
                            <div class="quick-chips">
                                <button type="button" class="chip" @click="down = format(total())">Recebi o total</button>
                                <button type="button" class="chip" @click="down = '0,00'">Nada agora</button>
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

                    <div class="installments-box" x-show="remaining() > 0">
                        <div class="installments-box-title"><x-icon name="calendar" width="16" height="16" /> Restante de <strong x-text="brl(remaining())"></strong> em parcelas</div>
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
                        <div class="installments-box-foot" x-text="`${count}x de ${brl(remaining() / Math.max(1, count || 1))}`"></div>
                    </div>

                    <div class="mt-3"><x-textarea name="notes" label="Observações" rows="2" /></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="panel position-sticky" style="top: 80px;">
                <div class="form-section-head"><span class="stat-icon accent-amber"><x-icon name="receipt" width="18" height="18" /></span><div class="min-w-0"><h2 class="panel-title">Resumo</h2></div></div>
                <div class="panel-body">
                    <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span class="num" x-text="brl(subtotal())"></span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Desconto</span><span class="num" x-text="'− ' + brl(toNumber(discount))"></span></div>
                    <div class="summary-total"><span>Total</span><span class="num" x-text="brl(total())"></span></div>
                    <div class="d-flex justify-content-between mt-2"><span>Recebido agora</span><span class="num text-positive" x-text="brl(Math.min(toNumber(down), total()))"></span></div>
                    <div class="d-flex justify-content-between"><span>A receber</span><span class="num text-negative" x-text="brl(remaining())"></span></div>
                    <div class="d-flex justify-content-between small text-muted-2 mt-2"><span>Lucro estimado</span><span class="num" x-text="brl(total() - totalCost())"></span></div>
                </div>
                <div class="summary-actions">
                    <button type="submit" class="btn btn-cta w-100 justify-content-start" :disabled="submitting || total() <= 0"><span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>Confirmar venda</button>
                    <a href="{{ route('sales.index') }}" class="btn btn-light w-100">Cancelar</a>
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
        scanCode: '',
        scanMessage: '',
        scanError: false,
        cameraOpen: false,
        cameraSupported: 'BarcodeDetector' in window && !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
        stream: null,
        scan(code) {
            code = String(code || '').trim();
            if (!code) return;
            const p = this.products.find(x => x.barcode && x.barcode === code);
            this.scanCode = '';
            if (!p) { this.scanError = true; this.scanMessage = `Nenhum produto disponível com o código ${code}.`; return; }
            let item = this.items.find(i => String(i.product_id) === String(p.id));
            if (item) {
                if (item.quantity >= p.stock) { this.scanError = true; this.scanMessage = `${p.name}: só há ${p.stock} em estoque.`; return; }
                item.quantity++;
            } else {
                item = this.items.find(i => !i.product_id && !i.description && !i.unit_price);
                if (!item) { this.addItem(); item = this.items[this.items.length - 1]; }
                item.product_id = String(p.id);
                item.quantity = 1;
                this.pickProduct(item);
            }
            this.scanError = false;
            this.scanMessage = `${p.name} adicionado (${item.quantity} un.).`;
        },
        async openCamera() {
            try {
                const detector = new BarcodeDetector();
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                this.cameraOpen = true;
                const video = this.$refs.video;
                video.srcObject = this.stream;
                await video.play();
                let last = '';
                const loop = async () => {
                    if (!this.cameraOpen) return;
                    try {
                        const codes = await detector.detect(video);
                        if (codes.length && codes[0].rawValue !== last) {
                            last = codes[0].rawValue;
                            this.scan(last);
                            setTimeout(() => { last = ''; }, 1500);
                        }
                    } catch (e) {}
                    requestAnimationFrame(loop);
                };
                loop();
            } catch (e) {
                this.scanError = true;
                this.scanMessage = 'Não foi possível abrir a câmera. Verifique a permissão do navegador.';
                this.closeCamera();
            }
        },
        closeCamera() {
            this.cameraOpen = false;
            if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
        },
        addItem() { this.items.push({ key: this.nextKey++, product_id: '', description: '', quantity: 1, unit_price: '' }); },
        removeItem(i) { this.items.splice(i, 1); },
        methodChanged() { this.down = this.method === 'parcelado' ? '0,00' : this.format(this.total()); },
        syncDown() { if (this.method !== 'parcelado') this.down = this.format(this.total()); },
    };
}
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush
