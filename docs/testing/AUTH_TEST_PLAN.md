# Authentication Test Plan

Last verified: 27/27 tests passed on 2026-08-05.

## Purpose
Verify that Sentinel authentication is secure, complete, and production-ready.

## Scope
- Login
- Logout
- JWT generation and validation
- Refresh tokens
- Password reset and password change
- Account lockout
- Current user lookup
- Login history

## Functional Tests
- Valid login returns an access token and refresh token.
- Invalid password returns a denial without exposing account existence.
- Invalid email returns a generic authentication failure.
- Locked account cannot authenticate.
- Disabled account cannot authenticate.
- Expired JWT is rejected.
- Refresh token can mint a new access token.
- Logout invalidates the active session or token pair.
- `/me` returns the current user when a valid token is supplied.

## Edge Cases
- Missing Authorization header.
- Malformed JWT.
- Reused refresh token.
- Password reset token reuse.
- SQL injection strings in email and password fields.

## Security Tests
- Brute-force protection after repeated failures.
- Timing consistency between valid and invalid credentials.
- Token tampering is rejected.
- Sensitive fields are never returned in responses.

## Acceptance Criteria
- All endpoints return stable, documented error codes.
- Login history records successful and failed attempts.
- Lockout behavior is deterministic and reversible by an admin or reset flow.

## Definition of Done
- All functional, edge-case, and security tests pass.
- Endpoint behavior is documented.
- No test relies on fake identity data.