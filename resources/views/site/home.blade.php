@extends('layouts.site')

@section('title', 'Meu Cash · Vendas, estoque e parcelas da sua loja em um só lugar')

@php
    $features = [
        ['cart', 'blue', 'Vendas à vista e parceladas', 'Registre a venda em segundos, com entrada, parcelas e vencimentos calculados sozinhos.'],
        ['box', 'violet', 'Estoque com lucro real', 'Custo de compra, despesas como peças e frete e o lucro esperado de cada produto.'],
        ['users', 'green', 'Clientes e cobrança', 'Histórico de compras de cada cliente e lembrete de parcela pronto para enviar no WhatsApp.'],
        ['wallet', 'amber', 'Caixa e financeiro', 'Entradas e saídas, saldo do caixa e o que ainda falta receber, sem planilha.'],
        ['receipt', 'rose', 'Comprovante em PDF', 'Comprovante de venda completo, com parcelas, cláusulas e assinaturas, pronto para imprimir.'],
        ['chart', 'slate', 'Relatórios e metas', 'Faturamento, lucro, produtos mais vendidos e meta do mês para a loja e para cada vendedor.'],
        ['team', 'blue', 'Equipe com permissões', 'Funcionários vendem e cadastram; só o administrador vê o financeiro e exclui registros.'],
        ['upload', 'violet', 'Importação de planilha', 'Já tem tudo no Excel? Envie a planilha e cadastre todos os produtos de uma vez.'],
    ];
    $faq = [
        ['Preciso instalar alguma coisa?', 'Não. O Meu Cash funciona no navegador do celular, do tablet ou do computador. É só entrar com seu e-mail ou com a conta Google.'],
        ['Meus funcionários podem usar?', 'Sim. O administrador convida a equipe pelo e-mail. Cada pessoa tem seu próprio acesso, e funcionários não veem o financeiro nem excluem registros.'],
        ['Os dados da minha loja ficam separados?', 'Sim. Cada empresa só enxerga os próprios dados. O sistema separa as informações automaticamente em todas as telas, relatórios e exportações.'],
        ['Como o meu cliente acompanha as parcelas?', 'Cada cliente recebe um código de acesso, que sai no comprovante. Na área do cliente ele vê as compras, as parcelas e baixa o comprovante, sem ver nada de outros clientes.'],
        ['Consigo levar meus dados embora?', 'Sim. Em Configurações você exporta clientes, produtos, vendas, parcelas e caixa em planilhas quando quiser.'],
        ['Funciona no modo escuro?', 'Funciona. Escolha claro, escuro ou automático no menu da sua conta.'],
    ];
    $billing = \App\Models\Company::billingEnabled();
@endphp

@section('content')
<section class="hero">
    <div class="site-container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><span class="eyebrow-dot"></span> Feito para lojas e revendedores</span>
            <h1>Tudo o que você&nbsp;vende, <span class="text-gradient">sob&nbsp;seu&nbsp;controle.</span></h1>
            <p class="hero-lead">O Meu Cash reúne vendas, estoque, clientes, parcelas e caixa em um só lugar. Você sabe na hora quanto vendeu, quanto lucrou e quem está devendo.</p>
            <div class="hero-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-cta btn-cta-lg"><span class="btn-cta-icon"><x-icon name="home" width="18" height="18" /></span>Abrir o painel</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-cta btn-cta-lg"><span class="btn-cta-icon"><x-icon name="chevron-right" width="18" height="18" /></span>Criar minha conta</a>
                    <a href="#recursos" class="btn btn-cta btn-cta-light btn-cta-lg"><span class="btn-cta-icon"><x-icon name="eye" width="18" height="18" /></span>Ver recursos</a>
                @endauth
            </div>
            <ul class="hero-checks">
                <li><x-icon name="check" width="16" height="16" /> Funciona no celular e no computador</li>
                <li><x-icon name="check" width="16" height="16" /> Nada para instalar</li>
                <li><x-icon name="check" width="16" height="16" /> Seus dados separados e protegidos</li>
            </ul>
        </div>

        <div class="hero-visual" aria-hidden="true">
            <div class="mock-window">
                <div class="mock-bar"><span></span><span></span><span></span><div class="mock-url">meucash · painel</div></div>
                <div class="mock-body">
                    <div class="mock-stats">
                        <div class="mock-stat"><small>Vendas do mês</small><strong>R$ 18.420</strong><em class="up">+12,4%</em></div>
                        <div class="mock-stat"><small>Lucro</small><strong>R$ 6.135</strong><em class="up">+8,1%</em></div>
                        <div class="mock-stat"><small>A receber</small><strong>R$ 4.980</strong><em>23 parcelas</em></div>
                    </div>
                    <div class="mock-chart">
                        <div class="mock-chart-head"><span>Vendas por dia</span><span class="mock-pill">Este mês</span></div>
                        <div class="mock-bars">
                            @foreach ([38, 52, 30, 66, 48, 74, 58, 82, 61, 90, 70, 96] as $h)
                                <span style="height: {{ $h }}%"></span>
                            @endforeach
                        </div>
                    </div>
                    <div class="mock-list">
                        <div><span class="mock-avatar">AP</span><span><b>Ana Paula</b><small>Parcela 2/5 · vence hoje</small></span><strong>R$ 320,00</strong></div>
                        <div><span class="mock-avatar is-green">JC</span><span><b>João Carlos</b><small>Pago no Pix</small></span><strong class="ok">R$ 450,00</strong></div>
                    </div>
                </div>
            </div>
            <div class="float-card float-card-a">
                <span class="stat-icon accent-green"><x-icon name="check" width="16" height="16" /></span>
                <span><b>Venda V00128 registrada</b><small>iPhone 13 · 3x de R$ 1.066</small></span>
            </div>
            <div class="float-card float-card-b">
                <span class="stat-icon accent-amber"><x-icon name="bell" width="16" height="16" /></span>
                <span><b>3 parcelas vencem hoje</b><small>Lembrete pronto no WhatsApp</small></span>
            </div>
        </div>
    </div>
