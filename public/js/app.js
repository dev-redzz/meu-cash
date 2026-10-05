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
        if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
            el.value = value;
        } else {
            el.textContent = value;
        }
    });
});

document.querySelectorAll('[data-period-select]').forEach(function (select) {
    const toggle = function () {
        const custom = select.value === 'personalizado';
        select.closest('form').querySelectorAll('[data-period-custom]').forEach(function (el) {
            el.classList.toggle('d-none', !custom);
        });
    };
    select.addEventListener('change', function () {
        toggle();
        if (select.value !== 'personalizado') select.form.submit();
    });
    toggle();
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
