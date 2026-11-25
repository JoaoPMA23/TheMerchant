# 📐 Especificações Técnicas e Arquiteturais — TheMerchant

> **Documento de Arquitetura de Software e Especificação Técnica**  
> **Projeto:** TheMerchant (Marketplace de Cosméticos e Serviços para Jogos Digitais)  
> **Stack:** PHP 8.2+, Laravel 12, Blade, Tailwind CSS, Alpine.js, PostgreSQL (Supabase), Laravel Reverb + Echo  
> **Situação:** implementado x pendente por requisito em [docs/RASTREABILIDADE.md](docs/RASTREABILIDADE.md)  

---

## 1. Visão Arquitetural

O **TheMerchant** segue o padrão de **Monólito Modular em Camadas**, tirando proveito da robustez do ecossistema Laravel. Esta abordagem mantém o front-end (Blade + Tailwind CSS + Alpine.js) e o back-end no mesmo repositório, simplificando o deploy, testes e manutenção, enquanto isola as regras de negócio em uma **Camada de Serviços (*Service Layer*)**, permitindo que futuras interfaces (como um App Mobile via API REST ou SPA em Inertia/Vue) reutilizem a mesma inteligência de domínio sem duplicação de código.

```
[ Navegador Web ]
       │  (HTTPS / CSRF Token / Cookie de Sessão)
       ▼
[ Camada de Roteamento (routes/web.php) ]
       │
[ Middlewares (Authenticate, CheckRole, VerifyCsrfToken) ]
       │
[ Controllers (Http/Controllers) ] ──> [ Form Requests (Validação) ]
       │
       ▼
[ Camada de Serviços (app/Services) ] ──> [ Policies (app/Policies) ]
   ├── CheckoutService / ReservationService
   ├── PaymentGatewayService (+ Payments/AbacatePayGateway)
   ├── SaleDeliveryService / ReviewService
   ├── ChatService
   └── ReportModerationService / SellerAccountService / CatalogService
       │
       ├──────────────────────────┬──────────────────────────────┬──────────────────────────┐
       ▼                          ▼                              ▼                          ▼
[ Eloquent Models & DB ]  [ Fila + worker ]              [ Gateway Externo ]         [ Scheduler ]
(PostgreSQL / Supabase)   (webhooks, estornos,           (AbacatePay PIX, API v2,    (payments:reconcile
                           e-mails, MessageSent)          sandbox na apresentação)    a cada minuto)
                                  │
                                  ▼
                          [ Laravel Reverb ] ──canal privado chat.{id}, só ids──> [ Navegador (Echo) ]
```

Não há carteira própria nem custódia de valores: todo pagamento e estorno passa pelo gateway externo. O texto das mensagens do chat sempre chega pelo HTTP autorizado; o WebSocket leva apenas o aviso.

---

## 2. Dicionário de Dados e Modelo Físico

