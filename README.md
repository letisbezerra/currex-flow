# CurrEx Flow

Multi-currency payment request service built with Laravel 12. Employees across different countries submit payment requests in their local currency; the API fetches the live EUR exchange rate at submission time and stores it immutably with the request. Finance team members can then approve or reject pending requests.

---

## Tech Stack

| | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 12 |
| MySQL | 8.0 |
| Redis | 7 |
| Nginx | Alpine |
| Laravel Sanctum | token auth |
| dedoc/scramble | OpenAPI 3.1 docs |

---

## Requirements

- Docker
- Docker Compose

> No local PHP or Composer installation required. Tests also run inside Docker.

---

## Local Setup

```bash
# 1. Clone
git clone https://github.com/letisbezerra/currex-flow.git
cd currex-flow

# 2. Configure environment
cp .env.example .env
```

Edit `.env` and fill in the required values:

```env
APP_KEY=                        # generated in step 5
DB_PASSWORD=secret              # any local password
DB_ROOT_PASSWORD=secret
EXCHANGE_RATE_API_KEY=          # free key at exchangerate-api.com
```

> Free API key: [exchangerate-api.com](https://www.exchangerate-api.com)

```bash
# 3. Start containers
docker compose up -d

# 4. Install dependencies
docker compose exec app composer install

# 5. Generate app key
docker compose exec app php artisan key:generate

# 6. Run migrations and seed demo users
docker compose exec app php artisan migrate --seed
```

API available at **http://localhost:8000**  
Interactive docs at **http://localhost:8000/docs/api**

---

## Seed Users

| Name | Email | Password | Currency | Role |
|------|-------|----------|----------|------|
| Ana Lima | ana@currex.dev | password | BRL | employee |
| James Smith | james@currex.dev | password | GBP | employee |
| Yuki Tanaka | yuki@currex.dev | password | JPY | employee |
| Priya Patel | priya@currex.dev | password | INR | employee |
| Lucas Dupont | lucas@currex.dev | password | CAD | employee |
| Maria Santos | maria@currex.dev | password | EUR | **finance** |

> `maria@currex.dev` is the only user who can approve or reject payment requests.

---

## API Endpoints

Base URL: `http://localhost:8000/api/v1`

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/auth/register` | — | Register a new employee |
| `POST` | `/auth/login` | — | Login and receive Bearer token |
| `POST` | `/auth/logout` | ✓ | Revoke current token |

### Payment Requests

| Method | Endpoint | Auth | Role | Description |
|--------|----------|------|------|-------------|
| `GET` | `/payment-requests` | ✓ | any | List requests (employees see own only). Filter: `?status=pending\|approved\|rejected\|expired` |
| `POST` | `/payment-requests` | ✓ | any | Create request — exchange rate fetched automatically from user's currency |
| `GET` | `/payment-requests/{id}` | ✓ | any | Get request details (employees can only view their own) |
| `PATCH` | `/payment-requests/{id}/status` | ✓ | finance | Approve or reject a pending request |

### Error Responses

| Code | Meaning |
|------|---------|
| 401 | Missing or invalid Bearer token |
| 403 | Authenticated but not authorized for this action |
| 404 | Resource not found |
| 409 | Conflict — e.g. approving an already-approved request |
| 422 | Validation failed — body includes `errors` object |
| 503 | Exchange rate service unavailable |

---

## Usage Examples

### Register and login

```bash
# Register
curl -s -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@example.com","password":"Password1!","password_confirmation":"Password1!","country":"Brazil","currency_code":"BRL"}'

# Login
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ana@currex.dev","password":"password"}'
```

### Create a payment request

The currency is taken from the authenticated user's profile — no need to send it in the body.

```bash
curl -s -X POST http://localhost:8000/api/v1/payment-requests \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"amount": 1500, "description": "Office supplies"}'
```

```json
{
  "data": {
    "id": 1,
    "amount": "1500.00",
    "currency_code": "BRL",
    "amount_in_eur": "276.61",
    "description": "Office supplies",
    "status": "pending",
    "exchange_rate": "5.423100",
    "exchange_rate_source": "exchangerate-api.com",
    "exchange_rate_fetched_at": "2026-06-11T22:00:00Z",
    "expires_at": "2026-06-13T22:00:00Z",
    "created_at": "2026-06-11T22:00:00Z"
  }
}
```

> The exchange rate is fetched once at creation and stored permanently. Subsequent reads always return the rate as it was at submission time — the EUR equivalent never drifts.

### Approve a payment request (finance only)

```bash
curl -s -X PATCH http://localhost:8000/api/v1/payment-requests/1/status \
  -H "Authorization: Bearer {finance_token}" \
  -H "Content-Type: application/json" \
  -d '{"status": "approved"}'
```

---

## Architecture

```
app/
├── Actions/          # One class per business operation (SRP)
├── Console/Commands/ # Artisan commands (ExpirePaymentRequests)
├── Contracts/        # Interfaces for dependency inversion
├── DTOs/             # Typed readonly value objects (PHP 8.2)
├── Enums/            # PaymentStatus, UserRole
├── Exceptions/       # Domain exceptions (ExchangeRateException)
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/   # ForceJsonResponse, SecurityHeaders
│   ├── Requests/     # Input validation (FormRequest)
│   └── Resources/    # JSON output shaping
├── Models/
├── Policies/         # Authorization rules (PaymentRequestPolicy)
└── Services/         # External integrations (ExchangeRateApiService)
```

### Design Decisions

Key architectural choices are documented as Architecture Decision Records (ADRs) in [`docs/adr/`](docs/adr/). Each decision is recorded with full context and trade-offs; the rationale is summarized below.

| ADR | Decision | Why |
|-----|----------|-----|
| [ADR-001](docs/adr/001-sanctum-over-passport.md) | Sanctum over Passport | Passport is OAuth2 server infrastructure — overkill for a first-party API. Sanctum provides the same token-based auth for SPA and mobile clients with no added complexity. |
| [ADR-002](docs/adr/002-action-pattern-over-service-pattern.md) | Action pattern over God Service | A single `PaymentService` grows unbounded and violates SRP. One class per operation (`CreatePaymentRequestAction`, `ApprovePaymentRequestAction`, `RejectPaymentRequestAction`) keeps each path independently readable, testable, and changeable. |
| [ADR-003](docs/adr/003-interface-for-exchange-rate-service.md) | Interface for exchange rate provider | `ExchangeRateServiceInterface` decouples the domain from any specific third-party API. Tests inject a mock that satisfies the contract; the live provider can be swapped without touching business logic. |
| [ADR-004](docs/adr/004-decimal-over-float-for-monetary-values.md) | `DECIMAL` over `float` for monetary values | IEEE 754 cannot represent most decimal fractions exactly — `0.1 + 0.2` yields `0.30000000000000004` in PHP. Financial values stored as `DECIMAL(15,2)` and cast as PHP strings eliminate rounding errors across the full request/response cycle. |
| [ADR-005](docs/adr/005-api-versioning-v1.md) | URL versioning under `/api/v1` | URL versioning is explicit, cache-friendly, and works in every HTTP client without custom headers. Header-based versioning (`Accept: application/vnd.api+json; version=1`) is harder to test in browsers and standard API clients. |

---

## Security

This is a payment API — security was treated as a first-class concern throughout, following the [OWASP API Security Top 10](https://owasp.org/API-Security/).

| Layer | Measure |
|-------|---------|
| **Headers** | `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer`, `Content-Security-Policy: default-src 'none'` on every response |
| **Rate limiting** | 60 req/min API-wide; 6 req/min on auth endpoints (anti-brute-force) |
| **CORS** | Wildcard in development; restricted to `CORS_ALLOWED_ORIGINS` env variable in production |
| **Debug mode** | `APP_DEBUG=false` in `.env.example` — no stack traces leak in production |
| **Token revocation** | `POST /auth/logout` deletes the current token server-side; abandoned tokens cannot be reused |
| **Authorization** | `PaymentRequestPolicy` enforces ownership — employees can only read their own requests; only `finance` role can approve/reject |
| **Consistent JSON** | `ForceJsonResponse` middleware ensures the API never returns an HTML error page regardless of the `Accept` header |

---

## Tests

**43 tests — 106 assertions.** All tests run inside Docker against an in-memory SQLite database.

```bash
docker compose exec app php artisan test
```

Coverage includes: authentication flows, payment request lifecycle (create → approve/reject), authorization boundaries (employee vs. finance), validation errors, 48h expiry command, exchange rate failure handling, and pagination.

---

## Scheduled Tasks

Payment requests that remain `pending` for more than 48 hours are automatically set to `expired`. The scheduler runs inside a dedicated `scheduler` Docker container — no external cron configuration required.

```bash
# View scheduled tasks
docker compose exec app php artisan schedule:list

# Run manually
docker compose exec app php artisan payments:expire-pending
```

---

## Useful Commands

```bash
# Run all tests
docker compose exec app php artisan test

# Static analysis (PHPStan level 6)
docker compose exec app ./vendor/bin/phpstan analyse

# Code style check/fix (Laravel Pint / PSR-12)
docker compose exec app ./vendor/bin/pint

# Open API documentation
open http://localhost:8000/docs/api
```

---

## License

MIT
