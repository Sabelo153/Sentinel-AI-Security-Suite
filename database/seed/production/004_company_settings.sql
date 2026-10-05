-- Defines the database changes in 004_company_settings.

USE sentinel_ai_security;

-- Production profile uses top-level seed files managed by seed_plan.php.
SELECT 'Use run_seeds.php with --profile=production' AS info_message;
