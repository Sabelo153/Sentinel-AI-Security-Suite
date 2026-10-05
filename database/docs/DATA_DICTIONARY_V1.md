# Data Dictionary V1 (Identity & Access + Core Platform)

## Core Tenant Tables

- `companies`: Tenant owner record for each security company using Sentinel.
- `clients`: Business customers served by a company.
- `contracts`: Time-bound commercial agreements that define service scope and billing context.
- `branches`: Optional regional client structure.
- `sites`: Operational locations where security work occurs.
- `posts`: Guard operating positions within a site (gate, reception, warehouse, parking, etc.).

## Identity Tables

- `people`: Tenant-scoped master person profile.
- `employees`: Employment-specific profile linked to `people`.
- `users`: Authentication account linked one-to-one to employee.
- `roles`: Tenant-defined authorization roles.
- `permissions`: Permission catalog (global or tenant custom).
- `user_roles`: Role assignments for users.
- `role_permissions`: Permission bundles attached to roles.
- `login_history`: Authentication event history.

## Operations Foundation

- `departments`: Organizational units used for allocation and reporting.
- `shift_assignments`: Employee-to-shift-to-post planning and execution context.
- `attendance`: Clock-in/out and attendance outcomes by employee and date.
- `company_settings`: Per-tenant product behavior and preferences.

## Patrol Foundation

- `patrol_routes`: Planned patrol journeys scoped to site/post.
- `patrol_checkpoints`: Ordered checkpoints for route completion and compliance tracking.

## AI Foundation

- `ai_requests`: Incoming AI work requests.
- `ai_jobs`: Queue and processing lifecycle for AI execution.
- `ai_responses`: Final AI outputs and approval metadata.

## Compliance and Audit

- `audit_logs`: Immutable event trail for business-critical actions.

## Common Column Standards

- Primary key: `BIGINT UNSIGNED AUTO_INCREMENT`.
- Multi-tenant key: `company_id` on tenant-bound tables.
- Audit columns: `created_at`, `updated_at`.
- State columns: status enums for lifecycle controls.
