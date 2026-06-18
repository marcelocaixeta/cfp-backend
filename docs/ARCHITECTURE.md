# Arquitetura do CFP Backend

## Objetivo

Construir um backend em Laravel moderno, com PostgreSQL e Docker, para controle financeiro pessoal e acompanhamento de Bitcoin por usuário nas seguintes áreas:

- Dashboard BTC
- Análises
- Finanças Pessoais
- Configurações
- Suporte

Cada usuário deve acessar somente os próprios dados. O sistema nasce como uma API REST versionada para servir aplicações web/mobile futuras, mantendo separação clara entre autenticação, domínio financeiro, análises de Bitcoin e suporte.

## Stack Recomendada

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 13.x |
| Linguagem | PHP 8.3+ |
| Banco de dados | PostgreSQL 16+ |
| Autenticação API | Laravel Sanctum |
| Filas | Laravel Queue com driver database inicialmente |
| Cache | Redis opcional em fase futura |
| Testes | PHPUnit/Pest |
| Qualidade | Laravel Pint, PHPStan/Larastan |
| Containerização | Docker + Docker Compose |

Laravel 13.x é a versão atual recomendada em 1 de junho de 2026. O projeto deve usar constraint `laravel/framework:^13.0` para receber correções compatíveis sem travar em uma versão exata.

## Visão Geral

```text
Cliente Web/Mobile
        |
        v
API Laravel 13
        |
        +-- Auth e Usuários
        +-- Dashboard BTC
        +-- Análises
        +-- Finanças Pessoais
        +-- Configurações
        +-- Suporte
        |
        v
PostgreSQL
```

Ambiente Docker:

```text
docker-compose.yml
        |
        +-- backend: Laravel + PHP-FPM/Artisan
        +-- postgres: PostgreSQL
```

Em produção, recomenda-se adicionar um proxy HTTP dedicado, como Nginx, Caddy ou serviço gerenciado da plataforma. Para o primeiro momento, a arquitetura solicitada mantém apenas os containers obrigatórios: backend Laravel e PostgreSQL.

## Princípios Arquiteturais

- API first: todas as funcionalidades expostas por endpoints REST em `/api/v1`.
- Multiusuário por propriedade de dados: tabelas de domínio financeiro sempre possuem `usuario_id`.
- Segurança por padrão: autenticação obrigatória, policies, rate limiting e validação em Form Requests.
- Domínio organizado por módulos: código agrupado por responsabilidade, sem concentrar regras de negócio nos controllers.
- Banco como fonte de verdade: PostgreSQL com migrations, constraints, índices e chaves estrangeiras.
- Testabilidade: regras críticas em Services/Actions com testes unitários e endpoints com testes de feature.
- Evolução incremental: começar simples com filas/cache via database e preparar pontos de extensão para Redis, workers e integrações externas.

## Estrutura de Pastas Recomendada

```text
app/
  Actions/
    Finance/
    Btc/
    Support/
  DTOs/
  Enums/
  Http/
    Controllers/
      Api/V1/
        Auth/
        Btc/
        Finance/
        Settings/
        Support/
    Middleware/
    Requests/
      Auth/
      Finance/
      Support/
    Resources/
      Btc/
      Finance/
      Support/
  Models/
  Policies/
  Services/
    Btc/
    Finance/
    Support/
  Support/
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
routes/
  api.php
tests/
  Feature/
  Unit/
```

### Padrão de Implementação

- Controllers recebem a requisição, chamam Actions/Services e retornam Resources.
- Form Requests validam entrada e autorização simples.
- Resources padronizam resposta JSON.
- Policies garantem que o usuário só manipule os próprios registros.
- Services concentram regras de cálculo, integração e agregação.
- Actions executam casos de uso específicos, por exemplo `CreateCreditCardDebtAction`.

## Módulos

### Endpoints Públicos e Operacionais

Responsabilidades:

- Verificar a disponibilidade básica da API.
- Registrar interesse de emails vindo de fluxos públicos.

Endpoints atuais:

```text
GET  /api/v1/health
POST /api/v1/email-signups
```

