-- Defines the database changes in 015_company_settings.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS company_settings (
    company_setting_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    timezone VARCHAR(100) DEFAULT 'Africa/Johannesburg',
    time_format ENUM('12H', '24H') DEFAULT '24H',
    language_code VARCHAR(10) DEFAULT 'en',
    theme ENUM('LIGHT', 'DARK', 'SYSTEM') DEFAULT 'LIGHT',
    gps_enabled BOOLEAN DEFAULT TRUE,
    ai_enabled BOOLEAN DEFAULT TRUE,
    settings_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_company_settings_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_company_settings_company_id (company_id),
    INDEX idx_company_settings_company_id (company_id)
);
