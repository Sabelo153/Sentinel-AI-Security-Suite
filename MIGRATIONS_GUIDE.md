# Sentinel AI Security Suite - Database Migrations Guide

## Overview

This guide covers the SQL migrations that implement the Sentinel AI Security Suite database schema. Migrations follow a strict dependency order to build from core infrastructure to domain-specific functionality.

**Total Migrations**: 6 (Phase 1 complete, 7+ planned for remaining domains)
**Total Tables**: 52 (Phase 1), 99 (full system)
**Estimated Execution Time**: 2-3 hours total
**Database**: MySQL 8.0+ / MariaDB 10.3+

---

## Migration Architecture

### Design Principles

1. **Dependency Ordering**: Each migration depends only on previous migrations
2. **Multi-Tenancy**: Every domain table includes `company_id` for strict data isolation
3. **Soft Deletes**: All data marked deleted, never removed (audit trail + compliance)
4. **Audit Trails**: Complete history via `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`
5. **Event-Driven**: Domain events enable cross-module communication without coupling
6. **Rule-Based Intelligence**: Business rules evaluated before AI intervention (<100ms SLA)

### Migration Dependency Tree

```
001_identity_and_access.sql (Foundation)
    ├── 002_seed_identity_access.sql (Demo Data)
    ├── 003_workflow_engine.sql (Orchestration)
    │   └── 004_event_system.sql (Nervous System)
    │       └── 005_rule_engine.sql (Intelligence Engine)
    │           └── 006_attendance_domain.sql (Business Domain)
    │
    └── (Future: 007-012_remaining_domains.sql)
```

---

## Migration Details

### Migration 1: Identity & Access Domain
**File**: `001_identity_and_access.sql`
**Status**: ✅ Complete and Production Ready
**Execution Time**: ~10-15 seconds

#### Tables Created (12)
| Table | Purpose | Key Features |
|-------|---------|--------------|
| `users` | User accounts | 2FA, password_hash, login tracking, email_verified |
| `roles` | Role definitions | System roles, company-scoped, permission_count denormalized |
| `permissions` | Permission catalog | System-wide (not company-scoped), module/action/category breakdown, 30 defaults pre-inserted |
| `role_permissions` | Role→Permission mapping | Many-to-many, granted_by audit |
| `user_roles` | User→Role assignment | Multi-role support, site scoping, valid_from/until for temporary assignments |
| `user_sessions` | Session management | session_token, device tracking, session_type (Web/Mobile/API/Desktop) |
| `api_keys` | API authentication | key_hash, rate_limit, allowed_ips, allowed_origins, scopes |
| `login_history` | Login audit trail | ip_address, device/OS/browser, login_method, failure_reason, suspicious_flag |
| `password_resets` | Password reset tracking | reset_token, expires_at, used_at, ip_comparison |
| `companies` | Company records | registration_number, industry, subscription_plan, max_users/sites |
| `branches` | Branch/office locations | address, GPS coords, is_active |
| `sites` | Physical work sites | site_type, client_info, security_level, max_visitors_per_day, operating_hours |

#### Permissions Pre-Inserted (30)
```
Authentication:    login, logout, change_password, reset_password
Users:             create, read, update, delete, reset_password, assign_role
Roles:             create, read, update, delete
Company:           manage_profile, manage_settings, view_usage
Incidents:         create, read, update, approve, close
Attendance:        clock_in, view_records, manage_records
Reports:           view, create, export, schedule
Admin:             audit_log, system_settings, view_api_keys, manage_api_keys
```

#### Indexes Created (20+)
- Compound: `(company_id, site_code)` - uniqueness check
- Temporal: `(created_at)` - sorting and archiving
- Lookup: All foreign keys indexed for JOIN performance
- Soft delete aware: All indexes filter `deleted_at IS NULL`

#### Constraints
- Foreign key cascades: `ON DELETE CASCADE` for company deletions
- Unique constraints: Email, username, site_code per company
- Check constraints: Soft delete enforcement

---

### Migration 2: Seed Data
**File**: `002_seed_identity_access.sql`
**Status**: ✅ Complete and Production Ready
**Execution Time**: ~5 seconds

