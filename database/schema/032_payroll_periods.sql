-- Defines the database changes in 032_payroll_periods.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS payroll_periods (
    payroll_period_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    period_code VARCHAR(30) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    payroll_date DATE NOT NULL,
    status ENUM('OPEN', 'LOCKED', 'EXPORTED', 'CLOSED') DEFAULT 'OPEN',
    approved_by_user_id BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payroll_periods_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payroll_periods_approved_by
        FOREIGN KEY (approved_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_payroll_periods_company_code (company_id, period_code),
    INDEX idx_payroll_periods_company_id (company_id),
    INDEX idx_payroll_periods_status (status),
    INDEX idx_payroll_periods_dates (period_start, period_end)
);

CREATE TABLE IF NOT EXISTS payroll_export_batches (
    payroll_export_batch_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    payroll_period_id BIGINT UNSIGNED NOT NULL,
    export_format VARCHAR(50) NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    file_path VARCHAR(255) NULL,
    record_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('PENDING', 'GENERATED', 'FAILED') DEFAULT 'PENDING',
    generated_by_user_id BIGINT UNSIGNED NULL,
    generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payroll_export_batches_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payroll_export_batches_period
        FOREIGN KEY (payroll_period_id)
        REFERENCES payroll_periods(payroll_period_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_payroll_export_batches_generated_by
        FOREIGN KEY (generated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_payroll_export_batch_checksum (company_id, checksum_sha256),
    INDEX idx_payroll_export_batches_company_id (company_id),
    INDEX idx_payroll_export_batches_period_id (payroll_period_id),
    INDEX idx_payroll_export_batches_status (status)
);