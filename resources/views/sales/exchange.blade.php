@extends('layouts.app')

@section('title', 'Troca · venda '.$sale->code)

@section('subtitle', 'Marque o que o cliente devolveu e bipe o que ele vai levar. O estoque, a venda e o caixa se ajustam sozinhos.')

@section('content')
<form method="POST" action="{{ route('sales.exchange.store', $sale) }}" x-data="exchangeForm()" @submit="submitting = true" class="form-page">
    @csrf

    @if ($hasInvoice)
        <div class="notice-bar is-warning mb-3"><x-icon name="alert" width="18" height="18" /><span>Esta venda tem NFC-e autorizada. A troca não altera a nota: confira com o seu contador se precisa de nota de devolução.</span></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="panel mb-3">
                <div class="form-section-head"><span class="stat-icon accent-rose"><x-icon name="arrow-in" width="18" height="18" /></span><div class="min-w-0"><h2 class="panel-title">O que o cliente devolveu</h2><p class="form-section-desc">Itens da venda {{ $sale->code }} · {{ $sale->customerName() }} · {{ $sale->sale_date->format('d/m/Y') }}</p></div></div>
                <div class="panel-body">
                    <template x-for="item in saleItems" :key="item.id">
                        <div class="xc-row" :class="{ 'is-on': item.back > 0 }">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate" x-text="item.name"></div>
                                <div class="small text-muted-2"><span x-text="item.qty"></span> vendido(s) · <span x-text="brl(item.price)"></span> cada</div>
                            </div>
                            <div class="qty-stepper" role="group" :aria-label="'Devolver ' + item.name">
                                <button type="button" @click="item.back = Math.max(0, item.back - 1)" aria-label="Menos">−</button>
                                <input type="number" min="0" :max="item.qty" :name="`returns[${item.id}]`" x-model.number="item.back" @input="item.back = Math.min(item.qty, Math.max(0, item.back || 0))">
                                <button type="button" @click="item.back = Math.min(item.qty, item.back + 1)" aria-label="Mais">+</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="panel">
                <div class="form-section-head"><span class="stat-icon accent-green"><x-icon name="arrow-out" width="18" height="18" /></span><div class="min-w-0"><h2 class="panel-title">O que o cliente vai levar</h2><p class="form-section-desc">Bipe a etiqueta ou procure o produto.</p></div></div>
                <div class="panel-body">
                    <div class="pdv-search">
                        <x-icon name="barcode" width="18" height="18" />
                        <input type="text" class="form-control" placeholder="Bipe o código ou digite o nome e tecle Enter" autocomplete="off"
                               x-model="query" @keydown.enter.prevent="addFromQuery()" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" x-ref="search">
                        <div class="pdv-results" x-show="query.length >= 2 && results().length" x-cloak>
                            <template x-for="(p, i) in results()" :key="p.id">
                                <button type="button" class="pdv-result" :class="{ active: i === cursor }" @click="add(p)">
                                    <span class="text-truncate" x-text="p.name"></span>
                                    <span class="small text-muted-2" x-text="`${brl(p.price)} · ${p.stock} em estoque`"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="form-text" :class="error ? 'text-negative' : 'text-positive'" x-show="message" x-text="message"></div>

                    <template x-for="(line, index) in added" :key="line.key">
                        <div class="xc-row is-new">
                            <input type="hidden" :name="`added[${index}][product_id]`" :value="line.id">
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate" x-text="line.name"></div>
                                <div class="d-flex align-items-center gap-2 small text-muted-2">
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm xc-price" :name="`added[${index}][unit_price]`" x-model="line.price">
                                    <span>cada</span>
                                </div>
                            </div>
                            <div class="qty-stepper">
                                <button type="button" @click="line.qty > 1 ? line.qty-- : remove(index)" aria-label="Menos">−</button>
                                <input type="number" min="1" :max="line.stock" :name="`added[${index}][quantity]`" x-model.number="line.qty">
                                <button type="button" @click="line.qty = Math.min(line.stock, line.qty + 1)" aria-label="Mais">+</button>
                            </div>
                            <button type="button" class="icon-btn icon-btn-danger" @click="remove(index)" aria-label="Remover"><x-icon name="trash" width="16" height="16" /></button>
                        </div>
                    </template>
                    <p class="small text-muted-2 mt-2 mb-0" x-show="!added.length">Nenhum produto novo. Se o cliente só devolveu, deixe vazio.</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <aside class="panel xc-summary">
                <div class="panel-body">
                    <div class="xc-line"><span>Devolvido</span><strong class="text-negative" x-text="'− ' + brl(returnedTotal())"></strong></div>
                    <div class="xc-line"><span>Levando</span><strong x-text="brl(addedTotal())"></strong></div>
                    @if ($sale->discount > 0)
                        <div class="xc-line small text-muted-2"><span>Desconto da venda</span><span>− {{ money($sale->discount) }}</span></div>
                    @endif
                    <div class="xc-total" :class="diff() > 0 ? 'is-pay' : (diff() < 0 ? 'is-back' : '')">
                        <span x-text="diff() > 0 ? 'Cliente paga' : (diff() < 0 ? 'Loja devolve' : 'Sem diferença')"></span>
                        <strong x-text="brl(Math.abs(diff()))"></strong>
                    </div>

                    <div x-show="Math.abs(diff()) >= 0.01">
                        <div class="xc-options">
                            <label class="xc-option">
                                <input type="radio" name="settlement" value="agora" x-model="settlement">
                                <span x-text="diff() > 0 ? 'Recebo agora' : 'Devolvo agora'"></span>
                            </label>
                            @if ($hasOpen)
                                <label class="xc-option">
                                    <input type="radio" name="settlement" value="parcelas" x-model="settlement">
                                    <span x-text="diff() > 0 ? 'Somar na última parcela' : 'Abater das parcelas'"></span>
                                </label>
                            @endif
                        </div>
                        <div x-show="settlement === 'agora'">
                            <label class="form-label small fw-semibold" for="payment_method">Forma</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                @foreach ($methods as $value => $label)
                                    <option value="{{ $value }}" @selected($value === 'dinheiro')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="settlement" value="agora" x-bind:disabled="Math.abs(diff()) >= 0.01">

                    <label class="form-label small fw-semibold mt-3" for="notes">Observação</label>
                    <input type="text" name="notes" id="notes" class="form-control" maxlength="500" placeholder="Ex.: trocou M pelo G" value="{{ old('notes') }}">

                    <button class="btn btn-cta w-100 justify-content-start mt-3" :disabled="submitting || !changed()">
                        <span class="btn-cta-icon"><x-icon name="refresh" width="16" height="16" /></span>Confirmar troca
                    </button>
                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-light w-100 mt-2">Voltar para a venda</a>
                </div>
            </aside>
        </div>
    </div>
