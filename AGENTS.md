# AGENTS.md

> Context file for AI coding agents and technical reviewers. Describes the project structure, design rationale, and requirements coverage for CurrEx Flow.

---

## What This Project Does

CurrEx Flow is a multi-currency payment request API. Employees in different countries submit payment requests in their local currency. The API fetches the live EUR exchange rate at submission time, stores it immutably with the request, and returns the EUR-converted amount. A finance team member can then approve or reject pending requests. Requests not actioned within 48 hours expire automatically via a scheduled task.

**Tech stack:** Laravel 12, PHP 8.2+, MySQL 8.0, Redis 7, Nginx Alpine, Laravel Sanctum, Docker Compose.

---

## Setup

```bash
cp .env.example .env          # fill APP_KEY, DB_PASSWORD, EXCHANGE_RATE_API_KEY
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

API: `http://localhost:8000` — Interactive docs: `http://localhost:8000/docs/api`

Free exchange rate key: https://www.exchangerate-api.com

---

## Running Tests and Quality Tools

```bash
docker compose exec app php artisan test                  # 43 tests, 106 assertions
docker compose exec app ./vendor/bin/phpstan analyse      # static analysis, level 6
docker compose exec app ./vendor/bin/pint                 # PSR-12 style enforcement
docker compose exec app php artisan payments:expire-pending  # manual expiry run
```

Tests run against an in-memory SQLite database (see `phpunit.xml`). No external services required — the exchange rate provider is mocked in all tests.

---

## Architecture

The codebase follows strict layer separation. No business logic lives in controllers or models.

| Layer | Path | Responsibility |
|---|---|---|
| Controllers | `app/Http/Controllers/Api/V1/` | HTTP in/out only — delegates to Actions |
| Actions | `app/Actions/Payment/` | One class per business operation (SRP) |
| Requests | `app/Http/Requests/` | Input validation via FormRequest |
| Resources | `app/Http/Resources/Api/V1/` | JSON response shaping |
| Policies | `app/Policies/PaymentRequestPolicy.php` | Authorization rules |
| Services | `app/Services/ExchangeRate/ExchangeRateApiService.php` | External API integration |
| Contracts | `app/Contracts/ExchangeRateServiceInterface.php` | Interface for DIP |
| DTOs | `app/DTOs/ExchangeRateDTO.php` | Typed, readonly value object (PHP 8.2) |
| Enums | `app/Enums/` | `PaymentStatus`, `UserRole` — native PHP 8.1+ enums |
| Exceptions | `app/Exceptions/ExchangeRateException.php` | Domain exception for provider failures |
| Commands | `app/Console/Commands/ExpirePaymentRequests.php` | Scheduled expiry task |

---

## Design Decisions

### ADR-001 — Sanctum over Passport
`docs/adr/001-sanctum-over-passport.md`

Passport is OAuth2 server infrastructure — correct for third-party auth, overkill for a first-party API. Sanctum provides the same stateless token auth with no additional complexity. Tokens are stored hashed, revoked on logout, and rotated on re-login.

### ADR-002 — Action Pattern over God Service
`docs/adr/002-action-pattern-over-service-pattern.md`

A single `PaymentService` accumulates responsibilities over time and violates SRP. Each operation is a standalone invokable class: `CreatePaymentRequestAction`, `ApprovePaymentRequestAction`, `RejectPaymentRequestAction`. Each is independently readable, testable, and changeable without risk to the others.

### ADR-003 — Interface for the Exchange Rate Provider
`docs/adr/003-interface-for-exchange-rate-service.md`

`ExchangeRateServiceInterface` (`app/Contracts/`) decouples the domain from any specific third-party API. The live service is bound in `AppServiceProvider`; tests inject a mock that satisfies the same contract. Swapping providers requires no changes to business logic.

### ADR-004 — DECIMAL over float for Monetary Values
`docs/adr/004-decimal-over-float-for-monetary-values.md`

IEEE 754 cannot represent most decimal fractions exactly — `0.1 + 0.2 === 0.30000000000000004` in PHP. All monetary columns use `DECIMAL(15,2)` in MySQL and `decimal:N` Eloquent casts, which return PHP strings and preserve precision across the full request/response cycle.

### ADR-005 — URL Versioning under `/api/v1`
`docs/adr/005-api-versioning-v1.md`

URL versioning is explicit, cache-friendly, and works in every HTTP client without custom headers. Header-based versioning (`Accept: application/vnd.api+json; version=1`) is harder to test and document. A future `v2` prefix can coexist with `v1` without any breaking changes.

