USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS company_subscriptions (
    company_subscription_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    plan_code ENUM('FREE', 'ESSENTIAL', 'PROFESSIONAL', 'ENTERPRISE') NOT NULL DEFAULT 'FREE',
    subscription_status ENUM('ACTIVE', 'TRIALING', 'PAST_DUE', 'SUSPENDED', 'CANCELLED') NOT NULL DEFAULT 'ACTIVE',
    trial_ends_at DATETIME NULL,
    current_period_ends_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_company_subscriptions_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    UNIQUE KEY uq_company_subscriptions_company (company_id),
    INDEX idx_company_subscriptions_plan_status (plan_code, subscription_status)
);
