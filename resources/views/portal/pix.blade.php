@extends('layouts.portal')

@section('title', 'Pagar com Pix')

@section('content')
@php
    $pending = $payment->isPending() && $installment->isOpen();
    $paid = $payment->isPaid() || ! $installment->isOpen();
@endphp

<a href="{{ route('portal.home') }}" class="portal-back"><x-icon name="arrow-left" width="16" height="16" /> Voltar</a>

<section class="pix-card" data-pix data-status-url="{{ route('portal.pix.status', $installment->id) }}" data-home="{{ route('portal.home') }}" data-state="{{ $paid ? 'pago' : ($pending ? 'pendente' : 'expirado') }}">
    <div class="pix-head">
        <div class="pix-label">Parcela {{ $installment->label() }} · {{ $installment->originLabel() }}</div>
        <div class="pix-value">{{ money($payment->amount) }}</div>
        <div class="pix-due">Vencimento {{ $installment->due_date->format('d/m/Y') }}</div>
    </div>

    <div class="pix-state pix-paid" @unless ($paid) hidden @endunless>
        <span class="pix-check"><x-icon name="check" width="30" height="30" /></span>
        <strong>Pagamento confirmado!</strong>
        <p>A parcela já está quitada. Obrigado!</p>
        <a href="{{ route('portal.home') }}" class="btn btn-cta w-100 justify-content-start"><span class="btn-cta-icon"><x-icon name="receipt" width="16" height="16" /></span>Ver minhas compras</a>
    </div>

    <div class="pix-state pix-expired" @if ($paid || $pending) hidden @endif>
        <strong>Este Pix expirou</strong>
        <p>Gere um novo código para pagar.</p>
        <form method="POST" action="{{ route('portal.pix.create', $installment->id) }}">
            @csrf
            <button class="btn btn-cta w-100 justify-content-start"><span class="btn-cta-icon"><x-icon name="pix" width="16" height="16" /></span>Gerar novo Pix</button>
        </form>
    </div>

    <div class="pix-state pix-pending" @unless ($pending) hidden @endunless>
        @if ($payment->live_mode === false)
            <div class="portal-flash is-error">Pix de teste: não pode ser pago no app do banco. A loja precisa usar o token de produção do Mercado Pago.</div>
        @endif
        @if ($payment->qr_code_base64)
            <img class="pix-qr" src="data:image/png;base64,{{ $payment->qr_code_base64 }}" alt="QR Code do Pix" width="220" height="220">
        @endif
        <ol class="pix-steps">
            <li>Abra o app do seu banco e escolha <strong>Pix</strong>.</li>
            <li>Leia o QR Code ou use o <strong>Pix copia e cola</strong>.</li>
            <li>Confirme o pagamento. Esta tela atualiza sozinha.</li>
        </ol>
        <label class="form-label small fw-semibold" for="pix-code">Pix copia e cola</label>
        <textarea id="pix-code" class="form-control pix-code" rows="3" readonly>{{ $payment->qr_code }}</textarea>
        <button type="button" class="btn btn-cta w-100 justify-content-start mt-2" data-pix-copy>
            <span class="btn-cta-icon"><x-icon name="copy" width="16" height="16" /></span><span data-pix-copy-text>Copiar código</span>
        </button>
        <div class="pix-wait" aria-live="polite"><span class="pix-dot"></span> Aguardando pagamento · válido até {{ $payment->expires_at->format('d/m H:i') }}</div>
    </div>
</section>

<p class="portal-note">Pagamento processado pelo Mercado Pago. O valor vai direto para {{ $portalCompany->name }}.</p>

<script>
(function () {
    var root = document.querySelector('[data-pix]');
    if (!root) return;
    var copy = root.querySelector('[data-pix-copy]');
    if (copy) copy.addEventListener('click', function () {
        var area = document.getElementById('pix-code');
        var done = function () { root.querySelector('[data-pix-copy-text]').textContent = 'Código copiado!'; setTimeout(function () { root.querySelector('[data-pix-copy-text]').textContent = 'Copiar código'; }, 2500); };
        if (navigator.clipboard) navigator.clipboard.writeText(area.value).then(done, function () { area.select(); document.execCommand('copy'); done(); });
        else { area.select(); document.execCommand('copy'); done(); }
    });
    if (root.getAttribute('data-state') !== 'pendente') return;
    var show = function (state) {
        root.querySelectorAll('.pix-state').forEach(function (el) { el.hidden = !el.classList.contains('pix-' + state); });
    };
    var tries = 0;
    var tick = function () {
        if (document.hidden) { setTimeout(tick, 4000); return; }
        fetch(root.getAttribute('data-status-url'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.paid) return show('paid');
                if (d.status === 'expirado' || d.status === 'cancelado') return show('expired');
                tries++;
                setTimeout(tick, tries < 60 ? 5000 : 15000);
            })
            .catch(function () { setTimeout(tick, 10000); });
    };
    setTimeout(tick, 5000);
})();
</script>
@endsection
