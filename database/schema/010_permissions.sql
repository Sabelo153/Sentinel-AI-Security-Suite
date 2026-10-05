-- Defines the database changes in 010_permissions.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS permissions (
    permission_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    permission_code VARCHAR(150) NOT NULL,
    permission_name VARCHAR(150) NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    action_name VARCHAR(50) NOT NULL,
    permission_description TEXT,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_permission_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_permissions_company_code (company_id, permission_code),
    UNIQUE KEY uq_permissions_company_module_action (company_id, module_name, action_name),
    INDEX idx_permissions_company_id (company_id),
    INDEX idx_permissions_status (status)
);
