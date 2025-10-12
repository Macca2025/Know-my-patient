-- Database Performance Indexes
-- Safe version that won't error if indexes already exist
-- Run these to improve query performance

-- Users table
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(active);
CREATE INDEX IF NOT EXISTS idx_users_email_active ON users(email, active); -- Composite for login queries

-- Patient profiles
CREATE INDEX IF NOT EXISTS idx_patient_profiles_uid ON patient_profiles(patient_uid);
CREATE INDEX IF NOT EXISTS idx_patient_profiles_user_id ON patient_profiles(user_id);
CREATE INDEX IF NOT EXISTS idx_patient_profiles_family_id ON patient_profiles(family_id);
CREATE INDEX IF NOT EXISTS idx_patient_profiles_nhs_number ON patient_profiles(nhs_number);

-- Audit log
CREATE INDEX IF NOT EXISTS idx_audit_log_user ON audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_target_user ON audit_log(target_user_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_activity_type ON audit_log(activity_type);
CREATE INDEX IF NOT EXISTS idx_audit_log_timestamp ON audit_log(timestamp);
CREATE INDEX IF NOT EXISTS idx_audit_log_user_timestamp ON audit_log(user_id, timestamp); -- Composite for user activity queries

-- Card requests
CREATE INDEX IF NOT EXISTS idx_card_requests_user ON card_requests(user_id);
CREATE INDEX IF NOT EXISTS idx_card_requests_patient_uid ON card_requests(patient_uid);
CREATE INDEX IF NOT EXISTS idx_card_requests_status ON card_requests(status);
CREATE INDEX IF NOT EXISTS idx_card_requests_request_date ON card_requests(request_date);

-- Support messages
CREATE INDEX IF NOT EXISTS idx_support_messages_user ON support_messages(user_id);
CREATE INDEX IF NOT EXISTS idx_support_messages_status ON support_messages(status);
CREATE INDEX IF NOT EXISTS idx_support_messages_created ON support_messages(created_at);

-- Onboarding enquiries
CREATE INDEX IF NOT EXISTS idx_onboarding_status ON onboarding_enquiries(status);
CREATE INDEX IF NOT EXISTS idx_onboarding_created ON onboarding_enquiries(created_at);
