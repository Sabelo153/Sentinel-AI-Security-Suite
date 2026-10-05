-- Defines the database changes in 007_demo_sites.

USE sentinel_ai_security;

INSERT INTO clients (
    company_id,
    client_name,
    contact_person,
    phone,
    email,
    city,
    province,
    country,
    contract_start,
    status
)
SELECT
    c.company_id,
    'Toyota South Africa',
    'Demo Client Contact',
    '+27-11-000-0020',
    'contact@toyota-demo.local',
    'Durban',
    'KwaZulu-Natal',
    'South Africa',
    CURRENT_DATE,
    'ACTIVE'
FROM companies c
WHERE c.company_name = 'ABC Security'
  AND NOT EXISTS (
      SELECT 1
      FROM clients cl
      WHERE cl.company_id = c.company_id
        AND cl.client_name = 'Toyota South Africa'
  );

INSERT INTO branches (
    company_id,
    client_id,
    branch_name,
    phone,
    email,
    city,
    province,
    status
)
SELECT
    c.company_id,
    cl.client_id,
    'Prospecton Plant',
    '+27-31-000-0030',
    'prospecton@toyota-demo.local',
    'Durban',
    'KwaZulu-Natal',
    'ACTIVE'
FROM clients cl
JOIN companies c ON c.company_id = cl.company_id
WHERE cl.client_name = 'Toyota South Africa'
  AND NOT EXISTS (
      SELECT 1
      FROM branches b
      WHERE b.client_id = cl.client_id
        AND b.branch_name = 'Prospecton Plant'
  );

INSERT INTO sites (
    company_id,
    client_id,
    branch_id,
    site_name,
    address,
    city,
    province,
    status
)
SELECT
    c.company_id,
    cl.client_id,
    b.branch_id,
    'Warehouse A',
    '1 Demo Road',
    'Durban',
    'KwaZulu-Natal',
    'ACTIVE'
FROM branches b
JOIN clients cl ON cl.client_id = b.client_id
JOIN companies c ON c.company_id = cl.company_id
WHERE b.branch_name = 'Prospecton Plant'
  AND NOT EXISTS (
      SELECT 1
      FROM sites s
      WHERE s.branch_id = b.branch_id
        AND s.site_name = 'Warehouse A'
  );
