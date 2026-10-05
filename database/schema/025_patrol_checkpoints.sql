-- Defines the database changes in 025_patrol_checkpoints.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS patrol_checkpoints (
    patrol_checkpoint_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patrol_checkpoint_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    patrol_route_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED,
    checkpoint_name VARCHAR(255) NOT NULL,
    checkpoint_order INT UNSIGNED NOT NULL,
    qr_code VARCHAR(255),
    nfc_tag VARCHAR(255),
    gps_lat DECIMAL(10,8),
    gps_long DECIMAL(11,8),
    expected_interval_minutes INT UNSIGNED,
    status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE',
    created_by_user_id BIGINT UNSIGNED,
    updated_by_user_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_patrol_checkpoint_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_patrol_checkpoint_route
        FOREIGN KEY (patrol_route_id)
        REFERENCES patrol_routes(patrol_route_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_patrol_checkpoint_post
        FOREIGN KEY (post_id)
        REFERENCES posts(post_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_patrol_checkpoint_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_patrol_checkpoint_updated_by
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_patrol_checkpoint_uuid (patrol_checkpoint_uuid),
    UNIQUE KEY uq_patrol_checkpoint_route_order (patrol_route_id, checkpoint_order),
    INDEX idx_patrol_checkpoint_company_id (company_id),
    INDEX idx_patrol_checkpoint_route_id (patrol_route_id),
    INDEX idx_patrol_checkpoint_post_id (post_id),
    INDEX idx_patrol_checkpoint_status (status)
);
