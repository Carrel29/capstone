-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 24, 2025 at 06:56 PM
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
-- Table structure for table `archived_catering_addons`
--

DROP TABLE IF EXISTS `archived_catering_addons`;
CREATE TABLE IF NOT EXISTS `archived_catering_addons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text,
  `archived_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int DEFAULT NULL,
  `reason` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_catering_dishes`
--

DROP TABLE IF EXISTS `archived_catering_dishes`;
CREATE TABLE IF NOT EXISTS `archived_catering_dishes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('Pork','Chicken','Fish','Vegetables','Pasta','Dessert','Juice','Soup','Appetizer') NOT NULL,
  `description` text,
  `archived_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int DEFAULT NULL,
  `reason` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_catering_packages`
--

DROP TABLE IF EXISTS `archived_catering_packages`;
CREATE TABLE IF NOT EXISTS `archived_catering_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `min_attendees` int NOT NULL DEFAULT '100',
  `dish_count` int NOT NULL,
  `includes` text NOT NULL,
  `archived_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int DEFAULT NULL,
  `reason` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `archived_catering_packages`
--

INSERT INTO `archived_catering_packages` (`id`, `original_id`, `name`, `base_price`, `min_attendees`, `dish_count`, `includes`, `archived_at`, `archived_by`, `reason`) VALUES
(1, 1, '4-Dish Package', 10000.00, 100, 4, 'Rental service, Buffet service, Registration/gift/cake tables, Complete silverware, Steamed rice, Drinks, Desserts', '2025-09-24 13:06:27', 5, 'dupe\r\n'),
(2, 2, '5-Dish Package', 15000.00, 100, 5, 'Rental service, Buffet service, Registration/gift/cake tables, Complete silverware, Steamed rice, Drinks, Desserts', '2025-09-24 13:06:38', 5, 'dupe');

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
-- Table structure for table `archived_inventory`
--

DROP TABLE IF EXISTS `archived_inventory`;
CREATE TABLE IF NOT EXISTS `archived_inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text,
  `category` varchar(100) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `available_quantity` int DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `reorder_level` int DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int DEFAULT NULL,
  `reason` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_packages`
--

DROP TABLE IF EXISTS `archived_packages`;
CREATE TABLE IF NOT EXISTS `archived_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `base_attendees` int NOT NULL,
  `min_attendees` int DEFAULT '100',
  `max_attendees` int DEFAULT '150',
  `excess_price` decimal(10,2) NOT NULL,
  `duration` int NOT NULL,
  `includes` text NOT NULL,
  `archived_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int DEFAULT NULL,
  `reason` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `archived_services`
--

