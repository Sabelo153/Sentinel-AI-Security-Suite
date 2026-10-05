USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS assignment_scopes (
    assignment_scope_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_assignment_id BIGINT UNSIGNED NOT NULL,
    scope_type ENUM('COMPANY', 'CLIENT', 'BRANCH', 'SITE', 'ZONE', 'ROUTE', 'CHECKPOINT', 'GEOFENCE', 'CUSTOM') NOT NULL,
    scope_reference_type VARCHAR(50) NOT NULL,
    scope_reference_id BIGINT UNSIGNED NOT NULL,
    scope_status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    valid_from TIMESTAMP NULL,
    valid_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_assignment_scopes_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_assignment_scopes_employee_assignment_id_employee_assignments
        FOREIGN KEY (employee_assignment_id)
        REFERENCES employee_assignments(employee_assignment_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_assignment_scopes_assignment_scope (employee_assignment_id, scope_type, scope_reference_type, scope_reference_id, deleted_at),
    INDEX ix_assignment_scopes_company_id (company_id),
    INDEX ix_assignment_scopes_scope_reference (scope_reference_type, scope_reference_id),
    INDEX ix_assignment_scopes_scope_status (scope_status)
);
