-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET NAMES utf8mb4 */;

-- Database: `medical_center`

-- --------------------------------------------------------
-- Table structure for table `consultants`
-- --------------------------------------------------------
CREATE TABLE `consultants` (
  `consultant_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `consultants`
  ADD PRIMARY KEY (`consultant_id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `consultants`
  MODIFY `consultant_id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `consultations`
-- --------------------------------------------------------
CREATE TABLE `consultations` (
  `consultation_id` int NOT NULL,
  `patient_type` varchar(50) NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `consultant_id` int NOT NULL,
  `disease_name` varchar(255) NOT NULL,
  `consultation_date` date NOT NULL,
  `consultation_time` time NOT NULL,
  `triage_priority` varchar(50) NOT NULL,
  `symptoms` text,
  `total_price` decimal(10,2) DEFAULT '0.00',
  `referral_status` varchar(10) DEFAULT 'No',
  `referral_place` varchar(255) DEFAULT NULL,
  `referral_reason` varchar(255) DEFAULT NULL,
  `comments` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `consultations`
  ADD PRIMARY KEY (`consultation_id`);

ALTER TABLE `consultations`
  MODIFY `consultation_id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `faculty`
-- --------------------------------------------------------
CREATE TABLE `faculty` (
  `id` int NOT NULL,
  `faculty_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `total_costs` decimal(20,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_id` (`faculty_id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `faculty`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `medicines`
-- --------------------------------------------------------
CREATE TABLE `medicines` (
  `medicine_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `expiry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`);

ALTER TABLE `medicines`
  MODIFY `medicine_id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `prescription`
-- --------------------------------------------------------
CREATE TABLE `prescription` (
  `prescription_id` int NOT NULL,
  `consultation_id` int NOT NULL,
  `medicine_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `prescription`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `consultation_id` (`consultation_id`);

ALTER TABLE `prescription`
  MODIFY `prescription_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `prescription`
  ADD CONSTRAINT `prescription_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`);

-- --------------------------------------------------------
-- Table structure for table `referrals`
-- --------------------------------------------------------
CREATE TABLE `referrals` (
  `referral_id` int NOT NULL,
  `consultation_id` int NOT NULL,
  `place` varchar(255) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `consultation_id` (`consultation_id`);

ALTER TABLE `referrals`
  MODIFY `referral_id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`);

-- --------------------------------------------------------
-- Table structure for table `staff`
-- --------------------------------------------------------
CREATE TABLE `staff` (
  `id` int NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `position` varchar(50) NOT NULL,
  `total_costs` decimal(20,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `students`
-- --------------------------------------------------------
CREATE TABLE `students` (
  `id` int NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `total_costs` decimal(20,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Consultant') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT;

COMMIT;
