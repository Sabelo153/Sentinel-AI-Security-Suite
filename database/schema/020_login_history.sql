-- Defines the database changes in 020_login_history.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS login_history (
    login_history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    auth_method ENUM('PASSWORD', 'MFA', 'SSO', 'API_TOKEN') DEFAULT 'PASSWORD',
    login_status ENUM('SUCCESS', 'FAILED', 'LOCKED') NOT NULL,
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_history_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_login_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    INDEX idx_login_history_company_id (company_id),
    INDEX idx_login_history_user_id (user_id),
    INDEX idx_login_history_login_at (login_at),
    INDEX idx_login_history_status (login_status)
);
