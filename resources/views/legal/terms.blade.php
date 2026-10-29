@extends('legal.layout')

@php
    $name = config('meucash.legal_name') !== 'Meu Cash' ? config('meucash.legal_name') : null;
    $doc = config('meucash.legal_document');
    $city = config('meucash.legal_city');
    $toc = [
        'aceite' => 'Aceite',
        'servico' => 'O serviço',
        'conta' => 'Conta e acesso',
        'equipe' => 'Equipe e permissões',
        'area-cliente' => 'Área do cliente',
        'responsabilidades' => 'Responsabilidades da empresa',
        'proibido' => 'Uso proibido',
        'disponibilidade' => 'Disponibilidade e dados',
        'propriedade' => 'Propriedade intelectual',
        'limites' => 'Limites de responsabilidade',
        'encerramento' => 'Suspensão e encerramento',
        'alteracoes' => 'Alterações',
        'foro' => 'Lei aplicável e foro',
    ];
@endphp

@section('legal_title', 'Termos de Uso')
@section('legal_lead', 'As regras para usar o Meu Cash. Escrevemos em linguagem simples para que você saiba exatamente o que esperar do serviço e o que esperamos de você.')

@section('toc')
    <ol>
        @foreach ($toc as $id => $label)
            <li><a href="#{{ $id }}">{{ $label }}</a></li>
        @endforeach
    </ol>
@endsection

@section('legal_content')
<div class="legal-summary">
    <strong>Resumo</strong>
    <ul>
        <li>O Meu Cash é uma ferramenta de gestão para lojas: vendas, estoque, clientes, parcelas e caixa.</li>
        <li>Os dados que a sua empresa cadastra pertencem a ela, e você pode exportá-los quando quiser.</li>
        <li>A empresa é responsável pelo que cadastra e por quem ela libera para usar o sistema.</li>
        <li>O Meu Cash não emite nota fiscal e não substitui o seu contador.</li>
    </ul>
</div>

<h2 id="aceite">1. Aceite</h2>
<p>Estes Termos de Uso regulam o uso do Meu Cash{{ $name ? ', oferecido por '.$name.($doc ? ' ('.$doc.')' : '') : '' }}. Ao criar uma conta, entrar no sistema ou acessar a área do cliente, você declara que leu e concorda com estes termos e com a <a href="{{ route('privacy') }}">Política de Privacidade</a>. Se não concordar, não use o serviço.</p>
<p>Quem cria a conta em nome de uma empresa declara ter autorização para representá-la. Nestes termos, "empresa" é a loja ou negócio que usa o Meu Cash, "usuário" é cada pessoa com acesso ao painel e "cliente final" é o cliente da empresa que acessa a área do cliente.</p>

<h2 id="servico">2. O serviço</h2>
<p>O Meu Cash é um sistema on-line, acessado pelo navegador, para registrar vendas à vista e parceladas, controlar estoque e custos de produtos, cadastrar clientes, acompanhar parcelas e recebimentos, registrar entradas e saídas do caixa, emitir comprovantes de venda e consultar relatórios.</p>
<p>O serviço pode ganhar novas funções e ter funções alteradas ou retiradas ao longo do tempo. Mudanças que reduzam de forma importante o que você usa serão avisadas com antecedência dentro do sistema.</p>

<h2 id="conta">3. Conta e acesso</h2>
<ul>
    <li>Você pode entrar com e-mail e senha ou com a sua conta Google.</li>
    <li>As informações do cadastro precisam ser verdadeiras e mantidas atualizadas.</li>
    <li>A senha é pessoal. Não a compartilhe e avise-nos se suspeitar de acesso indevido.</li>
    <li>Cada e-mail pertence a uma única empresa no Meu Cash.</li>
    <li>O serviço é destinado a maiores de 18 anos.</li>
</ul>

<h2 id="equipe">4. Equipe e permissões</h2>
<p>Quem cadastra a empresa se torna seu administrador. O administrador pode convidar outras pessoas, definir se são administradores ou funcionários e retirar o acesso a qualquer momento. Funcionários não veem o financeiro, os relatórios e as configurações, e não excluem nem cancelam registros.</p>
<p>A empresa responde pelos atos praticados pelos usuários que ela liberou.</p>

