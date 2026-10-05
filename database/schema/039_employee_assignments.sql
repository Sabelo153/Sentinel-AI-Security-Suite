USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS employee_assignments (
    employee_assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    organization_id BIGINT UNSIGNED NULL,
    organizational_unit_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NOT NULL,
    authority_level_id BIGINT UNSIGNED NOT NULL,
    assignment_code VARCHAR(60) NOT NULL,
    assignment_status ENUM('PLANNED', 'ACTIVE', 'SUSPENDED', 'COMPLETED', 'CANCELLED') DEFAULT 'ACTIVE',
    starts_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    is_primary BOOLEAN DEFAULT TRUE,
    assigned_by_user_id BIGINT UNSIGNED NULL,
    assignment_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_employee_assignments_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_employee_assignments_employee_id_employees
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_employee_assignments_user_id_users
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_employee_assignments_organization_id_organizations
        FOREIGN KEY (organization_id)
        REFERENCES organizations(organization_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_employee_assignments_org_unit
        FOREIGN KEY (organizational_unit_id)
        REFERENCES organizational_units(organizational_unit_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_employee_assignments_position_id_positions
        FOREIGN KEY (position_id)
        REFERENCES positions(position_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_employee_assignments_authority_level_id_authority_levels
        FOREIGN KEY (authority_level_id)
        REFERENCES authority_levels(authority_level_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_employee_assignments_assigned_by_user_id_users
        FOREIGN KEY (assigned_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_employee_assignments_assignment_code (assignment_code),
    UNIQUE KEY uq_employee_assignments_employee_id_is_primary_deleted_at (employee_id, is_primary, deleted_at),
    INDEX ix_employee_assignments_company_id (company_id),
    INDEX ix_employee_assignments_user_id (user_id),
    INDEX ix_employee_assignments_assignment_status (assignment_status),
    INDEX ix_employee_assignments_starts_at (starts_at)
);
