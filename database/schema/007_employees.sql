-- Defines the database changes in 007_employees.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS employees (
    employee_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    person_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_number VARCHAR(50),
    psira_number VARCHAR(50),
    hire_date DATE,
    employment_status ENUM('ACTIVE', 'SUSPENDED', 'TERMINATED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_employee_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_employee_person
        FOREIGN KEY (person_id)
        REFERENCES people(person_id)
        ON DELETE RESTRICT,
    UNIQUE KEY uq_employees_company_number (company_id, employee_number),
    UNIQUE KEY uq_employees_company_psira (company_id, psira_number),
    UNIQUE KEY uq_employees_person (person_id),
    INDEX idx_employees_company_id (company_id),
    INDEX idx_employees_status (employment_status)
);