### 1. Autenticação e Usuários

Responsabilidades:

- Cadastro de usuário.
- Login e logout.
- Tokens de API via Laravel Sanctum.
- Recuperação de senha.
- Perfil básico do usuário.
- Isolamento de dados por usuário autenticado.
- Administração simples de perfis de usuários.

Entidades principais:

- `usuarios`
- `tokens_acesso_pessoal`
- `tokens_redefinicao_senha`

Endpoints iniciais:

```text
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/me
PATCH  /api/v1/me
GET    /api/v1/users
PATCH  /api/v1/users/{user}/profile
```

### 2. Dashboard Financeiro

Responsabilidades:

- Exibir visão consolidada de Bitcoin.
- Mostrar preço atual, variação, holdings cadastrados e indicadores resumidos.
- Cadastrar ativos financeiros pessoais classificados como BTC, Renda Fixa ou Renda Variável.
- Consolidar no dashboard BTC apenas os ativos classificados como BTC.

Entidades sugeridas:

- `ativos_btc`
- `capturas_precos_btc`
- `btc_watchlist_items`

Endpoints iniciais:

```text
GET    /api/v1/btc/dashboard
GET    /api/v1/btc/assets
POST   /api/v1/btc/assets
GET    /api/v1/btc/assets/{btcAsset}
PUT    /api/v1/btc/assets/{btcAsset}
PATCH  /api/v1/btc/assets/{btcAsset}
DELETE /api/v1/btc/assets/{btcAsset}
```

Observação: a integração com cotação externa deve ficar encapsulada em `BtcPriceProviderInterface`, permitindo trocar o provedor sem afetar controllers ou banco.

### 3. Análises

Responsabilidades:

- Gerar indicadores para BTC e finanças pessoais.
- Exibir evolução mensal de dívidas, parcelas, saldo previsto e exposição em BTC.
- Permitir análises por período.

Serviços sugeridos:

- `FinanceSummaryService`
- `DebtProjectionService`
- `BtcAnalyticsService`

Endpoints iniciais:

```text
GET /api/v1/analytics/overview
```

### 4. Finanças Pessoais

Responsabilidades:

- Cadastro de receitas mensais (salário, freelancer, etc.).
- Cadastro de dívidas no cartão de crédito.
- Cadastro de prestações de empréstimos.
- Controle por usuário.
- Acompanhamento de status, vencimentos, parcelas pagas e valores pendentes.

Entidades principais:

- `receitas_mensais`
- `cartoes_credito`
- `dividas_cartao_credito`
- `emprestimos`
- `parcelas_emprestimos`
- `categorias_financeiras`

Endpoints iniciais:

```text
GET    /api/v1/finance/summary
GET    /api/v1/finance/dashboard?mes=YYYY-MM
GET    /api/v1/finance/current-week-due-dates

GET    /api/v1/finance/income
POST   /api/v1/finance/income
GET    /api/v1/finance/income/{income}
PUT    /api/v1/finance/income/{income}
PATCH  /api/v1/finance/income/{income}
DELETE /api/v1/finance/income/{income}

GET    /api/v1/finance/credit-cards
POST   /api/v1/finance/credit-cards
GET    /api/v1/finance/credit-cards/{credit_card}
PUT    /api/v1/finance/credit-cards/{credit_card}
PATCH  /api/v1/finance/credit-cards/{credit_card}
DELETE /api/v1/finance/credit-cards/{credit_card}

GET    /api/v1/finance/credit-card-debts
POST   /api/v1/finance/credit-card-debts
GET    /api/v1/finance/credit-card-debts/{credit_card_debt}
PUT    /api/v1/finance/credit-card-debts/{credit_card_debt}
PATCH  /api/v1/finance/credit-card-debts/{credit_card_debt}
DELETE /api/v1/finance/credit-card-debts/{credit_card_debt}

GET    /api/v1/finance/loans
POST   /api/v1/finance/loans
GET    /api/v1/finance/loans/{loan}
PUT    /api/v1/finance/loans/{loan}
PATCH  /api/v1/finance/loans/{loan}
DELETE /api/v1/finance/loans/{loan}

GET    /api/v1/finance/loans/{loan}/installments
PATCH  /api/v1/finance/loan-installments/{loanInstallment}
PATCH  /api/v1/finance/loan-installments/{loanInstallment}/pay
```

