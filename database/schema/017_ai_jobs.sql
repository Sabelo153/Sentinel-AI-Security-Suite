-- Defines the database changes in 017_ai_jobs.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS ai_jobs (
    job_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    request_id BIGINT UNSIGNED NOT NULL,
    queue_name VARCHAR(50) DEFAULT 'default',
    attempt_number INT UNSIGNED DEFAULT 1,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    processing_time_ms INT UNSIGNED,
    model_version VARCHAR(100),
    job_status ENUM('QUEUED', 'RUNNING', 'RETRYING', 'COMPLETED', 'FAILED') DEFAULT 'QUEUED',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_jobs_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ai_jobs_request
        FOREIGN KEY (request_id)
        REFERENCES ai_requests(request_id)
        ON DELETE CASCADE,
    INDEX idx_ai_jobs_company_id (company_id),
    INDEX idx_ai_jobs_request_id (request_id),
    INDEX idx_ai_jobs_status (job_status),
    INDEX idx_ai_jobs_started_at (started_at)
);
