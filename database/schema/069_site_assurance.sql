USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS site_instruction_versions (
 instruction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, revision INT NOT NULL, title VARCHAR(150) NOT NULL,
 instructions TEXT NOT NULL, emergency_contacts TEXT NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 published_at DATETIME NOT NULL, UNIQUE KEY uq_site_instruction_revision(company_id,site_id,revision)
);
CREATE TABLE IF NOT EXISTS site_instruction_acknowledgements (
 acknowledgement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 instruction_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, acknowledged_at DATETIME NOT NULL,
 UNIQUE KEY uq_instruction_ack(company_id,instruction_id,user_id)
);
CREATE TABLE IF NOT EXISTS shift_handovers (
 handover_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 source_shift_id BIGINT UNSIGNED NOT NULL, target_shift_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 sender_employee_id BIGINT UNSIGNED NOT NULL, receiver_employee_id BIGINT UNSIGNED NOT NULL,
 keys_summary VARCHAR(1000) NOT NULL, equipment_summary VARCHAR(1000) NOT NULL,
 open_incident_ids TEXT NOT NULL, outstanding_tasks TEXT NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL, accepted_at DATETIME NULL, accepted_by_user_id BIGINT UNSIGNED NULL,
 receiver_notes VARCHAR(1000) NULL, UNIQUE KEY uq_shift_handover(company_id,source_shift_id,target_shift_id)
);
CREATE TABLE IF NOT EXISTS training_competencies (
 competency_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 competency_code VARCHAR(60) NOT NULL, competency_name VARCHAR(150) NOT NULL,
 UNIQUE KEY uq_competency_code(company_id,competency_code)
);
CREATE TABLE IF NOT EXISTS site_training_requirements (
 requirement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, competency_id BIGINT UNSIGNED NOT NULL, removed_at DATETIME NULL,
 UNIQUE KEY uq_site_competency(company_id,site_id,competency_id)
);
CREATE TABLE IF NOT EXISTS employee_training (
 training_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 employee_id BIGINT UNSIGNED NOT NULL, competency_id BIGINT UNSIGNED NOT NULL,
 reference_number VARCHAR(150) NOT NULL, valid_from DATE NOT NULL, valid_until DATE NOT NULL,
 verified_at DATETIME NULL, verified_by_user_id BIGINT UNSIGNED NULL, revoked_at DATETIME NULL,
 revoked_by_user_id BIGINT UNSIGNED NULL, revocation_reason VARCHAR(500) NULL,
 INDEX ix_training_employee(company_id,employee_id,competency_id,valid_until)
);
CREATE TABLE IF NOT EXISTS maintenance_tickets (
 ticket_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, equipment_id BIGINT UNSIGNED NULL, asset_type VARCHAR(20) NOT NULL,
 title VARCHAR(150) NOT NULL, description TEXT NOT NULL, priority VARCHAR(10) NOT NULL,
 assignee_user_id BIGINT UNSIGNED NOT NULL, state VARCHAR(30) NOT NULL, revision INT NOT NULL DEFAULT 1,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
 verified_at DATETIME NULL, verified_by_user_id BIGINT UNSIGNED NULL
);
CREATE TABLE IF NOT EXISTS maintenance_history (
 history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 ticket_id BIGINT UNSIGNED NOT NULL, state VARCHAR(30) NOT NULL, notes VARCHAR(2000) NOT NULL,
 actor_user_id BIGINT UNSIGNED NOT NULL, occurred_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS site_service_targets (
 target_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, effective_from DATE NOT NULL, response_minutes INT NOT NULL,
 patrols_per_day INT NOT NULL, guard_minutes_per_day INT NOT NULL,
 UNIQUE KEY uq_site_service_date(company_id,site_id,effective_from)
);
CREATE TABLE IF NOT EXISTS incident_evidence (
 evidence_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 incident_id BIGINT UNSIGNED NOT NULL, original_filename VARCHAR(150) NOT NULL,
 file_base64 MEDIUMTEXT NOT NULL, byte_length INT NOT NULL, sha256 CHAR(64) NOT NULL,
 description VARCHAR(1000) NOT NULL, captured_at DATETIME NOT NULL, received_at DATETIME NOT NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, supersedes_evidence_id BIGINT UNSIGNED NULL, purged_at DATETIME NULL,
 source_report_photo_id BIGINT UNSIGNED NULL, UNIQUE KEY uq_evidence_source_photo(company_id,source_report_photo_id),
 INDEX ix_incident_evidence(company_id,incident_id,evidence_id)
);
CREATE TABLE IF NOT EXISTS evidence_access_history (
 access_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 evidence_id BIGINT UNSIGNED NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL,
 action_name VARCHAR(30) NOT NULL, reason VARCHAR(500) NOT NULL, occurred_at DATETIME NOT NULL,
 sha256 CHAR(64) NOT NULL
);
CREATE TABLE IF NOT EXISTS retention_policies (
 policy_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 category VARCHAR(30) NOT NULL, retention_days INT NOT NULL, updated_by_user_id BIGINT UNSIGNED NOT NULL,
 updated_at DATETIME NOT NULL, UNIQUE KEY uq_retention_category(company_id,category)
);
CREATE TABLE IF NOT EXISTS retention_holds (
 hold_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 category VARCHAR(30) NOT NULL, record_id BIGINT UNSIGNED NULL, reason VARCHAR(500) NOT NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 released_at DATETIME NULL, released_by_user_id BIGINT UNSIGNED NULL, release_reason VARCHAR(500) NULL
);
CREATE TABLE IF NOT EXISTS retention_previews (
 preview_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 actor_user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, category VARCHAR(30) NOT NULL,
 cutoff_at DATETIME NOT NULL, policy_days INT NOT NULL, candidate_ids TEXT NOT NULL,
 expires_at DATETIME NOT NULL, consumed_at DATETIME NULL, UNIQUE KEY uq_retention_preview(token_hash)
);
