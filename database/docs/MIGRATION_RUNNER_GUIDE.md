# Migration Runner Guide

## Environment Strategy

Supported environments:

- `development`
- `testing`
- `staging`
- `production`

Environment files are stored in `database/config/environments`.
Runtime environment is selected via `APP_ENV` or `--env`.

## Migration Review Model

The migration runner reads `database/migrations/migration_plan.php` and executes domains in planned order:

- Identity
- Company
- Workforce
- Patrol
- Security
- AI
- Reports
- Notifications
- Audit

Core object-driven migrations now include:

- Contracts and site-contract links
- Posts
- Patrol routes and checkpoints
- Shift assignments and attendance linkage

The runner validates and prints the canonical `database/schema/000_run_all.sql` dependency order. It does not change a database. Database execution must follow the reviewed staging and release procedure in `docs/OPERATIONS_RELEASE_RUNBOOK.md`.

## Review the Schema Plan

Validate the full plan:

```powershell
./database/migrations/run_migrations.ps1
```

Target environment:

```powershell
./database/migrations/run_migrations.ps1 -Env staging
```

Run a single domain:

```powershell
./database/migrations/run_migrations.ps1 -Domain Identity
```

Run to a specific migration:

```powershell
./database/migrations/run_migrations.ps1 -Until 014_attendance.sql
```

Dry run:

```powershell
./database/migrations/run_migrations.ps1 -DryRun
```

Rollback is intentionally rejected. Use a tested backup restore or a reviewed forward migration.

## Review Seed Data

Validate the production seed plan:

```powershell
./database/seed/run_seeds.ps1 -Profile production
```

Development defaults plus demo data:

```powershell
./database/seed/run_seeds.ps1 -Profile development
```

Dry run seeds:

```powershell
./database/seed/run_seeds.ps1 -Profile development -DryRun
```

## Validate Schema

```powershell
php database/tools/validate_schema.php
```

Use this validation in CI to block non-compliant schema changes.
