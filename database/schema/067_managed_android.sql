USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS managed_android (
 managed_device_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 device_name VARCHAR(120) NOT NULL, enrollment_hash CHAR(64) NULL, enrollment_expires_at DATETIME NOT NULL,
 installation_id CHAR(36) NULL, device_token_hash CHAR(64) NULL, enrolled_user_id BIGINT UNSIGNED NULL,
 policy_json TEXT NOT NULL, revision INT NOT NULL DEFAULT 1, recovery_salt CHAR(32) NOT NULL,
 recovery_hash CHAR(64) NOT NULL, last_seen_at DATETIME NULL, reported_revision INT NOT NULL DEFAULT 0,
 reported_state VARCHAR(40) NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_managed_installation(company_id,installation_id),
 INDEX ix_managed_company(company_id,managed_device_id)
);