### 2.1 Tabela `users`
Armazena credenciais e dados de perfil de todos os usuários da plataforma.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `name` | `VARCHAR(120)` | Não | Nome completo de exibição |
| `email` | `VARCHAR(150)` | Não | E-mail único para autenticação |
| `email_verified_at` | `TIMESTAMP` | Sim | Data da confirmação de e-mail |
| `password` | `VARCHAR(255)` | Não | Hash seguro da senha |
| `is_admin` | `BOOLEAN` | Não | Administração independente da habilitação de vendas (default: `false`) |
| `status` | `ENUM('active', 'suspended')` | Não | Estado da conta (default: `active`) |
| `remember_token` | `VARCHAR(100)` | Sim | Token para recurso "Lembrar-me" |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.2 Tabela `seller_profiles`
Metadados de venda da mesma conta usada para comprar. Solicitações começam em `pending`; apenas um administrador pode aprovar ou suspender vendas. A suspensão comercial não suspende compras nem a entrega de pedidos existentes.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `user_id` | `BIGINT UNSIGNED` | Não | FK para `users.id` (Unique, 1:1) |
| `status` | `ENUM('pending', 'approved', 'suspended')` | Não | Aprovação de vendas, default `pending` |
| `bio` | `TEXT` | Sim | Descrição comercial e apresentação |
| `reputation_score` | `DECIMAL(3,2)` | Não | Média agregada de avaliações (0.00 a 5.00) |
| `total_reviews` | `INT UNSIGNED` | Não | Total de avaliações recebidas (default: 0) |
| `total_sales` | `INT UNSIGNED` | Não | Total de itens vendidos com sucesso (default: 0) |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.3 Tabela `games`
Catálogo oficial dos jogos suportados pela plataforma.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `name` | `VARCHAR(100)` | Não | Nome do jogo (ex: Counter-Strike 2, Valorant) |
| `slug` | `VARCHAR(120)` | Não | Slug único para URLs amigáveis |
| `cover_image` | `VARCHAR(255)` | Sim | Caminho no storage da imagem de capa |
| `active` | `BOOLEAN` | Não | Se o jogo aceita novos anúncios (default: `true`) |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.4 Tabela `categories`
Categorização hierárquica vinculada ou não a jogos específicos.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `game_id` | `BIGINT UNSIGNED` | Sim | FK opcional para `games.id` |
| `name` | `VARCHAR(100)` | Não | Nome da categoria (ex: Facas, Skins de Armas, Coaching) |
| `slug` | `VARCHAR(120)` | Não | Slug único para busca |
| `type` | `ENUM('cosmetic', 'service')` | Não | Natureza do item comercializado |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.5 Tabela `listings`
Armazena as publicações de cosméticos e serviços feitas pelos vendedores.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `seller_id` | `BIGINT UNSIGNED` | Não | FK para `users.id` (o vendedor anunciante) |
| `game_id` | `BIGINT UNSIGNED` | Não | FK para `games.id` |
| `category_id` | `BIGINT UNSIGNED` | Não | FK para `categories.id` |
| `title` | `VARCHAR(150)` | Não | Título atrativo do anúncio |
| `slug` | `VARCHAR(180)` | Não | Slug único com hash para URL do produto |
| `description` | `TEXT` | Não | Detalhes, especificações e condições de entrega |
| `price` | `DECIMAL(10,2)` | Não | Valor em reais (mínimo R$ 1,00) |
| `status` | `ENUM('rascunho', 'publicado', 'pausado', 'vendido', 'bloqueado')` | Não | Estado atual do anúncio |
| `views_count` | `INT UNSIGNED` | Não | Contador de visualizações (default: 0) |
| `capacity` | `INT UNSIGNED` | Não | Saldo ainda não consumido: 1 para cosmético; sessões disponíveis para coaching |
| `session_duration_minutes` | `SMALLINT UNSIGNED` | Sim | Duração de cada sessão de coaching |
| `delivery_conditions` | `TEXT` | Sim | Condições de entrega exibidas ao comprador |
| `timezone` | `VARCHAR(64)` | Sim | Fuso do coaching (ex.: `America/Sao_Paulo`) |
| `archived_at` | `TIMESTAMP` | Sim | Arquivamento que tira o anúncio da vitrine sem apagar histórico |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.6 Tabela `listing_images`
Galeria de imagens anexadas ao anúncio.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `listing_id` | `BIGINT UNSIGNED` | Não | FK para `listings.id` com `cascadeOnDelete` |
| `image_path` | `VARCHAR(255)` | Não | Caminho relativo no storage público |
| `is_primary` | `BOOLEAN` | Não | Se é a imagem de capa (default: `false`) |
| `display_order` | `SMALLINT UNSIGNED`| Não | Ordem de exibição no carrossel |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.7 Tabela `carts` e `cart_items`
Controle do carrinho persistido por usuário autenticado.

```sql
-- carts
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL UNIQUE (FK users.id),
created_at, updated_at TIMESTAMP

-- cart_items
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
cart_id BIGINT UNSIGNED NOT NULL (FK carts.id, cascade),
listing_id BIGINT UNSIGNED NOT NULL (FK listings.id),
quantity INT UNSIGNED NOT NULL DEFAULT 1,
unit_price DECIMAL(10,2) NOT NULL,
created_at, updated_at TIMESTAMP
```

### 2.8 Tabela `orders` e `order_items`
Registro de pedidos com preços e condições comerciais preservados; estados de pagamento/entrega podem evoluir.

