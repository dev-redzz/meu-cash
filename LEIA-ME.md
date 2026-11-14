# Meu Cash

Sistema de vendas, estoque, parcelas e caixa para lojas, no formato SaaS (várias empresas no mesmo sistema), em Laravel 12.
Cada empresa só enxerga os próprios dados.

## O que o sistema faz

- **Vendas à vista e parceladas:** entrada, parcelas, vencimentos, comprovante em PDF e cobrança pelo WhatsApp.
- **Estoque:** lucro de cada produto, preço de venda sugerido, despesas (peças, frete), estoque mínimo com aviso de reposição e entrada de mercadoria com custo médio.
- **Clientes e área do cliente:** cada cliente consulta as próprias compras e parcelas com um código de acesso.
- **Caixa, relatórios, metas e comissão de vendedores.**
- **Equipe:** administrador e funcionário, com permissões diferentes.
- **Backup diário** do banco por e-mail e **monitoramento de erros**, no Painel da plataforma.
- **Painel da plataforma** (`/plataforma`): todas as empresas, uso, suspender e reativar, erros e sugestões da versão beta.
- **Troca:** o cliente devolve peças e leva outras; estoque, venda, caixa e parcelas se ajustam.
- **Etiquetas com código de barras:** térmica ou folha A4, com preço; produto sem código ganha um código da loja.
- **Catálogo online:** página pública da loja com os produtos em estoque e pedido pelo WhatsApp.
- **Nota fiscal (NFC-e):** emissão pela Nuvem Fiscal, automática ou pelo botão na venda, com DANFE, XML e cancelamento.
- **Pix na área do cliente:** a loja conecta o Mercado Pago dela e o cliente paga a parcela pelo Pix, com baixa automática.
- **Assinatura das lojas:** teste grátis, plano mensal ou anual pelo Mercado Pago e modo só consulta quando vence (desligada durante o beta).
- **Versão beta:** selo Beta e botão **Enviar sugestão** dentro do sistema; as mensagens chegam por e-mail e ficam em Plataforma > Sugestões.

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

- Todo dia, depois das 3h, o sistema gera uma cópia completa do banco (`.zip` com o `.sql` dentro) e manda por e-mail para o `OWNER_EMAIL` (ou `BACKUP_EMAIL`).
- Se o arquivo passar de 8 MB, o e-mail avisa e você baixa pelo painel.
- No **Painel da plataforma**: data do último backup, **Fazer backup agora** e **Baixar backup**.
- Para restaurar: descompacte o `.zip` e importe o `.sql` no MySQL (phpMyAdmin, ou `mysql -u ... nome_do_banco < arquivo.sql`).
- O backup roda quando alguém usa o sistema depois das 3h. Para rodar mesmo sem ninguém usar, crie um Cron no Railway com `php artisan meucash:backup` e horário `0 6 * * *` (3h de Brasília).
- Precisa do e-mail configurado (Brevo).

## Troca

Na página da venda, botão **Troca**:

1. Marque quantas unidades de cada item o cliente devolveu.
2. Bipe ou procure os produtos que ele vai levar (o preço pode ser ajustado).
3. O sistema mostra a diferença: **Cliente paga** ou **Loja devolve**.
   - **Recebo/Devolvo agora:** entra ou sai do caixa na forma escolhida.
   - **Somar na última parcela / Abater das parcelas** (venda no crediário): ajusta as parcelas em aberto, da última para a primeira.
4. A venda passa a mostrar os itens que o cliente ficou, com total e lucro atualizados. O histórico fica no quadro **Trocas** da venda.

Para devolver tudo, cancele a venda. Venda com NFC-e: a troca não altera a nota (confira a nota de devolução com o contador).

## Etiquetas

**Produtos > Etiquetas** (ou **Etiquetas** na página do produto): escolha a quantidade de cada produto e o modelo:

- térmica 50 × 30, 40 × 25 ou 33 × 22 mm (3 colunas);
- folha A4 com 21 ou 65 etiquetas.

Abre uma página pronta para imprimir com nome, preço e código de barras (Code 128, lido por qualquer leitor). Produto sem código ganha um **EAN-13 interno** (começa com 2), que já serve para bipar na venda e na troca. Na impressora térmica, escolha o papel do mesmo tamanho da etiqueta e margens "Nenhuma".

## Catálogo online

**Configurações > Catálogo online**: ligue, escolha o endereço (ex.: `SEU-DOMINIO/loja/boutique-da-ana`) e o WhatsApp que recebe os pedidos.

- Aparecem só produtos em estoque, com preço e marcados com **Mostrar no catálogo online** (no cadastro do produto). Esgotou, sai sozinho.
- O cliente busca, filtra por categoria, monta o pedido e toca em **Enviar pedido pelo WhatsApp**: chega uma mensagem pronta com itens, quantidades e total.
- Custo, lucro e observações do produto nunca aparecem.

## Nota fiscal (NFC-e)

O Meu Cash emite a **NFC-e** (cupom fiscal eletrônico do consumidor, modelo 65) pela **Nuvem Fiscal**, que assina e transmite para a SEFAZ. Funciona para empresas do **Simples Nacional** e **MEI**.

**Você (plataforma), uma vez só:**

