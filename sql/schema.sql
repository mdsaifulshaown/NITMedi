-- phpMyAdmin SQL Dump
-- Host: localhost
-- Database: `medical_center`
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `consultants`
-- --------------------------------------------------------
CREATE TABLE `consultants` (
  `consultant_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `consultants` (`consultant_id`, `name`, `email`, `phone`, `password`) VALUES
(1, 'Dr. Saiful', 'binarybin2003@gmail.com', '01643352285', '$2y$12$vhqzkp4YtWlpTkTDKHA4BeSHuGs7yQeLnwyDYnqQesDJouTBRPpIq');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `consultations` (`consultation_id`, `patient_type`, `patient_id`, `consultant_id`, `disease_name`, `consultation_date`, `consultation_time`, `triage_priority`, `symptoms`, `total_price`, `referral_status`, `referral_place`, `referral_reason`, `comments`) VALUES
(8, 'Student', 'B24CS041', 2, 'ghgfh', '2025-09-28', '23:49:00', 'Medium', 'fdf', 0.00, 'No', '', '', ''),
(9, 'Student', 'B24CS041', 2, 'hjkh', '2025-09-28', '23:58:00', 'Low', 'yjg', 0.00, 'No', '', '', ''),
(10, 'Student', 'B24CS041', 2, 'tytryt', '2025-09-29', '00:08:00', 'Medium', 'tyrty', 0.00, 'No', '', '', ''),
(11, 'Student', 'B24CS041', 2, 'dfgfdg', '2025-09-29', '00:19:00', 'Low', 'ss', 0.00, 'No', '', '', ''),
(12, 'Student', 'B24CS041', 2, 'fdgfdg', '2025-09-29', '00:27:00', 'Medium', 'fdsfsgd', 0.00, 'No', '', '', ''),
(13, 'Student', 'B24CS041', 2, 'dfdfdf', '2025-09-29', '00:48:00', 'High', 'dsfdsf', 0.00, 'No', '', '', '');

-- --------------------------------------------------------
-- Table structure for table `faculty`
-- --------------------------------------------------------
CREATE TABLE `faculty` (
  `id` int NOT NULL,
  `faculty_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `faculty` (`id`, `faculty_id`, `name`, `email`, `phone`, `department`) VALUES
(1, '01', 'Sajid Hasan', 'muhammadsaifulshaown@gmail.com', '01643352285', 'CSE');

-- --------------------------------------------------------
-- Table structure for table `medicines`
-- --------------------------------------------------------
CREATE TABLE `medicines` (
  `medicine_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `expiry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `medicines` (`medicine_id`, `name`, `stock`, `price`, `expiry_date`) VALUES
(1, 'Paracetamol', 100, 10.00, '2027-05-01'),
(2, 'Napa', 100, 10.00, '2028-07-19');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `referrals`
-- --------------------------------------------------------
CREATE TABLE `referrals` (
  `referral_id` int NOT NULL,
  `consultation_id` int NOT NULL,
  `place` varchar(255) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `staff`
-- --------------------------------------------------------
CREATE TABLE `staff` (
  `id` int NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `position` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `staff` (`id`, `staff_id`, `name`, `email`, `phone`, `position`) VALUES
(1, '01', 'Abid Islam', 'farukmbc3910@gmail.com', '6033426915', 'Security Guard');

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
  `dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `students` (`id`, `student_id`, `name`, `email`, `phone`, `department`, `dob`) VALUES
(1, 'B24CS041', 'MD SAIFUL ISLAM', 'b24cs041@nitm.ac.in', '6033426915', 'CSE', '2004-01-10');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Admin Shaown', 'b24cs041@nitm.ac.in', '6033426915', 'admin123', 'Admin', '2025-09-28 04:37:39'),
(2, 'Dr. Saiful', 'binarybin2003@gmail.com', '01643352285', 'consult123', 'Consultant', '2025-09-28 04:56:15');

-- --------------------------------------------------------
-- Indexes & AUTO_INCREMENT
-- --------------------------------------------------------
ALTER TABLE `consultants` ADD PRIMARY KEY (`consultant_id`), ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `consultations` ADD PRIMARY KEY (`consultation_id`);
ALTER TABLE `faculty` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `faculty_id` (`faculty_id`), ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `medicines` ADD PRIMARY KEY (`medicine_id`);
ALTER TABLE `prescription` ADD PRIMARY KEY (`prescription_id`), ADD KEY `consultation_id` (`consultation_id`);
ALTER TABLE `referrals` ADD PRIMARY KEY (`referral_id`), ADD KEY `consultation_id` (`consultation_id`);
ALTER TABLE `staff` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `staff_id` (`staff_id`), ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `students` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `student_id` (`student_id`);
ALTER TABLE `users` ADD PRIMARY KEY (`user_id`), ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `consultants` MODIFY `consultant_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `consultations` MODIFY `consultation_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
ALTER TABLE `faculty` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `medicines` MODIFY `medicine_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `prescription` MODIFY `prescription_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `referrals` MODIFY `referral_id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `staff` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `students` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `users` MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------
-- Foreign key constraints
-- --------------------------------------------------------
ALTER TABLE `prescription` ADD CONSTRAINT `prescription_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`);
ALTER TABLE `referrals` ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`);

COMMIT;
