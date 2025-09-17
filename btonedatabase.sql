-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 10, 2025 at 03:35 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

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
  `total_cost` double NOT NULL DEFAULT '0',
  `additional_headcount` int NOT NULL DEFAULT '0',
  `btattendees` int DEFAULT NULL,
  `btservices` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `btmessage` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Approved','Canceled','Completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `payment_status` enum('unpaid','partial','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `btuser_id`, `btaddress`, `btevent`, `btschedule`, `EventDuration`, `total_cost`, `additional_headcount`, `btattendees`, `btservices`, `btmessage`, `status`, `payment_status`, `created_at`) VALUES
(1, 1, 'Rizal', 'Party', '2025-04-14 13:12:00', '2025-04-15 01:12:00', 0, 0, 23, 'Lights,Speakers,Event Place', 'test', 'Pending', 'unpaid', '2025-04-13 21:12:51'),
(2, 5, '525 gondola Street ', 'Weddings', '2025-04-22 15:18:00', '2025-04-23 03:18:00', 0, 0, 56, 'LED Wall,Red Carpet (From entrance to stage),Band Equipment (On venue setup only)', 'mama mo', 'Pending', 'unpaid', '2025-04-21 23:19:10'),
(3, 5, '525 gondola Street ', 'Weddings', '2025-05-23 22:50:00', '2025-05-24 10:50:00', 0, 0, 56, 'Bridal Car - Civic,Bridal Car - Suzuki Jimny', 'hello', 'Pending', 'unpaid', '2025-05-08 06:52:05'),
(4, 5, '525 gondola Street ', 'Weddings', '2025-05-24 23:58:00', '2025-05-25 11:58:00', 0, 0, 56, 'Acrylic Stage - 12x16 Ft,Acrylic Stage - 12x20 Ft', 'hello', 'Pending', 'unpaid', '2025-05-08 07:58:07'),
(5, 6, 'EVERLASTING ST.', 'Graduation', '2025-05-11 23:02:00', '2025-05-12 11:02:00', 0, 0, 80, 'Additional Hour of Usage in Venue,Red Carpet (From entrance to stage),Acrylic Stage - 12x20 Ft', 'hello', 'Pending', 'unpaid', '2025-05-11 15:02:39'),
(6, 1, '123', 'Weddings', '2025-05-23 23:04:00', '2025-05-24 11:04:00', 0, 0, 123, 'Ceremony & Reception Usage Additional Charges', '123', 'Pending', 'unpaid', '2025-05-11 15:05:06'),
(7, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-09 00:05:00', '2025-05-09 12:05:00', 0, 0, 123, 'LED Wall', '123', 'Pending', 'unpaid', '2025-05-11 16:05:28'),
(8, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-31 04:03:00', '2025-06-01 04:03:00', 0, 0, 123, 'LED Wall', 'hello', 'Pending', 'unpaid', '2025-05-11 20:03:39'),
(9, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-17 04:09:00', '2025-05-17 16:09:00', 0, 0, 123, 'Band Equipment (On venue setup only)', 'hi', 'Pending', 'unpaid', '2025-05-11 20:09:35'),
(10, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-12 11:20:00', '2025-05-12 23:20:00', 0, 0, 12, 'LED Wall', 'asdasd', 'Pending', 'unpaid', '2025-05-12 03:20:51'),
(11, 5, '222 Candola', 'Weddings', '2025-05-23 02:00:00', '2025-05-23 14:00:00', 0, 0, 45, 'LED Wall,Ceremony & Reception Usage Additional Charges,Bridal Car - Vios', 'make it glamorues', 'Pending', 'unpaid', '2025-05-14 02:05:33');

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `btuser`
--

INSERT INTO `btuser` (`bt_user_id`, `bt_first_name`, `bt_last_name`, `bt_email`, `bt_phone_number`, `bt_password_hash`, `bt_created_at`, `bt_updated_at`, `bt_is_active`, `bt_privilege_id`) VALUES
(1, 'Ezekiel', 'Vasquez', 'sample@gmail.com', 2147483647, '$2y$10$5NYsPUc..HCNtkW11wQij.llEs.JiEtXOx8lbHVhvVy71fKlety12', '2025-01-27 13:10:45', NULL, 1, 2),
(2, 'zek', 'zek', 'data@gmail.com', 2147483647, '$2y$10$/brk2VoDTrJUOlkKFeL53eA0uab.8vDlDBzx8TvdnQ7UvJpDTp9Da', '2025-01-27 16:35:38', NULL, 1, 1),
(5, 'Geuel', 'Cabello', 'geuelcabello@gmail.com', 2147483647, '$2y$10$FqTA6C8vMaZcjJvU793uieuyP8ysbyDkhAlecO3VF7XzBOXH38Poe', '2025-02-06 09:43:33', NULL, 1, 2),
(6, 'Carrel', 'john', 'buenaventura@gmail.com', 2147483647, '$2y$10$.lj6pj0Dv2lCSlfJlQ5egu9s6.S61dPJJvzo.8MGuRT3ZpdbRMoFu', '2025-05-11 22:52:19', NULL, 1, 2),
(10, 'Lance', 'Mendoza', 'lanceaeronm@gmail.com', 2147483647, '$2y$10$/p9PLSsJ6bxJFNzkwGtgz.BZIa9ZQL7SgO007XB/64bomjRiW5r1q', '2025-08-15 13:42:50', NULL, 1, 1);

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
  `AmountPaid` double NOT NULL DEFAULT '0',
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
