# CFP Backend

Backend API para controle financeiro pessoal e acompanhamento de Bitcoin, construido com Laravel, PostgreSQL e Docker.

## Stack

- PHP 8.3+
- Laravel 13
- PostgreSQL 16
- Laravel Sanctum
- Docker Compose
- PHPUnit
- Laravel Pint

## Modulos Principais

- Autenticacao e usuarios
- Captura de e-mails do fluxo de cadastro
- Dashboard Bitcoin
- Ativos BTC por usuario
- Financas pessoais
- Cartoes, dividas e emprestimos
- Analytics
- Configuracoes
- Suporte

## Requisitos

- Docker
- Docker Compose
- Git

Para desenvolvimento fora do container, tambem e necessario:

- PHP 8.3+
- Composer 2
- Node.js 20+

## Como Rodar Localmente

Clone o repositorio:

```bash
git clone git@github.com:marcelocaixeta/cfp-backend.git
cd cfp-backend
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
```

Suba os containers:

```bash
docker compose up -d --build
```

Instale as dependencias PHP dentro do container:

```bash
docker compose exec backend composer install
```

Gere a chave da aplicacao:

```bash
docker compose exec backend php artisan key:generate
```

Execute as migrations:

```bash
docker compose exec backend php artisan migrate
```

Para rodar os testes, suba tambem o banco isolado de testes e execute a suite:

```bash
docker compose up -d postgres_test
docker compose exec backend php artisan config:clear
docker compose exec backend php artisan test
```

Os testes usam o banco `cfp_backend_testing` no servico `postgres_test`, com volume separado de `postgres_data`. Assim o `RefreshDatabase` limpa apenas os dados de teste e nao toca no banco local `cfp_backend`. A suite tambem possui uma trava em `tests/TestCase.php` para abortar caso a conexao esteja apontando para o banco principal.

A API ficara disponivel em:

```text
http://localhost:8000/api/v1
```

## Endpoints Iniciais

```text
GET    /api/v1/health
POST   /api/v1/email-signups
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
GET    /api/v1/me
PATCH  /api/v1/me
GET    /api/v1/users
PATCH  /api/v1/users/{user}/profile
GET    /api/v1/finance/summary?mes=YYYY-MM
GET    /api/v1/finance/dashboard?mes=YYYY-MM
GET    /api/v1/finance/current-week-due-dates?mes=YYYY-MM
GET    /api/v1/finance/receitas-mensais
POST   /api/v1/finance/receitas-mensais
GET    /api/v1/finance/receitas-mensais/{monthlyIncome}
PUT    /api/v1/finance/receitas-mensais/{monthlyIncome}
PATCH  /api/v1/finance/receitas-mensais/{monthlyIncome}
DELETE /api/v1/finance/receitas-mensais/{monthlyIncome}
GET    /api/v1/btc/dashboard
GET    /api/v1/analytics/overview
```

## Captura de E-mail

Endpoint usado pela pop-up do frontend:

```http
POST /api/v1/email-signups
```

Payload:

```json
{
  "email": "usuario@email.com",
  "origem": "registration-popup"
}
```

Resposta:

```json
{
  "data": {
    "id": 1,
    "email": "usuario@email.com",
    "origem": "registration-popup"
  }
}
```

## Frontend Local

O CORS local aceita as origens:

```text
http://localhost:3000
http://localhost:5173
```

O frontend React deve apontar para:

```text
REACT_APP_API_URL=http://localhost:8000/api
```

## Qualidade

Formatar codigo PHP:

```bash
docker compose exec backend ./vendor/bin/pint
```

Rodar testes:

```bash
docker compose exec backend php artisan test
```

## Boas Praticas de Versionamento

Arquivos sensiveis e artefatos locais nao devem ser commitados:

- `.env`
- `vendor/`
- `node_modules/`
- logs
- caches
- bancos locais SQLite
- arquivos de IDE
- dados temporarios de runtime

O arquivo `.env.example` deve ser versionado para documentar as variaveis esperadas sem expor segredos.

## Documentacao

Mais detalhes da arquitetura estao em:

```text
docs/ARCHITECTURE.md
```