Regras importantes:

- Toda dívida deve pertencer a um usuário.
- Todo cartão deve pertencer a um usuário.
- Todo empréstimo deve pertencer a um usuário.
- Parcelas de empréstimo devem ser geradas automaticamente ao criar um empréstimo parcelado.
- Valores monetários devem usar `decimal(14, 2)`, nunca `float`.
- Datas de vencimento devem usar `date`.
- Exclusões podem usar soft delete em registros financeiros para preservar histórico.

### 5. Configurações

Responsabilidades:

- Preferências do usuário.
- Moeda padrão.
- Timezone.
- Notificações.
- Preferências de exibição do dashboard.

Entidades sugeridas:

- `configuracoes_usuario`
- `notification_preferences`

Endpoints iniciais:

```text
GET   /api/v1/settings
PATCH /api/v1/settings
```

### 6. Suporte

Responsabilidades:

- Abertura de tickets.
- Histórico de mensagens por ticket.
- Status do atendimento.
- Classificação de prioridade.

Entidades principais:

- `chamados_suporte`
- `mensagens_chamados_suporte`

Endpoints iniciais:

```text
GET    /api/v1/support/tickets/all
GET    /api/v1/support/tickets
POST   /api/v1/support/tickets
GET    /api/v1/support/tickets/{supportTicket}
PATCH  /api/v1/support/tickets/{supportTicket}
DELETE /api/v1/support/tickets/{supportTicket}
PATCH  /api/v1/support/tickets/{supportTicket}/resolve
POST   /api/v1/support/tickets/{supportTicket}/messages
```

## Modelo de Dados Inicial

### usuarios

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| nome | varchar | Nome |
| email | varchar | Único |
| email_verificado_em | timestamp nullable | Verificação |
| senha | varchar | Hash |
| lembrar_token | varchar nullable | Laravel |
| criado_em / atualizado_em | timestamps | Controle |

### receitas_mensais

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId | FK usuarios |
| categoria_id | foreignId nullable | FK categorias_financeiras |
| descricao | varchar | Ex.: Salário, Freelance, Dividendos |
| valor | decimal(14,2) | Valor da receita |
| data_recebimento | date | Data do recebimento |
| recorrente | boolean | Padrão true |
| tipo_receita | varchar | salary, freelance, investment, other |
| observacoes | text nullable | Observações |
| criado_em / atualizado_em | timestamps | Controle |
| excluido_em | softDeletes | Histórico |

Índices:

- `index(usuario_id, data_recebimento)`
- `index(usuario_id, tipo_receita)`

### cartoes_credito

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId | FK usuarios |
| nome | varchar | Ex.: Nubank, Inter |
| ultimos_quatro_digitos | char(4) nullable | Final do cartão |
| bandeira | varchar nullable | Visa, Mastercard |
| limite_valor | decimal(14,2) nullable | Limite |
| dia_fechamento | smallint nullable | Dia de fechamento |
| dia_vencimento | smallint nullable | Dia de vencimento |
| ativo | boolean | Padrão true |
| criado_em / atualizado_em | timestamps | Controle |
| excluido_em | softDeletes | Histórico |

Índices:

- `index(usuario_id)`
- `unique(usuario_id, nome)` opcional

### dividas_cartao_credito

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId | FK usuarios |
| cartao_credito_id | foreignId nullable | FK cartoes_credito |
| categoria_id | foreignId nullable | FK categorias_financeiras |
| descricao | varchar | Descrição da compra/dívida |
| valor_total | decimal(14,2) | Valor total |
| quantidade_parcelas | smallint | Quantidade de parcelas |
| parcela_atual | smallint | Parcela atual |
| valor_parcela | decimal(14,2) | Valor da parcela |
| primeira_data_vencimento | date | Primeiro vencimento |
| situacao | varchar | pending, paid, overdue, canceled |
| observacoes | text nullable | Observações |
| criado_em / atualizado_em | timestamps | Controle |
| excluido_em | softDeletes | Histórico |

