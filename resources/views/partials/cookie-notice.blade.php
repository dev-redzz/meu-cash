@unless(request()->cookie('cookie_notice'))
<div class="cookie-notice" id="cookie-notice" role="region" aria-label="Aviso de cookies">
    <p>Usamos apenas cookies necessários para manter você conectado e proteger seus dados. <a href="{{ route('privacy') }}#cookies">Saiba mais</a></p>
    <button type="button" class="btn btn-primary btn-sm" id="cookie-notice-ok">Entendi</button>
</div>
<style>
.cookie-notice{position:fixed;left:16px;bottom:16px;z-index:1080;max-width:380px;display:flex;align-items:center;gap:14px;padding:14px 16px;background:#fff;border:1px solid #e4e7ec;border-radius:12px;box-shadow:0 12px 32px rgba(16,24,40,.14);font-size:.8125rem;color:#475467;animation:cookie-in .3s ease-out}
.cookie-notice p{margin:0;line-height:1.45}
.cookie-notice a{font-weight:600}
.cookie-notice .btn{flex-shrink:0}
.cookie-notice.is-hiding{opacity:0;transform:translateY(8px);transition:.2s}
@keyframes cookie-in{from{opacity:0;transform:translateY(8px)}}
@media (max-width:575.98px){.cookie-notice{left:12px;right:12px;bottom:12px;max-width:none}}
</style>
<script>
document.getElementById('cookie-notice-ok').addEventListener('click', function () {
    document.cookie = 'cookie_notice=1; max-age=31536000; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    var box = document.getElementById('cookie-notice');
    box.classList.add('is-hiding');
    setTimeout(function () { box.remove(); }, 200);
});
</script>
@endunless
