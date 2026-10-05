-- ============================================================================
-- Sentinel AI Security Suite - Device Presence & Telemetry Extension
-- Extends device tracking with presence detection and enhanced telemetry
-- ============================================================================
-- This migration adds:
-- - Extended heartbeat telemetry (GPS, permissions, app state)
-- - Client-side captured timestamp for offline detection accuracy
-- - Tenant-configurable presence detection thresholds
-- - GPS quality/status tracking for degraded state detection
--
-- Phase: 2.1 Device Presence / Offline Detection Foundation
-- Status: Foundation for Guard Activity Engine
-- Date: 2026-08-12
-- ============================================================================

-- ============================================================================
-- Extend DEVICE_HEARTBEAT_HISTORY with enhanced telemetry
-- ============================================================================
ALTER TABLE device_heartbeat_history ADD COLUMN IF NOT EXISTS (
    -- GPS & Location Telemetry
    gps_enabled TINYINT(1) DEFAULT 1 COMMENT 'Whether GPS was enabled on device',
    gps_accuracy INT COMMENT 'GPS accuracy in meters from location service',
    gps_status VARCHAR(50) COMMENT 'GPS status: OK, DEGRADED, POOR, UNAVAILABLE',
    
    -- Permission State
    location_permission VARCHAR(50) DEFAULT 'granted' COMMENT 'Location permission state: granted, denied, restricted',
    
    -- App State
    app_state VARCHAR(50) DEFAULT 'foreground' COMMENT 'App state: foreground, background, terminated',
    
    -- Network Details
    network_type VARCHAR(50) COMMENT 'Network type: LTE, 4G, 5G, WiFi, 3G, etc.',
    
    -- Client-side Timestamp
    captured_at TIMESTAMP COMMENT 'Client timestamp when heartbeat was captured (before transmission)',
    
    -- Data Quality
    is_valid_sample TINYINT(1) DEFAULT 1 COMMENT 'Whether this heartbeat represents valid device state'
) COMMENT 'Extended heartbeat data for presence detection and analytics';

-- ============================================================================
-- Extend TRACKING_CONFIGURATION with presence detection thresholds
-- ============================================================================
ALTER TABLE tracking_configuration ADD COLUMN IF NOT EXISTS (
    -- Presence Detection Intervals (seconds)
    normal_heartbeat_interval INT DEFAULT 10 COMMENT 'Expected heartbeat interval in normal conditions',
    
    -- Presence State Thresholds (seconds since last valid heartbeat)
    warning_after_seconds INT DEFAULT 30 COMMENT 'Seconds before WARNING state (1.5x normal interval)',
    unresponsive_after_seconds INT DEFAULT 60 COMMENT 'Seconds before DEVICE_UNRESPONSIVE state (3x normal interval)',
    offline_after_seconds INT DEFAULT 120 COMMENT 'Seconds before OFFLINE state (hard timeout, 12x normal interval)',
    critical_offline_after_seconds INT DEFAULT 300 COMMENT 'Seconds before CRITICAL state (5 minutes)',
    
    -- GPS Degradation Thresholds
    gps_accuracy_warning_meters INT DEFAULT 100 COMMENT 'GPS accuracy above this is DEGRADED',
    gps_accuracy_critical_meters INT DEFAULT 500 COMMENT 'GPS accuracy above this is critical',
    
    -- Presence State Recovery Rules
    state_recovery_enabled TINYINT(1) DEFAULT 1 COMMENT 'Allow automatic state recovery to ONLINE when heartbeat received',
    minimum_valid_heartbeats INT DEFAULT 1 COMMENT 'Minimum consecutive valid heartbeats to transition to ONLINE',
    
    -- Charging & Battery Policy
    low_battery_threshold INT DEFAULT 20 COMMENT 'Battery % below which LOW_BATTERY events fire',
    critical_battery_threshold INT DEFAULT 5 COMMENT 'Battery % below which CRITICAL_BATTERY events fire'
) COMMENT 'Extended presence detection and telemetry configuration';

-- ============================================================================
-- Extend DEVICE_LOCATION_HISTORY with GPS status
-- ============================================================================
ALTER TABLE device_location_history ADD COLUMN IF NOT EXISTS (
    gps_status VARCHAR(50) COMMENT 'GPS status at time of location update: OK, DEGRADED, POOR, UNAVAILABLE',
    gps_enabled TINYINT(1) DEFAULT 1 COMMENT 'Whether GPS was enabled at time of update',
    network_type VARCHAR(50) COMMENT 'Network type at time of location update'
) COMMENT 'Extended location data for activity detection';

