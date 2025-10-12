-- Database Performance Indexes
-- Safe version that drops existing indexes before creating them
-- Run these to improve query performance

-- Note: These statements will show warnings if index doesn't exist, but won't error
-- You can safely run this script multiple times

-- Users table
DROP INDEX IF EXISTS idx_users_email ON users;
CREATE INDEX idx_users_email ON users(email);

DROP INDEX IF EXISTS idx_users_role ON users;
CREATE INDEX idx_users_role ON users(role);

DROP INDEX IF EXISTS idx_users_active ON users;
CREATE INDEX idx_users_active ON users(active);

DROP INDEX IF EXISTS idx_users_email_active ON users;
CREATE INDEX idx_users_email_active ON users(email, active); -- Composite for login queries

-- Patient profiles
DROP INDEX IF EXISTS idx_patient_profiles_uid ON patient_profiles;
CREATE INDEX idx_patient_profiles_uid ON patient_profiles(patient_uid);

DROP INDEX IF EXISTS idx_patient_profiles_user_id ON patient_profiles;
CREATE INDEX idx_patient_profiles_user_id ON patient_profiles(user_id);

DROP INDEX IF EXISTS idx_patient_profiles_family_id ON patient_profiles;
CREATE INDEX idx_patient_profiles_family_id ON patient_profiles(family_id);

DROP INDEX IF EXISTS idx_patient_profiles_nhs_number ON patient_profiles;
CREATE INDEX idx_patient_profiles_nhs_number ON patient_profiles(nhs_number);

-- Audit log
DROP INDEX IF EXISTS idx_audit_log_user ON audit_log;
CREATE INDEX idx_audit_log_user ON audit_log(user_id);

DROP INDEX IF EXISTS idx_audit_log_target_user ON audit_log;
CREATE INDEX idx_audit_log_target_user ON audit_log(target_user_id);

DROP INDEX IF EXISTS idx_audit_log_activity_type ON audit_log;
CREATE INDEX idx_audit_log_activity_type ON audit_log(activity_type);

DROP INDEX IF EXISTS idx_audit_log_timestamp ON audit_log;
CREATE INDEX idx_audit_log_timestamp ON audit_log(timestamp);

DROP INDEX IF EXISTS idx_audit_log_user_timestamp ON audit_log;
CREATE INDEX idx_audit_log_user_timestamp ON audit_log(user_id, timestamp); -- Composite for user activity queries

-- Card requests
DROP INDEX IF EXISTS idx_card_requests_user ON card_requests;
CREATE INDEX idx_card_requests_user ON card_requests(user_id);

DROP INDEX IF EXISTS idx_card_requests_patient_uid ON card_requests;
CREATE INDEX idx_card_requests_patient_uid ON card_requests(patient_uid);

DROP INDEX IF EXISTS idx_card_requests_status ON card_requests;
CREATE INDEX idx_card_requests_status ON card_requests(status);

DROP INDEX IF EXISTS idx_card_requests_request_date ON card_requests;
CREATE INDEX idx_card_requests_request_date ON card_requests(request_date);

-- Support messages
DROP INDEX IF EXISTS idx_support_messages_user ON support_messages;
CREATE INDEX idx_support_messages_user ON support_messages(user_id);

DROP INDEX IF EXISTS idx_support_messages_status ON support_messages;
CREATE INDEX idx_support_messages_status ON support_messages(status);

DROP INDEX IF EXISTS idx_support_messages_created ON support_messages;
CREATE INDEX idx_support_messages_created ON support_messages(created_at);

-- Onboarding enquiries
DROP INDEX IF EXISTS idx_onboarding_status ON onboarding_enquiries;
CREATE INDEX idx_onboarding_status ON onboarding_enquiries(status);

DROP INDEX IF EXISTS idx_onboarding_created ON onboarding_enquiries;
CREATE INDEX idx_onboarding_created ON onboarding_enquiries(created_at);
