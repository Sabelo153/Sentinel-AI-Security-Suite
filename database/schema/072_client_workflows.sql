USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS user_site_grants (
 grant_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL, granted_by_user_id BIGINT UNSIGNED NOT NULL,
 granted_at DATETIME NOT NULL, UNIQUE KEY uq_user_site_grant(company_id,user_id,site_id)
);
CREATE TABLE IF NOT EXISTS inspection_form_versions (
 form_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, form_code VARCHAR(60) NOT NULL, revision INT NOT NULL,
 title VARCHAR(150) NOT NULL, fields_json TEXT NOT NULL, published_by_user_id BIGINT UNSIGNED NOT NULL,
 published_at DATETIME NOT NULL, UNIQUE KEY uq_inspection_form_revision(company_id,site_id,form_code,revision)
);
CREATE TABLE IF NOT EXISTS inspection_form_submissions (
 submission_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, form_id BIGINT UNSIGNED NOT NULL, operation_uuid CHAR(36) NOT NULL,
 request_hash CHAR(64) NOT NULL, answers_json MEDIUMTEXT NOT NULL, submitted_by_user_id BIGINT UNSIGNED NOT NULL,
 submitted_at DATETIME NOT NULL, UNIQUE KEY uq_inspection_submission(company_id,submitted_by_user_id,operation_uuid)
);
CREATE TABLE IF NOT EXISTS inspection_form_photos (
 photo_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 submission_id BIGINT UNSIGNED NOT NULL, field_key VARCHAR(60) NOT NULL, mime_type VARCHAR(20) NOT NULL,
 image_base64 MEDIUMTEXT NOT NULL, sha256 CHAR(64) NOT NULL
);
CREATE TABLE IF NOT EXISTS inspection_followups (
 followup_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, submission_id BIGINT UNSIGNED NOT NULL, field_key VARCHAR(60) NOT NULL,
 task_name VARCHAR(200) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OPEN', resolution VARCHAR(1000) NULL,
 resolved_by_user_id BIGINT UNSIGNED NULL, resolved_at DATETIME NULL
);
CREATE TABLE IF NOT EXISTS client_requests (
 request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL, request_type VARCHAR(30) NOT NULL,
 title VARCHAR(150) NOT NULL, description TEXT NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OPEN',
 revision INT NOT NULL DEFAULT 1, related_report_id BIGINT UNSIGNED NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, response_due_at DATETIME NOT NULL,
    first_response_at DATETIME NULL,
 resolved_at DATETIME NULL, confirmed_at DATETIME NULL, confirmed_by_user_id BIGINT UNSIGNED NULL
);
CREATE TABLE IF NOT EXISTS client_request_events (
 event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 request_id BIGINT UNSIGNED NOT NULL, event_type VARCHAR(30) NOT NULL, audience VARCHAR(10) NOT NULL,
 message TEXT NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL, actor_kind VARCHAR(10) NOT NULL,
 occurred_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS employee_availability (
 availability_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 employee_id BIGINT UNSIGNED NOT NULL, request_type VARCHAR(20) NOT NULL, starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL, reason VARCHAR(1000) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'REQUESTED',
 revision INT NOT NULL DEFAULT 1, created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 reviewed_by_user_id BIGINT UNSIGNED NULL, reviewed_at DATETIME NULL, review_notes VARCHAR(1000) NULL,
 INDEX ix_availability_period(company_id,employee_id,starts_at,ends_at,state)
);
CREATE TABLE IF NOT EXISTS client_report_reviews (
 review_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 report_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, report_hash CHAR(64) NOT NULL,
 acknowledged_at DATETIME NOT NULL, UNIQUE KEY uq_client_report_review(company_id,report_id,user_id,report_hash)
);
CREATE TABLE IF NOT EXISTS service_quotations (
 quotation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL, proposed_site_name VARCHAR(150) NOT NULL, title VARCHAR(150) NOT NULL,
 currency CHAR(3) NOT NULL, lines_json TEXT NOT NULL, subtotal_minor BIGINT UNSIGNED NOT NULL,
 tax_basis_points INT NOT NULL, tax_minor BIGINT UNSIGNED NOT NULL, total_minor BIGINT UNSIGNED NOT NULL,
 terms TEXT NOT NULL, valid_until DATE NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
 revision INT NOT NULL DEFAULT 1, shared_hash CHAR(64) NULL, shared_at DATETIME NULL,
 accepted_at DATETIME NULL, accepted_by_user_id BIGINT UNSIGNED NULL, decision_notes VARCHAR(1000) NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS site_onboardings (
 onboarding_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 quotation_id BIGINT UNSIGNED NOT NULL, contract_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 conversion_hash CHAR(64) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'PREPARING',
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 activated_by_user_id BIGINT UNSIGNED NULL, activated_at DATETIME NULL,
 UNIQUE KEY uq_quotation_onboarding(company_id,quotation_id)
);
CREATE TABLE IF NOT EXISTS mobilisation_tasks (
 task_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 onboarding_id BIGINT UNSIGNED NOT NULL, task_name VARCHAR(200) NOT NULL, revision INT NOT NULL DEFAULT 1,
 completed_at DATETIME NULL, completed_by_user_id BIGINT UNSIGNED NULL, evidence_reference VARCHAR(1000) NULL
);
