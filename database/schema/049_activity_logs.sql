USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS activity_logs (
    activity_log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    employee_id BIGINT UNSIGNED NULL,
    business_object_id BIGINT UNSIGNED NULL,
    activity_name VARCHAR(120) NOT NULL,
    activity_category VARCHAR(80) NOT NULL,
    activity_status ENUM('SUCCESS', 'FAILED', 'DENIED') DEFAULT 'SUCCESS',
    source_type ENUM('WEB', 'MOBILE', 'API', 'SYSTEM') DEFAULT 'SYSTEM',
    source_reference_id VARCHAR(120) NULL,
    activity_payload JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_activity_logs_user_id_users
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_activity_logs_employee_id_employees
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_activity_logs_business_object_id_business_objects
        FOREIGN KEY (business_object_id)
        REFERENCES business_objects(business_object_id)
        ON DELETE SET NULL,
    INDEX ix_activity_logs_company_id_occurred_at (company_id, occurred_at),
    INDEX ix_activity_logs_user_id_occurred_at (user_id, occurred_at),
    INDEX ix_activity_logs_employee_id_occurred_at (employee_id, occurred_at),
    INDEX ix_activity_logs_activity_name (activity_name),
    INDEX ix_activity_logs_activity_category (activity_category)
);
