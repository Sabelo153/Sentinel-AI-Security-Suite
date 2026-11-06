USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS dispatch_jobs (
 dispatch_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 incident_id BIGINT UNSIGNED NOT NULL, responder_user_id BIGINT UNSIGNED NOT NULL,
 state VARCHAR(20) NOT NULL, response_due_at DATETIME NOT NULL, created_at DATETIME NOT NULL,
 accepted_at DATETIME NULL, arrived_at DATETIME NULL, closed_at DATETIME NULL,
 resolution VARCHAR(1000) NULL, revision INT NOT NULL DEFAULT 1,
 INDEX ix_dispatch_company(company_id,state,response_due_at)
);
CREATE TABLE IF NOT EXISTS lone_worker_plans (
 plan_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 shift_assignment_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 interval_minutes INT NOT NULL, next_due_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
 duress_hash VARCHAR(255) NOT NULL, last_checkin_at DATETIME NULL, closed_at DATETIME NULL,
 UNIQUE KEY uq_lone_shift(company_id,shift_assignment_id)
);
CREATE TABLE IF NOT EXISTS lone_worker_events (
 event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 plan_id BIGINT UNSIGNED NOT NULL, event_type VARCHAR(30) NOT NULL, occurred_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS coverage_requirements (
 coverage_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 post_id BIGINT UNSIGNED NOT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
 required_guards INT NOT NULL, cancelled_at DATETIME NULL
);
CREATE TABLE IF NOT EXISTS shift_relief_history (
 relief_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 shift_assignment_id BIGINT UNSIGNED NOT NULL, previous_employee_id BIGINT UNSIGNED NOT NULL,
 replacement_employee_id BIGINT UNSIGNED NOT NULL, reason VARCHAR(500) NOT NULL,
 changed_by_user_id BIGINT UNSIGNED NOT NULL, changed_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS gate_register (
 gate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 operation_uuid CHAR(36) NOT NULL, request_hash CHAR(64) NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, visitor_name VARCHAR(150) NOT NULL,
 vehicle_registration VARCHAR(30) NULL, purpose VARCHAR(500) NOT NULL,
 direction VARCHAR(5) NOT NULL, authorization VARCHAR(10) NOT NULL,
 occurred_at DATETIME NOT NULL, received_at DATETIME NOT NULL, recorded_by_user_id BIGINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_gate_operation(company_id,recorded_by_user_id,operation_uuid),
 INDEX ix_gate_company(company_id,site_id,occurred_at)
);
CREATE TABLE IF NOT EXISTS contract_hourly_rates (
 rate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 contract_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 hourly_minor INT NOT NULL, currency CHAR(3) NOT NULL, effective_from DATE NOT NULL,
 UNIQUE KEY uq_rate_date(company_id,contract_id,site_id,effective_from)
);
CREATE TABLE IF NOT EXISTS invoice_drafts (
 invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 contract_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 date_from DATE NOT NULL, date_to DATE NOT NULL, currency CHAR(3) NOT NULL,
 subtotal_minor BIGINT UNSIGNED NOT NULL, lines_json MEDIUMTEXT NOT NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
 INDEX ix_invoice_period(company_id,contract_id,site_id,date_from,date_to)
);
CREATE TABLE IF NOT EXISTS operational_signals (
 signal_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 signal_key VARCHAR(150) NOT NULL, signal_type VARCHAR(40) NOT NULL, record_id BIGINT UNSIGNED NULL,
 message VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, acknowledged_at DATETIME NULL,
 acknowledged_by_user_id BIGINT UNSIGNED NULL,
 UNIQUE KEY uq_operational_signal(company_id,signal_key)
);
CREATE TABLE IF NOT EXISTS worker_runs (
 run_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 worker_name VARCHAR(50) NOT NULL, started_at DATETIME NOT NULL, finished_at DATETIME NOT NULL,
 state VARCHAR(10) NOT NULL, summary VARCHAR(500) NOT NULL
);
CREATE TABLE IF NOT EXISTS backup_checks (
 backup_check_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 backup_reference VARCHAR(150) NOT NULL, backed_up_at DATETIME NOT NULL,
 restored_at DATETIME NULL, checked_by_user_id BIGINT UNSIGNED NOT NULL, recorded_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS phone_health (
 health_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, installation_id CHAR(36) NOT NULL,
 battery_percent INT NOT NULL, version_code INT NOT NULL, received_at DATETIME NOT NULL,
 UNIQUE KEY uq_phone_health(company_id,user_id,installation_id)
);
