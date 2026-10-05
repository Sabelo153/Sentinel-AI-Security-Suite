-- Defines the database changes in 016_ai_requests.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS ai_requests (
    request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    request_type VARCHAR(100) NOT NULL,
    input_payload JSON,
    status ENUM('PENDING', 'QUEUED', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELLED') DEFAULT 'PENDING',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_requests_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ai_requests_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ai_requests_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    INDEX idx_ai_requests_company_id (company_id),
    INDEX idx_ai_requests_employee_id (employee_id),
    INDEX idx_ai_requests_user_id (user_id),
    INDEX idx_ai_requests_status (status),
    INDEX idx_ai_requests_requested_at (requested_at)
);