```sql
-- orders
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_number VARCHAR(32) NOT NULL UNIQUE,
checkout_key VARCHAR(64) NULL, -- UNIQUE(buyer_id, checkout_key): reenvio devolve o mesmo pedido
buyer_id BIGINT UNSIGNED NOT NULL (FK users.id),
total_amount DECIMAL(10,2) NOT NULL,
currency CHAR(3) NOT NULL DEFAULT 'BRL',
reservation_expires_at TIMESTAMP NULL,
status ENUM('pendente', 'pago', 'em_andamento', 'concluido', 'cancelado') NOT NULL DEFAULT 'pendente',
notes TEXT NULL,
created_at, updated_at TIMESTAMP

-- order_items
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
order_id BIGINT UNSIGNED NOT NULL (FK orders.id, cascade),
listing_id BIGINT UNSIGNED NOT NULL (FK listings.id),
seller_id BIGINT UNSIGNED NOT NULL (FK users.id),
unit_price DECIMAL(10,2) NOT NULL, -- Preço congelado no ato da compra
quantity INT UNSIGNED NOT NULL DEFAULT 1,
-- snapshot: listing_title, listing_description, listing_type, game_id/game_name,
-- category_id/category_name, currency, session_duration_minutes, delivery_instructions,
-- delivery_conditions, scheduling_mode, timezone, scheduled_at
delivery_status ENUM('aguardando_pagamento', 'em_entrega', 'entregue') NOT NULL DEFAULT 'aguardando_pagamento',
delivered_at TIMESTAMP NULL,
created_at, updated_at TIMESTAMP
```

### 2.8.1 Disponibilidade e reservas (contrato #29, implementado em #5, #13 e #14)

Contrato normativo e exemplos D01 a D14: [Contrato de domínio v1](docs/CONTRATO_DOMINIO.md). Os exemplos são cobertos por `CheckoutReservationTest`, `OrderTest` e `ReviewTest`. `capacity` e `session_duration_minutes` estão em `listings`; cosmético único possui capacidade 1. O snapshot de tipo, duração e instruções fica em cada item do pedido. Serviço admite múltiplas compras até consumir sua capacidade.

