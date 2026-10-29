@extends('legal.layout')

@php
    $name = config('meucash.legal_name') !== 'Meu Cash' ? config('meucash.legal_name') : null;
    $doc = config('meucash.legal_document');
    $email = config('meucash.support_email');
    $toc = [
        'quem-somos' => 'Quem somos',
        'dados' => 'Dados que tratamos',
        'finalidades' => 'Para que usamos',
        'compartilhamento' => 'Com quem compartilhamos',
        'internacional' => 'Transferência internacional',
        'cookies' => 'Cookies e armazenamento',
        'seguranca' => 'Segurança',
        'retencao' => 'Por quanto tempo guardamos',
        'direitos' => 'Seus direitos',
        'menores' => 'Menores de idade',
        'encarregado' => 'Encarregado (DPO)',
        'mudancas' => 'Mudanças nesta política',
    ];
@endphp

@section('legal_title', 'Política de Privacidade')
@section('legal_lead', 'Como o Meu Cash trata dados pessoais, para quê, com quem compartilha e como você exerce seus direitos, conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).')

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
        <li>Usamos os dados só para fazer o sistema funcionar, manter a segurança e falar com você sobre a sua conta.</li>
        <li>Não vendemos dados e não usamos cookies de publicidade.</li>
        <li>Cada empresa só acessa os próprios dados.</li>
        <li>Você pode pedir acesso, correção ou exclusão dos seus dados a qualquer momento.</li>
    </ul>
</div>

<h2 id="quem-somos">1. Quem somos e qual o nosso papel</h2>
<p>{{ $name ? 'O Meu Cash é oferecido por '.$name.($doc ? ' ('.$doc.')' : '').'. ' : '' }}O nosso papel muda conforme o tipo de dado:</p>
<ul>
    <li><strong>Dados da conta</strong> (de quem usa o painel): somos o <em>controlador</em>, ou seja, decidimos como esses dados são usados.</li>
    <li><strong>Dados que a empresa cadastra</strong> (clientes, vendas, parcelas e afins): a empresa é a controladora e nós somos o <em>operador</em>. Tratamos esses dados apenas para prestar o serviço, seguindo as instruções da empresa.</li>
</ul>

<h2 id="dados">2. Dados que tratamos</h2>
<div class="legal-table">
    <table>
        <thead><tr><th>Origem</th><th>Dados</th></tr></thead>
        <tbody>
            <tr><td>Cadastro com e-mail e senha</td><td>Nome, nome da empresa, e-mail e senha (guardada apenas de forma criptografada, que nem nós conseguimos ler).</td></tr>
            <tr><td>Entrada com Google</td><td>Nome, e-mail e foto de perfil. Não temos acesso à sua senha, aos seus e-mails nem aos seus arquivos do Google.</td></tr>
            <tr><td>Cadastrados pela empresa</td><td>Dados dos clientes da loja (nome, CPF ou CNPJ, telefone, WhatsApp, e-mail e endereço), produtos e fotos, vendas, parcelas, pagamentos e lançamentos de caixa.</td></tr>
            <tr><td>Área do cliente</td><td>Código de acesso do cliente final e as informações das compras dele na loja.</td></tr>
            <tr><td>Registros de uso</td><td>Endereço IP, data e hora de acesso, navegador e ações importantes feitas no sistema, como vendas, pagamentos e exclusões.</td></tr>
        </tbody>
    </table>
</div>

<h2 id="finalidades">3. Para que usamos e com qual base legal</h2>
<div class="legal-table">
    <table>
        <thead><tr><th>Finalidade</th><th>Base legal (LGPD)</th></tr></thead>
        <tbody>
            <tr><td>Criar a conta, permitir o acesso e prestar o serviço</td><td>Execução de contrato (art. 7º, V)</td></tr>
            <tr><td>Enviar e-mails da conta, como recuperação de senha e resumo semanal</td><td>Execução de contrato (art. 7º, V)</td></tr>
            <tr><td>Registrar ações e acessos para segurança e auditoria</td><td>Legítimo interesse (art. 7º, IX) e Marco Civil da Internet (art. 15)</td></tr>
            <tr><td>Cumprir obrigações legais e ordens de autoridades</td><td>Obrigação legal (art. 7º, II)</td></tr>
        </tbody>
    </table>
</div>
<p>Não usamos os dados para publicidade e não tomamos decisões automatizadas sobre você.</p>

<h2 id="compartilhamento">4. Com quem compartilhamos</h2>
<p>Não vendemos nem alugamos dados pessoais. Compartilhamos apenas o necessário com fornecedores que nos ajudam a operar o serviço:</p>
<ul>
    <li><strong>Hospedagem e banco de dados</strong> (Railway): onde o sistema e os dados ficam armazenados;</li>
    <li><strong>Envio de e-mails</strong> (Brevo ou outro provedor de e-mail): para recuperação de senha e avisos da conta;</li>
    <li><strong>Google</strong>: apenas quando você escolhe entrar com a conta Google;</li>
