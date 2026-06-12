# Buzzvel — Multi-Currency Payment API
## Architecture Document

> Buzzvel 2026 Technical Test · Laravel 12 · PHP 8.2+ · Docker

---

## Guides and Standards

### PHP-FIG — PSR Standards

| PSR | What it defines | Automated by |
|-----|-----------------|--------------|
| PSR-1 | `PascalCase` classes, `UPPER_CASE` constants, `camelCase` methods | Laravel Pint |
| PSR-4 | Namespace-based autoloading (resolved by Composer) | Composer |
| PSR-12 | Code style: 4 spaces, braces, max line length ≤ 120 chars | Laravel Pint |

### OWASP API Security Top 10
Reference: [OWASP Laravel Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html)

Applied at every layer of the project (details in the Security section).

---

## Architectural Decision: Action Pattern

The classic **Service Pattern** (`PaymentService` with 20 methods) violates SRP and becomes a "God Class". Modern Laravel development uses **Action Classes**:

| ❌ Service Pattern (avoid) | ✅ Action Pattern (use) |
|----------------------------|-------------------------|
| `PaymentService::create()` | `CreatePaymentRequestAction` |
| `PaymentService::approve()` | `ApprovePaymentRequestAction` |
| `PaymentService::reject()` | `RejectPaymentRequestAction` |

Each Action does **one thing only** — SRP in practice.

> **Exception**: `ExchangeRateApiService` keeps the "Service" naming because it is an infrastructure integration, not a business operation.

---

## SOLID — How Each Principle Appears in the Project

| Principle | Where it appears in the code |
|-----------|------------------------------|
| **S** — Single Responsibility | Each Action class has a single responsibility |
| **O** — Open/Closed | `ExchangeRateServiceInterface` — swap provider without modifying existing code |
| **L** — Liskov Substitution | Any interface implementation can replace another without breaking anything |
| **I** — Interface Segregation | Small, focused interface: only `getRate()` |
| **D** — Dependency Inversion | Controllers and Actions depend on the interface, not the concrete implementation |

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 |
| Language | PHP 8.2+ |
| Database | MySQL 8.0 |
| Cache / Rate Limiting | Redis 7 |
| Web server | Nginx (Alpine) |
| PHP runtime | PHP 8.2-FPM |
| Authentication | Laravel Sanctum |
| Code style | Laravel Pint (PSR-12) |
| Static analysis | Larastan (PHPStan for Laravel) |
| Containerisation | Docker + Docker Compose |

---

## Folder Structure

```
payment-api/
├── app/
│   │
│   ├── Actions/                              # Business logic — one class, one action (SRP)
│   │   ├── Auth/
│   │   │   └── LogoutAction.php
│   │   └── Payment/
│   │       ├── CreatePaymentRequestAction.php   # fetches rate + persists
│   │       ├── ApprovePaymentRequestAction.php
│   │       └── RejectPaymentRequestAction.php
│   │
│   ├── Contracts/                            # Interfaces — Dependency Inversion Principle
│   │   └── ExchangeRateServiceInterface.php
│   │
│   ├── DTOs/                                 # Data Transfer Objects — PHP 8.2 readonly classes
│   │   └── ExchangeRateDTO.php
│   │
│   ├── Enums/                                # PHP 8.1+ native enums (type-safe)
│   │   ├── PaymentStatus.php                 # pending | approved | rejected | expired
│   │   └── UserRole.php                      # employee | finance
│   │
│   ├── Exceptions/
│   │   └── ExchangeRateException.php
│   │
│   ├── Http/
│   │   ├── Controllers/Api/V1/               # Versioning: /api/v1/
│   │   │   ├── AuthController.php
│   │   │   └── PaymentRequestController.php
│   │   │
│   │   ├── Requests/                         # Validation decoupled from controller (SRP)
│   │   │   ├── Auth/
│   │   │   │   ├── LoginRequest.php
│   │   │   │   └── RegisterRequest.php
│   │   │   └── Payment/
│   │   │       ├── StorePaymentRequest.php
│   │   │       └── UpdatePaymentStatusRequest.php
│   │   │
│   │   └── Resources/Api/V1/                 # Formats JSON output (no internal fields exposed)
│   │       ├── PaymentRequestResource.php
│   │       └── UserResource.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   └── PaymentRequest.php
│   │
│   ├── Policies/
│   │   └── PaymentRequestPolicy.php          # Authorization: who can do what
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php            # Binding: Interface → Concrete implementation
│   │
│   └── Services/ExchangeRate/
│       └── ExchangeRateApiService.php        # Implements ExchangeRateServiceInterface
│
├── bootstrap/
│   └── app.php                               # Custom global exception handler
│
├── database/
│   ├── migrations/
│   │   ├── xxxx_add_fields_to_users_table.php
│   │   └── xxxx_create_payment_requests_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── UserSeeder.php                    # 5+ employees + 1 finance user
│
├── routes/
│   └── api.php                               # All routes prefixed with /v1
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
│       └── ExchangeRateServiceTest.php       # HTTP mock — no real API call
│
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile
│
├── docker-compose.yml
├── .env.example                              # No real secrets
├── phpstan.neon
└── README.md                                 # Required for submission
```