#### Bootstrap Data
1. **Demo Company**: "Sentinel Demo Company" (DEMO-001)
   - Industry: Security
   - Subscription: Enterprise
   - Timezone: America/New_York

2. **System Roles** (6 total)
   - Super Admin (all permissions)
   - Administrator (all non-critical)
   - Manager (read/approve/create for incidents, attendance, reports)
   - Supervisor (read/create/update for operations)
   - Security Guard (basic clock-in, incident creation)
   - Operator (monitoring and dispatch)

3. **Branches & Sites**
   - HQ: Headquarters (New York)
   - West Coast: Regional Office (San Francisco)
   - 3 Sites: Downtown Office, Warehouse, Tech Campus

4. **Demo Users**
   - admin@demo.sentinel-ai.com (Super Admin)
   - manager@demo.sentinel-ai.com (Manager at Downtown Office)
   - guard@demo.sentinel-ai.com (Guard at Downtown Office)

5. **API Key** for development integration testing

#### SQL Variables Set
- `@demo_company_id` - Used for branch/site creation
- `@hq_branch_id`, `@west_branch_id` - Branch references
- `@downtown_site_id`, `@warehouse_site_id` - Site references
- `@super_admin_role_id`, `@admin_role_id`, etc. - Role references

#### What Happens Next
After this migration, you can:
- Login with `admin@demo.sentinel-ai.com`
- Assign roles to users
- Create real employees
- Begin clock-in testing

---

### Migration 3: Workflow Engine
**File**: `003_workflow_engine.sql`
**Status**: ✅ Complete and Production Ready
**Execution Time**: ~15-20 seconds

#### Tables Created (8)

| Table | Purpose | Row-count Estimate |
|-------|---------|-------------------|
| `workflow_definitions` | Workflow blueprints | 50-100 (per company) |
| `workflow_steps` | Step configurations | 200-500 (per company) |
| `workflow_executions` | Runtime instances | 1,000,000+ (per year) |
| `workflow_step_executions` | Step tracking | 5,000,000+ (per year) |
| `workflow_variables` | Step variables | 500,000+ (per year) |
| `workflow_transitions` | Valid transitions | 100-300 (per company) |
| `workflow_audit_log` | Change tracking | 10,000+ (per company) |
| `workflow_metrics` | Performance tracking | 50-100 (per workflow) |

#### Step Types Supported (8)
```
start             → Workflow entry point
end               → Workflow exit point
action            → Execute business logic (create incident, send notification, etc)
decision          → Conditional branching (if/then/else)
parallel          → Execute multiple steps simultaneously
wait              → Pause until condition met (timeout, event, etc)
notification      → Send notification to users/roles
escalation        → Escalate to higher authority
```

#### Action Types (8+)
```
create_incident              → Create new incident record
update_entity                → Update any entity
send_notification            → Send alert to users/roles
trigger_workflow             → Trigger another workflow
create_task                  → Create task/todo
escalate_to_manager          → Route to manager
update_status                → Change entity status
apply_penalty                → Record violation
```

#### Error Handling
- Retry logic: `max_retries`, `retry_delay_seconds`
- Timeout: `timeout_seconds` per step
- Next step routing: `next_step_success`, `next_step_failure`
- Execution context preserved in JSON for debugging

#### Example Workflow (Late Arrival)
```
START
  ↓
Decision: Is person >15 min late?
  YES → Create Incident (severity=medium)
        ↓
        Send Notification to Supervisor
        ↓
        Trigger Rule Engine evaluation
  NO  → Log Exception
       ↓
       END
```

---

### Migration 4: Event System (Event Bus - SEB)
**File**: `004_event_system.sql`
**Status**: ✅ Complete and Production Ready
**Execution Time**: ~20-30 seconds

#### Tables Created (8)

