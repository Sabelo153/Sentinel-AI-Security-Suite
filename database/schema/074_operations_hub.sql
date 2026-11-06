USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS radio_channels (
 channel_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, channel_name VARCHAR(100) NOT NULL, history_enabled INT NOT NULL DEFAULT 0,
 active INT NOT NULL DEFAULT 1, revision INT NOT NULL DEFAULT 1, created_by_user_id BIGINT UNSIGNED NOT NULL,
 floor_user_id BIGINT UNSIGNED NULL, floor_hash CHAR(64) NULL, floor_until DATETIME NULL,
 floor_priority INT NOT NULL DEFAULT 0, floor_uuid CHAR(36) NULL,
 UNIQUE KEY uq_radio_channel(company_id,site_id,channel_name)
);
CREATE TABLE IF NOT EXISTS radio_members (
 member_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 channel_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, joined_at DATETIME NOT NULL, joined_after_message_id BIGINT UNSIGNED NOT NULL DEFAULT 0, joined_after_call_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
 UNIQUE KEY uq_radio_member(company_id,channel_id,user_id)
);
CREATE TABLE IF NOT EXISTS radio_messages (
 message_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 channel_id BIGINT UNSIGNED NOT NULL, sender_user_id BIGINT UNSIGNED NOT NULL, operation_uuid CHAR(36) NOT NULL,
 payload_hash CHAR(64) NOT NULL, priority INT NOT NULL, mime_type VARCHAR(50) NOT NULL,
 audio_base64 MEDIUMTEXT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL,
 UNIQUE KEY uq_radio_operation(company_id,sender_user_id,operation_uuid),
 INDEX ix_radio_feed(company_id,channel_id,message_id,expires_at)
);
CREATE TABLE IF NOT EXISTS radio_priority_calls (
 call_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 channel_id BIGINT UNSIGNED NOT NULL, caller_user_id BIGINT UNSIGNED NOT NULL, operation_uuid CHAR(36) NOT NULL,
 created_at DATETIME NOT NULL, acknowledged_by_user_id BIGINT UNSIGNED NULL, acknowledged_at DATETIME NULL,
 UNIQUE KEY uq_radio_call(company_id,caller_user_id,operation_uuid),
 INDEX ix_radio_calls(company_id,channel_id,created_at)
);
CREATE TABLE IF NOT EXISTS monthly_service_reviews (
 review_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, month_code CHAR(7) NOT NULL,
 summary TEXT NOT NULL, snapshot_json MEDIUMTEXT NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
 revision INT NOT NULL DEFAULT 1, generated_at DATETIME NOT NULL, published_at DATETIME NULL,
 published_hash CHAR(64) NULL, created_by_user_id BIGINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_monthly_service(company_id,site_id,month_code)
);
CREATE TABLE IF NOT EXISTS service_review_actions (
 action_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 review_id BIGINT UNSIGNED NOT NULL, title VARCHAR(150) NOT NULL, owner_user_id BIGINT UNSIGNED NOT NULL,
 due_on DATE NOT NULL, state VARCHAR(30) NOT NULL DEFAULT 'OPEN', revision INT NOT NULL DEFAULT 1,
 completion_notes VARCHAR(2000) NULL, completed_at DATETIME NULL, confirmed_by_user_id BIGINT UNSIGNED NULL,
 confirmed_at DATETIME NULL,
 INDEX ix_review_actions(company_id,review_id,state)
);
CREATE TABLE IF NOT EXISTS service_review_feedback (
 feedback_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 review_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, rating INT NOT NULL,
 comments VARCHAR(2000) NOT NULL, review_hash CHAR(64) NOT NULL, created_at DATETIME NOT NULL,
 UNIQUE KEY uq_service_feedback(company_id,review_id,user_id)
);
CREATE TABLE IF NOT EXISTS integration_credentials (
 credential_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, label VARCHAR(100) NOT NULL, key_hash CHAR(64) NOT NULL,
 scopes_json TEXT NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 expires_at DATETIME NOT NULL, revoked_at DATETIME NULL, last_used_at DATETIME NULL,
 UNIQUE KEY uq_integration_hash(key_hash),
 INDEX ix_integration_company(company_id,credential_id)
);
CREATE TABLE IF NOT EXISTS integration_receipts (
 receipt_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 credential_id BIGINT UNSIGNED NOT NULL, operation_uuid CHAR(36) NOT NULL, request_hash CHAR(64) NOT NULL,
 response_json TEXT NOT NULL, received_at DATETIME NOT NULL,
 UNIQUE KEY uq_integration_receipt(company_id,credential_id,operation_uuid)
);
CREATE TABLE IF NOT EXISTS integration_webhooks (
 webhook_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, label VARCHAR(100) NOT NULL, endpoint_url VARCHAR(1000) NOT NULL,
 secret_cipher TEXT NOT NULL, enabled INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS integration_events (
 event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, event_uuid CHAR(36) NOT NULL, event_type VARCHAR(80) NOT NULL,
 payload_json TEXT NOT NULL, created_at DATETIME NOT NULL, UNIQUE KEY uq_integration_event(event_uuid),
 INDEX ix_integration_site(company_id,site_id,event_id)
);
CREATE TABLE IF NOT EXISTS webhook_deliveries (
 delivery_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 webhook_id BIGINT UNSIGNED NOT NULL, event_id BIGINT UNSIGNED NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'PENDING',
 attempts INT NOT NULL DEFAULT 0, next_attempt_at DATETIME NOT NULL, last_http_status INT NULL,
 last_result VARCHAR(200) NULL, attempted_at DATETIME NULL,
 UNIQUE KEY uq_webhook_delivery(company_id,webhook_id,event_id),
 INDEX ix_webhook_pending(company_id,state,next_attempt_at)
);
CREATE TABLE IF NOT EXISTS customer_support_tickets (
 ticket_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, created_by_user_id BIGINT UNSIGNED NOT NULL, subject VARCHAR(150) NOT NULL,
 description TEXT NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OPEN', priority VARCHAR(20) NOT NULL,
 revision INT NOT NULL DEFAULT 1, assignee_user_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL,
 resolved_at DATETIME NULL,
 INDEX ix_support_site(company_id,site_id,client_id,ticket_id), INDEX ix_support_owner(company_id,created_by_user_id,ticket_id)
);
CREATE TABLE IF NOT EXISTS customer_support_messages (
 message_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 ticket_id BIGINT UNSIGNED NOT NULL, actor_user_id BIGINT UNSIGNED NOT NULL, message VARCHAR(3000) NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX ix_support_messages(company_id,ticket_id,message_id)
);
CREATE TABLE IF NOT EXISTS support_diagnostic_consents (
 consent_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 ticket_id BIGINT UNSIGNED NOT NULL, shared_by_user_id BIGINT UNSIGNED NOT NULL,
 snapshot_json MEDIUMTEXT NULL, snapshot_hash CHAR(64) NOT NULL, shared_at DATETIME NOT NULL,
 revoked_at DATETIME NULL,
 INDEX ix_support_consents(company_id,ticket_id)
);
CREATE TABLE IF NOT EXISTS release_notices (
 notice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(150) NOT NULL, body TEXT NOT NULL, audience VARCHAR(20) NOT NULL,
 content_hash CHAR(64) NOT NULL, published_at DATETIME NOT NULL, published_by_user_id BIGINT UNSIGNED NOT NULL,
 INDEX ix_notices_company(company_id,notice_id)
);
CREATE TABLE IF NOT EXISTS release_notice_reads (
 read_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 notice_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, content_hash CHAR(64) NOT NULL,
 read_at DATETIME NOT NULL, UNIQUE KEY uq_notice_read(company_id,notice_id,user_id)
);
CREATE TABLE IF NOT EXISTS inspection_queue_health (
 health_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, pending_count INT NOT NULL,
 blocked_count INT NOT NULL, oldest_at DATETIME NULL, received_at DATETIME NOT NULL,
 UNIQUE KEY uq_inspection_health(company_id,site_id,user_id)
);
CREATE TABLE IF NOT EXISTS operational_alert_rules (
 rule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, recipient_user_id BIGINT UNSIGNED NOT NULL, backup_user_id BIGINT UNSIGNED NOT NULL,
 queue_minutes INT NOT NULL, findings_hours INT NOT NULL, escalate_minutes INT NOT NULL,
 enabled INT NOT NULL DEFAULT 1, revision INT NOT NULL DEFAULT 1,
 UNIQUE KEY uq_operational_rule(company_id,site_id)
);
CREATE TABLE IF NOT EXISTS operational_alerts (
 alert_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, source_key VARCHAR(150) NOT NULL, alert_type VARCHAR(40) NOT NULL,
 message VARCHAR(1000) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'OPEN', episode INT NOT NULL DEFAULT 1, revision INT NOT NULL DEFAULT 1,
 opened_at DATETIME NOT NULL, last_seen_at DATETIME NOT NULL, acknowledged_at DATETIME NULL,
 acknowledged_by_user_id BIGINT UNSIGNED NULL, resolved_at DATETIME NULL,
 UNIQUE KEY uq_operational_source(company_id,source_key),
 INDEX ix_operational_site(company_id,site_id,state)
);
CREATE TABLE IF NOT EXISTS operational_alert_deliveries (
 delivery_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 alert_id BIGINT UNSIGNED NOT NULL, episode INT NOT NULL, stage VARCHAR(10) NOT NULL,
 recipient_user_id BIGINT UNSIGNED NOT NULL, delivered_at DATETIME NOT NULL,
 UNIQUE KEY uq_operational_delivery(company_id,alert_id,episode,stage,recipient_user_id)
);
