# Company Test Plan

Status: In progress. Tenant isolation baseline verified at 9/9 through `scripts/verify_tenant_isolation_matrix.ps1`.

## Purpose
Verify that company hierarchy and tenant isolation are enforced across the platform.

## Scope
- Company CRUD
- Branch CRUD
- Site CRUD
- Department CRUD
- Tenant scoping by `company_id`

## Functional Tests
- Create a company and retrieve it by its own scope.
- Create branches under a company.
- Create sites under a branch.
- Create departments under a company or branch scope as defined by the model.
- Company list endpoints return only the current tenant's records.

## Edge Cases
- Cross-company lookup returns no data or a forbidden response.
- Invalid parent references are rejected.
- Deleted records do not appear in normal list endpoints.
- Duplicate tenant-scoped identifiers are rejected.

## Security Tests
- Company A cannot access Company B records.
- One tenant cannot infer another tenant's existence through error messages.
- Tenant-scoped filters are applied at repository level and API level.

## Acceptance Criteria
- Every company-scoped record includes `company_id`.
- Hierarchy relationships are enforced by foreign keys or validated constraints.
- Isolation is verified with real tenant data.

## Definition of Done
- Cross-tenant access tests pass.
- CRUD flows work with seeded tenant data.