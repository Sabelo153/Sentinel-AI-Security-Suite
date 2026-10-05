-- Defines the database changes in 005_demo_company.

USE sentinel_ai_security;

INSERT INTO companies (
    company_name,
    registration_number,
    vat_number,
    phone,
    email,
    website,
    city,
    province,
    country,
    status
)
SELECT
    'ABC Security',
    'REG-ABC-2026',
    'VAT-ABC-2026',
    '+27-11-000-0000',
    'ops@abcsecurity.local',
    'https://abcsecurity.local',
    'Johannesburg',
    'Gauteng',
    'South Africa',
    'ACTIVE'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM companies c
    WHERE c.company_name = 'ABC Security'
);
