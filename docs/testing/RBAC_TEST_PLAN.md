# RBAC Test Plan

Last verified: 11/11 tests passed on 2026-08-05.

## Purpose
Verify that Sentinel authorizes actions by permission, not by hard-coded role checks.

## Scope
- Route-level permission enforcement
- Role assignment
- Permission assignment
- Authorization middleware
- Protected business routes

## Functional Tests
- System Administrator can create a company.
- Company Administrator can view users.
- Company Administrator can assign roles.
- Client User can view clients.
- Client User cannot create clients.
- Client User cannot view users.
- Auditor can view companies.
- Auditor cannot create companies.
- Security Officer cannot create users.
- Permission assignment changes effective access without code changes.
- Middleware blocks requests without the required permission.

## Edge Cases
- Role swaps take effect on the next authenticated request.
- Restoring the original role set restores access.
- Multi-role assignments preserve access across all granted permissions.

## Security Tests
- Client users are blocked from user administration endpoints.
- Auditors are blocked from write operations.
- Security officers are blocked from user creation.
- Unauthorized route access returns a consistent error response.

## Acceptance Criteria
- Permission checks are consistent across route middleware and role mappings.
- Role names do not control access by themselves.
- Protected business routes are covered by integration tests.

## Definition of Done
- All permission checks pass under automated tests.
- No route depends on a hard-coded role name for authorization.