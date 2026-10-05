-- Defines the database changes in 005_sites.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS sites (
    site_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    site_name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    status ENUM('ONBOARDING', 'ACTIVE', 'SUSPENDED', 'CONTRACT_EXPIRED', 'CLOSED', 'INACTIVE') DEFAULT 'ONBOARDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_site_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_site_client
        FOREIGN KEY (client_id)
        REFERENCES clients(client_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_site_branch
        FOREIGN KEY (branch_id)
        REFERENCES branches(branch_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_sites_branch_name (branch_id, site_name),
    INDEX idx_sites_company_id (company_id),
    INDEX idx_sites_client_id (client_id),
    INDEX idx_sites_branch_id (branch_id),
    INDEX idx_sites_status (status)
);
