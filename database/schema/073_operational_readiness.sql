USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS custody_assets (
 asset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, asset_code VARCHAR(60) NOT NULL, asset_name VARCHAR(150) NOT NULL,
 asset_type VARCHAR(20) NOT NULL, state VARCHAR(30) NOT NULL DEFAULT 'AVAILABLE', revision INT NOT NULL DEFAULT 1,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 UNIQUE KEY uq_custody_asset(company_id,site_id,asset_code)
);
CREATE TABLE IF NOT EXISTS custody_loans (
 loan_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 asset_id BIGINT UNSIGNED NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, state VARCHAR(30) NOT NULL,
 revision INT NOT NULL DEFAULT 1, due_at DATETIME NOT NULL, issued_by_user_id BIGINT UNSIGNED NOT NULL,
 issued_at DATETIME NOT NULL, accepted_at DATETIME NULL, accepted_by_user_id BIGINT UNSIGNED NULL,
 recipient_signature MEDIUMTEXT NULL, recipient_signature_hash CHAR(64) NULL,
 return_requested_at DATETIME NULL, return_signature MEDIUMTEXT NULL, return_signature_hash CHAR(64) NULL,
 received_at DATETIME NULL, received_by_user_id BIGINT UNSIGNED NULL,
 receiver_signature MEDIUMTEXT NULL, receiver_signature_hash CHAR(64) NULL,
 INDEX ix_custody_due(company_id,state,due_at)
);
CREATE TABLE IF NOT EXISTS custody_events (
 event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 asset_id BIGINT UNSIGNED NOT NULL, loan_id BIGINT UNSIGNED NULL, event_type VARCHAR(30) NOT NULL,
 notes VARCHAR(2000) NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL, occurred_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS deployment_preferences (
 preference_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 employee_id BIGINT UNSIGNED NOT NULL, max_weekly_minutes INT NOT NULL, minimum_rest_minutes INT NOT NULL,
 base_latitude DECIMAL(10,7) NULL, base_longitude DECIMAL(10,7) NULL, max_distance_km INT NOT NULL,
 revision INT NOT NULL DEFAULT 1, UNIQUE KEY uq_deployment_preference(company_id,employee_id)
);
CREATE TABLE IF NOT EXISTS emergency_procedures (
 procedure_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, revision INT NOT NULL, title VARCHAR(150) NOT NULL,
 steps_json MEDIUMTEXT NOT NULL, emergency_contacts TEXT NOT NULL, assembly_point VARCHAR(500) NOT NULL,
 published_by_user_id BIGINT UNSIGNED NOT NULL, published_at DATETIME NOT NULL,
 UNIQUE KEY uq_emergency_revision(company_id,site_id,revision)
);
CREATE TABLE IF NOT EXISTS emergency_exercises (
 exercise_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, procedure_id BIGINT UNSIGNED NOT NULL, exercise_type VARCHAR(20) NOT NULL,
 title VARCHAR(150) NOT NULL, state VARCHAR(30) NOT NULL DEFAULT 'ACTIVE', revision INT NOT NULL DEFAULT 1,
 started_by_user_id BIGINT UNSIGNED NOT NULL, started_at DATETIME NOT NULL,
 closed_by_user_id BIGINT UNSIGNED NULL, closed_at DATETIME NULL, debrief TEXT NULL
);
CREATE TABLE IF NOT EXISTS emergency_roll_calls (
 participant_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 exercise_id BIGINT UNSIGNED NOT NULL, participant_name VARCHAR(150) NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'UNKNOWN', revision INT NOT NULL DEFAULT 1,
 notes VARCHAR(1000) NULL, checked_by_user_id BIGINT UNSIGNED NULL, checked_at DATETIME NULL
);
CREATE TABLE IF NOT EXISTS emergency_step_records (
 step_record_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 exercise_id BIGINT UNSIGNED NOT NULL, step_number INT NOT NULL, notes VARCHAR(1000) NOT NULL,
 completed_by_user_id BIGINT UNSIGNED NOT NULL, completed_at DATETIME NOT NULL,
 UNIQUE KEY uq_emergency_step(company_id,exercise_id,step_number)
);
CREATE TABLE IF NOT EXISTS user_workflow_languages (
 preference_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, language_code VARCHAR(10) NOT NULL,
 UNIQUE KEY uq_user_workflow_language(company_id,user_id)
);
CREATE TABLE IF NOT EXISTS workflow_translations (
 translation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 entity_kind VARCHAR(30) NOT NULL, entity_id BIGINT UNSIGNED NOT NULL, language_code VARCHAR(10) NOT NULL,
 source_hash CHAR(64) NOT NULL, content_json MEDIUMTEXT NOT NULL, revision INT NOT NULL DEFAULT 1,
 reviewed_by_user_id BIGINT UNSIGNED NOT NULL, reviewed_at DATETIME NOT NULL,
 UNIQUE KEY uq_workflow_translation(company_id,entity_kind,entity_id,language_code)
);
CREATE TABLE IF NOT EXISTS company_workspace_profiles (
 profile_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 display_name VARCHAR(150) NOT NULL, primary_color CHAR(7) NOT NULL, logo_base64 MEDIUMTEXT NULL,
 logo_mime VARCHAR(20) NULL, support_contact VARCHAR(300) NOT NULL, revision INT NOT NULL DEFAULT 1,
 updated_by_user_id BIGINT UNSIGNED NOT NULL, updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_workspace_company(company_id)
);
CREATE TABLE IF NOT EXISTS company_setup_checks (
 check_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 check_code VARCHAR(40) NOT NULL, evidence_reference VARCHAR(1000) NOT NULL,
 completed_by_user_id BIGINT UNSIGNED NOT NULL, completed_at DATETIME NOT NULL,
 UNIQUE KEY uq_company_setup(company_id,check_code)
);
CREATE TABLE IF NOT EXISTS subscription_change_requests (
 request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 target_plan VARCHAR(30) NOT NULL, notes VARCHAR(1000) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'REQUESTED',
 requested_by_user_id BIGINT UNSIGNED NOT NULL, requested_at DATETIME NOT NULL,
 cancelled_at DATETIME NULL
);
