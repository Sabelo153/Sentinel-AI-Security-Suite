-- Defines the database changes in 008_users.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    password_last_changed_at TIMESTAMP NULL,
    is_mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_secret VARCHAR(255),
    failed_login_attempts INT UNSIGNED DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    status ENUM('ACTIVE', 'LOCKED', 'DISABLED', 'ARCHIVED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_users_user_uuid (user_uuid),
    UNIQUE KEY uq_users_company_username (company_id, username),
    UNIQUE KEY uq_users_company_email (company_id, email),
    UNIQUE KEY uq_users_employee_id (employee_id),
    INDEX idx_users_company_id (company_id),
    INDEX idx_users_employee_id (employee_id),
    INDEX idx_users_status (status)
);
