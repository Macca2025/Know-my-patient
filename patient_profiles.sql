-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 19, 2025 at 09:23 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `know_my_patient`
--

-- --------------------------------------------------------

--
-- Table structure for table `patient_profiles`
--

CREATE TABLE `patient_profiles` (
  `id` int NOT NULL,
  `user_id` varchar(32) NOT NULL,
  `patient_uid` varchar(32) NOT NULL,
  `patient_name` varchar(200) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `nhs_number` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text,
  `postcode` varchar(10) DEFAULT NULL,
  `allergies` text,
  `medical_conditions` text,
  `medications` text,
  `emergency_contact_1_name` varchar(200) DEFAULT NULL,
  `emergency_contact_1_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_1_relationship` varchar(100) DEFAULT NULL,
  `gp_name` varchar(200) DEFAULT NULL,
  `gp_practice` varchar(200) DEFAULT NULL,
  `gp_phone` varchar(20) DEFAULT NULL,
  `additional_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` varchar(32) DEFAULT NULL,
  `updated_by` varchar(32) DEFAULT NULL,
  `lpa_health_attorney_name` varchar(255) DEFAULT NULL,
  `lpa_health_attorney_phone` varchar(20) DEFAULT NULL,
  `lpa_health_document_name` varchar(255) DEFAULT NULL,
  `lpa_health_document_path` varchar(500) DEFAULT NULL,
  `lpa_finance_attorney_name` varchar(255) DEFAULT NULL,
  `lpa_finance_attorney_phone` varchar(20) DEFAULT NULL,
  `lpa_finance_document_name` varchar(255) DEFAULT NULL,
  `lpa_finance_document_path` varchar(500) DEFAULT NULL,
  `lpa_additional_notes` text,
  `has_respect_form` enum('yes','no','unknown') DEFAULT NULL,
  `resuscitation_status` enum('full_active','cpr_not_indicated','do_not_attempt','unknown') DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `workplace` varchar(255) DEFAULT NULL,
  `important_memories` text,
  `has_dementia` enum('yes','no','unknown') DEFAULT NULL,
  `has_learning_disability` enum('yes','no','unknown') DEFAULT NULL,
  `previous_stroke` enum('yes','no','unknown') DEFAULT NULL,
  `other_cognitive_conditions` varchar(500) DEFAULT NULL,
  `stroke_effects` text,
  `communication_needs` text,
  `diet_type` enum('normal','vegetarian','vegan','halal','kosher','gluten_free','diabetic','low_sodium','soft_foods','pureed','other') DEFAULT NULL,
  `fluid_consistency` enum('normal','nectar_thick','honey_thick','pudding_thick') DEFAULT NULL,
  `special_diet_notes` text,
  `food_preferences` text,
  `food_dislikes` text,
  `personal_likes` text,
  `personal_dislikes` text,
  `religion` varchar(100) DEFAULT NULL,
  `cultural_needs` text,
  `funeral_arrangements` enum('burial','cremation','natural_burial','donation_to_science','other','not_specified') DEFAULT NULL,
  `organ_donation` enum('yes','no','unknown') DEFAULT NULL,
  `funeral_details_notes` text,
  `advance_directives` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_uid` (`patient_uid`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_nhs_number` (`nhs_number`),
  ADD KEY `idx_patient_profiles_resuscitation_status` (`resuscitation_status`),
  ADD KEY `idx_patient_profiles_diet_type` (`diet_type`),
  ADD KEY `idx_patient_profiles_uid` (`patient_uid`),
  ADD KEY `idx_patient_profiles_user_id` (`user_id`),
  ADD KEY `idx_patient_profiles_nhs_number` (`nhs_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
