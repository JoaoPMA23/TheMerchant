# 📋 Requisitos e Regras de Negócio — TheMerchant

> **Documento de Engenharia de Requisitos**  
> **Disciplina:** Laboratório de Engenharia de Software III (LES III 2026) — FATEC PG  
> **Projeto:** TheMerchant (Marketplace de Cosméticos e Serviços para Jogos Digitais)  
> **Integrantes:** Igor Marcoli Bastos e João Pedro Martins de Andrade  

---

## 1. Visão Geral do Produto

O **TheMerchant** é uma plataforma web intermediadora para a compra e venda de itens cosméticos (skins, pacotes visuais, temas, avatares) e serviços digitais (como coaching de partidas) associados a jogos eletrônicos. Seu foco principal é sanar os problemas de informalidade enfrentados pela comunidade gamer em redes sociais e fóruns, fornecendo um ambiente estruturado com:
- Autenticação e perfis diferenciados (*Comprador*, *Vendedor*, *Administrador*);
- Anúncios categorizados e vinculados a jogos específicos;
- Carrinho de compras unificado e checkout transparente integrado a gateway externo;
- Atualização assíncrona de pagamentos via webhooks com proteção contra duplicidade;
- Reputação e avaliação condicionadas à compra efetivamente concluída;
- Painel de moderação para tratamento de denúncias e suspensão de itens irregulares;
- Chat privado de texto entre interessado e vendedor, incluído na v1 em 18/09/2026 (RF21 a RF23).

> **Implementado x planejado:** este documento define o que o sistema deve fazer. A situação de cada requisito, com issue, critério de aceite e evidência, está na [matriz de rastreabilidade](docs/RASTREABILIDADE.md). Um requisito só conta como entregue quando há evidência registrada.

---

## 2. Limites e Premissas de Escopo

