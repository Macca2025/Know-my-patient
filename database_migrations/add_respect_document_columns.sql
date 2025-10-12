-- Migration: Add ReSPECT document columns to patient_profiles table
-- Date: 2025-10-12
-- Description: Adds columns to store uploaded ReSPECT form documents

ALTER TABLE `patient_profiles` 
ADD COLUMN `respect_document_name` VARCHAR(255) DEFAULT NULL AFTER `has_respect_form`,
ADD COLUMN `respect_document_path` VARCHAR(500) DEFAULT NULL AFTER `respect_document_name`;

-- Add comment for clarity
ALTER TABLE `patient_profiles` 
MODIFY COLUMN `respect_document_name` VARCHAR(255) DEFAULT NULL COMMENT 'Original filename of uploaded ReSPECT form',
MODIFY COLUMN `respect_document_path` VARCHAR(500) DEFAULT NULL COMMENT 'Server path to stored ReSPECT form document';
