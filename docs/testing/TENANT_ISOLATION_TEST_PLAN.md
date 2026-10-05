# Tenant Isolation Test Plan

Last verified: 9/9 tests passed on 2026-08-05.

## Purpose
Prove that tenant boundaries are enforced for reads, writes, and relationship mapping across company-scoped APIs.

## Scope
- Tenant context middleware
- Company endpoint scoping
- Client, branch, department, and site tenant ownership validation
- Cross-tenant role assignment protection
- Company settings tenant scoping

## Functional Tests
- Tenant company endpoint resolves to authenticated company.
- Client listing never returns foreign tenant records.
- Client creation ignores spoofed company identifiers and uses authenticated tenant.
- Branch creation rejects foreign-tenant client references.
- Department creation rejects foreign-tenant branch references.
- Site creation rejects foreign-tenant client/branch references.
- Role assignment rejects foreign-tenant role IDs.
- Company settings read and update remain tenant-scoped.

## Edge Cases
- Request query includes a foreign `company_id` but result stays tenant-scoped.
- Write payload includes foreign `company_id` but persisted records stay tenant-scoped.
- Relationship IDs from another tenant are rejected with forbidden responses.

## Security Tests
- Company A cannot view Company B clients.
- Company A cannot attach Company B role IDs to its users.
- Company A cannot create branch/department/site records against Company B references.

## Acceptance Criteria
- Tenant context is resolved for every protected business route.
- Service-level ownership checks block cross-tenant references.
- Test matrix passes consistently on seeded development data.

## Definition of Done
- Tenant isolation matrix passes at 100%.
- No business endpoint trusts tenant IDs from request query/body.