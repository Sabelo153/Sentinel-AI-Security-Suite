USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS positions (
    position_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NULL,
    organizational_unit_id BIGINT UNSIGNED NULL,
    position_code VARCHAR(50) NOT NULL,
    position_name VARCHAR(150) NOT NULL,
    position_category ENUM('GUARD', 'SUPERVISOR', 'CONTROL_ROOM', 'MANAGEMENT', 'SUPPORT') DEFAULT 'GUARD',
    hierarchy_rank INT UNSIGNED NOT NULL DEFAULT 100,
    position_status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_positions_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_positions_organization_id_organizations
        FOREIGN KEY (organization_id)
        REFERENCES organizations(organization_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_positions_organizational_unit_id_organizational_units
        FOREIGN KEY (organizational_unit_id)
        REFERENCES organizational_units(organizational_unit_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_positions_company_id_position_code (company_id, position_code),
    INDEX ix_positions_company_id (company_id),
    INDEX ix_positions_company_id_position_category (company_id, position_category),
    INDEX ix_positions_organizational_unit_id (organizational_unit_id)
);
