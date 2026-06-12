# ADR-001: Sanctum over Passport

## Status
Accepted

## Context
The application requires token-based authentication for a REST API used by internal employees. The choice was between Laravel Sanctum and Laravel Passport, both official Laravel packages.

## Decision
Use **Laravel Sanctum** for authentication.

## Consequences

**Pros:**
- Sanctum is the official Laravel recommendation for simple API token authentication (SPA, mobile, first-party APIs)
- Significantly simpler setup — no OAuth2 server infrastructure needed
- Tokens are stored in the database and can be easily revoked
- Ships as a first-party Laravel package with minimal configuration overhead
- Sufficient for the use case: authenticated employees performing actions on their own data

**Cons:**
- Does not support OAuth2 flows (authorization code, client credentials, etc.)
- Not suitable if third-party applications need to authenticate on behalf of users

**Why not Passport:**
Passport implements the full OAuth2 specification, which is required only when third-party authorization is needed (e.g., "Login with Google", external client apps requesting delegated access). For an internal API where the only consumers are first-party clients, Passport adds complexity — an OAuth2 server, multiple grant types, client management — without any benefit. Over-engineering authentication is a known source of security vulnerabilities (OWASP A07: Identification and Authentication Failures).
