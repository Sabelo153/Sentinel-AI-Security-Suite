# Dashboard Test Plan

## Purpose
Verify that the dashboard shows live operational data instead of fake widgets.

## Scope
- Guards on duty
- Open incidents
- Active sites
- Expired PSIRA registrations
- Employees expiring within 30 days
- Clock-ins today

## Functional Tests
- Dashboard numbers reflect seeded or live database values.
- Counts update when underlying records change.
- Empty states render cleanly when no data exists.
- Dashboard respects tenant scope.

## Edge Cases
- No active employees returns zero counts, not errors.
- Partial data does not break summary cards.
- Time-bound counts use the correct timezone.

## Security Tests
- Users without dashboard permission cannot access summary data.
- Tenant data cannot leak across companies.
- Sensitive raw records are not exposed in summary responses.

## Acceptance Criteria
- Every widget is backed by a real query or service.
- Dashboard data is consistent with other modules.
- Tenant scoping is enforced on every metric.

## Definition of Done
- Dashboard metrics pass integration tests.
- No placeholder or fake values remain in production paths.