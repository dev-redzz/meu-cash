@extends('layouts.site')

@section('title', 'Meu Cash: vendas parceladas, estoque e caixa da sua loja')

@php
    $due = now()->startOfMonth()->addDays(9);
    $coupons = [
        ['n' => 1, 'date' => $due->copy()->subMonth(), 'paid' => true],
        ['n' => 2, 'date' => $due->copy(), 'paid' => true, 'stamp' => true],
        ['n' => 3, 'date' => $due->copy()->addMonth(), 'paid' => false],
    ];
    $questions = [
        ['Quem está me devendo?', 'A lista de parcelas atrasadas mostra o cliente, o valor e quantos dias passou. Um toque abre o WhatsApp com a mensagem de cobrança pronta.'],
        ['Quanto eu lucrei este mês?', 'O lucro é calculado em cada venda, já descontando o custo do produto e as despesas dele, como peças e frete.'],
        ['Ainda tenho esse produto?', 'Cada venda baixa o estoque sozinha. Você vê quantas unidades sobraram e quanto dinheiro está parado nelas.'],
        ['O cliente já pagou a parcela?', 'Ao receber, você dá baixa na parcela e o caixa é atualizado. O cliente também confere pela área dele.'],
        ['Quanto tem no caixa agora?', 'Entradas e saídas ficam registradas com data e forma de pagamento. O saldo aparece no topo do painel.'],
    ];
    $routine = [
        'No balcão' => ['Venda à vista ou parcelada em poucos toques', 'Busca pelo nome ou pelo código de barras', 'Comprovante em PDF com as parcelas e as cláusulas da loja'],
        'No fim do dia' => ['Baixa das parcelas recebidas', 'Entradas e saídas do caixa', 'Aviso das parcelas que vencem amanhã'],
        'No fim do mês' => ['Faturamento, lucro e produtos mais vendidos', 'Meta da loja e de cada vendedor, com comissão', 'Exportação de tudo em planilha'],
    ];
    $faq = [
        ['Preciso instalar alguma coisa?', 'Não. O Meu Cash abre no navegador do celular, do tablet ou do computador. Você entra com e-mail e senha ou com a conta Google.'],
        ['Meus funcionários podem usar?', 'Podem. Você convida cada um pelo e-mail. Funcionários vendem e cadastram, mas não veem o financeiro nem excluem registros.'],
        ['Outra loja consegue ver os meus dados?', 'Não. Cada empresa só enxerga as próprias informações, em todas as telas, relatórios e exportações.'],
        ['Como o cliente entra na área dele?', 'Cada cliente tem um código de acesso, que sai impresso no comprovante. Com ele, o cliente vê só as próprias compras e parcelas.'],
        ['Já tenho os produtos numa planilha. Preciso digitar tudo?', 'Não. Baixe o modelo, cole seus produtos e envie o arquivo. Todos são cadastrados de uma vez.'],
        ['Consigo levar meus dados embora?', 'Sim. Em Configurações você baixa clientes, produtos, vendas, parcelas e caixa em planilhas sempre que quiser.'],
    ];
    $billing = \App\Models\Company::billingEnabled();
@endphp

@section('content')
<section class="hero">
    <div class="wrap hero-grid">
        <div class="hero-copy">
            <h1>Cada parcela no lugar certo.</h1>
            <p class="lead">O Meu Cash registra a venda, gera as parcelas, mostra quem atrasou e calcula o lucro de cada produto. Para lojas de celular, roupa, eletrônicos e quem mais vende no crediário.</p>
            <div class="hero-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="s-btn s-btn-primary s-btn-lg">Abrir o painel</a>
                @else
                    <a href="{{ route('register') }}" class="s-btn s-btn-primary s-btn-lg">Criar conta da loja</a>
                    <a href="#por-dentro" class="s-btn s-btn-ghost s-btn-lg">Ver o sistema por dentro</a>
                @endauth
            </div>
            <p class="hero-note">Funciona no navegador. Não precisa instalar nada.</p>
        </div>

        <figure class="carne" aria-label="Exemplo de carnê de uma venda parcelada em 3 vezes">
            <div class="carne-head">
                <span class="carne-store">Loja do João</span>
                <span>Carnê de pagamento</span>
            </div>
            @foreach ($coupons as $c)
                <div @class(['coupon', 'is-paid' => $c['paid'], 'is-next' => ! $c['paid']])>
                    <div class="coupon-stub">
                        <small>Parcela</small>
                        <strong>{{ $c['n'] }}/3</strong>
                    </div>
                    <dl class="coupon-body">
                        <div class="coupon-wide"><dt>Cliente</dt><dd>Ana Paula Ribeiro</dd></div>
                        <div><dt>Vencimento</dt><dd>{{ $c['date']->format('d/m/Y') }}</dd></div>
                        <div><dt>Valor</dt><dd>R$ 366,00</dd></div>
                    </dl>
                    @if ($c['paid'])
                        <span @class(['stamp', 'stamp-live' => $c['stamp'] ?? false]) aria-label="Pago">Pago</span>
                    @else
                        <span class="coupon-flag">Em aberto</span>
                    @endif
                </div>
            @endforeach
            <figcaption>Venda V00128, celular 128 GB em 3 vezes. No Meu Cash, cada parcela tem dono, data e situação.</figcaption>
        </figure>
    </div>
</section>

