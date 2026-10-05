-- ============================================================================
-- Sentinel AI Security Suite - Workflow Engine Migration
-- Core Domain Infrastructure for Business Process Automation
-- ============================================================================
-- This migration creates the Workflow Engine infrastructure tables that enable
-- the Sentinel Rule Engine (SRE) to orchestrate business processes
-- 
-- Dependencies: 001_identity_and_access.sql (users, companies, roles)
-- Status: Production Ready
-- Version: 1.0
-- Date: 2026-07-08
-- ============================================================================

-- ============================================================================
-- 1. WORKFLOW_DEFINITIONS - Blueprint for workflows
-- ============================================================================
CREATE TABLE workflow_definitions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique workflow definition ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    workflow_name VARCHAR(255) NOT NULL COMMENT 'Human-readable workflow name',
    workflow_code VARCHAR(100) NOT NULL COMMENT 'Unique code identifier',
    workflow_description TEXT COMMENT 'Detailed description of workflow purpose',
    domain_name VARCHAR(100) NOT NULL COMMENT 'Domain this workflow belongs to (e.g., attendance, incidents, etc)',
    trigger_event VARCHAR(255) NOT NULL COMMENT 'Event that triggers this workflow',
    version INT DEFAULT 1 COMMENT 'Workflow version for change tracking',
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether workflow is currently active',
    auto_execute TINYINT(1) DEFAULT FALSE COMMENT 'Whether to execute automatically on trigger',
    requires_approval TINYINT(1) DEFAULT FALSE COMMENT 'Whether workflow requires manual approval before execution',
    approval_roles JSON COMMENT 'Roles that can approve this workflow',
    estimated_duration_minutes INT COMMENT 'Estimated time to complete workflow in minutes',
    created_by BIGINT COMMENT 'User ID who created workflow',
    updated_by BIGINT COMMENT 'User ID who last updated workflow',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_workflow_def_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_def_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_workflow_def_updater FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT uniq_workflow_code UNIQUE (company_id, workflow_code),
    INDEX idx_company_domain (company_id, domain_name),
    INDEX idx_trigger_event (company_id, trigger_event),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_workflow_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 2. WORKFLOW_STEPS - Individual steps within a workflow