<h2 id="area-cliente">5. Área do cliente</h2>
<p>A empresa pode oferecer aos seus clientes finais o acesso à área do cliente, onde cada um consulta as próprias compras, parcelas e comprovantes usando um código de acesso individual. A empresa é responsável por entregar o código apenas ao cliente correto e por gerar um novo código sempre que suspeitar que ele foi exposto.</p>
<p>O cliente final deve guardar o código e não compartilhá-lo. Dúvidas sobre valores, parcelas e pagamentos devem ser tratadas diretamente com a loja.</p>

<h2 id="responsabilidades">6. Responsabilidades da empresa</h2>
<ul>
    <li>Garantir que os dados cadastrados estão corretos e que ela tem base legal para tratar os dados pessoais dos seus clientes, conforme a Lei Geral de Proteção de Dados (LGPD).</li>
    <li>Cumprir as leis que se aplicam ao seu negócio, inclusive as obrigações fiscais e de emissão de notas. O Meu Cash é uma ferramenta de gestão e não emite documentos fiscais.</li>
    <li>Conferir os cálculos e relatórios antes de tomar decisões financeiras importantes.</li>
    <li>Atender os pedidos dos seus clientes sobre os dados pessoais deles. Podemos ajudar nisso pelo contato de suporte.</li>
</ul>

<h2 id="proibido">7. Uso proibido</h2>
<p>Não é permitido:</p>
<ul>
    <li>usar o Meu Cash para atividades ilegais, fraudes ou venda de produtos proibidos;</li>
    <li>tentar acessar dados de outras empresas ou de outros clientes;</li>
    <li>testar falhas de segurança, sobrecarregar o serviço ou usar robôs para extrair dados sem autorização;</li>
    <li>cadastrar conteúdo ofensivo, discriminatório ou que viole direitos de terceiros;</li>
    <li>revender ou copiar o sistema.</li>
</ul>

<h2 id="disponibilidade">8. Disponibilidade e dados</h2>
<p>Trabalhamos para manter o Meu Cash disponível e seguro, mas o serviço pode ficar fora do ar por manutenções, atualizações ou falhas de fornecedores de hospedagem, internet e e-mail. Sempre que possível, manutenções programadas serão feitas em horários de pouco movimento.</p>
<p>Os dados cadastrados pela empresa pertencem a ela. Em Configurações, o administrador pode exportar clientes, produtos, vendas, parcelas e caixa a qualquer momento. Recomendamos exportar periodicamente as informações importantes.</p>

<h2 id="propriedade">9. Propriedade intelectual</h2>
<p>O sistema, a marca Meu Cash, o layout e o código pertencem {{ $name ? 'a '.$name : 'ao responsável pelo Meu Cash' }}. O uso do serviço não transfere nenhum desses direitos. Os dados, fotos e textos cadastrados pela empresa continuam sendo dela.</p>

<h2 id="limites">10. Limites de responsabilidade</h2>
<p>O Meu Cash é uma ferramenta de apoio à gestão. Não respondemos por decisões tomadas com base nos relatórios, por dados cadastrados de forma errada, por perdas causadas por uso indevido das senhas ou códigos de acesso, nem por falhas de serviços de terceiros como provedores de internet, Google e WhatsApp. Nada nestes termos afasta direitos garantidos por lei ao consumidor.</p>

<h2 id="encerramento">11. Suspensão e encerramento</h2>
<p>Podemos suspender o acesso de uma empresa ou usuário que descumpra estes termos, use o serviço de forma ilegal ou coloque em risco a segurança de outras empresas. Sempre que possível, avisaremos antes e explicaremos o motivo.</p>
<p>A empresa pode encerrar o uso quando quiser, pedindo a exclusão da conta pelo contato de suporte. Antes de excluir, recomendamos exportar os dados. Depois do pedido, os dados são apagados conforme a <a href="{{ route('privacy') }}#retencao">Política de Privacidade</a>.</p>

<h2 id="alteracoes">12. Alterações</h2>
<p>Estes termos podem ser atualizados para refletir mudanças no serviço ou na lei. A data da última atualização aparece no topo da página. Mudanças importantes serão avisadas dentro do sistema antes de valerem.</p>

<h2 id="foro">13. Lei aplicável e foro</h2>
<p>Estes termos seguem as leis brasileiras. Eventuais conflitos serão resolvidos no foro da comarca de {{ $city ?: 'domicílio do responsável pelo Meu Cash' }}, ressalvado o direito do consumidor de propor a ação no foro do próprio domicílio.</p>
@endsection
