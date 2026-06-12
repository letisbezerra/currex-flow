# ADR-003: Interface for Exchange Rate Service

## Status
Accepted

## Context
The application fetches live exchange rates from a third-party provider to process payment requests. The provider may change, the endpoint format may evolve, or a caching layer may be introduced. The core domain logic (creating payment requests) must not be coupled to any specific HTTP implementation.

## Decision
Define `ExchangeRateServiceInterface` as the contract, implement it in `ExchangeRateApiService`, and bind both in Laravel's IoC container.

## Consequences

**Pros:**
- Follows Dependency Inversion Principle (SOLID D) — `CreatePaymentRequestAction` depends on the abstraction, never on the concrete class
- Swapping providers (e.g., Open Exchange Rates, ECB) requires only a new implementation class and a one-line change in `AppServiceProvider`
- Unit tests use `Http::fake()` to test the service in isolation without hitting the real API
- The interface makes the contract explicit and self-documenting

**Cons:**
- Slight boilerplate compared to direct class injection

**Why `bind` and not `singleton`?**
Exchange rates change constantly. Binding without singleton ensures each request fetches a fresh rate rather than serving a stale one cached in the container for the lifetime of the process.
