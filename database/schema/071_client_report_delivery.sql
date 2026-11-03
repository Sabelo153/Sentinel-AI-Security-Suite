USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS client_report_schedules (
 schedule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL, time_zone VARCHAR(80) NOT NULL, times_json TEXT NOT NULL,
 enabled INT NOT NULL DEFAULT 1, auto_share_approved INT NOT NULL DEFAULT 0,
 revision INT NOT NULL DEFAULT 1, next_due_at DATETIME NOT NULL, last_due_at DATETIME NULL,
 updated_by_user_id BIGINT UNSIGNED NOT NULL, updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_client_report_schedule(company_id,client_id)
);
CREATE TABLE IF NOT EXISTS client_report_runs (
 run_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 schedule_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL,
 slot_at DATETIME NOT NULL, period_from DATETIME NOT NULL, period_to DATETIME NOT NULL,
 generated_at DATETIME NOT NULL, portal_report_ids TEXT NOT NULL, email_subject VARCHAR(200) NOT NULL,
 email_body MEDIUMTEXT NOT NULL, UNIQUE KEY uq_client_report_slot(company_id,schedule_id,slot_at)
);
CREATE TABLE IF NOT EXISTS client_report_outbox (
 delivery_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 run_id BIGINT UNSIGNED NOT NULL, recipient_user_id BIGINT UNSIGNED NOT NULL, recipient_email VARCHAR(255) NOT NULL,
 state VARCHAR(30) NOT NULL DEFAULT 'PENDING', attempts INT NOT NULL DEFAULT 0,
 attempted_at DATETIME NULL, accepted_at DATETIME NULL, status_message VARCHAR(500) NULL,
 UNIQUE KEY uq_client_report_email(company_id,run_id,recipient_user_id)
);