</section>

<section class="site-strip">
    <div class="site-container strip-grid">
        <div><strong>1 lugar</strong><span>para vendas, estoque e caixa</span></div>
        <div><strong>Segundos</strong><span>para registrar uma venda parcelada</span></div>
        <div><strong>24 horas</strong><span>de acesso, de qualquer aparelho</span></div>
        <div><strong>100%</strong><span>dos dados separados por empresa</span></div>
    </div>
</section>

<section class="site-section" id="recursos">
    <div class="site-container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Recursos</span>
            <h2>Tudo o que a sua loja precisa no dia a dia</h2>
            <p>Sem módulos escondidos: o que você usa para vender, cobrar e controlar já vem pronto.</p>
        </div>
        <div class="feature-grid">
            @foreach ($features as [$icon, $accent, $title, $text])
                <article class="feature-card" data-reveal>
                    <span class="stat-icon accent-{{ $accent }}"><x-icon :name="$icon" width="20" height="20" /></span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="site-section site-section-soft" id="como-funciona">
    <div class="site-container">
        <div class="section-head" data-reveal>
            <span class="eyebrow">Como funciona</span>
            <h2>Comece a usar hoje, em três passos</h2>
        </div>
        <ol class="steps">
            <li data-reveal>
                <span class="step-num">1</span>
                <h3>Crie a conta da loja</h3>
                <p>Informe seu nome, o nome da empresa e o e-mail. Você vira o administrador.</p>
            </li>
            <li data-reveal>
                <span class="step-num">2</span>
                <h3>Cadastre os produtos</h3>
                <p>Um por um, com foto e preço, ou todos de uma vez pela planilha.</p>
            </li>
            <li data-reveal>
                <span class="step-num">3</span>
                <h3>Venda e acompanhe</h3>
                <p>Cada venda atualiza o estoque, o caixa e as parcelas. O painel mostra o resultado na hora.</p>
            </li>
        </ol>
    </div>
</section>

