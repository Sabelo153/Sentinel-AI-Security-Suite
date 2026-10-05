-- Defines the canonical long-term guard-to-site deployment relationship.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS guard_site_assignments (
    guard_site_assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_assignment_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL,
    deployment_status ENUM('PLANNED', 'ACTIVE', 'SUSPENDED', 'ENDED', 'CANCELLED') NOT NULL DEFAULT 'PLANNED',
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NULL,
    assignment_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_guard_site_assignments_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_guard_site_assignments_employee_assignment
        FOREIGN KEY (employee_assignment_id)
        REFERENCES employee_assignments(employee_assignment_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_guard_site_assignments_site
        FOREIGN KEY (site_id)
        REFERENCES sites(site_id)
        ON DELETE RESTRICT,
    INDEX idx_gsa_company_status (company_id, deployment_status),
    INDEX idx_gsa_company_employee_assignment (company_id, employee_assignment_id),
    INDEX idx_gsa_company_site (company_id, site_id),
    INDEX idx_gsa_company_starts_at (company_id, starts_at),
    INDEX idx_gsa_company_ends_at (company_id, ends_at),
    INDEX idx_gsa_company_deleted_at (company_id, deleted_at)
);
