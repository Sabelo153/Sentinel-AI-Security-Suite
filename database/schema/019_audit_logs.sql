-- Defines the database changes in 019_audit_logs.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS audit_logs (
    audit_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    entity_name VARCHAR(100) NOT NULL,
    entity_id VARCHAR(100),
    action_name VARCHAR(100) NOT NULL,
    action_status ENUM('SUCCESS', 'FAILED', 'DENIED') DEFAULT 'SUCCESS',
    ip_address VARCHAR(45),
    user_agent TEXT,
    old_values JSON,
    new_values JSON,
    metadata JSON,
    occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    INDEX idx_audit_logs_company_id (company_id),
    INDEX idx_audit_logs_user_id (user_id),
    INDEX idx_audit_logs_entity (entity_name, entity_id),
    INDEX idx_audit_logs_action_name (action_name),
    INDEX idx_audit_logs_occurred_at (occurred_at)
);
