-- ============================================================================
-- Sentinel AI Security Suite - Database Migrations
-- Identity & Access Domain
-- ============================================================================
-- This file contains SQL migrations for the Identity & Access Domain
-- Including users, roles, permissions, and company foundational tables
-- 
-- Status: Production Ready
-- Version: 1.0
-- Date: 2026-07-08
-- ============================================================================

-- ============================================================================
-- 001: Create Core Identity Tables
-- ============================================================================

-- Users Table
-- Core user account records for all system access
CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique user identifier',
    
    email VARCHAR(255) NOT NULL UNIQUE 
        COMMENT 'Email address - unique per system',
    
    username VARCHAR(100) NOT NULL UNIQUE 
        COMMENT 'Username for login',
    
    password_hash VARCHAR(255) NOT NULL 
        COMMENT 'bcrypt hashed password (never store plain text)',
    
    first_name VARCHAR(100) NOT NULL 
        COMMENT 'User first name',
    
    last_name VARCHAR(100) NOT NULL 
        COMMENT 'User last name',
    
    phone_number VARCHAR(20) 
        COMMENT 'Optional phone number',
    
    is_active BOOLEAN DEFAULT TRUE 
        COMMENT 'Account active status (false = suspended)',
    
    is_email_verified BOOLEAN DEFAULT FALSE 
        COMMENT 'Email address verified',
    
    two_factor_enabled BOOLEAN DEFAULT FALSE 
        COMMENT 'Two-factor authentication enabled',
    
    two_factor_secret VARCHAR(255) 
        COMMENT 'AES-256 encrypted 2FA secret',
    
    last_login_at TIMESTAMP NULL 
        COMMENT 'Last successful login timestamp',
    
    last_login_ip VARCHAR(45) 
        COMMENT 'IP address of last login (IPv4 or IPv6)',
    
    failed_login_attempts INT DEFAULT 0 
        COMMENT 'Consecutive failed login attempts (reset after successful login)',
    
    locked_until TIMESTAMP NULL 
        COMMENT 'Account locked until this timestamp (security measure)',
    
    password_changed_at TIMESTAMP NULL 
        COMMENT 'Last password change date (for password expiry policy)',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When user account was created',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last update timestamp',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp (null = active)',
    
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Users table - Core user accounts for Sentinel AI';


