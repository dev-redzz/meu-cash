# Meu Cash

Sistema de vendas e controle financeiro para várias empresas (SaaS), em Laravel 12.
Cada empresa entra com conta Google e só enxerga os próprios dados.

## Como funciona o acesso

- **Primeiro acesso:** a pessoa clica em *Entrar com Google*, escolhe a conta e cadastra a empresa. Ela vira **Administrador** dessa empresa.
- **Equipe:** o administrador vai em **Configurações > Equipe** e libera o e-mail Google do funcionário. No primeiro login, a pessoa já cai na empresa certa.
- **Separação dos dados:** todas as tabelas têm `company_id`. O sistema filtra tudo automaticamente pela empresa de quem está logado; um registro de outra empresa responde "não encontrado".
- Um e-mail Google pertence a uma única empresa.

## 1. Criar as credenciais do Google (uma vez)

1. Acesse https://console.cloud.google.com e crie um projeto (ex.: "Meu Cash").
2. **APIs e serviços > Tela de consentimento OAuth**: tipo **Externo**, preencha nome do app, e-mail de suporte e logo. Publique o app (status "Em produção") para qualquer conta Google poder entrar.
3. **APIs e serviços > Credenciais > Criar credenciais > ID do cliente OAuth**:
   - Tipo: **Aplicativo da Web**
   - **URIs de redirecionamento autorizados**, adicione:
     - `https://SEU-DOMINIO/auth/google/callback` (produção, ex.: Railway)
     - `http://localhost:8000/auth/google/callback` (uso local)
4. Copie o **ID do cliente** e a **Chave secreta**.

O Google só aceita `http://` para `localhost`. Por isso, localmente use `php artisan serve` (porta 8000) em vez do endereço `meucash.test`.

## 2. Variáveis de ambiente

```
GOOGLE_CLIENT_ID=...apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=...
APP_URL=https://SEU-DOMINIO
```

`GOOGLE_REDIRECT_URI` é opcional. Se ficar vazio, o sistema usa `APP_URL/auth/google/callback`.

## 3. Atualizar quem já usava a versão anterior

A migração cria uma empresa com todos os dados que já existiam e mantém os usuários antigos nela.
Como o login agora é só pelo Google, o administrador antigo precisa ter o e-mail da sua conta Google:

- Antes de publicar, coloque `OWNER_EMAIL=seu@gmail.com` nas variáveis. A migração troca o e-mail do primeiro administrador para esse.
- Se o e-mail do administrador já for o seu Gmail, não precisa fazer nada.

O módulo **Serviços** foi removido: serviços, despesas de serviço e as parcelas/pagamentos ligados a eles são apagados na migração. As entradas e saídas já lançadas no caixa continuam no fluxo de caixa.

## 4. Railway

Variáveis do serviço (além das que já existiam):

```
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
OWNER_EMAIL=seu@gmail.com
```

Pode remover `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD`, que não são mais usadas.

O **Pre-deploy Command** continua:

```
php artisan migrate --force
```

Faça o deploy pelo `git push` normalmente.

Para os avisos de vencimento rodarem mesmo sem ninguém usar o sistema, crie um Cron no Railway com `php artisan meucash:alertas` uma vez por dia (opcional: eles também rodam quando alguém abre o sistema).

## 5. Uso local (XAMPP)

```
composer install
copy .env.example .env
php artisan key:generate
php artisan meucash:instalar
php artisan serve
```

Acesse `http://localhost:8000`. O MySQL do XAMPP precisa estar iniciado.

## Perfis

- **Administrador**: acesso total à empresa, inclusive Financeiro, Relatórios, Configurações e Equipe.
- **Funcionário**: vendas, clientes, produtos, estoque e recebimentos. Não vê Financeiro, Relatórios e Configurações, e não exclui nem cancela registros.

## Regras financeiras

- **Faturamento**: total das vendas concluídas no período.
- **Recebido**: entradas reais no caixa.
- **A receber**: parcelas pendentes e vencidas.
- **Saldo**: saldo inicial (Configurações) + entradas − saídas.
- **Lucro**: valor da venda − custo dos produtos (compra + despesas).

## Testes

```
php artisan test
```

Cobrem login Google (simulado), cadastro de empresa, convite de funcionário, isolamento entre empresas, venda parcelada e permissões.

## Estrutura

```
app/Models/Concerns/BelongsToCompany.php   filtro automático por empresa
app/Support/Tenant.php                     empresa do usuário logado
app/Http/Middleware/IdentifyCompany.php    identifica a empresa em cada acesso
app/Services/GoogleAuthService.php         login com Google (OAuth)
app/Services/CompanyProvisioner.php        cria empresa, dono, configurações e categorias
```
