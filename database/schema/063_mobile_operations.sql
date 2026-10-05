-- Durable retry receipts for authenticated, tenant-scoped guard commands.
USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS mobile_operations (
    mobile_operation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    operation_uuid CHAR(36) NOT NULL,
    request_hash CHAR(64) NOT NULL,
    response_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mobile_operations_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_mobile_operations_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_mobile_operations_retry (company_id,user_id,operation_uuid),
    INDEX idx_mobile_operations_created (company_id,created_at)
);