Índices:

- `index(usuario_id, situacao)`
- `index(usuario_id, primeira_data_vencimento)`
- `index(cartao_credito_id)`

### emprestimos

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId | FK usuarios |
| categoria_id | foreignId nullable | FK categorias_financeiras |
| credor_nome | varchar | Banco ou credor |
| descricao | varchar nullable | Descrição |
| valor_principal | decimal(14,2) | Valor contratado |
| taxa_juros | decimal(8,4) nullable | Taxa ao mês ou ao ano |
| periodo_taxa_juros | varchar nullable | monthly, yearly |
| quantidade_parcelas | smallint | Total de parcelas |
| valor_parcela | decimal(14,2) | Valor da parcela |
| primeira_data_vencimento | date | Primeiro vencimento |
| situacao | varchar | active, paid, overdue, canceled |
| criado_em / atualizado_em | timestamps | Controle |
| excluido_em | softDeletes | Histórico |

Índices:

- `index(usuario_id, situacao)`
- `index(usuario_id, primeira_data_vencimento)`

### parcelas_emprestimos

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| emprestimo_id | foreignId | FK emprestimos |
| usuario_id | foreignId | FK usuarios para consultas rápidas e policy |
| numero_parcela | smallint | Número da parcela |
| data_vencimento | date | Vencimento |
| valor | decimal(14,2) | Valor |
| pago_em | timestamp nullable | Pagamento |
| situacao | varchar | pending, paid, overdue, canceled |
| criado_em / atualizado_em | timestamps | Controle |

Índices:

- `unique(emprestimo_id, numero_parcela)`
- `index(usuario_id, situacao)`
- `index(usuario_id, data_vencimento)`

### categorias_financeiras

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId nullable | Null para categorias globais |
| nome | varchar | Nome |
| tipo | varchar | debt, loan, income, expense |
| cor | varchar nullable | Hex |
| criado_em / atualizado_em | timestamps | Controle |

### ativos_btc

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId | FK usuarios |
| rotulo | varchar | Nome da carteira, corretora ou ativo |
| tipo_ativo | varchar | BTC, RENDA_FIXA ou RENDA_VARIAVEL |
| quantidade_satoshis | decimal(30,10) nullable | Quantidade em satoshis para ativos BTC |
| preco_medio_compra | decimal(18,2) nullable | Preço médio para BTC |
| valor_investido | decimal(18,2) nullable | Valor aplicado em ativos não BTC |
| valor_atual | decimal(18,2) nullable | Valor atual em ativos não BTC |
| moeda | char(3) | BRL/USD |
| criado_em / atualizado_em | timestamps | Controle |
| excluido_em | softDeletes | Histórico |

### capturas_precos_btc

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| provedor | varchar | Provedor |
| moeda | char(3) | BRL/USD |
| preco | decimal(18,2) | Cotação |
| capturado_em | timestamp | Momento da captura |
| criado_em / atualizado_em | timestamps | Controle |

Índices:

- `index(moeda, capturado_em)`

### configuracoes_usuario

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId | FK usuarios, único |
| moeda_padrao | char(3) | BRL por padrão |
| fuso_horario | varchar | America/Sao_Paulo por padrão |
| preferencias_dashboard | jsonb | Preferências flexíveis |
| preferencias_notificacao | jsonb | Preferências flexíveis |
| criado_em / atualizado_em | timestamps | Controle |

### chamados_suporte

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| usuario_id | foreignId | FK usuarios |
| assunto | varchar | Assunto |
| categoria | varchar nullable | Categoria |
| prioridade | varchar | low, normal, high |
| situacao | varchar | open, waiting_user, waiting_support, resolved, closed |
| criado_em / atualizado_em | timestamps | Controle |

### mensagens_chamados_suporte

