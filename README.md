# Currex Flow

Multi-currency payment request service built with Laravel 12. Employees across different countries can submit payment requests in their local currency, with real-time EUR exchange rate conversion and finance team approval workflow.

---

## Tech Stack

- **PHP** 8.2+
- **Laravel** 12
- **MySQL** 8.0
- **Redis** 7 (rate limiting)
- **Docker** + Docker Compose
- **Laravel Sanctum** (authentication)

---

## Requirements

- Docker
- Docker Compose

> No local PHP or Composer installation required.

---

## Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/letisbezerra/currex-flow.git
cd currex-flow
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` and set your exchange rate API key:

```env
EXCHANGE_RATE_API_KEY=your_key_here
```

> Free API key available at [exchangerate-api.com](https://www.exchangerate-api.com)

### 3. Start the containers

```bash
docker compose up -d
```

### 4. Install dependencies

```bash
docker compose exec app composer install
```

### 5. Generate application key

```bash
docker compose exec app php artisan key:generate
```

### 6. Run migrations and seeders

```bash
docker compose exec app php artisan migrate --seed
```

The application will be available at **http://localhost:8000**

---

## Seed Users

The database is seeded with the following users for testing:

| Name | Email | Password | Currency | Role |
|------|-------|----------|----------|------|
| Ana Lima | ana@currex.dev | password | BRL | employee |
| James Smith | james@currex.dev | password | GBP | employee |
| Yuki Tanaka | yuki@currex.dev | password | JPY | employee |
| Priya Patel | priya@currex.dev | password | INR | employee |
| Lucas Dupont | lucas@currex.dev | password | CAD | employee |
| Maria Santos | maria@currex.dev | password | EUR | finance |

> `maria@currex.dev` has the **finance** role and can approve or reject payment requests.

---

## API Endpoints

Base URL: `http://localhost:8000/api/v1`

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/auth/register` | — | Register a new user |
| `POST` | `/auth/login` | — | Login and receive Bearer token |
| `POST` | `/auth/logout` | ✓ | Revoke current token |

### Payment Requests

| Method | Endpoint | Auth | Role | Description |
|--------|----------|------|------|-------------|
| `GET` | `/payment-requests` | ✓ | any | List requests. Filter: `?status=pending` |
| `POST` | `/payment-requests` | ✓ | any | Create request (exchange rate fetched automatically) |
| `GET` | `/payment-requests/{id}` | ✓ | any | Get request details |
| `PATCH` | `/payment-requests/{id}/status` | ✓ | finance | Approve or reject a pending request |

### Example: Create a payment request

**Request**
```http
POST /api/v1/payment-requests
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 1500.00,
  "currency_code": "BRL",
  "description": "Office supplies reimbursement"
}
```

**Response** `201 Created`
```json
{
  "data": {
    "id": 1,
    "amount": "1500.00",
    "currency_code": "BRL",
    "description": "Office supplies reimbursement",
    "status": "pending",
    "exchange_rate": "5.423100",
    "exchange_rate_source": "exchangerate-api.com",
    "exchange_rate_fetched_at": "2026-06-11T22:00:00Z",
    "amount_in_eur": "276.61",
    "expires_at": "2026-06-13T22:00:00Z",
    "created_at": "2026-06-11T22:00:00Z"
  }
}
```

### Example: Approve a payment request

**Request**
```http
PATCH /api/v1/payment-requests/1/status
Authorization: Bearer {finance_token}
Content-Type: application/json

{
  "status": "approved"
}
```

**Response** `200 OK`
```json
{
  "data": {
    "id": 1,
    "status": "approved",
    "reviewed_by": "Maria Santos",
    "reviewed_at": "2026-06-11T23:00:00Z"
  }
}
```

---

## Running Tests

```bash
docker compose exec app php artisan test
```

---

## Scheduled Tasks

Payment requests that remain `pending` for more than 48 hours are automatically set to `expired`. The scheduler runs inside a dedicated Docker container and requires no external cron configuration.

---

## Project Structure

```
app/
├── Actions/          # Single-responsibility business operations (SRP)
├── Contracts/        # Interfaces for dependency inversion (DIP)
├── DTOs/             # Typed data transfer objects (PHP 8.2 readonly)
├── Enums/            # PaymentStatus, UserRole
├── Exceptions/       # Domain-specific exceptions
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Requests/     # Input validation
│   └── Resources/    # JSON response formatting
├── Models/
├── Policies/         # Authorization rules
└── Services/         # Infrastructure integrations (exchange rate API)
```

---

## License

MIT
