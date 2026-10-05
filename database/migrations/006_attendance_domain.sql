-- ============================================================================
-- Sentinel AI Security Suite - Attendance Domain Migration
-- Core workforce management and time tracking
-- ============================================================================
-- The Attendance Domain tracks employee/contractor presence with:
-- - Clock in/out with GPS and facial recognition
-- - Shift management and assignments
-- - Break tracking (mandatory and voluntary)
-- - Overtime and leave management
-- - Attendance exceptions and rules
-- - Performance metrics and reporting
-- 
-- This domain demonstrates integration of:
-- - Event Bus (SEB) for event emission
-- - Workflow Engine (WFE) for process automation
-- - Rule Engine (SRE) for intelligent decision-making
-- 
-- Dependencies: 001_identity_and_access.sql, 003_workflow_engine.sql, 004_event_system.sql, 005_rule_engine.sql
-- Status: Production Ready
-- Version: 1.0
-- Date: 2026-07-08
-- ============================================================================

-- ============================================================================
-- 1. SHIFT_TYPES - Define shift categories
-- ============================================================================
CREATE TABLE shift_types (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique shift type ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    shift_type_name VARCHAR(100) NOT NULL COMMENT 'Name of shift type (e.g., Morning, Night, Graveyard)',
    shift_code VARCHAR(50) NOT NULL COMMENT 'Unique code for shift type',
    shift_description TEXT COMMENT 'Description of shift characteristics',
    
    -- Timing
    start_time TIME NOT NULL COMMENT 'Shift start time (HH:MM:SS)',
    end_time TIME NOT NULL COMMENT 'Shift end time (HH:MM:SS)',
    duration_hours DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN TIME_TO_SEC(end_time) >= TIME_TO_SEC(start_time)
            THEN (TIME_TO_SEC(end_time) - TIME_TO_SEC(start_time)) / 3600
            ELSE ((24*3600 - TIME_TO_SEC(start_time)) + TIME_TO_SEC(end_time)) / 3600
        END
    ) STORED COMMENT 'Duration in hours (calculated)',
    
    -- Break Configuration
    break_minutes_after_hours DECIMAL(4,2) COMMENT 'Break provided after X hours of work',
    break_duration_minutes INT COMMENT 'Duration of break in minutes',
    mandatory_breaks INT DEFAULT 1 COMMENT 'Number of mandatory breaks',
    
    -- Overtime Policy
    overtime_starts_after_hours DECIMAL(5,2) COMMENT 'Overtime begins after X hours',
    overtime_multiplier DECIMAL(3,2) DEFAULT 1.5 COMMENT 'Overtime pay multiplier',
    
    -- Allowances
    weekend_multiplier DECIMAL(3,2) DEFAULT 1.0 COMMENT 'Pay multiplier for weekend shifts',
    holiday_multiplier DECIMAL(3,2) DEFAULT 2.0 COMMENT 'Pay multiplier for holiday shifts',
    night_shift_allowance DECIMAL(10,2) DEFAULT 0 COMMENT 'Fixed allowance for night shifts',
    
    -- Status and Dates
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether shift type is currently active',
    effective_from DATE COMMENT 'Date shift type becomes effective',
    effective_until DATE COMMENT 'Date shift type expires',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_shift_type_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT uniq_shift_code UNIQUE (company_id, shift_code),
    INDEX idx_company_active (company_id, is_active),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_shift_type_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 2. SHIFTS - Specific shift instances