-- ============================================================================
CREATE TABLE workflow_steps (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique workflow step ID',
    workflow_id BIGINT NOT NULL COMMENT 'Reference to parent workflow',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    step_number INT NOT NULL COMMENT 'Execution order (1-based)',
    step_name VARCHAR(255) NOT NULL COMMENT 'Human-readable step name',
    step_type ENUM(
        'start',
        'end',
        'action',
        'decision',
        'parallel',
        'wait',
        'notification',
        'escalation'
    ) DEFAULT 'action' COMMENT 'Type of workflow step',
    action_type VARCHAR(100) COMMENT 'Specific action type (e.g., create_incident, send_notification, update_attendance)',
    action_config JSON COMMENT 'JSON configuration for the action (parameters, templates, etc)',
    decision_criteria JSON COMMENT 'For decision steps: conditions that determine next step',
    error_handling VARCHAR(50) COMMENT 'What to do on error: continue, retry, abort, escalate',
    max_retries INT DEFAULT 0 COMMENT 'Maximum retry attempts on failure',
    retry_delay_seconds INT DEFAULT 5 COMMENT 'Seconds to wait before retry',
    timeout_seconds INT DEFAULT 300 COMMENT 'Step timeout in seconds',
    next_step_success INT COMMENT 'ID of step to execute on success',
    next_step_failure INT COMMENT 'ID of step to execute on failure',
    data_mapping JSON COMMENT 'Maps input data to action parameters',
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether step is active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_workflow_step_workflow FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_step_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT uniq_step_number UNIQUE (workflow_id, step_number),
    INDEX idx_company_id (company_id),
    INDEX idx_step_type (step_type),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_step_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 3. WORKFLOW_EXECUTIONS - Runtime instances of workflow executions
-- ============================================================================
CREATE TABLE workflow_executions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique workflow execution ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    workflow_id BIGINT NOT NULL COMMENT 'Reference to workflow definition',
    trigger_event VARCHAR(255) NOT NULL COMMENT 'Event that triggered this execution',
    trigger_entity_type VARCHAR(100) COMMENT 'Type of entity that triggered workflow (attendance, incident, etc)',
    trigger_entity_id BIGINT COMMENT 'ID of entity that triggered workflow',
    execution_status ENUM(
        'pending',
        'running',
        'waiting_approval',
        'paused',
        'completed',
        'failed',
        'aborted',
        'timeout'
    ) DEFAULT 'pending' COMMENT 'Current execution status',
    current_step_id INT COMMENT 'Currently executing step ID',
    initiated_by BIGINT COMMENT 'User ID who initiated workflow',
    approved_by BIGINT COMMENT 'User ID who approved workflow (if required)',
    approved_at TIMESTAMP NULL COMMENT 'Approval timestamp',
    started_at TIMESTAMP NULL COMMENT 'Execution start timestamp',
    completed_at TIMESTAMP NULL COMMENT 'Execution completion timestamp',
    failure_reason VARCHAR(500) COMMENT 'Reason for failure if status=failed',
    execution_context JSON COMMENT 'Full context/state for current execution',
    result_data JSON COMMENT 'Final result data after execution',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_workflow_exec_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_exec_workflow FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_exec_initiator FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_workflow_exec_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_workflow (company_id, workflow_id),
    INDEX idx_execution_status (execution_status),
    INDEX idx_trigger_entity (trigger_entity_type, trigger_entity_id),
    INDEX idx_created_at (created_at),
    INDEX idx_started_at (started_at),
    CONSTRAINT chk_exec_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 4. WORKFLOW_STEP_EXECUTIONS - Individual step execution tracking
-- ============================================================================
CREATE TABLE workflow_step_executions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique step execution ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    workflow_execution_id BIGINT NOT NULL COMMENT 'Reference to parent workflow execution',
    step_id BIGINT NOT NULL COMMENT 'Reference to workflow step definition',
    step_number INT NOT NULL COMMENT 'Sequential step number',
    execution_status ENUM(
        'pending',
        'running',
        'completed',
        'failed',
        'skipped',
        'retry',
        'timeout'
    ) DEFAULT 'pending' COMMENT 'Status of this step execution',
    attempt_number INT DEFAULT 1 COMMENT 'Attempt number for this step',
    started_at TIMESTAMP NULL COMMENT 'Step execution start timestamp',
    completed_at TIMESTAMP NULL COMMENT 'Step execution completion timestamp',
    duration_milliseconds INT COMMENT 'How long step took to execute',
    input_data JSON COMMENT 'Input data provided to step',
    output_data JSON COMMENT 'Output/result from step execution',
    error_message VARCHAR(500) COMMENT 'Error message if step failed',
    error_code VARCHAR(50) COMMENT 'Error code for programmatic handling',
    retry_count INT DEFAULT 0 COMMENT 'Number of retries performed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_step_exec_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_step_exec_workflow_exec FOREIGN KEY (workflow_execution_id) REFERENCES workflow_executions(id) ON DELETE CASCADE,
    CONSTRAINT fk_step_exec_step FOREIGN KEY (step_id) REFERENCES workflow_steps(id) ON DELETE CASCADE,
    INDEX idx_workflow_execution (workflow_execution_id),
    INDEX idx_execution_status (execution_status),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_step_exec_not_deleted CHECK (deleted_at IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 5. WORKFLOW_VARIABLES - Workflow-level variables for data passing
-- ============================================================================
CREATE TABLE workflow_variables (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique variable ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    workflow_execution_id BIGINT NOT NULL COMMENT 'Reference to workflow execution',
    variable_name VARCHAR(255) NOT NULL COMMENT 'Variable name',
    variable_type ENUM(
        'string',
        'integer',
        'boolean',
        'decimal',
        'json',
        'datetime',
        'entity_id'
    ) DEFAULT 'string' COMMENT 'Data type of variable',
    variable_value TEXT COMMENT 'Variable value (serialized if complex)',
    is_secret TINYINT(1) DEFAULT FALSE COMMENT 'Whether value is sensitive/encrypted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    CONSTRAINT fk_var_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_var_workflow_exec FOREIGN KEY (workflow_execution_id) REFERENCES workflow_executions(id) ON DELETE CASCADE,
    INDEX idx_company_execution (company_id, workflow_execution_id),
    INDEX idx_variable_name (variable_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 6. WORKFLOW_TRANSITIONS - Define valid step transitions
-- ============================================================================
CREATE TABLE workflow_transitions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique transition ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    workflow_id BIGINT NOT NULL COMMENT 'Reference to workflow definition',
    from_step_id INT NOT NULL COMMENT 'Source step ID',
    to_step_id INT NOT NULL COMMENT 'Target step ID',
    transition_condition VARCHAR(255) COMMENT 'Condition for this transition (e.g., "approval==true")',
    transition_order INT DEFAULT 1 COMMENT 'Order to check this transition',
    is_default_transition TINYINT(1) DEFAULT FALSE COMMENT 'Is this the default if no conditions match',
    is_active TINYINT(1) DEFAULT TRUE COMMENT 'Whether transition is active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_trans_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_trans_workflow FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    INDEX idx_workflow_transitions (workflow_id, from_step_id),
    INDEX idx_default_trans (workflow_id, is_default_transition)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 7. WORKFLOW_AUDIT_LOG - Complete audit trail of workflow changes
-- ============================================================================
CREATE TABLE workflow_audit_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique audit record ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    workflow_id BIGINT COMMENT 'Reference to workflow definition',
    workflow_execution_id BIGINT COMMENT 'Reference to workflow execution',
    audit_action VARCHAR(50) NOT NULL COMMENT 'Action performed (created, updated, executed, etc)',
    audit_entity_type VARCHAR(100) COMMENT 'Type of entity affected',
    audit_entity_id BIGINT COMMENT 'ID of entity affected',
    changed_by BIGINT COMMENT 'User ID who made the change',
    old_values JSON COMMENT 'Previous values before change',
    new_values JSON COMMENT 'New values after change',
    audit_reason TEXT COMMENT 'Reason for change',
    ip_address VARCHAR(45) COMMENT 'IP address of user making change',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Audit timestamp',
    
    CONSTRAINT fk_audit_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_workflow FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_workflow_exec FOREIGN KEY (workflow_execution_id) REFERENCES workflow_executions(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_company_audit (company_id, created_at),
    INDEX idx_entity_audit (audit_entity_type, audit_entity_id),
    INDEX idx_audit_action (audit_action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 8. WORKFLOW_METRICS - Performance tracking for workflows
-- ============================================================================
CREATE TABLE workflow_metrics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT 'Unique metric ID',
    company_id BIGINT NOT NULL COMMENT 'Reference to company',
    workflow_id BIGINT NOT NULL COMMENT 'Reference to workflow definition',
    total_executions INT DEFAULT 0 COMMENT 'Total number of executions',
    successful_executions INT DEFAULT 0 COMMENT 'Number of successful executions',
    failed_executions INT DEFAULT 0 COMMENT 'Number of failed executions',
    average_duration_seconds DECIMAL(10,2) COMMENT 'Average execution duration',
    min_duration_seconds INT COMMENT 'Minimum execution duration',
    max_duration_seconds INT COMMENT 'Maximum execution duration',
    last_execution_status ENUM(
        'pending',
        'running',
        'waiting_approval',
        'paused',
        'completed',
        'failed',
        'aborted',
        'timeout'
    ) COMMENT 'Status of last execution',
    last_execution_at TIMESTAMP NULL COMMENT 'Timestamp of last execution',
    success_rate_percentage DECIMAL(5,2) COMMENT 'Percentage of successful executions',
    most_common_failure_reason VARCHAR(255) COMMENT 'Most common failure reason',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    CONSTRAINT fk_metric_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_metric_workflow FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    INDEX idx_company_workflow (company_id, workflow_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 9. Create Indexes for Performance
-- ============================================================================

-- Workflow step execution query optimization
CREATE INDEX idx_workflow_exec_status_company ON workflow_executions(company_id, execution_status);
CREATE INDEX idx_workflow_exec_trigger ON workflow_executions(company_id, trigger_event, created_at);
CREATE INDEX idx_step_exec_step_number ON workflow_step_executions(workflow_execution_id, step_number);

-- Audit trail query optimization
CREATE INDEX idx_audit_entity_company ON workflow_audit_log(company_id, audit_entity_type);

-- Workflow definition lookup
CREATE INDEX idx_workflow_domain_active ON workflow_definitions(company_id, domain_name, is_active);


-- ============================================================================
-- 10. Initial System Status
-- ============================================================================

SELECT 'Workflow Engine Infrastructure Created Successfully' AS status;
SELECT 'Tables Created: 8 (Definitions, Steps, Executions, Step Executions, Variables, Transitions, Audit Log, Metrics)' AS info;
SELECT 'Ready for: Domain Event processing, Rule evaluation, Business workflow orchestration' AS info;

-- ============================================================================
-- End of Workflow Engine Migration
-- ============================================================================