| Campo | Tipo | Observação |
| --- | --- | --- |
| id | bigserial | PK |
| chamado_id | foreignId | FK chamados_suporte |
| usuario_id | foreignId | Autor |
| mensagem | text | Conteúdo |
| interno | boolean | Padrão false |
| criado_em / atualizado_em | timestamps | Controle |

## Relacionamentos

```text
usuarios 1---n receitas_mensais

usuarios 1---n cartoes_credito
usuarios 1---n dividas_cartao_credito
cartoes_credito 1---n dividas_cartao_credito

usuarios 1---n emprestimos
emprestimos 1---n parcelas_emprestimos

usuarios 1---n ativos_btc
usuarios 1---1 configuracoes_usuario

usuarios 1---n chamados_suporte
chamados_suporte 1---n mensagens_chamados_suporte
```

## Segurança e Autorização

- Usar Laravel Sanctum para autenticação por token.
- Proteger rotas privadas com middleware `auth:sanctum`.
- Aplicar Policies em todos os recursos com `usuario_id`.
- Nunca confiar em `usuario_id` recebido pelo cliente; usar sempre `$request->user()->id`.
- Validar payloads com Form Requests.
- Aplicar rate limit em login, suporte e endpoints públicos.
- Usar hash de senha nativo do Laravel.
- Guardar segredos somente em `.env`, nunca no repositório.
- Ativar CORS apenas para origens conhecidas em produção.

Exemplo de regra de policy:

```php
public function view(User $user, CreditCardDebt $debt): bool
{
    return $debt->usuario_id === $user->id;
}
```

## API

### Convenções

- Prefixo: `/api/v1`.
- Resposta JSON sempre padronizada.
- Paginação em listas.
- Filtros por query string.
- Datas em ISO 8601.
- Dinheiro retornado como string decimal para evitar perda de precisão no frontend.

Exemplo de resposta:

```json
{
  "data": {
    "id": 1,
    "descricao": "Notebook",
    "valor_total": "4500.00",
    "situacao": "pending"
  }
}
```

Exemplo de erro:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "valor_total": ["The valor total field is required."]
  }
}
```

## Docker

### Containers Obrigatórios

```text
backend
postgres
```

### docker-compose.yml Proposto

```yaml
services:
  backend:
    build:
      context: .
      dockerfile: docker/backend/Dockerfile
    container_name: cfp_backend
    working_dir: /var/www/html
    volumes:
      - .:/var/www/html
      - composer_cache:/tmp/composer-cache
    ports:
      - "8000:8000"
    environment:
      COMPOSER_CACHE_DIR: /tmp/composer-cache
      APP_ENV: local
      APP_DEBUG: "true"
      APP_URL: http://localhost:8000
      FRONTEND_URL: http://localhost:3000,http://localhost:5173
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: cfp_backend
      DB_USERNAME: cfp_backend
      DB_PASSWORD: cfp_backend
      QUEUE_CONNECTION: database
      CACHE_STORE: database
      SESSION_DRIVER: database
    depends_on:
      postgres:
        condition: service_healthy
    command: php artisan serve --host=0.0.0.0 --port=8000

  postgres:
    image: postgres:16-alpine
    container_name: cfp_postgres
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: cfp_backend
      POSTGRES_USER: cfp_backend
      POSTGRES_PASSWORD: cfp_backend
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U cfp_backend -d cfp_backend"]
      interval: 5s
      timeout: 5s
      retries: 10

volumes:
  composer_cache:
  postgres_data:
```

### Dockerfile Proposto

```Dockerfile
FROM php:8.5-cli-alpine

RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libpq-dev \
    oniguruma-dev \
    unzip \
    zip \
    && docker-php-ext-install intl mbstring pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
```

Para produção, trocar `php artisan serve` por uma camada HTTP adequada, como Nginx/Caddy + PHP-FPM, Laravel Octane ou plataforma gerenciada.

## Configuração de Ambiente

Variáveis principais:

```env
APP_NAME="CFP Backend"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000,http://localhost:5173

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=cfp_backend
DB_USERNAME=cfp_backend
DB_PASSWORD=cfp_backend

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

