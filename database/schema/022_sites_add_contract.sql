-- Defines the database changes in 022_sites_add_contract.

USE sentinel_ai_security;

ALTER TABLE sites
    ADD COLUMN contract_id BIGINT UNSIGNED NULL AFTER client_id,
    ADD COLUMN updated_by_user_id BIGINT UNSIGNED NULL AFTER updated_at,
    ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_by_user_id,
    ADD INDEX idx_sites_contract_id (contract_id),
    ADD INDEX idx_sites_updated_by_user_id (updated_by_user_id),
    ADD CONSTRAINT fk_site_contract
        FOREIGN KEY (contract_id)
        REFERENCES contracts(contract_id)
        ON DELETE SET NULL,
    ADD CONSTRAINT fk_site_updated_by
        FOREIGN KEY (updated_by_user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL;
