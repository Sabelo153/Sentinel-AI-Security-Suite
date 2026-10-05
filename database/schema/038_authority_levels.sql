USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS authority_levels (
    authority_level_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    authority_code VARCHAR(50) NOT NULL,
    authority_name VARCHAR(150) NOT NULL,
    rank_value INT UNSIGNED NOT NULL,
    can_approve_shifts BOOLEAN DEFAULT FALSE,
    can_approve_leave BOOLEAN DEFAULT FALSE,
    can_escalate_incidents BOOLEAN DEFAULT FALSE,
    can_dispatch_resources BOOLEAN DEFAULT FALSE,
    can_view_cross_site_data BOOLEAN DEFAULT FALSE,
    authority_status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_authority_levels_authority_code (authority_code),
    UNIQUE KEY uq_authority_levels_rank_value (rank_value),
    INDEX ix_authority_levels_authority_status (authority_status)
);
