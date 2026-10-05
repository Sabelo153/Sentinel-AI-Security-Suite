USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS device_assignments (
    device_assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, device_id BIGINT UNSIGNED NOT NULL, employee_id BIGINT UNSIGNED NOT NULL, employee_assignment_id BIGINT UNSIGNED NULL,
    starts_at TIMESTAMP NOT NULL, ends_at TIMESTAMP NULL, assignment_status ENUM('PLANNED','ACTIVE','ENDED','REVOKED') NOT NULL DEFAULT 'PLANNED', assigned_by_user_id BIGINT UNSIGNED NULL, returned_at TIMESTAMP NULL, notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_device_assignments_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_device_assignments_device FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_assignments_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_assignments_employee_assignment FOREIGN KEY (employee_assignment_id) REFERENCES employee_assignments(employee_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_assignments_assigned_by FOREIGN KEY (assigned_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_device_assignments_company_status (company_id,assignment_status), INDEX idx_device_assignments_company_device (company_id,device_id), INDEX idx_device_assignments_company_employee (company_id,employee_id), INDEX idx_device_assignments_period (starts_at,ends_at)
);