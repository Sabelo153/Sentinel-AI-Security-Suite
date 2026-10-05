# API Security Hardening

## Route Security Matrix

The executable route security matrix is `ApplicationTest::testAllManagementApiRoutesRequireAuthenticationTenantAndPermission`. It inspects the registered router metadata after application bootstrap.

The only public API routes are:

| Method | Route | Reason |
| --- | --- | --- |
| GET | `/api/v1/status` | Health/status probe |
| POST | `/api/v1/auth/login` | Establishes authentication |

All other management routes require effective authentication, tenant context, and a nonempty permission. Router permission metadata implies authentication, and tenant routes are ordered as authentication, tenant resolution, authorization, then handler.

## Tenant Boundary

The active root application uses `company_id` as its only tenant discriminator. `RuntimeContext` receives the company from server-side user state after authentication. Request body, query, headers, and JWT tenant claims do not select the effective tenant. Tenant-owned repositories use explicit `company_id` predicates.

## Input and Response Controls

JSON bodies are limited by `MAX_JSON_BODY_BYTES` (default: 1 MiB), reject explicit non-JSON content types, and reject malformed JSON. Request-facing services validate explicit field allowlists. User responses use a safe projection; RBAC and telemetry repositories use explicit column projections rather than `SELECT *`.

## Pagination

Most established list routes retain their existing unpaginated array response contract in this phase to avoid a breaking API-wide envelope change. GPS location history accepts only allowlisted filters and an optional `limit` from 1 to 500. Before large operational history is exposed, approve a common paginated response envelope and add it consistently to list endpoints.

## Browser and Cross-Origin Policy

Every application response now includes `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, and `Referrer-Policy: no-referrer`. The current API emits no CORS headers and is therefore same-origin by default. `CORS_ALLOWED_ORIGINS` is empty in `.env.example`; an explicit environment-driven CORS middleware must be approved before enabling cross-origin clients. Never combine a wildcard origin with credentials.

## Brute-Force Protection

Login lockout and failed-login tracking are the active immediate brute-force defenses. No production rate-limit store or distributed limiter is currently available. Introduce rate limiting at the edge gateway or through an approved shared cache-backed middleware before public internet deployment; do not use an in-memory per-process limiter for production.