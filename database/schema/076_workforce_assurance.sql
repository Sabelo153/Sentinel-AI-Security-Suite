USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS learning_courses (
 course_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(150) NOT NULL, lesson MEDIUMTEXT NOT NULL, quiz_json MEDIUMTEXT NOT NULL, pass_percent INT NOT NULL, valid_days INT NOT NULL,
 max_attempts INT NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'DRAFT', content_hash CHAR(64) NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL, published_at DATETIME NULL, INDEX ix_learning_site(company_id,site_id,state)
);
CREATE TABLE IF NOT EXISTS learning_assignments (
 assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, course_id BIGINT UNSIGNED NOT NULL,
 employee_id BIGINT UNSIGNED NOT NULL, due_on DATE NOT NULL, read_at DATETIME NULL, state VARCHAR(20) NOT NULL DEFAULT 'ASSIGNED',
 assigned_by_user_id BIGINT UNSIGNED NOT NULL, assigned_at DATETIME NOT NULL, UNIQUE KEY uq_learning_employee(company_id,course_id,employee_id)
);
CREATE TABLE IF NOT EXISTS learning_attempts (
 attempt_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, assignment_id BIGINT UNSIGNED NOT NULL,
 operation_uuid CHAR(36) NOT NULL, request_hash CHAR(64) NOT NULL, score_percent INT NOT NULL, passed INT NOT NULL,
 submitted_by_user_id BIGINT UNSIGNED NOT NULL, submitted_at DATETIME NOT NULL, UNIQUE KEY uq_learning_attempt(company_id,operation_uuid)
);
CREATE TABLE IF NOT EXISTS learning_certificates (
 certificate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, assignment_id BIGINT UNSIGNED NOT NULL,
 certificate_code CHAR(36) NOT NULL, issued_at DATETIME NOT NULL, expires_on DATE NOT NULL, revoked_at DATETIME NULL, revocation_reason VARCHAR(1000) NULL,
 UNIQUE KEY uq_learning_certificate(company_id,assignment_id), UNIQUE KEY uq_certificate_code(certificate_code)
);
CREATE TABLE IF NOT EXISTS found_property (
 property_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 description VARCHAR(2000) NOT NULL, found_location VARCHAR(300) NOT NULL, storage_location VARCHAR(300) NOT NULL, found_at DATETIME NOT NULL,
 photo_base64 MEDIUMTEXT NOT NULL, photo_mime VARCHAR(20) NOT NULL, photo_sha256 CHAR(64) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'STORED',
 revision INT NOT NULL DEFAULT 1, created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 INDEX ix_found_site(company_id,site_id,state)
);
CREATE TABLE IF NOT EXISTS found_property_events (
 event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, property_id BIGINT UNSIGNED NOT NULL,
 event_type VARCHAR(20) NOT NULL, notes VARCHAR(2000) NOT NULL, claimant_name VARCHAR(150) NULL, signature_base64 MEDIUMTEXT NULL,
 signature_sha256 CHAR(64) NULL, storage_location VARCHAR(300) NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL, occurred_at DATETIME NOT NULL,
 INDEX ix_found_history(company_id,property_id,event_id)
);
CREATE TABLE IF NOT EXISTS investigation_cases (
 case_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, incident_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, investigator_user_id BIGINT UNSIGNED NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 state VARCHAR(30) NOT NULL DEFAULT 'TRIAGE', revision INT NOT NULL DEFAULT 1, due_on DATE NOT NULL, summary TEXT NULL,
 submitted_by_user_id BIGINT UNSIGNED NULL, reviewed_by_user_id BIGINT UNSIGNED NULL, review_hash CHAR(64) NULL,
 hold_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, closed_at DATETIME NULL,
 UNIQUE KEY uq_incident_case(company_id,incident_id), INDEX ix_case_site(company_id,site_id,state)
);
CREATE TABLE IF NOT EXISTS investigation_notes (
 note_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, case_id BIGINT UNSIGNED NOT NULL,
 note_type VARCHAR(20) NOT NULL, notes TEXT NOT NULL, evidence_id BIGINT UNSIGNED NULL, evidence_sha256 CHAR(64) NULL,
 actor_user_id BIGINT UNSIGNED NOT NULL, occurred_at DATETIME NOT NULL, INDEX ix_case_notes(company_id,case_id,note_id)
);
CREATE TABLE IF NOT EXISTS investigation_actions (
 action_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, case_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(300) NOT NULL, owner_user_id BIGINT UNSIGNED NOT NULL, due_on DATE NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OPEN',
 revision INT NOT NULL DEFAULT 1, completion_notes VARCHAR(2000) NULL, completed_by_user_id BIGINT UNSIGNED NULL, completed_at DATETIME NULL,
 INDEX ix_case_actions(company_id,case_id,state)
);
CREATE TABLE IF NOT EXISTS investigation_history (
 history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, case_id BIGINT UNSIGNED NOT NULL,
 state VARCHAR(30) NOT NULL, notes TEXT NOT NULL, snapshot_hash CHAR(64) NULL, actor_user_id BIGINT UNSIGNED NOT NULL,
 occurred_at DATETIME NOT NULL, INDEX ix_case_history(company_id,case_id,history_id)
);
CREATE TABLE IF NOT EXISTS stock_items (
 stock_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 sku VARCHAR(60) NOT NULL, item_name VARCHAR(150) NOT NULL, item_size VARCHAR(30) NOT NULL, category VARCHAR(20) NOT NULL,
 available_quantity INT NOT NULL DEFAULT 0, reorder_level INT NOT NULL, revision INT NOT NULL DEFAULT 1,
 UNIQUE KEY uq_stock_sku(company_id,site_id,sku,item_size)
);
CREATE TABLE IF NOT EXISTS stock_allocations (
 allocation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, stock_id BIGINT UNSIGNED NOT NULL,
 employee_id BIGINT UNSIGNED NOT NULL, quantity INT NOT NULL, returned_quantity INT NOT NULL DEFAULT 0, written_off_quantity INT NOT NULL DEFAULT 0,
 due_at DATETIME NOT NULL, state VARCHAR(30) NOT NULL DEFAULT 'PENDING_ACCEPTANCE', revision INT NOT NULL DEFAULT 1,
 accepted_at DATETIME NULL, signature_base64 MEDIUMTEXT NULL, signature_sha256 CHAR(64) NULL,
 INDEX ix_stock_employee(company_id,employee_id,state)
);
CREATE TABLE IF NOT EXISTS stock_movements (
 movement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, stock_id BIGINT UNSIGNED NOT NULL,
 allocation_id BIGINT UNSIGNED NULL, operation_uuid CHAR(36) NOT NULL, request_hash CHAR(64) NOT NULL, movement_type VARCHAR(20) NOT NULL,
 quantity INT NOT NULL, affected_quantity INT NOT NULL, balance_after INT NOT NULL, notes VARCHAR(2000) NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL,
 occurred_at DATETIME NOT NULL, receipt_json TEXT NOT NULL, UNIQUE KEY uq_stock_operation(company_id,operation_uuid),
 INDEX ix_stock_history(company_id,stock_id,movement_id)
);
