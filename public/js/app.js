document.addEventListener('submit', function (event) {
    const form = event.target;
    const message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }
    const button = form.querySelector('[type="submit"]');
    if (button && !form.hasAttribute('data-no-lock')) {
        setTimeout(function () { button.disabled = true; }, 0);
    }
});

document.addEventListener('show.bs.modal', function (event) {
    const trigger = event.relatedTarget;
    const modal = event.target;
    if (!trigger) return;
    const form = modal.querySelector('form');
    if (form && trigger.dataset.action) {
        form.setAttribute('action', trigger.dataset.action);
    }
    modal.querySelectorAll('[data-fill]').forEach(function (el) {
        const key = el.getAttribute('data-fill');
        const value = trigger.dataset[key];
        if (value === undefined) return;
        if (el._flatpickr) {
            el._flatpickr.setDate(value, false);
        } else if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
            el.value = value;
        } else {
            el.textContent = value;
        }
    });
});



window.brl = function (value) {
    return (Number(value) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

window.parseMoney = function (value) {
    if (typeof value === 'number') return value;
    let v = String(value || '').replace(/[^\d,.-]/g, '');
    if (v.indexOf(',') !== -1) v = v.replace(/\./g, '').replace(',', '.');
    else if (/^-?\d{1,3}(\.\d{3})+$/.test(v)) v = v.replace(/\./g, '');
    const n = parseFloat(v);
    return isNaN(n) ? 0 : n;
};

(function () {
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var apply = function (choice) {
        var dark = choice === 'dark' || (choice === 'auto' && media && media.matches);
        root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        root.setAttribute('data-theme-choice', choice);
        document.querySelectorAll('[data-theme-set]').forEach(function (button) {
            button.setAttribute('aria-pressed', button.dataset.themeSet === choice);
        });
    };
    var current = function () { return root.getAttribute('data-theme-choice') || 'light'; };
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-theme-set]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        try { localStorage.setItem('mc-theme', button.dataset.themeSet); } catch (e) {}
        apply(button.dataset.themeSet);
    });
    if (media && media.addEventListener) {
        media.addEventListener('change', function () { if (current() === 'auto') apply('auto'); });
    }
    apply(current());
})();

/* Tabelas viram cartões no celular: cada célula recebe o nome da coluna. */
document.querySelectorAll('.panel table.table:not(.no-stack)').forEach(function (table) {
    var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) { return th.querySelector('.visually-hidden') ? '' : th.textContent.trim(); });
    if (!heads.length) return;
    var titleIndex = Array.prototype.findIndex.call(table.querySelectorAll('thead th'), function (th) { return th.hasAttribute('data-stack-title'); });
    if (titleIndex < 0) titleIndex = 0;
    table.classList.add('table-stack');
    table.querySelectorAll('tbody tr').forEach(function (tr) {
        var col = 0;
        Array.prototype.forEach.call(tr.children, function (td) {
            var span = parseInt(td.getAttribute('colspan') || '1', 10);
            if (span > 1) {
                td.classList.add('stack-empty');
            } else if (col === titleIndex) {
                td.classList.add('stack-title');
            } else if (!heads[col]) {
                td.classList.add('stack-actions');
            } else if (!td.hasAttribute('data-label')) {
                td.setAttribute('data-label', heads[col]);
            }
            col += span;
        });
    });
});

/* Atalho "/" para buscar */
document.addEventListener('keydown', function (event) {
    if (event.key !== '/' || event.ctrlKey || event.metaKey) return;
    var tag = (document.activeElement && document.activeElement.tagName) || '';
    if (/INPUT|TEXTAREA|SELECT/.test(tag) || (document.activeElement && document.activeElement.isContentEditable)) return;
    var input = document.querySelector('.topbar-search input');
    if (input && input.offsetParent !== null) {
        event.preventDefault();
        input.focus();
    }
});

/* Copiar código */
document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-copy-button]');
    if (!button) return;
    var box = button.closest('[data-copy]');
    if (!box || !navigator.clipboard) return;
    navigator.clipboard.writeText(box.getAttribute('data-copy')).then(function () {
        box.classList.add('is-copied');
        setTimeout(function () { box.classList.remove('is-copied'); }, 1500);
    });
});

