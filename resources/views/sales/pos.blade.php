@extends('layouts.app')

@section('title', 'Venda rápida')
@section('subtitle', 'Bipe o código de barras ou toque no produto. Para vender parcelado, use a venda completa.')

@section('actions')
    <a href="{{ route('sales.create') }}" class="btn btn-cta btn-cta-light"><span class="btn-cta-icon"><x-icon name="cart" width="16" height="16" /></span>Venda completa</a>
@endsection

@section('content')
@if ($lastSale)
    <div class="pos-last">
        <span class="stat-icon accent-green"><x-icon name="check" width="18" height="18" /></span>
        <div class="min-w-0">
            <strong>Venda {{ $lastSale->code }} registrada · {{ money($lastSale->total) }}</strong>
            <span>{{ $lastSale->payment_method->label() }}{{ $lastSale->customer ? ' · '.$lastSale->customer->name : '' }}</span>
        </div>
        <div class="pos-last-actions">
            <a href="{{ route('receipts.show', $lastSale) }}" target="_blank" class="btn btn-sm btn-light"><x-icon name="receipt" width="15" height="15" /> Comprovante</a>
            <a href="{{ route('sales.show', $lastSale) }}" class="btn btn-sm btn-light">Ver venda</a>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('sales.store') }}" class="pos" x-data="pos()" @submit="submit($event)">
    @csrf
    <input type="hidden" name="origin" value="pdv">
    <input type="hidden" name="sale_date" value="{{ now()->toDateString() }}">
    <input type="hidden" name="payment_method" :value="method">
    <template x-for="(item, i) in cart" :key="item.id">
        <div>
            <input type="hidden" :name="`items[${i}][product_id]`" :value="item.id">
            <input type="hidden" :name="`items[${i}][quantity]`" :value="item.qty">
            <input type="hidden" :name="`items[${i}][unit_price]`" :value="item.price.toFixed(2)">
            <input type="hidden" :name="`items[${i}][unit_cost]`" :value="item.cost.toFixed(2)">
        </div>
    </template>

    <div class="pos-grid">
        <section class="panel pos-catalog">
            <div class="pos-search">
                <x-icon name="search" width="20" height="20" />
                <input type="search" x-ref="search" x-model="q" @keydown.enter.prevent="enter()" @keydown.escape="q = ''" placeholder="Bipe o código de barras ou busque pelo nome" aria-label="Buscar produto" autocomplete="off" autofocus>
                <kbd class="d-none d-lg-inline">F2</kbd>
            </div>
            <div class="pos-feedback" x-show="message" x-text="message" :class="messageError ? 'is-error' : ''" x-cloak></div>

            <div class="pos-products">
                <template x-for="p in filtered()" :key="p.id">
                    <button type="button" class="pos-product" @click="add(p)" :class="inCart(p) ? 'is-in-cart' : ''">
                        <span class="pos-product-media">
                            <template x-if="p.photo"><img :src="p.photo" alt="" loading="lazy" onerror="this.remove()"></template>
                            <span class="pos-product-initial" x-text="p.name.charAt(0)"></span>
                        </span>
                        <span class="pos-product-name" x-text="p.name"></span>
                        <span class="pos-product-foot">
                            <strong x-text="brl(p.price)"></strong>
                            <small x-text="p.stock + ' em estoque'"></small>
                        </span>
                        <span class="pos-product-qty" x-show="inCart(p)" x-text="qtyOf(p)"></span>
                    </button>
                </template>
            </div>
            <div class="pos-empty" x-show="filtered().length === 0" x-cloak>
                @if ($products->isEmpty())
                    Nenhum produto disponível para venda. <a href="{{ route('products.create') }}">Cadastre um produto</a>.
                @else
                    Nenhum produto encontrado para "<span x-text="q"></span>".
                @endif
            </div>
        </section>

        <aside class="panel pos-cart" id="carrinho">
            <header class="pos-cart-head">
                <h2 class="panel-title mb-0">Carrinho <span class="pos-count" x-show="count() > 0" x-text="count()"></span></h2>
                <button type="button" class="btn btn-sm btn-link text-negative p-0" x-show="cart.length" @click="clear()">Limpar</button>
            </header>

            <div class="pos-lines">
                <template x-for="item in cart" :key="item.id">
                    <div class="pos-line">
                        <div class="min-w-0">
                            <div class="pos-line-name" x-text="item.name"></div>
                            <div class="pos-line-unit" x-text="brl(item.price) + ' cada'"></div>
                        </div>
                        <div class="pos-stepper">
                            <button type="button" @click="dec(item)" :aria-label="'Diminuir ' + item.name"><x-icon name="minus" width="14" height="14" /></button>
                            <span x-text="item.qty"></span>
                            <button type="button" @click="inc(item)" :disabled="item.qty >= item.stock" :aria-label="'Aumentar ' + item.name"><x-icon name="plus" width="14" height="14" /></button>
                        </div>
                        <div class="pos-line-total" x-text="brl(item.price * item.qty)"></div>
                    </div>
                </template>
                <div class="pos-cart-empty" x-show="cart.length === 0">
                    <x-icon name="cart" width="28" height="28" />
                    <span>Bipe ou toque em um produto para começar.</span>
                </div>
            </div>

            <div class="pos-checkout">
                <div class="row g-2">
                    <div class="col-7">
                        <label class="form-label" for="pos_customer">Cliente</label>
                        <select name="customer_id" id="pos_customer" class="form-select">
                            <option value="">Consumidor final</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label" for="pos_discount">Desconto</label>
                        <input type="text" name="discount" id="pos_discount" class="form-control" inputmode="decimal" placeholder="0,00" x-model="discountText" autocomplete="off">
                    </div>
                </div>

                <div class="pos-totals">
                    <div><span>Subtotal</span><span x-text="brl(subtotal())"></span></div>
                    <div x-show="discount() > 0"><span>Desconto</span><span class="text-negative" x-text="'− ' + brl(discount())"></span></div>
                    <div class="pos-total"><span>Total</span><strong x-text="brl(total())"></strong></div>
                </div>

                <div class="pos-methods" role="radiogroup" aria-label="Forma de pagamento">
                    @foreach ($methods as $value => $label)
                        <button type="button" role="radio" :aria-checked="method === '{{ $value }}'" :class="method === '{{ $value }}' ? 'is-active' : ''" @click="method = '{{ $value }}'">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="pos-cash" x-show="method === 'dinheiro'" x-cloak>
                    <div class="flex-grow-1">
                        <label class="form-label" for="pos_received">Valor recebido</label>
                        <input type="text" id="pos_received" class="form-control" inputmode="decimal" placeholder="0,00" x-model="receivedText" autocomplete="off">
                    </div>
                    <div class="pos-change">
                        <span>Troco</span>
                        <strong :class="change() < 0 ? 'text-negative' : ''" x-text="received() > 0 ? brl(Math.max(0, change())) : '—'"></strong>
                    </div>
                </div>
                <div class="form-text text-negative" x-show="method === 'dinheiro' && received() > 0 && change() < 0" x-cloak>O valor recebido é menor que o total.</div>

                <button type="submit" class="btn btn-cta w-100 pos-finish" :disabled="!canFinish()">
                    <span class="btn-cta-icon"><x-icon name="check" width="16" height="16" /></span>
                    <span x-text="sending ? 'Registrando...' : 'Finalizar venda · ' + brl(total())"></span>
                    <kbd class="ms-auto d-none d-lg-inline">F9</kbd>
                </button>
            </div>
        </aside>
    </div>

    <a href="#carrinho" class="pos-mobile-bar d-lg-none" x-show="cart.length" x-cloak>
        <span><strong x-text="count() + (count() === 1 ? ' item' : ' itens')"></strong> · <span x-text="brl(total())"></span></span>
        <span>Ver carrinho</span>
    </a>
