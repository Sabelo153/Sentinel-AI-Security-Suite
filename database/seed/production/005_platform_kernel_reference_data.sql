USE sentinel_ai_security;

-- Authority levels used by assignment and delegation workflows.
INSERT INTO authority_levels (
    authority_code,
    authority_name,
    rank_value,
    can_approve_shifts,
    can_approve_leave,
    can_escalate_incidents,
    can_dispatch_resources,
    can_view_cross_site_data,
    authority_status
)
VALUES
    ('GUARD', 'Guard', 10, FALSE, FALSE, FALSE, FALSE, FALSE, 'ACTIVE'),
    ('TEAM_LEAD', 'Team Lead', 20, TRUE, FALSE, TRUE, FALSE, FALSE, 'ACTIVE'),
    ('SUPERVISOR', 'Supervisor', 30, TRUE, TRUE, TRUE, TRUE, TRUE, 'ACTIVE'),
    ('CONTROL_ROOM_OPERATOR', 'Control Room Operator', 40, FALSE, FALSE, TRUE, TRUE, TRUE, 'ACTIVE'),
    ('OPERATIONS_MANAGER', 'Operations Manager', 50, TRUE, TRUE, TRUE, TRUE, TRUE, 'ACTIVE')
ON DUPLICATE KEY UPDATE
    authority_name = VALUES(authority_name),
    can_approve_shifts = VALUES(can_approve_shifts),
    can_approve_leave = VALUES(can_approve_leave),
    can_escalate_incidents = VALUES(can_escalate_incidents),
    can_dispatch_resources = VALUES(can_dispatch_resources),
    can_view_cross_site_data = VALUES(can_view_cross_site_data),
    authority_status = VALUES(authority_status),
    updated_at = CURRENT_TIMESTAMP;

-- Universal business object categories used by applications.
INSERT INTO business_object_types (
    type_code,
    type_name,
    description,
    is_gps_enabled,
    is_ai_enabled,
    object_type_status
)
VALUES
    ('incident', 'Incident', 'Security incidents reported by guards or systems.', FALSE, TRUE, 'ACTIVE'),
    ('patrol', 'Patrol', 'Patrol execution and verification records.', TRUE, TRUE, 'ACTIVE'),
    ('checkpoint', 'Checkpoint', 'QR/NFC patrol checkpoints.', TRUE, FALSE, 'ACTIVE'),
    ('site', 'Site', 'Operational client site object.', TRUE, FALSE, 'ACTIVE'),
    ('shift', 'Shift', 'Shift execution object tied to assignment and attendance.', TRUE, TRUE, 'ACTIVE'),
    ('assignment', 'Assignment', 'Employee assignment object with scope and authority.', FALSE, FALSE, 'ACTIVE'),
    ('inspection', 'Inspection', 'Supervisor and compliance inspections.', TRUE, TRUE, 'ACTIVE'),
    ('vehicle', 'Vehicle', 'Fleet movement and compliance object.', TRUE, TRUE, 'ACTIVE')
ON DUPLICATE KEY UPDATE
    type_name = VALUES(type_name),
    description = VALUES(description),
    is_gps_enabled = VALUES(is_gps_enabled),
    is_ai_enabled = VALUES(is_ai_enabled),
    object_type_status = VALUES(object_type_status),
    updated_at = CURRENT_TIMESTAMP;

-- Global status catalog for UBO lifecycle.
INSERT INTO object_statuses (
    business_object_type_id,
    status_code,
    status_name,
    status_group,
    sequence_no,
    is_terminal,
    is_active
)
VALUES
    (NULL, 'draft', 'Draft', 'INITIAL', 10, FALSE, TRUE),
    (NULL, 'open', 'Open', 'IN_PROGRESS', 20, FALSE, TRUE),
    (NULL, 'in_review', 'In Review', 'IN_PROGRESS', 30, FALSE, TRUE),
    (NULL, 'escalated', 'Escalated', 'IN_PROGRESS', 40, FALSE, TRUE),
    (NULL, 'resolved', 'Resolved', 'TERMINAL', 90, TRUE, TRUE),
    (NULL, 'closed', 'Closed', 'TERMINAL', 100, TRUE, TRUE),
    (NULL, 'cancelled', 'Cancelled', 'TERMINAL', 110, TRUE, TRUE)
ON DUPLICATE KEY UPDATE
    status_name = VALUES(status_name),
    status_group = VALUES(status_group),
    sequence_no = VALUES(sequence_no),
    is_terminal = VALUES(is_terminal),
    is_active = VALUES(is_active),
    updated_at = CURRENT_TIMESTAMP;
