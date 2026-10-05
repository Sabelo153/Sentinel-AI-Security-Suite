-- Defines the database changes in 018_ai_responses.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS ai_responses (
    response_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    request_id BIGINT UNSIGNED NOT NULL,
    response_output LONGTEXT,
    confidence_score DECIMAL(5,4),
    human_approved BOOLEAN DEFAULT FALSE,
    approved_by_user_id BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_responses_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ai_responses_request
        FOREIGN KEY (request_id)
        REFERENCES ai_requests(request_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ai_responses_approved_by
        FOREIGN KEY (approved_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,
    INDEX idx_ai_responses_company_id (company_id),
    INDEX idx_ai_responses_request_id (request_id),
    INDEX idx_ai_responses_approved_by_user_id (approved_by_user_id)
);
