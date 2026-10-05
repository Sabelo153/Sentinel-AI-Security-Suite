-- Defines the database changes in 027_attendance_add_shift_assignment.

USE sentinel_ai_security;

ALTER TABLE attendance
    ADD COLUMN shift_assignment_id BIGINT UNSIGNED NULL AFTER employee_id,
    ADD COLUMN attendance_ref VARCHAR(30) NULL AFTER attendance_id,
    ADD COLUMN contract_id BIGINT UNSIGNED NULL AFTER company_id,
    ADD COLUMN post_id BIGINT UNSIGNED NULL AFTER site_id,
    ADD COLUMN created_by_user_id BIGINT UNSIGNED NULL AFTER notes,
    ADD COLUMN updated_by_user_id BIGINT UNSIGNED NULL AFTER created_by_user_id,
    ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_by_user_id,
    ADD INDEX idx_attendance_shift_assignment_id (shift_assignment_id),
    ADD INDEX idx_attendance_contract_id (contract_id),
    ADD INDEX idx_attendance_post_id (post_id),
    ADD UNIQUE KEY uq_attendance_ref (attendance_ref),
    ADD CONSTRAINT fk_attendance_shift_assignment
        FOREIGN KEY (shift_assignment_id)
        REFERENCES shift_assignments(shift_assignment_id)
        ON DELETE SET NULL,
    ADD CONSTRAINT fk_attendance_contract
        FOREIGN KEY (contract_id)
        REFERENCES contracts(contract_id)
        ON DELETE SET NULL,
    ADD CONSTRAINT fk_attendance_post
        FOREIGN KEY (post_id)
        REFERENCES posts(post_id)
        ON DELETE SET NULL,
    ADD CONSTRAINT fk_attendance_created_by
        FOREIGN KEY (created_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    ADD CONSTRAINT fk_attendance_updated_by
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL;
