USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS report_photos (
 photo_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 report_type VARCHAR(20) NOT NULL, report_id BIGINT UNSIGNED NOT NULL, image_base64 MEDIUMTEXT NOT NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX ix_report_photos_report(company_id,report_type,report_id)
);
CREATE TABLE IF NOT EXISTS mobile_diagnostics (
 company_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, installation_id CHAR(36) NOT NULL,
 app_version VARCHAR(40) NOT NULL, pending_count INT NOT NULL, blocked_count INT NOT NULL,
 network_state VARCHAR(30) NOT NULL, received_at DATETIME NOT NULL,
 PRIMARY KEY(company_id,user_id,installation_id)
);
CREATE TABLE IF NOT EXISTS security_sessions (
 session_id CHAR(36) PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, company_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE, session_label VARCHAR(100) NOT NULL DEFAULT 'API', created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL,
 revoked_at DATETIME NULL, INDEX ix_security_sessions_user(company_id,user_id)
);
CREATE TABLE IF NOT EXISTS account_mfa (
 user_id BIGINT UNSIGNED PRIMARY KEY, secret_cipher TEXT NOT NULL, enabled INT NOT NULL DEFAULT 0,
 last_counter BIGINT NOT NULL DEFAULT -1, failed_attempts INT NOT NULL DEFAULT 0, locked_until BIGINT NOT NULL DEFAULT 0,
 recovery_codes_json TEXT NULL
);
CREATE TABLE IF NOT EXISTS support_grants (
 grant_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, expires_at DATETIME NOT NULL, reason VARCHAR(500) NOT NULL,
 revoked_at DATETIME NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 INDEX ix_support_grants_user(company_id,user_id,expires_at)
);
CREATE TABLE IF NOT EXISTS compliance_items (
 compliance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 employee_id BIGINT UNSIGNED NOT NULL, requirement_name VARCHAR(150) NOT NULL, reference_number VARCHAR(150) NULL,
 due_on DATE NOT NULL, verified_at DATETIME NULL, verified_by_user_id BIGINT UNSIGNED NULL,
 INDEX ix_compliance_due(company_id,due_on)
);
CREATE TABLE IF NOT EXISTS equipment_items (
 equipment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 asset_code VARCHAR(100) NOT NULL, equipment_name VARCHAR(150) NOT NULL,
 UNIQUE KEY uq_equipment_code(company_id,asset_code)
);
CREATE TABLE IF NOT EXISTS equipment_handovers (
 handover_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 equipment_id BIGINT UNSIGNED NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 issued_at DATETIME NOT NULL, returned_at DATETIME NULL, issue_condition VARCHAR(500) NOT NULL,
 return_condition VARCHAR(500) NULL, issued_by_user_id BIGINT UNSIGNED NOT NULL, returned_by_user_id BIGINT UNSIGNED NULL,
 INDEX ix_equipment_open(company_id,equipment_id,returned_at)
);
CREATE TABLE IF NOT EXISTS attendance_approvals (
 company_id BIGINT UNSIGNED NOT NULL, attendance_id BIGINT UNSIGNED NOT NULL,
 approved_by_user_id BIGINT UNSIGNED NOT NULL, approved_at DATETIME NOT NULL, record_hash CHAR(64) NOT NULL,
 PRIMARY KEY(company_id,attendance_id)
);
CREATE TABLE IF NOT EXISTS client_portal_access (
 company_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(company_id,user_id,client_id)
);
CREATE TABLE IF NOT EXISTS published_site_reports (
 report_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL, title VARCHAR(150) NOT NULL,
 report_text TEXT NOT NULL, published_by_user_id BIGINT UNSIGNED NOT NULL, published_at DATETIME NOT NULL,
 INDEX ix_published_client(company_id,client_id,published_at)
);
CREATE TABLE IF NOT EXISTS escalation_rules (
 rule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 recipient_user_id BIGINT UNSIGNED NOT NULL, backup_user_id BIGINT UNSIGNED NOT NULL, escalate_minutes INT NOT NULL,
 active INT NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS escalation_events (
 escalation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 rule_id BIGINT UNSIGNED NOT NULL, event_key CHAR(64) NOT NULL, event_type VARCHAR(60) NOT NULL,
 site_id BIGINT UNSIGNED NULL, message VARCHAR(1000) NOT NULL, created_at DATETIME NOT NULL,
 acknowledged_at DATETIME NULL, acknowledged_by_user_id BIGINT UNSIGNED NULL, escalated_at DATETIME NULL,
 UNIQUE KEY uq_escalation_event(company_id,rule_id,event_key)
);
