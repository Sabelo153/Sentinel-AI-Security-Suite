# Client Test Plan

## Purpose
Verify that clients and sites are managed correctly before security operations are added.

## Scope
- Client CRUD
- Site CRUD
- Client-to-site assignment
- Site-to-company isolation

## Functional Tests
- Create a client under the active company.
- Create one or more sites for that client.
- Retrieve client sites by tenant scope.
- Update client and site metadata.

## Edge Cases
- Duplicate client names within a company are handled according to the data model.
- Invalid site references are rejected.
- Disabled clients do not appear in active assignment flows.

## Security Tests
- One tenant cannot view another tenant's clients or sites.
- Site assignment endpoints require the correct permission.
- Client data cannot be injected through unvalidated fields.

## Acceptance Criteria
- Client and site records are tenant-aware.
- Site assignment flows are usable for later patrol and attendance modules.

## Definition of Done
- Client and site CRUD tests pass.
- Cross-tenant access is blocked.