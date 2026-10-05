USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS object_statuses (
    object_status_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_object_type_id BIGINT UNSIGNED NULL,
    status_code VARCHAR(80) NOT NULL,
    status_name VARCHAR(120) NOT NULL,
    status_group ENUM('INITIAL', 'IN_PROGRESS', 'TERMINAL') DEFAULT 'IN_PROGRESS',
    sequence_no INT UNSIGNED DEFAULT 100,
    is_terminal BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_object_statuses_business_object_type_id_business_object_types
        FOREIGN KEY (business_object_type_id)
        REFERENCES business_object_types(business_object_type_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_object_statuses_type_id_status_code (business_object_type_id, status_code),
    INDEX ix_object_statuses_status_group (status_group),
    INDEX ix_object_statuses_is_active (is_active)
);
