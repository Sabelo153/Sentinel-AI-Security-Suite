-- Defines the canonical patrol execution session table.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS patrol_sessions (
    patrol_session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patrol_session_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    patrol_route_id BIGINT UNSIGNED NOT NULL,
    employee_assignment_id BIGINT UNSIGNED NOT NULL,
    shift_assignment_id BIGINT UNSIGNED NULL,
    status ENUM('IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'ABORTED')
        NOT NULL DEFAULT 'IN_PROGRESS',
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    ended_at DATETIME NULL,
    execution_notes VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    CONSTRAINT fk_patrol_session_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_patrol_session_route
        FOREIGN KEY (patrol_route_id)
        REFERENCES patrol_routes(patrol_route_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_patrol_session_employee_assignment
        FOREIGN KEY (employee_assignment_id)
        REFERENCES employee_assignments(employee_assignment_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_patrol_session_shift_assignment
        FOREIGN KEY (shift_assignment_id)
        REFERENCES shift_assignments(shift_assignment_id)
        ON DELETE SET NULL,

    UNIQUE KEY uq_patrol_session_uuid (patrol_session_uuid),

    INDEX idx_patrol_session_company_status
        (company_id, status),

    INDEX idx_patrol_session_company_employee_assignment
        (company_id, employee_assignment_id),

    INDEX idx_patrol_session_company_route
        (company_id, patrol_route_id),

    INDEX idx_patrol_session_company_started_at
        (company_id, started_at),

    INDEX idx_patrol_session_company_shift
        (company_id, shift_assignment_id),

    INDEX idx_patrol_session_company_deleted_at
        (company_id, deleted_at)
);
