-- Defines the database changes in 031_finance_journals.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS finance_journals (
    journal_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    journal_number VARCHAR(50) NOT NULL,
    journal_date DATE NOT NULL,
    source_module VARCHAR(100) NOT NULL,
    source_reference VARCHAR(120) NULL,
    description TEXT,
    total_debit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_credit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    status ENUM('DRAFT', 'POSTED', 'REVERSED') DEFAULT 'DRAFT',
    created_by_user_id BIGINT UNSIGNED NULL,
    posted_by_user_id BIGINT UNSIGNED NULL,
    posted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_finance_journals_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_finance_journals_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_finance_journals_posted_by
        FOREIGN KEY (posted_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_finance_journals_company_number (company_id, journal_number),
    INDEX idx_finance_journals_company_id (company_id),
    INDEX idx_finance_journals_status (status),
    INDEX idx_finance_journals_date (journal_date)
);

CREATE TABLE IF NOT EXISTS finance_journal_lines (
    journal_line_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journal_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    line_number INT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    entry_description VARCHAR(255) NULL,
    debit_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    branch_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_finance_journal_lines_journal
        FOREIGN KEY (journal_id)
        REFERENCES finance_journals(journal_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_finance_journal_lines_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_finance_journal_lines_account
        FOREIGN KEY (account_id)
        REFERENCES finance_chart_of_accounts(account_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_finance_journal_lines_branch
        FOREIGN KEY (branch_id)
        REFERENCES branches(branch_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_finance_journal_lines_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_finance_journal_lines_unique (journal_id, line_number),
    INDEX idx_finance_journal_lines_company_id (company_id),
    INDEX idx_finance_journal_lines_account_id (account_id)
);