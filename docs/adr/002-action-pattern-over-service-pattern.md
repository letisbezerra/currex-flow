# ADR-002: Action Pattern over Service Pattern

## Status
Accepted

## Context
The application requires several distinct business operations: creating a payment request, approving it, and rejecting it. The classic approach is a `PaymentService` class with multiple methods (`create`, `approve`, `reject`). As the application grows, this class accumulates methods, dependencies, and responsibilities — a "God Class" that violates SRP.

## Decision
Use the **Action Pattern**: one class per operation, each with a single `__invoke()` method.

```
app/Actions/Payment/
├── CreatePaymentRequestAction.php
├── ApprovePaymentRequestAction.php
└── RejectPaymentRequestAction.php
```

## Consequences

**Pros:**
- Each Action has exactly one reason to change (SRP in practice)
- Each Action is independently testable with minimal setup
- Constructor injection makes dependencies explicit — `CreatePaymentRequestAction` declares it needs `ExchangeRateServiceInterface`; the others do not
- Adding a new operation (e.g., `CancelPaymentRequestAction`) requires creating a new file, not modifying existing ones (Open/Closed Principle)
- Controllers stay thin — they receive a validated request, call one Action, return a Resource

**Cons:**
- More files than a single service class
- Requires discipline to keep Actions focused and avoid logic creep

**Why not a Service?**
A `PaymentService` with `create`, `approve`, and `reject` would need to inject `ExchangeRateServiceInterface` even when approving — a dependency it does not use. With Actions, each class declares only what it actually needs.

**Note on `ExchangeRateApiService` naming:**
This service is retained with the "Service" naming because it is an infrastructure concern (HTTP client integration), not a business operation. The Action Pattern applies to domain operations only.
