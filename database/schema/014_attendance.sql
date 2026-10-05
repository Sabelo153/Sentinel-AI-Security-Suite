-- Defines the database changes in 014_attendance.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS attendance (
    attendance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED,
    shift_date DATE NOT NULL,
    clock_in_at DATETIME,
    clock_out_at DATETIME,
    attendance_status ENUM('PRESENT', 'LATE', 'ABSENT', 'OFF_DUTY') DEFAULT 'PRESENT',
    source ENUM('MOBILE_APP', 'BIOMETRIC', 'MANUAL', 'API') DEFAULT 'MOBILE_APP',
    notes VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attendance_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_attendance_site
        FOREIGN KEY (site_id)
        REFERENCES sites(site_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_attendance_employee_shift (employee_id, shift_date),
    INDEX idx_attendance_company_id (company_id),
    INDEX idx_attendance_employee_id (employee_id),
    INDEX idx_attendance_site_id (site_id),
    INDEX idx_attendance_shift_date (shift_date),
    INDEX idx_attendance_status (attendance_status)
);