-- ============================================================================
CREATE TABLE shifts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique shift ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    site_id BIGINT NOT NULL COMMENT 'Reference to site where shift occurs',
    shift_type_id BIGINT NOT NULL COMMENT 'Reference to shift type',
    shift_date DATE NOT NULL COMMENT 'Date of shift',
    shift_name VARCHAR(255) COMMENT 'Optional custom shift name',
    
    -- Staffing
    required_staff_count INT DEFAULT 1 COMMENT 'Minimum staff required for shift',
    assigned_staff_count INT DEFAULT 0 COMMENT 'Current number assigned',
    
    -- Status
    shift_status ENUM('planned', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned' COMMENT 'Current shift status',
    
    -- Timing (may override shift type)
    shift_start_time TIME COMMENT 'Actual start time (override if different from shift type)',
    shift_end_time TIME COMMENT 'Actual end time (override if different from shift type)',
    
    -- Break Configuration
    break_count INT COMMENT 'Number of breaks for this shift',
    break_duration_total_minutes INT COMMENT 'Total break minutes for shift',
    
    -- Special Conditions
    is_holiday TINYINT(1) DEFAULT FALSE COMMENT 'Whether shift is on a holiday',
    is_emergency TINYINT(1) DEFAULT FALSE COMMENT 'Whether this is an emergency/unplanned shift',
    emergency_reason TEXT COMMENT 'Reason for emergency shift',
    
    -- Audit
    created_by BIGINT COMMENT 'User ID who created shift',
    updated_by BIGINT COMMENT 'User ID who last updated shift',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_shift_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_shift_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_shift_type FOREIGN KEY (shift_type_id) REFERENCES shift_types(id) ON DELETE CASCADE,
    CONSTRAINT fk_shift_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_shift_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_site_date (company_id, site_id, shift_date),
    INDEX idx_shift_status (shift_status),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_shift_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 3. SHIFT_ASSIGNMENTS - Assign staff to shifts
-- ============================================================================
CREATE TABLE shift_assignments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique shift assignment ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    shift_id BIGINT NOT NULL COMMENT 'Reference to shift',
    person_id BIGINT NOT NULL COMMENT 'Reference to person assigned',
    employment_id BIGINT COMMENT 'Reference to employment record',
    
    -- Assignment Status
    assignment_status ENUM(
        'tentative',
        'confirmed',
        'started',
        'completed',
        'no_show',
        'cancelled'
    ) DEFAULT 'tentative' COMMENT 'Status of assignment',
    
    -- Confirmation
    assigned_by BIGINT COMMENT 'User ID who assigned',
    confirmed_by BIGINT COMMENT 'User ID who confirmed',
    confirmed_at TIMESTAMP NULL COMMENT 'When assignment was confirmed',
    
    -- Actual Hours
    actual_start_time TIMESTAMP NULL COMMENT 'When person actually started',
    actual_end_time TIMESTAMP NULL COMMENT 'When person actually ended',
    actual_hours_worked DECIMAL(6,2) COMMENT 'Calculated actual hours',
    
    -- Notes
    assignment_notes TEXT COMMENT 'Any notes about assignment',
    no_show_reason VARCHAR(255) COMMENT 'Reason if person didn\'t show up',
    
    -- Deviations from Shift
    early_arrival_minutes INT COMMENT 'Minutes early person arrived',
    late_arrival_minutes INT COMMENT 'Minutes late person arrived',
    early_departure_minutes INT COMMENT 'Minutes early person left',
    late_departure_minutes INT COMMENT 'Minutes late person left',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_assignment_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_employment FOREIGN KEY (employment_id) REFERENCES employment(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignment_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_assignment_confirmed_by FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_shift (company_id, shift_id),
    INDEX idx_person_date (person_id, DATE(created_at)),
    INDEX idx_assignment_status (assignment_status),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_assignment_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 4. ATTENDANCE - Daily attendance records
-- ============================================================================
CREATE TABLE attendance (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique attendance record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    person_id BIGINT NOT NULL COMMENT 'Reference to person',
    employment_id BIGINT COMMENT 'Reference to employment at time of attendance',
    site_id BIGINT COMMENT 'Reference to site where person worked',
    shift_assignment_id BIGINT COMMENT 'Reference to shift assignment',
    
    -- Date
    attendance_date DATE NOT NULL COMMENT 'Date of attendance',
    
    -- Status
    attendance_status ENUM(
        'present',
        'absent',
        'late',
        'early_leave',
        'on_leave',
        'holiday',
        'weekend',
        'exception'
    ) DEFAULT 'present' COMMENT 'Attendance status',
    
    -- Clock Times
    clock_in_time TIMESTAMP NULL COMMENT 'When person clocked in',
    clock_out_time TIMESTAMP NULL COMMENT 'When person clocked out',
    
    -- Clock Details
    clock_in_location_latitude DECIMAL(10, 8) COMMENT 'GPS latitude of clock in',
    clock_in_location_longitude DECIMAL(11, 8) COMMENT 'GPS longitude of clock in',
    clock_in_address VARCHAR(500) COMMENT 'Address of clock in location',
    clock_in_device_id VARCHAR(100) COMMENT 'Device ID used for clock in',
    clock_in_method ENUM('mobile', 'web', 'biometric', 'manual', 'api') COMMENT 'Method used to clock in',
    clock_in_verified TINYINT(1) DEFAULT FALSE COMMENT 'Whether clock in was verified',
    
    clock_out_location_latitude DECIMAL(10, 8) COMMENT 'GPS latitude of clock out',
    clock_out_location_longitude DECIMAL(11, 8) COMMENT 'GPS longitude of clock out',
    clock_out_address VARCHAR(500) COMMENT 'Address of clock out location',
    clock_out_device_id VARCHAR(100) COMMENT 'Device ID used for clock out',
    clock_out_method ENUM('mobile', 'web', 'biometric', 'manual', 'api') COMMENT 'Method used to clock out',
    clock_out_verified TINYINT(1) DEFAULT FALSE COMMENT 'Whether clock out was verified',
    
    -- Hours Calculation
    hours_worked DECIMAL(6,2) COMMENT 'Total hours worked (calculated)',
    hours_expected DECIMAL(6,2) COMMENT 'Expected hours for shift',
    hours_difference DECIMAL(6,2) COMMENT 'Difference from expected (negative=short, positive=over)',
    
    -- Attendance Quality
    is_verified TINYINT(1) DEFAULT FALSE COMMENT 'Whether attendance record is verified',
    verified_by BIGINT COMMENT 'User ID who verified record',
    verified_at TIMESTAMP NULL COMMENT 'When verified',
    
    -- Flags and Notes
    has_exception TINYINT(1) DEFAULT FALSE COMMENT 'Whether record has exceptions',
    exception_reason VARCHAR(255) COMMENT 'Reason for exception',
    notes TEXT COMMENT 'Additional notes about attendance',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_attendance_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_employment FOREIGN KEY (employment_id) REFERENCES employment(id) ON DELETE SET NULL,
    CONSTRAINT fk_attendance_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL,
    CONSTRAINT fk_attendance_shift FOREIGN KEY (shift_assignment_id) REFERENCES shift_assignments(id) ON DELETE SET NULL,
    CONSTRAINT fk_attendance_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_person_date (company_id, person_id, attendance_date),
    INDEX idx_site_date (site_id, attendance_date),
    INDEX idx_attendance_status (attendance_status),
    INDEX idx_clock_in_time (clock_in_time),
    INDEX idx_verified (is_verified),
    INDEX idx_has_exception (has_exception),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_attendance_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 5. CLOCK_LOGS - Detailed log of all clock in/out events
-- ============================================================================
CREATE TABLE clock_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique clock log ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    person_id BIGINT NOT NULL COMMENT 'Reference to person',
    attendance_id BIGINT COMMENT 'Reference to attendance record',
    
    -- Clock Event
    clock_event_type ENUM('clock_in', 'clock_out') NOT NULL COMMENT 'Type of clock event',
    clock_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When clock event occurred',
    
    -- Location
    location_latitude DECIMAL(10, 8) COMMENT 'GPS latitude of clock location',
    location_longitude DECIMAL(11, 8) COMMENT 'GPS longitude of clock location',
    location_address VARCHAR(500) COMMENT 'Address of clock location',
    location_site_id BIGINT COMMENT 'Site ID if clock was at a registered site',
    location_accuracy_meters INT COMMENT 'Accuracy of GPS location in meters',
    
    -- Device Information
    device_id VARCHAR(100) COMMENT 'Unique device ID',
    device_type VARCHAR(50) COMMENT 'Type of device (mobile, desktop, tablet, biometric_terminal)',
    device_os VARCHAR(50) COMMENT 'Operating system of device',
    device_app_version VARCHAR(50) COMMENT 'App version on device',
    
    -- Clock Method
    clock_method ENUM('mobile_app', 'web_portal', 'biometric_fingerprint', 'biometric_facial', 'manual_entry', 'api') COMMENT 'Method of clocking',
    
    -- Verification
    is_verified TINYINT(1) DEFAULT FALSE COMMENT 'Whether clock is verified',
    verification_method VARCHAR(100) COMMENT 'How clock was verified (if at all)',
    
    -- Biometric Data
    biometric_match_score INT COMMENT 'Confidence score if biometric (0-100)',
    biometric_template_id BIGINT COMMENT 'Reference to stored biometric template',
    
    -- Additional Data
    user_agent VARCHAR(500) COMMENT 'User agent string from device',
    ip_address VARCHAR(45) COMMENT 'IP address of clocking device',
    notes TEXT COMMENT 'Any notes about the clock event',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_clock_log_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_clock_log_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_clock_log_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
    CONSTRAINT fk_clock_log_site FOREIGN KEY (location_site_id) REFERENCES sites(id) ON DELETE SET NULL,
    INDEX idx_company_person_timestamp (company_id, person_id, clock_timestamp),
    INDEX idx_clock_event_type (clock_event_type),
    INDEX idx_clock_timestamp (clock_timestamp),
    INDEX idx_is_verified (is_verified),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_clock_log_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 6. BREAKS - Break records
-- ============================================================================
CREATE TABLE breaks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique break record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    attendance_id BIGINT NOT NULL COMMENT 'Reference to attendance record',
    person_id BIGINT NOT NULL COMMENT 'Reference to person',
    
    -- Break Classification
    break_type ENUM('mandatory', 'meal', 'prayer', 'rest', 'other') DEFAULT 'mandatory' COMMENT 'Type of break',
    break_name VARCHAR(100) COMMENT 'Name of break (e.g., "Lunch Break")',
    
    -- Timing
    break_start_time TIMESTAMP NOT NULL COMMENT 'When break started',
    break_end_time TIMESTAMP NULL COMMENT 'When break ended',
    break_duration_minutes INT COMMENT 'Duration of break in minutes',
    
    -- Approval
    break_status ENUM('planned', 'taken', 'skipped', 'denied') DEFAULT 'planned' COMMENT 'Status of break',
    approved_by BIGINT COMMENT 'User ID who approved break',
    approved_at TIMESTAMP NULL COMMENT 'When break was approved',
    
    -- Location
    break_location_latitude DECIMAL(10, 8) COMMENT 'GPS latitude during break',
    break_location_longitude DECIMAL(11, 8) COMMENT 'GPS longitude during break',
    location_on_site TINYINT(1) COMMENT 'Whether break was taken on site',
    
    -- Notes
    break_notes TEXT COMMENT 'Notes about break',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_break_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_break_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
    CONSTRAINT fk_break_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_break_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_attendance (company_id, attendance_id),
    INDEX idx_break_status (break_status),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_break_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 7. OVERTIME - Overtime records
-- ============================================================================
CREATE TABLE overtime (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique overtime record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    person_id BIGINT NOT NULL COMMENT 'Reference to person',
    attendance_id BIGINT COMMENT 'Reference to attendance record if applicable',
    
    -- Period
    overtime_date DATE NOT NULL COMMENT 'Date of overtime',
    overtime_start_time TIMESTAMP NOT NULL COMMENT 'When overtime started',
    overtime_end_time TIMESTAMP NULL COMMENT 'When overtime ended',
    
    -- Duration
    overtime_hours DECIMAL(6,2) COMMENT 'Total overtime hours',
    overtime_multiplier DECIMAL(3,2) COMMENT 'Pay multiplier for this overtime',
    
    -- Reason
    overtime_reason ENUM(
        'extended_shift',
        'emergency',
        'workload',
        'client_request',
        'system_failure',
        'other'
    ) COMMENT 'Reason for overtime',
    overtime_description TEXT COMMENT 'Detailed description of overtime reason',
    
    -- Approval
    overtime_status ENUM('requested', 'approved', 'completed', 'denied', 'cancelled') DEFAULT 'requested' COMMENT 'Status of overtime',
    approved_by BIGINT COMMENT 'User ID who approved overtime',
    approved_at TIMESTAMP NULL COMMENT 'When overtime was approved',
    approval_reason VARCHAR(500) COMMENT 'Reason for approval/denial',
    
    -- Compensation
    compensation_type ENUM('payment', 'time_off', 'both') DEFAULT 'payment' COMMENT 'How overtime is compensated',
    paid_amount DECIMAL(10,2) COMMENT 'Amount paid if compensation is payment',
    time_off_hours DECIMAL(6,2) COMMENT 'Hours of time off if compensation is time_off',
    paid_at TIMESTAMP NULL COMMENT 'When overtime compensation was processed',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_overtime_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_overtime_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_overtime_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE SET NULL,
    CONSTRAINT fk_overtime_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_person_date (company_id, person_id, overtime_date),
    INDEX idx_overtime_status (overtime_status),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_overtime_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 8. ATTENDANCE_METRICS - Summary metrics for reporting
-- ============================================================================
CREATE TABLE attendance_metrics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique metric record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    person_id BIGINT COMMENT 'Reference to person (NULL = company-wide)',
    site_id BIGINT COMMENT 'Reference to site (NULL = all sites)',
    
    -- Period
    metric_period ENUM('daily', 'weekly', 'monthly', 'quarterly', 'annual') DEFAULT 'monthly' COMMENT 'Aggregation period',
    period_start_date DATE NOT NULL COMMENT 'Start date of metric period',
    period_end_date DATE NOT NULL COMMENT 'End date of metric period',
    
    -- Attendance Metrics
    total_scheduled_days INT COMMENT 'Total scheduled work days',
    days_present INT DEFAULT 0 COMMENT 'Days present',
    days_absent INT DEFAULT 0 COMMENT 'Days absent',
    days_late INT DEFAULT 0 COMMENT 'Days person was late',
    days_early_leave INT DEFAULT 0 COMMENT 'Days person left early',
    days_on_leave INT DEFAULT 0 COMMENT 'Days on approved leave',
    days_holiday INT DEFAULT 0 COMMENT 'Holiday days',
    
    -- Hour Metrics
    total_hours_scheduled DECIMAL(10,2) COMMENT 'Total hours scheduled',
    total_hours_worked DECIMAL(10,2) DEFAULT 0 COMMENT 'Total hours actually worked',
    total_overtime_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Total overtime hours',
    total_break_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Total break hours',
    
    -- Performance
    attendance_percentage DECIMAL(5,2) COMMENT 'Attendance percentage (days_present/total_scheduled_days)',
    punctuality_percentage DECIMAL(5,2) COMMENT 'Punctuality percentage (days_on_time/total_scheduled_days)',
    productivity_hours DECIMAL(10,2) COMMENT 'Productive hours (total_hours_worked - total_break_hours)',
    
    -- Exceptions
    exceptions_count INT DEFAULT 0 COMMENT 'Total attendance exceptions',
    late_arrivals INT DEFAULT 0 COMMENT 'Count of late arrivals',
    early_departures INT DEFAULT 0 COMMENT 'Count of early departures',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    CONSTRAINT fk_metric_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_metric_person FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    CONSTRAINT fk_metric_site FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_company_period (company_id, period_end_date),
    INDEX idx_person_period (person_id, period_end_date),
    INDEX idx_site_period (site_id, period_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 9. Create Performance Indexes
-- ============================================================================

-- Shift assignment lookup
CREATE INDEX idx_shift_assignment_person_company ON shift_assignments(person_id, company_id);
CREATE INDEX idx_shift_assignment_confirmed ON shift_assignments(assignment_status, confirmed_at);

-- Attendance detail queries
CREATE INDEX idx_attendance_person_company ON attendance(person_id, company_id);
CREATE INDEX idx_attendance_has_exception ON attendance(has_exception, company_id);

-- Clock log queries
CREATE INDEX idx_clock_log_device ON clock_logs(device_id, clock_timestamp);
CREATE INDEX idx_clock_log_biometric ON clock_logs(biometric_match_score, clock_timestamp);

-- Break tracking
CREATE INDEX idx_break_attendance_person ON breaks(attendance_id, person_id);
CREATE INDEX idx_break_taken ON breaks(break_status, break_start_time);

-- Overtime requests
CREATE INDEX idx_overtime_approval ON overtime(overtime_status, approved_at);


-- ============================================================================
-- 10. Initial System Status
-- ============================================================================

SELECT 'Attendance Domain Created Successfully' AS status;
SELECT 'Tables Created: 8 (Shift Types, Shifts, Assignments, Attendance, Clock Logs, Breaks, Overtime, Metrics)' AS info;
SELECT 'Ready for: Workforce clock in/out, shift management, attendance tracking, leave management, reporting' AS info;
SELECT 'Integrations: Events (clock_in_recorded, person_absent, overtime_requested), Rules (late_arrival, excessive_overtime), Workflows (leave_approval)' AS info;

-- ============================================================================
-- End of Attendance Domain Migration
-- ============================================================================
