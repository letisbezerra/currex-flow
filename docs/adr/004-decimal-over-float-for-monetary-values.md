# ADR-004: Decimal over Float for Monetary Values

## Status
Accepted

## Context
Monetary values (payment amounts, exchange rates, EUR equivalents) require exact arithmetic. IEEE 754 floating-point types (`float`, `double`) cannot represent many decimal fractions exactly — `0.1 + 0.2` yields `0.30000000000000004` in PHP, which is unacceptable for financial data.

## Decision
Use `DECIMAL` columns in MySQL and the `decimal:N` Eloquent cast in PHP for all monetary fields.

```php
$table->decimal('amount', 15, 2);         // monetary amounts
$table->decimal('exchange_rate', 15, 6);  // exchange rates
$table->decimal('amount_in_eur', 15, 2);  // converted amounts
```

```php
protected function casts(): array
{
    return [
        'amount'       => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_in_eur' => 'decimal:2',
    ];
}
```

Eloquent's `decimal:N` cast returns values as strings in PHP, preserving precision across the full request/response cycle.

## Consequences

**Pros:**
- Exact decimal representation — no floating-point rounding errors
- MySQL `DECIMAL` stores exact values; arithmetic is done in base 10
- The API response always returns `"500.00"` not `500.0000000000001`

**Cons:**
- Values are PHP strings, not native floats — arithmetic requires `bcmath` or explicit casting
- Slightly more verbose column definitions (`15, 2` vs `double`)

**Why not `bcmath` everywhere?**
`bcmath` is the right tool for arbitrary-precision arithmetic. For this API, we do one division (`amount / rate`) in `CreatePaymentRequestAction` via `round()`, which is acceptable. If the domain grows to complex financial calculations, `bcmath` should replace `round()`.
