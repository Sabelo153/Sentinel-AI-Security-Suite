-- Defines the database changes in 024_patrol_routes.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS patrol_routes (
    patrol_route_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patrol_route_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED,
    route_name VARCHAR(255) NOT NULL,
    route_code VARCHAR(100),
    expected_duration_minutes INT UNSIGNED,
    status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE',
    created_by_user_id BIGINT UNSIGNED,
    updated_by_user_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_patrol_route_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_patrol_route_site
        FOREIGN KEY (site_id)
        REFERENCES sites(site_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_patrol_route_post
        FOREIGN KEY (post_id)
        REFERENCES posts(post_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_patrol_route_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_patrol_route_updated_by
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_patrol_route_uuid (patrol_route_uuid),
    UNIQUE KEY uq_patrol_route_site_code (site_id, route_code),
    INDEX idx_patrol_route_company_id (company_id),
    INDEX idx_patrol_route_site_id (site_id),
    INDEX idx_patrol_route_post_id (post_id),
    INDEX idx_patrol_route_status (status)
);
