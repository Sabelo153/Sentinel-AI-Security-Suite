-- Defines the database changes in 028_auth_sessions.

USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS refresh_tokens (
    refresh_token_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    access_token_jti CHAR(32) NOT NULL,
    refresh_token_hash CHAR(64) NOT NULL,
    refresh_token_expires_at DATETIME NOT NULL,
    revoked_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_refresh_tokens_company
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_refresh_tokens_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_refresh_tokens_access_token_jti (access_token_jti),
    UNIQUE KEY uq_refresh_tokens_refresh_token_hash (refresh_token_hash),
    INDEX idx_refresh_tokens_company_id (company_id),
    INDEX idx_refresh_tokens_user_id (user_id),
    INDEX idx_refresh_tokens_revoked_at (revoked_at),
    INDEX idx_refresh_tokens_refresh_token_expires_at (refresh_token_expires_at)
);