| Table | Purpose | Example Events |
|-------|---------|-----------------|
| `domain_events` | Event hub | user_clocked_in, shift_started, visitor_checked_in |
| `event_handlers` | Event subscriptions | On user_clocked_in → trigger workflow |
| `event_handler_executions` | Handler execution tracking | Logged every time a handler fires |
| `event_subscriptions` | Domain subscriptions | Attendance domain subscribes to all incident.* events |
| `event_dead_letter` | Failed events | Event that failed 3x retry attempts |
| `event_metrics` | Performance monitoring | Avg processing time: 45ms |
| `event_replay_log` | Recovery tracking | Replay attendance events from 2 days ago |
| `event_correlations` | Link related events | user_clocked_in → caused_by → shift_started |

#### Event Types (12)
```
attendance              clock_in, clock_out, shift_started, shift_ended
incident                incident_created, incident_updated, approval_requested
visitor                 visitor_checked_in, visitor_checked_out
workforce               employee_hired, employee_terminated, role_changed
security                unauthorized_access, suspicious_activity
patrol                  patrol_started, checkpoint_reached, patrol_completed
fleet                   vehicle_assigned, maintenance_completed
asset                   asset_checked_out, asset_damaged, asset_returned
fire_safety             drill_completed, equipment_inspected
reporting               report_generated, report_scheduled
ai_intelligence         ai_recommendation_generated, model_updated
system                  system_error, configuration_changed
workflow                workflow_started, step_completed, workflow_approved
user                    user_created, user_login, password_changed
```

#### Handler Execution Flow
```
Event Emitted → Event Handler Found
           ↓
      Async Execution (if configured)
           ↓
      Call Webhook / Trigger Workflow / Evaluate Rule
           ↓
      IF Success:
         Record in handler_executions (completed)
      ELSE IF Retry Enabled:
         Retry (max 3 times, with backoff)
      ELSE:
         Move to Dead Letter Queue
```

#### Dead Letter Queue Pattern
```
Failed Event → Dead Letter Queue
             ↓
        (Operations team alerted)
             ↓
        Manual investigation
             ↓
        Retry or archive decision
             ↓
        Logged in event_dead_letter with resolution_notes
```

#### Event Correlation Example
```
Event: user_clocked_in (id=1000)
Related Events:
  - triggered_by: shift_started (id=999)
  - caused_by: api_call (id=1001)
  - cascading: late_arrival_warning (id=1002)
```

---

### Migration 5: Rule Engine (SRE)
**File**: `005_rule_engine.sql`
**Status**: ✅ Complete and Production Ready
**Execution Time**: ~25-35 seconds

#### Tables Created (8)

| Table | Purpose | Row-count Estimate |
|-------|---------|-------------------|
| `rules` | Rule definitions | 100-300 (per company) |
| `rule_conditions` | Condition criteria | 300-1000 (per company) |
| `rule_actions` | Auto-actions | 200-500 (per company) |
| `rule_evaluations` | Evaluation history | 10,000,000+ (per year) |
| `rule_violations` | Violation records | 100,000+ (per year) |
| `rule_execution_log` | Execution audit | 1,000,000+ (per year) |
| `rule_templates` | Pre-built templates | 20-30 (system-wide) |
| `rule_metrics` | Performance aggregates | 100-300 (per company) |

#### Rule Types (6)

1. **Time-Based**: Trigger at specific times
   ```sql
   Example: "Alert if night shift between 10pm-6am AND person clocks in"
   Conditions: time_range (22:00-06:00), event_type = clock_in
   Action: send_notification to security_manager
   ```

2. **Frequency-Based**: Trigger if threshold met in timeframe
   ```sql
   Example: "Alert if person is late >3 times in 30 days"
   Conditions: aggregate_count (late_arrivals in 30 days) >= 3
   Action: create_incident, notify_manager
   ```

3. **Threshold-Based**: Trigger if metric exceeds limit
   ```sql
   Example: "Overtime alert if person >10 hours overtime in week"
   Conditions: aggregate_sum (overtime_hours in 7 days) > 10
   Action: notify_hr, trigger_workflow (overtime_approval)
   ```

4. **Status-Based**: Trigger when entity status changes
   ```sql
   Example: "Alert when person shifts from 'on_leave' to 'present'"
   Conditions: status_changed (from=on_leave, to=present)
   Action: log_event, update_dashboard
   ```

