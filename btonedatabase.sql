-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 13, 2025 at 04:53 AM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `btonedatabase`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `profile_picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','employee') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'employee',
  `reset_code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code_expiry` datetime DEFAULT NULL,
  `last_request_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `email`, `password`, `created_at`, `first_name`, `last_name`, `profile_picture`, `role`, `reset_code`, `code_expiry`, `last_request_time`) VALUES
(1, 'admin@example.com', '$2y$10$NdWy5LG/0qI8BrzdXYHd..MLzjKsZFrR70XQT7O5yST12fjvZxOXO', '2025-01-24 06:04:05', 'Lance', 'Mendoza', NULL, 'admin', NULL, NULL, NULL),
(25, 'admin3@example.com', '$2y$10$rcjetnFVZ121gpBlrO8/DedUFXjFGDL6aErV.BCOFryX5XllczoQq', '2025-02-24 10:51:38', 'dsadsa', 'Lebron', NULL, 'employee', NULL, NULL, NULL),
(28, 'admin@example2.com', 'reset_required', '2025-04-11 04:44:16', 'James', 'Lebron', NULL, 'employee', NULL, NULL, NULL),
(31, 'lanceaeronm@gmail.com', '$2y$10$VVRMwg7fl2J/TsjGiJgQi.brf0a8NSDF5vrZqO4HzFmEtD2IkTyJi', '2025-05-09 09:21:23', 'James', 'David', NULL, 'employee', NULL, NULL, NULL),
(32, 'admin33@example.com', '$2y$10$jq9zmnV0BZ3RIJcbZVteCODA/AIJq/.XKfO4UvGOHyvgnqJCIbfli', '2025-05-11 08:17:56', 'dsadsa', 'Lebron', NULL, 'employee', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `archived_event_types`
--

DROP TABLE IF EXISTS `archived_event_types`;
CREATE TABLE IF NOT EXISTS `archived_event_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_users`
--

DROP TABLE IF EXISTS `archived_users`;
CREATE TABLE IF NOT EXISTS `archived_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `profile_picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','employee') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin_user',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_users`
--

INSERT INTO `archived_users` (`id`, `original_id`, `email`, `first_name`, `last_name`, `profile_picture`, `role`, `archived_at`, `account_type`) VALUES
(6, 30, 'aeronm49@gmai.com', 'Natoy Ange', 'Lebron', NULL, 'employee', '2025-05-09 09:16:56', 'admin_user');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `btuser_id` int NOT NULL,
  `btaddress` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btevent` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btschedule` datetime DEFAULT NULL,
  `EventDuration` datetime DEFAULT NULL,
  `btattendees` int DEFAULT NULL,
  `btservices` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `btmessage` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `btuser_id`, `btaddress`, `btevent`, `btschedule`, `EventDuration`, `btattendees`, `btservices`, `btmessage`, `created_at`) VALUES
(1, 1, 'Rizal', 'Party', '2025-04-14 13:12:00', '2025-04-15 01:12:00', 23, 'Lights,Speakers,Event Place', 'test', '2025-04-13 21:12:51'),
(2, 5, '525 gondola Street ', 'Weddings', '2025-04-22 15:18:00', '2025-04-23 03:18:00', 56, 'LED Wall,Red Carpet (From entrance to stage),Band Equipment (On venue setup only)', 'mama mo', '2025-04-21 23:19:10'),
(3, 5, '525 gondola Street ', 'Weddings', '2025-05-23 22:50:00', '2025-05-24 10:50:00', 56, 'Bridal Car - Civic,Bridal Car - Suzuki Jimny', 'hello', '2025-05-08 06:52:05'),
(4, 5, '525 gondola Street ', 'Weddings', '2025-05-24 23:58:00', '2025-05-25 11:58:00', 56, 'Acrylic Stage - 12x16 Ft,Acrylic Stage - 12x20 Ft', 'hello', '2025-05-08 07:58:07'),
(5, 6, 'EVERLASTING ST.', 'Graduation', '2025-05-11 23:02:00', '2025-05-12 11:02:00', 80, 'Additional Hour of Usage in Venue,Red Carpet (From entrance to stage),Acrylic Stage - 12x20 Ft', 'hello', '2025-05-11 15:02:39'),
(6, 1, '123', 'Weddings', '2025-05-23 23:04:00', '2025-05-24 11:04:00', 123, 'Ceremony & Reception Usage Additional Charges', '123', '2025-05-11 15:05:06'),
(7, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-09 00:05:00', '2025-05-09 12:05:00', 123, 'LED Wall', '123', '2025-05-11 16:05:28'),
(8, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-31 04:03:00', '2025-06-01 04:03:00', 123, 'LED Wall', 'hello', '2025-05-11 20:03:39'),
(9, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-17 04:09:00', '2025-05-17 16:09:00', 123, 'Band Equipment (On venue setup only)', 'hi', '2025-05-11 20:09:35'),
(10, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-12 11:20:00', '2025-05-12 23:20:00', 12, 'LED Wall', 'asdasd', '2025-05-12 03:20:51'),
(11, 5, '222 Candola', 'Weddings', '2025-05-23 02:00:00', '2025-05-23 14:00:00', 45, 'LED Wall,Ceremony & Reception Usage Additional Charges,Bridal Car - Vios', 'make it glamorues', '2025-05-14 02:05:33');

-- --------------------------------------------------------

--
-- Table structure for table `booking_equipment`
--

DROP TABLE IF EXISTS `booking_equipment`;
CREATE TABLE IF NOT EXISTS `booking_equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int DEFAULT NULL,
  `equipment_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `rental_start` date DEFAULT NULL,
  `rental_end` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `equipment_id` (`equipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_equipment`
--

INSERT INTO `booking_equipment` (`id`, `booking_id`, `equipment_id`, `quantity`, `rental_start`, `rental_end`) VALUES
(1, 11, 11, 2, '2025-02-06', '2025-02-06'),
(2, 11, 12, 2, '2025-02-06', '2025-02-06'),
(3, 12, 11, 3, '2025-03-05', '2025-03-05'),
(4, 12, 12, 3, '2025-03-05', '2025-03-05'),
(5, 12, 14, 4, '2025-03-05', '2025-03-05'),
(6, 13, 11, 5, '2025-03-03', '2025-03-03'),
(7, 13, 12, 4, '2025-03-03', '2025-03-03'),
(8, 13, 14, 6, '2025-03-03', '2025-03-03'),
(9, 14, 11, 3, '2025-03-04', '2025-03-04'),
(10, 14, 12, 4, '2025-03-04', '2025-03-04'),
(11, 15, 11, 1, '2025-04-17', '2025-04-17'),
(12, 15, 12, 1, '2025-04-17', '2025-04-17'),
(13, 15, 14, 1, '2025-04-17', '2025-04-17'),
(14, 15, 17, 1, '2025-04-17', '2025-04-17'),
(15, 15, 16, 1, '2025-04-17', '2025-04-17'),
(16, 16, 11, 7, '2025-04-17', '2025-04-17'),
(17, 16, 12, 1, '2025-04-17', '2025-04-17'),
(18, 16, 14, 1, '2025-04-17', '2025-04-17'),
(19, 16, 17, 1, '2025-04-17', '2025-04-17'),
(20, 16, 16, 1, '2025-04-17', '2025-04-17'),
(21, 17, 11, 1, '2025-05-06', '2025-05-06'),
(22, 17, 14, 1, '2025-05-06', '2025-05-06'),
(23, 17, 16, 1, '2025-05-06', '2025-05-06'),
(24, 18, 16, 1, '2025-05-08', '2025-05-08'),
(25, 19, 11, 1, '2025-05-12', '2025-05-12'),
(26, 20, 17, 1, '2025-05-14', '2025-05-14'),
(27, 21, 12, 1, '2025-05-13', '2025-05-13'),
(28, 21, 14, 1, '2025-05-13', '2025-05-13'),
(29, 22, 14, 1, '2025-05-07', '2025-05-07'),
(30, 22, 17, 1, '2025-05-07', '2025-05-07'),
(31, 23, 11, 1, '2025-05-22', '2025-05-22'),
(32, 23, 16, 1, '2025-05-22', '2025-05-22'),
(33, 24, 11, 1, '2025-05-22', '2025-05-22'),
(34, 24, 16, 1, '2025-05-22', '2025-05-22'),
(35, 25, 11, 1, '2025-05-22', '2025-05-22'),
(36, 25, 16, 1, '2025-05-22', '2025-05-22'),
(38, 27, 12, 1, '2025-05-23', '2025-05-23'),
(42, 30, 14, 1, '2025-05-13', '2025-05-13'),
(43, 31, 11, 1, '2025-05-29', '2025-05-29'),
(45, 33, 11, 1, '2025-05-21', '2025-05-21'),
(46, 33, 14, 1, '2025-05-21', '2025-05-21'),
(47, 34, 11, 1, '2025-05-16', '2025-05-16'),
(48, 34, 14, 1, '2025-05-16', '2025-05-16'),
(49, 34, 17, 1, '2025-05-16', '2025-05-16'),
(50, 35, 11, 1, '2025-05-16', '2025-05-16'),
(51, 35, 16, 1, '2025-05-16', '2025-05-16'),
(52, 36, 12, 1, '2025-05-30', '2025-05-30'),
(53, 37, 11, 1, '2025-05-29', '2025-05-29'),
(54, 37, 12, 1, '2025-05-29', '2025-05-29'),
(55, 38, 11, 1, '2025-05-02', '2025-05-02'),
(56, 38, 12, 1, '2025-05-02', '2025-05-02'),
(57, 39, 12, 1, '2025-05-23', '2025-05-23'),
(58, 40, 11, 1, '2025-05-23', '2025-05-23'),
(59, 40, 12, 1, '2025-05-23', '2025-05-23'),
(60, 41, 11, 1, '2025-05-23', '2025-05-23'),
(61, 41, 12, 1, '2025-05-23', '2025-05-23'),
(62, 42, 11, 1, '2025-06-29', '2025-06-29'),
(63, 42, 14, 1, '2025-06-29', '2025-06-29'),
(64, 43, 11, 1, '2025-05-16', '2025-05-16'),
(65, 44, 11, 1, '2025-05-17', '2025-05-17'),
(66, 44, 17, 1, '2025-05-17', '2025-05-17'),
(67, 45, 11, 1, '2025-05-16', '2025-05-16'),
(68, 45, 12, 1, '2025-05-16', '2025-05-16');

-- --------------------------------------------------------

--
-- Table structure for table `btuser`
--

DROP TABLE IF EXISTS `btuser`;
CREATE TABLE IF NOT EXISTS `btuser` (
  `bt_user_id` int NOT NULL AUTO_INCREMENT,
  `bt_first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bt_last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bt_email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bt_phone_number` int NOT NULL,
  `bt_password_hash` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bt_created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bt_updated_at` datetime DEFAULT NULL,
  `bt_is_active` tinyint NOT NULL DEFAULT '1',
  `bt_privilege_id` int NOT NULL DEFAULT '2',
  PRIMARY KEY (`bt_user_id`),
  UNIQUE KEY `email_unique` (`bt_email`),
  KEY `fk_btuser_privilege` (`bt_privilege_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `btuser`
--

INSERT INTO `btuser` (`bt_user_id`, `bt_first_name`, `bt_last_name`, `bt_email`, `bt_phone_number`, `bt_password_hash`, `bt_created_at`, `bt_updated_at`, `bt_is_active`, `bt_privilege_id`) VALUES
(1, 'Ezekiel', 'Vasquez', 'sample@gmail.com', 2147483647, '$2y$10$5NYsPUc..HCNtkW11wQij.llEs.JiEtXOx8lbHVhvVy71fKlety12', '2025-01-27 13:10:45', NULL, 1, 2),
(2, 'zek', 'zek', 'data@gmail.com', 2147483647, '$2y$10$/brk2VoDTrJUOlkKFeL53eA0uab.8vDlDBzx8TvdnQ7UvJpDTp9Da', '2025-01-27 16:35:38', NULL, 1, 1),
(5, 'Geuel', 'Cabello', 'geuelcabello@gmail.com', 2147483647, '$2y$10$FqTA6C8vMaZcjJvU793uieuyP8ysbyDkhAlecO3VF7XzBOXH38Poe', '2025-02-06 09:43:33', NULL, 1, 2),
(6, 'Carrel', 'john', 'buenaventura@gmail.com', 2147483647, '$2y$10$.lj6pj0Dv2lCSlfJlQ5egu9s6.S61dPJJvzo.8MGuRT3ZpdbRMoFu', '2025-05-11 22:52:19', NULL, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `btuserprivilege`
--

DROP TABLE IF EXISTS `btuserprivilege`;
CREATE TABLE IF NOT EXISTS `btuserprivilege` (
  `bt_privilege_id` int NOT NULL,
  `bt_privilege_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`bt_privilege_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `btuserprivilege`
--

INSERT INTO `btuserprivilege` (`bt_privilege_id`, `bt_privilege_name`) VALUES
(1, 'ADMIN'),
(2, 'USER');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `archived` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `archived`) VALUES
(1, 'coffee', 0),
(2, 'breakfast', 0),
(3, 'addons', 0),
(4, 'milktea', 0);

-- --------------------------------------------------------

--
-- Table structure for table `client_users`
--

DROP TABLE IF EXISTS `client_users`;
CREATE TABLE IF NOT EXISTS `client_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `verification_code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `verification_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_users`
--

INSERT INTO `client_users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone_number`, `verification_code`, `is_verified`, `verification_expiry`, `created_at`, `updated_at`, `last_login`, `status`) VALUES
(1, 'lanceaeronm@gmail.com', '$2y$10$O75m.M..wK8sw//u6wRmz.YNFzzEknT8b8JoeysV1B1Bhr9jvP7l.', 'Lance', 'Mendoza', '09666057991', '9043', 1, '2025-05-11 20:25:33', '2025-05-11 12:15:33', '2025-05-11 13:23:16', '2025-05-11 21:23:16', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `customer_inquiries`
--

DROP TABLE IF EXISTS `customer_inquiries`;
CREATE TABLE IF NOT EXISTS `customer_inquiries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `contact_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inquiry_date` date NOT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `status` enum('Pending','Confirmed','Ongoing','Completed','Cancelled','Archived','In Cart') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `event_package` enum('Wedding','18th Birthday','Welcome Party','Portable Bar','Portable Sound and Lights','Catering') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `location_type` enum('On-site','Custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'On-site',
  `additional_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `down_payment_status` enum('pending','partial','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `down_payment_amount` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('pending','partial','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `travel_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `time_slot` enum('Morning','Afternoon','Evening') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci GENERATED ALWAYS AS ((case when (cast(`event_time` as time) < _cp850'12:00:00') then _cp850'Morning' when (cast(`event_time` as time) < _cp850'17:00:00') then _cp850'Afternoon' else _cp850'Evening' end)) STORED,
  `payment_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `payment_verified` tinyint(1) DEFAULT '0',
  `payment_method_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inquiry_date` (`inquiry_date`),
  KEY `idx_event_package` (`event_package`),
  KEY `idx_status` (`status`),
  KEY `status` (`status`),
  KEY `event_date` (`event_date`),
  KEY `customer_name` (`customer_name`),
  KEY `idx_booking_time` (`event_date`,`time_slot`,`location_type`,`status`),
  KEY `fk_payment_method` (`payment_method_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_inquiries`
--

INSERT INTO `customer_inquiries` (`id`, `customer_name`, `contact_email`, `contact_phone`, `inquiry_date`, `event_date`, `event_time`, `status`, `event_package`, `location_type`, `additional_details`, `total_cost`, `down_payment_status`, `down_payment_amount`, `payment_status`, `created_at`, `updated_at`, `travel_fee`, `last_updated`, `payment_notes`, `payment_verified`, `payment_method_id`) VALUES
(2, 'Jane Smith', 'jane@example.com', '555-5678', '2024-01-20', '2024-03-15', NULL, 'Completed', 'Welcome Party', 'On-site', 'Corporate welcome event', 3500.00, 'pending', 0.00, 'pending', '2025-01-24 05:58:14', '2025-03-18 11:26:48', 0.00, '2025-03-18 11:26:48', NULL, 0, NULL),
(3, 'Mike Johnson', 'mike@example.com', '555-9012', '2024-01-25', '2024-04-10', NULL, 'Completed', 'Portable Bar', 'On-site', 'Birthday party bar service', 1200.00, 'pending', 0.00, 'pending', '2025-01-24 05:58:14', '2025-03-18 11:31:15', 0.00, '2025-03-18 11:31:15', NULL, 0, NULL),
(4, 'Sarah Williams', 'sarah@example.com', '555-3456', '2024-02-01', '2024-05-05', NULL, 'Confirmed', 'Catering', 'On-site', 'Company annual dinner', 7500.00, 'pending', 0.00, 'pending', '2025-01-24 05:58:14', '2025-01-24 05:58:14', 0.00, '2025-03-18 11:16:02', NULL, 0, NULL),
(8, 'Sofia Rodriguez', 'sofia.r@email.com', '345-678-9012', '2025-02-01', '2025-08-01', NULL, 'Completed', 'Welcome Party', 'On-site', 'Corporate welcome party for new employees, 75 guests expected', 3750.00, 'paid', 0.00, 'pending', '2025-02-01 09:21:34', '2025-04-10 03:07:19', 0.00, '2025-04-10 03:07:19', NULL, 0, NULL),
(9, 'David Chen', 'david.c@email.com', '456-789-0123', '2025-02-01', '2025-08-15', NULL, 'Pending', 'Portable Bar', 'On-site', 'Beach party setup, needs mobile bar and bartender service', 1200.00, 'partial', 0.00, 'pending', '2025-02-01 09:21:34', '2025-04-11 04:04:49', 0.00, '2025-04-11 04:04:49', NULL, 0, NULL),
(11, 'Lanz ', 'lanceaeronm@gmail.com', '0966057991', '2025-02-06', '2025-02-06', NULL, 'Confirmed', 'Welcome Party', 'On-site', '', 1900.00, 'pending', 0.00, 'pending', '2025-02-06 01:48:30', '2025-03-01 04:56:10', 0.00, '2025-03-18 11:16:02', NULL, 0, NULL),
(12, 'PAPA p', 'lanceaeronm@gmail.com', '0966057991', '2025-03-01', '2025-03-05', NULL, 'Completed', 'Catering', 'On-site', '', 3250.00, 'pending', 0.00, 'pending', '2025-03-01 06:42:55', '2025-03-18 11:27:02', 0.00, '2025-03-18 11:27:02', NULL, 0, NULL),
(13, 'MAMA D', 'lanceaeronm@gmail.com', '0966057991', '2025-03-01', '2025-03-03', NULL, 'Confirmed', '18th Birthday', 'On-site', '', 4550.00, 'pending', 0.00, 'pending', '2025-03-01 06:43:38', '2025-03-01 13:34:43', 0.00, '2025-03-18 11:16:02', NULL, 0, NULL),
(14, 'MAMA D', 'lanceaeronm@gmail.com', '0966057991', '2025-03-01', '2025-03-04', NULL, 'Confirmed', 'Welcome Party', 'On-site', '', 3650.00, 'pending', 0.00, 'pending', '2025-03-01 06:44:15', '2025-03-01 13:34:43', 0.00, '2025-03-18 11:16:02', NULL, 0, NULL),
(15, 'Lanz ', 'lanceaeronm@gmail.com', '0966057991', '2025-04-06', '2025-04-17', '15:24:00', 'Pending', 'Wedding', 'On-site', '0', 1420.00, 'pending', 0.00, 'pending', '2025-04-06 07:24:46', '2025-04-06 07:53:08', 0.00, '2025-04-06 07:53:08', NULL, 0, NULL),
(16, 'Lance', 'lanceaeronm@gmail.com', '0966057991', '2025-04-09', '2025-04-17', '17:48:00', 'Confirmed', 'Wedding', 'On-site', '0', 2320.00, 'paid', 1856.00, 'partial', '2025-04-09 09:48:19', '2025-04-11 04:13:06', 0.00, '2025-04-11 04:13:06', NULL, 0, NULL),
(17, 'Carrel', 'carrel@gmail.com', '09`12345678', '2025-05-11', '2025-05-06', '23:49:00', 'Confirmed', 'Welcome Party', 'On-site', '0', 600.00, 'pending', 0.00, 'pending', '2025-05-11 15:49:33', '2025-05-11 20:08:25', 0.00, '2025-05-11 20:08:25', NULL, 0, NULL),
(18, 'Carrel', 'buenaventura@gmail.com', '0912314341', '2025-05-12', '2025-05-08', '04:08:00', 'Confirmed', 'Portable Bar', 'On-site', '0', 850.00, 'pending', 0.00, 'pending', '2025-05-11 20:08:02', '2025-05-11 20:08:25', 500.00, '2025-05-11 20:08:25', NULL, 0, NULL),
(19, 'Carrel', 'carrel@gmail.com', '0912314341', '2025-05-12', '2025-05-12', '11:24:00', 'Pending', 'Wedding', 'On-site', '0', 150.00, 'pending', 0.00, 'pending', '2025-05-12 03:24:20', '2025-05-12 03:24:20', 0.00, '2025-05-12 03:24:20', NULL, 0, NULL),
(20, 'DANIELA CAYETANO PAPA', 'papadaniela408@gmail.com', '09`12345678', '2025-05-12', '2025-05-14', '11:32:00', 'Pending', '18th Birthday', 'On-site', '0', 20.00, 'pending', 0.00, 'pending', '2025-05-12 03:30:54', '2025-05-12 03:30:54', 0.00, '2025-05-12 03:30:54', NULL, 0, NULL),
(21, 'DANIELA CAYETANO PAPA', 'papadaniela408@gmail.com', '0912314341', '2025-05-12', '2025-05-13', '12:05:00', 'Pending', 'Catering', 'On-site', '0', 900.00, 'pending', 0.00, 'pending', '2025-05-12 04:04:34', '2025-05-12 04:04:34', 0.00, '2025-05-12 04:04:34', NULL, 0, NULL),
(22, 'Admin', 'admin@example.com', '92709123', '2025-05-12', '2025-05-07', '13:00:00', 'In Cart', '18th Birthday', 'On-site', '0', 120.00, 'pending', 0.00, 'pending', '2025-05-12 06:06:18', '2025-05-12 06:06:18', 0.00, '2025-05-12 06:06:18', NULL, 0, NULL),
(23, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-22', '19:10:00', 'Pending', '18th Birthday', 'On-site', 'hi', 500.00, 'pending', 0.00, 'pending', '2025-05-12 11:08:03', '2025-05-12 11:08:03', 0.00, '2025-05-12 11:08:03', NULL, 0, NULL),
(24, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-22', '19:10:00', 'In Cart', '18th Birthday', 'On-site', 'hi', 500.00, 'pending', 0.00, 'pending', '2025-05-12 11:08:07', '2025-05-12 11:08:07', 0.00, '2025-05-12 11:08:07', NULL, 0, NULL),
(25, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-22', '19:10:00', 'In Cart', '18th Birthday', 'On-site', 'hi', 500.00, 'pending', 0.00, 'pending', '2025-05-12 11:08:09', '2025-05-12 11:08:09', 0.00, '2025-05-12 11:08:09', NULL, 0, NULL),
(27, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-23', '19:14:00', 'Pending', 'Wedding', 'On-site', '', 800.00, 'pending', 0.00, 'pending', '2025-05-12 11:10:50', '2025-05-12 11:10:50', 0.00, '2025-05-12 11:10:50', NULL, 0, NULL),
(30, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-13', '19:54:00', 'Pending', 'Catering', 'On-site', 'none', 100.00, 'pending', 0.00, 'pending', '2025-05-12 11:48:58', '2025-05-12 11:48:58', 0.00, '2025-05-12 11:48:58', NULL, 0, NULL),
(31, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-29', '23:56:00', 'Pending', 'Portable Bar', 'On-site', '', 650.00, 'pending', 0.00, 'pending', '2025-05-12 11:56:18', '2025-05-12 11:56:18', 500.00, '2025-05-12 11:56:18', NULL, 0, NULL),
(33, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-21', '20:28:00', 'Pending', 'Catering', 'On-site', '', 250.00, 'paid', 250.00, 'paid', '2025-05-12 12:23:27', '2025-05-12 13:08:37', 0.00, '2025-05-12 13:08:37', NULL, 0, NULL),
(34, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-16', '20:45:00', 'In Cart', 'Welcome Party', 'On-site', '', 270.00, 'paid', 260.00, 'partial', '2025-05-12 12:44:39', '2025-05-12 13:00:03', 0.00, '2025-05-12 13:00:03', NULL, 0, NULL),
(35, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-16', '20:52:00', 'In Cart', 'Portable Sound and Lights', 'On-site', '', 1000.00, 'pending', 0.00, 'pending', '2025-05-12 12:50:38', '2025-05-12 12:50:38', 500.00, '2025-05-12 12:50:38', NULL, 0, NULL),
(36, 'Geuel Cabello', 'admin@example.com', '92709123', '2025-05-12', '2025-05-30', '21:32:00', 'Pending', 'Portable Bar', 'On-site', '', 1300.00, 'paid', 520.00, 'partial', '2025-05-12 13:27:15', '2025-05-12 13:27:48', 500.00, '2025-05-12 13:27:48', NULL, 0, NULL),
(37, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-29', '21:00:00', 'Pending', 'Welcome Party', 'On-site', '', 950.00, 'pending', 0.00, 'pending', '2025-05-12 13:56:28', '2025-05-12 13:56:28', 0.00, '2025-05-12 13:56:28', NULL, 0, NULL),
(38, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-02', '21:58:00', 'Pending', 'Welcome Party', 'On-site', '', 950.00, 'pending', 0.00, 'pending', '2025-05-12 13:57:44', '2025-05-12 13:57:44', 0.00, '2025-05-12 13:57:44', NULL, 0, NULL),
(39, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-23', '14:19:00', 'Pending', 'Wedding', 'On-site', '', 800.00, 'pending', 0.00, 'pending', '2025-05-12 14:19:43', '2025-05-12 14:19:43', 0.00, '2025-05-12 14:19:43', NULL, 0, NULL),
(40, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-23', '14:19:00', 'Pending', 'Wedding', 'On-site', '', 950.00, 'paid', 380.00, 'partial', '2025-05-12 14:20:23', '2025-05-12 14:20:34', 0.00, '2025-05-12 14:20:34', NULL, 0, NULL),
(41, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-23', '14:19:00', 'Pending', 'Wedding', 'On-site', '', 950.00, 'paid', 380.00, 'partial', '2025-05-12 14:33:25', '2025-05-12 14:37:23', 0.00, '2025-05-12 14:37:23', NULL, 1, NULL),
(42, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-06-29', '22:40:00', 'Pending', 'Wedding', 'On-site', '', 250.00, 'paid', 100.00, 'partial', '2025-05-12 14:39:01', '2025-05-12 14:39:19', 0.00, '2025-05-12 14:39:19', NULL, 1, NULL),
(43, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-16', '22:43:00', 'Pending', 'Portable Sound and Lights', 'On-site', '', 650.00, 'paid', 260.00, 'partial', '2025-05-12 14:41:29', '2025-05-12 14:41:32', 500.00, '2025-05-12 14:41:32', NULL, 1, NULL),
(44, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-17', '22:45:00', 'Pending', 'Catering', 'On-site', '', 170.00, 'paid', 68.00, 'partial', '2025-05-12 14:42:48', '2025-05-12 14:42:52', 0.00, '2025-05-12 14:42:52', NULL, 1, NULL),
(45, 'Geuel Cabello', 'geuelcabello@gmail.com', '92709123', '2025-05-12', '2025-05-16', '15:44:00', 'Pending', 'Portable Bar', 'On-site', '', 1450.00, 'paid', 580.00, 'partial', '2025-05-12 14:44:31', '2025-05-12 14:44:34', 500.00, '2025-05-12 14:44:34', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
CREATE TABLE IF NOT EXISTS `employee` (
  `employee_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `hire_date` date NOT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `time_in` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `time_out` datetime NOT NULL,
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_dates` (`start_time`,`end_time`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `start_time`, `end_time`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Team Meeting', '2024-01-15 09:00:00', '2024-01-15 11:00:00', 'Monthly team sync', '2025-01-28 09:51:22', '2025-01-28 09:51:22'),
(2, 'Client Call', '2024-01-15 14:00:00', '2024-01-15 15:30:00', 'Project review call', '2025-01-28 09:51:22', '2025-01-28 09:51:22'),
(3, 'Training Session', '2024-01-20 10:00:00', '2024-01-20 13:00:00', 'New system training', '2025-01-28 09:51:22', '2025-01-28 09:51:22'),
(4, 'Project Review', '2024-02-01 13:00:00', '2024-02-01 15:00:00', 'Q1 project status review', '2025-01-28 09:51:22', '2025-01-28 09:51:22'),
(5, 'Workshop', '2024-02-15 09:00:00', '2024-02-15 12:00:00', 'Design thinking workshop', '2025-01-28 09:51:22', '2025-01-28 09:51:22'),
(6, 'Board Meeting', '2024-03-01 10:00:00', '2024-03-01 12:00:00', 'Monthly board review', '2025-01-28 09:51:22', '2025-01-28 09:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` enum('Sound Equipment','Lighting Equipment','Display Equipment','Catering Equipment','Furniture','Decorations') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `supplier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reorder_level` int NOT NULL,
  `last_restock_date` datetime DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `available_quantity` int DEFAULT '0',
  `rented_quantity` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_inventory_category` (`category`),
  KEY `idx_inventory_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `category`, `quantity`, `unit_price`, `supplier`, `reorder_level`, `last_restock_date`, `status`, `created_at`, `updated_at`, `available_quantity`, `rented_quantity`) VALUES
(11, 'Professional Speaker', 'Sound Equipment', 20, 150.00, 'Sound Pro Inc.', 5, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-03-01 06:44:15', 7, 13),
(12, 'Wireless Microphone', 'Sound Equipment', 15, 800.00, 'Sound Pro Inc.', 3, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-03-01 06:44:15', 2, 13),
(13, 'LED Wall Panel', '', 50, 200.00, 'Visual Tech Ltd.', 10, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-02-02 07:52:35', 50, 0),
(14, 'Stage Light', 'Lighting Equipment', 30, 100.00, 'Light Masters', 6, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-03-01 06:43:38', 20, 10),
(15, 'Fog Machine', '', 8, 120.00, 'Effects Plus', 2, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-02-02 07:52:35', 8, 0),
(16, 'Round Table', 'Furniture', 40, 350.00, 'Event Furnish', 10, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-02-04 11:15:06', 40, 0),
(17, 'Banquet Chair', 'Furniture', 400, 20.00, 'Event Furnish', 50, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-02-02 07:52:35', 400, 0),
(18, 'Line Array Speaker System', 'Sound Equipment', 4, 8000.00, 'Sound Pro Inc.', 1, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(19, 'Mixer 16 Channel', 'Sound Equipment', 5, 2500.00, 'Audio Tech', 1, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(20, 'Powered Subwoofer', 'Sound Equipment', 8, 2000.00, 'Sound Pro Inc.', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(21, 'Stage Platform 4x8', 'Display Equipment', 12, 1500.00, 'Event Essentials', 3, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(22, 'Fog Machine', 'Lighting Equipment', 6, 1200.00, 'Light Masters', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(23, 'Tiffany Chair', 'Furniture', 200, 50.00, 'Event Essentials', 20, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(24, 'Cocktail Table', 'Furniture', 20, 300.00, 'Event Essentials', 5, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(25, 'Coffee Urn', 'Catering Equipment', 10, 1000.00, 'Kitchen Pro', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(26, 'Stage Light Truss 10ft', 'Lighting Equipment', 10, 1800.00, 'Light Masters', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(27, 'LED Strip Light 5m', 'Lighting Equipment', 20, 500.00, 'Light Masters', 5, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(28, 'Portable Aircon', 'Display Equipment', 8, 3500.00, 'Event Essentials', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(29, 'Photo Backdrop Stand', 'Display Equipment', 5, 800.00, 'Display Solutions', 1, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(30, 'Professional Speaker 1000W', 'Sound Equipment', 10, 200.00, 'Sound Pro Inc.', 2, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(31, 'Wireless Microphone', 'Sound Equipment', 15, 50.00, 'Audio Tech', 3, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(32, 'LED Wall Panel 4x4', 'Display Equipment', 8, 500.00, 'Display Solutions', 2, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(33, 'LED Par Light', 'Lighting Equipment', 20, 75.00, 'Light Masters', 5, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(34, 'Moving Head Light', 'Lighting Equipment', 12, 150.00, 'Light Masters', 3, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(35, 'Round Table', 'Furniture', 30, 40.00, 'Event Essentials', 5, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(36, 'Chafing Dish', 'Catering Equipment', 25, 45.00, 'Kitchen Pro', 5, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(37, 'Table Cloth', 'Decorations', 50, 15.00, 'Event Essentials', 10, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('coffee','food','addons') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_medium` decimal(10,2) NOT NULL,
  `price_large` decimal(10,2) DEFAULT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `category`, `price_medium`, `price_large`, `image_path`, `created_at`) VALUES
(1, 'Americano Coffee', 'coffee', 70.00, 90.00, NULL, '2025-01-13 21:52:21'),
(2, 'Vanilla Latte', 'coffee', 80.00, 95.00, NULL, '2025-01-13 21:52:21'),
(3, 'Cafe Latte', 'coffee', 80.00, 95.00, NULL, '2025-01-13 21:52:21'),
(4, 'Caramel Macchiato', 'coffee', 125.00, NULL, NULL, '2025-01-13 21:52:21'),
(5, 'Cappuccino', 'coffee', 80.00, 95.00, NULL, '2025-01-13 21:52:21'),
(6, 'Matcha Espresso', 'coffee', 100.00, 120.00, NULL, '2025-01-13 21:52:21'),
(7, 'Cafe Mocha', 'coffee', 100.00, 120.00, NULL, '2025-01-13 21:52:21'),
(8, 'Affogato', 'coffee', 100.00, 120.00, NULL, '2025-01-13 21:52:21'),
(9, 'Spanish Latte', 'coffee', 90.00, 110.00, NULL, '2025-01-13 21:52:21'),
(10, 'Lechon Kawali', 'food', 70.00, 90.00, NULL, '2025-01-13 21:52:21'),
(11, 'Burger Steak', 'food', 80.00, 95.00, NULL, '2025-01-13 21:52:21'),
(12, 'Sisig', 'food', 80.00, 95.00, NULL, '2025-01-13 21:52:21');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `sales_id` int NOT NULL AUTO_INCREMENT,
  `btuser_id` int NOT NULL,
  `booking_id` int NOT NULL,
  `GcashReferenceNo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `TotalAmount` double NOT NULL,
  `AmountPaid` double DEFAULT NULL,
  `Status` smallint NOT NULL DEFAULT '1',
  `DateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateUpdate` datetime DEFAULT NULL,
  `userUpdated_Id` int DEFAULT NULL,
  PRIMARY KEY (`sales_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

DROP TABLE IF EXISTS `service`;
CREATE TABLE IF NOT EXISTS `service` (
  `services_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double NOT NULL,
  PRIMARY KEY (`services_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`services_id`, `name`, `price`) VALUES
(1, 'LED Wall', 16500),
(2, 'Additional Hour of Usage in Venue', 1000),
(3, 'Ceremony & Reception Usage Additional Charges', 2000),
(4, 'Bridal Car - Vios', 3500),
(5, 'Bridal Car - Civic', 4500),
(6, 'Bridal Car - Suzuki Jimny', 5500),
(7, 'Red Carpet (From entrance to stage)', 1000),
(8, 'Dressing room usage (2hrs ahead before event)', 350),
(9, 'Acrylic Stage - 12x16 Ft', 8500),
(10, 'Acrylic Stage - 12x20 Ft', 10500),
(11, 'Band Equipment (On venue setup only)', 3500);

-- --------------------------------------------------------

--
-- Table structure for table `users table`
--

DROP TABLE IF EXISTS `users table`;
CREATE TABLE IF NOT EXISTS `users table` (
  `user_id` int UNSIGNED NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