</ul>
<p>Quando você usa os botões de WhatsApp, a mensagem é aberta no aplicativo do WhatsApp do seu aparelho e o envio acontece por lá. Também podemos compartilhar dados quando a lei exigir ou por ordem judicial.</p>

<h2 id="internacional">5. Transferência internacional</h2>
<p>Alguns fornecedores podem armazenar ou processar dados em servidores fora do Brasil. Nesses casos, escolhemos empresas que adotam medidas de segurança e proteção de dados compatíveis com a LGPD, conforme o art. 33 da lei.</p>

<h2 id="cookies">6. Cookies e armazenamento no navegador</h2>
<p>Usamos apenas o necessário para o sistema funcionar. Não usamos cookies de publicidade nem de rastreamento.</p>
<div class="legal-table">
    <table>
        <thead><tr><th>Nome</th><th>Para quê</th><th>Duração</th></tr></thead>
        <tbody>
            <tr><td>Sessão</td><td>Mantém você conectado enquanto usa o sistema ou a área do cliente</td><td>Até sair ou ficar inativo</td></tr>
            <tr><td>XSRF-TOKEN</td><td>Protege os formulários contra envios falsos</td><td>Até sair ou ficar inativo</td></tr>
            <tr><td>remember_web_…</td><td>Mantém conectado, só se você marcar essa opção ao entrar</td><td>Até sair</td></tr>
            <tr><td>cookie_notice</td><td>Lembra que você já viu o aviso de cookies</td><td>1 ano</td></tr>
            <tr><td>mc-theme</td><td>Guarda no navegador o tema escolhido (claro, escuro ou automático)</td><td>Até você apagar</td></tr>
        </tbody>
    </table>
</div>
<p>Por serem necessários, não podem ser desativados no sistema. Você pode apagá-los nas configurações do navegador, mas precisará entrar de novo.</p>

<h2 id="seguranca">7. Segurança</h2>
<ul>
    <li>Conexão protegida (HTTPS) em todas as páginas;</li>
    <li>senhas guardadas de forma criptografada;</li>
    <li>separação automática dos dados de cada empresa;</li>
    <li>permissões diferentes para administrador e funcionário;</li>
    <li>códigos de acesso individuais e com limite de tentativas na área do cliente;</li>
    <li>registro das ações importantes, consultável pelo administrador;</li>
    <li>cópia de segurança diária do banco de dados, enviada apenas ao responsável pelo Meu Cash, para recuperar os dados em caso de falha.</li>
</ul>
<p>Nenhum sistema é totalmente imune a falhas. Se ocorrer um incidente de segurança que possa trazer risco aos titulares, avisaremos as empresas afetadas e a Autoridade Nacional de Proteção de Dados (ANPD), como manda a lei.</p>

<h2 id="retencao">8. Por quanto tempo guardamos</h2>
<ul>
    <li>Os dados da empresa ficam guardados enquanto a conta estiver ativa.</li>
    <li>Após o pedido de encerramento, os dados são apagados em até 90 dias, exceto o que a lei nos obriga a guardar.</li>
    <li>Registros de acesso são mantidos por no mínimo 6 meses, como exige o Marco Civil da Internet.</li>
    <li>Itens excluídos no sistema podem ser recuperados pelo botão <em>Desfazer</em> por alguns minutos e depois são apagados de vez.</li>
</ul>

<h2 id="direitos">9. Seus direitos</h2>
<p>Pela LGPD (art. 18), você pode pedir:</p>
<ul>
    <li>confirmação de que tratamos seus dados e acesso a eles;</li>
    <li>correção de dados incompletos, errados ou desatualizados;</li>
    <li>anonimização, bloqueio ou exclusão de dados desnecessários ou tratados em desacordo com a lei;</li>
    <li>portabilidade dos dados;</li>
    <li>informação sobre com quem compartilhamos;</li>
    <li>revisão ou oposição a um tratamento.</li>
</ul>
<p>Se você é <strong>cliente de uma loja</strong> que usa o Meu Cash, fale primeiro com a loja, que é a responsável pelos seus dados. Se precisar, ajudamos a loja a atender o seu pedido. Você também pode reclamar à ANPD.</p>

<h2 id="menores">10. Menores de idade</h2>
<p>O painel do Meu Cash é destinado a maiores de 18 anos. Dados de clientes menores de idade só devem ser cadastrados pela loja com o consentimento de um dos pais ou responsável, conforme o art. 14 da LGPD.</p>

<h2 id="encarregado">11. Encarregado pelo tratamento de dados (DPO)</h2>
<p>O contato para assuntos de privacidade e para exercer seus direitos é
    @if ($email)
        <a href="mailto:{{ $email }}">{{ $email }}</a>.
    @else
        o e-mail de suporte do Meu Cash.
    @endif
    Respondemos em até 15 dias.
</p>

<h2 id="mudancas">12. Mudanças nesta política</h2>
<p>Podemos atualizar esta política para refletir mudanças no serviço ou na lei. A data da última atualização aparece no topo da página. Mudanças importantes serão avisadas dentro do sistema.</p>
@endsection
