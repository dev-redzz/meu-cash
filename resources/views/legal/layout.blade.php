@extends('layouts.site')

@section('title')@yield('legal_title') · Meu Cash @endsection

@section('content')
<section class="legal-hero">
    <div class="site-container">
        <span class="eyebrow">Legal</span>
        <h1>@yield('legal_title')</h1>
        <p>@yield('legal_lead')</p>
        <div class="legal-meta">
            <span><x-icon name="calendar" width="15" height="15" /> Atualizado em {{ $updated ?? '17/10/2026' }}</span>
            <nav class="legal-switch" aria-label="Documentos">
                <a href="{{ route('terms') }}" @class(['active' => request()->routeIs('terms')])>Termos de uso</a>
                <a href="{{ route('privacy') }}" @class(['active' => request()->routeIs('privacy')])>Privacidade</a>
            </nav>
        </div>
    </div>
</section>

<div class="site-container legal-grid">
    <aside class="legal-toc" aria-label="Neste documento">
        <div class="legal-toc-title">Neste documento</div>
        @yield('toc')
    </aside>
    <article class="legal-text">
        @yield('legal_content')
        <div class="legal-contact">
            <span class="stat-icon accent-blue"><x-icon name="info" width="18" height="18" /></span>
            <div>
                <strong>Ficou com alguma dúvida?</strong>
                <p>
                    @if (config('meucash.support_email'))
                        Escreva para <a href="mailto:{{ config('meucash.support_email') }}">{{ config('meucash.support_email') }}</a>. Respondemos em até 15 dias.
                    @else
                        Fale com a gente pelo e-mail de suporte do Meu Cash.
                    @endif
                </p>
            </div>
        </div>
    </article>
</div>
@endsection
