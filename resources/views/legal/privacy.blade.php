@extends('legal.layout')

@section('title', 'Política de Privacidade')

@section('content')
<p>O Meu Cash é um sistema on-line de gestão de vendas, estoque, clientes e controle financeiro para empresas. Esta política explica quais dados tratamos, para quê e como você pode exercer seus direitos, conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).</p>

<h2>1. Dados da conta Google</h2>
<p>Ao entrar com o Google, recebemos apenas seu nome, endereço de e-mail e foto de perfil. Usamos esses dados exclusivamente para identificar você, liberar o acesso à sua empresa e exibir seu nome e foto dentro do sistema. Não temos acesso à sua senha, aos seus e-mails, arquivos ou a qualquer outro dado da sua conta Google.</p>

<h2>2. Dados cadastrados no sistema</h2>
<p>Clientes, produtos, vendas, parcelas, pagamentos e lançamentos financeiros são inseridos pela própria empresa usuária, que é a responsável por eles. Cada empresa acessa somente os próprios dados; o sistema separa as informações de cada empresa automaticamente.</p>

<h2>3. Registros de uso</h2>
<p>Para segurança, registramos ações importantes no sistema (como entradas, vendas e pagamentos), com data, hora, usuário e endereço IP. Esses registros ficam disponíveis para o administrador da empresa na área de auditoria.</p>

<h2>4. Compartilhamento</h2>
<p>Não vendemos nem alugamos dados. Os dados são armazenados em provedores de hospedagem e banco de dados contratados para operar o sistema e só são compartilhados com terceiros quando exigido por lei ou ordem judicial.</p>

<h2 id="cookies">5. Cookies</h2>
<p>Usamos apenas cookies necessários para o funcionamento do sistema. Não usamos cookies de publicidade nem de rastreamento.</p>
<ul>
    <li><strong>Sessão</strong>: mantém você conectado enquanto usa o sistema.</li>
    <li><strong>Segurança</strong> (XSRF-TOKEN): protege os formulários contra envios falsos.</li>
    <li><strong>Manter conectado</strong> (remember_web_…): só é criado se você marcar essa opção ao entrar.</li>
    <li><strong>Aviso de cookies</strong> (cookie_notice): lembra que você já viu este aviso, por 1 ano.</li>
</ul>
<p>Por serem necessários, esses cookies não podem ser desativados no sistema. Você pode apagá-los nas configurações do seu navegador, mas precisará entrar de novo.</p>

<h2>6. Retenção e exclusão</h2>
<p>Os dados são mantidos enquanto a empresa usar o sistema. A empresa pode solicitar a exclusão da conta e de todos os seus dados a qualquer momento pelo contato abaixo.</p>

<h2>7. Seus direitos</h2>
<p>Você pode solicitar confirmação, acesso, correção, portabilidade ou exclusão dos seus dados pessoais, além de informações sobre o tratamento, pelo contato abaixo.</p>

<h2>8. Contato</h2>
<p class="mb-0">
    @if (config('meucash.support_email'))
        <a href="mailto:{{ config('meucash.support_email') }}">{{ config('meucash.support_email') }}</a>
    @else
        Use o e-mail de suporte informado na tela de login do Google.
    @endif
</p>
@endsection