---

## Database Schema

### `users` table (fields added to the default migration)

| Column | Type | Notes |
|--------|------|-------|
| `role` | `enum('employee','finance')` | default: `employee` |
| `country` | `string` | e.g.: Brazil, Japan |
| `currency_code` | `string(3)` | e.g.: BRL, JPY, GBP |

### `payment_requests` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | `bigint unsigned` | PK |
| `user_id` | `FK → users` | creator |
| `amount` | `decimal(15,2)` | value in local currency |
| `currency_code` | `string(3)` | e.g.: BRL |
| `description` | `string(255)` | required |
| `status` | `enum` | pending / approved / rejected / expired |
| `exchange_rate` | `decimal(15,6)` | **immutable** after creation |
| `exchange_rate_source` | `string` | e.g.: exchangerate-api.com |
| `exchange_rate_fetched_at` | `timestamp` | when it was fetched |
| `amount_in_eur` | `decimal(15,2)` | amount / exchange_rate |
| `reviewed_by` | `FK nullable → users` | who approved/rejected |
| `reviewed_at` | `timestamp nullable` | when |
| `expires_at` | `timestamp` | created_at + 48h |
| `timestamps` | | created_at, updated_at |

---

## API Endpoints

Base URL: `/api/v1`

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/auth/register` | — | Register new user |
| `POST` | `/auth/login` | — | Login, returns Bearer token |
| `POST` | `/auth/logout` | ✓ | Revoke current token |

### Payment Requests

| Method | Endpoint | Auth | Role | Description |
|--------|----------|------|------|-------------|
| `GET` | `/payment-requests` | ✓ | any | List (employee sees own; finance sees all). Filter: `?status=pending` |
| `POST` | `/payment-requests` | ✓ | any | Create and fetch exchange rate automatically |
| `GET` | `/payment-requests/{id}` | ✓ | any | Detail (with ownership restriction) |
| `PATCH` | `/payment-requests/{id}/status` | ✓ | finance | Approve or reject |

---

## Payment Request Creation Flow

```
POST /api/v1/payment-requests
  │
  ├── Middleware: Sanctum validates token
  ├── StorePaymentRequest: validates fields (amount, description)
  ├── PaymentRequestController::store()
  │     └── CreatePaymentRequestAction::__invoke()
  │           ├── ExchangeRateServiceInterface::getRate(currency_code)
  │           │     └── GET https://api.exchangerate-api.com/v4/latest/EUR
  │           │           └── returns ExchangeRateDTO { rate, source, fetchedAt }
  │           ├── calculates amount_in_eur = amount / rate
  │           ├── sets expires_at = now() + 48h
  │           └── PaymentRequest::create([...])  ← rate stored, immutable
  └── PaymentRequestResource → JSON 201 response
```

---

## Approval/Rejection Flow

```
PATCH /api/v1/payment-requests/{id}/status
  │
  ├── Middleware: Sanctum validates token
  ├── UpdatePaymentStatusRequest: validates { status: approved|rejected }
  ├── PaymentRequestController::updateStatus()
  │     ├── Policy::updateStatus() → checks user has finance role
  │     ├── Checks current status is 'pending' (cannot reprocess)
  │     └── ApprovePaymentRequestAction or RejectPaymentRequestAction
  │           ├── updates status
  │           ├── saves reviewed_by and reviewed_at
  │           └── returns updated PaymentRequest
  └── PaymentRequestResource → JSON 200 response
