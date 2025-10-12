-- Database Performance Indexes
-- Run ONE statement at a time in phpMyAdmin
-- If you get "Duplicate key name" error, that index already exists - skip to next one

-- ==============================================
-- USERS TABLE (4 indexes)
-- ==============================================
CREATE INDEX idx_users_email ON users(email);
-- If error "Duplicate key name 'idx_users_email'" - index already exists, continue to next

CREATE INDEX idx_users_role ON users(role);
-- If error - already exists, continue

CREATE INDEX idx_users_active ON users(active);
-- If error - already exists, continue

CREATE INDEX idx_users_email_active ON users(email, active);
-- Composite index for login queries (WHERE email = ? AND active = 1)

-- ==============================================
-- PATIENT PROFILES (4 indexes)
-- ==============================================
CREATE INDEX idx_patient_profiles_uid ON patient_profiles(patient_uid);

CREATE INDEX idx_patient_profiles_user_id ON patient_profiles(user_id);

CREATE INDEX idx_patient_profiles_family_id ON patient_profiles(family_id);

CREATE INDEX idx_patient_profiles_nhs_number ON patient_profiles(nhs_number);

-- ==============================================
-- AUDIT LOG (5 indexes)
-- ==============================================
CREATE INDEX idx_audit_log_user ON audit_log(user_id);

CREATE INDEX idx_audit_log_target_user ON audit_log(target_user_id);

CREATE INDEX idx_audit_log_activity_type ON audit_log(activity_type);

CREATE INDEX idx_audit_log_timestamp ON audit_log(timestamp);

CREATE INDEX idx_audit_log_user_timestamp ON audit_log(user_id, timestamp);
-- Composite index for user activity queries

-- ==============================================
-- CARD REQUESTS (4 indexes)
-- ==============================================
CREATE INDEX idx_card_requests_user ON card_requests(user_id);

CREATE INDEX idx_card_requests_patient_uid ON card_requests(patient_uid);

CREATE INDEX idx_card_requests_status ON card_requests(status);

CREATE INDEX idx_card_requests_request_date ON card_requests(request_date);

-- ==============================================
-- SUPPORT MESSAGES (3 indexes)
-- ==============================================
CREATE INDEX idx_support_messages_user ON support_messages(user_id);

CREATE INDEX idx_support_messages_status ON support_messages(status);

CREATE INDEX idx_support_messages_created ON support_messages(created_at);

-- ==============================================
-- ONBOARDING ENQUIRIES (2 indexes)
-- ==============================================
CREATE INDEX idx_onboarding_status ON onboarding_enquiries(status);

CREATE INDEX idx_onboarding_created ON onboarding_enquiries(created_at);

-- ==============================================
-- VERIFICATION
-- ==============================================
-- After running, verify with:
-- SHOW INDEX FROM users;
-- SHOW INDEX FROM patient_profiles;
-- SHOW INDEX FROM audit_log;
-- SHOW INDEX FROM card_requests;
-- SHOW INDEX FROM support_messages;
-- SHOW INDEX FROM onboarding_enquiries;
