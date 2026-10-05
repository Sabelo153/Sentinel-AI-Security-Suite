# Database ERD Planning - Sprint 1, Task 1

> Comprehensive guide for designing the Sentinel database schema

---

## 🎯 Objective

Design a professional **Entity Relationship Diagram (ERD)** with 40-50 tables, covering all business domains and supporting the entire 6-sprint development roadmap.

**Timeline**: Next session  
**Effort**: 8 hours  
**Deliverables**: 
- Complete ERD diagram
- Database design document
- SQL migration files
- Seed data scripts

---

## 📋 Domains to Model

### 1. Authentication Domain
Manages users, roles, permissions, and access control.

**Tables**:
- `users` - User accounts
- `roles` - User roles (Admin, Manager, Security Officer, Viewer, Guest)
- `permissions` - System permissions
- `role_permissions` - Many-to-many: roles ↔ permissions
- `user_roles` - Many-to-many: users ↔ roles (with site scope)
- `api_keys` - API authentication

### 2. Organization Domain
Company hierarchy and structure.

**Tables**:
- `companies` - Top-level organizations
- `branches` - Company branches
- `sites` - Physical locations/facilities
- `departments` - Organizational departments
- `divisions` - Business divisions (optional)

### 3. Workforce Domain
Employees and their management.

**Tables**:
- `employees` - Employee master records
- `employee_qualifications` - Certifications (PSIRA, etc.)
- `employee_training` - Training records
- `employee_emergency_contacts` - Emergency contacts
- `designations` - Job titles/positions
- `employment_types` - Full-time, part-time, contract

### 4. Attendance Domain
Clock-in/out and shift management.

**Tables**:
- `shifts` - Shift definitions (Morning, Afternoon, Night)
- `shift_assignments` - Employees assigned to shifts
- `attendance` - Daily attendance records
- `clock_logs` - Individual clock in/out entries
- `breaks` - Break records
- `overtime` - Overtime tracking
- `leave_types` - Leave categories
- `leave_requests` - Leave applications
- `leave_approvals` - Leave approval workflow
- `attendance_reports` - Generated reports

### 5. Security Operations Domain
Incident and occurrence tracking.

**Tables**:
- `incidents` - Incident master records
- `incident_types` - Incident categorization
- `incident_updates` - Incident status updates
- `incident_photos` - Evidence photos
- `incident_witnesses` - Witness information
- `occurrence_book` - Daily occurrence log
- `incident_status` - Incident statuses (Open, Closed, etc.)
- `incident_severity` - Severity levels

### 6. Visitor Domain
Visitor management and tracking.

**Tables**:
- `visitors` - Visitor master records
- `visitor_visits` - Visit records (check-in/out)
- `visitor_badges` - Temporary access badges
- `visitor_blacklist` - Blacklisted visitors
- `visit_purposes` - Reason for visit
- `visitor_hosts` - Employee host assignments

### 7. Patrol Domain
Security patrol operations.

**Tables**:
- `patrols` - Patrol assignments
- `patrol_routes` - Predefined patrol routes
- `patrol_checkpoints` - Checkpoint locations
- `patrol_checkpoint_logs` - QR code scan logs
- `patrol_reports` - Patrol completion reports
- `patrol_status` - Patrol statuses

### 8. Vehicle Domain
Vehicle and driver management.

**Tables**:
- `vehicles` - Vehicle records
- `vehicle_types` - Vehicle categories
- `drivers` - Driver assignments
- `vehicle_inspections` - Pre/post-trip inspections
- `vehicle_maintenance` - Maintenance logs
- `fuel_logs` - Fuel consumption tracking
- `vehicle_usage` - Usage logs

### 9. Fire Safety Domain
Fire safety and emergency procedures.

**Tables**:
- `fire_equipment` - Fire safety equipment
- `fire_drills` - Scheduled fire drills
- `fire_drill_records` - Fire drill execution records
- `safety_procedures` - Emergency procedures
- `safety_inspections` - Compliance inspections
- `fire_safety_status` - Compliance status

### 10. Asset Domain
Company asset tracking.

**Tables**:
- `assets` - Asset inventory
- `asset_types` - Asset categories
- `asset_assignments` - Assets assigned to employees
- `asset_maintenance` - Maintenance records

### 11. Reporting Domain
Report generation and templates.

**Tables**:
- `reports` - Generated reports
- `report_templates` - Report templates
- `scheduled_reports` - Scheduled report generation
- `report_parameters` - Dynamic report parameters
- `report_history` - Report archive

### 12. Analytics Domain
Metrics and performance tracking.

