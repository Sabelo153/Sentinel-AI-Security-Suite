-- Defines the database changes in 023_posts.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS posts (
    post_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    contract_id BIGINT UNSIGNED,
    site_id BIGINT UNSIGNED NOT NULL,
    post_name VARCHAR(255) NOT NULL,
    description TEXT,
    gps_lat DECIMAL(10,8),
    gps_long DECIMAL(11,8),
    status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE',
    created_by_user_id BIGINT UNSIGNED,
    updated_by_user_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_post_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_post_contract
        FOREIGN KEY (contract_id)
        REFERENCES contracts(contract_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_post_site
        FOREIGN KEY (site_id)
        REFERENCES sites(site_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_post_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_post_updated_by
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_post_uuid (post_uuid),
    UNIQUE KEY uq_post_site_name (site_id, post_name),
    INDEX idx_post_company_id (company_id),
    INDEX idx_post_contract_id (contract_id),
    INDEX idx_post_site_id (site_id),
    INDEX idx_post_status (status)
);