</form>

<script>
function exchangeForm() {
    return {
        products: @json($products),
        saleItems: @json($saleItems),
        discount: {{ (float) $sale->discount }},
        oldTotal: {{ (float) $sale->total }},
        added: [],
        query: '',
        cursor: 0,
        message: '',
        error: false,
        settlement: 'agora',
        submitting: false,
        key: 1,
        brl(v) { return window.brl(v); },
        num(v) { return window.parseMoney(v); },
        returnedTotal() { return this.saleItems.reduce((s, i) => s + i.back * i.price, 0); },
        addedTotal() { return this.added.reduce((s, l) => s + this.num(l.price) * (Number(l.qty) || 0), 0); },
        diff() {
            const kept = this.saleItems.reduce((s, i) => s + (i.qty - i.back) * i.price, 0);
            const subtotal = kept + this.addedTotal();
            const total = Math.max(0, subtotal - Math.min(this.discount, subtotal));
            return Math.round((total - this.oldTotal) * 100) / 100;
        },
        changed() { return this.returnedTotal() > 0 || this.added.length > 0; },
        norm(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); },
        results() {
            const q = this.norm(this.query.trim());
            if (q.length < 2) return [];
            return this.products.filter(p => this.norm(p.name).includes(q) || (p.barcode && p.barcode === this.query.trim())).slice(0, 8);
        },
        move(d) { const n = this.results().length; if (n) this.cursor = (this.cursor + d + n) % n; },
        addFromQuery() {
            const code = this.query.trim();
            if (!code) return;
            const exact = this.products.find(p => p.barcode && p.barcode === code);
            const list = this.results();
            const p = exact || list[this.cursor] || (list.length === 1 ? list[0] : null);
            if (!p) { this.error = true; this.message = `Nenhum produto disponível para "${code}".`; return; }
            this.add(p);
        },
        add(p) {
            const line = this.added.find(l => l.id === p.id);
            if (line) {
                if (line.qty >= p.stock) { this.error = true; this.message = `${p.name}: só há ${p.stock} em estoque.`; return; }
                line.qty++;
            } else {
                this.added.push({ key: this.key++, id: p.id, name: p.name, price: p.price.toFixed(2).replace('.', ','), qty: 1, stock: p.stock });
            }
            this.error = false;
            this.message = `${p.name} adicionado.`;
            this.query = '';
            this.cursor = 0;
            this.$nextTick(() => this.$refs.search.focus());
        },
        remove(i) { this.added.splice(i, 1); },
    };
}
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endsection