5. **Compound**: Multiple conditions with AND/OR logic
   ```sql
   Example: "Alert if (person is visitor AND (site_level=critical OR time is after_hours))"
   Conditions: 
     person_type = visitor
     AND (site_security_level = 'critical' OR time > 18:00)
   Action: alert_security_team, record_incident
   ```

6. **Sequential**: Must occur in specific order
   ```sql
   Example: "Alert if visitor checksin without supervisor, then tries restricted area"
   Conditions:
     1. visitor_checked_in AND supervisor_present = false
     2. (within 30 mins) visitor_attempts_access (restricted_area)
   Action: block_access, alert_security
   ```

#### Condition Types (10+)
```
time_range                 → Between specific times (08:00-17:00)
day_of_week                → Specific days (Mon, Tue, Wed)
field_comparison           → Compare field to value (status == "present")
field_in_list              → Check if field in list (role IN ['guard', 'operator'])
field_pattern              → Regex match (email LIKE '%@company.com')
aggregate_count            → Count in timeframe (late_arrivals > 3 in 30 days)
aggregate_sum              → Sum in timeframe (overtime_hours > 10 in week)
aggregate_average          → Average in timeframe (avg_hours_worked < 6 in month)
time_since_last_event      → Time elapsed since event (days_since_warning > 7)
custom_expression          → JavaScript/Python expression
```

#### Action Types (8+)
```
create_incident            → Create new incident (type, priority, assignee)
update_entity              → Modify any entity (attendance status, etc)
send_notification          → Alert users/roles
trigger_workflow           → Execute defined workflow
create_task                → Create task/todo
escalate_to_manager        → Route to reporting manager
update_status              → Change entity status
apply_penalty              → Record warning/violation
log_event                  → Emit domain event
webhook                    → POST to external webhook
custom                     → Execute custom code
```

#### Performance SLA
```
Evaluation Time Target: <100ms per rule
Actual Tracking:        Recorded in rule_execution_log.execution_duration_milliseconds
SLA Compliance:         Tracked in rule_metrics.evaluations_within_sla
Percentiles:            p95_evaluation_time_ms, p99_evaluation_time_ms
```

#### Example Rules (Pre-Built)

**Rule 1: Late Arrival Detection**
```
Name:        "Late Arrival Warning"
Type:        Time-based
Trigger:     clock_in event
Condition:   clock_in_time > shift_start_time + 15 minutes
Action:      create_incident (severity=medium)
             notify_manager
Status:      Active
```

**Rule 2: Excessive Overtime**
```
Name:        "Excessive Overtime Alert"
Type:        Frequency-based
Trigger:     Daily (scheduled evaluation)
Condition:   SUM(overtime_hours in 7 days) > 10
Action:      send_notification to HR
             trigger_workflow (overtime_approval)
             create_task (HR_review)
Status:      Active
```

**Rule 3: Unauthorized Access Attempt**
```
Name:        "Restricted Area Access"
Type:        Status-based
Trigger:     access_attempt event
Condition:   access_attempt.security_level = "critical" AND user_role != "admin"
Action:      block_access
             create_incident (severity=critical)
             alert_security_team
Status:      Active, Requires Approval (notify_admin=true)
```

---

### Migration 6: Attendance Domain
**File**: `006_attendance_domain.sql`
**Status**: ✅ Complete and Production Ready
**Execution Time**: ~20-25 seconds

#### Tables Created (8)

| Table | Purpose | Example Data |
|-------|---------|--------------|
| `shift_types` | Shift definitions | Morning (6am-2pm), Night (2pm-10pm), Graveyard (10pm-6am) |
| `shifts` | Shift instances | Monday 2024-07-08 6am-2pm at Downtown Office |
| `shift_assignments` | Staff allocation | John assigned to Monday Morning shift |
| `attendance` | Daily records | John present 8.5 hrs on 2024-07-08 |
| `clock_logs` | Clock events | John clocked in at 5:55am via mobile |
| `breaks` | Break tracking | John took lunch 12:00-1:00pm on-site |
| `overtime` | Overtime records | John worked 2 hrs overtime on 2024-07-08 |
| `attendance_metrics` | Summary stats | July: John 92% attendance, 0 late arrivals |