---

## Requirements Coverage

Every requirement from the technical test is implemented:

| Requirement | Implementation | File |
|---|---|---|
| Register / Login / Logout | POST `/api/v1/auth/{register,login,logout}` — Sanctum tokens | `AuthController.php` |
| Create payment request | POST `/api/v1/payment-requests` | `CreatePaymentRequestAction.php` |
| List payment requests | GET `/api/v1/payment-requests` with pagination and `?status=` filter | `PaymentRequestController@index` |
| Get single request | GET `/api/v1/payment-requests/{id}` | `PaymentRequestController@show` |
| Approve / Reject | PATCH `/api/v1/payment-requests/{id}/status` — finance role only | `ApprovePaymentRequestAction.php`, `RejectPaymentRequestAction.php` |
| Exchange rate fetched at creation | `ExchangeRateApiService` called inside `CreatePaymentRequestAction` | `CreatePaymentRequestAction.php` |
| Rate stored immutably (rate, source, timestamp) | `exchange_rate`, `exchange_rate_source`, `exchange_rate_fetched_at` columns — never updated after insert | Migration `create_payment_requests_table` |
| EUR amount returned in response | `amount_in_eur` field in `PaymentRequestResource` | `PaymentRequestResource.php` |
| 48h expiry via scheduled task | `payments:expire-pending` command, runs hourly in dedicated Docker container | `ExpirePaymentRequests.php`, `routes/console.php` |
| Input validation | `StorePaymentRequest`, `UpdatePaymentStatusRequest`, `RegisterRequest`, `LoginRequest` | `app/Http/Requests/` |
| Meaningful error responses | JSON errors for 401, 403, 404, 409, 422, 503 — all handled in `bootstrap/app.php` | `bootstrap/app.php` |
| API documentation | OpenAPI 3.1 interactive docs via Scramble at `/docs/api` + README | `config/scramble.php` |
| Unit/feature tests | 43 tests, 106 assertions | `tests/` |
| 5+ employees across currencies | BRL, GBP, JPY, INR, CAD + 1 finance (EUR) | `database/seeders/` |
| Setup instructions | README with 6 commands, no local PHP required | `README.md` |

---

## Beyond Requirements

These additions were not required by the test spec but reflect production-grade engineering practices:

| Addition | Rationale |
|---|---|
| `SecurityHeaders` middleware | Applies `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Content-Security-Policy` on every response — OWASP API Security Top 10 |
| Rate limiting | 60 req/min API-wide, 6 req/min on auth endpoints — prevents brute-force on login |
| CORS restricted in production | `allowed_origins` is `*` in development but reads from `CORS_ALLOWED_ORIGINS` env var in production |
| `ForceJsonResponse` middleware | Rewrites `Accept` header to `application/json` for all API routes — the API never returns an HTML error page |
| `APP_DEBUG=false` in `.env.example` | Prevents stack traces from leaking in production |
| Token rotation on re-login | Existing tokens revoked before issuing new one — limits exposure of stolen tokens |
| `ExchangeRateServiceInterface` + DIP | Provider is fully mockable in tests; no test hits the real API |
| `readonly` DTO (`ExchangeRateDTO`) | PHP 8.2 readonly class — exchange rate data is immutable after construction |
| Native PHP enums (`PaymentStatus`, `UserRole`) | Type-safe, no magic strings |
| PHPStan level 6 (Larastan) | Zero static analysis errors — enforces type correctness at build time |
| Laravel Pint (PSR-12) | Automated code style enforcement on every commit |
| `declare(strict_types=1)` everywhere | PHP strict mode on all files |
| Exchange rate retry logic | 2 retries on transient HTTP failure before throwing `ExchangeRateException` |
| Status index on `payment_requests` | `status` column indexed — list + filter queries stay fast as the table grows |
| 5 Architecture Decision Records | Documents the *why* behind key choices, not just the *what* |
| Dedicated `scheduler` Docker container | No host cron required — the scheduler runs inside the Compose stack |

---

## Code Conventions

- All files: `declare(strict_types=1)`
- Naming: PascalCase classes, camelCase methods, snake_case columns
- Commits: Conventional Commits (`feat:`, `fix:`, `security:`, `docs:`, etc.)
- Branch strategy: feature branches + PRs — no direct commits to `main` or `develop`
- PHP minimum: 8.2 — use `readonly`, named arguments, match expressions, and native enums where appropriate
