-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2026 at 08:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sakamoto`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_on_table`
--

CREATE TABLE `add_on_table` (
  `add_on_key` varchar(100) NOT NULL,
  `add_on_name` varchar(100) NOT NULL,
  `add_on_qty` int(100) NOT NULL,
  `add_on_price` float(7,2) NOT NULL,
  `add_on_status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_on_table`
--

INSERT INTO `add_on_table` (`add_on_key`, `add_on_name`, `add_on_qty`, `add_on_price`, `add_on_status`) VALUES
('Cinnamon', 'Cinnamon', 1000, 20.00, 'Available'),
('Honey', 'Honey', 1000, 70.00, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `cancel_history_table`
--

CREATE TABLE `cancel_history_table` (
  `order_username` varchar(100) NOT NULL,
  `order_product_name` varchar(100) NOT NULL,
  `order_flavor` varchar(100) NOT NULL,
  `order_cup_size` varchar(100) NOT NULL,
  `order_add_on` varchar(100) NOT NULL,
  `order_quantity` int(100) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_total_price` float(7,2) NOT NULL,
  `order_status` varchar(100) NOT NULL,
  `is_cancelled` varchar(100) NOT NULL,
  `cancellation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `mode_payment` varchar(100) NOT NULL,
  `list_number` int(11) NOT NULL,
  `order_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_table`
--

CREATE TABLE `cart_table` (
  `cart_id` int(100) NOT NULL,
  `acc_username` varchar(100) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `cup_size` varchar(100) NOT NULL,
  `flavor_name` varchar(100) NOT NULL,
  `add_ons` varchar(100) NOT NULL,
  `cart_quantity` int(100) NOT NULL,
  `prod_total_price` float(7,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category_table`
--

CREATE TABLE `category_table` (
  `category_code` varchar(100) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category_table`
--

INSERT INTO `category_table` (`category_code`, `category_name`, `category_status`) VALUES
('Coffee', 'Coffee', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `cups_table`
--

CREATE TABLE `cups_table` (
  `cup_key` varchar(100) NOT NULL,
  `cup_type` varchar(100) NOT NULL,
  `cup_quantity` int(100) NOT NULL,
  `cup_plus_price` float(7,2) NOT NULL,
  `cup_status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cups_table`
--

INSERT INTO `cups_table` (`cup_key`, `cup_type`, `cup_quantity`, `cup_plus_price`, `cup_status`) VALUES
('L', 'Large', 1000, 70.00, 'Available'),
('M', 'Medium', 1000, 50.00, 'Available'),
('S', 'Small', 919, 20.00, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `flavor_table`
--

CREATE TABLE `flavor_table` (
  `flavor_key` varchar(100) NOT NULL,
  `flavor_name` varchar(100) NOT NULL,
  `flavor_qty` int(100) NOT NULL,
  `flavor_price` float(7,2) NOT NULL,
  `flavor_status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flavor_table`
--

INSERT INTO `flavor_table` (`flavor_key`, `flavor_name`, `flavor_qty`, `flavor_price`, `flavor_status`) VALUES
('Chocolate', 'Chocolate', 1000, 40.00, 'Available'),
('Strawberry', 'Strawberry', 1000, 50.00, 'Available'),
('Vanilla', 'Vanilla', 1000, 50.00, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `history_table`
--

CREATE TABLE `history_table` (
  `order_username` varchar(100) NOT NULL,
  `order_product_name` varchar(100) NOT NULL,
  `order_flavor` varchar(100) NOT NULL,
  `order_cup_size` varchar(100) NOT NULL,
  `order_add_on` varchar(100) NOT NULL,
  `order_quantity` int(100) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_total_price` float(7,2) NOT NULL,
  `order_status` varchar(100) NOT NULL,
  `is_cancelled` varchar(100) NOT NULL,
  `cancellation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `mode_payment` varchar(100) NOT NULL,
  `list_number` int(11) NOT NULL,
  `order_type` varchar(100) NOT NULL,
  `queue_number` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `history_table`
--

INSERT INTO `history_table` (`order_username`, `order_product_name`, `order_flavor`, `order_cup_size`, `order_add_on`, `order_quantity`, `order_date`, `order_total_price`, `order_status`, `is_cancelled`, `cancellation_date`, `mode_payment`, `list_number`, `order_type`, `queue_number`) VALUES
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 04:11:03', 120.00, 'APPROVED', 'ORDER ', '2026-08-27 04:11:03', 'cash', 1, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 04:17:32', 120.00, 'APPROVED', 'ORDER', '2026-08-27 04:17:32', 'cash', 2, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 04:27:03', 120.00, 'APPROVED', 'ORDER', '2026-08-27 04:27:03', 'credit_card', 3, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:15:59', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:15:59', 'cash', 4, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:17:37', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:17:37', 'credit_card', 5, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:19:10', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:19:10', 'cash', 6, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:24:51', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:24:51', 'cash', 7, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 2, '2026-08-27 06:32:22', 240.00, 'APPROVED', 'ORDER', '2026-08-27 06:32:22', 'credit_card', 8, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:35:53', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:35:53', 'cash', 9, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:37:29', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:37:29', 'credit_card', 10, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:42:11', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:42:11', 'cash', 11, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 06:43:23', 120.00, 'APPROVED', 'ORDER', '2026-08-27 06:43:23', 'credit_card', 12, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 13:35:38', 120.00, 'APPROVED', 'ORDER', '2026-08-27 13:35:38', 'cash', 13, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 14:02:56', 120.00, 'APPROVED', 'CANCEL', '2026-08-27 08:04:03', '', 14, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 14:17:29', 120.00, 'APPROVED', 'ORDER', '2026-08-27 14:17:29', '', 15, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 22:59:19', 120.00, 'APPROVED', 'ORDER', '2026-08-27 22:59:19', 'Cash', 16, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 23:16:03', 120.00, 'APPROVED', 'ORDER', '2026-08-27 23:16:03', 'Credit Card', 17, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 23:38:10', 120.00, 'APPROVED', 'ORDER', '2026-08-27 23:38:10', 'Credit Card', 18, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-27 23:54:00', 120.00, 'APPROVED', 'ORDER', '2026-08-27 23:54:00', 'Credit Card', 19, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 00:23:28', 120.00, 'APPROVED', 'ORDER', '2026-08-28 00:23:28', 'Debit Card', 20, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 00:45:00', 120.00, 'APPROVED', 'ORDER', '2026-08-28 00:45:00', 'Cash', 21, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 01:09:27', 120.00, 'APPROVED', 'ORDER', '2026-08-28 01:09:27', 'Credit Card', 22, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 01:21:51', 120.00, 'APPROVED', 'ORDER', '2026-08-28 01:21:51', '', 23, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 04:53:56', 120.00, 'APPROVED', 'ORDER', '2026-08-28 04:53:56', 'Credit Card', 24, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 05:53:36', 120.00, 'APPROVED', 'ORDER', '2026-08-28 05:53:36', 'Cash', 25, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 08:17:49', 120.00, 'APPROVED', 'ORDER', '2026-08-28 08:17:49', 'Cash', 26, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 08:29:32', 120.00, 'APPROVED', 'ORDER', '2026-08-28 08:29:32', 'Cash', 27, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 09:31:18', 120.00, 'APPROVED', 'ORDER', '2026-08-28 09:31:18', 'Cash', 28, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 10:48:51', 120.00, 'APPROVED', 'ORDER', '2026-08-28 10:48:51', 'Cash', 29, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 10:55:33', 120.00, 'APPROVED', 'ORDER', '2026-08-28 10:55:33', 'Credit Card', 30, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 11:07:14', 120.00, 'APPROVED', 'ORDER', '2026-08-28 11:07:14', 'Cash', 31, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 11:08:06', 120.00, 'APPROVED', 'ORDER', '2026-08-28 11:08:06', 'Credit Card', 32, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 12:31:28', 120.00, 'APPROVED', 'ORDER', '2026-08-28 12:31:28', 'Cash', 33, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 13:01:01', 120.00, 'APPROVED', 'ORDER', '2026-08-28 13:01:01', 'Credit Card', 34, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 13:11:08', 120.00, 'APPROVED', 'ORDER', '2026-08-28 13:11:08', 'Cash', 35, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 13:12:15', 120.00, 'APPROVED', 'CANCEL', '2026-08-28 07:12:29', '', 36, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 13:42:02', 120.00, 'APPROVED', 'ORDER', '2026-08-28 13:42:02', 'Cash', 37, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-28 14:03:21', 120.00, 'APPROVED', 'ORDER', '2026-08-28 14:03:21', 'Credit Card', 38, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 00:19:07', 120.00, 'APPROVED', 'ORDER', '2026-08-29 00:19:07', 'Cash', 39, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 04:36:39', 120.00, 'APPROVED', 'ORDER', '2026-08-29 04:36:39', 'Cash', 40, 'Take Out', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 05:03:18', 120.00, 'APPROVED', 'ORDER', '2026-08-29 05:03:18', 'Credit Card', 41, 'Dine In', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 05:04:56', 120.00, 'APPROVED', 'ORDER', '2026-08-29 05:04:56', 'Cash', 42, 'Dine In', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 05:24:50', 120.00, 'APPROVED', 'ORDER', '2026-08-29 05:24:50', 'Cash', 43, 'Dine In', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 05:37:48', 120.00, 'APPROVED', 'ORDER', '2026-08-29 05:37:48', 'Cash', 44, 'Dine In', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 05:54:02', 120.00, 'PENDING', 'CANCEL', '2026-08-29 05:54:02', '', 45, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 05:54:41', 120.00, 'APPROVED', 'CANCEL', '2026-08-28 23:54:56', '', 46, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 06:02:05', 120.00, 'APPROVED', 'ORDER', '2026-08-29 06:02:05', 'Cash', 47, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 06:12:53', 120.00, 'APPROVED', 'CANCEL', '2026-08-29 00:13:11', '', 48, '', ''),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 06:22:08', 120.00, 'APPROVED', 'ORDER', '2026-08-29 06:22:08', 'Cash', 49, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 06:30:55', 120.00, 'APPROVED', 'ORDER', '2026-08-29 06:30:55', 'Cash', 50, 'Take Out', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 06:34:25', 120.00, 'APPROVED', 'ORDER', '2026-08-29 06:34:25', 'Cash', 51, 'Take Out', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 06:46:59', 120.00, 'APPROVED', 'ORDER', '2026-08-29 06:46:59', 'Credit Card', 52, 'Take Out', '1'),
('KarlKingKong69', 'Latte', 'Strawberry', 'Small', '', 1, '2026-08-29 06:48:14', 170.00, 'APPROVED', 'ORDER', '2026-08-29 06:48:14', 'Cash', 53, 'Take Out', '1'),
('KarlKingKong69', 'Latte', 'Strawberry', 'Small', '', 1, '2026-08-29 06:59:05', 170.00, 'APPROVED', 'ORDER', '2026-08-29 06:59:05', 'Cash', 54, 'Take Out', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 07:56:51', 120.00, 'APPROVED', 'ORDER', '2026-08-29 07:56:51', 'Cash', 55, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 08:00:15', 120.00, 'APPROVED', 'ORDER', '2026-08-29 08:00:15', 'Cash', 56, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 08:04:55', 120.00, 'APPROVED', 'ORDER', '2026-08-29 08:04:55', 'Cash', 57, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 11:56:04', 120.00, 'APPROVED', 'ORDER', '2026-08-29 11:56:04', 'Cash', 58, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 12:03:29', 120.00, 'APPROVED', 'ORDER', '2026-08-29 12:03:29', 'Cash', 59, 'Dine In', '1'),
('KarlKingKong69', 'Latte', 'Strawberry', 'Small', '', 1, '2026-08-29 12:09:49', 170.00, 'APPROVED', 'ORDER', '2026-08-29 12:09:49', 'Cash', 60, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 12:15:26', 120.00, 'APPROVED', 'ORDER', '2026-08-29 12:15:26', 'Credit Card', 61, 'Take Out', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 12:18:32', 120.00, 'APPROVED', 'ORDER', '2026-08-29 12:18:32', 'Cash', 62, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 12:22:17', 120.00, 'APPROVED', 'ORDER', '2026-08-29 12:22:17', 'Cash', 63, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 12:24:14', 120.00, 'APPROVED', 'ORDER', '2026-08-29 12:24:14', 'Cash', 64, 'Take Out', '1'),
('KarlKingKong69', 'Latte', 'Strawberry', 'Small', '', 1, '2026-08-29 12:25:02', 170.00, 'APPROVED', 'ORDER', '2026-08-29 12:25:02', 'Cash', 65, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 12:38:42', 120.00, 'APPROVED', 'ORDER', '2026-08-29 12:38:42', 'Cash', 66, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 12:43:05', 120.00, 'APPROVED', 'ORDER', '2026-08-29 12:43:05', 'Cash', 67, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 13:01:28', 120.00, 'APPROVED', 'ORDER', '2026-08-29 13:01:28', 'Cash', 68, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 13:03:54', 120.00, 'APPROVED', 'ORDER', '2026-08-29 13:03:54', 'Cash', 69, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 13:36:13', 120.00, 'APPROVED', 'ORDER', '2026-08-29 13:36:13', 'Cash', 70, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:01:08', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:01:08', 'Cash', 71, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:05:48', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:05:48', 'Cash', 72, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:08:12', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:08:12', 'Cash', 73, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:09:51', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:09:51', 'Cash', 74, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:13:07', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:13:07', 'Cash', 75, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:17:05', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:17:05', 'Cash', 76, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:19:36', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:19:36', 'Cash', 77, 'Take Out', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:21:44', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:21:44', 'Cash', 78, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-29 14:22:57', 120.00, 'APPROVED', 'ORDER', '2026-08-29 14:22:57', 'Cash', 79, 'Dine In', '1'),
('KarlKingKong69', 'Latte', '', 'Small', '', 1, '2026-08-31 07:39:54', 120.00, 'APPROVED', 'ORDER', '2026-08-31 07:39:54', 'Cash', 80, 'Dine In', '1');

-- --------------------------------------------------------

--
-- Table structure for table `home_image_table`
--

CREATE TABLE `home_image_table` (
  `home_image_code` int(10) NOT NULL,
  `home_image` longblob NOT NULL,
  `home_image_description` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `home_image_table`
--

INSERT INTO `home_image_table` (`home_image_code`, `home_image`, `home_image_description`) VALUES
(1, 0x2e2e2f75706c6f6164732f686f6d655f696d6167652f6261636b67726f756e645f313738373830313035383435305f6261636b67726f756e645f313738353734363439383130355f6261636b67726f756e645f313733363638383732393330325f70726f662e706e67, 'Enjoy moments within our branches, make memories with our coffee'),
(2, 0x2e2e2f75706c6f6164732f686f6d655f696d6167652f6261636b67726f756e645f313738373830313036383430315f6261636b67726f756e645f313738353734363537333838395f6261636b67726f756e645f313733363533343532333433335f6a6a6b2e706e67, 'We will serve you and will make your preferred coffee');

-- --------------------------------------------------------

--
-- Table structure for table `logo_table`
--

CREATE TABLE `logo_table` (
  `logo_id` varchar(100) NOT NULL,
  `logo_image` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logo_table`
--

INSERT INTO `logo_table` (`logo_id`, `logo_image`) VALUES
('logo', 0x2e2e2f75706c6f6164732f6c6f676f5f696d6167652f6c6f676f5f366138666163663230383464392e706e67);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `account_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 07:57:10'),
(2, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 08:00:40'),
(3, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 08:05:13'),
(4, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 11:56:27'),
(5, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:03:51'),
(6, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:09:15'),
(7, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:09:29'),
(8, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:10:08'),
(9, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:14:28'),
(10, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:15:51'),
(11, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:18:51'),
(12, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:22:34'),
(13, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:24:41'),
(14, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:28:44'),
(15, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:39:58'),
(16, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 12:46:43'),
(17, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 13:01:47'),
(18, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 13:04:13'),
(19, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 13:36:33'),
(20, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 13:44:51'),
(21, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 14:01:36'),
(22, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 14:06:10'),
(23, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 14:19:55'),
(24, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 14:22:27'),
(25, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-29 14:23:11'),
(26, 1, 'Order Receipt Available', 'Your order receipt has been sent successfully to your email. Queue Number: #001.', 1, '2026-08-31 07:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `order_table`
--

CREATE TABLE `order_table` (
  `order_id` int(100) NOT NULL,
  `order_username` varchar(100) NOT NULL,
  `order_product_name` varchar(100) NOT NULL,
  `order_cup_size` varchar(100) NOT NULL,
  `order_flavor` varchar(100) NOT NULL,
  `order_add_on` varchar(100) NOT NULL,
  `order_quantity` int(10) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_total_price` float(7,2) NOT NULL,
  `order_status` varchar(100) NOT NULL,
  `is_cancelled` varchar(100) NOT NULL,
  `mode_payment` varchar(100) NOT NULL,
  `queue_number` int(10) NOT NULL,
  `queue_status` varchar(100) NOT NULL,
  `cancellation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_amount` float(7,2) NOT NULL,
  `change_amount` float(7,2) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_number` varchar(100) NOT NULL,
  `order_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_table`
--

CREATE TABLE `product_table` (
  `product_code` varchar(100) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `minutes` int(10) NOT NULL,
  `product_price` float(7,2) NOT NULL,
  `category_code` varchar(100) NOT NULL,
  `product_created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_status` varchar(100) NOT NULL,
  `product_image` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_table`
--

INSERT INTO `product_table` (`product_code`, `product_name`, `minutes`, `product_price`, `category_code`, `product_created_date`, `product_status`, `product_image`) VALUES
('P-101', 'Capuccino', 10, 50.00, 'Coffee', '2026-08-29 01:28:07', 'AVAILABLE', 0x2e2e2f75706c6f6164732f70726f647563745f696d6167652f70726f647563745f36613937636635306261343032322e34323037393938392e706e67),
('P-102', 'Latte', 10, 100.00, 'Coffee', '2026-08-27 04:00:25', 'AVAILABLE', 0x2e2e2f75706c6f6164732f70726f647563745f696d6167652f70726f647563745f36613937636565373434366435302e39353238333132392e77656270),
('P-103', 'Americano', 10, 100.00, 'Coffee', '2026-08-29 02:06:44', 'AVAILABLE', 0x2e2e2f75706c6f6164732f70726f647563745f696d6167652f70726f647563745f36613937616338616332373832302e35373135323333362e706e67),
('P-104', 'Machiatto', 10, 105.00, 'Coffee', '2026-09-02 07:27:43', 'AVAILABLE', 0x2e2e2f75706c6f6164732f70726f647563745f696d6167652f70726f647563745f36613937636666353935353439332e31393833383235342e706e67),
('P-105', 'Irish Coffee', 20, 120.00, 'Coffee', '2026-09-02 07:28:37', 'AVAILABLE', 0x2e2e2f75706c6f6164732f70726f647563745f696d6167652f70726f647563745f36613937643032396265646263302e38313532313638342e77656270);

-- --------------------------------------------------------

--
-- Table structure for table `slideshow_table`
--

CREATE TABLE `slideshow_table` (
  `slideshow_code` int(11) NOT NULL,
  `slideshow_file` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slideshow_table`
--

INSERT INTO `slideshow_table` (`slideshow_code`, `slideshow_file`) VALUES
(1, 0x2e2e2f75706c6f6164732f736c69646573686f775f66696c652f736c69646573686f775f366139376433343161353562372e706e67),
(2, 0x2e2e2f75706c6f6164732f736c69646573686f775f66696c652f736c69646573686f775f366139376433343930323438392e706e67),
(3, 0x2e2e2f75706c6f6164732f736c69646573686f775f66696c652f736c69646573686f775f366139376433353130346634612e706e67),
(4, 0x2e2e2f75706c6f6164732f736c69646573686f775f66696c652f736c69646573686f775f366139376433356135633237312e706e67),
(5, 0x2e2e2f75706c6f6164732f736c69646573686f775f66696c652f736c69646573686f775f366139376433363731623663392e706e67);

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `account_id` int(100) NOT NULL,
  `account_username` varchar(100) NOT NULL,
  `account_email` varchar(100) NOT NULL,
  `account_password` varchar(1000) NOT NULL,
  `verification_code` int(10) NOT NULL,
  `account_status` varchar(100) NOT NULL,
  `is_logged` varchar(100) NOT NULL,
  `account_image` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_on_table`
--
ALTER TABLE `add_on_table`
  ADD PRIMARY KEY (`add_on_key`);

--
-- Indexes for table `cancel_history_table`
--
ALTER TABLE `cancel_history_table`
  ADD PRIMARY KEY (`list_number`);

--
-- Indexes for table `cart_table`
--
ALTER TABLE `cart_table`
  ADD PRIMARY KEY (`cart_id`);

--
-- Indexes for table `category_table`
--
ALTER TABLE `category_table`
  ADD PRIMARY KEY (`category_code`);

--
-- Indexes for table `cups_table`
--
ALTER TABLE `cups_table`
  ADD PRIMARY KEY (`cup_key`);

--
-- Indexes for table `flavor_table`
--
ALTER TABLE `flavor_table`
  ADD PRIMARY KEY (`flavor_key`);

--
-- Indexes for table `history_table`
--
ALTER TABLE `history_table`
  ADD PRIMARY KEY (`list_number`);

--
-- Indexes for table `home_image_table`
--
ALTER TABLE `home_image_table`
  ADD PRIMARY KEY (`home_image_code`);

--
-- Indexes for table `logo_table`
--
ALTER TABLE `logo_table`
  ADD PRIMARY KEY (`logo_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `order_table`
--
ALTER TABLE `order_table`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `product_table`
--
ALTER TABLE `product_table`
  ADD PRIMARY KEY (`product_code`);

--
-- Indexes for table `slideshow_table`
--
ALTER TABLE `slideshow_table`
  ADD PRIMARY KEY (`slideshow_code`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `account_username` (`account_username`,`account_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cancel_history_table`
--
ALTER TABLE `cancel_history_table`
  MODIFY `list_number` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_table`
--
ALTER TABLE `cart_table`
  MODIFY `cart_id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `history_table`
--
ALTER TABLE `history_table`
  MODIFY `list_number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `slideshow_table`
--
ALTER TABLE `slideshow_table`
  MODIFY `slideshow_code` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `account_id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
