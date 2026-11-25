# Roteiro de apresentação

[Voltar ao README](../README.md) · [Execução](EXECUCAO.md) · [Chat](CHAT_TEMPO_REAL.md) · [Pagamentos](PAGAMENTOS.md) · [Rastreabilidade](RASTREABILIDADE.md)

Roteiro da [#25](https://github.com/IgorMarcoli/TheMerchant/issues/25) para demonstrar o TheMerchant em ambiente local, do cadastro à moderação. Cada etapa indica os requisitos que ela mostra. A coluna "Já validado em" aponta a evidência registrada antes; o que não tem registro precisa ser anotado durante o ensaio (modelo na seção 6 de [TESTES.md](TESTES.md#6-evidências-e-conclusão)).

O deploy do Render está respondendo HTTP 500 (pendência da #16). **Apresente localmente.**

## 1. Contas de demonstração

Criadas pelo `DatabaseSeeder`. As senhas estão em `database/seeders/DatabaseSeeder.php` e na tabela da seção 4 de [EXECUCAO.md](EXECUCAO.md#4-dados-de-demonstração-e-imagens). São exclusivas de demonstração.

| Conta | E-mail | Papel na demo |
| :--- | :--- | :--- |
| Administrador | `admin@themerchant.local` | Painel, contas, categorias e denúncias |
| Igor Marcoli (Vendedor Pro) | `vendedor@themerchant.local` | Vende as skins de CS2 |
| Marina Costa (Coach) | `vendedora2@themerchant.local` | Vende coaching de Valorant e LoL |
| João Pedro (Comprador) | `comprador@themerchant.local` | Compra, conversa, avalia e denuncia |

O `DemoCatalogSeeder`, chamado pelo `DatabaseSeeder`, cria 11 anúncios sem foto, todos com preço acima de zero (com R$ 0,00 o PIX não é gerado). Os vendedores começam sem avaliações.

## 2. Preparação (antes da apresentação)

### 2.1 Banco descartável

> ⚠️ `php artisan migrate:fresh --seed` **apaga todas as tabelas** do banco configurado. Rode somente em um banco local descartável, criado para a demo. Nunca no Supabase compartilhado nem em base com dados a preservar. Confira `DB_CONNECTION` e `DB_DATABASE` antes.

Exemplo com SQLite local (o mesmo usado nas homologações das #16, #21 e #32):

```powershell
if (!(Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File }
php artisan config:clear
php artisan migrate:fresh --seed
php artisan storage:link
```

### 2.2 Variáveis do `.env`

Sem valores reais no repositório. Preencha chaves e segredos só no `.env` local.

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
DB_DATABASE=            # vazio usa database/database.sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=          # valores aleatórios gerados para a demo
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_ALLOWED_ORIGINS=localhost,127.0.0.1
PAYMENT_GATEWAY=abacatepay
ABACATEPAY_API_KEY=     # chave de DESENVOLVIMENTO da AbacatePay
ABACATEPAY_WEBHOOK_SECRET=
ABACATEPAY_DEV_MODE=true
MAIL_MAILER=log         # ou o bloco SMTP do Resend, ver seção 5
```

Depois de mudar o `.env`: `php artisan config:clear` e reinicie worker e Reverb.

### 2.3 Subir os processos

Quatro terminais na raiz do projeto:

| Terminal | Comando | Para quê |
| :--- | :--- | :--- |
| 1 | `php artisan serve --host=127.0.0.1 --port=8000` | Aplicação |
| 2 | `php artisan reverb:start --debug` | WebSocket do chat |
| 3 | `php artisan queue:work --tries=3` | E-mails, webhooks e avisos do chat |
| 4 | `php artisan schedule:work` | `payments:reconcile` a cada minuto (expiração de reservas e PIX pendentes) |

### 2.4 Duas sessões no navegador

Use dois navegadores diferentes, ou uma janela normal e uma anônima. Outra opção, usada na #32: uma sessão em `http://localhost:8000` e outra em `http://127.0.0.1:8000`, que têm cookies separados. As duas origens precisam estar em `REVERB_ALLOWED_ORIGINS`.

### 2.5 Conferência rápida

- `http://127.0.0.1:8000/up` responde.
- O chat mostra "Tempo real ativo" no topo da conversa.
- Opcional: `php artisan test` e `npm test` antes de começar (último registro: 234 e 23 testes aprovados, #21).

## 3. Roteiro

| # | Etapa | Quem | Ações | Resultado esperado | Requisitos | Já validado em |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Vitrine e catálogo | Visitante | Abrir `/`, clicar em um jogo, abrir `/anuncios`, combinar jogo, categoria e faixa de preço | Catálogo filtrado; paginação mantém os filtros | RF07, RF08 | `CatalogTest` |
| 2 | Cadastro | Visitante | `/cadastro` com nome, e-mail e senha; mostrar erro com senha curta | Conta criada sem perfil de vendedor; erro junto ao campo | RF01, RF02 | #21 |
| 3 | Verificação de e-mail | Nova conta | Abrir o link de confirmação (no `storage/logs/laravel.log` com `MAIL_MAILER=log`, ou na caixa de entrada com o Resend) | Perfil mostra o e-mail confirmado | RF03 | #25 (Resend) |
| 4 | Recuperação de acesso | Nova conta | Sair, "Esqueci minha senha", abrir o link, definir nova senha, entrar; abrir o mesmo link de novo | Login com a senha nova; segundo uso do link recusado | RF03 | `AccountRecoveryTest` |
| 5 | Conversa antes da compra | Comprador + Marina | Comprador abre um coaching da Marina e clica em "Falar com o vendedor"; pergunta sobre horário. Marina vê na outra sessão | Mensagem aparece sem recarregar; contador de não lidas da Marina sobe e zera ao abrir | RF21, RF22, RF23 | #32, #21 |
| 6 | Carrinho com dois vendedores | Comprador | Adicionar uma skin do Igor e um coaching da Marina com 2 sessões | Skin com quantidade fixa 1; total = skin + 2 × preço da sessão | RF09 | #21 |
| 7 | Checkout | Comprador | Revisar, aceitar as regras de entrega e finalizar | Pedido `pendente` com QR Code PIX gerado na AbacatePay (sandbox); anúncios ainda publicados, com reserva de 15 min | RF10, RF11 | #16, #21 |
| 8 | Confirmação do pagamento | Comprador | Clicar em "Simular pagamento (modo dev)". Alternativa: pagar no painel de dev da AbacatePay e usar "Já paguei, atualizar status", ou receber o webhook real (seção 4) | Pedido `pago`, itens `em_entrega`, skin `vendido`, capacidade do coaching reduzida em 2 | RF11, RF12 | #16, #21 |
| 9 | Painel de vendas | Igor e Marina | Cada um abre "Minhas vendas" | Cada vendedor vê só o próprio item | RF14 | #21 |
| 10 | Entrega por item | Igor | "Confirmar entrega" da skin | Item `entregue`; pedido continua `pago` porque o coaching está pendente | RF14 | #21 |
| 11 | Avaliação antes de concluir o pedido | Comprador | Avaliar a skin com 5 estrelas | Avaliação aceita; nota do Igor recalculada (5,00, 1 avaliação); coaching ainda sem formulário | RF15 | #21 |
| 12 | Coaching por sessões | Marina | "Falar com comprador" (mesma conversa da etapa 5); "Registrar sessão" 1 e 2 | Item `entregue` só após a 2ª sessão; pedido `concluido` | RF14, RF21 | #21 |
| 13 | Segunda avaliação | Comprador | Avaliar o coaching com 4 estrelas | Nota da Marina 4,00 na página pública | RF15 | #21 |
| 14 | Filtro por avaliação | Visitante | No catálogo, "Nota mínima do vendedor" = 4 e ordenar por "Reputação do vendedor"; depois nota mínima 5 | Com 4: anúncios do Igor e da Marina; com 5: só os do Igor. Vendedor sem avaliações fica por último na ordenação | RF07 | `CatalogTest` (sem registro manual) |
| 15 | Denúncia | Comprador | Em uma skin ainda publicada, "Denunciar anúncio" com motivo e detalhes | Denúncia registrada como aberta | RF16 | `ListingDetailTest` (sem registro manual) |
| 16 | Painel administrativo | Admin | `/admin/dashboard`, "Contas e vendedores", "Categorias e jogos" (inativar e reativar uma categoria) | Volume transacionado e pedidos pagos refletem a compra; ações só para admin | RF04, RF17, RF18 | `AdminTest`, #21 |
| 17 | Moderação e restauração | Admin + Igor | Na fila de "Denúncias", julgar procedente com parecer e bloquear o anúncio; conferir que sumiu do catálogo; "Restaurar anúncio" com motivo; Igor reativa em "Meus anúncios" | Anúncio bloqueado sai do catálogo; restaurado volta **pausado** e só reaparece quando o vendedor reativa; decisões no histórico | RF19, RF20 | `ReportModerationTest` (sem registro manual) |
| 18 | Chat com duas sessões | Comprador + Marina | Trocar mensagens com as duas janelas lado a lado; enviar `<b>teste</b>`; opcional: parar o Reverb, enviar, subir de novo | Tempo real; texto exibido sem virar HTML; sem Reverb, o indicador muda para "Sincronizado" e as mensagens chegam pelo HTTP | RF22, RF23, RNF03 | #32 |
| 19 | Fechamento | Equipe | Mostrar a [matriz de rastreabilidade](RASTREABILIDADE.md) e as pendências | Implementado separado de pendente | Todos | Este documento |

Para mostrar a suspensão com a conversa aberta (#32), suspenda a Marina em "Contas e vendedores" e reative ao final. Faça isso só no fim, porque a conta perde acesso até ser reativada.

## 4. Webhook real (opcional)

Em `localhost` a AbacatePay não alcança o webhook. Para mostrá-lo, como na #16:

1. Abra um túnel HTTPS temporário para `127.0.0.1:8000`.
2. No painel de desenvolvimento da AbacatePay, cadastre o webhook `transparent.completed` apontando para `https://<túnel>/api/webhooks/payment?webhookSecret=<ABACATEPAY_WEBHOOK_SECRET>`.
3. Pague o PIX pelo painel de desenvolvimento e não clique em nada no site. O pedido muda para `pago` sozinho, e o evento aparece em `payment_events`.

Não mostre a chave nem o segredo na tela. Se o túnel falhar, use o botão "Simular pagamento (modo dev)", que também consulta o provedor.

## 5. Se algo falhar

| Sintoma | O que fazer |
| :--- | :--- |
| Chat mostra "Sincronizado" em vez de "Tempo real ativo" | Reverb parado. Suba de novo `php artisan reverb:start`. O navegador reconecta sozinho em cerca de 30 s e reautoriza o canal. As mensagens não se perdem: chegam pela sincronização HTTP |
| Mensagem gravada mas sem aviso instantâneo | Worker parado. Suba `php artisan queue:work`; os avisos pendentes são enviados sem duplicar. Sem worker a mensagem aparece em até 25 s pela verificação HTTP |
| Jobs em `failed_jobs` | `php artisan queue:failed`. Avisos de chat que falharam com o Reverb fora do ar podem ser ignorados: o texto vem sempre pelo HTTP. Corrija a causa antes de `queue:retry` |
| Configuração mudou e nada aconteceu | `php artisan config:clear` e `php artisan queue:restart`; reinicie o Reverb |
| Cadastro dá erro ao enviar a confirmação | Com SMTP do Resend, a chave pode estar inválida. Sem domínio verificado, o Resend só entrega para o e-mail da própria conta Resend: cadastre com esse e-mail ou volte para `MAIL_MAILER=log`. Deixe um único bloco `MAIL_` no `.env` (#25) |
| "O gateway recusou a criação do pagamento" | Conferir a chave de desenvolvimento, `ABACATEPAY_DEV_MODE=true`, preço acima de zero e `storage/logs/laravel.log`. Textos enviados ao provedor não podem ter travessão |
| PIX não gerado por queda do gateway | O pedido fica `pendente` com a reserva; use "Gerar PIX novamente" |
| Pedido expirou durante a fala | A reserva dura 15 min. Refaça o checkout; o pedido antigo é cancelado pela reconciliação |
| Erro 419 | Sessão expirada ou mistura de `localhost` e `127.0.0.1` na mesma janela. Recarregue a página e entre de novo |

## 6. Registro da execução

Ao ensaiar, registre na #25: data, commit (`git rev-parse --short HEAD`), ambiente, contas, etapas, resultado esperado e obtido. As etapas 14, 15, 16 e 17 ainda não têm evidência manual registrada; elas fecham as lacunas L2 e L3 da [matriz](RASTREABILIDADE.md#lacunas-encontradas-nesta-revisão).