DROP TABLE IF EXISTS `archived_services`;
CREATE TABLE IF NOT EXISTS `archived_services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text,
  `category` varchar(50) DEFAULT 'General',
  `archived_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by` int DEFAULT NULL,
  `reason` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `status` enum('Pending','Approved','Canceled','Completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `payment_status` enum('unpaid','partial','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `btuser_id`, `btaddress`, `btevent`, `btschedule`, `EventDuration`, `total_cost`, `additional_headcount`, `btattendees`, `btservices`, `btmessage`, `status`, `payment_status`, `created_at`) VALUES
(1, 1, 'Rizal', 'Party', '2025-04-14 13:12:00', '2025-04-15 01:12:00', 0, 0, 23, 'Lights,Speakers,Event Place', 'test', 'Pending', 'unpaid', '2025-04-13 21:12:51'),
(5, 6, 'EVERLASTING ST.', 'Graduation', '2025-05-11 23:02:00', '2025-05-12 11:02:00', 0, 0, 80, 'Additional Hour of Usage in Venue,Red Carpet (From entrance to stage),Acrylic Stage - 12x20 Ft', 'hello', 'Pending', 'unpaid', '2025-05-11 15:02:39'),
(6, 1, '123', 'Weddings', '2025-05-23 23:04:00', '2025-05-24 11:04:00', 0, 0, 123, 'Ceremony & Reception Usage Additional Charges', '123', 'Pending', 'unpaid', '2025-05-11 15:05:06'),
(7, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-09 00:05:00', '2025-05-09 12:05:00', 0, 0, 123, 'LED Wall', '123', 'Pending', 'unpaid', '2025-05-11 16:05:28'),
(8, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-31 04:03:00', '2025-06-01 04:03:00', 0, 0, 123, 'LED Wall', 'hello', 'Approved', 'unpaid', '2025-05-11 20:03:39'),
(9, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-17 04:09:00', '2025-05-17 16:09:00', 0, 0, 123, 'Band Equipment (On venue setup only)', 'hi', 'Approved', 'unpaid', '2025-05-11 20:09:35'),
(10, 6, 'EVERLASTING ST.', 'Weddings', '2025-05-12 11:20:00', '2025-05-12 23:20:00', 0, 0, 12, 'LED Wall', 'asdasd', 'Pending', 'unpaid', '2025-05-12 03:20:51'),
(13, 10, 'asdasd', 'Birthday Party', '2025-09-27 08:00:00', '2025-09-27 14:00:00', 25000, 0, 30, '', '', 'Approved', 'partial', '2025-09-19 06:36:23'),
(14, 10, '20hdasklhd', 'Graduation', '2025-09-28 09:00:00', '2025-09-28 15:00:00', 22000, 0, 40, '', '', 'Approved', 'partial', '2025-09-19 08:12:02'),
(15, 5, 'gkjgk', 'Corporate Event', '2025-09-26 09:00:00', '2025-09-26 15:00:00', 300, 0, 44, '', '', 'Pending', 'unpaid', '2025-09-19 14:26:16'),
(16, 5, 'asdasdas', 'Wedding Package', '2025-09-25 09:00:00', '2025-09-25 17:00:00', 50000, 0, 100, '', '', 'Pending', 'unpaid', '2025-09-21 04:13:24'),
(17, 5, 'asdasd', 'Corporate Event', '2025-09-25 10:00:00', '2025-09-25 16:00:00', 40000, 0, 100, '', '', 'Pending', 'unpaid', '2025-09-22 07:02:51'),
(19, 5, 'asdasd', 'Corporate Event', '2025-09-25 20:10:00', '2025-09-26 02:10:00', 40000, 0, 100, '', '', 'Pending', 'unpaid', '2025-09-22 09:10:41'),
(20, 5, 'asdasd', 'Wedding Package', '2025-09-24 09:00:00', '2025-09-24 17:00:00', 50000, 0, 100, '', '', 'Pending', 'unpaid', '2025-09-23 10:59:05'),
(21, 5, 'gkjgk', 'Corporate Event', '2025-09-25 09:00:00', '2025-09-25 15:00:00', 43500, 0, 100, 'Band Equipment (On venue setup only)', '', 'Pending', 'unpaid', '2025-09-24 12:18:24'),
(22, 5, 'gkjgk', 'Corporate Event', '2025-09-25 20:23:00', '2025-09-26 02:23:00', 50500, 0, 100, 'Acrylic Stage - 12x20 Ft', '', 'Pending', 'unpaid', '2025-09-24 12:23:26'),
(23, 5, 'gkjgk', 'Wedding Package', '2025-09-25 20:23:00', '2025-09-26 04:23:00', 50000, 0, 100, '', '', 'Pending', 'unpaid', '2025-09-24 12:23:43'),
(24, 5, 'gkjgk', 'Wedding Package', '2025-09-25 22:18:00', '2025-09-26 06:18:00', 51120, 0, 100, 'Red Carpet (From entrance to stage)', '', 'Pending', 'unpaid', '2025-09-24 14:18:21'),
(25, 5, 'gkjgk', 'Wedding Package', '2025-09-25 22:18:00', '2025-09-26 06:18:00', 51120, 0, 100, 'Red Carpet (From entrance to stage)', '', 'Pending', 'unpaid', '2025-09-24 15:01:47'),
(26, 5, 'gkjgk', 'Wedding Package', '2025-09-25 23:02:00', '2025-09-26 07:02:00', 51120, 0, 100, 'Red Carpet (From entrance to stage)', '', 'Pending', 'unpaid', '2025-09-24 15:02:11'),
(27, 5, 'gkjgk', 'Corporate Event', '2025-09-25 15:10:00', '2025-09-25 21:10:00', 41350, 0, 100, 'Red Carpet (From entrance to stage)', '', 'Pending', 'unpaid', '2025-09-24 15:10:13'),
(28, 5, 'EVERLASTING ST.', 'Birthday Party', '2025-09-27 09:00:00', '2025-09-27 15:00:00', 28500, 0, 100, 'Bridal Car - Vios', '', 'Pending', 'unpaid', '2025-09-24 15:13:09'),
(29, 5, 'gkjgk', 'Wedding Package', '2025-09-25 02:27:00', '2025-09-25 10:27:00', 63500, 0, 100, 'Bridal Car - Vios', '', 'Pending', 'paid', '2025-09-24 17:27:57'),
(30, 5, 'gkjgk', 'Wedding Package', '2025-09-26 09:00:00', '2025-09-26 17:00:00', 50000, 0, 100, '', '', 'Pending', 'unpaid', '2025-09-24 18:37:39');

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
-- Table structure for table `catering_addons`
--

DROP TABLE IF EXISTS `catering_addons`;
CREATE TABLE IF NOT EXISTS `catering_addons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `catering_addons`
--

