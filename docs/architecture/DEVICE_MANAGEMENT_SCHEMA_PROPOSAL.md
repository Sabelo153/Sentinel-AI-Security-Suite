# Device Management Schema Proposal

**Status:** APPROVED - canonical SQL implemented in `053_devices.sql` through `055_device_sessions.sql`
**Date:** 2026-09-11
**Tenant key:** `company_id`

## Decision

A device is a company-owned operational asset. It is not a guard, employee, assignment, or shift. A device may be assigned to an employee for a bounded period and may be used during one or more valid employee assignments and shifts.

The canonical relationship is:

```text
companies
  -> devices
  -> device_assignments -> employees / employee_assignments
  -> device_sessions -> shift_assignments / sites
```

## Meaning of Existing Columns

The existing `guard_device_sessions` table is ambiguous and has no foreign keys. Its columns must not be used as canonical relationships:

- `guard_id`: legacy, unresolved identity. Do not map it to `user_id` or `employee_id` implicitly.
- `assignment_id`: legacy, unresolved assignment type. Do not assume it means `employee_assignment_id`.
- `shift_id`: legacy, unresolved shift type. Do not assume it means `shift_assignment_id`.
- `device_id`: legacy free-text identifier. Do not treat it as a foreign key.

The table should remain deferred until a separately approved compatibility migration maps or archives its records.

## Proposed `devices` Table

```sql
CREATE TABLE devices (
    device_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    device_name VARCHAR(120) NOT NULL,
    device_type VARCHAR(50) NOT NULL DEFAULT 'ANDROID',
    platform VARCHAR(30) NOT NULL DEFAULT 'ANDROID',
    device_identifier VARCHAR(255) NOT NULL,
    device_status ENUM('REGISTERED', 'ACTIVE', 'SUSPENDED', 'LOST', 'RETIRED') NOT NULL DEFAULT 'REGISTERED',
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_devices_company FOREIGN KEY (company_id)
        REFERENCES companies(company_id) ON DELETE CASCADE,
    UNIQUE KEY uq_devices_uuid (device_uuid),
    UNIQUE KEY uq_devices_company_identifier (company_id, device_identifier),
    INDEX ix_devices_company_status (company_id, device_status),
    INDEX ix_devices_company_last_seen (company_id, last_seen_at)
);
```

## Proposed `device_assignments` Table

This table records device custody and ownership history. It does not overwrite prior assignments.

```sql
CREATE TABLE device_assignments (
    device_assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    device_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    employee_assignment_id BIGINT UNSIGNED NULL,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NULL,
    assignment_status ENUM('PLANNED', 'ACTIVE', 'ENDED', 'REVOKED') NOT NULL DEFAULT 'PLANNED',
    assigned_by_user_id BIGINT UNSIGNED NULL,
    returned_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_device_assignments_company FOREIGN KEY (company_id)
        REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_device_assignments_device FOREIGN KEY (device_id)
        REFERENCES devices(device_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_assignments_employee FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_assignments_employee_assignment FOREIGN KEY (employee_assignment_id)
        REFERENCES employee_assignments(employee_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_assignments_assigned_by FOREIGN KEY (assigned_by_user_id)
        REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX ix_device_assignments_company_status (company_id, assignment_status),
    INDEX ix_device_assignments_company_device (company_id, device_id),
    INDEX ix_device_assignments_company_employee (company_id, employee_id),
    INDEX ix_device_assignments_period (starts_at, ends_at)
);
```

The service must validate that `employee_assignment_id`, when supplied, belongs to the same company and employee. It must reject overlapping active custody for the same device unless an explicit handover closes the prior assignment.

## Proposed `device_sessions` Table

A session is an authenticated period of device use. It records operational context, not identity ownership.

```sql
CREATE TABLE device_sessions (
    device_session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    device_id BIGINT UNSIGNED NOT NULL,
    device_assignment_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    employee_assignment_id BIGINT UNSIGNED NULL,
    shift_assignment_id BIGINT UNSIGNED NULL,
    site_id BIGINT UNSIGNED NULL,
    session_status ENUM('ACTIVE', 'ENDED', 'EXPIRED', 'REVOKED') NOT NULL DEFAULT 'ACTIVE',
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    last_heartbeat_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_device_sessions_company FOREIGN KEY (company_id)
        REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_device_sessions_device FOREIGN KEY (device_id)
        REFERENCES devices(device_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_sessions_device_assignment FOREIGN KEY (device_assignment_id)
        REFERENCES device_assignments(device_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_sessions_employee FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_sessions_employee_assignment FOREIGN KEY (employee_assignment_id)
        REFERENCES employee_assignments(employee_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_sessions_shift FOREIGN KEY (shift_assignment_id)
        REFERENCES shift_assignments(shift_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_sessions_site FOREIGN KEY (site_id)
        REFERENCES sites(site_id) ON DELETE SET NULL,
    INDEX ix_device_sessions_company_status (company_id, session_status),
    INDEX ix_device_sessions_company_device (company_id, device_id),
    INDEX ix_device_sessions_company_employee (company_id, employee_id),
    INDEX ix_device_sessions_last_heartbeat (company_id, last_heartbeat_at)
);
```

## Tenant and Security Rules

1. Every table carries `company_id` and every query scopes through the runtime company context.
2. A request cannot supply or override `company_id`.
3. A device, employee, assignment, shift, and site must all belong to the effective company.
4. A session can start only for an active device, active device assignment, active employee, and compatible assignment/shift context.
5. Device identifiers are not authentication secrets and must not be returned as bearer credentials.
6. Registration, assignment, session start/end, suspension, loss, retirement, and revocation are audited.
7. Historical device assignments and sessions are retained; deletion is restricted or soft-retained.
8. No `guard_id` foreign key is introduced. A guard remains an employee/domain role and may be resolved through employee assignment data.

## Approval Gates

Before adding canonical SQL or production code, review:

- whether `device_identifier` should be encrypted, hashed, or both;
- whether one device may have multiple active custodians during handover;
- whether shift context is mandatory for sessions;
- whether site context is captured at session start or derived from active deployment;
- retention and purge policy for sessions and telemetry;
- compatibility handling for existing `guard_device_sessions` rows.

## Approved Implementation Notes

The canonical tables are now registered in `database/schema/000_run_all.sql`. `024_guard_device_sessions.sql` remains a legacy compatibility artifact and is not a source for new relationships or application code. A separately approved data inventory and migration plan is required before mapping, archiving, or dropping its ambiguous rows. Device assignments and sessions retain history; destructive lifecycle operations are not implied by this schema.
