# Meu Cash

Sistema de vendas e controle financeiro em Laravel 12, rodando no XAMPP (Apache + MySQL).

## Requisitos

- XAMPP com **PHP 8.2 ou superior** (confira com `C:\xampp\php\php.exe -v`)
- Composer (https://getcomposer.org). Na instalação, aponte para `C:\xampp\php\php.exe`
- Internet no primeiro uso (dependências, Bootstrap e gráficos vêm por CDN)

## 1. Preparar o PHP do XAMPP

Abra `C:\xampp\php\php.ini` e confira se estas linhas estão **sem** `;` no início:

```
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
```

Depois de alterar, reinicie o Apache no XAMPP Control Panel.

## 2. Copiar o projeto

Extraia a pasta em `C:\xampp\meu-cash` (fora do `htdocs`, por segurança).

## 3. Instalar

1. No XAMPP Control Panel, clique em **Start** no **Apache** e no **MySQL**.
2. Dê dois cliques em `instalar.bat`.

O instalador baixa as dependências, cria o `.env`, cria o banco `meu_cash`, as tabelas, as categorias e o usuário administrador.

Se preferir criar o banco manualmente: em `http://localhost/phpmyadmin`, clique em **Novo**, nome `meu_cash`, agrupamento `utf8mb4_unicode_ci`, **Criar**. O instalador apenas usa o banco existente.

Comandos equivalentes, se quiser rodar no terminal dentro da pasta:

```
composer install
copy .env.example .env
php artisan meucash:instalar
```

## 4. Configurar o Apache (Virtual Host)

1. Em `C:\xampp\apache\conf\httpd.conf`, confirme que estas linhas estão sem `#`:
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   Include conf/extra/httpd-vhosts.conf
   ```
2. No final de `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, cole o conteúdo de `docs/apache-vhost.conf`.
3. Abra o Bloco de Notas **como administrador**, abra `C:\Windows\System32\drivers\etc\hosts` e adicione:
   ```
   127.0.0.1 meucash.test
   ```
4. Reinicie o Apache.

## 5. Acessar

Abra `http://meucash.test`

- E-mail: `admin@meucash.local`
- Senha: `admin123`

Troque a senha em **Configurações > Usuários** logo no primeiro acesso. Para usar outro e-mail/senha inicial, altere `ADMIN_EMAIL` e `ADMIN_PASSWORD` no `.env` antes de instalar.

## Configurações do .env

| Variável | Padrão | Para que serve |
|---|---|---|
| `APP_URL` | `http://meucash.test` | Endereço do sistema (deve bater com o Virtual Host) |
| `DB_DATABASE` | `meu_cash` | Nome do banco no MySQL do XAMPP |
| `DB_USERNAME` / `DB_PASSWORD` | `root` / vazio | Usuário padrão do MySQL do XAMPP |
| `APP_DEBUG` | `true` | Mude para `false` depois que tudo estiver funcionando |

Depois de mudar o `.env`, rode `php artisan optimize:clear`.

## Perfis

- **Administrador**: acesso total.
- **Funcionário**: vendas, clientes, produtos, estoque, serviços e notificações. Não acessa financeiro, relatórios e configurações, e não exclui nem cancela registros.

## Regras financeiras

- **Faturamento**: total das vendas concluídas + serviços em andamento ou concluídos.
- **Recebido**: entradas reais no caixa (entradas, parcelas pagas, lançamentos).
- **A receber**: parcelas pendentes e vencidas.
- **Saldo**: saldo inicial (Configurações) + todas as entradas − todas as saídas.
- **Lucro**: valor da venda − custo dos produtos (compra + despesas); no serviço, valor − despesas.
- Cadastrar produto pode lançar a compra como saída no caixa (opção marcada por padrão).
- Cancelar venda devolve o estoque e cancela parcelas abertas; dinheiro já recebido continua no caixa. Se devolver ao cliente, lance uma saída manual no Financeiro.

## Backup

- **Pelo sistema**: Configurações > Backup > Gerar backup. Arquivos em `storage/app/backups`.
- **Pelo phpMyAdmin**: banco `meu_cash` > Exportar > SQL > Executar.
- Para restaurar, use a mesma tela do sistema ou phpMyAdmin > Importar.

## Avisos de vencimento

Parcelas vencidas são atualizadas automaticamente quando alguém usa o sistema (no máximo a cada 30 minutos). Para forçar: **Notificações > Verificar vencimentos**, ou `php artisan meucash:alertas`.

## WhatsApp

O botão abre o WhatsApp Web/aplicativo com a mensagem pronta (link `wa.me`). Os textos ficam em Configurações. Toda mensagem fica registrada na tabela `whatsapp_messages`. A estrutura já tem `WHATSAPP_DRIVER`, `WHATSAPP_API_TOKEN` e `WHATSAPP_PHONE_NUMBER_ID` no `.env` para integrar a API oficial no futuro (`app/Services/WhatsAppService.php`).

## Problemas comuns

| Sintoma | Solução |
|---|---|
| `could not find driver` | Ative `extension=pdo_mysql` no php.ini |
| `SQLSTATE[HY000] [2002]` | O MySQL do XAMPP não está iniciado |
| Página "Not Found" do Apache em outras rotas | `mod_rewrite` desativado ou falta `AllowOverride All` no vhost |
| `meucash.test` não abre | Faltou a linha no arquivo hosts ou reiniciar o Apache |
| Fotos não aparecem | Rode `php artisan storage:link` |
| Erro 419 ao enviar formulário | Sessão expirou; recarregue a página |
| Erro 500 | Veja `storage/logs/laravel.log` |

## Estrutura

```
app/Enums              status e formas de pagamento
app/Http/Controllers   controllers enxutos por módulo
app/Http/Requests      validações
app/Http/Middleware    perfil, usuário ativo, alertas, cabeçalhos de segurança
app/Models             Eloquent
app/Services           regras de negócio (vendas, parcelas, financeiro, relatórios, backup, WhatsApp)
database/migrations    tabelas
database/seeders       administrador, configurações e categorias
resources/views        telas em Blade + Bootstrap 5
tests/Feature          testes automatizados (php artisan test)
```
