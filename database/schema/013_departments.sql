-- Defines the database changes in 013_departments.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS departments (
    department_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED,
    department_name VARCHAR(150) NOT NULL,
    department_code VARCHAR(50),
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_department_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_department_client
        FOREIGN KEY (client_id)
        REFERENCES clients(client_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_department_branch
        FOREIGN KEY (branch_id)
        REFERENCES branches(branch_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_departments_company_code (company_id, department_code),
    UNIQUE KEY uq_departments_company_name (company_id, department_name),
    INDEX idx_departments_company_id (company_id),
    INDEX idx_departments_client_id (client_id),
    INDEX idx_departments_branch_id (branch_id),
    INDEX idx_departments_status (status)
);
