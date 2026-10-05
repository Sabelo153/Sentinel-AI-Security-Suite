-- Defines append-only checkpoint events for patrol execution sessions.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS patrol_checkpoint_events (
    patrol_checkpoint_event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patrol_checkpoint_event_uuid CHAR(36) NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    patrol_session_id BIGINT UNSIGNED NOT NULL,
    patrol_checkpoint_id BIGINT UNSIGNED NOT NULL,
    event_type ENUM('VISITED', 'SKIPPED', 'FAILED')
        NOT NULL DEFAULT 'VISITED',
    recorded_at DATETIME NOT NULL,
    event_notes VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_patrol_checkpoint_event_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_patrol_checkpoint_event_session
        FOREIGN KEY (patrol_session_id)
        REFERENCES patrol_sessions(patrol_session_id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_patrol_checkpoint_event_checkpoint
        FOREIGN KEY (patrol_checkpoint_id)
        REFERENCES patrol_checkpoints(patrol_checkpoint_id)
        ON DELETE RESTRICT,

    UNIQUE KEY uq_patrol_checkpoint_event_uuid
        (patrol_checkpoint_event_uuid),

    INDEX idx_patrol_checkpoint_event_company_session
        (company_id, patrol_session_id),

    INDEX idx_patrol_checkpoint_event_company_checkpoint
        (company_id, patrol_checkpoint_id),

    INDEX idx_patrol_checkpoint_event_company_recorded_at
        (company_id, recorded_at),

    INDEX idx_patrol_checkpoint_event_company_type
        (company_id, event_type)
);