1. Crie a conta em https://www.nuvemfiscal.com.br e, no console, crie as credenciais da API (Client ID e Client Secret).
2. No Railway, adicione:
   ```
   NUVEMFISCAL_CLIENT_ID=...
   NUVEMFISCAL_CLIENT_SECRET=...
   NUVEMFISCAL_SANDBOX=true      # false quando for emitir de verdade
   ```
   Com `true` tudo vai para o ambiente de testes da Nuvem Fiscal. Cada loja é cadastrada como uma empresa dentro da sua conta; a Nuvem Fiscal cobra de você conforme o plano dela.

**Cada loja, em Configurações > Nota fiscal:**

1. Dados da empresa (CNPJ, Inscrição Estadual, razão social, regime) e endereço. O código do município é buscado pelo CEP.
2. **CSC** (ID e código), gerado no portal da SEFAZ do estado. Homologação e produção têm CSC diferentes.
3. **Certificado A1** (.pfx + senha). Vai direto para a Nuvem Fiscal; não fica guardado no Meu Cash.
4. Ambiente **Homologação** para testar; depois **Produção**. A numeração é separada por ambiente; se a loja já emitia em outro sistema, informe o próximo número.
5. Opcional: **Emitir automaticamente** ao concluir cada venda.

**Produtos:** cada produto precisa de **NCM** (seção "Dados fiscais" no cadastro). CFOP e CSOSN usam o padrão da loja se ficarem vazios.

**Na venda:** painel **Nota fiscal** com **Emitir NFC-e**, **DANFE** (PDF), **XML** e **Cancelar nota** (a maioria dos estados aceita só nos primeiros 30 minutos). Nota rejeitada mostra o motivo da SEFAZ; depois de corrigir, **transmitir de novo** usa o mesmo número. Cancelar a venda tenta cancelar a nota junto; venda com nota autorizada não pode ser excluída.

Formas de pagamento na nota: dinheiro 01, crédito 03, débito 04, Pix 17, transferência 18, outro 99; no parcelado, a entrada sai com a forma usada e o restante como **05 · Crédito loja**.

## Pix na área do cliente (dinheiro vai para a loja)

1. A loja entra em **Configurações > Recebimentos** e cola o **Access Token** do Mercado Pago dela (a própria tela explica onde pegar).
2. O sistema testa o token na hora e guarda criptografado.
3. Na área do cliente aparece **Pagar com Pix** em cada parcela em aberto. O cliente lê o QR Code ou copia o código.
4. Quando o Mercado Pago confirma, a parcela é baixada sozinha (com lançamento no caixa e notificação). A tela do cliente também se atualiza sozinha.

Segurança: o aviso do Mercado Pago (webhook, em `/webhooks/mercadopago/loja/{id}`) só diz qual pagamento mudou; o sistema sempre consulta o pagamento na API e confere a referência e o valor antes de dar baixa. O endereço do aviso usa o `APP_URL`, que precisa estar em https.

Para testar: use o token de **Credenciais de teste** (`TEST-...`) e pague com um usuário de teste do Mercado Pago.

## Assinatura das lojas (dinheiro vai para você)

Fica **desligada** enquanto `BILLING_ENABLED=false` (versão beta). Para ligar:

```
BILLING_ENABLED=true
BILLING_STARTS_AT=2026-12-01        # dia em que começou a cobrar: lojas antigas ganham o teste a partir daqui
BILLING_TRIAL_DAYS=14
BILLING_GRACE_DAYS=5
BILLING_PRICE_MONTHLY=39.90
BILLING_PRICE_YEARLY=399.00
MP_ACCESS_TOKEN=APP_USR-...          # Access Token da SUA conta Mercado Pago
```

- A loja vê **Configurações > Assinatura**, escolhe mensal ou anual e paga no Mercado Pago (Pix, cartão ou boleto).
- Pagou: o período soma a partir do fim do período atual (ou do fim do teste). A confirmação chega pelo aviso em `/webhooks/mercadopago/plataforma` e também quando a pessoa volta do Mercado Pago.
- Faltando poucos dias aparece um aviso no topo. Vencida, a loja tem `BILLING_GRACE_DAYS` de tolerância; depois fica **só para consulta** (vê e exporta, mas não vende nem cadastra).
- Sua empresa (a do `OWNER_EMAIL`) nunca paga. No **Painel da plataforma**, no botão `⋯` de cada empresa: **Dar 30 dias grátis** e **Não cobrar assinatura**.

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

- **Preço sugerido:** no cadastro do produto, o sistema sugere o preço de venda a partir do custo e do lucro desejado (padrão em Configurações > Empresa, 40%).
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
app/Services/ExchangeService.php           trocas
app/Services/CatalogService.php            catálogo online
app/Support/Barcode.php                    código de barras das etiquetas
app/Services/Fiscal/NuvemFiscal.php        conversa com a API da Nuvem Fiscal
app/Services/Fiscal/InvoiceService.php     montagem, emissão e cancelamento da NFC-e
app/Services/Payments/MercadoPago.php      conversa com a API do Mercado Pago
app/Services/Payments/PixService.php       Pix das parcelas na área do cliente
app/Services/Payments/SubscriptionService.php  assinatura das lojas
```
