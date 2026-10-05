USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS organizational_units (
    organizational_unit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    parent_unit_id BIGINT UNSIGNED NULL,
    unit_code VARCHAR(50) NOT NULL,
    unit_name VARCHAR(255) NOT NULL,
    unit_type ENUM('HQ', 'REGION', 'DISTRICT', 'AREA', 'SITE_CLUSTER', 'SITE') DEFAULT 'AREA',
    unit_status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE',
    manager_employee_id BIGINT UNSIGNED NULL,
    effective_from DATE NULL,
    effective_until DATE NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_organizational_units_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_organizational_units_organization_id_organizations
        FOREIGN KEY (organization_id)
        REFERENCES organizations(organization_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_organizational_units_parent_unit_id_organizational_units
        FOREIGN KEY (parent_unit_id)
        REFERENCES organizational_units(organizational_unit_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_organizational_units_manager_employee_id_employees
        FOREIGN KEY (manager_employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_organizational_units_company_id_unit_code (company_id, unit_code),
    INDEX ix_organizational_units_company_id (company_id),
    INDEX ix_organizational_units_organization_id (organization_id),
    INDEX ix_organizational_units_parent_unit_id (parent_unit_id)
);
