# Enterprise Standards Gate (Sprint 0 Phase 2)

- [ ] Table name is plural and snake_case.
- [ ] Primary key uses `<entity>_id` format.
- [ ] Tenant-bound table includes `company_id`.
- [ ] `created_at` and `updated_at` are present.
- [ ] `created_by` and `updated_by` included for business-critical entities.
- [ ] `deleted_at` present where soft delete is required.
- [ ] Foreign keys have explicit indexes.
- [ ] Public entity includes UUID column for API exposure.
- [ ] Status enum is used instead of multiple lifecycle booleans.
- [ ] Migration includes clear business justification in comments/docs.

# Database Migration Execution Checklist

**Project**: Sentinel AI Security Suite - Phase 1 Implementation
**Date**: July 8, 2024
**Migrations**: 6 files totaling 52+ tables
**Estimated Time**: 2-3 hours (including verification)

---

## Pre-Migration Setup (30 minutes)

### Database Server Check
- [ ] MySQL 8.0+ or MariaDB 10.3+ installed
- [ ] Server is running and accessible
- [ ] Verify connection:
  ```bash
  mysql -h localhost -u root -p -e "SELECT VERSION();"
  ```
- [ ] Record version: _______________

### Create Target Database
- [ ] Execute: `CREATE DATABASE sentinel_ai;`
- [ ] Verify: `SHOW DATABASES;` (should list sentinel_ai)
- [ ] Record creation time: _______________

### Verify User Permissions
- [ ] User has `CREATE TABLE` privilege
- [ ] User has `ALTER TABLE` privilege  
- [ ] User has `INSERT` privilege
- [ ] User has `INDEX` privilege
- [ ] Test with:
  ```bash
  mysql -u sentinel_user -p sentinel_ai -e "SHOW TABLES;"
  ```

### Backup Strategy
- [ ] Backup location identified: _______________
- [ ] Backup method documented: _______________
- [ ] Practice restore tested: Yes / No
- [ ] Responsible person identified: _______________

### File Verification
- [ ] Locate migration files in: `C:\Development\Sentinel-AI-Security-Suite\database\migrations\`
- [ ] [ ] `001_identity_and_access.sql` (size: ~50 KB)
- [ ] [ ] `002_seed_identity_access.sql` (size: ~15 KB)
- [ ] [ ] `003_workflow_engine.sql` (size: ~60 KB)
- [ ] [ ] `004_event_system.sql` (size: ~70 KB)
- [ ] [ ] `005_rule_engine.sql` (size: ~80 KB)
- [ ] [ ] `006_attendance_domain.sql` (size: ~100 KB)
- [ ] File integrity verified (checksums match)
- [ ] All files readable by database user

---

## Pre-Migration Communication (15 minutes)

- [ ] Notify team of migration window
- [ ] Notify operations if applicable
- [ ] Confirm no active connections to sentinel_ai database
- [ ] Alert: "Database will be inaccessible for ~2-3 hours"
- [ ] Record notification time: _______________
- [ ] Notify stakeholders: _______________

---

## Migration Execution (60-90 minutes)

### Migration 1: Identity & Access Domain
**File**: `001_identity_and_access.sql`
**Expected Time**: 10-15 seconds
**Tables**: 12 (users, roles, permissions, companies, branches, sites, etc.)

```bash
# Step 1: Connect and select database
mysql -h localhost -u root -p -e "USE sentinel_ai;"

# Step 2: Execute migration
SOURCE /path/to/001_identity_and_access.sql;

