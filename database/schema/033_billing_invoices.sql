-- Defines the database changes in 033_billing_invoices.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS billing_invoices (
    invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    contract_id BIGINT UNSIGNED NULL,
    invoice_number VARCHAR(60) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    status ENUM('DRAFT', 'ISSUED', 'PARTIALLY_PAID', 'PAID', 'VOID') DEFAULT 'DRAFT',
    issued_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_billing_invoices_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_billing_invoices_client
        FOREIGN KEY (client_id)
        REFERENCES clients(client_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_billing_invoices_contract
        FOREIGN KEY (contract_id)
        REFERENCES contracts(contract_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_billing_invoices_issued_by
        FOREIGN KEY (issued_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_billing_invoices_company_number (company_id, invoice_number),
    INDEX idx_billing_invoices_company_id (company_id),
    INDEX idx_billing_invoices_client_id (client_id),
    INDEX idx_billing_invoices_status (status),
    INDEX idx_billing_invoices_due_date (due_date)
);

CREATE TABLE IF NOT EXISTS billing_invoice_lines (
    invoice_line_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    line_number INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(18,4) NOT NULL DEFAULT 1.0000,
    unit_price DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    line_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    source_module VARCHAR(100) NULL,
    source_reference VARCHAR(120) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_billing_invoice_lines_invoice
        FOREIGN KEY (invoice_id)
        REFERENCES billing_invoices(invoice_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_billing_invoice_lines_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_billing_invoice_line_unique (invoice_id, line_number),
    INDEX idx_billing_invoice_lines_company_id (company_id)
);