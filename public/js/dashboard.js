/* Dashboard: gráfico de faturamento (barras) e lucro (linha), com dica ao passar o mouse ou tocar. */
(function () {
    var NS = 'http://www.w3.org/2000/svg';
    var money = function (v) { return (window.brl ? window.brl(v) : 'R$ ' + v.toFixed(2)); };
    var compact = function (v) {
        var a = Math.abs(v), sign = v < 0 ? '−' : '';
        if (a >= 1e6) return sign + 'R$ ' + (a / 1e6).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mi';
        if (a >= 1e3) return sign + 'R$ ' + (a / 1e3).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mil';
        return sign + 'R$ ' + Math.round(a).toLocaleString('pt-BR');
    };
    var niceStep = function (range, ticks) {
        var raw = range / ticks, pow = Math.pow(10, Math.floor(Math.log10(raw || 1))), n = raw / pow;
        return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 2.5 ? 2.5 : n <= 5 ? 5 : 10) * pow;
    };
    var el = function (name, attrs, parent) {
        var node = document.createElementNS(NS, name);
        for (var k in attrs) node.setAttribute(k, attrs[k]);
        if (parent) parent.appendChild(node);
        return node;
    };

    function draw(box, data) {
        box.innerHTML = '';
        var W = box.clientWidth, H = box.clientHeight || 240;
        if (W < 50) return;
        var M = { t: 12, r: 8, b: 26, l: W < 480 ? 46 : 60 };
        var iw = W - M.l - M.r, ih = H - M.t - M.b;

        var max = 0, min = 0;
        data.forEach(function (d) { max = Math.max(max, d.revenue, d.profit); min = Math.min(min, d.profit); });
        if (max <= 0) max = 1;
        var step = niceStep(max - min, 4);
        max = Math.ceil(max / step) * step;
        min = Math.floor(min / step) * step;
        var y = function (v) { return M.t + ih - (v - min) / (max - min) * ih; };
        var band = iw / data.length;
        var x = function (i) { return M.l + band * i + band / 2; };

        var svg = el('svg', { width: W, height: H, viewBox: '0 0 ' + W + ' ' + H, class: 'dash2-svg', 'aria-hidden': 'true' }, box);

        for (var v = min; v <= max + step / 2; v += step) {
            var gy = Math.round(y(v)) + .5;
            el('line', { x1: M.l, x2: W - M.r, y1: gy, y2: gy, class: v === 0 ? 'axis-zero' : 'grid' }, svg);
            var t = el('text', { x: M.l - 8, y: gy + 4, class: 'tick', 'text-anchor': 'end' }, svg);
            t.textContent = compact(v);
        }

        var every = Math.max(1, Math.ceil(data.length / (W < 480 ? 5 : 9))), lastX = -Infinity;
        data.forEach(function (d, i) {
            var isLast = i === data.length - 1;
            if (i % every !== 0 && !isLast) return;
            if (isLast && i % every !== 0 && x(i) - lastX < (W < 480 ? 46 : 52)) return;
            var t = el('text', { x: x(i), y: H - 6, class: 'tick', 'text-anchor': 'middle' }, svg);
            t.textContent = d.label;
            lastX = x(i);
        });

        var bw = Math.max(2, Math.min(28, band * 0.62)), r = Math.min(4, bw / 2);
        var bars = data.map(function (d, i) {
            if (d.revenue <= 0) return null;
            var top = y(d.revenue), base = y(0), left = x(i) - bw / 2;
            var h = Math.max(1, base - top), rr = Math.min(r, h);
            var path = 'M' + left + ',' + base + 'V' + (top + rr) + 'Q' + left + ',' + top + ' ' + (left + rr) + ',' + top +
                'H' + (left + bw - rr) + 'Q' + (left + bw) + ',' + top + ' ' + (left + bw) + ',' + (top + rr) + 'V' + base + 'Z';
            return el('path', { d: path, class: 'bar' }, svg);
        });

        var pts = data.map(function (d, i) { return x(i) + ',' + y(d.profit); }).join(' ');
        el('polyline', { points: pts, class: 'line' }, svg);

        var cross = el('line', { y1: M.t, y2: M.t + ih, class: 'cross', visibility: 'hidden' }, svg);
        var dot = el('circle', { r: 5, class: 'dot', visibility: 'hidden' }, svg);

        var tip = document.createElement('div');
        tip.className = 'dash2-tip';
        tip.hidden = true;
        box.appendChild(tip);

        var show = function (i) {
            var d = data[i];
            cross.setAttribute('x1', x(i)); cross.setAttribute('x2', x(i)); cross.setAttribute('visibility', 'visible');
            dot.setAttribute('cx', x(i)); dot.setAttribute('cy', y(d.profit)); dot.setAttribute('visibility', 'visible');
            bars.forEach(function (b, j) { if (b) b.classList.toggle('is-dim', j !== i); });
            tip.innerHTML = '<strong>' + d.title + '</strong>' +
                '<span><i class="is-revenue"></i>Faturamento <b>' + money(d.revenue) + '</b></span>' +
                '<span><i class="is-profit"></i>Lucro <b>' + money(d.profit) + '</b></span>' +
                '<small>' + d.count + (d.count === 1 ? ' venda' : ' vendas') + '</small>';
            tip.hidden = false;
            var left = x(i) + 14, tw = tip.offsetWidth;
            if (left + tw > W) left = x(i) - tw - 14;
            tip.style.left = Math.max(0, left) + 'px';
            tip.style.top = M.t + 'px';
        };
        var hide = function () {
            cross.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden');
            bars.forEach(function (b) { if (b) b.classList.remove('is-dim'); });
            tip.hidden = true;
        };
        var pick = function (e) {
            var rect = svg.getBoundingClientRect();
            var i = Math.floor((e.clientX - rect.left - M.l) / band);
            if (i < 0 || i >= data.length) { hide(); return; }
            show(i);
        };
        svg.addEventListener('pointermove', pick);
        svg.addEventListener('pointerdown', pick);
        svg.addEventListener('pointerleave', hide);
    }

    document.querySelectorAll('[data-dash-chart]').forEach(function (box) {
        var data = [];
        try { data = JSON.parse(box.getAttribute('data-dash-chart')); } catch (e) { return; }
        var render = function () { draw(box, data); };
        render();
        if ('ResizeObserver' in window) {
            var w = box.clientWidth;
            new ResizeObserver(function () { if (Math.abs(box.clientWidth - w) > 2) { w = box.clientWidth; render(); } }).observe(box);
        }
    });

    /* Abas simples (Cobranças: atrasadas / próximas) */
    document.querySelectorAll('[data-tabs]').forEach(function (root) {
        var tabs = root.querySelectorAll('[data-tab]');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { var on = t === tab; t.classList.toggle('active', on); t.setAttribute('aria-selected', on); });
                root.querySelectorAll('[data-tab-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-tab-panel') !== tab.getAttribute('data-tab'); });
            });
        });
    });
})();
