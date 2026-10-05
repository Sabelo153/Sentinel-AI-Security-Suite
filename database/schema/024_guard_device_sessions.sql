-- Defines the database changes in 024_guard_device_sessions.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS guard_device_sessions (
    session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    guard_id BIGINT UNSIGNED NOT NULL,
    assignment_id BIGINT UNSIGNED DEFAULT NULL,
    shift_id BIGINT UNSIGNED DEFAULT NULL,
    device_id VARCHAR(128) NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    last_latitude DECIMAL(10,7) DEFAULT NULL,
    last_longitude DECIMAL(10,7) DEFAULT NULL,
    last_accuracy INT DEFAULT NULL,
    last_location_at TIMESTAMP NULL,
    battery_percent TINYINT DEFAULT NULL,
    charging TINYINT(1) DEFAULT NULL,
    network_status VARCHAR(50) DEFAULT NULL,
    last_heartbeat_at TIMESTAMP NULL,
    device_status VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gds_company (company_id),
    INDEX idx_gds_device (device_id),
    INDEX idx_gds_guard (guard_id),
    INDEX idx_gds_shift (shift_id)
);
