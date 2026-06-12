# Buzzvel — Multi-Currency Payment API
## Documento de Arquitetura

> Teste técnico Buzzvel 2026 · Laravel 12 · PHP 8.2+ · Docker

---

## Guias e Padrões Adotados

### PHP-FIG — PSR Standards (o "guia oficial" do PHP)

| PSR | O que define | Ferramenta que automatiza |
|-----|-------------|--------------------------|
| PSR-1 | Classes em `PascalCase`, constantes em `UPPER_CASE`, métodos em `camelCase` | Laravel Pint |
| PSR-4 | Autoloading por namespace (Composer resolve) | Composer |
| PSR-12 | Estilo: 4 espaços, chaves, comprimento de linha ≤ 120 chars | Laravel Pint |

### OWASP API Security Top 10
Referência: [OWASP Laravel Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html)

Aplicado em cada camada do projeto (detalhes na seção Segurança).

---

## Decisão de Arquitetura: Action Pattern

O **Service Pattern** clássico (`PaymentService` com 20 métodos) viola SRP e vira um "God Class". O mercado atual de Laravel usa **Action Classes**:

| ❌ Service Pattern (evitar) | ✅ Action Pattern (usar) |
|-----------------------------|--------------------------|
| `PaymentService::create()` | `CreatePaymentRequestAction` |
| `PaymentService::approve()` | `ApprovePaymentRequestAction` |
| `PaymentService::reject()` | `RejectPaymentRequestAction` |

Cada Action faz **uma coisa só** — isso é SRP na prática.

> **Exceção**: `ExchangeRateApiService` mantém o nome "Service" porque é uma integração de infraestrutura, não uma operação de negócio.

---

## SOLID — Como cada princípio aparece no projeto

| Princípio | Onde aparece no código |
|-----------|----------------------|
| **S** — Single Responsibility | Cada Action class tem uma única responsabilidade |
| **O** — Open/Closed | `ExchangeRateServiceInterface` — troca de provider sem modificar código existente |
| **L** — Liskov Substitution | Qualquer implementação da interface substitui outra sem quebrar nada |
| **I** — Interface Segregation | Interface pequena e focada: apenas `getRate()` |
| **D** — Dependency Inversion | Controllers e Actions dependem da interface, não da implementação concreta |

---

## Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Framework | Laravel 12 |
| Linguagem | PHP 8.2+ |
| Banco de dados | MySQL 8.0 |
| Cache / Rate Limit | Redis 7 |
| Web server | Nginx (Alpine) |
| Runtime PHP | PHP 8.2-FPM |
| Autenticação | Laravel Sanctum |
| Código estilo | Laravel Pint (PSR-12) |
| Análise estática | Larastan (PHPStan para Laravel) |
| Containerização | Docker + Docker Compose |

---

## Estrutura de Pastas

```
payment-api/
├── app/
│   │
│   ├── Actions/                              # Lógica de negócio — uma classe, uma ação (SRP)
│   │   ├── Auth/
│   │   │   └── LogoutAction.php
│   │   └── Payment/
│   │       ├── CreatePaymentRequestAction.php   # busca câmbio + persiste
│   │       ├── ApprovePaymentRequestAction.php
│   │       └── RejectPaymentRequestAction.php
│   │
│   ├── Contracts/                            # Interfaces — Dependency Inversion Principle
│   │   └── ExchangeRateServiceInterface.php
│   │
│   ├── DTOs/                                 # Data Transfer Objects — PHP 8.2 readonly classes
│   │   └── ExchangeRateDTO.php
│   │
│   ├── Enums/                                # PHP 8.1+ enums nativos (type-safe)
│   │   ├── PaymentStatus.php                 # pending | approved | rejected | expired
│   │   └── UserRole.php                      # employee | finance
│   │
│   ├── Exceptions/
│   │   └── ExchangeRateException.php
│   │
│   ├── Http/
│   │   ├── Controllers/Api/V1/               # Versionamento: /api/v1/
│   │   │   ├── AuthController.php
│   │   │   └── PaymentRequestController.php
│   │   │
│   │   ├── Requests/                         # Validação separada do controller (SRP)
│   │   │   ├── Auth/
│   │   │   │   ├── LoginRequest.php
│   │   │   │   └── RegisterRequest.php
│   │   │   └── Payment/
│   │   │       ├── StorePaymentRequest.php
│   │   │       └── UpdatePaymentStatusRequest.php
│   │   │
│   │   └── Resources/Api/V1/                 # Formata o JSON de saída (sem expor dados internos)
│   │       ├── PaymentRequestResource.php
│   │       └── UserResource.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   └── PaymentRequest.php
│   │
│   ├── Policies/
│   │   └── PaymentRequestPolicy.php          # Autorização: quem pode fazer o quê
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php            # Bind: Interface → Implementação concreta
│   │
│   └── Services/ExchangeRate/
│       └── ExchangeRateApiService.php        # Implementa ExchangeRateServiceInterface
│
├── bootstrap/
│   └── app.php                               # Handler global de exceções customizado
│
├── database/
│   ├── migrations/
│   │   ├── xxxx_add_fields_to_users_table.php
│   │   └── xxxx_create_payment_requests_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── UserSeeder.php                    # 5+ employees + 1 finance
│
├── routes/
│   └── api.php                               # Todas as rotas com prefixo /v1
│
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   │   └── AuthTest.php
│   │   └── Payment/
│   │       ├── CreatePaymentRequestTest.php
│   │       ├── ListPaymentRequestTest.php
│   │       └── UpdatePaymentStatusTest.php
│   └── Unit/
│       └── ExchangeRateServiceTest.php       # Mock HTTP — sem chamar API real
│
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile
│
├── docker-compose.yml
├── .env.example                              # Sem secrets reais
├── phpstan.neon
└── README.md                                 # OBRIGATÓRIO para submissão
```

