USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS delegations (
    delegation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    delegator_assignment_id BIGINT UNSIGNED NOT NULL,
    delegate_assignment_id BIGINT UNSIGNED NOT NULL,
    authority_level_id BIGINT UNSIGNED NOT NULL,
    delegation_reason VARCHAR(255) NULL,
    delegation_status ENUM('PLANNED', 'ACTIVE', 'REVOKED', 'EXPIRED') DEFAULT 'ACTIVE',
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    revoked_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_delegations_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_delegations_delegator_assignment_id_employee_assignments
        FOREIGN KEY (delegator_assignment_id)
        REFERENCES employee_assignments(employee_assignment_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_delegations_delegate_assignment_id_employee_assignments
        FOREIGN KEY (delegate_assignment_id)
        REFERENCES employee_assignments(employee_assignment_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_delegations_authority_level_id_authority_levels
        FOREIGN KEY (authority_level_id)
        REFERENCES authority_levels(authority_level_id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_delegations_revoked_by_user_id_users
        FOREIGN KEY (revoked_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_delegations_unique_window (delegator_assignment_id, delegate_assignment_id, authority_level_id, starts_at),
    INDEX ix_delegations_company_id (company_id),
    INDEX ix_delegations_delegate_assignment_id (delegate_assignment_id),
    INDEX ix_delegations_delegation_status (delegation_status)
);