## Jobs e Rotinas

Jobs iniciais:

- `SyncBtcPriceJob`: sincroniza preço do BTC.
- `UpdateOverdueDebtsJob`: marca dívidas vencidas.
- `UpdateOverdueLoanInstallmentsJob`: marca parcelas vencidas.
- `SendDueDateReminderJob`: envia lembretes futuros.

Schedule:

```php
Schedule::job(new SyncBtcPriceJob())->everyFifteenMinutes();
Schedule::job(new UpdateOverdueDebtsJob())->daily();
Schedule::job(new UpdateOverdueLoanInstallmentsJob())->daily();
```

## Observabilidade

Ambiente local:

- Logs em `storage/logs/laravel.log`.
- Laravel Telescope opcional apenas em desenvolvimento.

Produção:

- Logs estruturados.
- Health check em `/api/v1/health`.
- Métricas de erro, latência e uso de filas.
- Backup automatizado do PostgreSQL.

Endpoint de health check:

```text
GET /api/v1/health
```

Resposta:

```json
{
  "status": "ok",
  "service": "cfp-backend"
}
```

## Testes

Cobertura mínima esperada:

- Registro, login e logout.
- Bloqueio de acesso sem token.
- Usuário não consegue acessar dívidas/cartões/empréstimos de outro usuário.
- Criação de dívida de cartão.
- Criação de empréstimo com geração automática de parcelas.
- Cálculo de resumo financeiro.
- Abertura e resposta em ticket de suporte.

Comandos:

```bash
php artisan test
vendor/bin/pint
vendor/bin/phpstan analyse
```

## Roadmap de Implementação

### Fase 1: Base

- Criar projeto Laravel 13.
- Configurar Docker com backend e PostgreSQL.
- Configurar conexão Postgres.
- Instalar Sanctum.
- Criar autenticação básica.
- Criar endpoint `/api/v1/health`.

### Fase 2: Finanças Pessoais

- Criar migrations de cartões, dívidas, empréstimos, parcelas e categorias.
- Criar Models, Policies, Form Requests, Resources e Controllers.
- Implementar CRUD de cartões.
- Implementar CRUD de dívidas de cartão.
- Implementar CRUD de empréstimos.
- Gerar parcelas automaticamente.
- Criar resumo financeiro por usuário.

### Fase 3: Dashboard BTC e Análises

- Criar cadastro de ativos BTC.
- Criar estrutura de snapshots de preço.
- Criar provider de cotação.
- Criar dashboard consolidado.
- Criar endpoints de análises financeiras e BTC.

### Fase 4: Configurações e Suporte

- Criar configurações do usuário.
- Criar preferências de notificação.
- Criar tickets e mensagens de suporte.
- Criar filtros e paginação.

### Fase 5: Qualidade e Produção

- Adicionar testes de feature e unitários.
- Configurar Pint e Larastan.
- Configurar filas e schedule.
- Criar documentação da API.
- Preparar imagem Docker de produção.
- Configurar backup e monitoramento.

## Decisões Técnicas

- Usar PostgreSQL por robustez, constraints, JSONB e boa performance em consultas analíticas.
- Usar `decimal` para dinheiro e quantidade BTC.
- Usar Sanctum pela simplicidade e integração nativa com Laravel.
- Começar com queue/cache/session em database para reduzir containers no primeiro momento.
- Manter Redis como evolução natural quando houver necessidade de cache, filas mais intensas ou rate limiting distribuído.
- Usar soft delete em entidades financeiras para preservar histórico do usuário.
- Usar interfaces para integrações externas, especialmente cotações de BTC.

## Próximos Arquivos a Criar

```text
docker-compose.yml
docker/backend/Dockerfile
.env.example
routes/api.php
app/Http/Controllers/Api/V1/
database/migrations/
```

Esta arquitetura deve orientar a criação do backend de forma incremental, mantendo padrão moderno de mercado para Laravel, isolamento dos dados por usuário e uma base preparada para crescer sem refatorações grandes logo no início.
