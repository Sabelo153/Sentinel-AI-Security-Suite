USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS business_object_types (
    business_object_type_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(80) NOT NULL,
    type_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    is_gps_enabled BOOLEAN DEFAULT FALSE,
    is_ai_enabled BOOLEAN DEFAULT FALSE,
    object_type_status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_business_object_types_type_code (type_code),
    INDEX ix_business_object_types_object_type_status (object_type_status)
);
