# Matriz de rastreabilidade: requisitos, issues e evidências

[Voltar ao README](../README.md) · [Requisitos](../requirements.md) · [Testes](TESTES.md) · [Roteiro de apresentação](ROTEIRO_APRESENTACAO.md)

Revisão de 25/11/2026, sobre a branch `João` (base `00533a9`). Atende ao item de rastreabilidade das issues [#33](https://github.com/IgorMarcoli/TheMerchant/issues/33) e [#25](https://github.com/IgorMarcoli/TheMerchant/issues/25).

## Como ler

- **Issue:** onde o requisito foi implementado ou validado. O estado da issue no GitHub não substitui a evidência.
- **Critério de aceite:** resumo do critério de [requirements.md](../requirements.md) e da issue.
- **Evidência automatizada:** classe de teste em `tests/Feature`, `tests/Unit` ou `tests/js`. Os testes rodam em SQLite em memória, com gateway e broadcaster simulados.
- **Evidência manual:** registro com data em comentário de issue. Os principais são:
  - **#16 (12/11/2026):** homologação da AbacatePay em sandbox (`devMode`), com PIX gerado no provedor, webhook real, HMAC, idempotência e cenários de falha.
  - **#25 (12/11/2026):** envio real de e-mail pelo SMTP do Resend.
  - **#24 (24/11/2026):** relatório da suíte automatizada (233 testes, 22 testes JS, Pint aprovado) e mapa de cobertura.
  - **#32 (24/11/2026):** chat com Reverb e worker reais, duas sessões, queda e volta do Reverb e do worker, suspensão com a conversa aberta, 375 px e teclado.
  - **#21 (24/11/2026):** compra ponta a ponta com dois vendedores (skin e coaching), entrega, avaliação por item e chat antes e depois da compra (234 testes, 23 testes JS).
- **Situação:** `Implementado` quando há código, teste e, quando o critério pede, evidência manual. `Parcial` quando parte do critério não tem evidência. `Pendente` quando nada foi feito.

## Requisitos funcionais

| RF | Issue(s) | Critério de aceite | Evidência automatizada | Evidência manual | Situação |
| :--- | :--- | :--- | :--- | :--- | :--- |
| RF01 Cadastro | #2, #3 | E-mail único e válido, senha com no mínimo 8 caracteres, conta sem privilégio administrativo; pedido de venda começa `pending` | `AuthTest`, `SellerPermissionsTest`, `AccountRecoveryTest` | #21: segunda vendedora criada pela tela de cadastro e aprovada pelo admin | Implementado |
| RF02 Sessão | #2, #3 | Login, logout com invalidação da sessão; conta suspensa perde acesso | `AuthTest`, `SellerPermissionsTest`, `AdminTest` | #32: sessão antiga recebe `401`/`419` após suspensão | Implementado |
| RF03 Verificação e recuperação | #27 | Link assinado com validade de 60 min; token de recuperação de uso único; resposta igual para conta existente ou não | `AccountRecoveryTest`, `AccountNotificationQueueTest` | #25: `VerifyEmailNotification` aceita pelo SMTP do Resend | Implementado (envio real limitado ao endereço da conta Resend sem domínio verificado) |
| RF04 Permissões | #2, #22 | Toda conta ativa compra; vender exige perfil `approved`; `/admin` só com `is_admin` | `SellerPermissionsTest`, `AdminTest`, `SellerListingTest` | #21: aprovação da vendedora pelo painel | Implementado |
| RF05 Gestão de anúncios | #6 | Criar, editar, pausar e arquivar só pelo dono ou admin; anúncio alheio retorna `403` | `SellerListingTest`, `ListingStorageTest` | #21: skin publicada com 2 fotos e coaching com 3 sessões | Implementado |
| RF06 Dados do anúncio | #5, #6, #29 | Jogo, categoria, título, descrição, preço mínimo R$ 1,00, 1 a 6 imagens; coaching com capacidade, duração e fuso | `SellerListingTest`, `ListingStorageTest` | #21 | Implementado (ver lacuna L6) |
| RF07 Busca e filtros | #7, #8 | Filtros combinados de texto, tipo, jogo, categoria, preço, **nota mínima do vendedor** e ordenação por reputação, preservados na paginação; vendedor sem avaliações fica por último | `CatalogTest`, `StorefrontTest`, `tests/js/catalog-filters.test.mjs` | Nenhuma registrada; incluída no roteiro | Implementado (meta de 500 ms não medida, ver RNF05) |
| RF08 Detalhe do anúncio | #10 | Galeria, dados do item, reputação e vendas do vendedor; compra desabilitada se indisponível; rascunho e bloqueado não aparecem | `ListingDetailTest` | #21: nota da vendedora exibida na página pública | Implementado |
| RF09 Carrinho | #11, #12 | Item único por anúncio, cosmético com quantidade 1, sessões dentro da capacidade, sem anúncio próprio | `CheckoutTest`, `SellerPermissionsTest` | #21: carrinho com skin e 2 sessões, total de R$ 25,00 | Implementado |
| RF10 Checkout | #13, #15 | Pedido `pendente` com preço congelado, reserva de 15 min, tudo ou nada na transação, mesma chave devolve o mesmo pedido | `CheckoutReservationTest` (D01 a D14), `CheckoutTest` | #16 e #21: reservas `active` antes do pagamento, sem baixar estoque | Implementado |
| RF11 Gateway externo | #14, #16 | Cobrança PIX criada na AbacatePay fora da transação, com `externalId` idempotente; nenhum dado de cartão | `AbacatePayGatewayTest`, `CheckoutReservationTest` | #16: três PIX gerados no sandbox; gateway indisponível e "Gerar PIX novamente" | Implementado em sandbox (deploy pendente, ver P3) |
| RF12 Webhook e idempotência | #14, #16 | `webhookSecret` e HMAC válidos; estado conferido no provedor; evento gravado uma vez por `event_id`; estado pago nunca regride | `WebhookPaymentTest`, `CheckoutReservationTest` (D09, D13) | #16: webhook real `transparent.completed`, reenvio com mesmo `id` sem efeito, payload forjado recusado com `401` | Parcial (pendências P2 e P3) |
| RF13 Pedidos do comprador | #17 | Só os próprios pedidos; status do pedido separado da entrega de cada item | `OrderTest` | #21 | Implementado (o rótulo "Pendente" na linha do tempo, BUG-10 da #21, foi corrigido em `e3a8562`, PR #62) |
| RF14 Vendas do vendedor | #18 | Vendedor vê só os próprios itens; entrega só com pedido pago; entrega idempotente; coaching por sessão | `SaleDeliveryTest` | #21: skin entregue, sessões 1 e 2 registradas, pedido concluído só depois | Implementado |
| RF15 Avaliação | #19, #20 | Uma avaliação por `order_item_id` pago e entregue, sem esperar outros vendedores; reputação recalculada | `ReviewTest`, `OrderTest`, `tests/js/review-form.test.mjs` | #21: skin avaliada com o coaching ainda em entrega; notas 5,00 e 4,00 | Implementado |
| RF16 Denúncia | #10, #23 | Só usuário autenticado; motivo e detalhes gravados em `reports` | `ListingDetailTest`, `ReportModerationTest` | Nenhuma registrada; incluída no roteiro | Implementado |
| RF17 Gestão de usuários | #22 | Listar contas, aprovar vendedores, suspender e reativar; sessão existente invalidada | `AdminTest`, `SellerPermissionsTest` | #21 (aprovação) e #32 (suspensão com conversa aberta) | Implementado |
| RF18 Categorias | #22 | Criar, editar, ativar/inativar categorias e jogos; não excluir categoria com anúncios | `AdminTest` | Nenhuma registrada; incluída no roteiro | Implementado |
| RF19 Moderação de anúncios | #23, #24 | Bloquear anúncio por denúncia procedente; restaurar como pausado ou rascunho, com motivo e auditoria; arquivado não é restaurado; republicar depende do vendedor | `ReportModerationTest`, `SellerListingTest` | Nenhuma registrada; incluída no roteiro | Parcial (ver lacuna L4) |
| RF20 Denúncias | #23 | Fila com abertas primeiro, parecer obrigatório, decisão procedente ou improcedente, histórico em `moderation_audits` | `ReportModerationTest` | Nenhuma registrada; incluída no roteiro | Implementado |
| RF21 Iniciar conversa | #30, #31, #32 | Conversa única por comprador, vendedor e anúncio; pelo anúncio, pelo pedido ou pela venda; nunca consigo mesmo | `ChatTest`, `ChatRegressionTest` | #21: mesma conversa antes e depois da compra | Implementado |
| RF22 Texto em tempo real | #30, #32 | Mensagem persistida antes do sinal; canal privado só para participantes ativos; reenvio com o mesmo UUID não duplica | `ChatTest`, `ChatRegressionTest`, `tests/js/chat.test.mjs` | #32: duas sessões com Reverb e worker reais em ambiente local | Implementado localmente (Reverb no Render não validado, ver P4) |
| RF23 Histórico e não lidas | #30, #31, #32 | Histórico paginado, cursor de leitura por participante, contador de não lidas, recuperação após reconexão | `ChatTest`, `ChatRegressionTest`, `tests/js/chat.test.mjs` | #32: contador 3 para 0, mensagens recuperadas após queda do Reverb e do worker | Implementado |

## Requisitos não funcionais

| RNF | Issue(s) | Critério de aceite | Evidência automatizada | Evidência manual | Situação |
| :--- | :--- | :--- | :--- | :--- | :--- |
| RNF01 Responsividade | #3, #4, #8, #12, #31, #25 | Uso de 320 px a desktop sem rolagem lateral, com teclado | `tests/js/*` cobrem comportamento, não layout | #32: chat em 375 px e teclado | Parcial (ajustes de toque, tamanho de texto e Enter no chat entraram no commit `4deb3a6`, ainda sem evidência registrada na #25) |
| RNF02 Hash de senha | #2, #27 | Senha só com hash irreversível (bcrypt do Laravel) | `AuthTest`, `AccountRecoveryTest` | Nenhuma | Implementado (custo do bcrypt no Render não conferido) |
| RNF03 Segurança | #2, #6, #24, #28, #32 | CSRF em todo formulário, Policies, escape do Blade, diagnósticos fechados | `SecurityHardeningTest`, `DiagnosticSecurityTest`, `DatabaseCheckTest`, testes de Policy | #32: XSS exibido como texto; terceira conta recebe `403` | Implementado |
| RNF04 Sem dados de cartão | #14 | Pagamento só por PIX no provedor; payload sem dados do pagador | `AbacatePayGatewayTest`, `WebhookPaymentTest` | #16: payload gravado sanitizado | Implementado |
| RNF05 Desempenho | #7 | Paginação, índices em `(game_id, category_id, status)` e `price`, resposta abaixo de 500 ms com protocolo documentado | `CatalogTest` (paginação) | Nenhuma | Parcial (ver lacuna L1) |
| RNF06 Integridade | #13, #14, #18, #19 | `DB::transaction` e locks nas operações com várias tabelas; sem pedido ou reserva órfãos | `CheckoutReservationTest` (D04, D12), `ReviewTest`, `SaleDeliveryTest`, `ListingStorageTest`, `ChatTest` | #16: recusa e expiração liberam reservas | Parcial (pendência P1) |
| RNF07 Privacidade | #19, #28, #30, #31 | Sem e-mail em páginas públicas e eventos do chat; admin sem acesso ao conteúdo das conversas | `ReviewTest`, `ChatTest`, `ChatRegressionTest`, `AdminTest`, `DiagnosticSecurityTest` | #32: evento do Reverb só com ids | Implementado |
| RNF08 Manutenibilidade | #1, #24 | Camadas com Services e Form Requests, PSR-12 com Pint, CI em PHP 8.2 e 8.3 | Workflow `.github/workflows/php-tests.yml`, `vendor/bin/pint --test` | #24 e #21: suíte completa aprovada | Implementado |
| RNF09 Observabilidade | #14, #23, #28 | Falhas de webhook e pagamento registradas com contexto, sem segredo; ações de moderação auditadas | `WebhookPaymentTest`, `ReportModerationTest`, `DiagnosticSecurityTest` | #16: `payment_events` com resultado e `processed_at` | Implementado (sem teste do conteúdo dos logs nem identificador de requisição) |

## Pendências conhecidas

| ID | Pendência | Requisitos | Onde |
| :--- | :--- | :--- | :--- |
| P1 | Duas compras concorrentes do mesmo cosmético único em **PostgreSQL**, com duas conexões. O SQLite ignora `lockForUpdate`, então D04 só é testado em sequência. | RF10, RNF06 | #16 |
| P2 | Webhook fora de ordem com eventos reais do provedor. Hoje só os testes automáticos (D09, D13) cobrem esse caso. | RF12 | #16 |
| P3 | Repetir o fluxo de pagamento no deploy do Render, com variáveis da AbacatePay e webhook no domínio público. O deploy está respondendo **HTTP 500**, causa ainda não investigada. | RF11, RF12 | #16 |
| P4 | Reverb como segundo serviço no Render. A validação com Reverb real foi feita só localmente. | RF22 | #32, [CHAT_TEMPO_REAL.md](CHAT_TEMPO_REAL.md#5-produção-render) |
| P5 | Revisão geral de usabilidade em desktop e celular, roteiro de apresentação executado com evidência e PDF acadêmico revisado com a equipe e o professor. | RNF01, RNF08 | #25, #33 |
| P6 | Figuras do documento acadêmico (casos de uso, arquitetura e MER) precisam ser reexportadas com o escopo atual. | Todos | #33 |

## Lacunas encontradas nesta revisão

- **L1. Desempenho sem medição.** Existem paginação e índices, mas não há protocolo nem resultado de medição para a meta de 500 ms (RF07, RNF05).
- **L2. Sem evidência manual registrada** para denúncia, moderação, restauração de anúncio e gestão de categorias (RF16, RF18, RF19, RF20). Há testes automatizados; a demonstração está no [roteiro](ROTEIRO_APRESENTACAO.md).
- **L3. Filtro por avaliação** (RF07) só tem evidência automatizada. A demonstração está no roteiro.
- **L4. Moderação sem denúncia.** O bloqueio e a restauração saem da fila de denúncias. Fora dela, o administrador só consegue pausar ou arquivar anúncio de terceiros pelas telas do vendedor; não existe tela administrativa de anúncios (RF19).
- **L5. Estado `em_andamento`.** O valor existe em `orders.status` e entra nas métricas do painel, mas nenhuma transição o usa hoje. O pedido vai de `pago` para `concluido`.
- **L6. Preço zero no seeder auxiliar.** `FreeListingsSeeder` (rodado à parte) cria anúncios `[TESTE]` com R$ 0,00, abaixo do mínimo de R$ 1,00 da regra RN02. Com R$ 0,00 o PIX não é gerado (#16). Na apresentação, use o catálogo do `DemoCatalogSeeder`, chamado pelo `DatabaseSeeder`, que tem preços acima de zero.
- **L7. E-mail real.** O Resend sem domínio verificado entrega apenas para o e-mail da própria conta Resend (#25).
