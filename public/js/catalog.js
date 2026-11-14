/* Catálogo online: busca, categorias, carrinho e pedido pelo WhatsApp. */
(function () {
    var root = document.querySelector('[data-catalog]');
    if (!root) return;
    var products = window.CATALOG || [];
    var byId = {};
    products.forEach(function (p) { byId[p.id] = p; });
    var showStock = root.getAttribute('data-stock') === '1';
    var key = root.getAttribute('data-key');
    var money = function (v) { return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
    var norm = function (s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); };
    var $ = function (sel) { return document.querySelector(sel); };
    var $$ = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };

    var cart = {};
    try { cart = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch (e) { cart = {}; }
    Object.keys(cart).forEach(function (id) {
        var p = byId[id];
        if (!p) delete cart[id]; else cart[id] = Math.min(cart[id], p.stock);
    });
    var save = function () { try { localStorage.setItem(key, JSON.stringify(cart)); } catch (e) {} };

    var state = { q: '', cat: '' };
    var grid = $('[data-shop-grid]');
    var empty = $('[data-shop-empty]');

    var photo = function (p, cls) {
        return p.photos.length
            ? '<img class="' + cls + '" src="' + esc(p.photos[0]) + '" alt="' + esc(p.name) + '" loading="lazy">'
            : '<div class="' + cls + ' shop-noimg" aria-hidden="true">' + esc(p.name.charAt(0).toUpperCase()) + '</div>';
    };

    var renderGrid = function () {
        var q = norm(state.q);
        var list = products.filter(function (p) {
            return (!state.cat || p.category === state.cat) && (!q || norm(p.name + ' ' + (p.brand || '') + ' ' + (p.category || '')).indexOf(q) !== -1);
        });
        grid.innerHTML = list.map(function (p) {
            var qty = cart[p.id] || 0;
            return '<article class="shop-card">' +
                '<button type="button" class="shop-card-img" data-shop-detail-open="' + p.id + '" aria-label="Ver ' + esc(p.name) + '">' + photo(p, 'shop-img') + '</button>' +
                '<div class="shop-card-body">' +
                    '<h3 class="shop-name">' + esc(p.name) + '</h3>' +
                    (showStock ? '<div class="shop-stock">' + p.stock + (p.stock === 1 ? ' unidade' : ' unidades') + '</div>' : '') +
                    '<div class="shop-card-foot"><span class="shop-price">' + money(p.price) + '</span>' +
                    (qty ? '<span class="shop-in">' + qty + ' no pedido</span>' : '') + '</div>' +
                    '<button type="button" class="shop-add" data-shop-add="' + p.id + '"' + (qty >= p.stock ? ' disabled' : '') + '>' + (qty >= p.stock ? 'Máximo no pedido' : 'Adicionar') + '</button>' +
                '</div></article>';
        }).join('');
        empty.hidden = list.length > 0;
    };

    var count = function () { return Object.keys(cart).reduce(function (s, id) { return s + cart[id]; }, 0); };
    var total = function () { return Object.keys(cart).reduce(function (s, id) { return s + cart[id] * byId[id].price; }, 0); };

    var renderCart = function () {
        var n = count();
        $('[data-shop-bar]').hidden = n === 0;
        $('[data-shop-count]').textContent = n;
        $$('[data-shop-total]').forEach(function (el) { el.textContent = money(total()); });
        var ids = Object.keys(cart);
        $('[data-shop-items]').innerHTML = ids.length ? ids.map(function (id) {
            var p = byId[id];
            return '<div class="shop-line">' + photo(p, 'shop-line-img') +
                '<div class="shop-line-info"><div class="shop-line-name">' + esc(p.name) + '</div><div class="shop-line-price">' + money(p.price) + '</div></div>' +
                '<div class="shop-qty"><button type="button" data-shop-dec="' + id + '" aria-label="Menos">−</button><span>' + cart[id] + '</span>' +
                '<button type="button" data-shop-inc="' + id + '" aria-label="Mais"' + (cart[id] >= p.stock ? ' disabled' : '') + '>+</button></div></div>';
        }).join('') : '<p class="shop-empty">Seu pedido está vazio.</p>';
    };

    var change = function (id, d) {
        var p = byId[id];
        if (!p) return;
        var q = (cart[id] || 0) + d;
        if (q <= 0) delete cart[id]; else cart[id] = Math.min(q, p.stock);
        save(); renderCart(); renderGrid();
    };

    var openSheet = function (sel) { var s = $(sel); s.hidden = false; document.body.classList.add('shop-lock'); };
    var closeSheets = function () { $$('.shop-sheet').forEach(function (s) { s.hidden = true; }); document.body.classList.remove('shop-lock'); };

    var openDetail = function (id) {
        var p = byId[id];
        if (!p) return;
        var imgs = p.photos.length ? p.photos.map(function (src) { return '<img src="' + esc(src) + '" alt="' + esc(p.name) + '">'; }).join('') : photo(p, 'shop-detail-noimg');
        $('[data-shop-detail-body]').innerHTML =
            '<div class="shop-sheet-head"><h2>' + esc(p.name) + '</h2><button type="button" class="shop-x" data-shop-close aria-label="Fechar">×</button></div>' +
            '<div class="shop-gallery">' + imgs + '</div>' +
            '<div class="shop-sheet-foot">' +
                '<div class="shop-detail-meta">' + [p.brand, p.category].filter(Boolean).map(esc).join(' · ') + '</div>' +
                '<div class="shop-sum"><span>' + (showStock ? p.stock + (p.stock === 1 ? ' unidade' : ' unidades') : 'Preço') + '</span><strong>' + money(p.price) + '</strong></div>' +
                '<button type="button" class="shop-send" data-shop-add="' + p.id + '" data-shop-then-close>Adicionar ao pedido</button>' +
            '</div>';
        openSheet('[data-shop-detail]');
    };

    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-shop-add],[data-shop-inc],[data-shop-dec],[data-shop-open],[data-shop-close],[data-shop-filter],[data-shop-detail-open],[data-shop-send]');
        if (!t) return;
        if (t.hasAttribute('data-shop-add')) { change(t.getAttribute('data-shop-add'), 1); if (t.hasAttribute('data-shop-then-close')) closeSheets(); }
        else if (t.hasAttribute('data-shop-inc')) change(t.getAttribute('data-shop-inc'), 1);
        else if (t.hasAttribute('data-shop-dec')) change(t.getAttribute('data-shop-dec'), -1);
        else if (t.hasAttribute('data-shop-open')) openSheet('[data-shop-sheet]');
        else if (t.hasAttribute('data-shop-close')) closeSheets();
        else if (t.hasAttribute('data-shop-detail-open')) openDetail(t.getAttribute('data-shop-detail-open'));
        else if (t.hasAttribute('data-shop-filter')) {
            state.cat = t.getAttribute('data-shop-filter');
            $$('[data-shop-filter]').forEach(function (c) { c.classList.toggle('active', c === t); });
            renderGrid();
        } else if (t.hasAttribute('data-shop-send')) {
            var wa = root.getAttribute('data-wa');
            if (!wa || !count()) return;
            var name = $('#shop-name').value.trim(), note = $('#shop-note').value.trim();
            var lines = ['Olá, ' + root.getAttribute('data-store') + '! Quero fazer um pedido pelo catálogo:', ''];
            Object.keys(cart).forEach(function (id) { lines.push(cart[id] + 'x ' + byId[id].name + ' — ' + money(byId[id].price * cart[id])); });
            lines.push('', '*Total: ' + money(total()) + '*');
            if (note) lines.push('Obs.: ' + note);
            if (name) lines.push('Nome: ' + name);
            window.open('https://wa.me/' + wa + '?text=' + encodeURIComponent(lines.join('\n')), '_blank', 'noopener');
        }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheets(); });
    $('[data-shop-search]').addEventListener('input', function (e) { state.q = e.target.value; renderGrid(); });

    renderGrid();
    renderCart();
})();
