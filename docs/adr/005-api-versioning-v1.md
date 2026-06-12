# ADR-005: URL-based API Versioning under /api/v1

## Status
Accepted

## Context
APIs evolve. Breaking changes — renamed fields, removed endpoints, changed status codes — are unavoidable as requirements grow. Without a versioning strategy, any change risks breaking existing clients.

## Decision
Use URL path versioning with the prefix `/api/v1/`.

All routes are defined in `routes/api.php` under a `v1` prefix group:

```php
Route::prefix('v1')->group(function () {
    // all endpoints
});
```

## Consequences

**Pros:**
- URLs are explicit and self-documenting — a client can see the version at a glance
- Simple to implement in Laravel with a route prefix group
- Easy to add `v2` alongside `v1` without breaking existing clients
- Works naturally with HTTP caching and reverse proxies

**Cons:**
- Purists argue the URL should identify resources, not versions; header-based versioning (`Accept: application/vnd.api+json; version=1`) is more RESTful
- Slightly longer URLs

**Why not header-based versioning?**
Header versioning is harder to test in browsers, curl, and most API clients. For a payment API where developers are the primary consumers, URL versioning provides a better developer experience with no meaningful trade-off in correctness.
