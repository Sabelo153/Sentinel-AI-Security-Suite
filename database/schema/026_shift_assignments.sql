-- Defines the database changes in 026_shift_assignments.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS shift_assignments (
    shift_assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_assignment_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    contract_id BIGINT UNSIGNED,
    site_id BIGINT UNSIGNED,
    post_id BIGINT UNSIGNED,
    assignment_date DATE NOT NULL,
    shift_name VARCHAR(120) NOT NULL,
    shift_start_at DATETIME NOT NULL,
    shift_end_at DATETIME NOT NULL,
    assignment_status ENUM('PLANNED', 'ASSIGNED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'MISSED', 'CANCELLED') DEFAULT 'PLANNED',
    check_in_window_start DATETIME,
    check_in_window_end DATETIME,
    created_by_user_id BIGINT UNSIGNED,
    updated_by_user_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_shift_assignment_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_shift_assignment_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_shift_assignment_contract
        FOREIGN KEY (contract_id)
        REFERENCES contracts(contract_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_shift_assignment_site
        FOREIGN KEY (site_id)
        REFERENCES sites(site_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_shift_assignment_post
        FOREIGN KEY (post_id)
        REFERENCES posts(post_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_shift_assignment_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_shift_assignment_updated_by
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_shift_assignment_uuid (shift_assignment_uuid),
    UNIQUE KEY uq_shift_assignment_employee_start (employee_id, shift_start_at),
    INDEX idx_shift_assignment_company_id (company_id),
    INDEX idx_shift_assignment_contract_id (contract_id),
    INDEX idx_shift_assignment_site_id (site_id),
    INDEX idx_shift_assignment_post_id (post_id),
    INDEX idx_shift_assignment_date (assignment_date),
    INDEX idx_shift_assignment_status (assignment_status)
);
