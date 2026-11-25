# Executar o TheMerchant localmente

[Voltar ao README](../README.md) · [Testes e validações](TESTES.md)

Guia revisado em 25/11/2026. Os comandos partem da raiz do repositório. Para a demonstração completa (app, Reverb e worker), siga também o [roteiro de apresentação](ROTEIRO_APRESENTACAO.md). Os exemplos principais usam PowerShell no Windows; comandos PHP/Composer são equivalentes em Linux/macOS.

## 1. Dependências

| Dependência | Uso |
| :--- | :--- |
| Git | Clonar e versionar |
| PHP 8.2+ | Executar a versão compatível com `composer.lock`; ambiente verificado: 8.2.33 |
| Composer 2 | Instalar dependências PHP |
| PostgreSQL / Supabase | Banco principal; conexão pelo Session pooler e schema `laravel` |
| Node.js 22+/npm | Testes JavaScript e compilação dos assets; o layout atual usa CDN |

O Supabase é configurado como banco PostgreSQL do Laravel. O diagnóstico `php artisan test:db --write` verifica leitura e gravação com rollback. SQLite em memória continua sendo usado nos testes isolados. Veja [o guia de Supabase](SUPABASE.md).

Verifique o terminal:

```powershell
git --version
php --version
php --ini
php -m
composer --version
```

PHP precisa das extensões exigidas pelo Composer e de `pdo_pgsql` para Supabase; `pdo_sqlite` é necessária para os testes isolados. Confira também `fileinfo`, `mbstring`, `openssl`, `dom`, `xml` e `xmlwriter`. Se houver mais de um PHP instalado, use `Get-Command php` no PowerShell para identificar o executável.

## 2. Obter código e dependências

```powershell
git clone https://github.com/IgorMarcoli/TheMerchant.git
cd TheMerchant
composer install
composer check-platform-reqs
```

Em clone já existente, entre na pasta e confira branch/alterações com `git status`. Use `composer install` para respeitar o lockfile; `composer update` altera a resolução de dependências e não faz parte da instalação normal.

O projeto não versiona um arquivo Compose. A dependência Laravel Sail, sozinha, não oferece um ambiente Docker pronto.

## 3. Banco local e arquivo de ambiente

No Supabase, obtenha os dados em **Connect → Session pooler**. Para uma instalação nova, crie o schema no SQL Editor:

```sql
CREATE SCHEMA IF NOT EXISTS laravel;
```

Use as credenciais do banco do seu projeto Supabase. Crie o `.env` sem sobrescrever uma configuração existente:

