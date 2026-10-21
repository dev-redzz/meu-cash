(function () {
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var apply = function (choice) {
        var dark = choice === 'dark' || (choice === 'auto' && media && media.matches);
        root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        root.setAttribute('data-theme-choice', choice);
        document.querySelectorAll('[data-theme-set]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.themeSet === choice); });
    };
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-theme-set]');
        if (!b) return;
        try { localStorage.setItem('mc-theme', b.dataset.themeSet); } catch (err) {}
        apply(b.dataset.themeSet);
    });
    if (media && media.addEventListener) media.addEventListener('change', function () { if (root.getAttribute('data-theme-choice') === 'auto') apply('auto'); });
    apply(root.getAttribute('data-theme-choice') || 'light');

    var header = document.querySelector('.site-header');
    var onScroll = function () { if (header) header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    var menuBtn = document.querySelector('[data-site-menu]');
    var nav = document.getElementById('site-nav');
    if (menuBtn && nav) {
        var setOpen = function (open) {
            nav.classList.toggle('is-open', open);
            document.body.classList.toggle('site-menu-open', open);
            menuBtn.setAttribute('aria-expanded', open);
            menuBtn.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
        };
        menuBtn.addEventListener('click', function () { setOpen(!nav.classList.contains('is-open')); });
        nav.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
    }

    var toc = document.querySelectorAll('.legal-toc a[href^="#"]');
    if (toc.length && 'IntersectionObserver' in window) {
        var links = {};
        toc.forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                toc.forEach(function (a) { a.classList.remove('active'); });
                var link = links[entry.target.id];
                if (link) link.classList.add('active');
            });
        }, { rootMargin: '-80px 0px -70% 0px' });
        Object.keys(links).forEach(function (id) { var el = document.getElementById(id); if (el) observer.observe(el); });
    }

})();
