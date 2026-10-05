-- Defines the database changes in 034_employee_profiles.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS employee_profiles (
    employee_profile_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    firearm_competency_number VARCHAR(80) NULL,
    driver_license_number VARCHAR(80) NULL,
    driver_license_expiry_date DATE NULL,
    medical_expiry_date DATE NULL,
    uniform_size VARCHAR(30) NULL,
    bank_name VARCHAR(120) NULL,
    bank_account_number VARCHAR(60) NULL,
    bank_branch_code VARCHAR(30) NULL,
    next_of_kin_name VARCHAR(150) NULL,
    next_of_kin_phone VARCHAR(30) NULL,
    next_of_kin_relationship VARCHAR(80) NULL,
    contract_type VARCHAR(80) NULL,
    qualification_summary TEXT NULL,
    training_summary TEXT NULL,
    certificate_summary TEXT NULL,
    warning_count INT UNSIGNED NOT NULL DEFAULT 0,
    performance_rating DECIMAL(5,2) NULL,
    photo_url VARCHAR(255) NULL,
    documents_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_profiles_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_employee_profiles_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_employee_profiles_employee (employee_id),
    INDEX idx_employee_profiles_company_id (company_id)
);