# Step 3: Verify
SELECT COUNT(*) as table_count FROM information_schema.tables 
WHERE table_schema = 'sentinel_ai';
```

- [ ] Connected to sentinel_ai database
- [ ] Migration executed without errors
- [ ] Start time: _______________
- [ ] End time: _______________
- [ ] Tables created (check for 12): _______________
- [ ] Sample verification:
  ```sql
  DESCRIBE users;
  SELECT COUNT(*) FROM permissions;
  -- Should show 30+ permissions pre-inserted
  ```
- [ ] Verification passed: Yes / No
- [ ] **Status**: ✅ COMPLETE

### Migration 2: Seed Data
**File**: `002_seed_identity_access.sql`
**Expected Time**: 5-10 seconds
**Inserts**: Demo company, 6 roles, 3 demo users, 3 demo sites

```bash
SOURCE /path/to/002_seed_identity_access.sql;
```

- [ ] Connected to sentinel_ai database
- [ ] Migration executed without errors
- [ ] Start time: _______________
- [ ] End time: _______________
- [ ] Verify seed data:
  ```sql
  SELECT company_name FROM companies;
  -- Should show "Sentinel Demo Company"
  
  SELECT COUNT(*) FROM users;
  -- Should show 3+ users
  
  SELECT role_name FROM roles WHERE is_system_role = TRUE;
  -- Should show 6 system roles
  ```
- [ ] Demo company verified: _______________
- [ ] Demo users verified: _______________
- [ ] Demo roles verified: _______________
- [ ] **Status**: ✅ COMPLETE

### Migration 3: Workflow Engine
**File**: `003_workflow_engine.sql`
**Expected Time**: 15-20 seconds
**Tables**: 8 (workflow_definitions, workflow_executions, etc.)

```bash
SOURCE /path/to/003_workflow_engine.sql;
```

- [ ] Connected to sentinel_ai database
- [ ] Migration executed without errors
- [ ] Start time: _______________
- [ ] End time: _______________
- [ ] Tables created (check for 8): _______________
- [ ] Verify workflow tables:
  ```sql
  SHOW TABLES LIKE 'workflow%';
  -- Should show 8 workflow-related tables
  
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'sentinel_ai' AND TABLE_NAME LIKE 'workflow%';
  -- Should show 100+ columns across workflow tables
  ```
- [ ] Workflow structure verified: Yes / No
- [ ] **Status**: ✅ COMPLETE

### Migration 4: Event System
**File**: `004_event_system.sql`
**Expected Time**: 20-30 seconds
**Tables**: 8 (domain_events, event_handlers, event_subscriptions, etc.)

```bash
SOURCE /path/to/004_event_system.sql;
```

- [ ] Connected to sentinel_ai database
- [ ] Migration executed without errors
- [ ] Start time: _______________
- [ ] End time: _______________
- [ ] Tables created (check for 8): _______________
- [ ] Verify event tables:
  ```sql
  SHOW TABLES LIKE 'event%';
  -- Should show 8 event-related tables
  
  DESCRIBE domain_events;
  -- Should show event_name, event_type, aggregate_type, etc.
  ```
- [ ] Event Bus structure verified: Yes / No
- [ ] **Status**: ✅ COMPLETE

### Migration 5: Rule Engine
**File**: `005_rule_engine.sql`
**Expected Time**: 25-35 seconds
**Tables**: 8 (rules, rule_conditions, rule_actions, etc.)

```bash
SOURCE /path/to/005_rule_engine.sql;
```

- [ ] Connected to sentinel_ai database
- [ ] Migration executed without errors
- [ ] Start time: _______________
- [ ] End time: _______________
- [ ] Tables created (check for 8): _______________
- [ ] Verify rule tables:
  ```sql
  SHOW TABLES LIKE 'rule%';
  -- Should show 8 rule-related tables
  
  DESCRIBE rules;
  -- Should show rule_type, evaluation_frequency, priority, etc.
  ```
- [ ] Rule Engine structure verified: Yes / No
- [ ] **Status**: ✅ COMPLETE

### Migration 6: Attendance Domain
**File**: `006_attendance_domain.sql`
**Expected Time**: 20-25 seconds
**Tables**: 8 (shift_types, shifts, shift_assignments, attendance, clock_logs, breaks, overtime, attendance_metrics)

```bash
SOURCE /path/to/006_attendance_domain.sql;
```

- [ ] Connected to sentinel_ai database
- [ ] Migration executed without errors
- [ ] Start time: _______________
- [ ] End time: _______________
- [ ] Tables created (check for 8): _______________
- [ ] Verify attendance tables:
  ```sql
  SHOW TABLES LIKE 'shift%';
  SHOW TABLES LIKE 'attendance%';
  SHOW TABLES LIKE 'clock%';
  SHOW TABLES LIKE 'break%';
  SHOW TABLES LIKE 'overtime%';
  
  DESCRIBE attendance;
  -- Should show clock_in_time, clock_out_time, location tracking, etc.
  ```
- [ ] Attendance domain structure verified: Yes / No
- [ ] **Status**: ✅ COMPLETE

---

## Post-Migration Verification (30-45 minutes)

### Overall Structure Check
```sql
-- Count total tables
SELECT COUNT(*) as total_tables FROM information_schema.tables 
WHERE table_schema = 'sentinel_ai';
```
- [ ] Total tables: _____ (expected: 52+)
- [ ] Matches expectation: Yes / No

### Table Verification
```sql
-- Verify all expected tables exist
SHOW TABLES;
```

List all tables created:
- [ ] users
- [ ] roles
- [ ] permissions
- [ ] role_permissions
- [ ] user_roles
- [ ] user_sessions
- [ ] api_keys
- [ ] login_history
- [ ] password_resets
- [ ] companies
- [ ] branches
- [ ] sites
- [ ] workflow_definitions
- [ ] workflow_steps
- [ ] workflow_executions
- [ ] workflow_step_executions
- [ ] workflow_variables
- [ ] workflow_transitions
- [ ] workflow_audit_log
- [ ] workflow_metrics
- [ ] domain_events
- [ ] event_handlers
- [ ] event_handler_executions
- [ ] event_subscriptions
- [ ] event_dead_letter
- [ ] event_metrics
- [ ] event_replay_log
- [ ] event_correlations
- [ ] rules
- [ ] rule_conditions
- [ ] rule_actions
- [ ] rule_evaluations
- [ ] rule_violations
- [ ] rule_execution_log
- [ ] rule_templates
- [ ] rule_metrics
- [ ] shift_types
- [ ] shifts
- [ ] shift_assignments
- [ ] attendance
- [ ] clock_logs
- [ ] breaks
- [ ] overtime
- [ ] attendance_metrics

**Total tables verified**: _____ / 52

### Foreign Key Verification
```sql
-- Count total foreign keys
SELECT COUNT(*) as fk_count FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sentinel_ai' AND REFERENCED_TABLE_NAME IS NOT NULL;
```
- [ ] Foreign keys created: _____ (expected: 120+)
- [ ] Sample foreign key check:
  ```sql
  SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME 
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = 'sentinel_ai' LIMIT 10;
  ```

### Index Verification
```sql
-- Count total indexes
SELECT COUNT(*) as index_count FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'sentinel_ai' AND INDEX_NAME != 'PRIMARY';
```
- [ ] Indexes created: _____ (expected: 50+)
- [ ] Performance indexes verified: Yes / No

### Multi-Tenancy Check
```sql
-- Verify company_id on domain tables
SELECT COUNT(DISTINCT TABLE_NAME) as tables_with_company_id
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'sentinel_ai' AND COLUMN_NAME = 'company_id';
```
- [ ] Tables with company_id: _____ (expected: 40+)
- [ ] Multi-tenancy isolation verified: Yes / No

### Soft Delete Check
```sql
-- Verify deleted_at on domain tables
SELECT COUNT(DISTINCT TABLE_NAME) as tables_with_deleted_at
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'sentinel_ai' AND COLUMN_NAME = 'deleted_at';
```
- [ ] Tables with deleted_at: _____ (expected: 35+)
- [ ] Soft delete strategy verified: Yes / No

### Demo Data Verification
```sql
-- Verify demo company
SELECT company_code, company_name FROM companies;
```
- [ ] Demo company exists: DEMO-001
- [ ] Demo company name: _______________

```sql
-- Verify demo users
SELECT email, username FROM users WHERE email LIKE '%demo%';
```
- [ ] admin@demo.sentinel-ai.com created: Yes / No
- [ ] manager@demo.sentinel-ai.com created: Yes / No
- [ ] guard@demo.sentinel-ai.com created: Yes / No

```sql
-- Verify system roles
SELECT COUNT(*) FROM roles WHERE is_system_role = TRUE;
```
- [ ] System roles created: _____ (expected: 6)

```sql
-- Verify default permissions
SELECT COUNT(*) FROM permissions;
```
- [ ] Default permissions created: _____ (expected: 30+)

### Constraint Verification
```sql
-- Check constraint validity
SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'sentinel_ai';
```
- [ ] Referential constraints: _____ (expected: 50+)
- [ ] All constraints valid: Yes / No

### Sample Query Performance
```sql
-- Test attendance lookup (should be <100ms)
SELECT * FROM attendance 
WHERE company_id = 1 AND person_id = 1 
LIMIT 10;
```
- [ ] Query execution time: _____ ms (target: <100ms)
- [ ] Query uses index: Yes / No

```sql
-- Test rule evaluation (should be <100ms)
SELECT * FROM rules 
WHERE company_id = 1 AND is_active = TRUE 
ORDER BY priority DESC 
LIMIT 10;
```
- [ ] Query execution time: _____ ms (target: <100ms)
- [ ] Query uses index: Yes / No

### Data Integrity Check
```sql
-- Check for orphaned foreign keys
SELECT * FROM attendance 
WHERE person_id NOT IN (SELECT id FROM people);
```
- [ ] Orphaned records found: _____ (expected: 0)
- [ ] Data integrity verified: Yes / No

---

## Post-Migration Validation (30 minutes)

### Database Statistics
```sql
-- Get database size
SELECT 
    table_schema,
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
FROM information_schema.tables
WHERE table_schema = 'sentinel_ai'
GROUP BY table_schema;
```
- [ ] Database size: _____ MB
- [ ] Size reasonable: Yes / No

### Table Row Counts
```sql
-- View row counts
SELECT 
    table_name,
    table_rows
FROM information_schema.tables
WHERE table_schema = 'sentinel_ai'
ORDER BY table_rows DESC;
```

Document top tables:
- [ ] companies: _____ (expected: 1)
- [ ] permissions: _____ (expected: 30+)
- [ ] shift_types: _____ (expected: 1+)
- [ ] workflow_definitions: _____ (expected: 0+)

### Application Connectivity Test
- [ ] Laravel can connect to database
  ```php
  php artisan tinker
  DB::select('SELECT 1');
  ```
- [ ] Connection successful: Yes / No

- [ ] API can query users table
  ```bash
  curl http://localhost:8000/api/v1/users
  ```
- [ ] API response successful: Yes / No

- [ ] Can login with demo user
  ```bash
  curl -X POST http://localhost:8000/api/v1/auth/login \
    -d "email=admin@demo.sentinel-ai.com&password=PUBLIC_EXAMPLE_CONFIGURE_LOCALLY"
  ```
- [ ] Login successful: Yes / No

### Performance Baseline
- [ ] Run simple queries and record baseline times
  - Clock-in lookup: _____ ms
  - Rule evaluation: _____ ms
  - Permission check: _____ ms
  - Attendance query: _____ ms

---

## Rollback Plan (If Needed)

### Rollback Decision Point
- [ ] All migrations executed without critical errors
- [ ] Verification passed: Yes / No

If verification FAILED:
- [ ] Identify failure cause: _______________
- [ ] Document issue: _______________

### Rollback Steps (If Required)
```sql
-- Option 1: Drop and recreate (Total reset)
DROP DATABASE sentinel_ai;
CREATE DATABASE sentinel_ai;
-- Then re-run migrations

-- Option 2: Restore from backup
-- [Contact DBA: _______________]
```

- [ ] Rollback decision made: Continue / Rollback
- [ ] Rollback executed (if needed): Yes / No
- [ ] Post-rollback verification: Passed / Failed

---

## Post-Migration Cleanup (15 minutes)

### Documentation
- [ ] Update DEPLOYMENT_LOG.md with migration execution details
- [ ] Document any issues encountered: _______________
- [ ] Update team wiki with database info

### Backup
- [ ] Create post-migration backup
- [ ] Backup location: _______________
- [ ] Backup timestamp: _______________
- [ ] Backup verified (test restore): Yes / No

### Communication
- [ ] Notify team: "Migration complete and verified"
- [ ] Update status page: _______________
- [ ] Document success metrics
  - Total time: _____ minutes
  - Tables created: 52+
  - Foreign keys: 120+
  - Indexes: 50+
  - Demo data: Complete

### Team Handoff
- [ ] Share migration results with team
- [ ] Provide demo credentials:
  - Email: admin@demo.sentinel-ai.com
  - Username: admin_demo
  - Demo Company: DEMO-001
- [ ] Share documentation:
  - MIGRATIONS_GUIDE.md
  - SCHEMA.md
  - RELATIONSHIPS.md

---

## Sign-Off

- [ ] All migrations executed successfully
- [ ] All verification tests passed
- [ ] Database ready for application deployment
- [ ] Backup created and verified
- [ ] Team notified

**Migration Execution Summary**
```
Total Time: _____ hours _____ minutes
Total Tables Created: 52+
Total Indexes: 50+
Total Constraints: 120+
Status: ✅ COMPLETE
Critical Issues: None / [Describe: _______________]
Known Limitations: None / [Describe: _______________]
```

**Executed By**: _______________________________
**Date & Time**: _______________________________
**Verified By**: _______________________________

---

## Next Steps

After successful migration:

1. **[ ] Execute remaining domain migrations** (007-012)
   - Incidents & Security Operations
   - Visitors
   - Vehicles/Fleet
   - Patrols
   - Assets
   - Fire Safety & Reporting
   - AI Intelligence

2. **[ ] Develop Laravel Service Layer** (1-2 weeks)
   - Authentication service
   - Authorization service
   - Event emitter
   - Rule evaluator

3. **[ ] Create API Endpoints** (1 week)
   - /api/v1/auth/*
   - /api/v1/users/*
   - /api/v1/attendance/*

4. **[ ] Build Dashboard** (2 weeks)
   - Login page
   - Admin dashboard

5. **[ ] Comprehensive Testing** (1 week)
   - Unit tests
   - Integration tests
   - Performance tests

**Estimated Phase 1 Completion**: _______________
