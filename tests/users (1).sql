-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 15, 2025 at 10:28 AM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `uid` varchar(64) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','nhs_user','admin','family') DEFAULT 'patient',
  `is_verified` tinyint(1) DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` int NOT NULL DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspended_by` varchar(255) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `uid`, `first_name`, `last_name`, `email`, `password`, `role`, `is_verified`, `email_verified_at`, `created_at`, `updated_at`, `active`, `last_login`, `suspended_at`, `suspended_by`, `verification_token_expires`, `remember_token`) VALUES
(11, 'da3aedcff3f5623fd8999d04535fa5a2', 'kirsty', 'hall', 'kirsty@kirstyandmacauly.xyz', '$2y$12$NZM2afHj9e4ONUXNZzKPgeCaQ1M1vRPKwUzoXUj.mytiVMccS6hOq', 'patient', 1, '2025-09-17 21:45:56', '2025-09-17 21:37:42', '2025-10-13 15:31:34', 1, '2025-09-18 09:53:14', NULL, NULL, NULL, NULL),
(12, '18cf5fe16207919bc54a1ceb5c088ead', 'macauly', 'Eggleton', 'macauly@gmail.com', '$2y$12$8.TgJeaQ36Pqnsav4PuG0OXJ5ue2apHphPi9wL7f3vT4gJhdPB3.K', 'admin', 0, NULL, '2025-10-12 10:14:15', '2025-10-12 10:15:01', 1, '2025-10-12 10:15:01', NULL, NULL, NULL, NULL),
(13, '97f1ba0c2be1307a30499a894ce30155', 'patient', 'patient', 'patient@gmail.com', '$2y$12$jROnOKPhF24o5opKrXOWR.2I/LsA.5d7N2lcHgl3lNUUmD2ULyJU6', 'nhs_user', 1, NULL, '2025-10-12 10:17:57', '2025-10-15 10:26:41', 1, '2025-10-15 10:26:41', NULL, NULL, NULL, NULL),
(14, 'eb40aaf793c947ba5274219eea861cde', 'macauly', 'eggleton', 'eggleton.mac@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$TFRpNnBKdXk1aU83Z0RLNg$ZUF5TnzBd+TXZaVNUcQTZYSKL+8WQd2fcXtXwAN/T2Y', 'patient', 0, NULL, '2025-10-15 07:37:38', '2025-10-15 07:37:38', 1, NULL, NULL, NULL, NULL, NULL),
(15, '19829ce7240ef201b099fb1ca35eef6a', 'macauly', 'eggleton', 'macaulysmeggleton@icloud.com', '$argon2id$v=19$m=65536,t=4,p=1$aGIvNVI1VlB6TmgxYjRjdA$mLO8XyenBmdTPPO/5gImOJlIHXwkvsgK/zBhU5etUjA', 'patient', 0, NULL, '2025-10-15 08:38:05', '2025-10-15 08:38:21', 1, '2025-10-15 08:38:21', NULL, NULL, NULL, NULL),
(16, '02af1c682ce13dc25288d4f9d674966e', 'jhokhb', 'jvhjhv', 'patient@outlook.com', '$argon2id$v=19$m=65536,t=4,p=1$TnMyT0RLdHJsbUIzVncvVQ$4SlCkGR06r5NiLY+3CAGtW7Dryp/e4kJDbWM6vTd78E', 'patient', 0, NULL, '2025-10-15 08:53:13', '2025-10-15 08:53:13', 1, NULL, NULL, NULL, NULL, NULL),
(17, '962603610d89485f189e8d124c8ceec0', 'yftuygi', 'yfcgvjhbk', 'ykfugyliho@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$Sm1xTVBmdDNWckIxNkpVTw$aqQpA/47yMfoZElvQChQRygyMNCDSd6YnkTxK7L2Q5s', 'patient', 0, NULL, '2025-10-15 08:57:21', '2025-10-15 08:57:21', 1, NULL, NULL, NULL, NULL, NULL),
(18, 'efbc2e7f1001b78329e76743c0144a7b', 'jhigkgh', 'hghg', 'hello@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$VTVsUGtVajRQM0w1YzI5WQ$jm27ZXwuG5u4SQAkhZDFO7tqamLncDuu9wQfN4/6AP4', 'patient', 0, NULL, '2025-10-15 09:04:08', '2025-10-15 09:04:08', 1, NULL, NULL, NULL, NULL, NULL),
(19, 'eab8741558b815451f8ddc1e44b1550f', 'hghg', 'hgghgh', 'ghhg@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$M2dma25WWmRGTThJR3VEdw$Hxjb4UCSrkrcl53fU7U1Hp8Im7L6eHM1dj6Y2C7IkjM', 'patient', 0, NULL, '2025-10-15 09:11:51', '2025-10-15 09:11:51', 1, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`active`),
  ADD KEY `idx_users_email_active` (`email`,`active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
