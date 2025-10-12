-- Migration Script: Update Patient UIDs to Match User UIDs
-- This script updates existing patient profiles to use the user's UID from users table
-- Run this ONLY ONCE after deploying the fix

-- ============================================
-- BACKUP FIRST! Create backup of patient_profiles
-- ============================================
CREATE TABLE patient_profiles_backup AS SELECT * FROM patient_profiles;

-- ============================================
-- Step 1: Show current mismatches
-- ============================================
SELECT 
    pp.id,
    pp.patient_uid as old_patient_uid,
    u.uid as user_uid,
    pp.user_id,
    pp.patient_name,
    CASE 
        WHEN pp.patient_uid = u.uid THEN '✓ Already Matches'
        WHEN pp.patient_uid LIKE 'PAT%' THEN '✗ Needs Update (Random)'
        ELSE '✗ Needs Update (Other)'
    END as status
FROM patient_profiles pp
INNER JOIN users u ON pp.user_id = u.id
ORDER BY pp.id;

-- ============================================
-- Step 2: Update patient_uid to match users.uid
-- ============================================
UPDATE patient_profiles pp
INNER JOIN users u ON pp.user_id = u.id
SET pp.patient_uid = u.uid
WHERE pp.patient_uid != u.uid;

-- ============================================
-- Step 3: Verify all patient_uid values match users.uid
-- ============================================
SELECT 
    COUNT(*) as total_profiles,
    SUM(CASE WHEN pp.patient_uid = u.uid THEN 1 ELSE 0 END) as matching,
    SUM(CASE WHEN pp.patient_uid != u.uid THEN 1 ELSE 0 END) as mismatched
FROM patient_profiles pp
INNER JOIN users u ON pp.user_id = u.id;

-- Expected result: matching = total_profiles, mismatched = 0

-- ============================================
-- Step 4: Show final state
-- ============================================
SELECT 
    pp.id,
    pp.patient_uid,
    u.uid as user_uid,
    pp.patient_name,
    '✓ Match' as status
FROM patient_profiles pp
INNER JOIN users u ON pp.user_id = u.id
WHERE pp.patient_uid = u.uid
ORDER BY pp.id;

-- ============================================
-- Step 5 (OPTIONAL): Add foreign key constraint
-- ============================================
-- Only run this after verifying all UIDs match
-- ALTER TABLE patient_profiles 
-- ADD CONSTRAINT fk_patient_user_uid 
-- FOREIGN KEY (patient_uid) 
-- REFERENCES users(uid) 
-- ON UPDATE CASCADE 
-- ON DELETE RESTRICT;

-- ============================================
-- Rollback (if needed)
-- ============================================
-- If something goes wrong, restore from backup:
-- DROP TABLE patient_profiles;
-- CREATE TABLE patient_profiles AS SELECT * FROM patient_profiles_backup;
-- DROP TABLE patient_profiles_backup;
