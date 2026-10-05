-- ============================================================================
-- Sentinel AI Security Suite - Event System Migration
-- Implements Event Bus (SEB) - Event-Driven Architecture Foundation
-- ============================================================================
-- This migration creates the Event System that enables domain communication
-- without direct coupling. Events are the "nervous system" of Sentinel.
-- 
-- The Event Bus allows:
-- - Domain events to trigger workflows and rules
-- - Asynchronous communication between modules
-- - Complete audit trail of system events
-- - Event replay for recovery and debugging
-- - Multi-subscriber pattern for extensibility
-- 
-- Dependencies: 001_identity_and_access.sql, 003_workflow_engine.sql
-- Status: Production Ready
-- Version: 1.0
-- Date: 2026-07-08
-- ============================================================================

-- ============================================================================
-- 1. DOMAIN_EVENTS - Base table for all domain events
-- ============================================================================
CREATE TABLE domain_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique event ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    event_name VARCHAR(255) NOT NULL COMMENT 'Fully qualified event name (e.g., "attendance.user_clocked_in")',
    event_type ENUM(
        'attendance',
        'incident',
        'visitor',
        'workforce',
        'security',
        'patrol',
        'fleet',
        'asset',
        'fire_safety',
        'reporting',
        'ai_intelligence',
        'system',
        'workflow',
        'user'
    ) NOT NULL COMMENT 'Category of event',
    event_source_domain VARCHAR(100) NOT NULL COMMENT 'Domain that emitted event',
    event_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When event occurred',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When event was recorded in system',
    
    -- Core Event Data
    aggregate_type VARCHAR(100) NOT NULL COMMENT 'Type of entity affected (e.g., "AttendanceRecord")',
    aggregate_id BIGINT NOT NULL COMMENT 'ID of entity affected',
    aggregate_version INT COMMENT 'Version of entity state when event occurred',
    
    -- Event Context
    triggered_by_user_id BIGINT COMMENT 'User ID that triggered event',
    triggered_by_workflow_id BIGINT COMMENT 'Workflow ID that triggered event',
    triggered_by_rule_id BIGINT COMMENT 'Rule ID that triggered event',
    triggered_by_api TINYINT(1) DEFAULT FALSE COMMENT 'Whether event was triggered by API call',
    
    -- Event Payload
    event_data JSON NOT NULL COMMENT 'Full event payload (mutable fields at time of event)',
    event_metadata JSON COMMENT 'Metadata: ip_address, user_agent, api_key_id, request_id, correlation_id',
    
    -- Event Status
    is_processed TINYINT(1) DEFAULT FALSE COMMENT 'Whether event has been processed by handlers',
    processing_status ENUM(
        'queued',
        'processing',
        'completed',
        'failed',
        'dead_letter'
    ) DEFAULT 'queued' COMMENT 'Status in event processing pipeline',
    failed_reason VARCHAR(500) COMMENT 'Reason if event processing failed',
    
    -- Replay Support
    is_replayed TINYINT(1) DEFAULT FALSE COMMENT 'Whether this is a replayed event',
    original_event_id BIGINT COMMENT 'Reference to original event if this is a replay',
    
    -- Indexing
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_event_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_event_triggered_by_user FOREIGN KEY (triggered_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_event_triggered_by_workflow FOREIGN KEY (triggered_by_workflow_id) REFERENCES workflow_definitions(id) ON DELETE SET NULL,
    CONSTRAINT fk_event_original FOREIGN KEY (original_event_id) REFERENCES domain_events(id) ON DELETE SET NULL,
    INDEX idx_company_event (company_id, event_name),
    INDEX idx_event_type (event_type),
    INDEX idx_event_source (event_source_domain),
    INDEX idx_aggregate (aggregate_type, aggregate_id),
    INDEX idx_timestamp (event_timestamp),
    INDEX idx_processing_status (processing_status),
    INDEX idx_created_at (created_at),
    INDEX idx_replay (is_replayed, original_event_id),
    CONSTRAINT chk_event_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 2. EVENT_HANDLERS - Subscriptions to events (workflow triggers, etc)
-- ============================================================================
CREATE TABLE event_handlers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique handler ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    event_name VARCHAR(255) NOT NULL COMMENT 'Event name to subscribe to (can use wildcards)',
    handler_type ENUM(
        'workflow_trigger',
        'rule_evaluation',
        'notification',
        'webhook',
        'integration',
        'custom'
    ) NOT NULL COMMENT 'Type of handler',
    handler_config JSON NOT NULL COMMENT 'Handler-specific configuration',
    
    -- Workflow Trigger Handler
    workflow_id BIGINT COMMENT 'Workflow to trigger (for workflow_trigger type)',
    workflow_auto_execute TINYINT(1) DEFAULT FALSE COMMENT 'Whether to auto-execute workflow',
    
    -- Rule Evaluation Handler
    rule_id BIGINT COMMENT 'Rule to evaluate (for rule_evaluation type)',
    
    -- Webhook Handler
    webhook_url VARCHAR(500) COMMENT 'URL to POST event to',
    webhook_headers JSON COMMENT 'HTTP headers to include',
    webhook_auth_type ENUM('none', 'api_key', 'oauth2', 'basic') COMMENT 'Authentication type for webhook',
    webhook_retry_enabled TINYINT(1) DEFAULT TRUE COMMENT 'Whether to retry on failure',
    webhook_retry_max INT DEFAULT 3 COMMENT 'Maximum retry attempts',
    
    -- Handler Status
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether handler is active',
    execution_priority INT DEFAULT 50 COMMENT 'Priority (1-100, higher = execute first)',
    async_execution TINYINT(1) DEFAULT TRUE COMMENT 'Whether to execute asynchronously',
    
    -- Filtering
    event_filter JSON COMMENT 'JSON filter to only execute on matching events',
    
    -- Audit
    created_by BIGINT COMMENT 'User ID who created handler',
    updated_by BIGINT COMMENT 'User ID who last updated handler',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_handler_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_handler_workflow FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_handler_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_handler_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_event_name (company_id, event_name),
    INDEX idx_handler_type (handler_type),
    INDEX idx_handler_active (is_active),
    INDEX idx_execution_priority (execution_priority),
    CONSTRAINT chk_handler_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 3. EVENT_HANDLER_EXECUTIONS - Execution history of handlers
-- ============================================================================
CREATE TABLE event_handler_executions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique execution record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    event_id BIGINT NOT NULL COMMENT 'Reference to triggering event',
    handler_id BIGINT NOT NULL COMMENT 'Reference to event handler',
    execution_status ENUM(
        'pending',
        'executing',
        'completed',
        'failed',
        'timeout',
        'skipped'
    ) DEFAULT 'pending' COMMENT 'Status of handler execution',
    started_at TIMESTAMP NULL COMMENT 'When execution started',
    completed_at TIMESTAMP NULL COMMENT 'When execution completed',
    duration_milliseconds INT COMMENT 'How long execution took',
    
    -- Execution Result
    result_data JSON COMMENT 'Result/output from handler execution',
    result_workflow_execution_id BIGINT COMMENT 'Workflow execution ID if handler triggered workflow',
    result_rule_evaluation TINYINT(1) COMMENT 'Whether rule evaluated to true',
    result_rule_action_taken TINYINT(1) COMMENT 'Whether rule action was executed',
    
    -- Error Information
    error_message VARCHAR(500) COMMENT 'Error message if failed',
    error_code VARCHAR(50) COMMENT 'Error code for handling',
    retry_count INT DEFAULT 0 COMMENT 'Number of retries',
    next_retry_at TIMESTAMP NULL COMMENT 'When next retry will be attempted',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_handler_exec_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_handler_exec_event FOREIGN KEY (event_id) REFERENCES domain_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_handler_exec_handler FOREIGN KEY (handler_id) REFERENCES event_handlers(id) ON DELETE CASCADE,
    CONSTRAINT fk_handler_exec_workflow_exec FOREIGN KEY (result_workflow_execution_id) REFERENCES workflow_executions(id) ON DELETE SET NULL,
    INDEX idx_company_event (company_id, event_id),
    INDEX idx_execution_status (execution_status),
    INDEX idx_started_at (started_at),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_handler_exec_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 4. EVENT_SUBSCRIPTIONS - Track which domains subscribe to which events
-- ============================================================================
CREATE TABLE event_subscriptions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique subscription ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    subscriber_domain VARCHAR(100) NOT NULL COMMENT 'Domain that is subscribing (e.g., "security_operations")',
    event_name VARCHAR(255) NOT NULL COMMENT 'Event to subscribe to (wildcard support: e.g., "attendance.*")',
    subscription_description TEXT COMMENT 'Why this domain subscribes to this event',
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether subscription is active',
    created_by BIGINT COMMENT 'User ID who created subscription',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_subscription_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT uniq_subscription UNIQUE (company_id, subscriber_domain, event_name),
    INDEX idx_subscriber_domain (subscriber_domain),
    INDEX idx_event_name (event_name),
    CONSTRAINT chk_subscription_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 5. EVENT_DEAD_LETTER - Events that failed processing
-- ============================================================================
CREATE TABLE event_dead_letter (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique dead letter record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    event_id BIGINT NOT NULL COMMENT 'Reference to failed event',
    event_name VARCHAR(255) NOT NULL COMMENT 'Name of failed event',
    event_data JSON NOT NULL COMMENT 'Complete event payload',
    failure_reason VARCHAR(500) COMMENT 'Reason event entered dead letter queue',
    failure_count INT DEFAULT 1 COMMENT 'Number of times processing failed',
    last_error_message TEXT COMMENT 'Last error message',
    quarantined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When event was quarantined',
    resolved_at TIMESTAMP NULL COMMENT 'When event was resolved/reprocessed',
    resolved_by BIGINT COMMENT 'User ID who resolved the issue',
    resolution_notes TEXT COMMENT 'Notes on how issue was resolved',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_dead_letter_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_dead_letter_event FOREIGN KEY (event_id) REFERENCES domain_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_dead_letter_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_event (company_id, event_name),
    INDEX idx_quarantined_at (quarantined_at),
    INDEX idx_resolved_at (resolved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 6. EVENT_METRICS - Performance monitoring of event system
-- ============================================================================
CREATE TABLE event_metrics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique metric ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    event_name VARCHAR(255) NOT NULL COMMENT 'Event name being measured',
    metric_period ENUM('hourly', 'daily', 'weekly') DEFAULT 'daily' COMMENT 'Aggregation period',
    metric_date DATE NOT NULL COMMENT 'Date for this metric',
    
    -- Count Metrics
    events_emitted INT DEFAULT 0 COMMENT 'Number of events emitted',
    events_processed INT DEFAULT 0 COMMENT 'Number of events successfully processed',
    events_failed INT DEFAULT 0 COMMENT 'Number of events that failed',
    
    -- Performance Metrics
    avg_processing_time_ms DECIMAL(10,2) COMMENT 'Average processing time in milliseconds',
    min_processing_time_ms INT COMMENT 'Minimum processing time',
    max_processing_time_ms INT COMMENT 'Maximum processing time',
    p95_processing_time_ms INT COMMENT '95th percentile processing time',
    p99_processing_time_ms INT COMMENT '99th percentile processing time',
    
    -- Handler Metrics
    handlers_triggered INT DEFAULT 0 COMMENT 'Number of handler executions triggered',
    handlers_successful INT DEFAULT 0 COMMENT 'Number of successful handler executions',
    handlers_failed INT DEFAULT 0 COMMENT 'Number of failed handler executions',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    CONSTRAINT fk_metric_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT uniq_metric UNIQUE (company_id, event_name, metric_date, metric_period),
    INDEX idx_company_date (company_id, metric_date),
    INDEX idx_event_name (event_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 7. EVENT_REPLAY_LOG - Track event replays for recovery
-- ============================================================================
CREATE TABLE event_replay_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique replay log ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    replay_name VARCHAR(255) NOT NULL COMMENT 'Name of replay operation',
    replay_type ENUM(
        'single_event',
        'event_range',
        'recovery',
        'testing'
    ) NOT NULL COMMENT 'Type of replay',
    
    -- Replay Specification
    from_event_id BIGINT COMMENT 'Start event ID (for range replays)',
    to_event_id BIGINT COMMENT 'End event ID (for range replays)',
    from_timestamp TIMESTAMP NULL COMMENT 'Start timestamp (for range replays)',
    to_timestamp TIMESTAMP NULL COMMENT 'End timestamp (for range replays)',
    event_filter JSON COMMENT 'Filter criteria for which events to replay',
    
    -- Replay Execution
    initiated_by BIGINT COMMENT 'User ID who initiated replay',
    status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending' COMMENT 'Replay status',
    started_at TIMESTAMP NULL COMMENT 'When replay started',
    completed_at TIMESTAMP NULL COMMENT 'When replay completed',
    events_replayed INT DEFAULT 0 COMMENT 'Number of events replayed',
    events_succeeded INT DEFAULT 0 COMMENT 'Number of successful replays',
    events_failed INT DEFAULT 0 COMMENT 'Number of failed replays',
    
    failure_reason VARCHAR(500) COMMENT 'Reason if replay failed',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_replay_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_replay_initiated_by FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_replay_from_event FOREIGN KEY (from_event_id) REFERENCES domain_events(id) ON DELETE SET NULL,
    CONSTRAINT fk_replay_to_event FOREIGN KEY (to_event_id) REFERENCES domain_events(id) ON DELETE SET NULL,
    INDEX idx_company_status (company_id, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 8. EVENT_CORRELATIONS - Link related events together
-- ============================================================================
CREATE TABLE event_correlations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique correlation ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    correlation_id VARCHAR(255) NOT NULL COMMENT 'Correlation ID (links related events)',
    primary_event_id BIGINT NOT NULL COMMENT 'Primary/parent event',
    related_event_id BIGINT NOT NULL COMMENT 'Related/child event',
    correlation_type ENUM(
        'triggered_by',
        'caused_by',
        'related_to',
        'cascading'
    ) DEFAULT 'related_to' COMMENT 'Type of correlation',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_corr_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_corr_primary_event FOREIGN KEY (primary_event_id) REFERENCES domain_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_corr_related_event FOREIGN KEY (related_event_id) REFERENCES domain_events(id) ON DELETE CASCADE,
    INDEX idx_correlation_id (correlation_id),
    INDEX idx_primary_event (primary_event_id),
    INDEX idx_correlation_type (correlation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 9. Create Performance Indexes
-- ============================================================================

-- Event lookup optimization
CREATE INDEX idx_event_company_timestamp ON domain_events(company_id, event_timestamp);
CREATE INDEX idx_event_aggregate_company ON domain_events(company_id, aggregate_type, aggregate_id);

-- Handler execution query optimization
CREATE INDEX idx_handler_exec_handler_status ON event_handler_executions(handler_id, execution_status);

-- Subscription lookup
CREATE INDEX idx_subscription_company_active ON event_subscriptions(company_id, is_active);

-- Dead letter queue monitoring
CREATE INDEX idx_dead_letter_company_unresolved ON event_dead_letter(company_id, resolved_at);

-- Event correlation traversal
CREATE INDEX idx_correlation_related_events ON event_correlations(related_event_id);


-- ============================================================================
-- 10. Initial System Status
-- ============================================================================

SELECT 'Event System (Event Bus - SEB) Created Successfully' AS status;
SELECT 'Tables Created: 8 (Domain Events, Event Handlers, Handler Executions, Subscriptions, Dead Letter, Metrics, Replay Log, Correlations)' AS info;
SELECT 'Ready for: Event emission, domain communication, workflow triggering, rule evaluation' AS info;
SELECT 'Key Features: Event correlation, replay capability, handler subscriptions, dead letter queue, performance metrics' AS info;

-- ============================================================================
-- End of Event System Migration
-- ============================================================================