#### Shift Types Features
```
Definition:
  - start_time / end_time
  - break_minutes_after_hours (after 4 hrs: 15 min break)
  - overtime_starts_after_hours (> 8 hrs)
  - weekend_multiplier (1.5x pay)
  - holiday_multiplier (2.0x pay)
  - night_shift_allowance ($10/night)

Example: Night Shift
  start_time:              14:00
  end_time:                22:00
  duration_hours:          8
  break_minutes_after_hours: 4
  break_duration_minutes:  30
  overtime_starts_after_hours: 8
  night_shift_allowance:   $10
```

#### Clock Methods Supported (6)
```
mobile_app                 → Security guard app on phone
web_portal                 → Web-based kiosk
biometric_fingerprint      → Fingerprint scanner
biometric_facial           → Facial recognition
manual_entry               → Supervisor enters time
api                        → System API call
```

#### Attendance Statuses (8)
```
present                    → Person worked full shift
absent                     → Person didn't show up
late                       → Person arrived >15 min late
early_leave                → Person left before shift end
on_leave                   → Approved leave day
holiday                    → Company holiday
weekend                    → Non-working day
exception                  → Special exception (personal reasons, etc)
```

#### Location Tracking
```
clock_in_location:
  - latitude / longitude (GPS)
  - address (reverse geocoded)
  - location_accuracy_meters (10-50m typical)
  - site_id (matched to registered sites)

Verification:
  - is_verified (manual approval)
  - verification_method (biometric_score > 95)
  - location_on_site (true if within site boundary)
```

#### Break Management
```
Types:
  - mandatory              → Required break (lunch)
  - meal                   → Meal break
  - prayer                 → Religious break
  - rest                   → Rest break
  - other                  → Custom break

Tracking:
  - break_start_time / break_end_time
  - break_duration_minutes (calculated)
  - location tracking (ensure on-site)
  - approval workflow (mandatory breaks require approval)
  - break_status (planned, taken, skipped, denied)
```

#### Overtime Compensation Options
```
Request Flow:
  1. overtime_status = 'requested'
  2. Manager reviews request
  3. overtime_status = 'approved' (or 'denied')
  4. Action executed:
     - compensation_type = 'payment'    → Pay overtime hours
     - compensation_type = 'time_off'   → Give equivalent hours off
     - compensation_type = 'both'       → Half pay + half time off

Tracking:
  - overtime_hours (calculated from clock_out - clock_in - breaks)
  - overtime_multiplier (1.5x, 2.0x, etc based on time/day)
  - paid_amount (if payment option)
  - time_off_hours (if time_off option)
  - paid_at (when compensation processed)
```

#### Integration with Event System
```
Events Emitted:
  - attendance.user_clocked_in
  - attendance.user_clocked_out
  - attendance.break_started
  - attendance.break_ended
  - attendance.shift_started
  - attendance.shift_ended
  - attendance.person_absent
  - attendance.person_late
  - attendance.overtime_requested
  - attendance.attendance_verified

Handlers Triggered:
  ON user_clocked_in:
    → Trigger "Late Arrival" rule
    → Update attendance metrics
    → Send notification if anomaly

  ON person_absent:
    → Create incident
    → Notify manager
    → Trigger workflow (absence_approval)

  ON overtime_requested:
    → Evaluate "Excessive Overtime" rule
    → Trigger workflow (overtime_approval)
```

#### Integration with Rule Engine
```
Example Rules:

1. Late Arrival Detection
   Trigger: attendance.user_clocked_in
   Condition: clock_in_time > shift.start_time + 15 min
   Action: create_incident (severity=medium)

2. Excessive Overtime (Weekly)
   Trigger: Scheduled daily
   Condition: SUM(overtime_hours in 7 days) > 10
   Action: notify_hr, create_task (review_required)

3. No Show Pattern
   Trigger: Daily
   Condition: COUNT(no_show) > 2 in 30 days
   Action: create_incident (severity=high)

4. Unauthorized Break
   Trigger: break_started
   Condition: break_status != 'approved' AND break_type = 'mandatory'
   Action: send_notification (supervisor)
```

