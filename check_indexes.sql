-- Check which indexes already exist in the database
-- Run this to see current index status

SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    INDEX_TYPE
FROM 
    information_schema.STATISTICS
WHERE 
    TABLE_SCHEMA = 'know_my_patient'
    AND TABLE_NAME IN ('users', 'patient_profiles', 'audit_log', 'card_requests', 'support_messages', 'onboarding_enquiries')
ORDER BY 
    TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
