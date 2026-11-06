USE sentinel_ai_security;
CREATE TABLE IF NOT EXISTS alarm_responses (
 alarm_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 incident_id BIGINT UNSIGNED NOT NULL, source_reference VARCHAR(150) NOT NULL, received_at DATETIME NOT NULL,
 dispatch_id BIGINT UNSIGNED NULL, outcome VARCHAR(30) NULL, false_alarm_cause VARCHAR(1000) NULL, resolution VARCHAR(2000) NULL,
 revision INT NOT NULL DEFAULT 1, closed_at DATETIME NULL, UNIQUE KEY uq_alarm_reference(company_id,site_id,source_reference)
);
CREATE TABLE IF NOT EXISTS guard_transport_runs (
 run_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 route_name VARCHAR(150) NOT NULL, vehicle_registration VARCHAR(30) NOT NULL, capacity INT NOT NULL,
 driver_user_id BIGINT UNSIGNED NOT NULL, departs_at DATETIME NOT NULL, pickup_grace_minutes INT NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'PLANNED', revision INT NOT NULL DEFAULT 1, notes VARCHAR(2000) NOT NULL,
 INDEX ix_transport_due(company_id,site_id,departs_at)
);
CREATE TABLE IF NOT EXISTS guard_transport_passengers (
 passenger_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, run_id BIGINT UNSIGNED NOT NULL,
 employee_id BIGINT UNSIGNED NOT NULL, pickup_point VARCHAR(300) NOT NULL, pickup_at DATETIME NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'EXPECTED', boarded_at DATETIME NULL, alighted_at DATETIME NULL,
 recorded_by_user_id BIGINT UNSIGNED NULL, notes VARCHAR(1000) NULL,
 UNIQUE KEY uq_transport_passenger(company_id,run_id,employee_id)
);
CREATE TABLE IF NOT EXISTS site_delivery_plans (
 plan_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL, plan_kind VARCHAR(20) NOT NULL, revision INT NOT NULL,
 title VARCHAR(150) NOT NULL, content_json MEDIUMTEXT NOT NULL, content_hash CHAR(64) NOT NULL,
 effective_on DATE NOT NULL, review_on DATE NOT NULL, published_by_user_id BIGINT UNSIGNED NOT NULL, published_at DATETIME NOT NULL,
 UNIQUE KEY uq_delivery_plan(company_id,site_id,plan_kind,revision)
);
CREATE TABLE IF NOT EXISTS site_delivery_acknowledgements (
 acknowledgement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, plan_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, audience VARCHAR(10) NOT NULL, content_hash CHAR(64) NOT NULL, acknowledged_at DATETIME NOT NULL,
 UNIQUE KEY uq_delivery_ack(company_id,plan_id,user_id,audience)
);
CREATE TABLE IF NOT EXISTS continuity_activations (
 activation_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, plan_id BIGINT UNSIGNED NOT NULL,
 scenario_index INT NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'ACTIVE', revision INT NOT NULL DEFAULT 1,
 reason VARCHAR(2000) NOT NULL, steps_json TEXT NOT NULL, started_by_user_id BIGINT UNSIGNED NOT NULL, started_at DATETIME NOT NULL,
 closed_at DATETIME NULL, debrief VARCHAR(2000) NULL, INDEX ix_continuity_plan(company_id,plan_id,state)
);
CREATE TABLE IF NOT EXISTS equipment_check_schedules (
 schedule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 equipment_id BIGINT UNSIGNED NOT NULL, checklist_json TEXT NOT NULL, interval_days INT NOT NULL,
 assignee_user_id BIGINT UNSIGNED NOT NULL, next_due_at DATETIME NOT NULL, revision INT NOT NULL DEFAULT 1,
 active INT NOT NULL DEFAULT 1, UNIQUE KEY uq_equipment_schedule(company_id,equipment_id)
);
CREATE TABLE IF NOT EXISTS equipment_check_results (
 result_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, schedule_id BIGINT UNSIGNED NOT NULL,
 operation_uuid CHAR(36) NOT NULL, request_hash CHAR(64) NOT NULL, checks_json TEXT NOT NULL, passed INT NOT NULL,
 notes VARCHAR(2000) NOT NULL, maintenance_ticket_id BIGINT UNSIGNED NULL, checked_by_user_id BIGINT UNSIGNED NOT NULL,
 checked_at DATETIME NOT NULL, UNIQUE KEY uq_equipment_check(company_id,operation_uuid)
);
CREATE TABLE IF NOT EXISTS control_room_handovers (
 handover_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 outgoing_user_id BIGINT UNSIGNED NOT NULL, incoming_user_id BIGINT UNSIGNED NOT NULL, snapshot_json MEDIUMTEXT NOT NULL,
 snapshot_hash CHAR(64) NOT NULL, notes VARCHAR(4000) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'PENDING', revision INT NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL, accepted_at DATETIME NULL, acceptance_notes VARCHAR(2000) NULL,
 INDEX ix_control_handover(company_id,site_id,state)
);
CREATE TABLE IF NOT EXISTS extra_service_requests (
 request_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, site_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL, request_type VARCHAR(20) NOT NULL, title VARCHAR(150) NOT NULL, details TEXT NOT NULL,
 starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, required_guards INT NOT NULL, quotation_id BIGINT UNSIGNED NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'REQUESTED', revision INT NOT NULL DEFAULT 1, shift_ids_json TEXT NULL,
 created_by_user_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL, completion_notes VARCHAR(2000) NULL,
 completed_at DATETIME NULL, confirmed_at DATETIME NULL, INDEX ix_extra_service(company_id,site_id,state)
);
CREATE TABLE IF NOT EXISTS client_mobilisation_reviews (
 review_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, onboarding_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, contacts_json TEXT NOT NULL, survey_reference VARCHAR(1000) NOT NULL,
 packet_hash CHAR(64) NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'READY', revision INT NOT NULL DEFAULT 1,
 prepared_by_user_id BIGINT UNSIGNED NOT NULL, prepared_at DATETIME NOT NULL, decision_by_user_id BIGINT UNSIGNED NULL,
 decision_at DATETIME NULL, decision_notes VARCHAR(2000) NULL, UNIQUE KEY uq_client_mobilisation(company_id,onboarding_id)
);
CREATE TABLE IF NOT EXISTS subcontractor_companies (
 subcontractor_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL,
 company_name VARCHAR(150) NOT NULL, registration_reference VARCHAR(150) NOT NULL, contact_details VARCHAR(1000) NOT NULL,
 licence_reference VARCHAR(300) NOT NULL, licence_expires_on DATE NOT NULL, insurance_reference VARCHAR(300) NOT NULL,
 insurance_expires_on DATE NOT NULL, state VARCHAR(20) NOT NULL DEFAULT 'ACTIVE', revision INT NOT NULL DEFAULT 1,
 UNIQUE KEY uq_subcontractor_registration(company_id,registration_reference)
);
CREATE TABLE IF NOT EXISTS subcontractor_sites (
 approval_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, subcontractor_id BIGINT UNSIGNED NOT NULL,
 site_id BIGINT UNSIGNED NOT NULL, valid_until DATE NOT NULL, approved_revision INT NOT NULL,
 approved_by_user_id BIGINT UNSIGNED NOT NULL, approved_at DATETIME NOT NULL,
 UNIQUE KEY uq_subcontractor_site(company_id,subcontractor_id,site_id)
);
CREATE TABLE IF NOT EXISTS subcontractor_users (
 membership_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, subcontractor_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, linked_by_user_id BIGINT UNSIGNED NOT NULL, linked_at DATETIME NOT NULL,
 UNIQUE KEY uq_subcontractor_user(company_id,user_id)
);
CREATE TABLE IF NOT EXISTS service_delivery_history (
 history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, company_id BIGINT UNSIGNED NOT NULL, entity_kind VARCHAR(30) NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL, action_name VARCHAR(30) NOT NULL, notes TEXT NOT NULL,
 actor_user_id BIGINT UNSIGNED NOT NULL, occurred_at DATETIME NOT NULL,
 INDEX ix_delivery_history(company_id,entity_kind,entity_id,history_id)
);
