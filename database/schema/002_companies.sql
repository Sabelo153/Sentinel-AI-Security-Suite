-- Defines the database changes in 002_companies.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS companies (
    company_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    registration_number VARCHAR(100),
    vat_number VARCHAR(100),
    phone VARCHAR(30),
    email VARCHAR(255),
    website VARCHAR(255),
    logo VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    status ENUM('ACTIVE', 'SUSPENDED', 'ARCHIVED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_companies_name (company_name),
    INDEX idx_companies_status (status)
);
