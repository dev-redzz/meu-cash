{{-- Abertura animada: roda só quando a pessoa vem do site (clique em Entrar/Criar conta). --}}
<div class="intro" id="intro" aria-hidden="true">
    <div class="intro-grid"></div>
    <div class="intro-stage">
        <div class="intro-mark">
            <svg class="intro-outline" viewBox="0 0 512 512" fill="none">
                <path pathLength="1" d="M0 135Q0 75 60 75Q88 75 108 90L215 185L345 92Q368 75 390 76Q435 80 435 125L435 150L220 292Q212 296 205 290L165 260L165 370Q165 388 150 395L100 430Q85 438 65 435Q0 425 0 360Z" />
                <path pathLength="1" class="d2" d="M105 222L165 260M105 222L105 415" />
                <path pathLength="1" class="d3" d="M280 310Q285 280 310 268L410 178Q425 170 432 185L438 198M320 280L450 205Q465 198 472 212L482 240" />
                <path pathLength="1" class="d4" d="M285 345Q285 300 330 292L480 255Q510 250 510 280L510 395Q510 430 475 432L325 435Q285 435 285 395Z" />
                <path pathLength="1" class="d5" d="M510 310L435 325Q415 330 415 350Q415 375 435 375L510 370" />
                <circle pathLength="1" class="d5" cx="450" cy="350" r="14" />
            </svg>
            <span class="intro-nodes">
                <i style="left:0%;top:26%"></i><i style="left:42%;top:36%"></i><i style="left:85%;top:24%"></i>
                <i style="left:20.5%;top:43%"></i><i style="left:32%;top:51%"></i><i style="left:55.5%;top:67%"></i>
                <i style="left:99.6%;top:54%"></i><i style="left:99.6%;top:77%"></i><i style="left:63%;top:85%"></i>
            </span>
            <img class="intro-logo" src="{{ asset('img/logo-icon.png') }}" alt="" width="512" height="512">
        </div>
        <div class="intro-word">
            <strong>Meu <span>Cash</span></strong>
            <i class="intro-rule"></i>
            <small>Vendas, parcelas e caixa da sua loja.</small>
        </div>
    </div>
    <button type="button" class="intro-skip" data-intro-skip>Pular</button>
</div>
<script>
(function () {
    var root = document.documentElement;
    var intro = document.getElementById('intro');
    if (!root.classList.contains('intro-run') || !intro) { if (intro) intro.remove(); return; }
    var done = false;
    var finish = function (fast) {
        if (done) return;
        done = true;
        root.classList.add(fast ? 'intro-skip-now' : 'intro-out', 'intro-enter');
        setTimeout(function () {
            root.classList.remove('intro-run', 'intro-out', 'intro-skip-now');
            intro.remove();
            var first = document.querySelector('.auth-box input:not([type=hidden])');
            if (first && window.matchMedia('(min-width: 768px)').matches) first.focus({ preventScroll: true });
            setTimeout(function () { root.classList.remove('intro-enter'); }, 1000);
        }, fast ? 250 : 700);
    };
    var timer = setTimeout(function () { finish(false); }, 3000);
    var skip = function () { clearTimeout(timer); finish(true); };
    intro.addEventListener('click', skip);
    document.addEventListener('keydown', function onKey() { document.removeEventListener('keydown', onKey); skip(); });
})();
</script>
