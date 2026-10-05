# Device Management Runtime

## Canonical Tables

Device management uses only `devices`, `device_assignments`, and `device_sessions` from schema files `053` through `055`. The runtime does not read from or write to `guard_device_sessions`.

## Tenant Boundary

All repository reads and writes derive `company_id` from `RuntimeContext`. Device, employee, employee-assignment, shift-assignment, site, device-assignment, and device-session lookups use the same company predicate. Request payloads cannot supply `company_id`.

## Lifecycle Rules

- Devices may be `REGISTERED`, `ACTIVE`, `SUSPENDED`, `LOST`, or `RETIRED`; a retired device cannot be reactivated through the management API.
- A device can have only one active custody assignment with no end time.
- Active custody must reference a same-company employee and, when supplied, an employee assignment belonging to that employee.
- Sessions require an active device. A provided device assignment must be active, unended, and match the device and employee.
- Provided employee assignment, shift assignment, and site context must match the employee and each other.
- A device can have only one active session. Sessions transition from `ACTIVE` to `ENDED`, `EXPIRED`, or `REVOKED` through the explicit end operation and cannot be reopened.
- Starting or ending a session updates the device last-seen timestamp.

## Audit

Successful device creation/update, custody assignment/update/end, and session start/end write best-effort records through `AuditService`. The records contain no device credentials or authentication material.

## Legacy 024

`024_guard_device_sessions.sql` remains compatibility-only. Its `guard_id`, `assignment_id`, `shift_id`, and free-text device identifier are never used by the canonical runtime. Mapping, archive, or removal requires a separate data-inventory and migration approval.