---

## Schema do Banco de Dados

### Tabela `users` (campos adicionados à migration padrão)

| Campo | Tipo | Observação |
|-------|------|-----------|
| `role` | `enum('employee','finance')` | default: `employee` |
| `country` | `string` | ex: Brazil, Japan |
| `currency_code` | `string(3)` | ex: BRL, JPY, GBP |

### Tabela `payment_requests`

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigint unsigned` | PK |
| `user_id` | `FK → users` | quem criou |
| `amount` | `decimal(15,2)` | valor na moeda local |
| `currency_code` | `string(3)` | ex: BRL |
| `description` | `string(255)` | obrigatório |
| `status` | `enum` | pending / approved / rejected / expired |
| `exchange_rate` | `decimal(15,6)` | **imutável** após criação |
| `exchange_rate_source` | `string` | ex: exchangerate-api.com |
| `exchange_rate_fetched_at` | `timestamp` | quando foi buscada |
| `amount_in_eur` | `decimal(15,2)` | amount / exchange_rate |
| `reviewed_by` | `FK nullable → users` | quem aprovou/rejeitou |
| `reviewed_at` | `timestamp nullable` | quando |
| `expires_at` | `timestamp` | created_at + 48h |
| `timestamps` | | created_at, updated_at |

---

## Endpoints da API

Base URL: `/api/v1`

### Autenticação

| Método | Endpoint | Auth | Descrição |
|--------|----------|------|-----------|
| `POST` | `/auth/register` | — | Registra novo usuário |
| `POST` | `/auth/login` | — | Login, retorna Bearer token |
| `POST` | `/auth/logout` | ✓ | Revoga o token atual |

### Payment Requests

| Método | Endpoint | Auth | Role | Descrição |
|--------|----------|------|------|-----------|
| `GET` | `/payment-requests` | ✓ | any | Lista (employee vê os seus; finance vê todos). Filtro: `?status=pending` |
| `POST` | `/payment-requests` | ✓ | any | Cria e busca câmbio automaticamente |
| `GET` | `/payment-requests/{id}` | ✓ | any | Detalhe (com restrição de ownership) |
| `PATCH` | `/payment-requests/{id}/status` | ✓ | finance | Aprova ou rejeita |

---

## Fluxo de Criação de Payment Request

```
POST /api/v1/payment-requests
  │
  ├── Middleware: Sanctum verifica token
  ├── StorePaymentRequest: valida campos (amount, currency_code, description)
  ├── PaymentRequestController::store()
  │     └── CreatePaymentRequestAction::__invoke()
  │           ├── ExchangeRateServiceInterface::getRate(currency_code)
  │           │     └── GET https://api.exchangerate-api.com/v4/latest/EUR
  │           │           └── retorna ExchangeRateDTO { rate, source, fetchedAt }
  │           ├── calcula amount_in_eur = amount / rate
  │           ├── define expires_at = now() + 48h
  │           └── PaymentRequest::create([...])  ← taxa salva, imutável
  └── PaymentRequestResource → resposta JSON 201
```

---

## Fluxo de Aprovação/Rejeição

```
PATCH /api/v1/payment-requests/{id}/status
  │
  ├── Middleware: Sanctum verifica token
  ├── UpdatePaymentStatusRequest: valida { status: approved|rejected }
  ├── PaymentRequestController::updateStatus()
  │     ├── Policy::updateStatus() → verifica se user tem role finance
  │     ├── Verifica se status atual é 'pending' (não pode reprocessar)
  │     └── ApprovePaymentRequestAction ou RejectPaymentRequestAction
  │           ├── atualiza status
  │           ├── salva reviewed_by e reviewed_at
  │           └── retorna PaymentRequest atualizado
  └── PaymentRequestResource → resposta JSON 200
```

---

## Tarefa Agendada — Expiração Automática

```php
// routes/console.php
Schedule::command('payments:expire-pending')->hourly();

