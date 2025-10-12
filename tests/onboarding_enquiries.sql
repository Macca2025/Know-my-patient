-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 12, 2025 at 07:27 PM
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
-- Table structure for table `onboarding_enquiries`
--

CREATE TABLE `onboarding_enquiries` (
  `id` int NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organization_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `organization_size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_systems` text COLLATE utf8mb4_unicode_ci,
  `integration_timeline` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specific_requirements` text COLLATE utf8mb4_unicode_ci,
  `additional_info` text COLLATE utf8mb4_unicode_ci,
  `gdpr_consent` tinyint(1) DEFAULT '0',
  `marketing_consent` tinyint(1) DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `assigned_to` int DEFAULT NULL,
  `estimated_value` decimal(10,2) DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `demo_scheduled` timestamp NULL DEFAULT NULL,
  `proposal_sent_date` date DEFAULT NULL,
  `decision_deadline` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `lead_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'website',
  `utm_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_contacted` timestamp NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `onboarding_enquiries`
--
ALTER TABLE `onboarding_enquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_organization_type` (`organization_type`),
  ADD KEY `idx_assigned_to` (`assigned_to`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_follow_up_date` (`follow_up_date`),
  ADD KEY `idx_status_assigned` (`status`,`assigned_to`),
  ADD KEY `idx_company_name` (`company_name`),
  ADD KEY `idx_priority_status` (`priority`,`status`),
  ADD KEY `idx_status_followup` (`status`,`follow_up_date`),
  ADD KEY `idx_created_status` (`created_at`,`status`),
  ADD KEY `idx_organization_size` (`organization_type`,`organization_size`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `onboarding_enquiries`
--
ALTER TABLE `onboarding_enquiries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