#### Attendance Metrics (Monthly/Quarterly/Annual)
```
Calculated:
  - attendance_percentage    = days_present / total_scheduled_days
  - punctuality_percentage   = days_on_time / total_scheduled_days
  - productivity_hours       = total_hours_worked - total_break_hours
  - exceptions_count         = late_arrivals + early_departures + no_shows

Example Output (John, July 2024):
  Total Scheduled Days:      21
  Days Present:              20
  Days Absent:               0
  Days Late:                 1
  Days Early Leave:          0
  Days On Leave:             1
  Attendance Percentage:     95.2%
  Punctuality Percentage:    95.2%
  Total Hours Scheduled:     168
  Total Hours Worked:        167.5
  Total Overtime Hours:      5
  Total Break Hours:         10.5
  Productivity Hours:        157
  Late Arrivals:             1
  Early Departures:          0
```

---

## Pre-Migration Checklist

Before running migrations:

1. **Database Server**
   - [ ] MySQL 8.0+ or MariaDB 10.3+ installed
   - [ ] Server is running
   - [ ] Admin credentials available

2. **Database**
   - [ ] Create database: `CREATE DATABASE sentinel_ai;`
   - [ ] Verify: `SHOW DATABASES;` includes `sentinel_ai`

3. **Permissions**
   - [ ] User has CREATE TABLE privilege
   - [ ] User has ALTER TABLE privilege
   - [ ] User has INSERT DATA privilege
   - [ ] User has INDEX privilege

4. **Backups**
   - [ ] Have backup strategy in place
   - [ ] Can restore if needed

---

## Migration Execution

### Option 1: Sequential Execution (Recommended for First Time)

```bash
# Login to MySQL
mysql -h localhost -u root -p

# Select database
USE sentinel_ai;

# Run each migration in order
SOURCE /path/to/001_identity_and_access.sql;
SOURCE /path/to/002_seed_identity_access.sql;
SOURCE /path/to/003_workflow_engine.sql;
SOURCE /path/to/004_event_system.sql;
SOURCE /path/to/005_rule_engine.sql;
SOURCE /path/to/006_attendance_domain.sql;

# Verify
SHOW TABLES;
SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'sentinel_ai';
```

### Option 2: Single Command

```bash
# Create migration execution script
cat 001_identity_and_access.sql \
    002_seed_identity_access.sql \
    003_workflow_engine.sql \
    004_event_system.sql \
    005_rule_engine.sql \
    006_attendance_domain.sql \
    > all_migrations.sql

# Execute
mysql -h localhost -u root -p sentinel_ai < all_migrations.sql
```

### Option 3: Laravel Migration (Recommended for Production)

```php
// Create Laravel migration wrapper
// database/migrations/2024_07_08_000001_create_sentinel_infrastructure.php

Schema::connection('sentinel')->statement(
    file_get_contents(database_path('migrations/raw/001_identity_and_access.sql'))
);
```

---

## Post-Migration Verification

### Check Table Creation
```sql
-- Count total tables
SELECT COUNT(*) as table_count 
FROM information_schema.tables 
WHERE table_schema = 'sentinel_ai';
-- Expected: 52 (Phase 1)

-- List all tables
SHOW TABLES;

-- Check specific domain
SHOW TABLES LIKE 'attendance%';
SHOW TABLES LIKE 'shift%';
```

### Verify Foreign Keys
```sql
-- Check all foreign keys
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sentinel_ai'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;
-- Expected: 120+ constraints
```

### Test Multi-Tenancy Isolation
```sql
-- Verify company_id on domain tables
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'sentinel_ai'
AND COLUMN_NAME = 'company_id'
ORDER BY TABLE_NAME;
-- Expected: 40+ tables have company_id
```

### Test Seed Data
```sql
-- Verify demo company created
SELECT * FROM companies WHERE company_code = 'DEMO-001';
-- Expected: 1 row

-- Verify demo users
SELECT email, username FROM users WHERE email LIKE '%demo%';
-- Expected: 3 rows (admin, manager, guard)

-- Verify demo roles created
SELECT role_name FROM roles WHERE is_system_role = TRUE;
-- Expected: 6 rows (Super Admin, Administrator, Manager, Supervisor, Guard, Operator)

-- Verify permissions inserted
SELECT COUNT(*) FROM permissions;
-- Expected: 30+ rows
```

