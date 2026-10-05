# Schema Validation Checklist

Use this checklist in local reviews and CI before merging schema changes.

## Migration Integrity

- Migration files are sequentially numbered with no gaps (`001_...`, `002_...`, ...).
- Migration filenames use lowercase `snake_case`.
- Each migration has one clear responsibility.
- `000_run_all.sql` references the current migration list in order.

## Relational Integrity

- Every foreign key references an existing table.
- Foreign key columns have explicit indexes.
- No circular dependency blocks migration order.
- Tenant-bound tables include `company_id`.

## Operational Consistency

- All business tables include `created_at` and `updated_at`.
- Business-critical tables include `created_by` and `updated_by`.
- Deletable entities include `deleted_at` (soft delete).
- All status fields use controlled enums.
- Uniqueness constraints enforce tenant isolation.
- Soft-deletable entities use status states instead of hard deletes where applicable.

## Naming Conventions

- Table names are plural and `snake_case`.
- Primary key pattern is `<entity>_id`.
- Do not use `id` as a standalone primary key name.
- Foreign key names are explicit (`fk_<source>_<target>`).
- Index names follow `idx_...` and unique keys follow `uq_...`.

## API-Safe Identity

- Public-facing entities include UUID columns (for example `employee_uuid`).
- API payloads expose UUID values, not internal numeric IDs.

## Event and Reference Standards

- Event-producing tables include `event_id` and related event metadata where applicable.
- Human-friendly reference numbers are used for operational workflows (for example incident references).

## Security and Multi-Tenant Checks

- Query paths can filter by `company_id` without deep joins.
- User, role, and permission assignments cannot leak across tenants.
- Audit log records include `company_id` and actor (`user_id`) where available.
- AI requests, jobs, and responses are all tenant-scoped.

## Automation

- Run `php database/tools/validate_schema.php` in CI.
- Run migration dry-run on a clean database.
- Run seed scripts and verify idempotent behavior.
- Block merge when validation fails.