INSERT INTO `catering_addons` (`id`, `name`, `price`, `description`, `status`, `created_at`) VALUES
(1, 'Mini Dessert Bar + Organic Salad Buffet', 9000.00, 'Delicious mini desserts and fresh organic salad bar', 'active', '2025-09-20 14:10:08'),
(2, 'Soup Option', 40.00, 'Per person soup addition', 'active', '2025-09-20 14:10:08'),
(3, 'Appetizer Option', 15.00, 'Per person appetizer addition', 'active', '2025-09-20 14:10:08'),
(4, 'Mini Dessert Bar + Organic Salad Buffet', 9000.00, 'Delicious mini desserts and fresh organic salad bar', 'active', '2025-09-22 00:54:39'),
(5, 'Soup Option', 40.00, 'Per person soup addition', 'active', '2025-09-22 00:54:39'),
(6, 'Appetizer Option', 15.00, 'Per person appetizer addition', 'active', '2025-09-22 00:54:39'),
(7, 'Extra Dessert Selection', 3000.00, 'Additional dessert option', 'active', '2025-09-22 00:54:39'),
(8, 'Extra Juice Selection', 2000.00, 'Additional juice flavor', 'active', '2025-09-22 00:54:39'),
(9, 'Extra Dish', 5000.00, 'Additional main dish selection', 'active', '2025-09-22 00:54:39');

-- --------------------------------------------------------

--
-- Table structure for table `catering_dishes`
--

DROP TABLE IF EXISTS `catering_dishes`;
CREATE TABLE IF NOT EXISTS `catering_dishes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` enum('Pork','Chicken','Fish','Vegetables','Pasta','Dessert','Juice','Soup','Appetizer') NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `description` text,
  `is_default` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `catering_dishes`
--

