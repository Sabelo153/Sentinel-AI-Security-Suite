USE sentinel_ai_security;

CREATE TABLE IF NOT EXISTS gps_events (
    gps_event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    gps_location_id BIGINT UNSIGNED NULL,
    geofence_id BIGINT UNSIGNED NULL,
    business_object_id BIGINT UNSIGNED NULL,
    event_name VARCHAR(120) NOT NULL,
    event_type ENUM('LOCATION_UPDATED', 'GEOFENCE_ENTER', 'GEOFENCE_EXIT', 'ROUTE_DEVIATION', 'IDLE_DETECTED', 'SOS_TRIGGERED', 'OTHER') DEFAULT 'OTHER',
    event_severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'LOW',
    event_payload JSON NULL,
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_gps_events_company_id_companies
        FOREIGN KEY (company_id)
        REFERENCES companies(company_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_gps_events_gps_location_id_gps_locations
        FOREIGN KEY (gps_location_id)
        REFERENCES gps_locations(gps_location_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_gps_events_geofence_id_geofences
        FOREIGN KEY (geofence_id)
        REFERENCES geofences(geofence_id)
        ON DELETE SET NULL,
    CONSTRAINT fk_gps_events_business_object_id_business_objects
        FOREIGN KEY (business_object_id)
        REFERENCES business_objects(business_object_id)
        ON DELETE SET NULL,
    INDEX ix_gps_events_company_id_occurred_at (company_id, occurred_at),
    INDEX ix_gps_events_event_name (event_name),
    INDEX ix_gps_events_event_type (event_type),
    INDEX ix_gps_events_event_severity (event_severity)
);
