-- Defines the database changes in 021_contracts.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS contracts (
    contract_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    contract_number VARCHAR(100) NOT NULL,
    contract_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    monthly_value DECIMAL(14,2) DEFAULT 0.00,
    sla_tier VARCHAR(50),
    required_guard_count INT UNSIGNED,
    shift_requirements_json JSON,
    status ENUM('DRAFT', 'ACTIVE', 'SUSPENDED', 'EXPIRED', 'TERMINATED', 'ARCHIVED') DEFAULT 'DRAFT',
    created_by_user_id BIGINT UNSIGNED,
    updated_by_user_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_contract_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_contract_client
        FOREIGN KEY (client_id)
        REFERENCES clients(client_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_contract_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_contract_updated_by
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_contract_uuid (contract_uuid),
    UNIQUE KEY uq_contract_company_number (company_id, contract_number),
    INDEX idx_contract_company_id (company_id),
    INDEX idx_contract_client_id (client_id),
    INDEX idx_contract_status (status),
    INDEX idx_contract_start_end (start_date, end_date)
);
