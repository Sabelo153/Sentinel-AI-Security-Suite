-- ============================================================================
-- Sentinel AI Security Suite - Rule Engine Migration
-- Implements Sentinel Rule Engine (SRE) - Intelligent Automation Before AI
-- ============================================================================
-- The Sentinel Rule Engine (SRE) is the core differentiator.
-- Business rules are evaluated BEFORE any AI intervention.
-- This creates fast (<100ms), deterministic, auditable automation.
-- 
-- Rule Types Supported:
-- 1. Time-based: Trigger at specific times
-- 2. Frequency-based: Trigger if threshold met in timeframe
-- 3. Threshold-based: Trigger if metric exceeds limit
-- 4. Status-based: Trigger when entity status changes
-- 5. Compound: Multiple conditions with AND/OR logic
-- 6. Sequential: Must occur in specific order
-- 
-- Dependencies: 001_identity_and_access.sql, 003_workflow_engine.sql, 004_event_system.sql
-- Status: Production Ready
-- Version: 1.0
-- Date: 2026-07-08
-- ============================================================================

-- ============================================================================
-- 1. RULES - Rule definitions and configuration
-- ============================================================================
CREATE TABLE rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique rule ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    rule_name VARCHAR(255) NOT NULL COMMENT 'Human-readable rule name',
    rule_code VARCHAR(100) NOT NULL COMMENT 'Unique code identifier',
    rule_description TEXT COMMENT 'Detailed description of rule purpose',
    
    -- Rule Classification
    rule_type ENUM(
        'time_based',
        'frequency_based',
        'threshold_based',
        'status_based',
        'compound',
        'sequential'
    ) NOT NULL COMMENT 'Type of rule',
    domain_name VARCHAR(100) NOT NULL COMMENT 'Domain this rule applies to',
    applies_to_entity_type VARCHAR(100) NOT NULL COMMENT 'Entity type this rule applies to (e.g., attendance, incident)',
    
    -- Rule Execution
    trigger_event VARCHAR(255) COMMENT 'Event that can trigger this rule',
    evaluation_frequency ENUM('immediate', 'hourly', 'daily', 'weekly', 'monthly') DEFAULT 'immediate' COMMENT 'How often rule is evaluated',
    batch_evaluation TINYINT(1) DEFAULT FALSE COMMENT 'Whether rule evaluates batches of records',
    batch_size INT COMMENT 'Number of records per batch (if batch_evaluation=true)',
    
    -- Priority and Routing
    priority INT DEFAULT 50 COMMENT 'Execution priority (1-100, higher executes first)',
    severity_level ENUM('critical', 'high', 'medium', 'low', 'info') DEFAULT 'medium' COMMENT 'Severity if rule is triggered',
    requires_human_review TINYINT(1) DEFAULT FALSE COMMENT 'Whether rule action needs human approval',
    
    -- Scope and Isolation
    applies_to_company_id BIGINT COMMENT 'NULL = applies to all companies (system), or specific company_id',
    applies_to_site_id BIGINT COMMENT 'NULL = applies to all sites, or specific site_id',
    applies_to_user_role_id BIGINT COMMENT 'NULL = applies to all roles, or specific role_id',
    
    -- Status
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether rule is currently active',
    is_system_rule TINYINT(1) DEFAULT FALSE COMMENT 'Whether this is a built-in system rule',
    
    -- Time Windows
    effective_from DATETIME COMMENT 'Rule becomes effective on this date',
    effective_until DATETIME COMMENT 'Rule expires on this date',
    
    -- Audit
    created_by BIGINT COMMENT 'User ID who created rule',
    updated_by BIGINT COMMENT 'User ID who last updated rule',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_rule_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_rule_site FOREIGN KEY (applies_to_site_id) REFERENCES sites(id) ON DELETE CASCADE,
    CONSTRAINT fk_rule_role FOREIGN KEY (applies_to_user_role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rule_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_rule_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT uniq_rule_code UNIQUE (company_id, rule_code),
    INDEX idx_company_domain (company_id, domain_name, is_active),
    INDEX idx_rule_type (rule_type),
    INDEX idx_entity_type (applies_to_entity_type),
    INDEX idx_priority (priority),
    INDEX idx_effective_dates (effective_from, effective_until),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_rule_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 2. RULE_CONDITIONS - Conditions that must be met for rule to trigger
-- ============================================================================
CREATE TABLE rule_conditions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique condition ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    rule_id BIGINT NOT NULL COMMENT 'Reference to parent rule',
    condition_number INT NOT NULL COMMENT 'Execution order for compound rules',
    
    -- Condition Type
    condition_type ENUM(
        'time_range',
        'day_of_week',
        'field_comparison',
        'field_in_list',
        'field_pattern',
        'aggregate_count',
        'aggregate_sum',
        'aggregate_average',
        'time_since_last_event',
        'custom_expression'
    ) NOT NULL COMMENT 'Type of condition',
    
    -- Field Reference
    target_field_path VARCHAR(255) COMMENT 'JSON path to field being evaluated (e.g., "attendance.status")',
    target_field_type VARCHAR(50) COMMENT 'Data type of field being compared',
    
    -- Condition Specifics
    operator VARCHAR(50) COMMENT 'Comparison operator (=, !=, >, <, >=, <=, IN, LIKE, BETWEEN, etc)',
    comparison_value VARCHAR(500) COMMENT 'Value to compare against',
    comparison_value_type VARCHAR(50) COMMENT 'Type of comparison value (literal, field_reference, timestamp_offset, etc)',
    
    -- Range Conditions
    range_start VARCHAR(50) COMMENT 'Start of range (for time ranges, aggregate ranges)',
    range_end VARCHAR(50) COMMENT 'End of range',
    
    -- Time-based Conditions
    time_of_day_start TIME COMMENT 'Start time for time_range condition',
    time_of_day_end TIME COMMENT 'End time for time_range condition',
    days_of_week JSON COMMENT 'Array of days (1=Mon, 7=Sun) for day_of_week condition',
    
    -- Aggregate Conditions
    aggregation_window_minutes INT COMMENT 'Time window for aggregate (e.g., count in last X minutes)',
    aggregation_operator VARCHAR(50) COMMENT 'How to aggregate (COUNT, SUM, AVG, MIN, MAX)',
    aggregation_threshold INT COMMENT 'Threshold value for aggregate',
    
    -- Logical Combination
    logical_operator ENUM('AND', 'OR') DEFAULT 'AND' COMMENT 'How to combine with previous condition',
    
    -- Negation
    negate_condition TINYINT(1) DEFAULT FALSE COMMENT 'Whether to negate this condition (NOT)',
    
    -- Custom Expression
    custom_expression TEXT COMMENT 'Custom expression/formula for complex conditions',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_condition_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_condition_rule FOREIGN KEY (rule_id) REFERENCES rules(id) ON DELETE CASCADE,
    CONSTRAINT uniq_condition_number UNIQUE (rule_id, condition_number),
    INDEX idx_rule_id (rule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 3. RULE_ACTIONS - Actions to execute when rule is triggered
-- ============================================================================
CREATE TABLE rule_actions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique action ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    rule_id BIGINT NOT NULL COMMENT 'Reference to parent rule',
    action_number INT NOT NULL COMMENT 'Execution order',
    
    -- Action Type
    action_type ENUM(
        'create_incident',
        'update_entity',
        'send_notification',
        'trigger_workflow',
        'create_task',
        'escalate_to_manager',
        'update_status',
        'apply_penalty',
        'log_event',
        'webhook',
        'custom'
    ) NOT NULL COMMENT 'Type of action to execute',
    
    -- Action Configuration
    action_config JSON NOT NULL COMMENT 'JSON configuration for action',
    
    -- Conditional Execution
    condition_for_execution VARCHAR(255) COMMENT 'Optional condition for whether to execute this action',
    
    -- Notifications
    notify_roles JSON COMMENT 'Array of role IDs to notify',
    notify_users JSON COMMENT 'Array of user IDs to notify',
    notification_template_id BIGINT COMMENT 'Template ID for notification message',
    
    -- Incident Creation
    incident_type VARCHAR(100) COMMENT 'Type of incident to create',
    incident_priority VARCHAR(50) COMMENT 'Priority of incident',
    incident_assignee_role VARCHAR(50) COMMENT 'Role to assign incident to',
    
    -- Workflow
    workflow_id BIGINT COMMENT 'Workflow to trigger',
    workflow_auto_execute TINYINT(1) DEFAULT FALSE COMMENT 'Whether to auto-execute',
    
    -- Escalation
    escalation_level INT COMMENT 'Escalation level (1-3)',
    escalation_delay_minutes INT COMMENT 'Minutes to wait before escalation',
    
    -- Status
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether action is active',
    
    -- Execution Behavior
    stop_on_failure TINYINT(1) DEFAULT FALSE COMMENT 'Whether to stop executing remaining actions if this fails',
    max_executions_per_period INT COMMENT 'Max times to execute in a time period (prevent spam)',
    execution_period_minutes INT COMMENT 'Time period for max executions limit',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_action_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_action_rule FOREIGN KEY (rule_id) REFERENCES rules(id) ON DELETE CASCADE,
    CONSTRAINT fk_action_workflow FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    CONSTRAINT uniq_action_number UNIQUE (rule_id, action_number),
    INDEX idx_rule_id (rule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 4. RULE_EVALUATIONS - History of rule evaluations
-- ============================================================================
CREATE TABLE rule_evaluations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique evaluation record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    rule_id BIGINT NOT NULL COMMENT 'Reference to evaluated rule',
    
    -- Evaluation Context
    evaluated_entity_type VARCHAR(100) NOT NULL COMMENT 'Type of entity being evaluated',
    evaluated_entity_id BIGINT NOT NULL COMMENT 'ID of entity being evaluated',
    evaluation_trigger VARCHAR(255) COMMENT 'What triggered this evaluation (event_name or scheduled)',
    
    -- Evaluation Result
    evaluation_result TINYINT(1) COMMENT 'Whether rule conditions were met (TRUE/FALSE)',
    conditions_met INT DEFAULT 0 COMMENT 'Number of conditions that evaluated to true',
    total_conditions INT DEFAULT 0 COMMENT 'Total number of conditions',
    evaluation_time_milliseconds INT COMMENT 'Time taken to evaluate rule',
    
    -- Actions Executed
    actions_executed INT DEFAULT 0 COMMENT 'Number of actions executed',
    actions_failed INT DEFAULT 0 COMMENT 'Number of actions that failed',
    action_results JSON COMMENT 'Details of each action execution',
    
    -- Performance
    is_performance_optimal TINYINT(1) DEFAULT TRUE COMMENT 'Whether evaluation completed within target time (<100ms)',
    
    -- Audit
    evaluated_by_user_id BIGINT COMMENT 'User ID if manually evaluated',
    evaluated_by_system TINYINT(1) DEFAULT TRUE COMMENT 'Whether evaluated by automated system',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_eval_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_eval_rule FOREIGN KEY (rule_id) REFERENCES rules(id) ON DELETE CASCADE,
    CONSTRAINT fk_eval_user FOREIGN KEY (evaluated_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_rule (company_id, rule_id),
    INDEX idx_entity_evaluation (evaluated_entity_type, evaluated_entity_id),
    INDEX idx_evaluation_result (evaluation_result),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_eval_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 5. RULE_VIOLATIONS - Record of rule violations/triggers
-- ============================================================================
CREATE TABLE rule_violations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique violation record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    rule_id BIGINT NOT NULL COMMENT 'Reference to violated rule',
    
    -- Violation Details
    violation_entity_type VARCHAR(100) NOT NULL COMMENT 'Type of entity violating rule',
    violation_entity_id BIGINT NOT NULL COMMENT 'ID of entity violating rule',
    violation_description TEXT COMMENT 'Human-readable description of violation',
    
    -- Violation Metrics
    violation_severity VARCHAR(50) COMMENT 'Severity of violation (from rule)',
    violation_count INT DEFAULT 1 COMMENT 'Number of violations in this period',
    
    -- Response
    action_taken VARCHAR(255) COMMENT 'Action taken in response to violation',
    action_timestamp TIMESTAMP NULL COMMENT 'When action was taken',
    
    -- Resolution
    is_resolved TINYINT(1) DEFAULT FALSE COMMENT 'Whether violation has been resolved',
    resolved_at TIMESTAMP NULL COMMENT 'When violation was resolved',
    resolved_by BIGINT COMMENT 'User ID who resolved violation',
    resolution_notes TEXT COMMENT 'Notes on how violation was resolved',
    
    -- Audit
    reported_by BIGINT COMMENT 'User ID who reported violation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_violation_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_violation_rule FOREIGN KEY (rule_id) REFERENCES rules(id) ON DELETE CASCADE,
    CONSTRAINT fk_violation_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_violation_reported_by FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_entity (company_id, violation_entity_type, violation_entity_id),
    INDEX idx_violation_status (is_resolved),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_violation_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 6. RULE_EXECUTION_LOG - Detailed audit trail of all rule executions
-- ============================================================================
CREATE TABLE rule_execution_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique execution log entry ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    rule_id BIGINT NOT NULL COMMENT 'Reference to executed rule',
    
    -- Execution Details
    execution_status ENUM('success', 'failure', 'timeout', 'skipped') DEFAULT 'success' COMMENT 'Status of rule execution',
    execution_duration_milliseconds INT COMMENT 'Time to execute rule',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When execution started',
    completed_at TIMESTAMP NULL COMMENT 'When execution completed',
    
    -- Results
    conditions_evaluated INT COMMENT 'Number of conditions evaluated',
    conditions_passed INT COMMENT 'Number of conditions that passed',
    rule_triggered TINYINT(1) DEFAULT FALSE COMMENT 'Whether rule was triggered',
    
    -- Failure Details
    error_message VARCHAR(500) COMMENT 'Error message if failed',
    error_stack_trace TEXT COMMENT 'Stack trace for debugging',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_exec_log_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_exec_log_rule FOREIGN KEY (rule_id) REFERENCES rules(id) ON DELETE CASCADE,
    INDEX idx_rule_execution_status (rule_id, execution_status),
    INDEX idx_created_at (created_at),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 7. RULE_TEMPLATES - Pre-built rule templates for quick deployment
-- ============================================================================
CREATE TABLE rule_templates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique template ID',
    template_name VARCHAR(255) NOT NULL COMMENT 'Template name',
    template_code VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique template code',
    template_description TEXT COMMENT 'Description of what template does',
    domain_name VARCHAR(100) NOT NULL COMMENT 'Domain this template applies to',
    rule_type VARCHAR(50) NOT NULL COMMENT 'Type of rule this template creates',
    
    -- Template Configuration
    template_config JSON NOT NULL COMMENT 'Template configuration (conditions + actions)',
    configurable_fields JSON COMMENT 'Fields that can be customized when instantiating template',
    
    -- Metadata
    is_published TINYINT(1) DEFAULT FALSE COMMENT 'Whether template is available for use',
    usage_count INT DEFAULT 0 COMMENT 'Number of times template has been used',
    
    created_by BIGINT COMMENT 'User ID who created template',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_template_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_domain (domain_name),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 8. RULE_METRICS - Performance and usage metrics for rules
-- ============================================================================
CREATE TABLE rule_metrics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique metric ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    rule_id BIGINT NOT NULL COMMENT 'Reference to rule',
    
    -- Execution Counts
    total_evaluations INT DEFAULT 0 COMMENT 'Total number of times rule evaluated',
    successful_evaluations INT DEFAULT 0 COMMENT 'Evaluations completed without error',
    failed_evaluations INT DEFAULT 0 COMMENT 'Evaluations that failed',
    evaluations_triggering_actions INT DEFAULT 0 COMMENT 'Evaluations where rule was triggered',
    
    -- Performance
    avg_evaluation_time_ms DECIMAL(10,2) COMMENT 'Average evaluation time',
    min_evaluation_time_ms INT COMMENT 'Minimum evaluation time',
    max_evaluation_time_ms INT COMMENT 'Maximum evaluation time',
    p95_evaluation_time_ms INT COMMENT '95th percentile evaluation time',
    p99_evaluation_time_ms INT COMMENT '99th percentile evaluation time',
    
    -- SLA Tracking
    evaluations_within_sla INT DEFAULT 0 COMMENT 'Evaluations completed in <100ms',
    sla_compliance_percentage DECIMAL(5,2) COMMENT 'Percentage of evaluations meeting SLA',
    
    -- Action Results
    actions_executed INT DEFAULT 0 COMMENT 'Total actions executed by this rule',
    actions_successful INT DEFAULT 0 COMMENT 'Actions that succeeded',
    actions_failed INT DEFAULT 0 COMMENT 'Actions that failed',
    
    -- Violations
    violations_created INT DEFAULT 0 COMMENT 'Number of violations recorded',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    CONSTRAINT fk_metric_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_metric_rule FOREIGN KEY (rule_id) REFERENCES rules(id) ON DELETE CASCADE,
    INDEX idx_company_rule (company_id, rule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 9. Create Performance Indexes
-- ============================================================================

-- Rule lookup optimization
CREATE INDEX idx_rule_company_active ON rules(company_id, is_active, effective_from, effective_until);
CREATE INDEX idx_rule_entity_type ON rules(applies_to_entity_type, is_active);
CREATE INDEX idx_rule_trigger_event ON rules(trigger_event, company_id);

-- Evaluation query optimization
CREATE INDEX idx_eval_entity_company ON rule_evaluations(company_id, evaluated_entity_type, evaluated_entity_id);
CREATE INDEX idx_eval_result_created ON rule_evaluations(evaluation_result, created_at);

-- Violation tracking
CREATE INDEX idx_violation_resolved ON rule_violations(is_resolved, created_at);

-- Execution log
CREATE INDEX idx_exec_log_duration ON rule_execution_log(execution_duration_milliseconds);


-- ============================================================================
-- 10. Initial System Status
-- ============================================================================

SELECT 'Sentinel Rule Engine (SRE) Created Successfully' AS status;
SELECT 'Tables Created: 8 (Rules, Conditions, Actions, Evaluations, Violations, Execution Log, Templates, Metrics)' AS info;
SELECT 'Key Features: <100ms evaluation SLA, 6 rule types, action automation, violation tracking, performance monitoring' AS info;
SELECT 'Ready for: Business rule automation, incident creation, notifications, workflow triggering' AS info;

-- ============================================================================
-- End of Rule Engine Migration
-- ============================================================================
