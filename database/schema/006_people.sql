-- Defines the database changes in 006_people.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS people (
    person_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    id_number VARCHAR(30),
    passport_number VARCHAR(30),
    phone VARCHAR(30),
    email VARCHAR(255),
    gender ENUM('MALE', 'FEMALE', 'OTHER'),
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_people_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_people_company_id_number (company_id, id_number),
    UNIQUE KEY uq_people_company_passport_number (company_id, passport_number),
    UNIQUE KEY uq_people_company_email (company_id, email),
    INDEX idx_people_company_id (company_id),
    INDEX idx_people_name (last_name, first_name)
);