INSERT INTO `catering_dishes` (`id`, `name`, `category`, `price`, `description`, `is_default`, `status`, `created_at`) VALUES
(1, 'Lechon Kawali', 'Pork', NULL, 'Crispy fried pork belly', 1, 'active', '2025-09-20 14:10:08'),
(2, 'Chicken BBQ', 'Chicken', NULL, 'Grilled chicken skewers', 1, 'active', '2025-09-20 14:10:08'),
(3, 'Grilled Tilapia', 'Fish', NULL, 'Fresh grilled tilapia', 1, 'active', '2025-09-20 14:10:08'),
(4, 'Fresh Lumpia', 'Vegetables', NULL, 'Fresh vegetable spring rolls', 1, 'active', '2025-09-20 14:10:08'),
(5, 'Chicken Carbonara', 'Pasta', NULL, 'Creamy chicken pasta', 1, 'active', '2025-09-20 14:10:08'),
(6, 'Buko Pandan', 'Dessert', NULL, 'Coconut pandan dessert', 1, 'active', '2025-09-20 14:10:08'),
(7, 'Mango Graham', 'Dessert', NULL, 'Mango and graham dessert', 1, 'active', '2025-09-20 14:10:09'),
(8, 'Four Seasons Juice', 'Juice', NULL, 'Refreshing fruit juice', 1, 'active', '2025-09-20 14:10:09'),
(9, 'Pineapple Juice', 'Juice', NULL, 'Sweet pineapple juice', 1, 'active', '2025-09-20 14:10:09'),
(10, 'Patatim', 'Pork', NULL, 'Slow-braised pork leg in savory sauce', 0, 'active', '2025-09-22 00:54:39'),
(11, 'Sweet & Sour Meat Balls', 'Pork', NULL, 'Pork meatballs in tangy sweet and sour sauce', 0, 'active', '2025-09-22 00:54:39'),
(12, 'Menudo', 'Pork', NULL, 'Traditional Filipino pork stew with vegetables', 0, 'active', '2025-09-22 00:54:39'),
(13, 'Pork Tonkatsu', 'Pork', NULL, 'Japanese-style breaded and fried pork cutlet', 0, 'active', '2025-09-22 00:54:39'),
(14, 'Crispy Pork Kare Kare', 'Pork', NULL, 'Crispy pork with peanut sauce and vegetables', 0, 'active', '2025-09-22 00:54:39'),
(15, 'Oriental Pork Special', 'Pork', NULL, 'Asian-inspired pork dish with special sauce', 0, 'active', '2025-09-22 00:54:39'),
(16, 'Stir Fry Beef Broccoli', '', NULL, 'Tender beef with fresh broccoli in savory sauce', 0, 'active', '2025-09-22 00:54:39'),
(17, 'Special Beef Morcon', '', NULL, 'Filipino beef roll with savory filling', 0, 'active', '2025-09-22 00:54:39'),
(18, 'Special Caldereta', '', NULL, 'Spicy beef stew with tomato sauce and vegetables', 0, 'active', '2025-09-22 00:54:39'),
(19, 'Beef in Mushroom Sauce', '', NULL, 'Tender beef slices in creamy mushroom sauce', 0, 'active', '2025-09-22 00:54:39'),
(20, 'Cordon Bleu with Garlic Sauce', 'Chicken', NULL, 'Breaded chicken stuffed with ham and cheese', 0, 'active', '2025-09-22 00:54:39'),
(21, 'Buffalo Wings with Veg Sticks & Onion Dip', 'Chicken', NULL, 'Spicy chicken wings with vegetable sticks and dip', 0, 'active', '2025-09-22 00:54:39'),
(22, 'Classic Fried Chicken with Gravy', 'Chicken', NULL, 'Crispy fried chicken with creamy gravy', 0, 'active', '2025-09-22 00:54:39'),
(23, 'Korean Style Fried Chicken', 'Chicken', NULL, 'Crispy chicken with sweet and spicy Korean sauce', 0, 'active', '2025-09-22 00:54:39'),
(24, 'Chicken Fingers with Honey Mustard Sauce', 'Chicken', NULL, 'Breaded chicken strips with honey mustard dip', 0, 'active', '2025-09-22 00:54:39'),
(25, 'Creamy Chicken Pastel', 'Chicken', NULL, 'Creamy chicken pot pie filling', 0, 'active', '2025-09-22 00:54:39'),
(26, 'Seared Fish Fillet in Lemon Beurre Blanc Sauce', 'Fish', NULL, 'Pan-seared fish with lemon butter sauce', 0, 'active', '2025-09-22 00:54:39'),
(27, 'Breaded Fish Fillet with Creamy Sauce', 'Fish', NULL, 'Crispy breaded fish with creamy dressing', 0, 'active', '2025-09-22 00:54:39'),
(28, 'Fish Tempura with Honey Mustard Cream Sauce', 'Fish', NULL, 'Lightly battered fish with special sauce', 0, 'active', '2025-09-22 00:54:39'),
(29, 'Beer-Battered Fish Fingers with Honey Mustard Sauce', 'Fish', NULL, 'Crispy beer-battered fish with dipping sauce', 0, 'active', '2025-09-22 00:54:39'),
(30, 'Shrimp in Creamy Garlic Parmesan Sauce', 'Fish', NULL, 'Shrimp in rich garlic parmesan cream sauce', 0, 'active', '2025-09-22 00:54:39'),
(31, 'Shrimp Gambas', 'Fish', NULL, 'Spanish-style garlic shrimp', 0, 'active', '2025-09-22 00:54:39'),
(32, 'Relyenong Bangus', 'Fish', NULL, 'Stuffed milkfish, a Filipino delicacy', 0, 'active', '2025-09-22 00:54:39'),
(33, 'Steamed Fish Fillet with Sauce or Mayo', 'Fish', NULL, 'Healthy steamed fish with choice of sauce (+₱50/head)', 0, 'active', '2025-09-22 00:54:39'),
(34, 'Buttered Mixed Vegetables with Quail Eggs', 'Vegetables', NULL, 'Fresh vegetables in butter sauce with quail eggs', 1, 'active', '2025-09-22 00:54:39'),
(35, 'Herbed Garlic Potatoes', 'Vegetables', NULL, 'Roasted potatoes with herbs and garlic', 0, 'active', '2025-09-22 00:54:39'),
(36, 'Lumpiang Sariwa with Special Peanut Sauce', 'Vegetables', NULL, 'Fresh spring rolls with peanut sauce', 0, 'active', '2025-09-22 00:54:39'),
(37, 'Herb-Buttered Glazed Vegetables', 'Vegetables', NULL, 'Seasonal vegetables with herb butter glaze', 0, 'active', '2025-09-22 00:54:39'),
(38, 'French Steak Fries with Sriracha Sauce', 'Vegetables', NULL, 'Thick-cut fries with spicy sriracha sauce', 0, 'active', '2025-09-22 00:54:39'),
(39, 'Oven-Baked Cheesy Vegetables', 'Vegetables', NULL, 'Mixed vegetables baked with cheese topping', 0, 'active', '2025-09-22 00:54:39'),
(40, '100% Beef Spaghetti with Meatballs', 'Pasta', NULL, 'Classic spaghetti with beef meatballs', 1, 'active', '2025-09-22 00:54:39'),
(41, 'Kids Style Double Cheese Spaghetti', 'Pasta', NULL, 'Cheesy spaghetti that kids love', 0, 'active', '2025-09-22 00:54:39'),
(42, 'Fettuccini Alfredo', 'Pasta', NULL, 'Creamy fettuccini with parmesan sauce', 0, 'active', '2025-09-22 00:54:39'),
(43, 'Creamy Bacon Carbonara', 'Pasta', NULL, 'Classic carbonara with bacon and cream sauce', 0, 'active', '2025-09-22 00:54:39'),
(44, 'Baked Beef Lasagna', 'Pasta', NULL, 'Layered pasta with beef and cheese', 0, 'active', '2025-09-22 00:54:39'),
(45, 'Cheesy Baked Mac', 'Pasta', NULL, 'Baked macaroni with three cheeses', 0, 'active', '2025-09-22 00:54:39'),
(46, 'Vegetarian Pasta with Olives & Tomato Herbs', 'Pasta', NULL, 'Healthy pasta with olives and fresh herbs', 0, 'active', '2025-09-22 00:54:39'),
(47, 'Oriental Pasta', 'Pasta', NULL, 'Asian-inspired pasta dish', 0, 'active', '2025-09-22 00:54:39'),
(48, 'Buko Pandan', 'Dessert', NULL, 'Filipino dessert with coconut and pandan', 1, 'active', '2025-09-22 00:54:39'),
(49, 'Buko Salad', 'Dessert', NULL, 'Young coconut salad with fruits and cream', 0, 'active', '2025-09-22 00:54:39'),
(50, 'Fruit Salad', 'Dessert', NULL, 'Mixed fruits in creamy dressing', 0, 'active', '2025-09-22 00:54:39'),
(51, 'Leche Flan', 'Dessert', NULL, 'Caramel custard dessert', 0, 'active', '2025-09-22 00:54:39'),
(52, 'Macapuno', 'Dessert', NULL, 'Sweet coconut sport dessert', 0, 'active', '2025-09-22 00:54:39'),
(53, 'Mango Sago', 'Dessert', NULL, 'Refreshing mango and sago pudding', 0, 'active', '2025-09-22 00:54:39'),
(54, 'Cucumber Lemonade', 'Juice', NULL, 'Refreshing cucumber-infused lemonade', 1, 'active', '2025-09-22 00:54:39'),
(55, 'Blue Lemonade', 'Juice', NULL, 'Vibrant blue lemonade drink', 0, 'active', '2025-09-22 00:54:39'),
(56, 'House Blend Iced Tea', 'Juice', NULL, 'Specialty iced tea blend', 0, 'active', '2025-09-22 00:54:39'),
(57, 'Red Iced Tea', 'Juice', NULL, 'Fruity red iced tea', 0, 'active', '2025-09-22 00:54:39'),
(58, 'Pumpkin Soup with Bacon Bits', 'Soup', NULL, 'Creamy pumpkin soup with crispy bacon', 0, 'active', '2025-09-22 00:54:39'),
(59, 'Cream of Mushroom Soup', 'Soup', NULL, 'Classic creamy mushroom soup', 0, 'active', '2025-09-22 00:54:39'),
(60, 'Crab & Corn Soup', 'Soup', NULL, 'Rich crab and sweet corn soup', 0, 'active', '2025-09-22 00:54:39'),
(61, 'Pica-Pica Crackers', 'Appetizer', NULL, 'Assorted crackers and bites', 0, 'active', '2025-09-22 00:54:39'),
(62, 'Cornicks', 'Appetizer', NULL, 'Crunchy corn snacks', 0, 'active', '2025-09-22 00:54:39'),
(63, 'Nuts', 'Appetizer', NULL, 'Assorted roasted nuts', 0, 'active', '2025-09-22 00:54:39');

