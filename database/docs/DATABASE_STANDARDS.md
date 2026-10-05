# Sentinel Database Standards (Enterprise Baseline)

## Rule Zero

No table exists unless it solves a real business problem.

## Naming Convention

- Table names must be plural and `snake_case`.
- Primary keys must follow `<entity>_id` (never `id`).
- Foreign keys must use the referenced parent key name.
- Constraint naming:
- Foreign keys: `fk_<child>_<parent>`
- Indexes: `idx_<table>_<column>`
- Unique constraints: `uq_<table>_<purpose>`

## Required Columns

Every business table must include:

- `created_at`
- `updated_at`
- `created_by` (nullable during bootstrap)
- `updated_by` (nullable during bootstrap)

Soft delete capable entities must include:

- `deleted_at`

## Tenant Isolation

- Tenant-scoped tables must include `company_id`.
- API and query paths must always be filterable by `company_id`.
- Join tables (`user_roles`, `role_permissions`, etc.) remain tenant-scoped.

## Internal ID vs External UUID

- Internal relational joins use `BIGINT UNSIGNED` IDs.
- External API exposure must use UUID columns (for example `employee_uuid`).
- UUID columns must be unique and immutable once assigned.

## Status Modeling

- Prefer a single status enum over multiple lifecycle booleans.
- Recommended status set (adapt per domain):
- `PENDING`, `ACTIVE`, `SUSPENDED`, `ARCHIVED`, `DELETED`

## Indexing Standards

- Every foreign key requires an explicit index.
- Add indexes for tenant filters and high-volume search paths.
- Add composite indexes for common report filters.

## Event Compatibility

Important operational tables should include event traceability fields:

- `event_id`
- `event_type`
- `reference_table`
- `reference_id`

## File Storage Standards

- Never store binary files in database rows.
- Store only metadata:
- `file_path`, `file_hash`, `mime_type`, `file_size`

## Human-Friendly Sentinel References

Use generated reference codes for user-facing operations:

- Incident: `INC-YYYY-NNNNNNN`
- Patrol: `PAT-YYYY-NNNNN`
- Visitor: `VIS-YYYY-NNNNN`
- Attendance: `ATT-YYYY-NNNNN`

## Migration Governance

- One job per migration file.
- Migrations are grouped by domain and executed by plan order.
- All migration changes must pass schema validation in CI.