### Performance Check
```sql
-- Verify indexes created
SELECT COUNT(*) as index_count
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'sentinel_ai'
AND INDEX_NAME != 'PRIMARY';
-- Expected: 50+ indexes

-- Check large table stats
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    AVG_ROW_LENGTH,
    DATA_LENGTH / 1024 / 1024 as size_mb
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'sentinel_ai'
ORDER BY TABLE_ROWS DESC
LIMIT 10;
```

---

## Troubleshooting

### Error: "Table already exists"
```sql
-- If re-running migrations, drop tables first
DROP DATABASE sentinel_ai;
CREATE DATABASE sentinel_ai;
-- Then re-run migrations
```

### Error: "Foreign key constraint fails"
```sql
-- Verify parent table exists first
SELECT * FROM information_schema.tables 
WHERE table_schema = 'sentinel_ai' 
AND table_name = 'companies';

-- If missing, re-run 001_identity_and_access.sql first
```

### Error: "Column doesn't exist"
```sql
-- Check migration order - ensure 001 before 002, 003, etc.
SHOW COLUMNS FROM attendance;
-- Should see: company_id, person_id, shift_assignment_id, etc.
```

### Error: "Permission denied"
```bash
# Check MySQL user privileges
mysql -h localhost -u root -p
SHOW GRANTS FOR 'sentinel_user'@'localhost';

# Add privileges if needed
GRANT ALL PRIVILEGES ON sentinel_ai.* TO 'sentinel_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Next Steps

After migrations complete:

1. **Execute 007-012 Domain Migrations** (Future)
   - Incident/Security Operations
   - Visitors
   - Vehicles/Fleet
   - Patrols
   - Assets
   - Fire Safety
   - Reporting
   - AI Intelligence

2. **Create Laravel Service Layer**
   - Authentication service
   - Authorization service
   - Event emitter
   - Rule evaluator

3. **Implement API Endpoints**
   - /api/v1/auth/*
   - /api/v1/users/*
   - /api/v1/roles/*
   - /api/v1/attendance/*

4. **Build Dashboard**
   - Login page
   - Admin dashboard
   - Attendance interface

5. **Testing & Hardening**
   - Unit tests
   - Integration tests
   - Performance tests
   - Security tests

---

## Migration Rollback

If you need to roll back:

```sql
-- Drop all tables (dangerous!)
DROP DATABASE sentinel_ai;

-- Or drop specific tables
DROP TABLE IF EXISTS domain_events;
DROP TABLE IF EXISTS workflow_executions;
-- etc...

-- Recreate empty database
CREATE DATABASE sentinel_ai;

-- Re-run migrations
```

---

## Performance Notes

### Estimated Data Growth (Per Company Per Year)
```
attendance_records:        365 × 100 employees = 36,500 rows
clock_logs:                365 × 200 events = 73,000 rows
rule_evaluations:          1,000 per day = 365,000 rows
domain_events:             500 per day = 182,500 rows
workflow_executions:       100 per day = 36,500 rows
Total estimated size:      ~5 GB per company per year
```

### Query Performance Targets
```
Clock-in lookup:           <50ms (indexed by company + person + date)
Rule evaluation:           <100ms (indexed by rule_id)
Attendance query:          <100ms (indexed by company + person + date)
Permission check:          <20ms (loaded in memory at login)
```

### Index Strategy
- Composite indexes on frequent joins: (company_id, entity_id)
- Temporal indexes on created_at for sorting/archiving
- Foreign key indexes for referential integrity
- Unique indexes on constraints

---

## Support & Documentation

- **Architecture Reference**: [ARCHITECTURE.md](../docs/ARCHITECTURE.md)
- **Schema Reference**: [SCHEMA.md](../docs/SCHEMA.md)
- **Naming Standards**: [NAMING_STANDARDS.md](../docs/NAMING_STANDARDS.md)
- **Relationships**: [RELATIONSHIPS.md](../docs/RELATIONSHIPS.md)
