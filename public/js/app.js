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