/* Calendário em português para os campos de data (no celular usa o calendário do aparelho) */
(function () {
    if (!window.flatpickr) return;
    if (flatpickr.l10ns && flatpickr.l10ns.pt) flatpickr.localize(flatpickr.l10ns.pt);

    var setup = function (input) {
        if (input._flatpickr || input.hasAttribute('data-native')) return;
        flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            monthSelectorType: 'static',
            static: !!input.closest('.dropdown-menu, .modal'),
            onReady: function (dates, str, instance) {
                if (instance.altInput) {
                    instance.altInput.classList.add('date-input');
                    instance.altInput.setAttribute('placeholder', 'dd/mm/aaaa');
                    instance.altInput.setAttribute('inputmode', 'numeric');
                }
                if (instance.mobileInput) instance.mobileInput.classList.add('date-input');
            },
            onChange: function () {
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    };

    var run = function (scope) {
        scope.querySelectorAll('input[type="date"]').forEach(function (input) {
            if (input.hasAttribute('x-model')) return;
            setup(input);
        });
    };
    run(document);

    document.addEventListener('alpine:initialized', function () {
        document.querySelectorAll('input[type="date"][x-model]').forEach(setup);
    });
    if (window.Alpine && window.Alpine.version) {
        document.querySelectorAll('input[type="date"][x-model]').forEach(setup);
    }
})();

/* ==========================================================================
   Listas de opção (select) com visual próprio.
   O <select> original continua no formulário (escondido) e recebe o valor
   escolhido, então formulários, validação e Alpine continuam funcionando.
   ========================================================================== */
(function () {
    var CHEVRON = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    var CHECK = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>';
    var SEARCH = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>';
    var enhanced = new Set();
    var openInstance = null;

    var splitLabel = function (option) {
        var text = (option.textContent || '').trim();
        var sub = option.getAttribute('data-sub');
        if (sub) return { title: text, sub: sub };
        var i = text.indexOf(' · ');
        return i > 0 ? { title: text.slice(0, i), sub: text.slice(i + 3) } : { title: text, sub: '' };
    };

    var normalize = function (s) {
        return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    };

    function McSelect(select) {
        var self = this;
        this.select = select;
        this.wrap = document.createElement('div');
        this.wrap.className = 'mc-select' + (select.classList.contains('form-select-sm') ? ' mc-select-sm' : '');
        this.trigger = document.createElement('button');
        this.trigger.type = 'button';
        this.trigger.className = 'mc-select-trigger';
        this.trigger.setAttribute('aria-haspopup', 'listbox');
        this.trigger.setAttribute('aria-expanded', 'false');
        if (select.id) {
            var label = document.querySelector('label[for="' + select.id + '"]');
            if (label) {
                label.addEventListener('click', function (e) { e.preventDefault(); self.trigger.focus(); });
                this.trigger.setAttribute('aria-label', label.textContent.trim());
            }
        } else if (select.getAttribute('aria-label')) {
            this.trigger.setAttribute('aria-label', select.getAttribute('aria-label'));
        }
        this.trigger.innerHTML = '<span class="mc-select-value"></span><span class="mc-select-caret">' + CHEVRON + '</span>';
        this.menu = document.createElement('div');
        this.menu.className = 'mc-select-menu';
        this.menu.setAttribute('role', 'listbox');

        select.parentNode.insertBefore(this.wrap, select);
        this.wrap.appendChild(select);
        this.wrap.appendChild(this.trigger);
        if (this.wrap.classList.contains('mc-select-sm')) this.menu.classList.add('mc-select-menu-sm');
        this.menu.addEventListener('mousedown', function (e) { if (e.target !== self.search) e.preventDefault(); });
        select.classList.add('mc-select-native');
        select.tabIndex = -1;

        this.trigger.addEventListener('click', function () { self.isOpen() ? self.close() : self.open(); });
        this.trigger.addEventListener('keydown', function (e) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(e.key) >= 0) { e.preventDefault(); self.open(); }
        });
        select.addEventListener('change', function () { self.refresh(); });
        select.addEventListener('invalid', function () { self.wrap.classList.add('is-invalid'); });
        new MutationObserver(function () { self.refresh(); }).observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'class'] });

        this.lastIndex = -2;
        this.refresh();
        enhanced.add(this);
    }

    McSelect.prototype.isOpen = function () { return this.wrap.classList.contains('is-open'); };

    McSelect.prototype.refresh = function () {
        var select = this.select;
        var option = select.options[select.selectedIndex];
        var value = this.trigger.querySelector('.mc-select-value');
        if (option) {
            var parts = splitLabel(option);
            value.innerHTML = '';
            var title = document.createElement('span');
            title.className = 'mc-select-title';
            title.textContent = parts.title;
            value.appendChild(title);
            if (parts.sub && !this.wrap.classList.contains('mc-select-sm')) {
                var sub = document.createElement('span');
                sub.className = 'mc-select-sub';
                sub.textContent = parts.sub;
                value.appendChild(sub);
            }
        } else {
            value.textContent = '';
        }
        this.wrap.classList.toggle('is-placeholder', !option || option.value === '');
        this.wrap.classList.toggle('is-disabled', select.disabled);
        this.wrap.classList.toggle('is-invalid', select.classList.contains('is-invalid'));
        this.trigger.disabled = select.disabled;
        this.lastIndex = select.selectedIndex;
        this.lastCount = select.options.length;
    };

    McSelect.prototype.build = function () {
        var self = this;
        var select = this.select;
        var options = Array.prototype.slice.call(select.options);
        this.menu.innerHTML = '';
        this.items = [];

        if (options.length > 7) {
            var box = document.createElement('div');
            box.className = 'mc-select-search';
            box.innerHTML = SEARCH + '<input type="text" placeholder="Buscar..." autocomplete="off" spellcheck="false">';
            this.menu.appendChild(box);
            this.search = box.querySelector('input');
            this.search.addEventListener('input', function () { self.filter(self.search.value); });
            this.search.addEventListener('keydown', function (e) { self.keys(e); });
        } else {
            this.search = null;
        }

        var list = document.createElement('div');
        list.className = 'mc-select-list';
        this.menu.appendChild(list);

        options.forEach(function (option, index) {
            if (option.hidden) return;
            var parts = splitLabel(option);
            var item = document.createElement('div');
            item.className = 'mc-select-option';
            item.setAttribute('role', 'option');
            if (index === select.selectedIndex) { item.classList.add('is-selected'); item.setAttribute('aria-selected', 'true'); }
            if (option.disabled) item.classList.add('is-disabled');
            item.innerHTML = '<span class="mc-select-option-text"><span class="mc-select-option-title"></span>' + (parts.sub ? '<span class="mc-select-option-sub"></span>' : '') + '</span><span class="mc-select-check">' + CHECK + '</span>';
            item.querySelector('.mc-select-option-title').textContent = parts.title;
            if (parts.sub) item.querySelector('.mc-select-option-sub').textContent = parts.sub;
            item.dataset.index = index;
            item.dataset.search = normalize(option.textContent);
            item.addEventListener('mousedown', function (e) { e.preventDefault(); });
            item.addEventListener('click', function () { if (!option.disabled) self.choose(index); });
            item.addEventListener('mousemove', function () { self.highlight(self.items.indexOf(item)); });
            list.appendChild(item);
            self.items.push(item);
        });

        this.empty = document.createElement('div');
        this.empty.className = 'mc-select-empty';
        this.empty.textContent = 'Nada encontrado';
        this.empty.hidden = true;
        list.appendChild(this.empty);
        this.list = list;
    };

    McSelect.prototype.filter = function (term) {
        var t = normalize(term.trim());
        var first = -1;
        this.items.forEach(function (item, i) {
            var show = !t || item.dataset.search.indexOf(t) >= 0;
            item.hidden = !show;
            if (show && first < 0) first = i;
        });
        this.empty.hidden = first >= 0;
        this.highlight(first);
    };

    McSelect.prototype.visible = function () {
        return this.items.filter(function (item) { return !item.hidden && !item.classList.contains('is-disabled'); });
    };

    McSelect.prototype.highlight = function (i) {
        this.items.forEach(function (item) { item.classList.remove('is-active'); });
        this.active = i;
        var item = this.items[i];
        if (item) {
            item.classList.add('is-active');
            var list = this.list;
            if (item.offsetTop < list.scrollTop) list.scrollTop = item.offsetTop;
            else if (item.offsetTop + item.offsetHeight > list.scrollTop + list.clientHeight) list.scrollTop = item.offsetTop + item.offsetHeight - list.clientHeight;
        }
    };

    McSelect.prototype.keys = function (e) {
        var visible = this.visible();
        var pos = visible.indexOf(this.items[this.active]);
        if (e.key === 'ArrowDown') { e.preventDefault(); var n = visible[Math.min(visible.length - 1, pos + 1)]; if (n) this.highlight(this.items.indexOf(n)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); var p = visible[Math.max(0, pos - 1)]; if (p) this.highlight(this.items.indexOf(p)); }
        else if (e.key === 'Enter') { e.preventDefault(); var cur = this.items[this.active]; if (cur && !cur.hidden) this.choose(Number(cur.dataset.index)); }
        else if (e.key === 'Escape') { e.preventDefault(); this.close(true); }
        else if (e.key === 'Tab') { this.close(); }
    };

    McSelect.prototype.open = function () {
        if (this.select.disabled) return;
        if (openInstance && openInstance !== this) openInstance.close();
        var self = this;
        this.build();
        this.wrap.classList.add('is-open');
        this.trigger.setAttribute('aria-expanded', 'true');
        openInstance = this;

        (this.select.closest('.modal') || document.body).appendChild(this.menu);
        this.menu.classList.add('is-open');
        this.place();

        var selected = this.items.findIndex(function (item) { return item.classList.contains('is-selected'); });
        this.highlight(selected >= 0 ? selected : 0);
        if (this.search) {
            setTimeout(function () { self.search.focus({ preventScroll: true }); }, 0);
        } else {
            this.menu.tabIndex = -1;
            this.menu.onkeydown = function (e) { self.keys(e); };
            setTimeout(function () { self.menu.focus({ preventScroll: true }); }, 0);
        }
    };

    McSelect.prototype.place = function () {
        var rect = this.trigger.getBoundingClientRect();
        var menu = this.menu;
        var below = window.innerHeight - rect.bottom;
        var bar = document.querySelector('.tabbar');
        if (bar && bar.offsetParent !== null) below -= bar.offsetHeight;
        var width = Math.max(rect.width, 220);
        var left = Math.min(rect.left, window.innerWidth - width - 8);
        menu.style.width = width + 'px';
        menu.style.left = Math.max(8, left) + 'px';
        var height = menu.offsetHeight;
        var up = below < height + 12 && rect.top > below;
        menu.classList.toggle('is-up', up);
        menu.style.top = (up ? rect.top - height - 6 : rect.bottom + 6) + 'px';
    };

    McSelect.prototype.close = function (focus) {
        this.menu.classList.remove('is-open', 'is-up');
        this.wrap.classList.remove('is-open', 'drop-up');
        this.trigger.setAttribute('aria-expanded', 'false');
        if (openInstance === this) openInstance = null;
        if (focus) this.trigger.focus();
    };

    McSelect.prototype.choose = function (index) {
        var select = this.select;
        var changed = select.selectedIndex !== index;
        select.selectedIndex = index;
        this.wrap.classList.remove('is-invalid');
        if (changed) {
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        this.refresh();
        this.close(true);
    };

    var enhance = function (root) {
        (root.querySelectorAll ? root : document).querySelectorAll('select.form-select:not([multiple]):not([data-native]):not(.mc-select-native)').forEach(function (select) {
            if (select.closest('template')) return;
            new McSelect(select);
        });
    };

    enhance(document);

    document.addEventListener('alpine:initialized', function () { enhance(document); });

    new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) return;
                if (node.matches && node.matches('select.form-select') && !node.classList.contains('mc-select-native')) enhance(node.parentNode);
                else if (node.querySelector && node.querySelector('select.form-select:not(.mc-select-native)')) enhance(node);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });

    document.addEventListener('mousedown', function (e) {
        if (openInstance && !openInstance.wrap.contains(e.target) && !openInstance.menu.contains(e.target)) openInstance.close();
    });
    window.addEventListener('scroll', function (e) {
        if (openInstance && !openInstance.menu.contains(e.target)) openInstance.place();
    }, true);
    window.addEventListener('resize', function () { if (openInstance) openInstance.close(); });

    /* Valores trocados por código (ex.: Alpine) também atualizam o visual */
    setInterval(function () {
        enhanced.forEach(function (instance) {
            if (!instance.select.isConnected) { instance.menu.remove(); enhanced.delete(instance); if (openInstance === instance) openInstance = null; return; }
            if (instance.select.selectedIndex !== instance.lastIndex || instance.select.options.length !== instance.lastCount) instance.refresh();
        });
    }, 250);

    window.mcEnhanceSelects = enhance;
})();
