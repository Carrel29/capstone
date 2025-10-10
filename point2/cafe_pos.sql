-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 02, 2025 at 02:22 PM
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
-- Database: `cafe_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `archived` tinyint(1) DEFAULT '0',
  `classification` enum('food','drinks') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `archived`, `classification`) VALUES
(1, 'coffee', 0, 'drinks'),
(2, 'breakfast', 0, 'food'),
(3, 'addons', 0, 'food'),
(4, 'milktea', 1, NULL),
(5, 'Non-Coffee Drinks', 1, NULL),
(6, 'Non-Coffee Drinks', 1, NULL),
(7, 'Non-Coffee ', 0, 'drinks');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `type` enum('cash','online') NOT NULL,
  `payment_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `archived` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `code`, `type`, `payment_image`, `is_active`, `created_at`, `archived`, `updated_at`) VALUES
(1, 'Cash', 'CASH', 'cash', NULL, 1, '2025-04-11 15:24:30', 0, '2025-04-12 05:35:22'),
(2, 'GCash', 'GCASH', 'online', 'images/2025/04/67fb540b33aaf.png', 1, '2025-04-11 15:24:30', 0, '2025-04-13 06:04:59'),
(3, 'PayMaya', 'MAYA', 'online', 'images/2025/04/67fb5414c99a6.png', 1, '2025-04-12 01:38:11', 0, '2025-04-16 12:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `price_medium` decimal(10,2) DEFAULT NULL,
  `price_large` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `archived` tinyint(1) DEFAULT '0',
  `classification` enum('food','drinks') DEFAULT 'food',
  `price_hot` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `code`, `price_medium`, `price_large`, `price`, `category_id`, `archived`, `classification`, `price_hot`) VALUES