**Tables**:
- `dashboards` - Dashboard configurations
- `metrics` - System metrics
- `kpis` - Key performance indicators
- `analytics_events` - Event tracking for analytics
- `daily_summaries` - Daily summary data
- `weekly_summaries` - Weekly summary data
- `monthly_summaries` - Monthly summary data

### 13. Audit & Compliance Domain
Activity logging and compliance.

**Tables**:
- `audit_logs` - System activity audit trail
- `change_logs` - Data change tracking
- `compliance_records` - Compliance status
- `user_activities` - User action logging
- `system_events` - System events

### 14. Settings & Configuration Domain
System and user settings.

**Tables**:
- `settings` - System-wide settings
- `user_preferences` - User preferences
- `company_settings` - Company-specific settings
- `feature_flags` - Feature toggle flags
- `notifications_preferences` - User notification settings

### 15. File Storage Domain
File and document management.

**Tables**:
- `files` - File metadata
- `file_attachments` - Attachments linked to records
- `backups` - Backup metadata

---

## 🔗 Key Relationships

### Company Hierarchy
```
Company (1)
  ↓ has many
Branch (many)
  ↓ has many
Site (many)
  ↓ has many
Employee (many)
```

### User & Access
```
User
  ↓ has many
User_Role
  ↓ has many
Role
  ↓ has many
Role_Permission
  ↓ has many
Permission
```

### Attendance Flow
```
Employee
  ↓ works in
Shift
  ↓ tracks
Attendance
  ↓ contains
Clock_Log
```

### Incident Workflow
```
Incident
  ├─ has many
  ├─ Incident_Update
  ├─ Incident_Photo
  └─ Incident_Witness
```

---

## 📊 Table Design Principles

### Standard Fields (Every Table)

Every table should include:

```sql
CREATE TABLE example_table (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL (for soft deletes),
    
    -- Audit
    created_by BIGINT UNSIGNED REFERENCES users(id),
    updated_by BIGINT UNSIGNED REFERENCES users(id),
    
    -- Status
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    
    -- Data
    ...domain_specific_columns...
)
```

### Naming Conventions

**Tables**: `snake_case` plural
```
users
attendance_records
incident_photos
```

**Columns**: `snake_case` singular
```
user_id (foreign key)
created_at (timestamp)
is_active (boolean)
```

**Primary Keys**: `id` (BIGINT UNSIGNED)
```
PRIMARY KEY (id)
```

**Foreign Keys**: `{table}_id`
```
user_id BIGINT UNSIGNED REFERENCES users(id)
company_id BIGINT UNSIGNED REFERENCES companies(id)
```

---

## 🎯 Specific Table Examples

### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    phone VARCHAR(20),
    employee_id BIGINT UNSIGNED REFERENCES employees(id),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_employee_id (employee_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

### Attendance Table
```sql
CREATE TABLE attendance (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    shift_id BIGINT UNSIGNED,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'half-day', 'on-leave') DEFAULT 'present',
    total_hours DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_employee_date (employee_id, attendance_date),
    INDEX idx_site_date (site_id, attendance_date),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (site_id) REFERENCES sites(id),
    FOREIGN KEY (shift_id) REFERENCES shifts(id)
);
```

### Clock Logs Table
```sql
CREATE TABLE clock_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    attendance_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    clock_type ENUM('in', 'out') NOT NULL,
    clock_time TIMESTAMP NOT NULL,
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    gps_accuracy FLOAT,
    method ENUM('manual', 'qr', 'biometric', 'gps') DEFAULT 'manual',
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_employee_date (employee_id, DATE(clock_time)),
    INDEX idx_attendance (attendance_id),
    FOREIGN KEY (attendance_id) REFERENCES attendance(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
```

### Incidents Table
```sql
CREATE TABLE incidents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL,
    incident_type_id BIGINT UNSIGNED NOT NULL,
    reported_by BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    severity_level ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('open', 'investigating', 'resolved', 'closed') DEFAULT 'open',
    occurred_at TIMESTAMP,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    ai_summary LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_site_date (site_id, occurred_at),
    INDEX idx_status (status),
    FOREIGN KEY (site_id) REFERENCES sites(id),
    FOREIGN KEY (incident_type_id) REFERENCES incident_types(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);
```

---

## 🗂️ Tables Summary

| Domain | Table Count | Tables |
|--------|---|---|
| Authentication | 6 | users, roles, permissions, user_roles, role_permissions, api_keys |
| Organization | 5 | companies, branches, sites, departments, divisions |
| Workforce | 6 | employees, employee_qualifications, employee_training, employee_emergency_contacts, designations, employment_types |
| Attendance | 10 | shifts, shift_assignments, attendance, clock_logs, breaks, overtime, leave_types, leave_requests, leave_approvals, attendance_reports |
| Security Ops | 8 | incidents, incident_types, incident_updates, incident_photos, incident_witnesses, occurrence_book, incident_status, incident_severity |
| Visitors | 6 | visitors, visitor_visits, visitor_badges, visitor_blacklist, visit_purposes, visitor_hosts |
| Patrols | 6 | patrols, patrol_routes, patrol_checkpoints, patrol_checkpoint_logs, patrol_reports, patrol_status |
| Vehicles | 7 | vehicles, vehicle_types, drivers, vehicle_inspections, vehicle_maintenance, fuel_logs, vehicle_usage |
| Fire Safety | 6 | fire_equipment, fire_drills, fire_drill_records, safety_procedures, safety_inspections, fire_safety_status |
| Assets | 4 | assets, asset_types, asset_assignments, asset_maintenance |
| Reports | 5 | reports, report_templates, scheduled_reports, report_parameters, report_history |
| Analytics | 7 | dashboards, metrics, kpis, analytics_events, daily_summaries, weekly_summaries, monthly_summaries |
| Audit | 5 | audit_logs, change_logs, compliance_records, user_activities, system_events |
| Settings | 5 | settings, user_preferences, company_settings, feature_flags, notifications_preferences |
| Files | 3 | files, file_attachments, backups |

**Total: ~48 tables**

---

## 📐 Design Tools

### Recommended Tools for ERD

1. **Draw.io** (Free, web-based)
   - Easy to use
   - Exports to PNG, SVG, PDF
   - Good for presentations

2. **Lucidchart** (Paid, but powerful)
   - Professional diagrams
   - Team collaboration
   - Export options

3. **MySQL Workbench** (Free, powerful)
   - SQL forward-engineer
   - Schema synchronization
   - Professional ERDs

4. **Miro** (Collaborative)
   - Team brainstorming
   - Real-time editing
   - Export to Lucidchart

---

## 📝 Next Session Agenda

### Hour 1: ERD Design
- [ ] Define all 48 tables
- [ ] Document relationships
- [ ] Identify primary/foreign keys
- [ ] Plan indexes

### Hour 2: Database Design Document
- [ ] Create comprehensive schema doc
- [ ] Document table purposes
- [ ] Document relationships
- [ ] Document constraints

### Hour 3-4: SQL Generation
- [ ] Generate migration files
- [ ] Create seed data scripts
- [ ] Test schema creation
- [ ] Verify relationships

### Hour 5-8: Implementation
- [ ] Set up database locally
- [ ] Run migrations
- [ ] Populate seed data
- [ ] Verify data integrity

---

## 🔍 Quality Checklist

Before finalizing ERD, verify:

- [ ] All domains have tables
- [ ] All relationships documented
- [ ] No orphaned tables
- [ ] Primary keys defined
- [ ] Foreign keys established
- [ ] Indexes planned
- [ ] Standard fields included (id, created_at, updated_at, etc.)
- [ ] Naming conventions consistent
- [ ] Soft deletes considered
- [ ] Audit trail support
- [ ] Data types appropriate
- [ ] Constraints documented

---

## 📚 Deliverables

By end of this task:

1. ✅ **ERD Diagram** (PNG/SVG)
   - All tables visible
   - All relationships shown
   - Professional appearance

2. ✅ **Database Design Document** (SCHEMA.md)
   - Table descriptions
   - Column documentation
   - Relationship documentation
   - Design decisions explained

3. ✅ **SQL Migration Files**
   - 001_authentication.sql
   - 002_organization.sql
   - 003_workforce.sql
   - ...and 12 more

4. ✅ **Seed Data Scripts**
   - test_companies.sql
   - test_users.sql
   - test_employees.sql
   - etc.

5. ✅ **Documentation**
   - README for database setup
   - Instructions for running migrations
   - Data dictionary

---

## 🚀 Success Criteria

This task is complete when:

- ✓ ERD shows all 40-50 tables
- ✓ All relationships correct
- ✓ All migrations run successfully
- ✓ Database created with zero errors
- ✓ Seed data loads without issues
- ✓ Referential integrity verified
- ✓ All documentation complete
- ✓ Ready to build Sprint 1 API endpoints

---

**Status**: Ready for next session  
**Effort**: 8 hours  
**Priority**: 🔴 Critical (foundation for everything)

Let's build a solid database! 🚀