-- --------------------------------------------------------

--
-- Table structure for table `catering_orders`
--

DROP TABLE IF EXISTS `catering_orders`;
CREATE TABLE IF NOT EXISTS `catering_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `package_id` int NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `package_id` (`package_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `catering_orders`
--

INSERT INTO `catering_orders` (`id`, `booking_id`, `package_id`, `status`, `created_at`) VALUES
(1, 19, 1, 'pending', '2025-09-22 09:10:57'),
(2, 23, 1, 'pending', '2025-09-24 12:24:19'),
(3, 23, 1, 'pending', '2025-09-24 12:47:35'),
(4, 23, 2, 'pending', '2025-09-24 12:54:20'),
(5, 23, 3, 'pending', '2025-09-24 13:06:51'),
(6, 23, 4, 'pending', '2025-09-24 13:06:54'),
(7, 23, 3, 'pending', '2025-09-24 13:06:56'),
(8, 26, 3, 'pending', '2025-09-24 15:09:20'),
(9, 27, 3, 'pending', '2025-09-24 15:10:16'),
(10, 28, 3, 'pending', '2025-09-24 15:13:11'),
(11, 28, 3, 'confirmed', '2025-09-24 15:37:46'),
(12, 29, 3, 'confirmed', '2025-09-24 18:26:38'),
(13, 30, 3, 'confirmed', '2025-09-24 18:37:46');

