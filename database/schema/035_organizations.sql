USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS organizations (
    organization_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    organization_code VARCHAR(50) NOT NULL,
    organization_name VARCHAR(255) NOT NULL,
    organization_type ENUM('COMPANY', 'DIVISION', 'CLIENT_ACCOUNT', 'OPERATIONAL_UNIT') DEFAULT 'COMPANY',
    organization_status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE',
    effective_from DATE NULL,
    effective_until DATE NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_organizations_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_organizations_company_id_organization_code (company_id, organization_code),
    INDEX ix_organizations_company_id (company_id),
    INDEX ix_organizations_company_id_organization_status (company_id, organization_status)
);
