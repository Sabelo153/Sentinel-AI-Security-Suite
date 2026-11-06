USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS open_shift_offers (
 offer_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 shift_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL, expected_employee_id BIGINT UNSIGNED NOT NULL,
 deadline_at DATETIME NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OPEN', revision INT NOT NULL DEFAULT 1,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, awarded_employee_id BIGINT UNSIGNED NULL,
 INDEX ix_open_shift_offers(company_id,state,deadline_at)
);
CREATE TABLE IF NOT EXISTS open_shift_candidates (
 candidate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 offer_id BIGINT UNSIGNED NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OFFERED',
 accepted_by_user_id BIGINT UNSIGNED NULL, accepted_at DATETIME NULL,
 UNIQUE KEY uq_offer_candidate(company_id,offer_id,employee_id)
);
CREATE TABLE IF NOT EXISTS visitor_appointments (
 appointment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, visitor_name VARCHAR(150) NOT NULL,
 purpose VARCHAR(500) NOT NULL, arrival_from DATETIME NOT NULL, departure_due DATETIME NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'PENDING', revision INT NOT NULL DEFAULT 1, pass_hash CHAR(64) NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, approved_by_user_id BIGINT UNSIGNED NULL,
 approved_at DATETIME NULL, checked_in_at DATETIME NULL, checked_out_at DATETIME NULL,
 UNIQUE KEY uq_visitor_pass(pass_hash),
 INDEX ix_visitor_appointments(company_id,site_id,state,departure_due)
);
CREATE TABLE IF NOT EXISTS fleet_vehicles (
 fleet_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, registration VARCHAR(30) NOT NULL, description VARCHAR(150) NOT NULL,
 odometer_km INT NOT NULL, next_service_km INT NOT NULL, next_service_on DATE NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'ACTIVE', revision INT NOT NULL DEFAULT 1,
 UNIQUE KEY uq_company_fleet(company_id,registration)
);
CREATE TABLE IF NOT EXISTS fleet_trips (
 trip_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 fleet_id BIGINT UNSIGNED NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL, purpose VARCHAR(500) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'ALLOCATED',
 revision INT NOT NULL DEFAULT 1, inspection_json TEXT NULL, start_odometer INT NULL, end_odometer INT NULL,
 completed_at DATETIME NULL,
 INDEX ix_fleet_trips(company_id,fleet_id,state,starts_at)
);
CREATE TABLE IF NOT EXISTS fleet_fuel (
 fuel_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 fleet_id BIGINT UNSIGNED NOT NULL, odometer_km INT NOT NULL, litres_milli INT NOT NULL,
 cost_minor BIGINT UNSIGNED NOT NULL, currency CHAR(3) NOT NULL, receipt_reference VARCHAR(300) NOT NULL,
 recorded_by_user_id BIGINT UNSIGNED NOT NULL, recorded_at DATETIME NOT NULL,
 INDEX ix_fleet_fuel(company_id,fleet_id,fuel_id)
);
CREATE TABLE IF NOT EXISTS fleet_services (
 service_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 fleet_id BIGINT UNSIGNED NOT NULL, evidence_reference VARCHAR(1000) NOT NULL, odometer_km INT NOT NULL,
 next_service_km INT NOT NULL, next_service_on DATE NOT NULL, recorded_by_user_id BIGINT UNSIGNED NOT NULL,
 recorded_at DATETIME NOT NULL,
 INDEX ix_fleet_services(company_id,fleet_id,service_id)
);
CREATE TABLE IF NOT EXISTS contract_renewals (
 renewal_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 contract_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, base_hash CHAR(64) NOT NULL,
 base_json MEDIUMTEXT NOT NULL, starts_on DATE NOT NULL, ends_on DATE NOT NULL,
 monthly_minor BIGINT UNSIGNED NOT NULL, currency CHAR(3) NOT NULL, terms TEXT NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'SHARED', revision INT NOT NULL DEFAULT 1, offer_hash CHAR(64) NOT NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 decided_by_user_id BIGINT UNSIGNED NULL, decided_at DATETIME NULL, decision_notes VARCHAR(1000) NULL,
 applied_at DATETIME NULL,
 INDEX ix_contract_renewals(company_id,state,starts_on)
);
CREATE TABLE IF NOT EXISTS contract_term_history (
 history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 contract_id BIGINT UNSIGNED NOT NULL, renewal_id BIGINT UNSIGNED NOT NULL,
 before_json MEDIUMTEXT NOT NULL, after_json MEDIUMTEXT NOT NULL, applied_at DATETIME NOT NULL,
 INDEX ix_contract_term_history(company_id,renewal_id)
);
CREATE TABLE IF NOT EXISTS business_operation_settings (
 settings_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 reminder_user_id BIGINT UNSIGNED NOT NULL, revision INT NOT NULL DEFAULT 1,
 UNIQUE KEY uq_business_settings(company_id)
);
CREATE TABLE IF NOT EXISTS business_reminders (
 reminder_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 source_key VARCHAR(150) NOT NULL, message VARCHAR(1000) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OPEN',
 created_at DATETIME NOT NULL, resolved_at DATETIME NULL, UNIQUE KEY uq_business_reminder(company_id,source_key)
);
CREATE TABLE IF NOT EXISTS backup_schedules (
 schedule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 enabled INT NOT NULL DEFAULT 0, interval_hours INT NOT NULL DEFAULT 24, retain_count INT NOT NULL DEFAULT 7,
 revision INT NOT NULL DEFAULT 1, last_success_at DATETIME NULL,
 UNIQUE KEY uq_backup_schedule(company_id)
);
CREATE TABLE IF NOT EXISTS encrypted_backups (
 backup_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 artifact_uuid CHAR(36) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'RUNNING',
 started_at DATETIME NOT NULL, finished_at DATETIME NULL, byte_count BIGINT UNSIGNED NULL,
 file_hash CHAR(64) NULL, record_count BIGINT UNSIGNED NULL, restore_state VARCHAR(20) NOT NULL DEFAULT 'UNTESTED',
 restored_at DATETIME NULL, result_note VARCHAR(500) NULL, UNIQUE KEY uq_backup_artifact(artifact_uuid),
 INDEX ix_encrypted_backups(company_id,state,backup_id)
);
CREATE TABLE IF NOT EXISTS pilot_role_templates (
 template_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 template_code VARCHAR(30) NOT NULL, role_id BIGINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_pilot_role(company_id,template_code)
);
CREATE TABLE IF NOT EXISTS pilot_demo_records (
 demo_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 label VARCHAR(150) NOT NULL, scenario_json TEXT NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX ix_pilot_demo_records(company_id,demo_id)
);
