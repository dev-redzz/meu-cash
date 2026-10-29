# Meu Cash

Sistema de vendas, estoque, parcelas e caixa para lojas, no formato SaaS (várias empresas no mesmo sistema), em Laravel 12.
Cada empresa só enxerga os próprios dados.

## O que o sistema faz

- **Vendas à vista e parceladas:** entrada, parcelas, vencimentos, comprovante em PDF e cobrança pelo WhatsApp.
- **Estoque:** lucro de cada produto, despesas (peças, frete), estoque mínimo com aviso de reposição e entrada de mercadoria com custo médio.
- **Clientes e área do cliente:** cada cliente consulta as próprias compras e parcelas com um código de acesso.
- **Caixa, relatórios, metas e comissão de vendedores.**
- **Equipe:** administrador e funcionário, com permissões diferentes.
- **Backup diário** do banco por e-mail e **monitoramento de erros**, no Painel da plataforma.
- **Painel da plataforma** (`/plataforma`): todas as empresas, uso, suspender e reativar.

## Acesso

- **Criar conta:** nome, empresa, e-mail e senha. Quem cria vira **Administrador** da empresa.
- **Google:** também dá para entrar com a conta Google.
- **Esqueci minha senha:** envia um link por e-mail (vale 60 minutos).
- **Equipe:** o administrador convida o funcionário pelo e-mail em **Configurações > Equipe**.
- **Separação dos dados:** todas as tabelas têm `company_id` e o sistema filtra tudo pela empresa de quem está logado.

## Variáveis no Railway

```
APP_URL=https://SEU-DOMINIO
OWNER_EMAIL=seu@gmail.com              # dono da plataforma: vê /plataforma, recebe backup e avisos de erro

GOOGLE_CLIENT_ID=...apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=...

MAIL_MAILER=brevo
BREVO_API_KEY=xkeysib-...
MAIL_FROM_ADDRESS=seu@gmail.com
MAIL_FROM_NAME="Meu Cash"

BACKUP_EMAIL=                          # opcional: outro e-mail para o backup (vários, separados por vírgula)
ERROR_EMAIL=                           # opcional: outro e-mail para os avisos de erro

LEGAL_NAME="Seu nome ou razão social"
LEGAL_DOCUMENT="CPF ou CNPJ"
LEGAL_CITY="Cidade - UF"
SUPPORT_EMAIL=seu@email.com
```

O **Pre-deploy Command** continua `php artisan migrate --force`. O deploy é pelo `git push`.

## Backup diário

- Todo dia, depois das 3h, o sistema gera uma cópia completa do banco (`.sql.gz`) e manda por e-mail para o `OWNER_EMAIL` (ou `BACKUP_EMAIL`).
- Se o arquivo passar de 8 MB, o e-mail avisa e você baixa pelo painel.
- No **Painel da plataforma**: data do último backup, **Fazer backup agora** e **Baixar backup**.
- Para restaurar: descompacte o arquivo e importe o `.sql` no MySQL (phpMyAdmin, ou `mysql -u ... nome_do_banco < arquivo.sql`).
- O backup roda quando alguém usa o sistema depois das 3h. Para rodar mesmo sem ninguém usar, crie um Cron no Railway com `php artisan meucash:backup` e horário `0 6 * * *` (3h de Brasília).
- Precisa do e-mail configurado (Brevo).

## Monitoramento de erros

- Todo erro do sistema fica registrado em **Painel da plataforma > Ver erros**, agrupado (o mesmo erro vira uma linha só, com a contagem).
- Quando aparece um erro novo, você recebe um e-mail com a página, a empresa e o usuário. O mesmo erro avisa no máximo uma vez por hora.
- Depois de corrigir, clique em **Marcar como resolvido**. Se o erro voltar, ele reaparece na lista e você é avisado de novo.
- Erros comuns (página não encontrada, formulário com campo faltando, sessão expirada) não entram.

## E-mail

O Railway bloqueia SMTP nos planos Free/Hobby, por isso o sistema usa a **Brevo** por HTTPS (grátis até 300 e-mails por dia):

1. Crie conta em https://www.brevo.com e confirme o e-mail remetente em **Senders**.
2. Em **SMTP & API > API Keys**, gere uma chave (começa com `xkeysib-`) e coloque em `BREVO_API_KEY`.

## Google (login opcional)

1. Em https://console.cloud.google.com, crie um projeto e configure a **Tela de consentimento OAuth** (Externo, publicada).
2. Em **Credenciais**, crie um **ID do cliente OAuth** (Aplicativo da Web) com o redirecionamento `https://SEU-DOMINIO/auth/google/callback`.
3. Copie o ID e a chave para `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET`.

## Tarefas automáticas (opcional)

Os avisos de parcelas e de estoque e o backup também rodam quando alguém abre o sistema. Para rodarem sozinhos, crie Crons no Railway:

- `php artisan meucash:alertas`, uma vez por dia;
- `php artisan meucash:resumo-semanal`, com horário `0 11 * * 1` (segunda, 8h de Brasília);
- `php artisan meucash:backup`, com horário `0 6 * * *` (3h de Brasília).

## No dia a dia

- **Estoque mínimo:** no cadastro do produto. Quando chega no mínimo, o produto aparece no filtro **Repor**, no Dashboard e nas notificações.
- **Repor estoque:** na página do produto, **Repor estoque** soma as unidades, recalcula o custo médio e lança a compra no caixa.
- **Desfazer:** depois de excluir produto, cliente, categoria, despesa ou lançamento, aparece **Desfazer** (ou `Ctrl+Z`), por até 10 minutos.
- **Atalhos:** aperte `?` dentro do sistema para ver todos.
- **Área do cliente:** o cliente entra em `SEU-DOMINIO/cliente` com o código que sai no comprovante. Na ficha do cliente dá para copiar, enviar pelo WhatsApp ou gerar outro código.
- **Exportar dados:** Configurações > Exportar dados (CSV de cada tabela ou tudo em .zip).
- **Importar produtos:** Produtos > Importar (CSV; baixe o modelo na própria tela).

## Perfis

- **Administrador:** acesso total à empresa, inclusive Financeiro, Relatórios, Configurações e Equipe.
- **Funcionário:** vendas, clientes, produtos, estoque e recebimentos. Não vê Financeiro, Relatórios e Configurações, e não exclui nem cancela registros.

## Regras financeiras

- **Faturamento:** total das vendas concluídas no período.
- **Recebido:** entradas reais no caixa.
- **A receber:** parcelas pendentes e vencidas.
- **Saldo:** saldo inicial (Configurações) + entradas − saídas.
- **Lucro:** valor da venda − custo dos produtos (compra + despesas).

## Uso local (XAMPP)

```
composer install
copy .env.example .env
php artisan key:generate
php artisan meucash:instalar
php artisan serve
```

Acesse `http://localhost:8000` com o MySQL do XAMPP ligado. No Windows, o `instalar.bat` faz esses passos.

## Testes

```
php artisan test
```

## Estrutura

```
app/Models/Concerns/BelongsToCompany.php   filtro automático por empresa
app/Support/Tenant.php                     empresa do usuário logado
app/Http/Middleware/IdentifyCompany.php    identifica a empresa em cada acesso
app/Services/BackupService.php             backup diário do banco
app/Services/ErrorMonitor.php              registro e aviso de erros
app/Services/SaleService.php               vendas, estoque e caixa
app/Services/UndoService.php               botão Desfazer
```
