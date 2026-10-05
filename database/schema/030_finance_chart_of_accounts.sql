-- Defines the database changes in 030_finance_chart_of_accounts.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS finance_chart_of_accounts (
    account_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    account_code VARCHAR(30) NOT NULL,
    account_name VARCHAR(200) NOT NULL,
    account_type ENUM('ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE') NOT NULL,
    parent_account_id BIGINT UNSIGNED NULL,
    is_posting_allowed BOOLEAN DEFAULT TRUE,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_finance_accounts_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_finance_accounts_parent
        FOREIGN KEY (parent_account_id)
        REFERENCES finance_chart_of_accounts(account_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_finance_accounts_company_code (company_id, account_code),
    UNIQUE KEY uq_finance_accounts_company_name (company_id, account_name),
    INDEX idx_finance_accounts_company_id (company_id),
    INDEX idx_finance_accounts_type (account_type),
    INDEX idx_finance_accounts_status (status)
);