// app/Console/Commands/ExpirePaymentRequestsCommand.php
// Busca todos os pending com expires_at < now() e muda para expired
```

No Docker, um container `scheduler` roda `php artisan schedule:work` continuamente — sem precisar de cron no host.

---

## Segurança — OWASP API Security Top 10

| # | Risco | Mitigação no projeto |
|---|-------|---------------------|
| 1 | Broken Object Level Authorization | Policy verifica `user_id` antes de qualquer leitura/escrita |
| 2 | Broken Authentication | Sanctum + rate limit `6 req/min` no login |
| 3 | Broken Object Property Authorization | API Resources — nunca retorna Model direto (sem `password`, etc.) |
| 4 | Unrestricted Resource Consumption | Rate limit `60 req/min` geral + paginação no index |
| 5 | Broken Function Level Authorization | Role `finance` verificado via Policy no approve/reject |
| 7 | SSRF | `Http::timeout(10)` + URL hardcoded, nunca aceita URL do usuário |
| 8 | Security Misconfiguration | `.env.example` sem secrets, `APP_DEBUG=false` em produção |
| 10 | Unsafe Consumption of APIs | Try/catch no ExchangeRateService com exception tipada |

---

## Docker — Containers

```
┌─────────────┐    ┌─────────────┐
│    nginx    │───▶│  app (FPM)  │
│  :8000→80   │    │  PHP 8.2    │
└─────────────┘    └──────┬──────┘
                          │
              ┌───────────┼───────────┐
              ▼           ▼           ▼
         ┌────────┐  ┌────────┐  ┌───────────┐
         │ mysql  │  │ redis  │  │ scheduler │
         │  8.0   │  │   7   │  │ schedule: │
         └────────┘  └────────┘  │   :work   │
                                  └───────────┘
```

| Container | Imagem | Porta | Função |
|-----------|--------|-------|--------|
| `app` | PHP 8.2-FPM (custom Dockerfile) | — | Executa o Laravel |
| `nginx` | nginx:alpine | 8000:80 | Web server |
| `db` | mysql:8.0 | — | Banco de dados (volume persistente) |
| `redis` | redis:7-alpine | — | Rate limiting + cache |
| `scheduler` | Mesmo do `app` | — | `php artisan schedule:work` |

---

## Seeders — 5 Funcionários

| Nome | País | Moeda | Role |
|------|------|-------|------|
| Ana Lima | Brasil | BRL | employee |
| James Smith | Reino Unido | GBP | employee |
| Yuki Tanaka | Japão | JPY | employee |
| Priya Patel | Índia | INR | employee |
| Lucas Dupont | Canadá | CAD | employee |
| Maria Santos | Portugal | EUR | **finance** |

---

## Testes

| Arquivo | O que testa |
|---------|------------|
| `Unit/ExchangeRateServiceTest` | Mock do Http facade — sem chamada real à API |
| `Feature/Auth/AuthTest` | Register, login, logout, token inválido |
| `Feature/Payment/CreatePaymentRequestTest` | Criação com câmbio mockado, validações |
| `Feature/Payment/ListPaymentRequestTest` | Filtro por status, isolamento por role |
| `Feature/Payment/UpdatePaymentStatusTest` | Aprovação (finance), rejeição, acesso negado (employee), pedido já processado |

---

## Ferramentas de Qualidade de Código

```bash
# Formatar código automaticamente (PSR-12)
./vendor/bin/pint

# Análise estática — encontra erros sem rodar o código
./vendor/bin/phpstan analyse --level=8

# Rodar testes
php artisan test
```

---

## Ordem de Implementação

1. `laravel new payment-api` + instalar Sanctum
2. Migrations (users + payment_requests)
3. Models com cast de Enum e relacionamentos
4. Auth (register / login / logout)
5. `ExchangeRateServiceInterface` + `ExchangeRateApiService`
6. Bind da interface no `AppServiceProvider`
7. Actions: Create, Approve, Reject
8. Controller + Routes + Resources
9. Policy de autorização
10. Command de expiração + schedule
11. Seeders
12. Testes
13. Docker completo
14. README

---

## O que diferencia essa arquitetura

| Diferencial | Por quê importa |
|------------|----------------|
| Action Pattern | SRP real — cada operação é uma classe testável e isolada |
| Interface para ExchangeRate | DIP — troca de provider sem tocar nas Actions |
| DTO `readonly` (PHP 8.2) | Dados imutáveis, type-safe, sem efeitos colaterais |
| API versionada `/v1/` | Permite evolução sem breaking change |
| Docker com scheduler | Fator decisivo na vaga Buzzvel |
| PHPStan nível 8 + Pint | Padrão de times profissionais |
| OWASP aplicado | Segurança real em cada camada |

---

*Referências: [PHP-FIG PSR](https://www.php-fig.org/psr/) · [OWASP Laravel Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html) · [Action Pattern](https://nabilhassen.com/action-pattern-in-laravel-concept-benefits-best-practices) · [Laravel Best Practices](https://medium.com/@paulofelipemartins/laravel-best-practices-solid-clean-architecture-design-patterns-c0fab56fe40c)*
