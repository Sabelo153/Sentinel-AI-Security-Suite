-- Defines the database changes in 004_company_settings_seed.

USE sentinel_ai_security;

INSERT INTO company_settings (
    company_id,
    timezone,
    time_format,
    language_code,
    theme,
    gps_enabled,
    ai_enabled,
    settings_json
)
SELECT
    c.company_id,
    'Africa/Johannesburg',
    '24H',
    'en',
    'LIGHT',
    TRUE,
    TRUE,
    JSON_OBJECT(
        'default_dashboard', 'operations',
        'shift_buffer_minutes', 15,
        'incident_escalation_enabled', TRUE
    )
FROM companies c
LEFT JOIN company_settings s
    ON s.company_id = c.company_id
WHERE s.company_setting_id IS NULL;