Tabela `reservations` (implementada em #13 e #14): `id`, `listing_id`, `order_item_id` único, `quantity`, `expires_at`, `status` (active/confirmed/released), `released_reason`, timestamps. Reservas ativas reduzem disponibilidade e são adquiridas sob lock; aprovação reduz capacidade e confirma reserva uma única vez. Expiração libera apenas reserva ainda ativa. Suspensão/pausa não pode apagar reservas ou histórico. A disputa real entre duas conexões depende do `lockForUpdate` no PostgreSQL e ainda não tem evidência (pendência da #16).

Tabela `order_item_deliveries` (#18): `order_item_id`, `delivery_number` (UNIQUE por item), `recorded_by`, `delivered_at`. Cada sessão de coaching gera um registro; o item só fica `entregue` quando todas as sessões contratadas forem registradas.

### 2.9 Tabela `payments`
Registro de auditoria e status retornado pelo gateway de pagamento.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `order_id` | `BIGINT UNSIGNED` | Não | FK para `orders.id` |
| `gateway` | `VARCHAR(50)` | Não | Nome do provedor (`abacatepay`) |
| `transaction_id` | `VARCHAR(100)` | Não | ID externo da transação no provedor |
| `method` | `VARCHAR(16)` | Sim | Meio de pagamento (`PIX`) |
| `status` | `VARCHAR(50)` | Não | Status normalizado: `pending`, `paid`, `expired`, `cancelled`, `failed`, `refunded`, `disputed` |
| `amount` | `DECIMAL(10,2)` | Não | Valor processado |
| `idempotency_key` | `VARCHAR(128)` | Não | Idempotência da criação da cobrança (`abacatepay:<nº do pedido>`) |
| `payload` | `JSON` | Sim | Campos operacionais da cobrança, sem dados do pagador |
| `br_code` / `br_code_base64` | `TEXT` | Sim | PIX copia e cola e QR Code |
| `expires_at` / `paid_at` | `TIMESTAMP` | Sim | Vencimento do PIX e confirmação verificada |
| `refund_status` / `refund_id` / `refund_attempts` / `last_error` | | Sim | Estorno de pagamento tardio |

| `dev_mode` | `BOOLEAN` | Não | Cobrança criada com chave de desenvolvimento (sandbox) |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

Eventos de webhook ficam em `payment_events` (`gateway`, `event_id` único, `event_type`, `charge_id`, payload sanitizado, `result`, `processed_at`). Estados e transições: [docs/PAGAMENTOS.md](docs/PAGAMENTOS.md).

### 2.10 Tabela `reviews`
Avaliações concedidas exclusivamente após pedido entregue.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `order_id` | `BIGINT UNSIGNED` | Não | FK para `orders.id`; admite avaliações de itens diferentes do pedido |
| `order_item_id` | `BIGINT UNSIGNED` | Não | FK para `order_items.id`, UNIQUE (uma avaliação por item) |
| `buyer_id` | `BIGINT UNSIGNED` | Não | FK para `users.id` (autor da avaliação) |
| `seller_id` | `BIGINT UNSIGNED` | Não | FK para `users.id` (vendedor avaliado) |
| `rating` | `TINYINT UNSIGNED` | Não | Nota de 1 a 5 estrelas |
| `comment` | `TEXT` | Sim | Comentário sobre o atendimento e agilidade |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.11 Tabela `reports`
Canal de denúncias para supervisão e moderação administrativa.

| Campo | Tipo | Nulo | Descrição |
| :--- | :--- | :---: | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Não | Chave primária |
| `reporter_id` | `BIGINT UNSIGNED` | Não | FK para `users.id` (denunciante) |
| `listing_id` | `BIGINT UNSIGNED` | Sim | FK opcional para `listings.id` |
| `reason` | `VARCHAR(100)` | Não | Motivo (ex: Golpe, Item Inexistente, Violação de Termos) |
| `details` | `TEXT` | Não | Evidências e texto explicativo |
| `status` | `ENUM('aberta', 'em_analise', 'procedente', 'improcedente')` | Não | Estado da denúncia (default: `aberta`) |
| `moderator_id` | `BIGINT UNSIGNED` | Sim | FK para `users.id` (admin que julgou) |
| `resolution_notes` | `TEXT` | Sim | Parecer da moderação |
| `created_at` / `updated_at` | `TIMESTAMP` | Sim | Timestamps do Eloquent |

### 2.12 Tabela `moderation_audits`
Histórico das decisões administrativas (#23, #24): `report_id`, `moderator_id` e `listing_id` opcionais, `action`, estados anterior e novo da denúncia e do anúncio e `resolution_notes` obrigatório. Bloqueio e restauração de anúncio sempre geram um registro.

### 2.13 Tabelas `conversations` e `messages`
Descritas na seção 6. Tabelas técnicas do Laravel (`sessions`, `cache`, `jobs`, `failed_jobs`, `password_reset_tokens`, `migrations`) apoiam a operação e não fazem parte do modelo de negócio.

---

## 3. Camada de Serviços (*Service Layer*)

Para cumprir o requisito não funcional **RNF08** de organização em camadas e testabilidade, a lógica pesada não reside em Controllers, mas em classes de serviço:

### 3.1 `CheckoutService`
Responsável pelo fluxo transacional de criação do pedido a partir do carrinho:
Entrada: `checkout(User $user, string $checkoutKey, ?string $notes, array $itemInstructions): Order`.
1. Validar preço, elegibilidade e quantidade (cosmético único = 1; serviço = sessões/capacidade). Preço alterado atualiza o carrinho e pede revisão.
2. Em `DB::transaction()`, bloquear anúncios por id crescente (`lockForUpdate`), liberar reservas vencidas, reservar por 15 minutos e criar pedido `pendente`.
3. Congelar preço, quantidade, tipo, duração e condições em `order_items`; limpar carrinho.
4. Após o commit, `PaymentGatewayService::startPayment` cria a cobrança PIX com `externalId` idempotente, fora da transação. Falha transitória mantém a reserva e permite "Gerar PIX novamente" (`retryPayment`).
5. Aprovação confirmada pelo provedor consome reserva: cosmético fica `vendido`; serviço mantém anúncio disponível se houver capacidade.
6. Recusa, cancelamento e expiração liberam a reserva de forma idempotente (`ReservationService`); pagamento tardio é estornado no provedor sem dupla venda.
7. `payments:reconcile`, agendado a cada minuto, expira pedidos vencidos, consulta cobranças pendentes e repete estornos que falharam.

### 3.2 `PaymentGatewayService`
Encapsula o provedor por meio da interface `App\Services\Payments\PaymentGateway` (implementação `AbacatePayGateway`):
1. `startPayment(Order $order): Payment`: cria a cobrança PIX com `idempotency_key = abacatepay:<nº do pedido>`.
2. `handleEvent(PaymentEvent $event): string`: processa um webhook já autenticado (segredo + HMAC no `WebhookController`), sempre consultando o provedor.
3. `syncPayment(Payment $payment): string`: consulta o provedor (botão "Já paguei" e reconciliação).
4. `simulatePayment(Payment $payment): string`: pede ao provedor a simulação do pagamento, só com `ABACATEPAY_DEV_MODE=true`.
5. `applyCharge(Payment $payment, GatewayCharge $charge): string`: aplica o estado sob lock, sem regredir pagamento confirmado.
6. `refundLatePayment(Payment $payment): void`: solicita estorno idempotente de pagamento tardio.

---

## 4. Fluxo do Webhook e Idempotência

```mermaid
sequenceDiagram
    autonumber
    actor Buyer as Comprador
    participant App as TheMerchant App
    participant Gateway as Gateway de Pagamento
    participant WebhookCtrl as WebhookController
    participant Queue as Laravel Queue / Job
    participant DB as Banco de Dados

    Buyer->>App: Finaliza Checkout
    App->>Gateway: Cria cobrança PIX (fora da transação)
    App-->>Buyer: Página do pedido com QR Code e PIX copia e cola
    Buyer->>Gateway: Realiza o pagamento
    Gateway->>WebhookCtrl: POST /api/webhooks/payment?webhookSecret=… + X-Webhook-Signature
    WebhookCtrl->>WebhookCtrl: Valida segredo e HMAC do corpo bruto
    WebhookCtrl->>DB: Grava evento em payment_events (event_id único)
    WebhookCtrl->>Queue: Despacha ProcessPaymentWebhookJob
    WebhookCtrl-->>Gateway: HTTP 200 OK imediato
    Queue->>Gateway: GET /transparents/check (estado autoritativo)

    Queue->>DB: Deduplica evento e bloqueia transação/pedido
    alt Mesmo evento já processado
        Queue-->>Queue: Encerra sem reprocessar (Idempotência garantida)
    else Novo evento validado no provedor
        Queue->>DB: Atualiza payment sem regredir estado
        alt Aprovado e reserva válida
            Queue->>DB: Consome reserva, pedido pago e itens em_entrega
            Queue->>App: Após commit, notifica comprador e vendedores
        else Recusado, cancelado ou reserva expirada
            Queue->>DB: Libera reserva ou registra reconciliação de pagamento tardio
        end
    end
```

---

## 5. Segurança, Validação e Políticas (RBAC)

1. **Proteção CSRF:** Habilitada por padrão em todas as rotas web (`VerifyCsrfToken`). A rota `/api/webhooks/payment` é isenta de CSRF, mas protegida por **verificação de assinatura HMAC** no cabeçalho.
2. **Hashing de Senhas:** Implementado via `Hash::make()` utilizando o driver padrão `bcrypt` com salt gerado automaticamente.
3. **Policies do Laravel:**
   - `ListingPolicy`: Impede que um vendedor modifique ou exclua anúncios pertencentes a outro usuário. Permite apenas ao dono ou a um `admin` a exclusão.
   - `OrderPolicy`: Garante que um comprador visualize apenas seus próprios pedidos, e que um vendedor visualize apenas os itens de pedidos destinados a ele.
   - `ReviewPolicy`: Garante que apenas o comprador do item pago e entregue possa avaliá-lo, uma vez por `order_item_id`, sem depender de itens de outros vendedores.

## 6. Chat privado em tempo real (RF21 a RF23, implementado)

Revisão de escopo solicitada pelo professor responsável em 18/09/2026, issues #30 (backend), #31 (UI) e #32 (QA), todas fechadas. O PDF original excluía chat; a revisão inclui texto privado, histórico e não lidas na v1. Validado com Reverb e worker reais em ambiente local em 24/11/2026 (#32). O Reverb no Render ainda não foi validado. Operação e variáveis em [docs/CHAT_TEMPO_REAL.md](docs/CHAT_TEMPO_REAL.md).

### Persistência e autorização

A estrutura das tabelas está versionada em `2026_09_24_000001_create_chat_tables`. Para bancos que misturam o protótipo em português e o modelo Laravel, consulte [a consolidação do schema](docs/CONSOLIDACAO_SCHEMA.md).

- `conversations`: id, buyer_id (usuário interessado), seller_id, listing_id, buyer_last_read_message_id e seller_last_read_message_id opcionais, timestamps. UNIQUE(buyer_id, seller_id, listing_id). Impedir participantes iguais.
- `messages`: id, conversation_id, sender_id, client_uuid, body (texto de 1–2000 caracteres), timestamps. UNIQUE(conversation_id, sender_id, client_uuid); índice (conversation_id, id).
- Remetente sempre derivado da sessão. Cursor de leitura deve pertencer à conversa e só avançar após exibição.
- `ConversationPolicy` protege leitura, envio e canal; somente participantes ativos. Administrador não obtém acesso ao conteúdo pelo papel. Histórico não expõe e-mail, credenciais ou dados de pagamento.
- Anúncio publicado permite iniciar conversa; compra existente permite retomada após venda. Não excluir conversas ao arquivar anúncio. Sem anexos, grupos ou áudio/vídeo.
- `ChatService` e Form Requests concentram criação, envio idempotente, leitura paginada e atualização de cursor, com transação quando alterar múltiplas tabelas.

### Transporte e interface

Laravel Broadcasting + Reverb no servidor e Laravel Echo 2.1.6 com pusher-js 8.4.0 no navegador. [Documentação oficial de Broadcasting](https://laravel.com/docs/12.x/broadcasting) e [Reverb](https://laravel.com/docs/12.x/reverb).

Persistir mensagem e transmitir evento enfileirado após commit em canal privado autorizado. UI Blade/Tailwind/Alpine envia por HTTP autenticado com CSRF, recebe via Echo e reconcilia mensagens por ID/UUID. Reconexão consulta histórico incremental, recuperando mensagens perdidas. Limitação de envios, saída escapada, estados de erro/retry e contador de não lidas são obrigatórios.

### Operação e validação

Servidor WebSocket e worker de fila devem ter inicialização/reinício documentados; configurar origens permitidas e TLS em produção, sem versionar segredos. Testar acesso HTTP e assinatura de canais com terceiros/contas suspensas, eventos após commit, reconexão, paginação e retries concorrentes. E2E em duas sessões usa Reverb e worker reais.

```mermaid
sequenceDiagram
    participant C as Comprador/interessado
    participant A as Laravel + ChatService
    participant D as Banco
    participant Q as Fila / Reverb
    participant V as Vendedor
    C->>A: Enviar texto + client_uuid (HTTP autenticado)
    A->>A: Policy, validação e limitação
    A->>D: Persistir mensagem em transação
    D-->>A: Commit
    A->>Q: Evento após commit
    Q-->>V: Mensagem no canal privado autorizado
    V->>A: Consultar histórico / marcar leitura
```

## 7. Rastreabilidade e pendências do documento acadêmico

A matriz completa RF/RNF → issue → critério → evidência está em [docs/RASTREABILIDADE.md](docs/RASTREABILIDADE.md).

- #27 entregou RF03 (envio real pelo Resend registrado na #25); #28 removeu os diagnósticos HTTP.
- #7/#8 entregaram filtro por nota mínima e ordenação por reputação; #23/#24 entregaram a restauração de anúncio bloqueado pelo administrador.
- #29 definiu cosmético único, sessões/capacidade e conclusão por item; #13, #14 e #18 implementaram. Avaliação única por `order_item_id` entregue/pago via ReviewService e Policy, sem aguardar outros vendedores.
- #16 homologou a AbacatePay em sandbox em 12/11/2026. Pendentes: compra concorrente em PostgreSQL, webhook fora de ordem com eventos reais e repetição no Render.
- #33 atualiza o documento acadêmico e os diagramas: associações visíveis, catálogo público para visitante, chat para comprador e vendedor, arquitetura com Reverb, Echo e filas, e MER com conversas, mensagens, cursores de leitura e reservas. O texto do `.docx` em `docs/entrega/` foi atualizado; as figuras ainda precisam ser reexportadas.
- #25 depende das evidências de todas as milestones e do [roteiro de apresentação](docs/ROTEIRO_APRESENTACAO.md). O PDF original permanece referência histórica.
