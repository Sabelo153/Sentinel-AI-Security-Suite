USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS device_sessions (
    device_session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, device_id BIGINT UNSIGNED NOT NULL, device_assignment_id BIGINT UNSIGNED NULL, employee_id BIGINT UNSIGNED NOT NULL, employee_assignment_id BIGINT UNSIGNED NULL, shift_assignment_id BIGINT UNSIGNED NULL, site_id BIGINT UNSIGNED NULL,
    session_status ENUM('ACTIVE','ENDED','EXPIRED','REVOKED') NOT NULL DEFAULT 'ACTIVE', started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ended_at TIMESTAMP NULL, last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_device_sessions_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_device_sessions_device FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_sessions_assignment FOREIGN KEY (device_assignment_id) REFERENCES device_assignments(device_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_sessions_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE RESTRICT,
    CONSTRAINT fk_device_sessions_employee_assignment FOREIGN KEY (employee_assignment_id) REFERENCES employee_assignments(employee_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_sessions_shift FOREIGN KEY (shift_assignment_id) REFERENCES shift_assignments(shift_assignment_id) ON DELETE SET NULL,
    CONSTRAINT fk_device_sessions_site FOREIGN KEY (site_id) REFERENCES sites(site_id) ON DELETE SET NULL,
    INDEX idx_device_sessions_company_status (company_id,session_status), INDEX idx_device_sessions_company_device (company_id,device_id), INDEX idx_device_sessions_company_employee (company_id,employee_id), INDEX idx_device_sessions_company_last_seen (company_id,last_seen_at)
);