### 2.1 Em Escopo (Primeira Versão)
1. Interface Web responsiva otimizada para desktops, tablets e smartphones.
2. Catálogo público com busca textual e filtros combinados (jogo, categoria, tipo, faixa de preço, nota mínima do vendedor e ordenação por preço, data ou reputação).
3. Gestão completa de anúncios por vendedores credenciados (criação, edição, upload de fotos, pausa e arquivamento).
4. Carrinho de compras persistido por conta.
5. Checkout com reserva de disponibilidade e delegação do pagamento a gateway externo (decisão registrada em #14: AbacatePay, PIX, API v2). A apresentação usa o modo de desenvolvimento (sandbox) do provedor, sem dinheiro real.
6. Confirmação assíncrona de pagamento via webhook e garantia de idempotência.
7. Rastreamento e acompanhamento de histórico de pedidos para o comprador e vendas para o vendedor.
8. Módulo de avaliação do vendedor (nota de 1 a 5 e comentário textual), por item pago e entregue.
9. Mecanismo de denúncia de anúncios suspeitos e painel administrativo para moderação.
10. Painel administrativo para gerenciamento de usuários, categorias e jogos.
11. Coaching vendido em sessões inteiras, com capacidade e duração definidas pelo vendedor e horário combinado pelo chat.
12. Chat privado de texto em tempo real (Laravel Reverb + Echo), com histórico, mensagens não lidas e recuperação após reconexão. Revisão de escopo solicitada pelo professor responsável em 18/09/2026.

### 2.2 Fora do Escopo (Primeira Versão)
1. Aplicativo mobile nativo para iOS ou Android.
2. Integração direta via API interna com distribuidoras de jogos (Steam Web API, Riot Games API, Blizzard API, etc.).
3. Anexos, áudio, vídeo, grupos e indicador de digitação no chat.
4. Múltiplos idiomas (i18n) e suporte a moedas estrangeiras (todas as operações em Real Brasileiro - BRL).
5. Carteira digital própria (*digital wallet*) ou custódia interna de saldo financeiro (todos os valores transitam pelo gateway externo).
6. Agenda automática de coaching, seleção de horários e sincronização de calendário.
7. Venda de contas de jogos.

---

## 3. Matriz de Requisitos Funcionais (RF)

| ID | Prioridade | Nome do Requisito | Descrição Detalhada | Critério de Aceitação |
| :--- | :---: | :--- | :--- | :--- |
| **RF01** | **Alta** | Cadastro de Conta por Visitante | O sistema deve permitir que um visitante cadastre uma conta informando, no mínimo, nome, e-mail e senha, com uma conta única apta a comprar e solicitar aprovação para vender. | E-mail validado quanto ao formato e unicidade; senha com no mínimo 8 caracteres; conta sem privilégios administrativos; solicitação de venda em `seller_profiles.status=pending`. |
| **RF02** | **Alta** | Autenticação e Sessão | O sistema deve permitir autenticação, logout e controle de sessão de usuários cadastrados. | Sessão com cookie seguro (`HttpOnly`, `SameSite=Lax`); logout invalida e regenera o token de sessão. |
| **RF03** | **Alta** | Verificação de E-mail e Recuperação | O sistema deve permitir verificação de e-mail e recuperação de acesso por meio de fluxo seguro. | Link de confirmação assinado e token de recuperação de uso único, ambos com validade de 60 minutos, enviados pela fila; resposta igual para conta existente, inexistente ou suspensa. |
| **RF04** | **Alta** | Controle de Acesso e Permissões (RBAC) | Toda conta ativa pode comprar; vender exige perfil aprovado; administração depende de `users.is_admin`. | Bloqueio imediato via Middleware e Policies; contas sem perfil aprovado não criam anúncios; apenas administradores ativos acessam `/admin`. |
| **RF05** | **Alta** | Gestão de Anúncios pelo Vendedor | O vendedor deve poder cadastrar, editar, pausar e remover seus próprios anúncios. | Somente o criador do anúncio ou administrador pode alterá-lo/excluí-lo (assegurado por `ListingPolicy`). |
| **RF06** | **Alta** | Estrutura de Dados do Anúncio | Cada anúncio deve permitir informar jogo, categoria, título, descrição, preço, imagens e status. | Preço maior que zero (min: R$ 1,00); pelo menos 1 imagem principal obrigatória; status inicial `publicado` ou `rascunho`. |
| **RF07** | **Alta** | Pesquisa e Filtros de Catálogo | O usuário deve poder pesquisar, filtrar e ordenar anúncios por jogo, categoria, faixa de preço e reputação do vendedor. | Filtros combinados (incluindo nota mínima do vendedor e ordenação por reputação, com vendedores sem avaliações por último) preservados na query string com paginação; tempo de resposta < 500ms medido conforme RNF05. |
| **RF08** | **Alta** | Página de Detalhes do Anúncio | O sistema deve exibir uma página de detalhes do anúncio com informações do produto ou serviço, vendedor e reputação disponível. | Carrossel de fotos, especificações, nota de reputação média e botão de adicionar ao carrinho contextual. |
| **RF09** | **Alta** | Gestão do Carrinho de Compras | O comprador deve poder gerenciar o carrinho, adicionando, removendo e ajustando itens antes da compra. | Persistência do carrinho por usuário; bloqueio de compra de anúncios próprios ou de anúncios pausados. |
| **RF10** | **Alta** | Finalização de Compra (Checkout) | O comprador deve poder finalizar a compra por meio do checkout, gerando o pedido correspondente. | Pedido `pendente` com preço, tipo, quantidade e condições congelados em `order_items`; reserva de 15 minutos criada na mesma transação, sem pedido parcial; reenvio com a mesma chave devolve o mesmo pedido. |
| **RF11** | **Alta** | Processamento com Gateway Externo | A finalização da compra deve incluir o processamento do pagamento por meio de um gateway externo. | Cobrança PIX criada na AbacatePay (API v2) fora da transação de banco, com `externalId` idempotente; nenhum dado de cartão salvo ou trafegado. Validado em sandbox (`devMode`). |
| **RF12** | **Alta** | Confirmação por Webhooks e Idempotência | O sistema deve receber notificações/webhooks do gateway e atualizar de forma idempotente os status do pagamento e do pedido. | Webhook exige `webhookSecret` e assinatura HMAC válida; evento gravado uma vez por `event_id` e processado em job; estado conferido no provedor antes de confirmar; pagamento confirmado nunca regride. |
| **RF13** | **Alta** | Histórico e Acompanhamento de Pedidos | O comprador deve poder acompanhar o histórico e o status de seus pedidos. | Listagem cronológica com status do pedido (Pendente, Pago, Concluído, Cancelado) separado do status de entrega de cada item (Aguardando pagamento, Em entrega, Entregue). |
| **RF14** | **Média** | Acompanhamento de Vendas pelo Vendedor | O vendedor deve poder acompanhar as vendas relacionadas aos seus anúncios. | Painel com apenas os itens do próprio vendedor; entrega só com pedido pago e de forma idempotente; coaching registrado sessão por sessão; pedido concluído só quando todos os itens forem entregues. |
| **RF15** | **Média** | Avaliação do Vendedor Pós-Compra | O comprador deve poder avaliar o vendedor somente após a conclusão da compra. | Avaliação liberada por item pago e entregue; 1 avaliação por order_item_id, sem aguardar outros vendedores; recálculo dinâmico da reputação. |
| **RF16** | **Média** | Denúncia de Anúncios por Usuários | O usuário autenticado deve poder denunciar um anúncio, informando o motivo da denúncia. | Modal de envio rápido; registro em `reports` com status inicial `aberta`; restrito a usuários logados. |
| **RF17** | **Alta** | Gestão Administrativa de Usuários | O administrador deve poder gerenciar usuários, incluindo consulta, alteração de status e demais ações administrativas previstas pela plataforma. | Visualização de lista de usuários, filtros por papel e opção de suspensão de contas irregulares. |
| **RF18** | **Alta** | Gestão Administrativa de Categorias | O administrador deve poder gerenciar as categorias utilizadas para classificar anúncios. | Criação, edição e inativação de categorias vinculadas aos jogos suportados. |
| **RF19** | **Alta** | Moderação Administrativa de Anúncios | O administrador deve poder moderar anúncios, podendo ocultar, bloquear ou restaurar anúncios conforme as regras da plataforma. | Bloqueio de anúncios irregulares procedentes, removendo-os imediatamente do catálogo público; restauração pelo administrador com motivo, voltando como pausado (ou rascunho) até o vendedor reativar; ações registradas em `moderation_audits`. |
| **RF20** | **Alta** | Tratamento Administrativo de Denúncias | O administrador deve poder consultar e tratar denúncias registradas pelos usuários. | Fila de moderação com histórico, campo de parecer motivado e julgamento (procedente / improcedente). |
| **RF21** | **Alta** | Iniciar e Retomar Conversa | O interessado deve poder iniciar uma conversa privada com o vendedor a partir do anúncio, e comprador e vendedor devem poder retomá-la a partir do pedido ou da venda. | Conversa única por comprador, vendedor e anúncio; visitante vai para o login; não é possível conversar consigo mesmo; vendedor só inicia conversa com quem comprou. |
| **RF22** | **Alta** | Mensagens de Texto em Tempo Real | Os participantes devem poder enviar e receber mensagens de texto sem recarregar a página, com persistência e autorização. | Texto de 1 a 2000 caracteres gravado antes da transmissão; sinal em canal privado (Reverb) só para participantes ativos, sem o conteúdo; reenvio com o mesmo UUID não duplica; terceiros, contas suspensas e administrador pelo papel não leem a conversa. |
| **RF23** | **Média** | Histórico, Não Lidas e Reconexão | Os participantes devem poder consultar o histórico, ver as mensagens não lidas e recuperar mensagens perdidas após uma queda de conexão. | Histórico paginado (50 por página); cursor de leitura por participante que só avança; contador de não lidas na navegação; sincronização HTTP recupera mensagens após queda do Reverb ou do worker. |

---

## 4. Matriz de Requisitos Não Funcionais (RNF)

| ID | Categoria | Descrição Técnica | Métrica / Validação |
| :--- | :--- | :--- | :--- |
| **RNF01** | **Usabilidade & Responsividade** | A aplicação deve ser desenvolvida em padrão mobile-first com Tailwind CSS, adaptando-se a resoluções de 320px a 4K. | Testes em viewports mobile (iPhone, Android) e navegadores modernos (Chrome, Firefox, Safari, Edge). |
| **RNF02** | **Criptografia de Senhas** | Nenhuma credencial deve ser persistida em texto claro; uso de algoritmo de hashing irreversível padrão do Laravel (Bcrypt com custo >= 12 ou Argon2id). | Auditoria no banco demonstrando hashes com salt automático. |
| **RNF03** | **Segurança da Aplicação** | Todas as requisições de alteração de estado (POST/PUT/PATCH/DELETE) devem validar tokens CSRF; autorizações checadas por Laravel Policies, inclusive na autorização dos canais privados do chat. | Prevenção contra CSRF, SQL Injection (Eloquent preparado) e XSS (escape nativo do Blade `{{ }}` e `x-text` no Alpine); webhook fora do CSRF, protegido por segredo e HMAC. |
| **RNF04** | **Conformidade PCI-DSS** | O sistema não deve transitar nem salvar números de cartão de crédito, CVV ou datas de expiração em seu banco de dados. | Pagamento por PIX gerado no provedor (AbacatePay); payload do webhook gravado sem dados do pagador. |
| **RNF05** | **Desempenho e Paginação** | Consultas ao catálogo e painéis devem responder em até 500ms sob carga acadêmica típica, utilizando paginação e índices em chaves estrangeiras e status. | Índices compostos em `(game_id, category_id, status)` e `price`; medição com protocolo documentado (volume, filtros, concorrência, mediana e p95). |
| **RNF06** | **Integridade Transacional** | Fluxos de criação de pedido, reserva e baixa de estoque, registro de pagamento, entrega, avaliação e envio de mensagem devem executar sob transações atômicas de banco (`DB::transaction`), com locks onde há disputa. | Garantia de que falhas no meio do processo realizem Rollback sem registros órfãos; concorrência validada em PostgreSQL. |
| **RNF07** | **Privacidade de Dados** | Coleta restrita aos campos estritamente necessários para viabilizar a negociação; e-mails não aparecem em avaliações públicas nem no chat. | Nomes completos e contatos diretos não são expostos em áreas públicas; eventos do chat levam só ids; administrador não lê conversas pelo papel. |
| **RNF08** | **Manutenibilidade e Padrões** | Código modular seguindo MVC estrito, camada de Services para regras de negócio, injeção de dependência e convenção PSR-12 / Laravel Pint. | Testes automatizados com Pest/PHPUnit no CI (PHP 8.2 e 8.3) e testes JavaScript com `npm test`. |
| **RNF09** | **Observabilidade e Logs** | Registro de falhas críticas, retornos de webhook e erros 500 em log (`storage/logs/laravel.log` ou `stderr` no Render) sem vazar dados confidenciais. | Log com contexto (número do pedido, id do evento) e motivo da falha; eventos de pagamento em `payment_events` e ações de moderação em `moderation_audits`. |

---

## 5. Regras de Negócio Críticas (RN)

1. **RN01 — Vinculação Obrigatória:** Todo anúncio cadastrado deve obrigatoriamente estar associado a um jogo ativo e a uma categoria válida cadastrada pelo administrador.
2. **RN02 — Preço Mínimo:** Anúncios não podem ter valor igual ou inferior a R$ 0,00. O valor mínimo é R$ 1,00.
3. **RN03 — Máquina de Estados do Anúncio:**
   - Transições válidas: `rascunho` ➔ `publicado` ➔ (`pausado`, `vendido`, `bloqueado`).
   - Anúncio com status `bloqueado` só pode ser desbloqueado por um administrador.
4. **RN04 — Congelamento de Preço no Pedido:** O preço praticado no momento da finalização do pedido é imutável e gravado na tabela `order_items`. Alterações posteriores no anúncio não impactam compras já efetuadas.
5. **RN05 — Condição de Avaliação:** O comprador pode avaliar cada item pago e entregue uma única vez por `order_item_id`, sem aguardar a entrega dos outros itens/vendedores. Serviço exige todas as sessões do item concluídas. Ver [contrato de domínio #29](docs/CONTRATO_DOMINIO.md).
6. **RN06 — Idempotência do Webhook:** Cada notificação é gravada uma vez por `payment_events.event_id`. A criação da cobrança usa `payments.idempotency_key` (`abacatepay:<nº do pedido>`). Evento repetido retorna `200 OK` sem reprocessar saldo ou status; eventos diferentes do mesmo pagamento são conferidos no provedor e aplicados sob lock, sem regredir um pagamento confirmado.
7. **RN07 — Denúncias por Usuários Autenticados:** Somente usuários autenticados no sistema poderão registrar denúncias de anúncios.
8. **RN08 — Acionamento Obrigatório de Gateway:** Toda finalização de compra deverá obrigatoriamente acionar o processamento do pagamento por meio do gateway externo.
9. **RN09 (Reserva e Estoque):** Carrinho não reserva. O checkout reserva por 15 minutos; o pagamento confirmado consome a reserva (cosmético fica `vendido`; coaching só fica `vendido` quando a capacidade chega a zero). Expiração, recusa ou cancelamento liberam a reserva sem baixar estoque. Pagamento que chega depois da expiração não toma estoque e é estornado pelo provedor. Ver [contrato de domínio](docs/CONTRATO_DOMINIO.md) e [pagamentos](docs/PAGAMENTOS.md).
10. **RN10 (Conclusão por Item e Coaching por Sessões):** Cada item é entregue pelo próprio vendedor. Coaching é vendido em sessões inteiras e o item só fica `entregue` depois de todas as sessões registradas. O pedido fica `concluido` quando todos os itens forem entregues; a avaliação de um item não espera os outros.
11. **RN11 (Privacidade do Chat):** Só os dois participantes ativos leem e enviam mensagens. A suspensão da conta revoga o acesso também em conversas já abertas. O administrador não ganha acesso ao conteúdo pelo papel.

---

## 6. Critérios de Modelagem UML Aplicados

Conforme a Seção 6.1 da documentação oficial:
- **Associações entre atores e casos de uso:** Representadas por linhas contínuas, sem pontas de seta.
- **Generalização de Atores:** Comprador, Vendedor e Administrador especializam o ator geral `Usuário` por meio de generalização (`herança`), compartilhando funcionalidades comuns (Autenticar-se, Pesquisar e filtrar anúncios, Visualizar anúncio e Denunciar anúncio).
- **Visitante:** Representa o usuário não autenticado, associado a Cadastrar-se, Autenticar-se, Pesquisar e filtrar anúncios e Visualizar anúncio.
- **Chat:** Comprador e Vendedor se associam a `Conversar com vendedor: texto, histórico e não lidas` (RF21 a RF23). O Administrador não se associa a esse caso de uso.
- **Relacionamento `<<include>>`:** O único relacionamento obrigatório entre casos de uso é `Finalizar compra <<include>> Processar pagamento`, pois toda finalização de compra exige a execução do processamento de pagamento.
- **Independência de Fluxos:** `Gerenciar carrinho` e `Finalizar compra` não possuem relacionamento direto no diagrama, pois representam uma sequência de fluxo e não uma relação estrutural de caso de uso.
- **Ações Administrativas Atômicas:** `Gerenciar usuários`, `Gerenciar categorias`, `Moderar anúncios` e `Tratar denúncias` são casos de uso separados para representar ações de gestão independentes.
- **Gateway de Pagamento como Ator Externo:** Participa ativamente dos casos `Processar pagamento` e `Atualizar status de pagamento/pedido`.

---

## 7. Situação de Implementação e Rastreabilidade

A [matriz de rastreabilidade](docs/RASTREABILIDADE.md) liga cada RF01 a RF23 e RNF01 a RNF09 à issue, ao critério de aceite e à evidência (teste automatizado ou registro em comentário de issue). Resumo em 25/11/2026:

- **Implementados com evidência:** RF01 a RF11, RF13 a RF18, RF20 a RF23; RNF02, RNF03, RNF04, RNF07, RNF08 e RNF09.
- **Parciais:** RF12 (webhook fora de ordem com eventos reais e repetição no Render), RF19 (moderação só a partir da fila de denúncias), RNF01 (revisão geral de telas na #25), RNF05 (meta de 500 ms sem medição) e RNF06 (compra concorrente em PostgreSQL).
- **Pagamento:** AbacatePay em modo de desenvolvimento (sandbox), homologado localmente em 12/11/2026 (#16). Não é integração em produção com dinheiro real.
