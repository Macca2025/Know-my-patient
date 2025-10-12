-- Database Performance Indexes
-- Simple version - just creates indexes
-- If an index already exists, you'll get an error but other indexes will still be created

-- INSTRUCTIONS:
-- 1. Run this in phpMyAdmin SQL tab
-- 2. If you see "Duplicate key name" errors, that's OK - it means those indexes already exist
-- 3. Indexes that don't exist will be created successfully

-- Users table (4 indexes)
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_active ON users(active);
CREATE INDEX idx_users_email_active ON users(email, active);

-- Patient profiles (4 indexes)
CREATE INDEX idx_patient_profiles_uid ON patient_profiles(patient_uid);
CREATE INDEX idx_patient_profiles_user_id ON patient_profiles(user_id);
CREATE INDEX idx_patient_profiles_family_id ON patient_profiles(family_id);
CREATE INDEX idx_patient_profiles_nhs_number ON patient_profiles(nhs_number);

-- Audit log (5 indexes)
CREATE INDEX idx_audit_log_user ON audit_log(user_id);
CREATE INDEX idx_audit_log_target_user ON audit_log(target_user_id);
CREATE INDEX idx_audit_log_activity_type ON audit_log(activity_type);
CREATE INDEX idx_audit_log_timestamp ON audit_log(timestamp);
CREATE INDEX idx_audit_log_user_timestamp ON audit_log(user_id, timestamp);

-- Card requests (4 indexes)
CREATE INDEX idx_card_requests_user ON card_requests(user_id);
CREATE INDEX idx_card_requests_patient_uid ON card_requests(patient_uid);
CREATE INDEX idx_card_requests_status ON card_requests(status);
CREATE INDEX idx_card_requests_request_date ON card_requests(request_date);

-- Support messages (3 indexes)
CREATE INDEX idx_support_messages_user ON support_messages(user_id);
CREATE INDEX idx_support_messages_status ON support_messages(status);
CREATE INDEX idx_support_messages_created ON support_messages(created_at);

-- Onboarding enquiries (2 indexes)
CREATE INDEX idx_onboarding_status ON onboarding_enquiries(status);
CREATE INDEX idx_onboarding_created ON onboarding_enquiries(created_at);

-- DONE! 22 indexes total
-- Check results with: SHOW INDEX FROM users;