-- ============================================================================
-- Extend DEVICE_SESSIONS with presence state tracking
-- ============================================================================
ALTER TABLE device_sessions MODIFY COLUMN IF EXISTS offline_status 
    ENUM('online', 'online_again', 'gps_degraded', 'warning', 'device_unresponsive', 'offline', 'critical') 
    DEFAULT 'online' 
    COMMENT 'Device presence state: online, gps_degraded, warning, device_unresponsive, offline, critical, online_again';

-- Add columns for presence state history
ALTER TABLE device_sessions ADD COLUMN IF NOT EXISTS (
    -- State History
    previous_offline_status VARCHAR(50) COMMENT 'Previous presence state before last transition',
    state_transition_count INT DEFAULT 0 COMMENT 'Number of state transitions in this session',
    last_state_change_at TIMESTAMP NULL COMMENT 'When the last state change occurred',
    
    -- GPS Degradation Tracking
    gps_enabled_status TINYINT(1) DEFAULT 1 COMMENT 'Latest GPS enabled status',
    gps_quality VARCHAR(50) COMMENT 'Latest GPS quality: OK, DEGRADED, POOR, UNAVAILABLE',
    gps_accuracy_meters INT COMMENT 'Latest GPS accuracy in meters',
    
    -- App State
    latest_app_state VARCHAR(50) DEFAULT 'foreground' COMMENT 'Latest reported app state',
    latest_network_type VARCHAR(50) COMMENT 'Latest reported network type',
    location_permission_state VARCHAR(50) DEFAULT 'granted' COMMENT 'Latest location permission state'
) COMMENT 'Extended presence state tracking';

-- ============================================================================
-- Index Additions for Presence Detection Performance
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_heartbeat_offline_detection 
    ON device_heartbeat_history(tenant_id, device_session_id, created_at, is_valid_sample)
    COMMENT 'Index for efficient offline detection queries';

CREATE INDEX IF NOT EXISTS idx_session_presence_state
    ON device_sessions(tenant_id, offline_status, last_heartbeat_at)
    COMMENT 'Index for presence state monitoring queries';

CREATE INDEX IF NOT EXISTS idx_device_gps_status
    ON device_location_history(tenant_id, device_session_id, gps_status, created_at)
    COMMENT 'Index for GPS degradation tracking';

-- ============================================================================
-- Data Integrity Constraints
-- ============================================================================
ALTER TABLE tracking_configuration 
    ADD CONSTRAINT chk_presence_thresholds 
    CHECK (
        warning_after_seconds > 0 
        AND unresponsive_after_seconds > warning_after_seconds
        AND offline_after_seconds > unresponsive_after_seconds
        AND critical_offline_after_seconds > offline_after_seconds
    ),
    ADD CONSTRAINT chk_heartbeat_interval
    CHECK (normal_heartbeat_interval >= 5 AND normal_heartbeat_interval <= 300);

-- ============================================================================
-- Data Population (for existing tenants)
-- ============================================================================
-- Existing tracking configs get sensible defaults
UPDATE tracking_configuration 
SET 
    normal_heartbeat_interval = COALESCE(normal_heartbeat_interval, 10),
    warning_after_seconds = COALESCE(warning_after_seconds, 30),
    unresponsive_after_seconds = COALESCE(unresponsive_after_seconds, 60),
    offline_after_seconds = COALESCE(offline_after_seconds, 120),
    critical_offline_after_seconds = COALESCE(critical_offline_after_seconds, 300),
    gps_accuracy_warning_meters = COALESCE(gps_accuracy_warning_meters, 100),
    gps_accuracy_critical_meters = COALESCE(gps_accuracy_critical_meters, 500),
    state_recovery_enabled = COALESCE(state_recovery_enabled, 1),
    minimum_valid_heartbeats = COALESCE(minimum_valid_heartbeats, 1),
    low_battery_threshold = COALESCE(low_battery_threshold, 20),
    critical_battery_threshold = COALESCE(critical_battery_threshold, 5)
WHERE 1=1;

-- ============================================================================
-- Verification
-- ============================================================================
-- Run this to verify migration:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
--   WHERE TABLE_SCHEMA = 'sentinel' AND TABLE_NAME = 'device_heartbeat_history';
