USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS photo_report_templates (
 template_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 template_name VARCHAR(120) NOT NULL, organisation_name VARCHAR(150) NOT NULL, footer_text VARCHAR(500) NOT NULL,
 accent_colour CHAR(7) NOT NULL, images_per_page INT NOT NULL, logo_base64 MEDIUMTEXT NULL,
 logo_mime VARCHAR(20) NULL, created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL
);
CREATE TABLE IF NOT EXISTS photo_reports (
 photo_report_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, template_id BIGINT UNSIGNED NOT NULL, title VARCHAR(150) NOT NULL,
 summary TEXT NOT NULL, revision INT NOT NULL DEFAULT 1, state VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
 approved_by_user_id BIGINT UNSIGNED NULL, approved_at DATETIME NULL
);
CREATE TABLE IF NOT EXISTS photo_report_items (
 item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 photo_report_id BIGINT UNSIGNED NOT NULL, evidence_id BIGINT UNSIGNED NOT NULL,
 caption VARCHAR(2000) NOT NULL, sort_order INT NOT NULL, annotations_json TEXT NOT NULL,
 UNIQUE KEY uq_photo_report_order(company_id,photo_report_id,sort_order)
);