-- --------------------------------------------------------

--
-- Table structure for table `catering_order_addons`
--

DROP TABLE IF EXISTS `catering_order_addons`;
CREATE TABLE IF NOT EXISTS `catering_order_addons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catering_order_id` int NOT NULL,
  `addon_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `catering_order_id` (`catering_order_id`),
  KEY `addon_id` (`addon_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catering_order_dishes`
--

DROP TABLE IF EXISTS `catering_order_dishes`;
CREATE TABLE IF NOT EXISTS `catering_order_dishes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catering_order_id` int NOT NULL,
  `dish_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `catering_order_id` (`catering_order_id`),
  KEY `dish_id` (`dish_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `catering_order_dishes`
--

INSERT INTO `catering_order_dishes` (`id`, `catering_order_id`, `dish_id`, `created_at`) VALUES
(1, 13, 35, '2025-09-24 18:53:31'),
(2, 13, 1, '2025-09-24 18:53:31'),
(3, 13, 21, '2025-09-24 18:53:31'),
(4, 13, 40, '2025-09-24 18:53:31'),
(5, 13, 54, '2025-09-24 18:53:31'),
(6, 13, 52, '2025-09-24 18:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `catering_packages`
--

DROP TABLE IF EXISTS `catering_packages`;
CREATE TABLE IF NOT EXISTS `catering_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `dish_count` int NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `min_attendees` int NOT NULL DEFAULT '100',
  `includes` text NOT NULL,
  `archived_at` datetime DEFAULT NULL,
  `reason` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `catering_packages`
--

INSERT INTO `catering_packages` (`id`, `name`, `dish_count`, `base_price`, `min_attendees`, `includes`, `archived_at`, `reason`, `status`, `created_at`) VALUES
(3, '4-Dish Package', 4, 10000.00, 100, 'Rental service, Buffet service, Registration/gift/cake tables, Complete silverware, Steamed rice, Drinks, Desserts', NULL, NULL, 'active', '2025-09-22 00:54:39'),
(4, '5-Dish Package', 5, 15000.00, 100, 'Rental service, Buffet service, Registration/gift/cake tables, Complete silverware, Steamed rice, Drinks, Desserts', NULL, NULL, 'active', '2025-09-22 00:54:39');

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
-- Table structure for table `equipment_categories`
--

DROP TABLE IF EXISTS `equipment_categories`;
CREATE TABLE IF NOT EXISTS `equipment_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `equipment_categories`
--

INSERT INTO `equipment_categories` (`id`, `name`, `description`, `status`) VALUES
(1, 'Sound Equipment', 'Audio and sound systems', 'active'),
(2, 'Visual Equipment', 'Screens, projectors and visual displays', 'active'),
(3, 'Lighting Equipment', 'Stage and event lighting', 'active'),
(4, 'Effects Equipment', 'Special effects equipment', 'active'),
(5, 'Furniture', 'Event furniture and seating', 'active');

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
  `description` text COLLATE utf8mb4_general_ci,
  `category` enum('Sound Equipment','Lighting Equipment','Display Equipment','Catering Equipment','Furniture','Decorations') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category_id` int DEFAULT NULL,
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

INSERT INTO `inventory` (`id`, `item_name`, `description`, `category`, `category_id`, `quantity`, `unit_price`, `supplier`, `reorder_level`, `last_restock_date`, `status`, `created_at`, `updated_at`, `available_quantity`, `rented_quantity`) VALUES
(11, 'Professional Speaker', NULL, 'Sound Equipment', 1, 20, 150.00, 'Sound Pro Inc.', 5, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-09-20 11:22:02', 7, 13),
(12, 'Wireless Microphone', NULL, 'Sound Equipment', 1, 15, 800.00, 'Sound Pro Inc.', 3, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-09-20 11:22:02', 2, 13),
(13, 'LED Wall Panels', NULL, '', 2, 50, 200.00, 'Visual Tech Ltd.', 10, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-09-24 06:54:45', 50, 0),
(14, 'Stage Light', NULL, 'Lighting Equipment', 3, 30, 100.00, 'Light Masters', 6, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-09-20 11:22:03', 20, 10),
(15, 'Fog Machine', NULL, '', NULL, 8, 120.00, 'Effects Plus', 2, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-09-24 15:02:12', 6, 2),
(16, 'Round Table', NULL, 'Furniture', 5, 40, 350.00, 'Event Furnish', 10, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-09-24 15:10:13', 39, 1),
(17, 'Banquet Chair', NULL, 'Furniture', 5, 400, 20.00, 'Event Furnish', 50, NULL, 'In Stock', '2025-02-02 07:52:35', '2025-09-20 11:22:03', 400, 0),
(18, 'Line Array Speaker System', NULL, 'Sound Equipment', 1, 4, 8000.00, 'Sound Pro Inc.', 1, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:02', 0, 0),
(19, 'Mixer 16 Channel', NULL, 'Sound Equipment', 1, 5, 2500.00, 'Audio Tech', 1, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:02', 0, 0),
(20, 'Powered Subwoofer', NULL, 'Sound Equipment', 1, 8, 2000.00, 'Sound Pro Inc.', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:02', 0, 0),
(21, 'Stage Platform 4x8', NULL, 'Display Equipment', NULL, 12, 1500.00, 'Event Essentials', 3, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(22, 'Fog Machine', NULL, 'Lighting Equipment', 3, 6, 1200.00, 'Light Masters', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:03', 0, 0),
(23, 'Tiffany Chair', NULL, 'Furniture', 5, 200, 50.00, 'Event Essentials', 20, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:03', 0, 0),
(24, 'Cocktail Table', NULL, 'Furniture', 5, 20, 300.00, 'Event Essentials', 5, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:03', 0, 0),
(25, 'Coffee Urn', NULL, 'Catering Equipment', NULL, 10, 1000.00, 'Kitchen Pro', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(26, 'Stage Light Truss 10ft', NULL, 'Lighting Equipment', 3, 10, 1800.00, 'Light Masters', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:03', 0, 0),
(27, 'LED Strip Light 5m', NULL, 'Lighting Equipment', 3, 20, 500.00, 'Light Masters', 5, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-09-20 11:22:03', 0, 0),
(28, 'Portable Aircon', NULL, 'Display Equipment', NULL, 8, 3500.00, 'Event Essentials', 2, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(29, 'Photo Backdrop Stand', NULL, 'Display Equipment', NULL, 5, 800.00, 'Display Solutions', 1, NULL, 'In Stock', '2025-02-04 11:15:07', '2025-02-04 11:15:07', 0, 0),
(30, 'Professional Speaker 1000W', NULL, 'Sound Equipment', 1, 10, 200.00, 'Sound Pro Inc.', 2, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-09-20 11:22:02', 0, 0),
(31, 'Wireless Microphone', NULL, 'Sound Equipment', 1, 15, 50.00, 'Audio Tech', 3, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-09-20 11:22:02', 0, 0),
(32, 'LED Wall Panel 4x4', NULL, 'Display Equipment', NULL, 8, 500.00, 'Display Solutions', 2, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(33, 'LED Par Light', NULL, 'Lighting Equipment', 3, 20, 75.00, 'Light Masters', 5, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-09-20 11:22:03', 0, 0),
(34, 'Moving Head Light', NULL, 'Lighting Equipment', 3, 12, 150.00, 'Light Masters', 3, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-09-20 11:22:03', 0, 0),
(35, 'Round Table', NULL, 'Furniture', 5, 30, 40.00, 'Event Essentials', 5, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-09-20 11:22:03', 0, 0),
(36, 'Chafing Dish', NULL, 'Catering Equipment', NULL, 25, 45.00, 'Kitchen Pro', 5, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0),
(37, 'Table Cloth', NULL, 'Decorations', NULL, 50, 15.00, 'Event Essentials', 10, NULL, 'In Stock', '2025-02-04 11:16:32', '2025-02-04 11:16:32', 0, 0);

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
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
CREATE TABLE IF NOT EXISTS `packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `base_attendees` int NOT NULL,
  `min_attendees` int NOT NULL DEFAULT '100',
  `max_attendees` int NOT NULL DEFAULT '150',
  `excess_price` decimal(10,2) NOT NULL,
  `duration` int NOT NULL,
  `includes` text NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `base_price`, `base_attendees`, `min_attendees`, `max_attendees`, `excess_price`, `duration`, `includes`, `status`, `created_at`) VALUES
(1, 'Wedding Package', 50000.00, 100, 100, 150, 800.00, 8, 'Venue rental for 8 hours, Event Coordination & Setup, Lights (2x), Speakers (4x), Tables & Chairs with linens, Backdrop & stage decor, Basic catering for 100 pax', 'active', '2025-09-20 14:10:08'),
(2, 'Corporate Event', 40000.00, 100, 100, 150, 700.00, 6, 'Venue rental for 6 hours, Professional stage & backdrop, Projector & screen, Lights (4x), Speakers (4x), Tables & chairs, Basic catering for 100 pax', 'active', '2025-09-20 14:10:08'),
(3, 'Birthday Party', 25000.00, 100, 100, 150, 600.00, 6, 'Venue rental for 6 hours, Themed backdrop & balloons, Lights (2x), Speakers (2x), Tables & chairs with covers, Basic catering for 100 pax', 'active', '2025-09-20 14:10:08');

-- --------------------------------------------------------

--
-- Table structure for table `payment_status_log`
--

DROP TABLE IF EXISTS `payment_status_log`;
CREATE TABLE IF NOT EXISTS `payment_status_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `booking_id` int NOT NULL,
  `old_payment_status` enum('unpaid','partial','paid') NOT NULL,
  `new_payment_status` enum('unpaid','partial','paid') NOT NULL,
  `changed_by` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_status_log`
--

INSERT INTO `payment_status_log` (`id`, `booking_id`, `old_payment_status`, `new_payment_status`, `changed_by`, `created_at`) VALUES
(1, 13, 'unpaid', 'partial', 'Customer', '2025-09-19 06:36:23'),
(2, 14, 'unpaid', 'partial', 'Customer', '2025-09-19 08:12:02'),
(3, 29, 'unpaid', 'paid', 'Customer', '2025-09-24 18:29:27');

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sales_id`, `btuser_id`, `booking_id`, `GcashReferenceNo`, `TotalAmount`, `AmountPaid`, `Status`, `DateCreated`, `DateUpdate`, `userUpdated_Id`) VALUES
(1, 10, 12, '195250', 25000, 20000, 1, '2025-09-19 14:27:13', NULL, NULL),
(2, 10, 13, '195250', 25000, 20000, 1, '2025-09-19 14:36:23', NULL, NULL),
(3, 10, 14, '214124313', 22000, 4400, 1, '2025-09-19 16:12:02', NULL, NULL),
(4, 5, 29, '09123', 63500, 63500, 1, '2025-09-25 02:29:27', NULL, NULL);

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
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(12, 'Band Equipment (On venue setup only)', 3500);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
CREATE TABLE IF NOT EXISTS `services` (
  `services_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text,
  `category` varchar(50) DEFAULT 'General',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`services_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