(1, 'Americano Coffee', 'AC', 70.00, 80.00, NULL, 1, 0, 'drinks', 60.00),
(2, 'Vanilla Latte', 'VL', 80.00, 90.00, NULL, 1, 0, 'drinks', 70.00),
(3, 'Café Latte', 'CL', 80.00, 90.00, NULL, 1, 0, 'drinks', 70.00),
(4, 'Caramel Macchiato', 'CM', 90.00, 100.00, NULL, 1, 0, 'drinks', 80.00),
(5, 'Cappuccino', 'CP', 80.00, 90.00, NULL, 1, 0, 'drinks', 70.00),
(6, 'Matcha Espresso', 'ME', 100.00, 110.00, NULL, 1, 0, 'drinks', 90.00),
(7, 'Classic Breakfast', 'CB', NULL, NULL, 120.00, 2, 0, 'food', NULL),
(8, 'Pancakes with Syrup', 'PS', NULL, NULL, 150.00, 2, 0, 'food', NULL),
(9, 'Eggs Benedict', 'EB', NULL, NULL, 130.00, 2, 0, 'food', NULL),
(10, 'Avocado Toast', 'AT', NULL, NULL, 110.00, 2, 0, 'food', NULL),
(11, 'Extra Shot of Espresso', 'ES', NULL, NULL, 20.00, 3, 0, 'food', NULL),
(12, 'Whipped Cream', 'WC', NULL, NULL, 10.00, 3, 0, 'food', NULL),
(13, 'Caramel Drizzle', 'CD', NULL, NULL, 15.00, 3, 0, 'food', NULL),
(14, 'Chocolate Syrup', 'CS', NULL, NULL, 15.00, 3, 0, 'food', NULL),
(15, 'Mocha Latte', 'ML', 80.00, 100.00, NULL, 1, 0, 'drinks', 70.00),
(16, 'Wintermelon', 'WM', 39.00, 49.00, NULL, 4, 1, 'drinks', 29.00),
(17, 'Chocolate Milk', 'CHM', 110.00, 130.00, NULL, 5, 1, 'food', NULL),
(18, 'Chocolate Milk', 'CHM', 110.00, 130.00, NULL, 5, 1, 'food', NULL),
(19, 'Chocolate Milk', 'CHM', 100.00, 120.00, NULL, 7, 0, 'drinks', NULL),
(20, 'Creamy Latte', 'CRL', 70.00, 80.00, NULL, 1, 0, 'drinks', 60.00),
(21, 'Milk', 'MK', NULL, NULL, 20.00, 3, 0, 'food', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_date` datetime NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_code` varchar(10) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `cash_received` decimal(10,2) DEFAULT NULL,
  `cash_change` decimal(10,2) DEFAULT NULL,
  `reference_number` varchar(4) DEFAULT NULL,
  `cart_items` text NOT NULL,
  `username` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_date`, `payment_method`, `payment_code`, `total_amount`, `cash_received`, `cash_change`, `reference_number`, `cart_items`, `username`, `created_at`) VALUES
(1, '2025-04-12 17:58:21', 'online', NULL, 90.00, NULL, NULL, '1234', '[{\"name\":\"Caf\\u00e9 Latte\",\"price\":\"90\",\"quantity\":\"1\"}]', 'Admin', '2025-04-12 09:58:21'),
(2, '2025-04-12 22:00:38', 'online', NULL, 80.00, NULL, NULL, '0629', '[{\"name\":\"Caf\\u00e9 Latte\",\"price\":\"80\",\"quantity\":\"1\"}]', 'Admin', '2025-04-12 14:00:38'),
(3, '2025-04-13 03:58:18', 'cash', 'CASH', 39.00, 50.00, 11.00, NULL, '[{\"name\":\"Wintermelon\",\"price\":\"39\",\"quantity\":\"1\"}]', 'Admin', '2025-04-12 19:58:18'),
(4, '2025-04-13 18:50:35', 'cash', 'CASH', 180.00, 200.00, 20.00, NULL, '[{\"name\":\"Caf\\u00e9 Latte\",\"price\":\"80\",\"quantity\":\"1\"},{\"name\":\"Caramel Macchiato\",\"price\":\"100\",\"quantity\":\"1\"}]', 'Admin', '2025-04-13 10:50:35'),
(5, '2025-04-13 20:12:24', 'cash', 'CASH', 90.00, 100.00, 10.00, NULL, '[{\"name\":\"Caramel Macchiato\",\"price\":\"90\",\"quantity\":\"1\"}]', 'Admin', '2025-04-13 12:12:24'),
(6, '2025-04-13 20:13:35', 'cash', 'CASH', 180.00, 200.00, 20.00, NULL, '[{\"name\":\"Cappuccino\",\"price\":\"90\",\"quantity\":\"1\"},{\"name\":\"Caramel Macchiato\",\"price\":\"90\",\"quantity\":\"1\"}]', 'Admin', '2025-04-13 12:13:35'),
(7, '2025-04-14 17:36:56', 'cash', 'CASH', 310.00, 400.00, 90.00, NULL, '[{\"name\":\"Cappuccino\",\"price\":\"80\",\"quantity\":\"1\"},{\"name\":\"Caf\\u00e9 Latte\",\"price\":\"80\",\"quantity\":\"1\"},{\"name\":\"Pancakes with Syrup\",\"price\":\"150\",\"quantity\":\"1\"}]', 'Admin', '2025-04-14 09:36:56'),
(8, '2025-04-14 17:39:48', 'online', NULL, 170.00, NULL, NULL, '0629', '[{\"name\":\"Cappuccino\",\"price\":\"90\",\"quantity\":\"1\"},{\"name\":\"Vanilla Latte\",\"price\":\"80\",\"quantity\":\"1\"}]', 'Admin', '2025-04-14 09:39:48'),
(9, '2025-04-16 20:07:40', 'cash', 'CASH', 190.00, 200.00, 10.00, NULL, '[{\"name\":\"Caf\\u00e9 Latte + Extra Shot of Espresso\",\"price\":\"110\",\"quantity\":\"1\"},{\"name\":\"Mocha Latte\",\"price\":\"80\",\"quantity\":\"1\"}]', 'Cashier', '2025-04-16 12:07:40'),
(10, '2025-04-17 17:02:37', 'cash', 'CASH', 200.00, 300.00, 100.00, NULL, '[{\"name\":\"Cappuccino\",\"price\":\"90\",\"quantity\":\"1\"},{\"name\":\"Avocado Toast\",\"price\":\"110\",\"quantity\":\"1\"}]', 'Admin', '2025-04-17 09:02:37'),
(11, '2025-04-17 19:10:21', 'cash', 'CASH', 90.00, 123.00, 33.00, NULL, '[{\"name\":\"Cappuccino\",\"price\":\"90\",\"quantity\":\"1\"}]', 'Admin', '2025-04-17 11:10:21'),
(12, '2025-04-18 12:14:16', 'online', NULL, 100.00, NULL, NULL, '8723', '[{\"name\":\"Caramel Macchiato\",\"price\":\"100\",\"quantity\":\"1\"}]', 'Admin', '2025-04-18 04:14:16'),
(13, '2025-04-18 12:14:38', 'online', NULL, 90.00, NULL, NULL, '5321', '[{\"name\":\"Caramel Macchiato\",\"price\":\"90\",\"quantity\":\"1\"}]', 'Admin', '2025-04-18 04:14:38'),
(14, '2025-04-18 14:58:37', 'cash', NULL, 95.00, 100.00, 5.00, NULL, '[{\"name\":\"Caf\\u00e9 Latte (medium, 50% sugar) with Chocolate Syrup\",\"price\":\"95\",\"quantity\":\"1\"}]', 'Admin', '2025-04-18 06:58:37'),
(15, '2025-04-18 18:45:13', 'GCASH', NULL, 190.00, NULL, NULL, '3424', '[{\"name\":\"Caf\\u00e9 Latte (hot, 50% sugar) with Whipped Cream, Caramel Drizzle\",\"price\":\"95\",\"quantity\":\"2\"}]', 'Admin', '2025-04-18 10:45:13'),
(16, '2025-04-28 21:29:49', 'cash', NULL, 120.00, 300.00, 180.00, NULL, '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:29:49'),
(17, '2025-04-28 21:30:00', 'cash', NULL, 120.00, 300.00, 180.00, NULL, '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:30:00'),
(18, '2025-04-28 21:30:42', 'cash', NULL, 120.00, 300.00, 180.00, NULL, '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:30:42'),
(19, '2025-04-28 21:41:05', 'CASH', 'CASH', 120.00, 0.00, 0.00, '', '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:41:05'),
(20, '2025-04-28 21:41:48', 'CASH', 'CASH', 120.00, 0.00, 0.00, '', '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:41:48'),
(21, '2025-04-28 21:41:56', 'GCASH', 'GCASH', 120.00, 0.00, 0.00, '1231', '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:41:56'),
(22, '2025-04-28 21:47:29', 'GCASH', 'GCASH', 120.00, 0.00, 0.00, '5321', '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:47:29'),
(23, '2025-04-28 21:49:42', 'CASH', 'CASH', 130.00, 0.00, 0.00, '', '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:49:42'),
(24, '2025-04-28 21:55:48', 'cash', 'cash', 130.00, 150.00, 20.00, NULL, '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:55:48'),
(25, '2025-04-28 21:58:17', 'cash', 'cash', 120.00, 150.00, 30.00, NULL, '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 13:58:17'),
(26, '2025-04-28 22:04:26', 'cash', 'cash', 130.00, 150.00, 20.00, NULL, '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:04:26'),
(27, '2025-04-28 22:12:23', 'online', 'online', 130.00, 0.00, 0.00, '5321', '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:12:23'),
(28, '2025-04-28 22:12:49', 'online', 'online', 130.00, 0.00, 0.00, '2412', '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:12:49'),
(29, '2025-04-28 22:18:15', 'cash', 'cash', 120.00, 200.00, 80.00, NULL, '[{\"name\":\"Classic Breakfast\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:18:15'),
(30, '2025-04-28 22:21:47', 'cash', 'cash', 110.00, 150.00, 40.00, NULL, '[{\"name\":\"Avocado Toast\",\"price\":\"110\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:21:47'),
(31, '2025-04-28 22:36:10', 'cash', 'cash', 110.00, 200.00, 90.00, NULL, '[{\"name\":\"Avocado Toast\",\"price\":\"110\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:36:10'),
(32, '2025-04-28 22:37:03', 'cash', 'cash', 130.00, 200.00, 70.00, NULL, '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:37:03'),
(33, '2025-04-28 22:39:54', 'cash', 'cash', 130.00, 1000.00, 870.00, NULL, '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-04-28 14:39:54'),
(34, '2025-05-02 12:23:23', 'cash', 'cash', 140.00, 150.00, 10.00, NULL, '[{\"name\":\"Caramel Macchiato (medium, 35% sugar) with Extra Shot of Espresso, Caramel Drizzle, Chocolate Syrup\",\"price\":\"140\",\"quantity\":\"1\"}]', 'Admin', '2025-05-02 04:23:23'),
(35, '2025-05-09 10:22:25', 'cash', 'cash', 100.00, 120.00, 20.00, NULL, '[{\"name\":\"Caf\\u00e9 Latte (hot, 25% sugar) with Extra Shot of Espresso, Whipped Cream\",\"price\":\"100\",\"quantity\":\"1\"}]', 'Admin', '2025-05-09 02:22:25'),
(36, '2025-05-09 17:00:00', 'cash', 'cash', 90.00, 100.00, 10.00, NULL, '[{\"name\":\"Americano Coffee (medium, 25% sugar) with Extra Shot of Espresso\",\"price\":\"90\",\"quantity\":\"1\"}]', 'Admin', '2025-05-09 09:00:00'),
(37, '2025-05-13 11:32:55', 'online', 'online', 15.00, 0.00, 0.00, '4009', '[{\"name\":\"Caramel Drizzle\",\"price\":\"15\",\"quantity\":\"1\"}]', 'Admin', '2025-05-13 03:32:55'),
(38, '2025-09-30 21:16:32', 'online', 'online', 100.00, 0.00, 0.00, '1231', '[{\"name\":\"Caf\\u00e9 Latte (medium, 50% sugar) with Milk\",\"price\":\"100\",\"quantity\":\"1\"}]', 'Admin', '2025-09-30 13:16:32'),
(39, '2025-09-30 21:18:57', 'online', 'online', 130.00, 0.00, 0.00, '8723', '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-09-30 13:18:57'),
(40, '2025-09-30 21:39:06', 'MAYA', 'MAYA', 150.00, 0.00, 0.00, '1456', '[{\"name\":\"Pancakes with Syrup\",\"price\":\"150\",\"quantity\":\"1\"}]', 'Admin', '2025-09-30 13:39:06'),
(41, '2025-09-30 21:39:48', 'CASH', 'CASH', 105.00, 0.00, 0.00, '', '[{\"name\":\"Mocha Latte (medium, 50% sugar) with Whipped Cream, Chocolate Syrup\",\"price\":\"105\",\"quantity\":\"1\"}]', 'Admin', '2025-09-30 13:39:48'),
(42, '2025-09-30 21:40:21', 'GCASH', 'GCASH', 130.00, 0.00, 0.00, '4321', '[{\"name\":\"Eggs Benedict\",\"price\":\"130\",\"quantity\":\"1\"}]', 'Admin', '2025-09-30 13:40:21'),
(43, '2025-10-01 11:07:07', 'CASH', NULL, 150.00, 200.00, 50.00, NULL, '[{\"name\":\"Pancakes with Syrup\",\"price\":\"150\",\"quantity\":\"1\"}]', 'Admin', '2025-10-01 03:07:07'),
(44, '2025-10-01 11:11:17', '', 'GCASH', 110.00, 0.00, 0.00, '4521', '[{\"name\":\"Cappuccino (medium, 50% sugar) with Extra Shot of Espresso, Whipped Cream\",\"price\":\"110\",\"quantity\":\"1\"}]', 'Admin', '2025-10-01 03:11:17'),
(45, '2025-10-01 11:12:37', '', 'CASH', 100.00, 120.00, 20.00, NULL, '[{\"name\":\"Cappuccino (medium, 50% sugar) with Extra Shot of Espresso\",\"price\":\"100\",\"quantity\":\"1\"}]', 'Admin', '2025-10-01 03:12:37'),
(46, '2025-10-01 11:13:08', '', 'MAYA', 120.00, 0.00, 0.00, '5321', '[{\"name\":\"Caramel Macchiato (large, 75% sugar) with Extra Shot of Espresso\",\"price\":\"120\",\"quantity\":\"1\"}]', 'Admin', '2025-10-01 03:13:08'),
(47, '2025-10-01 11:25:37', '', 'CASH', 150.00, 200.00, 50.00, NULL, '[{\"name\":\"Pancakes with Syrup\",\"price\":\"150\",\"quantity\":\"1\"}]', 'Admin', '2025-10-01 03:25:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `is_admin`, `created_at`) VALUES
(2, 'Carrel29', '$2y$10$Msh1YcsImn5dT3k/Vd2JDe7wFpEFSlpKQWKKOkdVeX/M/hRJIM31m', 1, '2025-04-10 06:00:52'),
(3, 'Admin', '$2y$10$aSQUZSs05ymzbbjQtV/1Peg1X3BqQtxnUVO/F1NuLVfbzDHruqHtC', 1, '2025-04-10 06:00:52'),
(4, 'Cashier', '$2y$10$Zl5RYCPXMEBbDka3ItlFneMU4QmnbrBObwTMIP8qBm6PmrqyIgxjG', 0, '2025-04-10 06:00:52');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
