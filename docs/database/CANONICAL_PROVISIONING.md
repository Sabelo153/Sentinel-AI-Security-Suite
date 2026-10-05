# Canonical Database Provisioning

**Status:** Current provisioning policy
**Canonical source:** `database/schema/`
**Canonical runner:** `database/schema/000_run_all.sql`
**Legacy source:** `database/migrations/`

## Fresh Installation

1. Create an empty MySQL 8 or MariaDB database using a dedicated provisioning account.
2. Execute `database/schema/000_run_all.sql` in order.
3. Apply approved seed scripts only after all referenced tables exist.
4. Verify foreign keys, indexes, and expected schema objects on the disposable target.
5. Configure the application with the same database name and `company_id`-based schema.

The schema runner is the only approved fresh-installation path. It currently includes every SQL file in `database/schema/`, including schemas `046` through `052`.

## Canonical Rules

- `company_id` is the only physical tenant discriminator.
- Canonical primary keys use entity-specific names such as `user_id`, `site_id`, and `gps_location_id`.
- Do not execute historical migration files as an alternative fresh-installation path.
- Do not add `tenant_id` or generic `id` columns to canonical tables to satisfy legacy code.
- Validate provisioning against a disposable database before any environment migration.

## Legacy Migration System

`database/migrations/` contains historical design intent and an older migration runner. It is not the canonical provisioning source. In particular, migrations `007_guard_devices_domain.sql` and `008_device_presence_telemetry.sql` use unresolved `tenant_id`, generic primary keys, and foreign-key assumptions that do not match the approved schema.

Those files must not be run against a canonical database. Their device and telemetry concepts are preserved as design input in [DEVICE_MANAGEMENT_SCHEMA_PROPOSAL.md](../architecture/DEVICE_MANAGEMENT_SCHEMA_PROPOSAL.md), not copied as executable schema.

## Upgrade Policy

Future schema changes must:

1. Add a numbered canonical SQL file.
2. Declare dependencies and required execution position.
3. Use `company_id` and canonical key names.
4. Include foreign keys and indexes in the same reviewed change.
5. Add isolated schema/repository tests.
6. Update `000_run_all.sql`.
7. Validate on a disposable database before release.
8. Document rollback or forward-fix behavior.

No legacy migration file may be removed or rewritten until its historical data and upgrade impact have been reviewed separately.
