-- Approved canonical device asset schema; does not migrate legacy guard_device_sessions data.
USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS devices (
    device_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, device_uuid CHAR(36) NOT NULL, company_id BIGINT UNSIGNED NOT NULL,
    device_name VARCHAR(120) NOT NULL, device_type VARCHAR(50) NOT NULL DEFAULT 'ANDROID', platform VARCHAR(30) NOT NULL DEFAULT 'ANDROID', device_identifier VARCHAR(255) NOT NULL,
    device_status ENUM('REGISTERED','ACTIVE','SUSPENDED','LOST','RETIRED') NOT NULL DEFAULT 'REGISTERED', last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_devices_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    UNIQUE KEY uq_devices_uuid (device_uuid), UNIQUE KEY uq_devices_company_identifier (company_id,device_identifier),
    INDEX idx_devices_company_status (company_id,device_status), INDEX idx_devices_company_last_seen (company_id,last_seen_at), INDEX idx_devices_company_deleted_at (company_id,deleted_at)
);