-- Roles Table
-- Role definitions per company (multi-tenant)
CREATE TABLE IF NOT EXISTS roles (
    role_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique role identifier',
    
    company_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Company owning this role (multi-tenant)',
    
    role_name VARCHAR(100) NOT NULL 
        COMMENT 'Role name (e.g., "Security Manager", "Guard")',
    
    role_description TEXT 
        COMMENT 'Description of role responsibilities',
    
    is_system_role BOOLEAN DEFAULT FALSE 
        COMMENT 'System roles (Super Admin, Admin) cannot be deleted',
    
    permission_count INT DEFAULT 0 
        COMMENT 'Denormalized for performance - count of permissions assigned',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When role was created',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last update timestamp',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    INDEX idx_company_id (company_id),
    INDEX idx_role_name (role_name),
    UNIQUE KEY uniq_company_role (company_id, role_name),
    FOREIGN KEY fk_company_id (company_id) 
        REFERENCES companies(company_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Roles table - Role definitions per company';


-- Permissions Table
-- System-wide permission definitions (not tenant-specific)
CREATE TABLE IF NOT EXISTS permissions (
    permission_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique permission identifier',
    
    permission_name VARCHAR(100) NOT NULL UNIQUE 
        COMMENT 'Permission name (e.g., "incidents:create", "reports:export")',
    
    permission_description TEXT 
        COMMENT 'Human-readable description of what this permission allows',
    
    category VARCHAR(50) NOT NULL 
        COMMENT 'Category: Authentication, Incidents, Attendance, Reporting, Admin, etc.',
    
    module VARCHAR(50) NOT NULL 
        COMMENT 'Module name (incidents, attendance, patrols, etc.)',
    
    action VARCHAR(50) NOT NULL 
        COMMENT 'Action type: create, read, update, delete, approve, export',
    
    requires_mfa BOOLEAN DEFAULT FALSE 
        COMMENT 'This permission requires multi-factor authentication',
    
    risk_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Low' 
        COMMENT 'Risk level for this permission',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When permission was created',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last update timestamp',
    
    INDEX idx_category (category),
    INDEX idx_module (module),
    INDEX idx_action (action),
    INDEX idx_permission_name (permission_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Permissions table - System-wide permission definitions';


-- Role Permissions Junction Table
-- Maps roles to permissions (many-to-many)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_permission_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique role-permission mapping identifier',
    
    role_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to role',
    
    permission_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to permission',
    
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When this permission was granted to role',
    
    granted_by BIGINT UNSIGNED 
        COMMENT 'User ID who granted this permission (for audit)',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    UNIQUE KEY uniq_role_permission (role_id, permission_id),
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id),
    FOREIGN KEY fk_role_id (role_id) 
        REFERENCES roles(role_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_permission_id (permission_id) 
        REFERENCES permissions(permission_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_granted_by (granted_by) 
        REFERENCES users(user_id) 
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Role-Permission mapping - Many-to-many junction table';


-- User Roles Junction Table
-- Maps users to roles at specific sites (many-to-many with site scoping)
CREATE TABLE IF NOT EXISTS user_roles (
    user_role_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique user-role assignment identifier',
    
    user_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to user',
    
    role_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to role',
    
    company_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Company where role is assigned (multi-tenant scoping)',
    
    site_id BIGINT UNSIGNED 
        COMMENT 'Optional: Specific site where role applies (null = company-wide)',
    
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When role was assigned to user',
    
    assigned_by BIGINT UNSIGNED 
        COMMENT 'User ID who assigned this role (for audit)',
    
    valid_from TIMESTAMP 
        COMMENT 'Optional: Role valid from this date',
    
    valid_until TIMESTAMP NULL 
        COMMENT 'Optional: Role valid until this date (for temporary assignments)',
    
    is_primary BOOLEAN DEFAULT FALSE 
        COMMENT 'Primary role for user (for display purposes)',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp (role revoked)',
    
    UNIQUE KEY uniq_user_role_site (user_id, role_id, site_id, deleted_at),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id),
    INDEX idx_company_id (company_id),
    INDEX idx_site_id (site_id),
    INDEX idx_valid_from (valid_from),
    FOREIGN KEY fk_user_id (user_id) 
        REFERENCES users(user_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_role_id (role_id) 
        REFERENCES roles(role_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_company_id (company_id) 
        REFERENCES companies(company_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_site_id (site_id) 
        REFERENCES sites(site_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User-Role assignment - Users can have multiple roles at different sites';


-- ============================================================================
-- 002: Create Session & Token Tables
-- ============================================================================

-- User Sessions Table
-- Track active user sessions for security
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique session identifier',
    
    user_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to user',
    
    session_token VARCHAR(255) NOT NULL UNIQUE 
        COMMENT 'Secure session token (JWT or similar)',
    
    ip_address VARCHAR(45) NOT NULL 
        COMMENT 'IP address of session (IPv4 or IPv6)',
    
    user_agent VARCHAR(500) 
        COMMENT 'User agent string (browser/device info)',
    
    device_id VARCHAR(255) 
        COMMENT 'Mobile device ID (for mobile app sessions)',
    
    session_type ENUM('Web', 'Mobile', 'API', 'Desktop') DEFAULT 'Web' 
        COMMENT 'Type of client session',
    
    is_active BOOLEAN DEFAULT TRUE 
        COMMENT 'Session currently active',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When session was created (login time)',
    
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last activity in this session',
    
    expires_at TIMESTAMP 
        COMMENT 'When session expires (auto-logout)',
    
    terminated_at TIMESTAMP NULL 
        COMMENT 'When session was manually terminated (logout)',
    
    termination_reason VARCHAR(50) 
        COMMENT 'Reason for termination: Manual, Expired, Timeout, SecurityEvent',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),
    FOREIGN KEY fk_user_id (user_id) 
        REFERENCES users(user_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User Sessions table - Track active user sessions for security';


-- API Keys Table
-- For programmatic API access
CREATE TABLE IF NOT EXISTS api_keys (
    api_key_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique API key identifier',
    
    user_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to user who owns this key',
    
    company_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Company this key is scoped to',
    
    key_hash VARCHAR(255) NOT NULL UNIQUE 
        COMMENT 'SHA-256 hash of actual API key (never store plain key)',
    
    key_name VARCHAR(100) NOT NULL 
        COMMENT 'Friendly name for this API key (e.g., "Mobile App", "Integration Bot")',
    
    key_prefix VARCHAR(10) 
        COMMENT 'First 10 characters of key (for identifying key in logs)',
    
    is_active BOOLEAN DEFAULT TRUE 
        COMMENT 'API key is active and usable',
    
    rate_limit INT DEFAULT 1000 
        COMMENT 'API requests per hour limit',
    
    allowed_ips JSON 
        COMMENT 'JSON array of allowed IP addresses (null = any)',
    
    allowed_origins JSON 
        COMMENT 'JSON array of allowed CORS origins',
    
    scopes JSON 
        COMMENT 'JSON array of API scopes this key can access',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When API key was created',
    
    last_used_at TIMESTAMP NULL 
        COMMENT 'Last API call using this key',
    
    expires_at TIMESTAMP NULL 
        COMMENT 'When key expires (null = never)',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp (key revoked)',
    
    INDEX idx_user_id (user_id),
    INDEX idx_company_id (company_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_used_at (last_used_at),
    FOREIGN KEY fk_user_id (user_id) 
        REFERENCES users(user_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_company_id (company_id) 
        REFERENCES companies(company_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='API Keys table - For programmatic API access';


-- ============================================================================
-- 003: Create Login History & Security Tables
-- ============================================================================

-- Login History Table
-- Comprehensive login audit trail for security analysis
CREATE TABLE IF NOT EXISTS login_history (
    login_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique login history record identifier',
    
    user_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to user',
    
    email VARCHAR(255) NOT NULL 
        COMMENT 'Email used for login attempt (may differ from current email)',
    
    login_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When login was attempted',
    
    ip_address VARCHAR(45) NOT NULL 
        COMMENT 'IP address of login attempt',
    
    user_agent VARCHAR(500) 
        COMMENT 'User agent string',
    
    device_type VARCHAR(50) 
        COMMENT 'Device type: Desktop, Mobile, Tablet, etc.',
    
    os_type VARCHAR(50) 
        COMMENT 'Operating system: Windows, macOS, iOS, Android, Linux',
    
    browser_name VARCHAR(50) 
        COMMENT 'Browser name: Chrome, Firefox, Safari, etc.',
    
    login_method ENUM('Email/Password', '2FA', 'API Key', 'OAuth', 'SSO') 
        COMMENT 'How user logged in',
    
    login_success BOOLEAN NOT NULL 
        COMMENT 'Whether login was successful',
    
    failure_reason VARCHAR(255) 
        COMMENT 'If login failed: InvalidCredentials, AccountLocked, TwoFAFailed, etc.',
    
    session_id BIGINT UNSIGNED 
        COMMENT 'Reference to session if login successful',
    
    two_fa_used BOOLEAN DEFAULT FALSE 
        COMMENT 'Two-factor authentication was used',
    
    geo_country VARCHAR(100) 
        COMMENT 'Country from IP geolocation',
    
    geo_city VARCHAR(100) 
        COMMENT 'City from IP geolocation',
    
    suspicious_flag BOOLEAN DEFAULT FALSE 
        COMMENT 'Flagged as suspicious (new location, time, device, etc.)',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    INDEX idx_user_id (user_id),
    INDEX idx_login_timestamp (login_timestamp),
    INDEX idx_ip_address (ip_address),
    INDEX idx_login_success (login_success),
    INDEX idx_suspicious_flag (suspicious_flag),
    FOREIGN KEY fk_user_id (user_id) 
        REFERENCES users(user_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_session_id (session_id) 
        REFERENCES user_sessions(session_id) 
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Login History table - Comprehensive audit trail for security analysis';


-- Password Reset Tokens Table
-- Secure password reset flow
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique password reset record identifier',
    
    user_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to user requesting password reset',
    
    email VARCHAR(255) NOT NULL 
        COMMENT 'Email address password reset was requested for',
    
    reset_token VARCHAR(255) NOT NULL UNIQUE 
        COMMENT 'Secure random token (hashed)',
    
    token_expires_at TIMESTAMP NOT NULL 
        COMMENT 'When token expires (typically 1 hour)',
    
    ip_address VARCHAR(45) 
        COMMENT 'IP address that requested reset',
    
    request_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When reset was requested',
    
    used_at TIMESTAMP NULL 
        COMMENT 'When token was used to reset password',
    
    used_ip_address VARCHAR(45) 
        COMMENT 'IP address that used the token',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_token_expires_at (token_expires_at),
    INDEX idx_request_timestamp (request_timestamp),
    FOREIGN KEY fk_user_id (user_id) 
        REFERENCES users(user_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Password Resets table - Secure password reset flow';


-- ============================================================================
-- 004: Create Company Foundation Tables
-- ============================================================================

-- Companies Table
-- Master company records (tenants)
CREATE TABLE IF NOT EXISTS companies (
    company_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique company identifier (tenant)',
    
    company_name VARCHAR(255) NOT NULL 
        COMMENT 'Company legal name',
    
    company_display_name VARCHAR(255) 
        COMMENT 'Display name (may differ from legal name)',
    
    company_code VARCHAR(50) UNIQUE 
        COMMENT 'Internal company code for reference',
    
    registration_number VARCHAR(50) UNIQUE 
        COMMENT 'Company registration/incorporation number',
    
    industry_type VARCHAR(100) 
        COMMENT 'Industry classification: Security, Retail, Manufacturing, etc.',
    
    company_size ENUM('Startup', 'Small', 'Medium', 'Large', 'Enterprise') 
        COMMENT 'Company size classification',
    
    phone_number VARCHAR(20) 
        COMMENT 'Main company phone number',
    
    website VARCHAR(255) 
        COMMENT 'Company website URL',
    
    logo_url VARCHAR(500) 
        COMMENT 'URL to company logo (for branding)',
    
    country VARCHAR(100) 
        COMMENT 'Country of registration',
    
    currency CHAR(3) DEFAULT 'USD' 
        COMMENT 'Primary currency code (ISO 4217)',
    
    timezone VARCHAR(50) DEFAULT 'UTC' 
        COMMENT 'Company timezone for scheduling',
    
    status ENUM('Active', 'Inactive', 'Suspended', 'Trial', 'Archived') DEFAULT 'Active' 
        COMMENT 'Company status',
    
    subscription_plan VARCHAR(50) 
        COMMENT 'Subscription plan: Free, Pro, Enterprise, etc.',
    
    subscription_expires_at TIMESTAMP NULL 
        COMMENT 'When subscription expires (null = indefinite)',
    
    max_users INT DEFAULT 10 
        COMMENT 'Maximum concurrent users allowed',
    
    max_sites INT DEFAULT 1 
        COMMENT 'Maximum sites this company can manage',
    
    billing_email VARCHAR(255) 
        COMMENT 'Email for billing communications',
    
    contact_name VARCHAR(100) 
        COMMENT 'Primary contact person name',
    
    contact_email VARCHAR(255) 
        COMMENT 'Primary contact email',
    
    contact_phone VARCHAR(20) 
        COMMENT 'Primary contact phone',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When company record was created',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last update timestamp',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    INDEX idx_company_name (company_name),
    INDEX idx_company_code (company_code),
    INDEX idx_status (status),
    INDEX idx_subscription_plan (subscription_plan),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Companies table - Master company records (tenants)';


-- Branches Table
-- Regional offices or divisions
CREATE TABLE IF NOT EXISTS branches (
    branch_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique branch identifier',
    
    company_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to parent company (multi-tenant)',
    
    branch_name VARCHAR(255) NOT NULL 
        COMMENT 'Branch name (e.g., "North Region", "New York Office")',
    
    branch_code VARCHAR(50) 
        COMMENT 'Internal branch code for reference',
    
    branch_type ENUM('Headquarters', 'Regional Office', 'Satellite Office', 'Field Office') DEFAULT 'Regional Office' 
        COMMENT 'Type of branch',
    
    address VARCHAR(500) 
        COMMENT 'Branch street address',
    
    city VARCHAR(100) 
        COMMENT 'City/town',
    
    state_province VARCHAR(100) 
        COMMENT 'State or province',
    
    postal_code VARCHAR(20) 
        COMMENT 'Postal/ZIP code',
    
    country VARCHAR(100) 
        COMMENT 'Country',
    
    phone_number VARCHAR(20) 
        COMMENT 'Branch phone number',
    
    email VARCHAR(255) 
        COMMENT 'Branch email address',
    
    manager_id BIGINT UNSIGNED 
        COMMENT 'Reference to employee who manages branch',
    
    gps_latitude DECIMAL(10, 8) 
        COMMENT 'GPS latitude of branch location',
    
    gps_longitude DECIMAL(11, 8) 
        COMMENT 'GPS longitude of branch location',
    
    is_active BOOLEAN DEFAULT TRUE 
        COMMENT 'Branch currently active',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When branch was created',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last update timestamp',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    INDEX idx_company_id (company_id),
    INDEX idx_branch_code (branch_code),
    INDEX idx_manager_id (manager_id),
    INDEX idx_is_active (is_active),
    UNIQUE KEY uniq_company_branch_code (company_id, branch_code),
    FOREIGN KEY fk_company_id (company_id) 
        REFERENCES companies(company_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Branches table - Regional offices or divisions';


-- Sites Table
-- Customer locations / security deployment sites
CREATE TABLE IF NOT EXISTS sites (
    site_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT 
        COMMENT 'Unique site identifier',
    
    branch_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to parent branch',
    
    company_id BIGINT UNSIGNED NOT NULL 
        COMMENT 'Reference to company (multi-tenant scoping)',
    
    site_name VARCHAR(255) NOT NULL 
        COMMENT 'Site name (e.g., "Downtown Office", "Warehouse A")',
    
    site_code VARCHAR(50) 
        COMMENT 'Internal site code for reference',
    
    site_type ENUM('Office', 'Warehouse', 'Retail', 'Manufacturing', 'Residential', 'Hospitality', 'Other') DEFAULT 'Office' 
        COMMENT 'Type of site',
    
    client_name VARCHAR(255) 
        COMMENT 'If this is a client location, client name',
    
    client_contact VARCHAR(255) 
        COMMENT 'Primary client contact person',
    
    client_email VARCHAR(255) 
        COMMENT 'Client email',
    
    client_phone VARCHAR(20) 
        COMMENT 'Client phone',
    
    address VARCHAR(500) 
        COMMENT 'Site street address',
    
    city VARCHAR(100) 
        COMMENT 'City/town',
    
    state_province VARCHAR(100) 
        COMMENT 'State or province',
    
    postal_code VARCHAR(20) 
        COMMENT 'Postal/ZIP code',
    
    country VARCHAR(100) 
        COMMENT 'Country',
    
    phone_number VARCHAR(20) 
        COMMENT 'Site phone number',
    
    email VARCHAR(255) 
        COMMENT 'Site email address',
    
    gps_latitude DECIMAL(10, 8) NOT NULL 
        COMMENT 'GPS latitude of site',
    
    gps_longitude DECIMAL(11, 8) NOT NULL 
        COMMENT 'GPS longitude of site',
    
    site_manager_id BIGINT UNSIGNED 
        COMMENT 'Reference to employee managing this site',
    
    guard_count INT DEFAULT 1 
        COMMENT 'Number of guards assigned to site',
    
    operating_hours_start TIME 
        COMMENT 'Site operating hours start time',
    
    operating_hours_end TIME 
        COMMENT 'Site operating hours end time',
    
    is_active BOOLEAN DEFAULT TRUE 
        COMMENT 'Site currently active',
    
    is_armed BOOLEAN DEFAULT FALSE 
        COMMENT 'Site security system armed status',
    
    timezone VARCHAR(50) 
        COMMENT 'Site timezone (for scheduling)',
    
    security_level ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium' 
        COMMENT 'Security classification of site',
    
    max_visitors_per_day INT DEFAULT 50 
        COMMENT 'Maximum visitors allowed per day',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
        COMMENT 'When site record was created',
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
        COMMENT 'Last update timestamp',
    
    deleted_at TIMESTAMP NULL 
        COMMENT 'Soft delete timestamp',
    
    INDEX idx_branch_id (branch_id),
    INDEX idx_company_id (company_id),
    INDEX idx_site_code (site_code),
    INDEX idx_site_manager_id (site_manager_id),
    INDEX idx_is_active (is_active),
    INDEX idx_security_level (security_level),
    UNIQUE KEY uniq_company_site_code (company_id, site_code),
    FOREIGN KEY fk_branch_id (branch_id) 
        REFERENCES branches(branch_id) 
        ON DELETE CASCADE,
    FOREIGN KEY fk_company_id (company_id) 
        REFERENCES companies(company_id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sites table - Customer locations / security deployment sites';


-- ============================================================================
-- 005: Create Default Permissions for Seed Data
-- ============================================================================

-- Note: Insert default system permissions
INSERT IGNORE INTO permissions (permission_name, permission_description, category, module, action, risk_level)
VALUES
    -- Authentication Permissions
    ('auth:login', 'User can login to system', 'Authentication', 'auth', 'read', 'Low'),
    ('auth:logout', 'User can logout', 'Authentication', 'auth', 'read', 'Low'),
    ('auth:change_password', 'User can change own password', 'Authentication', 'auth', 'update', 'Low'),
    ('auth:reset_password', 'User can reset password', 'Authentication', 'auth', 'update', 'Medium'),
    
    -- User Management Permissions
    ('users:create', 'Create new users', 'Admin', 'users', 'create', 'High'),
    ('users:read', 'View user information', 'Admin', 'users', 'read', 'Low'),
    ('users:update', 'Update user information', 'Admin', 'users', 'update', 'High'),
    ('users:delete', 'Delete users', 'Admin', 'users', 'delete', 'Critical'),
    ('users:reset_password', 'Reset another user password', 'Admin', 'users', 'update', 'High'),
    ('users:assign_role', 'Assign roles to users', 'Admin', 'users', 'update', 'High'),
    
    -- Role Management Permissions
    ('roles:create', 'Create new roles', 'Admin', 'roles', 'create', 'High'),
    ('roles:read', 'View role information', 'Admin', 'roles', 'read', 'Low'),
    ('roles:update', 'Update roles', 'Admin', 'roles', 'update', 'High'),
    ('roles:delete', 'Delete roles', 'Admin', 'roles', 'delete', 'Critical'),
    
    -- Company Management Permissions
    ('company:manage_profile', 'Manage company profile', 'Admin', 'company', 'update', 'High'),
    ('company:manage_settings', 'Manage company settings', 'Admin', 'company', 'update', 'High'),
    ('company:view_usage', 'View company usage statistics', 'Reporting', 'company', 'read', 'Low'),
    
    -- Incidents Permissions
    ('incidents:create', 'Report incidents', 'Incidents', 'incidents', 'create', 'Medium'),
    ('incidents:read', 'View incident details', 'Incidents', 'incidents', 'read', 'Low'),
    ('incidents:update', 'Update incident information', 'Incidents', 'incidents', 'update', 'Medium'),
    ('incidents:approve', 'Approve incident reports', 'Incidents', 'incidents', 'update', 'High'),
    ('incidents:close', 'Close incidents', 'Incidents', 'incidents', 'update', 'Medium'),
    
    -- Attendance Permissions
    ('attendance:clock_in', 'Clock in/out', 'Attendance', 'attendance', 'create', 'Low'),
    ('attendance:view_records', 'View attendance records', 'Attendance', 'attendance', 'read', 'Low'),
    ('attendance:manage_records', 'Manage attendance records', 'Attendance', 'attendance', 'update', 'High'),
    
    -- Reports Permissions
    ('reports:view', 'View reports', 'Reporting', 'reports', 'read', 'Low'),
    ('reports:create', 'Create reports', 'Reporting', 'reports', 'create', 'Medium'),
    ('reports:export', 'Export reports', 'Reporting', 'reports', 'read', 'Medium'),
    ('reports:schedule', 'Schedule automated reports', 'Reporting', 'reports', 'create', 'High'),
    
    -- Admin Permissions
    ('admin:audit_log', 'View audit logs', 'Admin', 'admin', 'read', 'High'),
    ('admin:system_settings', 'Manage system settings', 'Admin', 'admin', 'update', 'Critical'),
    ('admin:view_api_keys', 'View API keys', 'Admin', 'admin', 'read', 'High'),
    ('admin:manage_api_keys', 'Create and revoke API keys', 'Admin', 'admin', 'update', 'Critical');


-- ============================================================================
-- End of Identity & Access Domain Schema
-- ============================================================================
-- Migration Status: Complete
-- Next Step: Seed default company roles and create admin user
-- ============================================================================
