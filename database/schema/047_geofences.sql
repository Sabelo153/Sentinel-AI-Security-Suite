USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS geofences (
    geofence_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NULL,
    geofence_code VARCHAR(60) NOT NULL,
    geofence_name VARCHAR(150) NOT NULL,
    geofence_shape ENUM('CIRCLE', 'POLYGON') DEFAULT 'CIRCLE',
    center_latitude DECIMAL(10,8) NULL,
    center_longitude DECIMAL(11,8) NULL,
    radius_meters DECIMAL(10,2) NULL,
    polygon_json JSON NULL,
    geofence_status ENUM('ACTIVE', 'INACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_geofences_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_geofences_site_id_sites
        FOREIGN KEY (site_id)
        REFERENCES sites(site_id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_geofences_company_id_geofence_code (company_id, geofence_code),
    INDEX ix_geofences_company_id (company_id),
    INDEX ix_geofences_site_id (site_id),
    INDEX ix_geofences_geofence_status (geofence_status)
);