```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

Em Linux/macOS, a alternativa é:

```bash
test -f .env || cp .env.example .env
```

Edite estes valores, substituindo usuário/senha pelas suas credenciais:

```dotenv
APP_NAME="TheMerchant"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=host_do_session_pooler
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.referencia_do_projeto
DB_PASSWORD="senha_do_banco"
DB_SCHEMA=laravel
DB_SSLMODE=require

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
MAIL_MAILER=log
BROADCAST_CONNECTION=log
```

O `.env.example` usa sessão, cache e fila em banco; as migrations auxiliares de `sessions`, `cache` e `jobs` estão versionadas. O perfil acima permite iniciar com arquivos locais e jobs síncronos. A conexão está definida em `config/database.php`.

Pagamentos usam a AbacatePay (PIX, API v2). Para testar localmente, preencha `ABACATEPAY_API_KEY` com a chave de **desenvolvimento**, `ABACATEPAY_WEBHOOK_SECRET` com um segredo forte e `ABACATEPAY_DEV_MODE=true`; a página do pedido oferece "Simular pagamento (modo dev)". Nunca use a chave de produção em ambiente local. Anúncio com preço R$ 0,00 não gera PIX. Detalhes em [PAGAMENTOS.md](PAGAMENTOS.md).

```powershell
php artisan config:clear
php artisan key:generate
php artisan migrate
php artisan migrate:status
```

Gere `APP_KEY` apenas no primeiro preparo de um ambiente novo. Não troque uma chave de ambiente com dados criptografados/sessões a preservar. Se `migrate` falhar, resolva a conexão antes de continuar.

## 4. Dados de demonstração e imagens

Em banco recém-migrado e vazio:

```powershell
php artisan db:seed
php artisan storage:link
```

O seeder usa `create()` e e-mails/slugs fixos; rodá-lo novamente pode gerar erro de unicidade. Ele cria estas contas fictícias:

| Papel | E-mail | Senha local |
| :--- | :--- | :--- |
| Administrador | admin@themerchant.local | admin123456 |
| Vendedor (skins) | vendedor@themerchant.local | vendedor123456 |
| Vendedora (coaching) | vendedora2@themerchant.local | vendedora123456 |
| Comprador | comprador@themerchant.local | comprador123456 |

Essas credenciais são exclusivas de demonstração. Os vendedores começam sem avaliações e sem vendas; a reputação é recalculada a partir de avaliações reais.

O `DatabaseSeeder` também chama o `DemoCatalogSeeder`, que cria 11 anúncios sem imagens: skins de CS2 do vendedor e coaching de Valorant e League of Legends da vendedora, com preços acima de zero para o PIX ser gerado no sandbox. Rodar esse seeder de novo atualiza pelo slug, sem duplicar. Os anúncios gratuitos `[TESTE]` (R$ 0,00, só para testar carrinho) ficam à parte: `php artisan db:seed --class=FreeListingsSeeder`.

As capas de jogos referenciadas no seeder podem ser disponibilizadas em `storage/app/public/games/` (`cs2.jpg`, `valorant.jpg`, `lol.jpg`, `dota2.jpg`). A ligação `public/storage` precisa existir para imagens enviadas pelo cadastro de anúncios.

Para reconstruir **somente um banco local descartável**, confira primeiro `DB_DATABASE`. O comando abaixo apaga todas as tabelas desse banco:

```powershell
php artisan migrate:fresh --seed
```

Não execute em produção ou em base compartilhada com dados a preservar.

## 5. Iniciar a aplicação

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Mantenha esse terminal aberto e use outro para comandos. Encerre com `Ctrl+C`.

| Endereço | Finalidade |
| :--- | :--- |
| http://127.0.0.1:8000/up | Verificação de inicialização; não homologa banco/pagamento |
| http://127.0.0.1:8000/ | Vitrine com jogos e anúncios recentes |
| http://127.0.0.1:8000/anuncios | Catálogo |
| http://127.0.0.1:8000/login | Login |
| http://127.0.0.1:8000/cadastro | Cadastro |
| http://127.0.0.1:8000/perfil | Perfil autenticado |
| http://127.0.0.1:8000/carrinho | Carrinho |
| http://127.0.0.1:8000/pedidos | Pedidos |
| http://127.0.0.1:8000/vendedor/anuncios | Anúncios do vendedor |
| http://127.0.0.1:8000/vendedor/vendas | Vendas |
| http://127.0.0.1:8000/chat | Conversas |
| http://127.0.0.1:8000/admin/dashboard | Painel administrativo |

A existência de uma rota não comprova que todos os seus cenários funcionam. Confira permissões e fluxos com [TESTES.md](TESTES.md).

Os diagnósticos HTTP foram removidos na [#28](https://github.com/IgorMarcoli/TheMerchant/issues/28). Mesmo assim, mantenha o servidor local vinculado a `127.0.0.1`.

## 6. Frontend hoje e evolução para Vite

O layout `resources/views/layouts/app.blade.php` carrega Tailwind e Alpine por CDN e fontes externas. Uma página sem estilo pode indicar ausência de internet ou bloqueio do CDN.

O pipeline Vite/Laravel está configurado em `vite.config.js`, com entradas em `resources/css/app.css` e `resources/js/app.js`, Tailwind/PostCSS e as mesmas cores do layout. Instale as dependências pelo lockfile e valide:

```powershell
npm ci
npm test
npm run build
```

O build gera `public/build/manifest.json` e os bundles CSS/JS. `npm run dev` inicia o servidor Vite. O layout ainda usa os CDNs e não carrega esses bundles; a compilação não é um pré-requisito para executar a interface atual. Ao integrar `@vite`, substitua os scripts de CDN para não inicializar Alpine duas vezes.

## 7. Filas, e-mails e chat

**Autenticação RF03:** verificação de e-mail e recuperação de senha usam o Laravel, com envio em fila. Consulte [AUTENTICACAO.md](AUTENTICACAO.md) para configurar SMTP, worker e validar os fluxos. `MAIL_MAILER=log` não entrega e-mail real; `QUEUE_CONNECTION=sync` executa imediatamente e deve ficar restrito a testes/desenvolvimento.

**Validar fila real:** as migrations de `jobs` e `failed_jobs` estão versionadas. Execute:

```powershell
php artisan migrate
```

Definir `QUEUE_CONNECTION=database` no `.env` e executar:

```powershell
php artisan config:clear
php artisan queue:work --tries=3 --timeout=60
```

Mantenha o worker em outro terminal e reinicie após alterar código/configuração. Para diagnóstico:

```powershell
php artisan queue:failed
```

Antes de repetir um job, confirme que a operação é idempotente e que a causa foi corrigida. Sessão/cache em banco também exigem migrations próprias; não são necessárias com os drivers `file`.

**Chat:** implementado nas #30 a #32. Com `BROADCAST_CONNECTION=reverb` e `QUEUE_CONNECTION=database`, rode em terminais separados `php artisan serve`, `php artisan reverb:start` e `php artisan queue:work`. Variáveis, produção e roteiro com duas sessões em [CHAT_TEMPO_REAL.md](CHAT_TEMPO_REAL.md). Com `BROADCAST_CONNECTION=log` o chat funciona por sincronização HTTP, sem o aviso instantâneo.

**Pagamentos:** `php artisan schedule:work` roda `payments:reconcile` a cada minuto (expira reservas vencidas, consulta PIX pendentes e repete estornos). Para uma conferência pontual, rode `php artisan payments:reconcile`.

## 8. Problemas frequentes

| Sintoma | Verificação/ação |
| :--- | :--- |
| `php` ou `composer` não reconhecido | Corrigir PATH e abrir novo terminal; identificar PHP com `Get-Command php` |
| `vendor/autoload.php` ausente | Executar `composer install` |
| `No application encryption key` | Preparar `.env` e gerar chave do ambiente novo |
| `could not find driver` | Habilitar `pdo_pgsql` (Supabase) ou `pdo_sqlite` (testes) no PHP CLI mostrado por `php --ini` |
| `Connection refused` / `Access denied` | Conferir host do Session pooler, porta, credenciais e permissão no banco |
| Tabela `sessions`, `cache` ou `jobs` ausente | Executar as migrations auxiliares existentes |
| E-mail/slug duplicado no seed | Seeder já foi executado; não repetir sem necessidade |
| Erro 419 em formulário | Conferir sessão/cookies, `@csrf`, URL e consistência entre localhost/127.0.0.1 |
| Configuração antiga | `php artisan config:clear`; reiniciar servidor/worker |
| Imagem quebrada | Conferir arquivo físico e `public/storage`; no Windows, checar permissão para links simbólicos |
| "O gateway recusou a criação do pagamento" | Conferir `ABACATEPAY_API_KEY` (chave de desenvolvimento), preço acima de R$ 0,00 e `storage/logs/laravel.log` |
| PIX pago mas pedido continua pendente | Em `localhost` o webhook não chega sem túnel; usar "Já paguei, atualizar status" ou "Simular pagamento (modo dev)" |
| Chat mostra "Sincronizado" em vez de "Tempo real ativo" | Reverb parado ou `BROADCAST_CONNECTION=log`; ver [CHAT_TEMPO_REAL.md](CHAT_TEMPO_REAL.md) |

Logs locais:

```powershell
Get-Content storage/logs/laravel.log -Tail 80
```

Revise dados sensíveis antes de compartilhar logs. Use `php artisan test:db --write`: o diagnóstico suporta PostgreSQL e SQLite, sanitiza falhas e retorna código diferente de zero em caso de erro. Diagnósticos e consultas genéricas de tabelas por HTTP foram removidos; não há opção de reativá-los por parâmetros da requisição. Em publicação, configure `APP_DEBUG=false` e `APP_ENV=production`.