</form>
@endsection

@push('scripts')
<script>
function pos() {
    return {
        products: @json($products),
        cart: [],
        q: '',
        method: 'dinheiro',
        discountText: '',
        receivedText: '',
        message: '',
        messageError: false,
        sending: false,
        init() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'F2') { e.preventDefault(); this.$refs.search.focus(); this.$refs.search.select(); }
                if (e.key === 'F9') { e.preventDefault(); if (this.canFinish()) this.$root.requestSubmit(); }
            });
        },
        norm(t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },
        filtered() {
            const q = this.norm(this.q.trim());
            const list = q ? this.products.filter(p => this.norm(p.name + ' ' + (p.brand || '') + ' ' + (p.category || '')).includes(q) || (p.barcode && p.barcode === this.q.trim())) : this.products;
            return list.slice(0, 60);
        },
        enter() {
            const code = this.q.trim();
            if (!code) return;
            const exact = this.products.find(p => p.barcode && p.barcode === code);
            const list = this.filtered();
            const pick = exact || (list.length === 1 ? list[0] : null);
            if (pick) { this.add(pick); this.q = ''; }
            else this.say(list.length ? 'Mais de um produto encontrado. Toque no certo.' : 'Nenhum produto com esse código.', true);
        },
        add(p) {
            const line = this.cart.find(i => i.id === p.id);
            if (line) {
                if (line.qty >= p.stock) { this.say('Só há ' + p.stock + ' em estoque de ' + p.name + '.', true); return; }
                line.qty++;
            } else {
                if (p.stock < 1) { this.say(p.name + ' está sem estoque.', true); return; }
                this.cart.push({ id: p.id, name: p.name, price: p.price, cost: p.cost, stock: p.stock, qty: 1 });
            }
            this.say(p.name + ' adicionado.', false);
        },
        inc(item) { if (item.qty < item.stock) item.qty++; },
        dec(item) { item.qty > 1 ? item.qty-- : this.cart.splice(this.cart.indexOf(item), 1); },
        clear() { this.cart = []; this.discountText = ''; this.receivedText = ''; },
        inCart(p) { return this.cart.some(i => i.id === p.id); },
        qtyOf(p) { const l = this.cart.find(i => i.id === p.id); return l ? l.qty : 0; },
        count() { return this.cart.reduce((s, i) => s + i.qty, 0); },
        subtotal() { return this.cart.reduce((s, i) => s + i.price * i.qty, 0); },
        discount() { return Math.min(parseMoney(this.discountText), this.subtotal()); },
        total() { return Math.max(0, this.subtotal() - this.discount()); },
        received() { return parseMoney(this.receivedText); },
        change() { return this.received() - this.total(); },
        canFinish() {
            if (!this.cart.length || this.sending) return false;
            if (this.method === 'dinheiro' && this.received() > 0 && this.change() < -0.001) return false;
            return true;
        },
        say(text, error) {
            this.message = text; this.messageError = error;
            clearTimeout(this._t); this._t = setTimeout(() => this.message = '', 2500);
        },
        submit(e) {
            if (!this.canFinish()) { e.preventDefault(); return; }
            this.sending = true;
        },
    };
}
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush
