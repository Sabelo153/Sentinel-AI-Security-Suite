# Database Naming Standards & Conventions

**Consistency, Clarity, and Maintainability**

---

## 📋 Table Naming

### Rules
- ✅ Plural form: `users` not `user`
- ✅ Lowercase: `incident_photos` not `IncidentPhotos`
- ✅ Snake_case: `employee_training` not `employeeTraining`
- ✅ Descriptive: `shift_assignments` not `sa_records`
- ✅ Max 64 characters (MySQL limit)

### Examples

| Correct | Incorrect | Reason |
|---------|-----------|--------|
| users | user | Use plural |
| incident_photos | incidentPhotos | Use snake_case |
| employee_emergency_contacts | employee_contacts | Be specific |
| shift_assignments | ShiftAssignment | Lowercase, plural |
| clock_logs | clock_log | Plural even if many per day |
| role_permissions | role_permission | Plural |

### Domain-Based Naming

Each domain should have consistent prefix or clear grouping:

**Authentication Domain**:
- users
- roles
- permissions
- user_roles
- role_permissions
- api_keys

**Workforce Domain**:
- employees
- employee_qualifications
- employee_training
- employment_types
- employment_contracts

**Attendance Domain**:
- shifts
- shift_assignments
- attendance
- clock_logs
- breaks
- overtime
- leave_types
- leave_requests
- leave_approvals

---

## 🔑 Column Naming

### Primary Keys
- Format: `id` (for most tables)
- Type: BIGINT UNSIGNED
- Pattern: `{table_name}_id` when referenced elsewhere

```sql
-- In table
id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT

-- When referenced
user_id BIGINT UNSIGNED NOT NULL
```

### Foreign Keys
- Format: `{table}_id`
- Always reference primary key of parent table
- Include type and constraints

```sql
-- Good
user_id BIGINT UNSIGNED NOT NULL
company_id BIGINT UNSIGNED NOT NULL
incident_id BIGINT UNSIGNED COMMENT 'FK to incidents'

-- Bad
userid BIGINT UNSIGNED
compId BIGINT
incident BIGINT
```

### Boolean Columns
- Prefix: `is_`, `has_`, `can_`, `should_`
- Type: BOOLEAN (alias for TINYINT(1))
- Default: FALSE for most cases

```sql
-- Good
is_active BOOLEAN DEFAULT TRUE
is_verified BOOLEAN DEFAULT FALSE
has_children BOOLEAN DEFAULT FALSE
can_approve_leave BOOLEAN DEFAULT FALSE
requires_investigation BOOLEAN DEFAULT FALSE

-- Bad
active TINYINT(1)
verified BIT
approved INT
status_active TINYINT(1)
```

### Date/Time Columns
- Format: `{action}_at` for timestamps
- Format: `{action}_date` for dates
- Format: `{action}_time` for times

```sql
-- Timestamps (with timezone awareness)
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
deleted_at TIMESTAMP NULL  -- For soft deletes
hired_at TIMESTAMP
last_login TIMESTAMP
verified_at TIMESTAMP NULL

-- Dates (just date, no time)
hire_date DATE
birth_date DATE
incident_date DATE
leave_start_date DATE

-- Times (just time, no date)
shift_start_time TIME
shift_end_time TIME
scheduled_start_time TIME
```

### Amount/Money Columns
- Type: DECIMAL(12, 2) or DECIMAL(10, 2)
- Always include decimals
- Include currency field if needed

```sql
-- Good
salary DECIMAL(10, 2)
monthly_cost DECIMAL(12, 2)
overtime_amount DECIMAL(10, 2)
currency CHAR(3) DEFAULT 'ZAR'

-- Bad
salary INT
cost FLOAT
overtime_rate DOUBLE
```

### Text/String Columns
- Type: VARCHAR(length) for fixed-max text
- Type: TEXT for variable-length content
- Type: LONGTEXT for very large content

```sql
-- VARCHAR: Fixed maximum length
username VARCHAR(255)
email VARCHAR(255)
phone_number VARCHAR(20)
employee_number VARCHAR(50)

-- TEXT: Variable length, up to 65KB
description TEXT
notes TEXT
comments TEXT
reason TEXT

-- LONGTEXT: Very large, up to 4GB
incident_description LONGTEXT
investigation_findings LONGTEXT
ai_summary LONGTEXT
```

### Status/Enum Columns
- Use ENUM for fixed values
- Don't use 0/1 or integers
- Use descriptive names

```sql
-- Good
status ENUM('Active', 'Inactive', 'Archived', 'Suspended')
employment_type ENUM('Full-time', 'Part-time', 'Contract', 'Casual')
incident_severity ENUM('Critical', 'High', 'Medium', 'Low', 'Info')

-- Bad
status TINYINT(1)  -- 0/1 unclear
state VARCHAR(50)  -- Can be inconsistent spelling
type INT
```

### Location Coordinates
- Latitude: DECIMAL(10, 8)
- Longitude: DECIMAL(11, 8)
- Units: Degrees (not radians)

```sql
-- GPS Coordinates
gps_latitude DECIMAL(10, 8)      -- -90 to +90
gps_longitude DECIMAL(11, 8)     -- -180 to +180
gps_accuracy_meters INT           -- Accuracy radius

-- Can verify with:
CHECK (gps_latitude BETWEEN -90 AND 90)
CHECK (gps_longitude BETWEEN -180 AND 180)
```

### JSON Columns
- Use for arrays or objects
- Add COMMENT explaining structure
- Keep schema flexible

```sql
-- Good
visited_areas JSON COMMENT '["Building A", "Warehouse"]'
topics_covered JSON COMMENT '["CPR", "First Aid", "De-escalation"]'
properties JSON COMMENT '{"color": "blue", "size": "large"}'
permissions_json JSON COMMENT '[{"module": "incidents", "action": "read"}]'

-- Bad
tags VARCHAR(255)  -- Use JSON instead
attributes TEXT    -- Use structured JSON
metadata LONGTEXT
```

---

## 🔗 Common Column Names (Use Consistently)

### User/Person References
```sql
user_id             -- Person's user account
person_id           -- Person's master record
employee_id         -- Employee record
created_by          -- User who created
updated_by          -- User who updated
deleted_by          -- User who deleted
approved_by         -- User who approved
verified_by         -- User who verified
assigned_by         -- User who assigned
assigned_to         -- User who is assigned to
reported_by         -- User who reported
reviewed_by         -- User who reviewed
```

### Time Tracking
```sql
created_at          -- When record created
updated_at          -- When record updated
deleted_at          -- When soft-deleted
verified_at         -- When verified
approved_at         -- When approved
assigned_at         -- When assigned
resolved_at         -- When resolved
completed_at        -- When completed
started_at          -- When started
ended_at            -- When ended
```

### Status/State
```sql
status              -- Main status field
verification_status -- Verification state
approval_status     -- Approval state
payment_status      -- Payment state
```

### Counts/Metrics
```sql
total_count         -- Total items
current_count       -- Current items
remaining_count     -- Remaining items
duration_minutes    -- Duration in minutes
duration_seconds    -- Duration in seconds
distance_km         -- Distance in kilometers
height_cm           -- Height in centimeters
weight_kg           -- Weight in kilograms
```

### Identifiers
```sql
code                -- Internal code/reference
reference_number    -- Reference number
document_number     -- Document reference
serial_number       -- Serial/product number
batch_number        -- Batch identifier
transaction_id      -- Transaction identifier
request_id          -- Request identifier
correlation_id      -- Correlation identifier
```

---

## 🏷️ Comment Field Standards

Every column should have a comment for clarity:

```sql
-- Full example
CREATE TABLE incidents (
    incident_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT
        COMMENT 'Unique incident identifier',
    
    company_id BIGINT UNSIGNED NOT NULL
        COMMENT 'Company owning this incident - for multi-tenancy',
    
    reported_by BIGINT UNSIGNED NOT NULL
        COMMENT 'Employee who reported incident (FK to users)',
    
    severity_level ENUM('Critical', 'High', 'Medium', 'Low')
        COMMENT 'Severity: Critical=immediate action, Low=monitor',
    
    police_involved BOOLEAN DEFAULT FALSE
        COMMENT 'Whether police were involved',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        COMMENT 'When incident was created in system'
);
```

### Comment Content Checklist
- ✅ What does this field contain?
- ✅ What are valid values?
- ✅ Why is it here?
- ✅ Any constraints?
- ✅ Any calculated fields?
- ✅ Foreign key references?

---

## 📇 Index Naming

### Primary Key
```sql
PRIMARY KEY (id)
```

### Foreign Key Index
```sql
INDEX idx_{referenced_table} ({column})

-- Examples
INDEX idx_company (company_id)
INDEX idx_user (user_id)
INDEX idx_incident (incident_id)
```

### Composite Index (for common queries)
```sql
INDEX idx_{table1}_{table2} ({col1}, {col2})

-- Examples
INDEX idx_employee_date (employee_id, attendance_date)
INDEX idx_site_date (site_id, incident_date)
INDEX idx_company_status (company_id, status)
INDEX idx_user_created_at (user_id, created_at)
```

### Unique Constraints
```sql
UNIQUE KEY uniq_{field} ({column})
UNIQUE KEY uniq_{table}_{field} ({col1}, {col2})

-- Examples
UNIQUE KEY uniq_email (email)
UNIQUE KEY uniq_employee_number (company_id, employee_number)
UNIQUE KEY uniq_site_code (branch_id, site_code)
UNIQUE KEY uniq_role_permission (role_id, permission_id)
```

---

## 🚫 What NOT to Do

### ❌ DON'T Use
```sql
-- Single letters/abbreviations
col1, col2, x, y, a, b

-- Mixed case in table names
UserInfo, EmployeeTraining, IncidentPhotos

-- Singular table names
user, employee, role

-- Overly abbreviated
emp_trng, inc_type, comp_settings

-- Special characters
user@id, col-name, field.value

-- Leading underscores
_id, _status, _created_at

-- Spaces or punctuation
"user id", user-id, user.id
```

### ❌ DON'T Denormalize Except For Performance

```sql
-- Good (normalized)
employee_id, shift_id, attendance_date
-- Query joins tables to get full record

-- Only if documented for performance
employee_id, company_id COMMENT 'Denormalized for JOIN performance'
```

### ❌ DON'T Use CURRENT_USER() or CURRENT_TIMESTAMP() as Default
```sql
-- Bad: Can't override during migration/testing
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

-- Better: Application sets value, DB just validates
created_at TIMESTAMP NOT NULL
```

---

## 📋 Naming Examples by Domain

### Authentication
```sql
users              -- User accounts
roles              -- Role definitions
permissions        -- Permission definitions
user_roles         -- User to role mapping
role_permissions   -- Role to permission mapping
api_keys           -- API authentication tokens
```

### Workforce
```sql
employees                    -- Employee records
employee_qualifications      -- Employee certifications
employee_training            -- Employee training records
employment_contracts         -- Contract records
performance_reviews          -- Performance evaluations
payroll_records              -- Salary/payment records
employee_disciplinary        -- Disciplinary actions
```

### Attendance
```sql
shifts                   -- Shift definitions
shift_assignments        -- Employee to shift mapping
attendance               -- Daily attendance records
clock_logs               -- Individual clock in/out events
breaks                   -- Break records
overtime                 -- Overtime tracking
leave_types              -- Leave categorization
leave_requests           -- Leave applications
leave_approvals          -- Leave approval workflow
attendance_exceptions    -- Attendance discrepancies
```

### Incidents
```sql
incidents                    -- Main incident records
incident_types               -- Incident categorization
incident_photos              -- Evidence photos
incident_witnesses           -- Witness information
incident_updates             -- Status updates
incident_actions             -- Corrective actions
incident_responses           -- Emergency responses
incident_escalations         -- Escalation tracking
incident_risk_assessment     -- Risk analysis
incident_tags                -- Flexible tagging
```

---

## 🔧 Data Type Standards

### ID Fields
```sql
id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT
```
*Use consistently across all tables for easy migrations*

### Timestamps
```sql
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
deleted_at TIMESTAMP NULL  -- For soft deletes
```

### Dates
```sql
hire_date DATE
birth_date DATE
incident_date DATE
```

### Times
```sql
start_time TIME
end_time TIME
clock_in_time TIME
```

### Amounts
```sql
salary DECIMAL(10, 2)
cost DECIMAL(12, 2)
```

### Coordinates
```sql
latitude DECIMAL(10, 8)
longitude DECIMAL(11, 8)
```

### Status
```sql
status ENUM('Active', 'Inactive', 'Archived')
```

### Boolean
```sql
is_active BOOLEAN DEFAULT FALSE
```

### Text
```sql
short_text VARCHAR(255)
medium_text TEXT
long_text LONGTEXT
```

### JSON
```sql
settings JSON
tags JSON
```

---

## ✅ Validation Checklist

Before naming database objects, verify:

- [ ] Table names are plural
- [ ] All names are lowercase
- [ ] Snake_case used throughout
- [ ] No abbreviations (except standard: id, pk, fk)
- [ ] Column comments added
- [ ] Foreign key pattern: `{table}_id`
- [ ] Boolean pattern: `is_`, `has_`, `can_`
- [ ] Timestamp pattern: `{action}_at`
- [ ] Date pattern: `{action}_date`
- [ ] Time pattern: `{action}_time`
- [ ] ENUM for fixed values
- [ ] DECIMAL for money
- [ ] Indexes named: `idx_` or `uniq_`
- [ ] No single-letter names
- [ ] Max 64 characters per table
- [ ] Max 64 characters per column

---

**Status**: Naming Standards Complete  
**Consistency**: Enterprise Grade ✨  
**Maintainability**: Maximized

All developers should follow these standards for Sentinel AI database.
