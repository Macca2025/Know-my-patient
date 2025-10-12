-- Database Performance Indexes
-- Run these to improve query performance

-- Users table
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_active ON users(active);

-- Patient profiles
CREATE INDEX idx_patient_profiles_uid ON patient_profiles(patient_uid);
CREATE INDEX idx_patient_profiles_family_id ON patient_profiles(family_id);

-- Audit log
CREATE INDEX idx_audit_log_user ON audit_log(user_id);
CREATE INDEX idx_audit_log_action ON audit_log(action_type);
CREATE INDEX idx_audit_log_timestamp ON audit_log(timestamp);

-- Card requests
CREATE INDEX idx_card_requests_user ON card_requests(user_id);
CREATE INDEX idx_card_requests_status ON card_requests(status);

-- Support messages
CREATE INDEX idx_support_messages_status ON support_messages(status);
CREATE INDEX idx_support_messages_created ON support_messages(created_at);

-- Onboarding enquiries
CREATE INDEX idx_onboarding_status ON onboarding_enquiries(status);
CREATE INDEX idx_onboarding_created ON onboarding_enquiries(created_at);