```

---

## Scheduled Task — Automatic Expiration

```php
// routes/console.php
Schedule::command('payments:expire-pending')->hourly();

// app/Console/Commands/ExpirePaymentRequestsCommand.php
// Finds all pending requests with expires_at < now() and sets status to expired
```

In Docker, a dedicated `scheduler` container runs `php artisan schedule:work` continuously — no host-level cron required.

---

## Security — OWASP API Security Top 10

| # | Risk | Mitigation in this project |
|---|------|---------------------------|
| 1 | Broken Object Level Authorization | Policy checks `user_id` before any read/write |
| 2 | Broken Authentication | Sanctum + rate limit `6 req/min` on login |
| 3 | Broken Object Property Authorization | API Resources — never returns Model directly (no `password`, etc.) |
| 4 | Unrestricted Resource Consumption | Rate limit `60 req/min` general + pagination on index |
| 5 | Broken Function Level Authorization | `finance` role verified via Policy on approve/reject |
| 7 | SSRF | `Http::timeout(10)` + hardcoded URL, never accepts user-supplied URL |
| 8 | Security Misconfiguration | `.env.example` with no secrets, `APP_DEBUG=false` in production |
| 10 | Unsafe Consumption of APIs | Try/catch in ExchangeRateService with typed exception |

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
         │  8.0   │  │   7    │  │ schedule: │
         └────────┘  └────────┘  │   :work   │
                                  └───────────┘
```

| Container | Image | Port | Role |
|-----------|-------|------|------|
| `app` | PHP 8.2-FPM (custom Dockerfile) | — | Runs Laravel |
| `nginx` | nginx:alpine | 8000:80 | Web server |
| `db` | mysql:8.0 | — | Database (persistent volume) |
| `redis` | redis:7-alpine | — | Rate limiting + cache |
| `scheduler` | Same as `app` | — | `php artisan schedule:work` |

---

## Seeders — 5 Employees

| Name | Country | Currency | Role |
|------|---------|----------|------|
| Ana Lima | Brazil | BRL | employee |
| James Smith | United Kingdom | GBP | employee |
| Yuki Tanaka | Japan | JPY | employee |
| Priya Patel | India | INR | employee |
| Lucas Dupont | Canada | CAD | employee |
| Maria Santos | Portugal | EUR | **finance** |

---

## Tests

| File | What it tests |
|------|---------------|
| `Unit/ExchangeRateServiceTest` | Http facade mock — no real API call |
| `Feature/Auth/AuthTest` | Register, login, logout, invalid token |
| `Feature/Payment/CreatePaymentRequestTest` | Creation with mocked exchange rate, validations |
| `Feature/Payment/ListPaymentRequestTest` | Status filter, role-based isolation |
| `Feature/Payment/UpdatePaymentStatusTest` | Approval (finance), rejection, access denied (employee), already-processed request |

---

## Code Quality Tools

```bash
# Auto-format code (PSR-12)
composer format

# Static analysis — finds errors without running code
composer analyse

# Run tests
composer test
```

---

## What Differentiates This Architecture

| Differentiator | Why it matters |
|----------------|----------------|
| Action Pattern | Real SRP — each operation is a testable, isolated class |
| Interface for ExchangeRate | DIP — swap provider without touching Actions |
| `readonly` DTO (PHP 8.2) | Immutable, type-safe data, no side effects |
| Versioned API `/v1/` | Allows evolution without breaking changes |
| Docker with scheduler container | Decision factor for the Buzzvel role |
| PHPStan level 6 + Pint | Professional team standard |
| OWASP applied | Real security at every layer |

---

*References: [PHP-FIG PSR](https://www.php-fig.org/psr/) · [OWASP Laravel Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html) · [Action Pattern](https://nabilhassen.com/action-pattern-in-laravel-concept-benefits-best-practices) · [Laravel Best Practices](https://medium.com/@paulofelipemartins/laravel-best-practices-solid-clean-architecture-design-patterns-c0fab56fe40c)*
