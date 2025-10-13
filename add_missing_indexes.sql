-- Add Missing Database Indexes
-- Run each statement separately - ignore "Duplicate key name" errors

-- ==============================================
-- USERS TABLE
-- ==============================================
-- Add composite index for login queries (email + active)
-- Improves: SELECT * FROM users WHERE email = ? AND active = 1
CREATE INDEX idx_users_email_active ON users(email, active);

-- ==============================================
-- PATIENT PROFILES  
-- ==============================================
-- Add family_id index for family member lookups
-- Improves: SELECT * FROM patient_profiles WHERE family_id = ?
CREATE INDEX idx_patient_profiles_family_id ON patient_profiles(family_id);
