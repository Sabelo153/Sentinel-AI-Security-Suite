-- Explicit expected start windows. All times are UTC; adjacent windows are half-open.
USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS patrol_schedule_windows (
    patrol_schedule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    patrol_route_id BIGINT UNSIGNED NOT NULL,
    shift_assignment_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    cancelled_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patrol_window_company FOREIGN KEY (company_id) REFERENCES companies(company_id),
    CONSTRAINT fk_patrol_window_route FOREIGN KEY (patrol_route_id) REFERENCES patrol_routes(patrol_route_id),
    CONSTRAINT fk_patrol_window_shift FOREIGN KEY (shift_assignment_id) REFERENCES shift_assignments(shift_assignment_id),
    INDEX ix_patrol_window_tenant_time (company_id, starts_at, patrol_schedule_id),
    INDEX ix_patrol_window_overlap (company_id, shift_assignment_id, patrol_route_id, ends_at)
);
