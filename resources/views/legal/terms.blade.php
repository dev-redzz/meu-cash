@extends('legal.layout')

@section('title', 'Termos de Uso')

@section('content')
<p>Estes termos regulam o uso do Meu Cash. Ao criar uma empresa ou entrar no sistema, você concorda com eles.</p>

<h2>1. O serviço</h2>
<p>O Meu Cash é um sistema on-line para controle de vendas, estoque, clientes, parcelas e finanças de empresas.</p>

<h2>2. Conta e acesso</h2>
<p>O acesso é feito pela conta Google. Cada e-mail pertence a uma única empresa. Quem cadastra a empresa se torna seu administrador e é responsável por liberar, alterar e desativar o acesso de outras pessoas.</p>

<h2>3. Responsabilidades da empresa</h2>
<p>A empresa é responsável pela veracidade dos dados que insere, pelo uso que seus usuários fazem do sistema e por obter, quando necessário, o consentimento dos seus próprios clientes para cadastrá-los.</p>

<h2>4. Uso proibido</h2>
<p>É proibido usar o sistema para atividades ilegais, tentar acessar dados de outras empresas, sobrecarregar ou interferir no funcionamento do serviço.</p>

<h2>5. Disponibilidade</h2>
<p>Trabalhamos para manter o sistema disponível e seguro, mas podem ocorrer interrupções para manutenção, atualizações ou por falhas de fornecedores. Recomendamos exportar periodicamente as informações importantes.</p>

<h2>6. Suspensão e encerramento</h2>
<p>O acesso pode ser suspenso em caso de violação destes termos. A empresa pode encerrar o uso a qualquer momento e solicitar a exclusão dos seus dados.</p>

<h2>7. Alterações</h2>
<p>Estes termos podem ser atualizados. A data da última atualização aparece no topo desta página.</p>

<h2>8. Contato</h2>
<p class="mb-0">
    @if (config('meucash.support_email'))
        <a href="mailto:{{ config('meucash.support_email') }}">{{ config('meucash.support_email') }}</a>
    @else
        Use o e-mail de suporte informado na tela de login do Google.
    @endif
</p>
@endsection
