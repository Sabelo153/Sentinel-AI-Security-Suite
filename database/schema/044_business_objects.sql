USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS business_objects (
    business_object_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    business_object_uuid CHAR(36) NOT NULL,
    business_object_type_id BIGINT UNSIGNED NOT NULL,
    object_status_id BIGINT UNSIGNED NULL,
    source_reference_type VARCHAR(80) NULL,
    source_reference_id BIGINT UNSIGNED NULL,
    object_title VARCHAR(255) NOT NULL,
    object_summary TEXT NULL,
    priority_level ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'MEDIUM',
    risk_score DECIMAL(5,2) NULL,
    owner_employee_id BIGINT UNSIGNED NULL,
    assigned_employee_id BIGINT UNSIGNED NULL,
    opened_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    payload JSON NULL,
    created_by_user_id BIGINT UNSIGNED NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_business_objects_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_business_objects_type
        FOREIGN KEY (business_object_type_id)
        REFERENCES business_object_types(business_object_type_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_business_objects_object_status_id_object_statuses
        FOREIGN KEY (object_status_id)
        REFERENCES object_statuses(object_status_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_business_objects_owner_employee_id_employees
        FOREIGN KEY (owner_employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_business_objects_assigned_employee_id_employees
        FOREIGN KEY (assigned_employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_business_objects_created_by_user_id_users
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_business_objects_updated_by_user_id_users
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_business_objects_business_object_uuid (business_object_uuid),
    INDEX ix_business_objects_company_id (company_id),
    INDEX ix_business_objects_business_object_type_id (business_object_type_id),
    INDEX ix_business_objects_object_status_id (object_status_id),
    INDEX ix_business_objects_opened_at (opened_at),
    INDEX ix_business_objects_closed_at (closed_at)
);