<section class="site-section" id="area-do-cliente">
    <div class="site-container split">
        <div class="split-copy" data-reveal>
            <span class="eyebrow">Área do cliente</span>
            <h2>Seu cliente acompanha as próprias parcelas</h2>
            <p>Cada cliente recebe um código único, impresso no comprovante. Com ele, entra na área do cliente e vê o que comprou, o que já pagou e o que vence, sem precisar ligar para a loja.</p>
            <ul class="check-list">
                <li><x-icon name="check" width="18" height="18" /> Vê só as próprias compras, nunca as de outra pessoa</li>
                <li><x-icon name="check" width="18" height="18" /> Baixa o comprovante de cada compra em PDF</li>
                <li><x-icon name="check" width="18" height="18" /> Fala com a loja pelo WhatsApp com um toque</li>
                <li><x-icon name="check" width="18" height="18" /> Você troca o código quando quiser e o antigo para de funcionar</li>
            </ul>
            <a href="{{ route('portal.login') }}" class="btn btn-cta btn-cta-light"><span class="btn-cta-icon"><x-icon name="key" width="16" height="16" /></span>Sou cliente, quero entrar</a>
        </div>
        <div class="phone-wrap" aria-hidden="true" data-reveal>
            <div class="phone">
                <div class="phone-notch"></div>
                <div class="phone-screen">
                    <div class="phone-hello"><b>Olá, Ana</b><small>Suas compras na Loja do João</small></div>
                    <div class="phone-hero">
                        <small>Próxima parcela · V00128 · 2/3</small>
                        <strong>R$ 1.066,00</strong>
                        <span>Vence em 10/11 <em>em 12 dias</em></span>
                    </div>
                    <div class="phone-stats">
                        <div><small>Comprado</small><b>R$ 3.198</b></div>
                        <div><small>Pago</small><b class="ok">R$ 1.066</b></div>
                    </div>
                    <div class="phone-item">
                        <div><b>Compra V00128</b><small>iPhone 13 128GB</small></div>
                        <span class="pill pill-warning">Em aberto</span>
                    </div>
                    <div class="phone-progress"><span style="width: 33%"></span></div>
                    <div class="phone-btn"><x-icon name="receipt" width="14" height="14" /> Baixar comprovante</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-section site-section-dark">
    <div class="site-container">
        <div class="section-head section-head-light" data-reveal>
            <span class="eyebrow">Segurança</span>
            <h2>Os dados da sua loja ficam com a sua loja</h2>
        </div>
        <div class="trust-grid">
            <div data-reveal><x-icon name="layers" width="22" height="22" /><h3>Empresas separadas</h3><p>Cada empresa só acessa as próprias informações, em todas as telas e relatórios.</p></div>
            <div data-reveal><x-icon name="key" width="22" height="22" /><h3>Acesso por perfil</h3><p>Administrador e funcionário têm permissões diferentes. Você decide quem entra.</p></div>
            <div data-reveal><x-icon name="note" width="22" height="22" /><h3>Histórico de ações</h3><p>Vendas, pagamentos e exclusões ficam registrados com data, hora e usuário.</p></div>
            <div data-reveal><x-icon name="arrow-out" width="22" height="22" /><h3>Seus dados, sempre</h3><p>Exporte tudo em planilha quando quiser. Nada fica preso no sistema.</p></div>
        </div>
    </div>
</section>

@if ($billing)
    <section class="site-section" id="planos">
        <div class="site-container">
            <div class="section-head" data-reveal>
                <span class="eyebrow">Planos</span>
                <h2>Um preço simples, com tudo incluído</h2>
                <p>Teste grátis por {{ config('billing.trial_days') }} dias. Sem cartão para começar.</p>
            </div>
            <div class="price-grid">
                @foreach (config('billing.plans') as $key => $plan)
                    @php $perMonth = $plan['price'] / max(1, $plan['months']); @endphp
                    <article @class(['price-card', 'is-featured' => $plan['months'] > 1]) data-reveal>
                        @if ($plan['months'] > 1)<span class="price-badge">Mais econômico</span>@endif
                        <h3>{{ $plan['label'] }}</h3>
                        <div class="price-value">{{ money($perMonth) }}<small>/mês</small></div>
                        <p class="price-note">{{ $plan['months'] > 1 ? money($plan['price']).' cobrados a cada '.$plan['months'].' meses' : 'Cobrado todo mês' }}</p>
                        <ul class="check-list check-list-sm">
                            <li><x-icon name="check" width="16" height="16" /> Vendas, estoque e caixa sem limite</li>
                            <li><x-icon name="check" width="16" height="16" /> Equipe e área do cliente</li>
                            <li><x-icon name="check" width="16" height="16" /> Relatórios e exportação</li>
                        </ul>
                        <a href="{{ route('register') }}" @class(['btn btn-cta w-100', 'btn-cta-light' => $plan['months'] <= 1])><span class="btn-cta-icon"><x-icon name="chevron-right" width="16" height="16" /></span>Começar o teste</a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="site-section {{ $billing ? 'site-section-soft' : '' }}" id="duvidas">
    <div class="site-container faq-wrap">
        <div class="section-head section-head-left" data-reveal>
            <span class="eyebrow">Dúvidas</span>
            <h2>Perguntas frequentes</h2>
            <p>Não achou sua dúvida?@if (config('meucash.support_email')) Escreva para <a href="mailto:{{ config('meucash.support_email') }}">{{ config('meucash.support_email') }}</a>.@else Fale com a gente pelo e-mail de suporte.@endif</p>
        </div>
        <div class="faq">
            @foreach ($faq as $i => [$q, $a])
                <details @if ($i === 0) open @endif>
                    <summary>{{ $q }}<x-icon name="plus" width="18" height="18" /></summary>
                    <p>{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="site-cta">
    <div class="site-container">
        <div class="cta-band" data-reveal>
            <div>
                <h2>Pronto para organizar a sua loja?</h2>
                <p>Crie a conta agora e registre a primeira venda em poucos minutos.</p>
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-cta btn-cta-white btn-cta-lg"><span class="btn-cta-icon"><x-icon name="home" width="18" height="18" /></span>Abrir o painel</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-cta btn-cta-white btn-cta-lg"><span class="btn-cta-icon"><x-icon name="chevron-right" width="18" height="18" /></span>Criar minha conta</a>
            @endauth
        </div>
    </div>
</section>
@endsection
