# Employee Test Plan

## Purpose
Verify that employee records are complete, compliant, and linked correctly to platform identity.

## Scope
- Employee CRUD
- Personal details
- Employment details
- Compliance details
- Linked user account
- Status transitions
- Audit history

## Functional Tests
- Create an employee with personal and employment fields.
- Update employee details.
- Link an employee to a user account.
- Deactivate and reactivate an employee.
- Record compliance values such as PSIRA number and expiry.

## Edge Cases
- Missing required identity fields are rejected.
- Invalid date formats are rejected.
- Expiry dates in the past are flagged.
- Duplicate employee numbers are blocked.

## Security Tests
- Users without employee-edit permission cannot modify records.
- Employee records cannot be created outside the active company scope.
- Sensitive documents are access-controlled.

## Acceptance Criteria
- Employee profile supports personal, employment, compliance, and system sections.
- Audit history records create, update, deactivate, and reactivate actions.
- Compliance data can be surfaced to the dashboard later.

## Definition of Done
- CRUD and status transition tests pass.
- Employee-to-user linking works end to end.