<section class="block" id="perguntas">
    <div class="wrap">
        <div class="block-head">
            <h2>As perguntas do balcão, respondidas na hora</h2>
            <p>Aquilo que hoje você procura no caderno, na planilha ou no extrato do banco.</p>
        </div>
        <dl class="qa">
            @foreach ($questions as [$q, $a])
                <div class="qa-row">
                    <dt>{{ $q }}</dt>
                    <dd>{{ $a }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>

<section class="block block-tint" id="por-dentro">
    <div class="wrap">
        <div class="block-head">
            <h2>O painel que você abre todo dia</h2>
            <p>Saldo do caixa, o que entrou, o que falta receber e o lucro do período. Esta é a tela de verdade, não uma ilustração.</p>
        </div>
    </div>
    <div class="shot-wrap">
        <div class="shot">
            <img src="{{ asset('img/site/painel.webp') }}" class="only-light" alt="Painel do Meu Cash com saldo atual, faturamento, valores recebidos e a receber, lucro do mês e vendas recentes" width="1600" height="1000" loading="lazy">
            <img src="{{ asset('img/site/painel-escuro.webp') }}" class="only-dark" alt="" width="1600" height="1000" loading="lazy">
        </div>
    </div>
    <div class="wrap">
        <div class="routine">
            @foreach ($routine as $moment => $items)
                <div class="routine-col">
                    <h3>{{ $moment }}</h3>
                    <ul>
                        @foreach ($items as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="block" id="area-do-cliente">
    <div class="wrap split">
        <div class="split-text">
            <h2>Seu cliente confere as parcelas sem precisar ligar</h2>
            <p>Cada cliente recebe um código que sai impresso no comprovante. Com ele, entra na área do cliente pelo celular e vê o que comprou, o que já pagou e quando vence a próxima parcela.</p>
            <ul class="ticks">
                <li>Cada código mostra só as compras daquele cliente.</li>
                <li>O comprovante de cada compra fica disponível em PDF.</li>
                <li>Um botão abre o WhatsApp da loja com o código já na mensagem.</li>
                <li>Se o código vazar, você gera outro e o antigo para de funcionar.</li>
            </ul>
            <a href="{{ route('portal.login') }}" class="text-link">Sou cliente e quero ver minhas parcelas</a>
        </div>
        <div class="phone">
            <img src="{{ asset('img/site/area-do-cliente.webp') }}" class="only-light" alt="Área do cliente no celular, mostrando parcela atrasada, total comprado, valor pago e parcelas da compra" width="540" height="1080" loading="lazy">
            <img src="{{ asset('img/site/area-do-cliente-escuro.webp') }}" class="only-dark" alt="" width="540" height="1080" loading="lazy">
        </div>
    </div>
</section>

<section class="block block-tint" id="comecar">
    <div class="wrap">
        <div class="block-head">
            <h2>Para começar</h2>
        </div>
        <ol class="steps">
            <li><strong>Crie a conta da loja.</strong> Nome, empresa, e-mail e senha. Quem cria vira o administrador.</li>
            <li><strong>Cadastre os produtos.</strong> Um por um, com foto e preço, ou todos de uma vez pela planilha.</li>
            <li><strong>Registre a primeira venda.</strong> Estoque, caixa e parcelas se atualizam sozinhos a partir daí.</li>
        </ol>
    </div>
</section>

@if ($billing)
    <section class="block" id="planos">
        <div class="wrap">
            <div class="block-head">
                <h2>Planos</h2>
                <p>{{ config('billing.trial_days') }} dias para testar sem pagar nada. Todos os recursos em qualquer plano.</p>
            </div>
            <div class="plans">
                @foreach (config('billing.plans') as $plan)
                    @php $perMonth = $plan['price'] / max(1, $plan['months']); @endphp
                    <div @class(['plan', 'plan-featured' => $plan['months'] > 1])>
                        <h3>{{ $plan['label'] }}</h3>
                        <p class="plan-price">{{ money($perMonth) }} <small>por mês</small></p>
                        <p class="plan-note">{{ $plan['months'] > 1 ? money($plan['price']).' a cada '.$plan['months'].' meses' : 'Cobrado todo mês' }}</p>
                        <a href="{{ route('register') }}" class="s-btn {{ $plan['months'] > 1 ? 's-btn-primary' : 's-btn-ghost' }}">Começar o teste</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="block {{ $billing ? 'block-tint' : '' }}" id="duvidas">
    <div class="wrap faq-grid">
        <div class="block-head">
            <h2>Dúvidas</h2>
            <p>
                @if (config('meucash.support_email'))
                    Não achou a sua? Escreva para <a href="mailto:{{ config('meucash.support_email') }}">{{ config('meucash.support_email') }}</a>.
                @else
                    As perguntas que mais recebemos de quem está começando.
                @endif
            </p>
        </div>
        <div class="faq">
            @foreach ($faq as [$q, $a])
                <details>
                    <summary>{{ $q }}</summary>
                    <p>{{ $a }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="closing">
    <div class="wrap closing-inner">
        <h2>Feche o caderno de fiado.</h2>
        <div class="closing-side">
            <p>Crie a conta agora e registre a primeira venda parcelada ainda hoje.</p>
            @auth
                <a href="{{ route('dashboard') }}" class="s-btn s-btn-light s-btn-lg">Abrir o painel</a>
            @else
                <a href="{{ route('register') }}" class="s-btn s-btn-light s-btn-lg">Criar conta da loja</a>
            @endauth
        </div>
    </div>
</section>
@endsection
