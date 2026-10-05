USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS gps_locations (
    gps_location_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    business_object_id BIGINT UNSIGNED NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    accuracy DECIMAL(8,2) NULL,
    heading DECIMAL(6,2) NULL,
    speed DECIMAL(8,3) NULL,
    altitude DECIMAL(9,2) NULL,
    provider VARCHAR(50) NULL,
    battery_level DECIMAL(5,2) NULL,
    signal_strength DECIMAL(5,2) NULL,
    device_id VARCHAR(120) NULL,
    mock_location BOOLEAN DEFAULT FALSE,
    confidence DECIMAL(5,2) NULL,
    captured_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gps_locations_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_gps_locations_employee_id_employees
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_gps_locations_user_id_users
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_gps_locations_business_object_id_business_objects
        FOREIGN KEY (business_object_id)
        REFERENCES business_objects(business_object_id)
        ON DELETE SET NULL,
    INDEX ix_gps_locations_company_id_captured_at (company_id, captured_at),
    INDEX ix_gps_locations_employee_id_captured_at (employee_id, captured_at),
    INDEX ix_gps_locations_user_id_captured_at (user_id, captured_at),
    INDEX ix_gps_locations_business_object_id_captured_at (business_object_id, captured_at),
    INDEX ix_gps_locations_latitude_longitude (latitude, longitude)
);
