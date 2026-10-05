USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS object_relationships (
    object_relationship_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    parent_business_object_id BIGINT UNSIGNED NOT NULL,
    child_business_object_id BIGINT UNSIGNED NOT NULL,
    relationship_type VARCHAR(80) NOT NULL,
    relationship_status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_object_relationships_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_object_relationships_parent
        FOREIGN KEY (parent_business_object_id)
        REFERENCES business_objects(business_object_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_object_relationships_child
        FOREIGN KEY (child_business_object_id)
        REFERENCES business_objects(business_object_id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_object_relationships_parent_child_type (parent_business_object_id, child_business_object_id, relationship_type),
    INDEX ix_object_relationships_company_id (company_id),
    INDEX ix_object_relationships_relationship_type (relationship_type),
    INDEX ix_object_relationships_relationship_status (relationship_status)
);
