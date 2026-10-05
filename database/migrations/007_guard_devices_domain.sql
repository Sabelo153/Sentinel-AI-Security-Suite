-- ============================================================================
-- Sentinel AI Security Suite - Guard Devices Domain Migration
-- Device registration, ownership, and lifecycle management
-- ============================================================================
-- The Guard Devices Domain manages:
-- - Device registration linked to tenants and guards
-- - Device lifecycle (REGISTERED, ACTIVE, SUSPENDED, LOST, RETIRED)
-- - Device ownership enforcement (tenant isolation)
-- - Device authorization for guard assignment
-- - Multi-tenant device tracking sessions
-- - Heartbeat and location tracking validation
--
-- Security Rules Enforced:
-- - Device must be registered to company
-- - Device must be assigned to guard
-- - Device must be in ACTIVE state for session start
-- - Session can only receive updates from device's assigned guard
-- - Tenant isolation at all levels
--
-- Dependencies: 001_identity_and_access.sql
-- Status: Production Ready
-- Version: 1.0
-- Date: 2026-08-12
-- ============================================================================

-- ============================================================================
-- 1. GUARD_DEVICES - Device registration and ownership
-- ============================================================================
CREATE TABLE guard_devices (
    guard_device_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique device registration ID',
    company_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to company',
    guard_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to guard',
    device_identifier VARCHAR(255) NOT NULL COMMENT 'Unique device ID (IMEI, Android ID, etc.)',
    device_name VARCHAR(255) COMMENT 'User-friendly device name',
    platform ENUM('android', 'ios', 'web', 'other') DEFAULT 'android' COMMENT 'Device platform',
    app_version VARCHAR(50) COMMENT 'Mobile app version',
    
    -- Device Lifecycle
    status ENUM('REGISTERED', 'ACTIVE', 'SUSPENDED', 'LOST', 'RETIRED') DEFAULT 'REGISTERED' COMMENT 'Device lifecycle status',
    is_primary TINYINT(1) DEFAULT TRUE COMMENT 'Whether this is the primary device for guard',
    
    -- Tracking Configuration
    tracking_interval_seconds INT DEFAULT 10 COMMENT 'Interval for GPS/heartbeat updates (seconds)',
    
    -- Device State
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When device was first registered',
    activated_at TIMESTAMP NULL COMMENT 'When device was activated',
    suspended_at TIMESTAMP NULL COMMENT 'When device was suspended',
    last_seen_at TIMESTAMP NULL COMMENT 'Last heartbeat timestamp',
    lost_at TIMESTAMP NULL COMMENT 'When device was marked lost',
    retired_at TIMESTAMP NULL COMMENT 'When device was retired',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    CONSTRAINT fk_guard_device_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_guard_device_guard FOREIGN KEY (guard_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT uniq_device_identifier UNIQUE (company_id, device_identifier),
    CONSTRAINT uniq_primary_device UNIQUE (guard_id, is_primary),
    INDEX idx_company_guard (company_id, guard_id),
    INDEX idx_company_status (company_id, status),
    INDEX idx_device_identifier (device_identifier),
    INDEX idx_last_seen (last_seen_at),
    INDEX idx_created_at (created_at),
    CONSTRAINT chk_device_not_deleted CHECK (deleted_at IS NULL),
    CONSTRAINT chk_tracking_interval CHECK (tracking_interval_seconds >= 5 AND tracking_interval_seconds <= 300)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. GUARD_DEVICE_SESSIONS - Tracking sessions linked to devices
-- ============================================================================
CREATE TABLE guard_device_sessions (
    session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique session ID',
    company_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to company',
    guard_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to guard',
    guard_device_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to registered device',
    
    -- Session Context
    assignment_id BIGINT UNSIGNED COMMENT 'Related assignment ID',
    shift_id BIGINT UNSIGNED COMMENT 'Related shift ID',
    site_id BIGINT UNSIGNED COMMENT 'Reference to site being guarded',
    
    -- Session State
    session_status ENUM('active', 'paused', 'ended') DEFAULT 'active' COMMENT 'Current session status',
    
    -- Location Tracking
    current_latitude DECIMAL(10, 8) COMMENT 'Last known latitude',
    current_longitude DECIMAL(11, 8) COMMENT 'Last known longitude',
    location_accuracy INT COMMENT 'GPS accuracy in meters',
    location_updated_at TIMESTAMP NULL COMMENT 'When location was last updated',
    
    -- Device State
    battery_percent INT COMMENT 'Last reported battery percentage',
    charging TINYINT(1) DEFAULT FALSE COMMENT 'Whether device is charging',
    network_status VARCHAR(50) COMMENT 'Network connectivity status',
    device_status VARCHAR(50) COMMENT 'Device health status',
    
    -- Heartbeat Tracking
    heartbeat_count INT DEFAULT 0 COMMENT 'Number of heartbeats received',
    heartbeat_misses INT DEFAULT 0 COMMENT 'Number of missed heartbeats',
    last_heartbeat_at TIMESTAMP NULL COMMENT 'Last heartbeat timestamp',
    offline_status ENUM('online', 'warning', 'offline', 'critical') DEFAULT 'online' COMMENT 'Offline detection status',
    offline_since TIMESTAMP NULL COMMENT 'When offline detection started',
    
    -- Timestamps
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Session start time',
    paused_at TIMESTAMP NULL COMMENT 'When session was paused',
    ended_at TIMESTAMP NULL COMMENT 'Session end time',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    CONSTRAINT fk_device_session_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_device_session_guard FOREIGN KEY (guard_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_device_session_device FOREIGN KEY (guard_device_id) REFERENCES guard_devices(guard_device_id) ON DELETE CASCADE,
    CONSTRAINT fk_device_session_site FOREIGN KEY (site_id) REFERENCES sites(site_id) ON DELETE SET NULL,
    INDEX idx_company_status (company_id, session_status),
    INDEX idx_guard_active (guard_id, session_status),
    INDEX idx_device_active (guard_device_id, session_status),
    INDEX idx_started_at (started_at),
    INDEX idx_ended_at (ended_at),
    INDEX idx_heartbeat_missing (company_id, offline_status),
    INDEX idx_location_updated (location_updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. DEVICE_LOCATION_HISTORY - GPS tracking history
-- ============================================================================
CREATE TABLE device_location_history (
    location_history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique location record ID',
    company_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to company',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to session',
    guard_device_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to device',
    
    -- Location Data
    latitude DECIMAL(10, 8) NOT NULL COMMENT 'Latitude coordinate',
    longitude DECIMAL(11, 8) NOT NULL COMMENT 'Longitude coordinate',
    accuracy INT COMMENT 'GPS accuracy in meters',
    
    -- Context
    network_status VARCHAR(50) COMMENT 'Network status at time of update',
    device_status VARCHAR(50) COMMENT 'Device status at time of update',
    
    -- Timestamp
    location_at TIMESTAMP COMMENT 'Client-reported location timestamp',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    
    CONSTRAINT fk_location_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_location_session FOREIGN KEY (session_id) REFERENCES guard_device_sessions(session_id) ON DELETE CASCADE,
    CONSTRAINT fk_location_device FOREIGN KEY (guard_device_id) REFERENCES guard_devices(guard_device_id) ON DELETE CASCADE,
    INDEX idx_session_history (session_id, location_at),
    INDEX idx_company_history (company_id, location_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. DEVICE_HEARTBEAT_HISTORY - Heartbeat tracking history
-- ============================================================================
CREATE TABLE device_heartbeat_history (
    heartbeat_history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique heartbeat record ID',
    company_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to company',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to session',
    guard_device_id BIGINT UNSIGNED NOT NULL COMMENT 'Reference to device',
    
    -- Device State
    battery_percent INT COMMENT 'Battery percentage',
    charging TINYINT(1) COMMENT 'Whether device is charging',
    network_status VARCHAR(50) COMMENT 'Network connectivity status',
    device_status VARCHAR(50) COMMENT 'Device status at time of update',
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Heartbeat timestamp',
    
    CONSTRAINT fk_heartbeat_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
    CONSTRAINT fk_heartbeat_session FOREIGN KEY (session_id) REFERENCES guard_device_sessions(session_id) ON DELETE CASCADE,
    CONSTRAINT fk_heartbeat_device FOREIGN KEY (guard_device_id) REFERENCES guard_devices(guard_device_id) ON DELETE CASCADE,
    INDEX idx_session_history (session_id, created_at),
    INDEX idx_company_history (company_id, created_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. TRACKING_CONFIGURATION - Tenant-level tracking configuration
-- ============================================================================
CREATE TABLE tracking_configuration (
    tracking_config_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique configuration ID',
    company_id BIGINT UNSIGNED NOT NULL UNIQUE COMMENT 'Reference to company',
    
    -- Default Tracking Interval (seconds)
    default_tracking_interval INT DEFAULT 10 COMMENT 'Default interval for all devices',
    
    -- Policy-specific Intervals
    active_patrol_interval INT DEFAULT 5 COMMENT 'Interval during active patrol',
    stationary_duty_interval INT DEFAULT 10 COMMENT 'Interval during stationary duty',
    emergency_interval INT DEFAULT 3 COMMENT 'Interval during emergency',
    low_battery_interval INT DEFAULT 15 COMMENT 'Interval when battery is low',
    poor_network_interval INT DEFAULT 20 COMMENT 'Interval with poor network',
    
    -- Offline Detection
    heartbeat_miss_threshold INT DEFAULT 3 COMMENT 'Misses before WARNING status',
    heartbeat_critical_threshold INT DEFAULT 6 COMMENT 'Misses before OFFLINE status',
    warning_duration_seconds INT DEFAULT 30 COMMENT 'Duration to WARNING before OFFLINE',
    critical_duration_seconds INT DEFAULT 60 COMMENT 'Duration to OFFLINE before CRITICAL',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
    CONSTRAINT fk_tracking_config_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Indexes for Performance
-- ============================================================================
CREATE INDEX idx_guard_devices_company_active ON guard_devices(company_id, status);
CREATE INDEX idx_device_sessions_active ON guard_device_sessions(company_id, session_status);
CREATE INDEX idx_location_recent ON device_location_history(company_id, created_at);
CREATE INDEX idx_heartbeat_recent ON device_heartbeat_history(company_id, created_at);
