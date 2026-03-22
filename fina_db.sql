-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 14, 2026 at 08:29 AM
-- Server version: 10.11.14-MariaDB-ubu2204
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fina_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts_payable`
--

CREATE TABLE `accounts_payable` (
  `id` int(11) NOT NULL,
  `invoice_id` varchar(30) NOT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `expense_categories` varchar(100) NOT NULL,
  `expense_subcategory` varchar(100) DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) NOT NULL,
  `document` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_due` datetime NOT NULL,
  `status` enum('pending','approved','rejected','archived','paid') DEFAULT 'pending',
  `approval_date` datetime DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `ecash_provider` varchar(100) NOT NULL,
  `ecash_account_name` varchar(100) NOT NULL,
  `ecash_account_number` varchar(50) NOT NULL,
  `vendor_type` enum('Vendor','Supplier') DEFAULT 'Vendor',
  `vendor_address` text DEFAULT NULL,
  `gl_account` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts_payable`
--

INSERT INTO `accounts_payable` (`id`, `invoice_id`, `po_number`, `expense_categories`, `expense_subcategory`, `department`, `vendor_name`, `payment_method`, `document`, `description`, `amount`, `amount_paid`, `payment_due`, `status`, `approval_date`, `paid_date`, `created_at`, `updated_at`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `vendor_type`, `vendor_address`, `gl_account`, `invoice_date`) VALUES
(1, 'INV-20251116-5498', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Rapid Fleet Maintenance', 'Cash', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 12229.00, 0.00, '2025-11-16 00:00:00', 'approved', '2026-02-10 23:21:35', NULL, '2026-02-10 18:22:50', '2026-02-11 14:26:58', '', '', '', '', '', '', 'Vendor', '45 Shaw Blvd, Pasig City', '512001 - Maintenance & Servicing', '2025-11-16'),
(3, 'INV-20260106-4762', NULL, 'Supplies & Technology', 'Office Supplies', 'Core-1', 'Office Warehouse', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Office Supplies', 19217.00, 0.00, '2026-01-06 00:00:00', 'approved', '2026-02-11 22:42:49', NULL, '2026-02-10 18:22:50', '2026-02-11 22:42:49', NULL, NULL, NULL, '', '', '', 'Supplier', 'Quezon Ave, Quezon City', '554001 - Office Supplies', '2026-01-06'),
(4, 'INV-20250903-6865', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Logistic-1', 'AIG Insurance Phils', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Parts Replacement', 18163.00, 0.00, '2025-09-03 00:00:00', 'approved', '2026-02-13 22:22:06', NULL, '2026-02-10 18:22:50', '2026-02-13 22:22:06', NULL, NULL, NULL, '', '', '', 'Vendor', '1 Insurance Plaza, BGC Taguig', '513001 - Tire Replacement', '2025-09-03'),
(5, 'INV-20251105-3449', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Rapid Fleet Maintenance', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 15602.00, 0.00, '2025-11-05 00:00:00', 'approved', '2026-02-13 23:29:04', NULL, '2026-02-10 18:22:50', '2026-02-13 23:29:04', NULL, NULL, NULL, '', '', '', 'Vendor', '45 Shaw Blvd, Pasig City', '512001 - Maintenance & Servicing', '2025-11-05'),
(6, 'INV-20250916-5922', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Logistic-1', 'AIG Insurance Phils', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Parts Replacement', 24769.00, 0.00, '2025-09-16 00:00:00', 'approved', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Vendor', '1 Insurance Plaza, BGC Taguig', '513001 - Tire Replacement', '2025-09-16'),
(8, 'INV-20251213-3259', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Logistic-1', 'Petron Boni Ave', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Fuel & Energy', 20411.00, 0.00, '2025-12-13 00:00:00', 'approved', NULL, NULL, '2026-02-10 18:22:50', '2026-02-11 14:28:53', NULL, NULL, NULL, '', '', '', 'Supplier', '12 Makati Ave, Makati City', '511001 - Fuel & Energy Costs', '2025-12-13'),
(9, 'INV-20251111-9562', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Rapid Fleet Maintenance', 'Cash', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 5400.00, 0.00, '2025-11-11 00:00:00', 'approved', NULL, NULL, '2026-02-10 18:22:50', '2026-02-11 14:36:08', NULL, NULL, NULL, '', '', '', 'Vendor', '45 Shaw Blvd, Pasig City', '512001 - Maintenance & Servicing', '2025-11-11'),
(12, 'INV-20260113-8776', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Toyota Pasong Tamo', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 6720.00, 0.00, '2026-01-13 00:00:00', 'rejected', NULL, NULL, '2026-02-10 18:22:50', '2026-02-11 14:36:24', NULL, NULL, NULL, '', '', '', 'Vendor', 'Toyota St, Makati City', '512001 - Maintenance & Servicing', '2026-01-13'),
(13, 'INV-20260101-4424', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Core-1', 'AIG Insurance Phils', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Parts Replacement', 5696.00, 0.00, '2026-01-01 00:00:00', 'rejected', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Vendor', '1 Insurance Plaza, BGC Taguig', '513001 - Tire Replacement', '2026-01-01'),
(14, 'INV-20260128-1784', NULL, 'Indirect Costs', 'Legal & Compliance', 'Core-1', 'Globe Business', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Legal & Compliance', 13143.00, 0.00, '2026-01-28 00:00:00', 'rejected', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Supplier', 'Globe Tower, BGC Taguig', '553001 - Legal & Compliance', '2026-01-28'),
(15, 'INV-20250927-4380', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Logistic-1', 'Shell Makati', 'Cash', 'sample_receipt.pdf', 'Acquisition for Fuel & Energy', 10863.00, 0.00, '2025-09-27 00:00:00', 'rejected', NULL, NULL, '2026-02-10 18:22:50', '2026-02-11 14:36:08', NULL, NULL, NULL, '', '', '', 'Supplier', '88 Buendia Ave, Makati City', '511001 - Fuel & Energy Costs', '2025-09-27'),
(17, 'INV-20251002-8028', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Logistic-1', 'AIG Insurance Phils', 'Cash', 'sample_receipt.pdf', 'Acquisition for Parts Replacement', 5153.00, 0.00, '2025-10-02 00:00:00', 'archived', NULL, NULL, '2026-02-10 18:22:50', '2026-02-11 14:36:08', NULL, NULL, NULL, '', '', '', 'Vendor', '1 Insurance Plaza, BGC Taguig', '513001 - Tire Replacement', '2025-10-02'),
(18, 'INV-20251203-6849', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Toyota Pasong Tamo', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 9692.00, 0.00, '2025-12-03 00:00:00', 'archived', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Vendor', 'Toyota St, Makati City', '512001 - Maintenance & Servicing', '2025-12-03'),
(19, 'INV-20251003-1812', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Core-2', 'Petron Boni Ave', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Fuel & Energy', 5341.00, 0.00, '2025-10-03 00:00:00', 'archived', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Supplier', '12 Makati Ave, Makati City', '511001 - Fuel & Energy Costs', '2025-10-03'),
(20, 'INV-20251010-5860', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Rapid Fleet Maintenance', 'Bank Transfer', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 17319.00, 0.00, '2025-10-10 00:00:00', 'archived', NULL, NULL, '2026-02-10 18:22:50', '2026-02-11 14:32:30', NULL, NULL, NULL, '', '', '', 'Vendor', '45 Shaw Blvd, Pasig City', '512001 - Maintenance & Servicing', '2025-10-10'),
(21, 'INV-20260115-4276', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Administrative', 'Toyota Pasong Tamo', 'Cash', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 9456.00, 0.00, '2026-01-15 00:00:00', 'paid', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Vendor', 'Toyota St, Makati City', '512001 - Maintenance & Servicing', '2026-01-15'),
(22, 'INV-20251218-8743', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Human Resource-1', 'AIG Insurance Phils', 'Cash', 'sample_receipt.pdf', 'Acquisition for Parts Replacement', 23159.00, 0.00, '2025-12-18 00:00:00', 'paid', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Vendor', '1 Insurance Plaza, BGC Taguig', '513001 - Tire Replacement', '2025-12-18'),
(23, 'INV-20251130-6356', NULL, 'Indirect Costs', 'Legal & Compliance', 'Administrative', 'Globe Business', 'Cash', 'sample_receipt.pdf', 'Acquisition for Legal & Compliance', 21555.00, 0.00, '2025-11-30 00:00:00', 'paid', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Supplier', 'Globe Tower, BGC Taguig', '553001 - Legal & Compliance', '2025-11-30'),
(24, 'INV-20260111-5925', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Core-1', 'Toyota Pasong Tamo', 'Cash', 'sample_receipt.pdf', 'Acquisition for Vehicle Maintenance', 8269.00, 0.00, '2026-01-11 00:00:00', 'paid', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Vendor', 'Toyota St, Makati City', '512001 - Maintenance & Servicing', '2026-01-11'),
(25, 'INV-20251005-6511', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Human Resource-3', 'Shell Makati', 'Cash', 'sample_receipt.pdf', 'Acquisition for Fuel & Energy', 13475.00, 0.00, '2025-10-05 00:00:00', 'paid', NULL, NULL, '2026-02-10 18:22:50', '2026-02-10 18:22:50', NULL, NULL, NULL, '', '', '', 'Supplier', '88 Buendia Ave, Makati City', '511001 - Fuel & Energy Costs', '2025-10-05'),
(27, 'INV-2026-TEST-01', NULL, 'Office Supplies', 'Stationery', 'Logistic 1', 'ViaHale Supplier Co.', 'Bank Transfer', '[]', 'Monthly supply of office materials', 25500.00, 0.00, '2026-03-15 00:00:00', 'approved', '2026-02-13 22:05:17', NULL, '2026-02-13 11:25:23', '2026-02-13 22:05:17', '', '', '', '', '', '', 'Vendor', 'Quezon City, Manila', '50101', '2026-02-13'),
(28, 'INV-20260213-1001', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'AutoParts Supply Co.', 'Cash', 'sample_invoice.pdf', 'Vehicle spare parts and accessories', 8500.00, 0.00, '2026-02-20 00:00:00', 'approved', '2026-02-14 12:02:59', NULL, '2026-02-13 23:46:19', '2026-02-14 12:02:59', '', '', '', '', '', '', 'Vendor', '123 Quezon Ave, Quezon City', '512001 - Maintenance & Servicing', '2026-02-13'),
(29, 'INV-20260213-1002', NULL, 'Supplies & Technology', 'Office Supplies', 'Administrative', 'Office Depot Manila', 'Bank Transfer', 'sample_invoice.pdf', 'Office furniture and equipment', 12300.00, 0.00, '2026-02-21 00:00:00', 'approved', '2026-02-14 12:03:56', NULL, '2026-02-13 23:46:19', '2026-02-14 12:03:56', 'BDO', 'Office Depot Account', '1234567890', '', '', '', 'Supplier', '45 EDSA, Mandaluyong City', '554001 - Office Supplies', '2026-02-13'),
(30, 'INV-20260213-1003', NULL, 'Supplies & Technology', 'Hardware & Software', 'Human Resource-1', 'TechSolutions Inc.', 'Bank Transfer', 'sample_invoice.pdf', 'Computer and IT equipment', 25000.00, 0.00, '2026-02-22 00:00:00', 'approved', '2026-02-14 14:04:53', NULL, '2026-02-13 23:46:19', '2026-02-14 14:04:53', 'BPI', 'TechSolutions Inc', '0987654321', '', '', '', 'Vendor', '88 BGC, Taguig City', '555001 - IT Infrastructure', '2026-02-13'),
(31, 'INV-20260213-1004', NULL, 'Indirect Costs', 'Utilities', 'Core-1', 'Globe Telecom', 'Bank Transfer', 'sample_invoice.pdf', 'Monthly internet and phone services', 15600.00, 0.00, '2026-02-23 00:00:00', 'approved', '2026-02-14 14:18:26', NULL, '2026-02-13 23:46:19', '2026-02-14 14:18:26', 'Metrobank', 'Globe Telecom', '1122334455', '', '', '', 'Supplier', 'Globe Tower, BGC Taguig', '552001 - Communication Costs', '2026-02-13'),
(32, 'INV-20260213-1005', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Logistic-2', 'Shell Makati', 'Cash', 'sample_invoice.pdf', 'Fleet fuel purchase', 18900.00, 0.00, '2026-02-24 00:00:00', 'approved', '2026-02-14 14:20:56', NULL, '2026-02-13 23:46:19', '2026-02-14 14:20:56', '', '', '', '', '', '', 'Supplier', '88 Buendia Ave, Makati City', '511001 - Fuel & Energy Costs', '2026-02-13'),
(33, 'INV-20260213-1006', NULL, 'Indirect Costs', 'Facilities Management', 'Administrative', 'Metro Cleaning Services', 'Bank Transfer', 'sample_invoice.pdf', 'Monthly janitorial and cleaning services', 9500.00, 0.00, '2026-02-25 00:00:00', 'approved', '2026-02-14 14:24:20', NULL, '2026-02-13 23:46:19', '2026-02-14 14:24:20', 'BDO', 'Metro Cleaning', '5566778899', '', '', '', 'Vendor', '22 Ortigas Ave, Pasig City', '551001 - Rent & Facilities', '2026-02-13'),
(34, 'INV-20260213-1007', NULL, 'Indirect Costs', 'Utilities', 'Core-2', 'Manila Water Company', 'Bank Transfer', 'sample_invoice.pdf', 'Water utility bill payment', 6800.00, 0.00, '2026-02-26 00:00:00', 'approved', '2026-02-14 14:28:34', NULL, '2026-02-13 23:46:19', '2026-02-14 14:28:34', 'UnionBank', 'Manila Water', '9988776655', '', '', '', 'Supplier', 'Manila Water Bldg, Quezon City', '552002 - Water & Utilities', '2026-02-13'),
(35, 'INV-20260213-1008', NULL, 'Indirect Costs', 'Utilities', 'Administrative', 'Meralco', 'Bank Transfer', 'sample_invoice.pdf', 'Electricity bill payment', 22400.00, 0.00, '2026-02-27 00:00:00', 'approved', '2026-02-14 14:47:42', NULL, '2026-02-13 23:46:19', '2026-02-14 14:47:42', 'BPI', 'Meralco', '4433221100', '', '', '', 'Supplier', 'Meralco Center, Ortigas', '552003 - Electricity', '2026-02-13'),
(36, 'INV-20260213-1009', NULL, 'Indirect Costs', 'Security Services', 'Human Resource-2', 'Security Plus Agency', 'Cash', 'sample_invoice.pdf', 'Security guard services', 28000.00, 0.00, '2026-02-28 00:00:00', 'approved', '2026-02-14 14:58:59', NULL, '2026-02-13 23:46:19', '2026-02-14 14:58:59', '', '', '', '', '', '', 'Vendor', '67 Shaw Blvd, Pasig City', '551002 - Security Services', '2026-02-13'),
(37, 'INV-20260213-1010', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Rapid Fleet Maintenance', 'Bank Transfer', 'sample_invoice.pdf', 'Vehicle preventive maintenance', 16500.00, 0.00, '2026-03-01 00:00:00', 'approved', '2026-02-14 15:07:58', NULL, '2026-02-13 23:46:19', '2026-02-14 15:07:58', 'BDO', 'Rapid Fleet', '7788990011', '', '', '', 'Vendor', '45 Shaw Blvd, Pasig City', '512001 - Maintenance & Servicing', '2026-02-13'),
(38, 'INV-20260213-1001', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'AutoParts Supply Co.', 'Cash', 'sample_invoice.pdf', 'Vehicle spare parts and accessories', 8500.00, 0.00, '2026-02-20 00:00:00', 'approved', '2026-02-14 12:02:59', NULL, '2026-02-13 23:50:21', '2026-02-14 12:02:59', '', '', '', '', '', '', 'Vendor', '123 Quezon Ave, Quezon City', '512001 - Maintenance & Servicing', '2026-02-13'),
(39, 'INV-20260213-1002', NULL, 'Supplies & Technology', 'Office Supplies', 'Administrative', 'Office Depot Manila', 'Bank Transfer', 'sample_invoice.pdf', 'Office furniture and equipment', 12300.00, 0.00, '2026-02-21 00:00:00', 'approved', '2026-02-14 12:03:56', NULL, '2026-02-13 23:50:21', '2026-02-14 12:03:56', 'BDO', 'Office Depot Account', '1234567890', '', '', '', 'Supplier', '45 EDSA, Mandaluyong City', '554001 - Office Supplies', '2026-02-13'),
(40, 'INV-20260213-1003', NULL, 'Supplies & Technology', 'Hardware & Software', 'Human Resource-1', 'TechSolutions Inc.', 'Bank Transfer', 'sample_invoice.pdf', 'Computer and IT equipment', 25000.00, 0.00, '2026-02-22 00:00:00', 'approved', '2026-02-14 14:04:53', NULL, '2026-02-13 23:50:21', '2026-02-14 14:04:53', 'BPI', 'TechSolutions Inc', '0987654321', '', '', '', 'Vendor', '88 BGC, Taguig City', '555001 - IT Infrastructure', '2026-02-13'),
(41, 'INV-20260213-1004', NULL, 'Indirect Costs', 'Utilities', 'Core-1', 'Globe Telecom', 'Bank Transfer', 'sample_invoice.pdf', 'Monthly internet and phone services', 15600.00, 0.00, '2026-02-23 00:00:00', 'approved', '2026-02-14 14:18:26', NULL, '2026-02-13 23:50:21', '2026-02-14 14:18:26', 'Metrobank', 'Globe Telecom', '1122334455', '', '', '', 'Supplier', 'Globe Tower, BGC Taguig', '552001 - Communication Costs', '2026-02-13'),
(42, 'INV-20260213-1005', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Logistic-2', 'Shell Makati', 'Cash', 'sample_invoice.pdf', 'Fleet fuel purchase', 18900.00, 0.00, '2026-02-24 00:00:00', 'approved', '2026-02-14 14:20:56', NULL, '2026-02-13 23:50:21', '2026-02-14 14:20:56', '', '', '', '', '', '', 'Supplier', '88 Buendia Ave, Makati City', '511001 - Fuel & Energy Costs', '2026-02-13'),
(43, 'INV-20260213-1006', NULL, 'Indirect Costs', 'Facilities Management', 'Administrative', 'Metro Cleaning Services', 'Bank Transfer', 'sample_invoice.pdf', 'Monthly janitorial and cleaning services', 9500.00, 0.00, '2026-02-25 00:00:00', 'approved', '2026-02-14 14:24:20', NULL, '2026-02-13 23:50:21', '2026-02-14 14:24:20', 'BDO', 'Metro Cleaning', '5566778899', '', '', '', 'Vendor', '22 Ortigas Ave, Pasig City', '551001 - Rent & Facilities', '2026-02-13'),
(44, 'INV-20260213-1007', NULL, 'Indirect Costs', 'Utilities', 'Core-2', 'Manila Water Company', 'Bank Transfer', 'sample_invoice.pdf', 'Water utility bill payment', 6800.00, 0.00, '2026-02-26 00:00:00', 'approved', '2026-02-14 14:28:34', NULL, '2026-02-13 23:50:21', '2026-02-14 14:28:34', 'UnionBank', 'Manila Water', '9988776655', '', '', '', 'Supplier', 'Manila Water Bldg, Quezon City', '552002 - Water & Utilities', '2026-02-13'),
(45, 'INV-20260213-1008', NULL, 'Indirect Costs', 'Utilities', 'Administrative', 'Meralco', 'Bank Transfer', 'sample_invoice.pdf', 'Electricity bill payment', 22400.00, 0.00, '2026-02-27 00:00:00', 'approved', '2026-02-14 14:47:42', NULL, '2026-02-13 23:50:21', '2026-02-14 14:47:42', 'BPI', 'Meralco', '4433221100', '', '', '', 'Supplier', 'Meralco Center, Ortigas', '552003 - Electricity', '2026-02-13'),
(46, 'INV-20260213-1009', NULL, 'Indirect Costs', 'Security Services', 'Human Resource-2', 'Security Plus Agency', 'Cash', 'sample_invoice.pdf', 'Security guard services', 28000.00, 0.00, '2026-02-28 00:00:00', 'approved', '2026-02-14 14:58:59', NULL, '2026-02-13 23:50:21', '2026-02-14 14:58:59', '', '', '', '', '', '', 'Vendor', '67 Shaw Blvd, Pasig City', '551002 - Security Services', '2026-02-13'),
(47, 'INV-20260213-1010', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Rapid Fleet Maintenance', 'Bank Transfer', 'sample_invoice.pdf', 'Vehicle preventive maintenance', 16500.00, 0.00, '2026-03-01 00:00:00', 'approved', '2026-02-14 15:07:58', NULL, '2026-02-13 23:50:21', '2026-02-14 15:07:58', 'BDO', 'Rapid Fleet', '7788990011', '', '', '', 'Vendor', '45 Shaw Blvd, Pasig City', '512001 - Maintenance & Servicing', '2026-02-13'),
(48, 'INV-20260214-5001', NULL, 'Indirect Costs', 'Facilities Management', 'Administrative', 'CleanPro Services Inc.', 'Bank Transfer', 'invoice_5001.pdf', 'Monthly janitorial and sanitation services', 11500.00, 0.00, '2026-02-28 00:00:00', 'paid', '2026-02-14 15:18:37', '2026-02-14 15:58:34', '2026-02-14 15:18:14', '2026-02-14 15:58:34', 'BDO', 'CleanPro Services', '9876543210', '', '', '', 'Vendor', '456 Ortigas Ave, Pasig City', '551001 - Rent & Facilities', '2026-02-14'),
(49, 'INV-20260214-5002', NULL, 'Indirect Costs', 'Utilities', 'Core-1', 'PLDT Fibr Business', 'Bank Transfer', 'invoice_5002.pdf', 'High-speed internet connection - monthly subscription', 8900.00, 0.00, '2026-02-28 00:00:00', 'paid', '2026-02-14 15:19:55', '2026-02-14 15:50:37', '2026-02-14 15:18:14', '2026-02-14 15:50:37', 'Metrobank', 'PLDT Corporation', '1357924680', '', '', '', 'Supplier', 'PLDT Building, Makati City', '552001 - Communication Costs', '2026-02-14'),
(50, 'INV-20260214-5003', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Logistic-1', 'Toyota Genuine Parts', 'Cash', 'invoice_5003.pdf', 'Brake pads and filters for fleet vehicles', 14200.00, 0.00, '2026-02-25 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', '', '', '', '', '', '', 'Vendor', '789 Commonwealth Ave, Quezon City', '513001 - Tire Replacement', '2026-02-14'),
(51, 'INV-20260214-5004', NULL, 'Indirect Costs', 'Security Services', 'Human Resource-1', 'Guardian Security Agency', 'Bank Transfer', 'invoice_5004.pdf', 'Security personnel services - February 2026', 32000.00, 0.00, '2026-03-05 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', 'BPI', 'Guardian Security', '2468013579', '', '', '', 'Vendor', '321 Bonifacio St, Manila', '551002 - Security Services', '2026-02-14'),
(52, 'INV-20260214-5005', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Logistic-2', 'Petron Gasul', 'Cash', 'invoice_5005.pdf', 'Diesel fuel for delivery trucks', 22500.00, 0.00, '2026-02-20 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', '', '', '', '', '', '', 'Supplier', '567 EDSA, Quezon City', '511001 - Fuel & Energy Costs', '2026-02-14'),
(53, 'INV-20260214-5006', NULL, 'Supplies & Technology', 'Hardware & Software', 'Core-2', 'PC Express Store', 'Bank Transfer', 'invoice_5006.pdf', 'Laptops and monitors for office staff', 45000.00, 0.00, '2026-03-01 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', 'UnionBank', 'PC Express', '3691258470', '', '', '', 'Vendor', '234 SM North EDSA, Quezon City', '555001 - IT Infrastructure', '2026-02-14'),
(54, 'INV-20260214-5007', NULL, 'Supplies & Technology', 'Office Supplies', 'Administrative', 'National Bookstore', 'Cash', 'invoice_5007.pdf', 'Paper, pens, folders and office supplies', 5800.00, 0.00, '2026-02-22 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', '', '', '', '', '', '', 'Supplier', '890 Quezon Ave, Quezon City', '554001 - Office Supplies', '2026-02-14'),
(55, 'INV-20260214-5008', NULL, 'Indirect Costs', 'Utilities', 'Administrative', 'Maynilad Water Services', 'Bank Transfer', 'invoice_5008.pdf', 'Water consumption - January 2026', 4500.00, 0.00, '2026-02-28 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', 'BDO', 'Maynilad', '7412589630', '', '', '', 'Supplier', 'Maynilad Office, Quezon City', '552002 - Water & Utilities', '2026-02-14'),
(56, 'INV-20260214-5009', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-1', 'Caltex Auto Service Center', 'Bank Transfer', 'invoice_5009.pdf', 'Preventive maintenance service - 3 vehicles', 18500.00, 0.00, '2026-02-26 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', 'Security Bank', 'Caltex', '9517538520', '', '', '', 'Vendor', '123 South Superhighway, Makati', '512001 - Maintenance & Servicing', '2026-02-14'),
(57, 'INV-20260214-5010', NULL, 'Indirect Costs', 'Legal & Compliance', 'Core-1', 'Atty. Santos Law Office', 'Bank Transfer', 'invoice_5010.pdf', 'Legal consultation and document review', 25000.00, 0.00, '2026-03-10 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:18:14', '2026-02-14 15:18:14', 'BPI', 'Santos Law Office', '1593574560', '', '', '', 'Vendor', '678 Ayala Ave, Makati City', '553001 - Legal & Compliance', '2026-02-14'),
(58, 'INV-20260214-6001', NULL, 'Indirect Costs', 'Courier Services', 'Administrative', 'LBC Express', 'Cash', 'lbc_inv.pdf', 'Document delivery and shipping services', 3200.00, 0.00, '2026-02-25 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Vendor', '111 España Blvd, Manila', '551003 - Courier Services', '2026-02-14'),
(59, 'INV-20260214-6002', NULL, 'Indirect Costs', 'Insurance', 'Logistic-1', 'Malayan Insurance Co.', 'Bank Transfer', 'insurance_inv.pdf', 'Vehicle insurance renewal - quarterly premium', 28000.00, 0.00, '2026-03-01 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'BDO', 'Malayan Insurance', '4561237890', '', '', '', 'Supplier', '888 Ayala Triangle, Makati', '551004 - Insurance', '2026-02-14'),
(60, 'INV-20260214-6003', NULL, 'Supplies & Technology', 'Office Supplies', 'Core-1', 'Vistaprint Philippines', 'Bank Transfer', 'print_inv.pdf', 'Business cards and marketing materials', 9500.00, 0.00, '2026-02-28 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'Metrobank', 'Vistaprint PH', '7894561230', '', '', '', 'Vendor', '222 Tomas Morato, Quezon City', '554001 - Office Supplies', '2026-02-14'),
(61, 'INV-20260214-6004', NULL, 'Indirect Costs', 'Facilities Management', 'Administrative', 'Aircon Master Services', 'Cash', 'aircon_inv.pdf', 'Monthly aircon cleaning and maintenance', 7800.00, 0.00, '2026-02-20 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Vendor', '333 Quezon Ave, Quezon City', '551001 - Rent & Facilities', '2026-02-14'),
(62, 'INV-20260214-6005', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Logistic-2', 'Seaoil Station Makati', 'Cash', 'seaoil_inv.pdf', 'Premium gasoline for company vehicles', 16500.00, 0.00, '2026-02-22 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Supplier', '444 Buendia Ave, Makati', '511001 - Fuel & Energy Costs', '2026-02-14'),
(63, 'INV-20260214-6006', NULL, 'Supplies & Technology', 'Hardware & Software', 'Core-2', 'Octagon Computer Superstore', 'Bank Transfer', 'octagon_inv.pdf', 'Laptop repairs and hardware replacement', 12300.00, 0.00, '2026-02-27 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'BPI', 'Octagon', '1472583690', '', '', '', 'Vendor', '555 SM Megamall, Mandaluyong', '555001 - IT Infrastructure', '2026-02-14'),
(64, 'INV-20260214-6007', NULL, 'Indirect Costs', 'Facilities Management', 'Administrative', 'Rentokil Pest Control', 'Bank Transfer', 'rentokil_inv.pdf', 'Quarterly pest control treatment', 4500.00, 0.00, '2026-02-28 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'Security Bank', 'Rentokil', '9638527410', '', '', '', 'Vendor', '666 Ortigas Center, Pasig', '551001 - Rent & Facilities', '2026-02-14'),
(65, 'INV-20260214-6008', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Logistic-1', 'Bridgestone Tire Center', 'Cash', 'bridgestone_inv.pdf', 'New tires for 2 delivery trucks', 24000.00, 0.00, '2026-02-24 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Vendor', '777 Commonwealth Ave, QC', '513001 - Tire Replacement', '2026-02-14'),
(66, 'INV-20260214-6009', NULL, 'Indirect Costs', 'Utilities', 'Administrative', 'Wilkins Water Delivery', 'Cash', 'wilkins_inv.pdf', 'Water dispenser refills - 20 bottles', 2000.00, 0.00, '2026-02-18 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Supplier', '888 Katipunan Ave, QC', '552002 - Water & Utilities', '2026-02-14'),
(67, 'INV-20260214-6010', NULL, 'Supplies & Technology', 'Hardware & Software', 'Core-1', 'Microsoft Philippines', 'Bank Transfer', 'microsoft_inv.pdf', 'Microsoft 365 Business licenses - 10 users', 35000.00, 0.00, '2026-03-05 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'UnionBank', 'Microsoft Corp', '3216549870', '', '', '', 'Supplier', '999 BGC, Taguig City', '555001 - IT Infrastructure', '2026-02-14'),
(68, 'INV-20260214-6011', NULL, 'Indirect Costs', 'Facilities Management', 'Human Resource-1', 'Safety First Equipment', 'Cash', 'safety_inv.pdf', 'Fire extinguisher inspection and refill', 3800.00, 0.00, '2026-02-26 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Vendor', '123 Aurora Blvd, QC', '551001 - Rent & Facilities', '2026-02-14'),
(69, 'INV-20260214-6012', NULL, 'Direct Operating Costs', 'Vehicle Maintenance', 'Logistic-2', 'AutoGlow Car Spa', 'Cash', 'autoglow_inv.pdf', 'Professional car wash and detailing - 5 vehicles', 5500.00, 0.00, '2026-02-19 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Vendor', '234 Marcos Highway, Pasig', '512001 - Maintenance & Servicing', '2026-02-14'),
(70, 'INV-20260214-6013', NULL, 'Supplies & Technology', 'Office Equipment', 'Administrative', 'IKEA Manila', 'Bank Transfer', 'ikea_inv.pdf', 'Office chairs and desk organizers', 18900.00, 0.00, '2026-03-02 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'BDO', 'IKEA Philippines', '7539514620', '', '', '', 'Supplier', '345 Mall of Asia, Pasay', '554002 - Office Equipment', '2026-02-14'),
(71, 'INV-20260214-6014', NULL, 'Indirect Costs', 'Utilities', 'Core-2', 'Meralco', 'Bank Transfer', 'meralco_inv.pdf', 'Electricity consumption - January 2026', 19500.00, 0.00, '2026-02-28 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'Metrobank', 'Manila Electric Co', '8527419630', '', '', '', 'Supplier', '456 Ortigas Ave, Pasig', '552003 - Electricity', '2026-02-14'),
(72, 'INV-20260214-6015', NULL, 'Supplies & Technology', 'Hardware & Software', 'Core-1', 'QuickBooks Philippines', 'Bank Transfer', 'quickbooks_inv.pdf', 'QuickBooks subscription - annual renewal', 42000.00, 0.00, '2026-03-10 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'BPI', 'Intuit QuickBooks', '9517534680', '', '', '', 'Supplier', '567 Makati Ave, Makati', '555001 - IT Infrastructure', '2026-02-14'),
(73, 'INV-20260214-6016', NULL, 'Direct Operating Costs', 'Fuel & Energy', 'Logistic-1', 'Phoenix Petroleum', 'Bank Transfer', 'phoenix_inv.pdf', 'Fuel card reload for fleet vehicles', 30000.00, 0.00, '2026-02-25 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'Security Bank', 'Phoenix Petroleum', '1593578520', '', '', '', 'Supplier', '678 EDSA, Mandaluyong', '511001 - Fuel & Energy Costs', '2026-02-14'),
(74, 'INV-20260214-6017', NULL, 'Indirect Costs', 'Employee Benefits', 'Human Resource-2', 'Corporate Uniforms Inc.', 'Cash', 'uniform_inv.pdf', 'Employee uniforms - 15 sets', 22500.00, 0.00, '2026-02-27 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Vendor', '789 Recto Ave, Manila', '551005 - Employee Benefits', '2026-02-14'),
(75, 'INV-20260214-6018', NULL, 'Indirect Costs', 'Utilities', 'Administrative', 'Converge ICT', 'Bank Transfer', 'converge_inv.pdf', 'Backup internet line - monthly fee', 6500.00, 0.00, '2026-02-28 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'UnionBank', 'Converge', '7418529630', '', '', '', 'Supplier', '890 Shaw Blvd, Pasig', '552001 - Communication Costs', '2026-02-14'),
(76, 'INV-20260214-6019', NULL, 'Direct Operating Costs', 'Parts Replacement', 'Logistic-2', 'Motolite Battery', 'Cash', 'motolite_inv.pdf', 'Car batteries for 3 vehicles', 15600.00, 0.00, '2026-02-23 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', '', '', '', '', '', '', 'Vendor', '901 Rizal Ave, Caloocan', '513001 - Tire Replacement', '2026-02-14'),
(77, 'INV-20260214-6020', NULL, 'Indirect Costs', 'Training & Development', 'Human Resource-1', 'Fully Booked', 'Bank Transfer', 'fullybooked_inv.pdf', 'Training books and materials for staff development', 8900.00, 0.00, '2026-03-01 00:00:00', 'pending', NULL, NULL, '2026-02-14 15:49:00', '2026-02-14 15:49:00', 'BDO', 'Fully Booked', '3571592840', '', '', '', 'Supplier', '012 BGC, Taguig', '551006 - Training', '2026-02-14');

-- --------------------------------------------------------

--
-- Table structure for table `account_receivable`
--

CREATE TABLE `account_receivable` (
  `invoice_id` varchar(100) NOT NULL,
  `driver_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `amount_paid` int(11) NOT NULL DEFAULT 0,
  `payment_method` varchar(255) NOT NULL,
  `approval_date` datetime NOT NULL,
  `fully_paid_date` date NOT NULL,
  `status` varchar(255) DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_receivable`
--

INSERT INTO `account_receivable` (`invoice_id`, `driver_name`, `description`, `amount`, `amount_paid`, `payment_method`, `approval_date`, `fully_paid_date`, `status`, `created_at`, `updated_at`) VALUES
('VCH-TRIP-101', 'Cardo Dalisay', 'Trike/Sedan Trip (Local)', 100, 0, 'Cash', '0000-00-00 00:00:00', '2026-02-28', 'pending', '2026-02-12 11:46:26', '2026-02-12 11:46:26'),
('VCH-TRIP-102', 'Victor Magtanggol', 'Premium Ride (City to City)', 500, 0, 'Credit', '0000-00-00 00:00:00', '2026-03-05', 'pending', '2026-02-12 11:46:26', '2026-02-12 11:46:26'),
('VCH-TRIP-103', 'Rizalito Mercado', 'Short Distance Ride', 50, 0, 'Cash', '0000-00-00 00:00:00', '2026-02-25', 'pending', '2026-02-12 11:46:26', '2026-02-12 11:46:26'),
('VCH-TRIP-104', 'Bonifacio Silang', 'Standard Ride (Core 1)', 150, 0, 'Credit', '0000-00-00 00:00:00', '2026-03-10', 'pending', '2026-02-12 11:46:26', '2026-02-12 11:46:26'),
('VCH-TRIP-105', 'Luna Generoso', 'Medium Distance Trip', 250, 0, 'Cash', '0000-00-00 00:00:00', '2026-02-20', 'pending', '2026-02-12 11:46:26', '2026-02-12 11:46:26'),
('VCH-TRIP-201', 'Cardo Dalisay', 'Commission - Trip #BK-8801 (QC to Pasig)', 450, 0, 'Cash', '0000-00-00 00:00:00', '2026-02-28', 'pending', '2026-02-12 11:48:16', '2026-02-12 11:48:16'),
('VCH-TRIP-202', 'Victor Magtanggol', 'Commission - Short Ride Trip #BK-9907 (Hospital Drop-off)', 85, 0, 'Credit', '0000-00-00 00:00:00', '2026-03-05', 'pending', '2026-02-12 11:48:16', '2026-02-12 11:48:16'),
('VCH-TRIP-203', 'Rizalito Mercado', 'Commission - Trip #BK-7702 (BGC Office Loop)', 120, 0, 'Cash', '0000-00-00 00:00:00', '2026-02-25', 'pending', '2026-02-12 11:48:16', '2026-02-12 11:48:16'),
('VCH-TRIP-204', 'Bonifacio Silang', 'Commission - Short Ride Trip #BK-4405 (Nearby Supermarket)', 65, 0, 'Credit', '0000-00-00 00:00:00', '2026-03-10', 'pending', '2026-02-12 11:48:16', '2026-02-12 11:48:16'),
('VCH-TRIP-205', 'Luna Generoso', 'Commission - Trip #BK-2201 (Manila Airport Run)', 550, 0, 'Cash', '0000-00-00 00:00:00', '2026-02-20', 'pending', '2026-02-12 11:48:16', '2026-02-12 11:48:16'),
('VCH-TRIP-401', 'Juan Luna', 'Commission - Trip #BK-1001 (Mall to Airport)', 450, 0, 'Cash', '2026-02-01 09:30:00', '2026-02-28', 'pending', '2026-02-12 11:56:09', '2026-02-12 11:56:09'),
('VCH-TRIP-402', 'Jose Rizal', 'Commission - Trip #BK-1002 (QC to Makati)', 351, 0, 'Credit', '2026-02-02 10:15:00', '2026-03-05', 'pending', '2026-02-12 11:56:09', '2026-02-12 11:56:09'),
('VCH-TRIP-403', 'Andres Bonifacio', 'Commission - Trip #BK-1003 (Hospital to Village)', 120, 0, 'Cash', '2026-02-03 11:20:00', '2026-02-25', 'pending', '2026-02-12 11:56:09', '2026-02-12 11:56:09'),
('VCH-TRIP-404', 'Emilio Aguinaldo', 'Commission - Trip #BK-1004 (BGC to Ortigas)', 280, 0, 'Credit', '2026-02-04 09:45:00', '2026-03-10', 'pending', '2026-02-12 11:56:09', '2026-02-12 11:56:09'),
('VCH-TRIP-405', 'Apolinario Mabini', 'Commission - Trip #BK-1005 (Makati CBD Loop)', 151, 0, 'Cash', '2026-02-05 14:30:00', '2026-02-20', 'confirmed', '2026-02-12 11:56:09', '2026-02-12 11:56:09'),
('VCH-TRIP-401', 'Apolinario Mabini', 'Commission - Trip #BK-4001 (Binondo to Malate)', 450, 0, 'Cash', '2026-02-01 09:30:00', '2026-02-28', 'pending', '2026-02-12 12:09:04', '2026-02-12 12:09:04'),
('VCH-TRIP-402', 'Emilio Aguinaldo', 'Commission - Trip #BK-4002 (Intramuros to Ermita)', 320, 0, 'Credit', '2026-02-02 10:15:00', '2026-03-10', 'pending', '2026-02-12 12:09:04', '2026-02-12 12:09:04'),
('VCH-TRIP-403', 'Melchora Aquino', 'Commission - Trip #BK-4003 (Hospital to Pharmacy)', 150, 0, 'Cash', '2026-02-03 11:20:00', '2026-02-25', 'pending', '2026-02-12 12:09:04', '2026-02-12 12:09:04'),
('VCH-TRIP-404', 'Marcelo H. del Pilar', 'Commission - Trip #BK-4004 (Airport to Makati)', 850, 0, 'Credit', '2026-02-04 09:45:00', '2026-03-05', 'pending', '2026-02-12 12:09:04', '2026-02-12 12:09:04'),
('VCH-TRIP-405', 'Gabriela Silang', 'Commission - Trip #BK-4005 (QC to Pasig)', 520, 0, 'Cash', '2026-02-05 14:30:00', '2026-02-20', 'pending', '2026-02-12 12:09:04', '2026-02-12 12:09:04'),
('VCH-TRIP-406', 'Antonio Luna', 'Commission - Trip #BK-4006 (Mall to Park)', 280, 0, 'Credit', '2026-02-06 10:50:00', '2026-03-15', 'pending', '2026-02-12 12:09:04', '2026-02-12 12:09:04'),
('VCH-TRIP-407', 'Teresa Magbanua', 'Commission - Trip #BK-4007 (Hospital to Home)', 190, 0, 'Cash', '2026-02-07 13:15:00', '2026-02-22', 'pending', '2026-02-12 12:09:04', '2026-02-12 12:09:04'),
('VCH-TRIP-408', 'GOMBURZA', 'Commission - Trip #BK-4008 (University to Dorm)', 120, 120, 'Credit', '2026-02-08 11:40:00', '2026-03-01', 'paid', '2026-02-12 12:09:04', '2026-02-12 13:50:01'),
('VCH-TRIP-409', 'Lapu-Lapu', 'Commission - Trip #BK-4009 (Airport to Hotel)', 750, 2250, 'Cash', '2026-02-09 15:25:00', '2026-02-28', 'paid', '2026-02-12 12:09:04', '2026-02-12 13:49:09'),
('VCH-TRIP-410', 'Rajah Sulayman', 'Commission - Trip #BK-4010 (Market to Office)', 340, 0, 'Credit', '2026-02-10 09:10:00', '2026-03-05', 'confirmed', '2026-02-12 12:09:04', '2026-02-12 12:09:04');

-- --------------------------------------------------------

--
-- Table structure for table `active_department_tokens`
--

CREATE TABLE `active_department_tokens` (
  `token` varchar(255) DEFAULT NULL,
  `token_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `usage_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_audit_logs`
--

CREATE TABLE `ai_audit_logs` (
  `id` int(11) NOT NULL,
  `request_id` varchar(255) DEFAULT NULL,
  `response_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_system_health`
--

CREATE TABLE `ai_system_health` (
  `id` int(11) NOT NULL,
  `status` enum('Online','Offline','Error') NOT NULL,
  `response_time` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_validation_logs`
--

CREATE TABLE `ai_validation_logs` (
  `id` int(11) NOT NULL,
  `payout_id` varchar(50) NOT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH') NOT NULL,
  `risk_score` int(11) NOT NULL,
  `issues` text DEFAULT NULL,
  `recommendation` varchar(50) DEFAULT NULL,
  `schedule_info` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_validation_logs`
--

INSERT INTO `ai_validation_logs` (`id`, `payout_id`, `risk_level`, `risk_score`, `issues`, `recommendation`, `schedule_info`, `checked_at`) VALUES
(1, 'PO-2026-TEST-001', 'LOW', 15, '[\"No issues detected - transaction appears safe\"]', 'ALLOW_PAYOUT', NULL, '2026-02-01 12:24:08'),
(2, 'PO-2026-TEST-001', 'LOW', 15, '[\"No issues detected - transaction appears safe\"]', 'ALLOW_PAYOUT', NULL, '2026-02-05 05:20:08');

-- --------------------------------------------------------

--
-- Table structure for table `ar`
--

CREATE TABLE `ar` (
  `id` int(11) NOT NULL,
  `receipt_id` varchar(100) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount_received` decimal(10,2) NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `payment_date` date NOT NULL,
  `from_receivable` tinyint(1) DEFAULT 0,
  `invoice_reference` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `collected_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ar`
--

INSERT INTO `ar` (`id`, `receipt_id`, `driver_name`, `description`, `amount_received`, `payment_method`, `payment_date`, `from_receivable`, `invoice_reference`, `created_at`, `status`, `collected_at`) VALUES
(25, 'RCPT-1770875404-9569', 'Lapu-Lapu', 'Commission - Trip #BK-4009 (Airport to Hotel)', 750.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-409', '2026-02-12 05:49:09', 'pending', '2026-02-12 05:49:09'),
(26, 'RCPT-1770875404-8922', 'GOMBURZA', 'Commission - Trip #BK-4008 (University to Dorm)', 120.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-408', '2026-02-12 05:50:01', 'pending', '2026-02-12 05:50:01'),
(27, 'RCPT-1770883100-7249', 'Juan Dela Cruz', 'Commission - Trip #BK-0408', 450.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-408', '2026-02-12 01:30:15', 'collected', '2026-02-12 02:15:22'),
(28, 'RCPT-1770883100-3856', 'Maria Makiling', 'Commission - Trip #BK-0404', 125.50, 'Cash', '2026-02-12', 1, 'VCH-TRIP-404', '2026-02-12 01:45:30', 'collected', '2026-02-12 02:20:45'),
(29, 'RCPT-1770883100-9124', 'Rizal Mercado', 'Commission - Trip #BK-0403', 890.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-403', '2026-02-12 02:00:12', 'collected', '2026-02-12 02:35:18'),
(30, 'RCPT-1770883100-5673', 'Antonio Luna', 'Commission - Trip #BK-0410', 320.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-410', '2026-02-12 02:15:45', 'collected', '2026-02-12 03:00:30'),
(31, 'RCPT-1770883100-8442', 'Gabriela Silang', 'Commission - Trip #BK-0412', 1500.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-412', '2026-02-12 02:30:22', 'collected', '2026-02-12 03:15:40'),
(32, 'RCPT-1770883100-2198', 'Andres Bonifacio', 'Commission - Trip #BK-0415', 250.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-415', '2026-02-12 02:45:10', 'collected', '2026-02-12 03:30:25'),
(33, 'RCPT-1770883100-6735', 'Melchora Aquino', 'Commission - Trip #BK-0418', 180.75, 'Cash', '2026-02-12', 1, 'VCH-TRIP-418', '2026-02-12 03:00:35', 'collected', '2026-02-12 03:45:50'),
(34, 'RCPT-1770883100-4921', 'Emilio Jacinto', 'Commission - Trip #BK-0420', 540.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-420', '2026-02-12 03:15:20', 'collected', '2026-02-12 04:00:15'),
(35, 'RCPT-1770883100-1587', 'Apolinario Mabini', 'Commission - Trip #BK-0422', 310.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-422', '2026-02-12 03:30:45', 'collected', '2026-02-12 04:15:30'),
(36, 'RCPT-1770883100-3304', 'Marcelo H. del Pilar', 'Commission - Trip #BK-0425', 95.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-425', '2026-02-12 03:45:10', 'collected', '2026-02-12 04:30:20'),
(37, 'RCPT-1770883100-7249', 'Juan Dela Cruz', 'Commission - Trip #BK-0408', 450.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-408', '2026-02-12 01:30:15', 'collected', '2026-02-12 02:15:22'),
(38, 'RCPT-1770883100-3856', 'Maria Makiling', 'Commission - Trip #BK-0404', 125.50, 'Cash', '2026-02-12', 1, 'VCH-TRIP-404', '2026-02-12 01:45:30', 'collected', '2026-02-12 02:20:45'),
(39, 'RCPT-1770883100-9124', 'Rizal Mercado', 'Commission - Trip #BK-0403', 890.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-403', '2026-02-12 02:00:12', 'collected', '2026-02-12 02:35:18'),
(40, 'RCPT-1770883100-5673', 'Antonio Luna', 'Commission - Trip #BK-0410', 320.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-410', '2026-02-12 02:15:45', 'collected', '2026-02-12 03:00:30'),
(41, 'RCPT-1770883100-8442', 'Gabriela Silang', 'Commission - Trip #BK-0412', 1500.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-412', '2026-02-12 02:30:22', 'collected', '2026-02-12 03:15:40'),
(42, 'RCPT-1770883100-2198', 'Andres Bonifacio', 'Commission - Trip #BK-0415', 250.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-415', '2026-02-12 02:45:10', 'collected', '2026-02-12 03:30:25'),
(43, 'RCPT-1770883100-6735', 'Melchora Aquino', 'Commission - Trip #BK-0418', 180.75, 'Cash', '2026-02-12', 1, 'VCH-TRIP-418', '2026-02-12 03:00:35', 'collected', '2026-02-12 03:45:50'),
(44, 'RCPT-1770883100-4921', 'Emilio Jacinto', 'Commission - Trip #BK-0420', 540.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-420', '2026-02-12 03:15:20', 'collected', '2026-02-12 04:00:15'),
(45, 'RCPT-1770883100-1587', 'Apolinario Mabini', 'Commission - Trip #BK-0422', 310.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-422', '2026-02-12 03:30:45', 'collected', '2026-02-12 04:15:30'),
(46, 'RCPT-1770883100-3304', 'Marcelo H. del Pilar', 'Commission - Trip #BK-0425', 95.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-425', '2026-02-12 03:45:10', 'collected', '2026-02-12 04:30:20'),
(47, 'RCPT-1770883150-7841', 'Teresa Magbanua', 'Commission - Trip #BK-0430', 675.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-430', '2026-02-12 05:10:25', 'collected', '2026-02-12 05:45:30'),
(48, 'RCPT-1770883150-2956', 'Diego Silang', 'Commission - Trip #BK-0432', 420.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-432', '2026-02-12 05:25:40', 'collected', '2026-02-12 06:00:15'),
(49, 'RCPT-1770883150-6183', 'Gregorio del Pilar', 'Commission - Trip #BK-0435', 890.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-435', '2026-02-12 05:40:50', 'collected', '2026-02-12 06:15:20'),
(50, 'RCPT-1770883150-4527', 'Macario Sakay', 'Commission - Trip #BK-0438', 275.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-438', '2026-02-12 05:55:30', 'collected', '2026-02-12 06:30:45'),
(51, 'RCPT-1770883150-9162', 'Sultan Kudarat', 'Commission - Trip #BK-0440', 560.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-440', '2026-02-12 06:10:15', 'collected', '2026-02-12 06:45:25'),
(52, 'RCPT-1770883150-3748', 'Rajah Humabon', 'Commission - Trip #BK-0442', 190.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-442', '2026-02-12 06:25:40', 'collected', '2026-02-12 07:00:10'),
(53, 'RCPT-1770883150-8291', 'Francisco Dagohoy', 'Commission - Trip #BK-0445', 725.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-445', '2026-02-12 06:40:20', 'collected', '2026-02-12 07:15:35'),
(54, 'RCPT-1770883150-5614', 'Hermano Puli', 'Commission - Trip #BK-0448', 380.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-448', '2026-02-12 06:55:50', 'collected', '2026-02-12 07:30:15'),
(55, 'RCPT-1770883150-1927', 'Juan Luna', 'Commission - Trip #BK-0450', 950.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-450', '2026-02-12 07:10:30', 'collected', '2026-02-12 07:45:40'),
(56, 'RCPT-1770883150-7365', 'Felix Resurreccion Hidalgo', 'Commission - Trip #BK-0452', 215.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-452', '2026-02-12 07:25:15', 'collected', '2026-02-12 08:00:20'),
(57, 'RCPT-1770883200-4182', 'Graciano Lopez Jaena', 'Commission - Trip #BK-0455', 635.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-455', '2026-02-12 07:40:45', 'collected', '2026-02-12 08:15:50'),
(58, 'RCPT-1770883200-9536', 'Marcelo H. del Pilar', 'Commission - Trip #BK-0458', 410.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-458', '2026-02-12 07:55:25', 'collected', '2026-02-12 08:30:10'),
(59, 'RCPT-1770883200-2874', 'Mariano Ponce', 'Commission - Trip #BK-0460', 780.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-460', '2026-02-12 08:10:35', 'collected', '2026-02-12 08:45:30'),
(60, 'RCPT-1770883200-6419', 'Trinidad Tecson', 'Commission - Trip #BK-0462', 325.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-462', '2026-02-12 08:25:50', 'collected', '2026-02-12 09:00:15'),
(61, 'RCPT-1770883200-1753', 'Agueda Kahabagan', 'Commission - Trip #BK-0465', 870.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-465', '2026-02-12 08:40:20', 'collected', '2026-02-12 09:15:45'),
(62, 'RCPT-1770883200-8296', 'Tandang Sora', 'Commission - Trip #BK-0468', 245.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-468', '2026-02-12 08:55:40', 'collected', '2026-02-12 09:30:25'),
(63, 'RCPT-1770883200-5621', 'Josefa Llanes Escoda', 'Commission - Trip #BK-0470', 590.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-470', '2026-02-12 09:10:15', 'collected', '2026-02-12 09:45:30'),
(64, 'RCPT-1770883200-3184', 'Manuel L. Quezon', 'Commission - Trip #BK-0472', 1200.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-472', '2026-02-12 09:25:45', 'collected', '2026-02-12 10:00:50'),
(65, 'RCPT-1770883200-7492', 'Sergio Osmena', 'Commission - Trip #BK-0475', 465.00, 'Cash', '2026-02-12', 1, 'VCH-TRIP-475', '2026-02-12 09:40:30', 'collected', '2026-02-12 10:15:20'),
(66, 'RCPT-1770883200-2658', 'Manuel Dela Cruz', 'Commission - Trip #BK-0478', 820.00, 'Credit', '2026-02-12', 1, 'VCH-TRIP-478', '2026-02-12 09:55:10', 'collected', '2026-02-12 10:30:40'),
(67, 'RCPT-1770883250-9137', 'Elpidio Quirino', 'Commission - Trip #BK-0480', 355.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-480', '2026-02-11 01:15:25', 'collected', '2026-02-11 01:50:30'),
(68, 'RCPT-1770883250-4765', 'Ramon Magsaysay', 'Commission - Trip #BK-0482', 695.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-482', '2026-02-11 01:30:40', 'collected', '2026-02-11 02:05:15'),
(69, 'RCPT-1770883250-8214', 'Carlos P. Garcia', 'Commission - Trip #BK-0485', 515.00, 'Credit', '2026-02-11', 1, 'VCH-TRIP-485', '2026-02-11 01:45:50', 'collected', '2026-02-11 02:20:35'),
(70, 'RCPT-1770883250-1926', 'Diosdado Macapagal', 'Commission - Trip #BK-0488', 930.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-488', '2026-02-11 02:00:20', 'collected', '2026-02-11 02:35:45'),
(71, 'RCPT-1770883250-6583', 'Ferdinand Marcos', 'Commission - Trip #BK-0490', 285.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-490', '2026-02-11 02:15:35', 'collected', '2026-02-11 02:50:20'),
(72, 'RCPT-1770883250-3147', 'Corazon Aquino', 'Commission - Trip #BK-0492', 750.00, 'Credit', '2026-02-11', 1, 'VCH-TRIP-492', '2026-02-11 02:30:50', 'collected', '2026-02-11 03:05:30'),
(73, 'RCPT-1770883250-7829', 'Fidel V. Ramos', 'Commission - Trip #BK-0495', 425.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-495', '2026-02-11 02:45:15', 'collected', '2026-02-11 03:20:40'),
(74, 'RCPT-1770883250-5461', 'Joseph Estrada', 'Commission - Trip #BK-0498', 880.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-498', '2026-02-11 03:00:30', 'collected', '2026-02-11 03:35:15'),
(75, 'RCPT-1770883250-2938', 'Gloria Macapagal-Arroyo', 'Commission - Trip #BK-0500', 340.00, 'Credit', '2026-02-11', 1, 'VCH-TRIP-500', '2026-02-11 03:15:45', 'collected', '2026-02-11 03:50:25'),
(76, 'RCPT-1770883250-9614', 'Benigno Aquino III', 'Commission - Trip #BK-0502', 615.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-502', '2026-02-11 03:30:20', 'collected', '2026-02-11 04:05:35'),
(77, 'RCPT-1770883300-4287', 'Rodrigo Duterte', 'Commission - Trip #BK-0505', 1050.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-505', '2026-02-11 03:45:35', 'collected', '2026-02-11 04:20:50'),
(78, 'RCPT-1770883300-7935', 'Bongbong Marcos', 'Commission - Trip #BK-0508', 475.00, 'Credit', '2026-02-11', 1, 'VCH-TRIP-508', '2026-02-11 04:00:50', 'collected', '2026-02-11 04:35:15'),
(79, 'RCPT-1770883300-1562', 'Sara Duterte', 'Commission - Trip #BK-0510', 795.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-510', '2026-02-11 04:15:25', 'collected', '2026-02-11 04:50:40'),
(80, 'RCPT-1770883300-8174', 'Leni Robredo', 'Commission - Trip #BK-0512', 385.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-512', '2026-02-11 04:30:40', 'collected', '2026-02-11 05:05:20'),
(81, 'RCPT-1770883300-6429', 'Isko Moreno', 'Commission - Trip #BK-0515', 920.00, 'Credit', '2026-02-11', 1, 'VCH-TRIP-515', '2026-02-11 04:45:15', 'collected', '2026-02-11 05:20:35'),
(82, 'RCPT-1770883300-2851', 'Manny Pacquiao', 'Commission - Trip #BK-0518', 265.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-518', '2026-02-11 05:00:30', 'collected', '2026-02-11 05:35:45'),
(83, 'RCPT-1770883300-9736', 'Ping Lacson', 'Commission - Trip #BK-0520', 680.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-520', '2026-02-11 05:15:45', 'collected', '2026-02-11 05:50:20'),
(84, 'RCPT-1770883300-5193', 'Grace Poe', 'Commission - Trip #BK-0522', 540.00, 'Credit', '2026-02-11', 1, 'VCH-TRIP-522', '2026-02-11 05:30:20', 'collected', '2026-02-11 06:05:30'),
(85, 'RCPT-1770883300-3647', 'Chiz Escudero', 'Commission - Trip #BK-0525', 1150.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-525', '2026-02-11 05:45:35', 'collected', '2026-02-11 06:20:15'),
(86, 'RCPT-1770883300-8025', 'Alan Peter Cayetano', 'Commission - Trip #BK-0528', 445.00, 'Cash', '2026-02-11', 1, 'VCH-TRIP-528', '2026-02-11 06:00:50', 'collected', '2026-02-11 06:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `archive`
--

CREATE TABLE `archive` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(20) NOT NULL,
  `account_name` varchar(15) NOT NULL,
  `requested_department` varchar(30) NOT NULL,
  `mode_of_payment` varchar(24) NOT NULL,
  `expense_categories` varchar(25) NOT NULL,
  `amount` bigint(11) NOT NULL,
  `description` text NOT NULL,
  `document` varchar(25) NOT NULL,
  `time_period` varchar(25) NOT NULL,
  `payment_due` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bank_name` varchar(24) NOT NULL,
  `bank_account_name` varchar(255) NOT NULL,
  `bank_account_number` varchar(20) NOT NULL,
  `ecash_provider` varchar(100) NOT NULL,
  `ecash_account_name` varchar(255) NOT NULL,
  `ecash_account_number` varchar(20) NOT NULL,
  `rejected_reason` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive`
--

INSERT INTO `archive` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `time_period`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `rejected_reason`) VALUES
(36, 'BR-5628-2025', 'test', 'Admininistrative', 'Cash', 'Facility Cost', 50000, 'facility expense', '', 'Monthly', '2025-09-15 16:00:00', '', '', '', '', '', '', 'no document'),
(39, 'BR-2583-2025', 'test', 'Financial', 'Cash', 'Tax Payment', 2000, 'tax ', '', 'Monthly', '2025-08-17 16:00:00', '', '', '', '', '', '', 'no document'),
(40, 'BR-2728-2025', 'hr', 'Human Resource-2', 'Cash', 'Training Cost', 5000, 'training', '', 'Quarterly', '2025-08-18 00:00:00', '', '', '', '', '', '', 'no document'),
(41, 'INV-20251015-9191', 'admin admin', 'Human Resource-4', 'Cash', '', 100, 'test', '', '', '2025-10-26 00:00:00', '', '', '', '', '', '', ''),
(36, 'BR-5628-2025', 'test', 'Admininistrative', 'Cash', 'Facility Cost', 50000, 'facility expense', '', 'Monthly', '2025-09-15 16:00:00', '', '', '', '', '', '', 'no document'),
(39, 'BR-2583-2025', 'test', 'Financial', 'Cash', 'Tax Payment', 2000, 'tax ', '', 'Monthly', '2025-08-17 16:00:00', '', '', '', '', '', '', 'no document'),
(40, 'BR-2728-2025', 'hr', 'Human Resource-2', 'Cash', 'Training Cost', 5000, 'training', '', 'Quarterly', '2025-08-18 00:00:00', '', '', '', '', '', '', 'no document'),
(41, 'INV-20251015-9191', 'admin admin', 'Human Resource-4', 'Cash', '', 100, 'test', '', '', '2025-10-26 00:00:00', '', '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `archive_payables`
--

CREATE TABLE `archive_payables` (
  `id` int(11) NOT NULL,
  `invoice_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `payment_due` date DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ecash_provider` varchar(100) DEFAULT NULL,
  `ecash_account_name` varchar(255) DEFAULT NULL,
  `ecash_account_number` varchar(50) DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `vendor_type` enum('Vendor','Supplier') DEFAULT 'Vendor',
  `vendor_address` text DEFAULT NULL,
  `gl_account` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `archive_payables`
--

INSERT INTO `archive_payables` (`id`, `invoice_id`, `department`, `vendor_name`, `payment_method`, `amount`, `description`, `document`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `rejected_reason`, `created_at`, `vendor_type`, `vendor_address`, `gl_account`, `invoice_date`) VALUES
(1, 'IN-20250904-4699', 'Financial', 'budget manager', 'Cash', 500.00, 'test', '', '2025-09-17', '', '', '', '', '', '', 'No document file submitted', '2026-01-31 08:11:27', 'Vendor', NULL, NULL, NULL),
(2, 'IN-20250904-4699', 'Financial', 'budget manager', 'Cash', 500.00, 'test', '', '2025-09-17', '', '', '', '', '', '', 'No document file submitted', '2026-01-31 08:11:27', 'Vendor', NULL, NULL, NULL),
(3, 'INV-20251015-6685', 'Financial', 'admin admin', 'Cash', 86000.00, 'buy a motorcycle', '', '2025-10-31', '', '', '', '', '', '', 'no submitted document', '2026-01-31 08:11:39', 'Vendor', NULL, NULL, NULL),
(4, 'INV-20251015-6685', 'Financial', 'admin admin', 'Cash', 86000.00, 'buy a motorcycle', '', '2025-10-31', '', '', '', '', '', '', 'no submitted document', '2026-01-31 08:11:39', 'Vendor', NULL, NULL, NULL),
(5, 'INV-20251015-8678', 'Core-2', 'Ethan Magsaysay', 'Cash', 478.00, 'Provisions', '', '2025-06-11', '', '', '', '', '', '', 'Insufficient budget allocation. Budget shortage: ₱448.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(6, 'INV-20251015-8678', 'Core-2', 'Ethan Magsaysay', 'Cash', 478.00, 'Provisions', '', '2025-06-11', '', '', '', '', '', '', 'Insufficient budget allocation. Budget shortage: ₱448.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(7, 'INV-20251015-8678', 'Core-2', 'Ethan Magsaysay', 'Cash', 478.00, 'Provisions', '', '2025-06-11', '', '', '', '', '', '', 'Insufficient budget allocation. Budget shortage: ₱448.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(8, 'INV-20251015-2630', 'Logistic-1', 'admin admin', 'Bank Transfer', 2224444.00, 'test run', '', '2025-12-19', 'test run', 'test run', '11222', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱8,000.00. Invoice Amount: ₱2,224,444.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(9, 'INV-20251015-2630', 'Logistic-1', 'admin admin', 'Bank Transfer', 2224444.00, 'test run', '', '2025-12-19', 'test run', 'test run', '11222', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱8,000.00. Invoice Amount: ₱2,224,444.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(10, 'INV-20251015-2630', 'Logistic-1', 'admin admin', 'Bank Transfer', 2224444.00, 'test run', '', '2025-12-19', 'test run', 'test run', '11222', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱8,000.00. Invoice Amount: ₱2,224,444.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(11, 'INV-20251015-6155', 'Human Resource-1', 'admin admin', 'Cash', 100000.00, 'test run', '', '2025-10-17', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱100,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(12, 'INV-20251015-6155', 'Human Resource-1', 'admin admin', 'Cash', 100000.00, 'test run', '', '2025-10-17', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱100,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(13, 'INV-20251015-6155', 'Human Resource-1', 'admin admin', 'Cash', 100000.00, 'test run', '', '2025-10-17', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱100,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(14, 'INV-20251015-6205', 'Human Resource-1', 'admin admin', 'Cash', 233399.00, 'test run', '1760527795_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '2025-10-16', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱233,399.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(15, 'INV-20251015-6205', 'Human Resource-1', 'admin admin', 'Cash', 233399.00, 'test run', '1760527795_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '2025-10-16', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱233,399.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(16, 'INV-20251015-6205', 'Human Resource-1', 'admin admin', 'Cash', 233399.00, 'test run', '1760527795_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '2025-10-16', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱233,399.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(17, 'INV-20251015-4198', 'Human Resource-2', 'admin admin', 'Cash', 29999000.00, 'test run', '', '2025-10-17', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱5,200.00. Invoice Amount: ₱29,999,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(18, 'INV-20251015-4198', 'Human Resource-2', 'admin admin', 'Cash', 29999000.00, 'test run', '', '2025-10-17', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱5,200.00. Invoice Amount: ₱29,999,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(19, 'INV-20251015-4198', 'Human Resource-2', 'admin admin', 'Cash', 29999000.00, 'test run', '', '2025-10-17', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱5,200.00. Invoice Amount: ₱29,999,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(20, 'INV-20251015-4746', 'Administrative', 'admin admin', 'Bank Transfer', 222222.00, 'test run', '', '2025-10-13', 'test run', 'test run', '200000', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱10,700.00. Invoice Amount: ₱222,222.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(21, 'INV-20251015-4746', 'Administrative', 'admin admin', 'Bank Transfer', 222222.00, 'test run', '', '2025-10-13', 'test run', 'test run', '200000', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱10,700.00. Invoice Amount: ₱222,222.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(22, 'INV-20251015-4746', 'Administrative', 'admin admin', 'Bank Transfer', 222222.00, 'test run', '', '2025-10-13', 'test run', 'test run', '200000', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱10,700.00. Invoice Amount: ₱222,222.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(23, 'INV-20251015-2706', 'Human Resource-3', 'admin admin', 'Bank Transfer', 40000.00, 'bayrin', '', '2025-12-23', 'test run', 'test run', '2667889', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱32,000.00. Invoice Amount: ₱40,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(24, 'INV-20251015-2706', 'Human Resource-3', 'admin admin', 'Bank Transfer', 40000.00, 'bayrin', '', '2025-12-23', 'test run', 'test run', '2667889', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱32,000.00. Invoice Amount: ₱40,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(25, 'INV-20251015-2706', 'Human Resource-3', 'admin admin', 'Bank Transfer', 40000.00, 'bayrin', '', '2025-12-23', 'test run', 'test run', '2667889', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱32,000.00. Invoice Amount: ₱40,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(26, 'INV-20251015-3587', 'Core-2', 'admin admin', 'Cash', 500000.00, 'test run', '', '2025-10-16', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱30.00. Invoice Amount: ₱500,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(27, 'INV-20251015-3587', 'Core-2', 'admin admin', 'Cash', 500000.00, 'test run', '', '2025-10-16', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱30.00. Invoice Amount: ₱500,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(28, 'INV-20251015-3587', 'Core-2', 'admin admin', 'Cash', 500000.00, 'test run', '', '2025-10-16', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱30.00. Invoice Amount: ₱500,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(29, 'INV-20251015-1872', 'Human Resource-1', 'admin admin', 'Bank Transfer', 500000.00, 'testing', '', '2025-10-10', 'testing', 'testing', '2067443', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱500,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(30, 'INV-20251015-1872', 'Human Resource-1', 'admin admin', 'Bank Transfer', 500000.00, 'testing', '', '2025-10-10', 'testing', 'testing', '2067443', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱500,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(31, 'INV-20251015-1872', 'Human Resource-1', 'admin admin', 'Bank Transfer', 500000.00, 'testing', '', '2025-10-10', 'testing', 'testing', '2067443', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱62,500.00. Invoice Amount: ₱500,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(32, 'INV-20251015-1398', 'Administrative', 'admin admin', 'Bank Transfer', 400000.00, 'testing', '1760528988_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '2025-10-18', 'testing', 'testing', '223678', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱10,700.00. Invoice Amount: ₱400,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(33, 'INV-20251015-1398', 'Administrative', 'admin admin', 'Bank Transfer', 400000.00, 'testing', '1760528988_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '2025-10-18', 'testing', 'testing', '223678', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱10,700.00. Invoice Amount: ₱400,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(34, 'INV-20251015-1398', 'Administrative', 'admin admin', 'Bank Transfer', 400000.00, 'testing', '1760528988_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '2025-10-18', 'testing', 'testing', '223678', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱10,700.00. Invoice Amount: ₱400,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(35, 'INV-20251015-8202', 'Human Resource-3', 'admin admin', 'Bank Transfer', 700000.00, 'testing', '', '2025-10-31', 'testing', 'testing', '11212', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱32,000.00. Invoice Amount: ₱700,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(36, 'INV-20251015-8202', 'Human Resource-3', 'admin admin', 'Bank Transfer', 700000.00, 'testing', '', '2025-10-31', 'testing', 'testing', '11212', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱32,000.00. Invoice Amount: ₱700,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(37, 'INV-20251015-8202', 'Human Resource-3', 'admin admin', 'Bank Transfer', 700000.00, 'testing', '', '2025-10-31', 'testing', 'testing', '11212', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱32,000.00. Invoice Amount: ₱700,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(38, 'INV-20251015-7336', 'Logistic-2', 'admin admin', 'Cash', 13000.00, 'freight delivery services', '', '2025-10-15', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱2,645.00. Invoice Amount: ₱13,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(39, 'INV-20251015-7336', 'Logistic-2', 'admin admin', 'Cash', 13000.00, 'freight delivery services', '', '2025-10-15', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱2,645.00. Invoice Amount: ₱13,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(40, 'INV-20251015-7336', 'Logistic-2', 'admin admin', 'Cash', 13000.00, 'freight delivery services', '', '2025-10-15', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱2,645.00. Invoice Amount: ₱13,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(41, '202601318927', 'Core-1', 'Maria', 'Cash', 65462.00, 'vehicle maintenance', '[\"1769849218_reimbursement_report_2026-01-31T06-50-24.pdf\",\"1769849218_disbursed-records.pdf\"]', '2026-02-28', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱20,282.00. Invoice Amount: ₱65,462.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(42, '202601318927', 'Core-1', 'Maria', 'Cash', 65462.00, 'vehicle maintenance', '[\"1769849220_reimbursement_report_2026-01-31T06-50-24.pdf\",\"1769849220_disbursed-records.pdf\"]', '2026-02-28', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱20,282.00. Invoice Amount: ₱65,462.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(43, '202601318927', 'Core-1', 'Maria', 'Cash', 65462.00, 'vehicle maintenance', '[\"1769849255_reimbursement_report_2026-01-31T06-50-24.pdf\",\"1769849255_disbursed-records.pdf\"]', '2026-02-28', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱20,282.00. Invoice Amount: ₱65,462.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(44, '202601316448', 'Core-1', 'Maria', 'Cash', 54651.00, 'vehicle maintenance', '[\"1769849487_reimbursement_report_2026-01-31T06-50-59.pdf\",\"1769849487_payables_receipts_disbursed.pdf\"]', '2026-02-28', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱20,282.00. Invoice Amount: ₱54,651.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(45, '202601311794', 'Logistic-2', 'Juan Alfonso', 'Cash', 10000.00, 'tire replacement', '[\"1769849985_reimbursement_report_2026-01-31T06-50-59.pdf\",\"1769849985_payables_receipts_disbursed.pdf\"]', '2026-02-28', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱2,645.00. Invoice Amount: ₱10,000.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(46, 'INV-20251015-8654', 'Human Resource-1', 'admin admin', 'Cash', 8000.00, 'hr compliance training', '', '2025-10-15', '', '', '', '', '', '', 'basta', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(47, 'INV-20251015-8654', 'Human Resource-1', 'admin admin', 'Cash', 8000.00, 'hr compliance training', '', '2025-10-15', '', '', '', '', '', '', 'basta', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(48, 'INV-20251015-8654', 'Human Resource-1', 'admin admin', 'Cash', 8000.00, 'hr compliance training', '', '2025-10-15', '', '', '', '', '', '', 'basta', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(49, '202601317951', 'Core-1', 'Jose Rizal', 'Cash', 51023.00, '0', '[\"1769850407_reimbursement_report_2026-01-31T06-50-24.pdf\"]', '2026-03-27', '', '', '', '', '', '', 'System Auto-Reject: Insufficient budget allocation. Remaining: ₱20,282.00. Invoice Amount: ₱51,023.00', '2026-01-31 09:11:25', 'Vendor', NULL, NULL, NULL),
(50, 'INV-20251015-6702', 'Core-1', 'admin admin', 'Cash', 6500.00, ' metroprint for forms', '', '2025-10-15', '', '', '', '', '', '', 'reject', '2026-01-31 09:12:50', 'Vendor', NULL, NULL, NULL),
(51, 'INV-20251015-6702', 'Core-1', 'admin admin', 'Cash', 6500.00, ' metroprint for forms', '', '2025-10-15', '', '', '', '', '', '', 'reject', '2026-01-31 09:12:50', 'Vendor', NULL, NULL, NULL),
(52, 'INV-20251015-6702', 'Core-1', 'admin admin', 'Cash', 6500.00, ' metroprint for forms', '', '2025-10-15', '', '', '', '', '', '', 'reject', '2026-01-31 09:12:50', 'Vendor', NULL, NULL, NULL),
(53, 'INV-20251015-6079', 'Human Resource-4', 'admin admin', 'Cash', 115.00, 'service fee request from hr4', '', '2025-10-15', '', '', '', '', '', '', 'reject', '2026-01-31 09:13:25', 'Vendor', NULL, NULL, NULL),
(54, 'INV-20251015-6079', 'Human Resource-4', 'admin admin', 'Cash', 115.00, 'service fee request from hr4', '', '2025-10-15', '', '', '', '', '', '', 'reject', '2026-01-31 09:13:25', 'Vendor', NULL, NULL, NULL),
(55, 'INV-20251015-6079', 'Human Resource-4', 'admin admin', 'Cash', 115.00, 'service fee request from hr4', '', '2025-10-15', '', '', '', '', '', '', 'reject', '2026-01-31 09:13:25', 'Vendor', NULL, NULL, NULL),
(56, 'INV-20251015-5087', 'Administrative', 'admin admin', 'Cash', 500.00, 'stationery', '', '2025-11-01', '', '', '', '', '', '', 'reject', '2026-01-31 09:13:25', 'Vendor', NULL, NULL, NULL),
(57, 'INV-20251015-5087', 'Administrative', 'admin admin', 'Cash', 500.00, 'stationery', '', '2025-11-01', '', '', '', '', '', '', 'reject', '2026-01-31 09:13:25', 'Vendor', NULL, NULL, NULL),
(58, 'INV-20251015-5087', 'Administrative', 'admin admin', 'Cash', 500.00, 'stationery', '', '2025-11-01', '', '', '', '', '', '', 'reject', '2026-01-31 09:13:25', 'Vendor', NULL, NULL, NULL),
(59, 'INV-20250907-7056', 'Administrative', 'admin admin', 'Cash', 123.00, 'asd', '1757273458_receivables_records1.xlsx', '2025-09-17', '', '', '', '', '', '', 'test', '2026-01-31 14:58:28', 'Vendor', NULL, NULL, NULL),
(60, 'INV-20250907-7056', 'Administrative', 'admin admin', 'Cash', 123.00, 'asd', '1757273458_receivables_records1.xlsx', '2025-09-17', '', '', '', '', '', '', 'test', '2026-01-31 14:58:34', 'Vendor', NULL, NULL, NULL),
(61, 'INV-20250907-7056', 'Administrative', 'admin admin', 'Cash', 123.00, 'asd', '1757273458_receivables_records1.xlsx', '2025-09-17', '', '', '', '', '', '', 'test', '2026-01-31 14:58:38', 'Vendor', NULL, NULL, NULL),
(62, '202601311683', 'Financials', 'Jula', 'Cash', 7000.00, 'tax payment', '[\"1769851427_payables_receipts_disbursed.pdf\",\"1769851427_reimbursement_report_2026-01-31T06-50-59.pdf\"]', '2026-02-28', '', '', '', '', '', '', 'invalid', '2026-02-01 09:44:06', 'Vendor', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `archive_pettycash`
--

CREATE TABLE `archive_pettycash` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(50) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `requested_department` varchar(100) NOT NULL,
  `mode_of_payment` varchar(50) NOT NULL,
  `expense_categories` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `time_period` varchar(20) DEFAULT NULL,
  `payment_due` date DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ecash_provider` varchar(50) DEFAULT NULL,
  `ecash_account_name` varchar(100) DEFAULT NULL,
  `ecash_account_number` varchar(50) DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive_pettycash`
--

INSERT INTO `archive_pettycash` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `time_period`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `rejected_reason`, `created_at`) VALUES
(1, 'PC-20250901-6094', 'test', 'Human Resource-2', 'bank', 'test', 30.00, 'test', '', '', '2025-09-01', 'test', 'test', '12345678910', '', '', '', 'no file', '2025-10-04 05:28:47'),
(1, 'PC-20250901-6094', 'test', 'Human Resource-2', 'bank', 'test', 30.00, 'test', '', '', '2025-09-01', 'test', 'test', '12345678910', '', '', '', 'no file', '2025-10-04 05:28:47');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user` varchar(255) NOT NULL,
  `action` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user`, `action`, `created_at`) VALUES
(1, 'Supremo360', 'Viewed Audit Logs', '2026-02-09 05:12:29'),
(2, 'Supremo360', 'Logged out from system', '2026-02-09 05:12:39'),
(3, 'Supremo360', 'Logged into system', '2026-02-09 05:13:22'),
(4, 'Supremo360', 'Viewed Audit Logs', '2026-02-09 05:13:28'),
(5, 'Supremo360', 'Logged into system', '2026-02-10 02:32:14'),
(6, 'Supremo360', 'Logged into system', '2026-02-10 02:38:09'),
(7, 'Supremo360', 'Viewed Audit Logs', '2026-02-10 02:38:28'),
(8, 'Supremo360', 'Logged into system', '2026-02-10 02:40:11'),
(9, 'Supremo360', 'Logged into system', '2026-02-10 02:42:14'),
(10, 'Supremo360', 'Logged into system', '2026-02-10 02:43:07'),
(11, 'Supremo360', 'Logged into system', '2026-02-10 10:34:23'),
(12, 'Supremo360', 'Viewed Audit Logs', '2026-02-10 15:38:33'),
(13, 'Supremo360', 'Logged out from system', '2026-02-10 15:55:19'),
(14, 'Supremo360', 'Logged into system', '2026-02-11 01:42:07'),
(15, 'Supremo360', 'Logged into system', '2026-02-11 03:11:38'),
(16, 'Supremo360', 'Viewed Audit Logs', '2026-02-11 08:35:00'),
(17, 'Supremo360', 'Logged into system', '2026-02-11 08:41:05'),
(18, 'Supremo360', 'Logged out from system', '2026-02-11 08:51:43'),
(19, 'JuanD20', 'Logged into system', '2026-02-11 08:52:32'),
(20, 'JuanD20', 'Logged out from system', '2026-02-11 08:54:09'),
(21, 'Supremo360', 'Logged into system', '2026-02-11 08:54:26'),
(22, 'Supremo360', 'Viewed Audit Logs', '2026-02-11 09:41:54'),
(23, 'Supremo360', 'Viewed Audit Logs', '2026-02-11 10:04:07'),
(24, 'Supremo360', 'Logged into system', '2026-02-11 14:20:36'),
(25, 'Supremo360', 'Logged into system', '2026-02-11 14:58:53'),
(26, 'Supremo360', 'Viewed Audit Logs', '2026-02-11 14:59:02'),
(27, 'Supremo360', 'Logged into system', '2026-02-11 18:54:01'),
(28, 'Supremo360', 'Viewed Audit Logs', '2026-02-11 18:56:43'),
(29, 'Supremo360', 'Logged out from system', '2026-02-11 18:58:17'),
(30, 'Supremo360', 'Logged into system', '2026-02-11 18:58:44'),
(31, 'Supremo360', 'Viewed Audit Logs', '2026-02-11 18:58:50'),
(32, 'Supremo360', 'Logged into system', '2026-02-12 00:56:37'),
(33, 'Supremo360', 'Viewed Audit Logs', '2026-02-12 00:59:05'),
(34, 'Supremo360', 'Logged into system', '2026-02-12 01:18:37'),
(35, 'Supremo360', 'Viewed Audit Logs', '2026-02-12 05:52:22'),
(36, 'Supremo360', 'Suspended user account \'JuanD20\'', '2026-02-12 09:50:39'),
(37, 'Supremo360', 'Unsuspended user account \'JuanD20\'', '2026-02-12 09:50:46'),
(38, 'Supremo360', 'Logged into system', '2026-02-12 11:48:03'),
(39, 'Supremo360', 'Logged out from system', '2026-02-12 13:25:28'),
(40, 'Supremo360', 'Logged into system', '2026-02-12 13:29:07'),
(41, 'Supremo360', 'Logged into system', '2026-02-13 02:56:27'),
(42, 'Supremo360', 'Logged into system', '2026-02-13 06:51:19'),
(43, 'Supremo360', 'Logged into system', '2026-02-13 12:31:39'),
(44, 'Supremo360', 'Viewed Audit Logs', '2026-02-13 12:36:36'),
(45, 'Supremo360', 'Logged out from system', '2026-02-13 17:03:51'),
(46, 'Supremo360', 'Logged into system', '2026-02-14 03:58:39'),
(47, 'Supremo360', 'Logged into system', '2026-02-14 08:27:09');

-- --------------------------------------------------------

--
-- Table structure for table `audit_reports`
--

CREATE TABLE `audit_reports` (
  `id` int(11) NOT NULL,
  `report_number` varchar(50) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_period` varchar(50) NOT NULL,
  `audit_team` varchar(255) NOT NULL,
  `site_section` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `generated_date` datetime NOT NULL,
  `updated_date` timestamp NULL DEFAULT NULL,
  `audit_findings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audit_findings`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_reports`
--

INSERT INTO `audit_reports` (`id`, `report_number`, `report_title`, `report_period`, `audit_team`, `site_section`, `start_date`, `end_date`, `generated_date`, `updated_date`, `audit_findings`, `created_at`) VALUES
(1, 'AUD-20250927-4685', '2025 Expense Report', 'H1 2024', 'Financial Audit Team', 'Financial Department', '2025-08-28', '2025-09-27', '2025-09-27 22:36:02', NULL, '[{\"element\":\"Journal Entries\",\"compliance\":\"Unbalanced journal entries detected\",\"corrective_action\":\"Review and correct unbalanced entries to ensure debit = credit\",\"status\":\"pending\"},{\"element\":\"Journal Entries\",\"compliance\":\"Missing transaction types in journal entries\",\"corrective_action\":\"Update journal entries with proper transaction type categorization\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payables account\",\"corrective_action\":\"Investigate and reconcile Accounts Payables account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Receivable account\",\"corrective_action\":\"Investigate and reconcile Accounts Receivable account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Bank account\",\"corrective_action\":\"Investigate and reconcile Bank account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary account\",\"corrective_action\":\"Investigate and reconcile Boundary account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary Fee account\",\"corrective_action\":\"Investigate and reconcile Boundary Fee account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Cash account\",\"corrective_action\":\"Investigate and reconcile Cash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Ecash account\",\"corrective_action\":\"Investigate and reconcile Ecash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Facility Cost account\",\"corrective_action\":\"Investigate and reconcile Facility Cost account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Miscellaneous Expense account\",\"corrective_action\":\"Investigate and reconcile Miscellaneous Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Training Cost account\",\"corrective_action\":\"Investigate and reconcile Training Cost account balance\",\"status\":\"pending\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity\",\"corrective_action\":\"Reconcile balance sheet accounts and identify missing entries\",\"status\":\"pending\"}]', '2025-09-27 14:36:02'),
(2, 'AUD-20250927-6811', '2025 Expense Report', 'Full Year 2024', 'Financial Audit Team', 'Financial Department', '2025-08-28', '2025-08-31', '2025-09-27 22:37:20', NULL, '[{\"element\":\"Journal Entries\",\"compliance\":\"Unbalanced journal entries detected\",\"corrective_action\":\"Review and correct unbalanced entries to ensure debit = credit\",\"status\":\"pending\"},{\"element\":\"Journal Entries\",\"compliance\":\"Missing transaction types in journal entries\",\"corrective_action\":\"Update journal entries with proper transaction type categorization\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payables account\",\"corrective_action\":\"Investigate and reconcile Accounts Payables account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Bank account\",\"corrective_action\":\"Investigate and reconcile Bank account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary Fee account\",\"corrective_action\":\"Investigate and reconcile Boundary Fee account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Cash account\",\"corrective_action\":\"Investigate and reconcile Cash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Miscellaneous Expense account\",\"corrective_action\":\"Investigate and reconcile Miscellaneous Expense account balance\",\"status\":\"pending\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity\",\"corrective_action\":\"Reconcile balance sheet accounts and identify missing entries\",\"status\":\"pending\"}]', '2025-09-27 14:37:20'),
(3, 'AUD-20250927-3728', '2025 Expense Report', 'Custom', 'Financial Audit Team', 'Financial Department', '2025-01-27', '2025-09-27', '2025-09-27 23:42:55', NULL, '[{\"element\":\"Journal Entries\",\"compliance\":\"Unbalanced journal entries detected\",\"corrective_action\":\"Review and correct unbalanced entries to ensure debit = credit\",\"status\":\"pending\"},{\"element\":\"Journal Entries\",\"compliance\":\"Missing transaction types in journal entries\",\"corrective_action\":\"Update journal entries with proper transaction type categorization\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payable account\",\"corrective_action\":\"Investigate and reconcile Accounts Payable account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payables account\",\"corrective_action\":\"Investigate and reconcile Accounts Payables account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Receivable account\",\"corrective_action\":\"Investigate and reconcile Accounts Receivable account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Bank account\",\"corrective_action\":\"Investigate and reconcile Bank account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary account\",\"corrective_action\":\"Investigate and reconcile Boundary account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary Fee account\",\"corrective_action\":\"Investigate and reconcile Boundary Fee account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary Fee Revenue account\",\"corrective_action\":\"Investigate and reconcile Boundary Fee Revenue account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Cash account\",\"corrective_action\":\"Investigate and reconcile Cash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Ecash account\",\"corrective_action\":\"Investigate and reconcile Ecash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Facility Cost account\",\"corrective_action\":\"Investigate and reconcile Facility Cost account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fare Revenue account\",\"corrective_action\":\"Investigate and reconcile Fare Revenue account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fuel Expense account\",\"corrective_action\":\"Investigate and reconcile Fuel Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Maintenance\\/Repair account\",\"corrective_action\":\"Investigate and reconcile Maintenance\\/Repair account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Miscellaneous Expense account\",\"corrective_action\":\"Investigate and reconcile Miscellaneous Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Platform Commission account\",\"corrective_action\":\"Investigate and reconcile Platform Commission account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Training Cost account\",\"corrective_action\":\"Investigate and reconcile Training Cost account balance\",\"status\":\"pending\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity\",\"corrective_action\":\"Reconcile balance sheet accounts and identify missing entries\",\"status\":\"pending\"}]', '2025-09-27 15:42:55'),
(4, 'AUD-20250927-9878', 'Q9 2025 Expense Report', 'Custom', 'Financial Audit Team', 'Financial Department', '2025-09-01', '2025-09-27', '2025-09-27 23:44:30', NULL, '[{\"element\":\"Journal Entries\",\"compliance\":\"Unbalanced journal entries detected\",\"corrective_action\":\"Review and correct unbalanced entries to ensure debit = credit\",\"status\":\"pending\"},{\"element\":\"Journal Entries\",\"compliance\":\"Missing transaction types in journal entries\",\"corrective_action\":\"Update journal entries with proper transaction type categorization\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payables account\",\"corrective_action\":\"Investigate and reconcile Accounts Payables account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Receivable account\",\"corrective_action\":\"Investigate and reconcile Accounts Receivable account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Bank account\",\"corrective_action\":\"Investigate and reconcile Bank account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary account\",\"corrective_action\":\"Investigate and reconcile Boundary account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Cash account\",\"corrective_action\":\"Investigate and reconcile Cash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Facility Cost account\",\"corrective_action\":\"Investigate and reconcile Facility Cost account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Miscellaneous Expense account\",\"corrective_action\":\"Investigate and reconcile Miscellaneous Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Training Cost account\",\"corrective_action\":\"Investigate and reconcile Training Cost account balance\",\"status\":\"pending\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity\",\"corrective_action\":\"Reconcile balance sheet accounts and identify missing entries\",\"status\":\"pending\"}]', '2025-09-27 15:44:30'),
(5, 'AUD-20251016-7536', 'October 2025 Audit Report', 'Custom', 'Financial Audit Team', 'Financial Department', '2025-10-01', '2025-10-31', '2025-10-16 08:05:19', NULL, '[{\"element\":\"Journal Entries\",\"compliance\":\"Unbalanced journal entries detected\",\"corrective_action\":\"Review and correct unbalanced entries to ensure debit = credit\",\"status\":\"pending\"},{\"element\":\"Journal Entries\",\"compliance\":\"Missing transaction types in journal entries\",\"corrective_action\":\"Update journal entries with proper transaction type categorization\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payable account\",\"corrective_action\":\"Investigate and reconcile Accounts Payable account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary Fee Revenue account\",\"corrective_action\":\"Investigate and reconcile Boundary Fee Revenue account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Cash account\",\"corrective_action\":\"Investigate and reconcile Cash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in eCash account\",\"corrective_action\":\"Investigate and reconcile eCash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Facility Cost account\",\"corrective_action\":\"Investigate and reconcile Facility Cost account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fare Revenue account\",\"corrective_action\":\"Investigate and reconcile Fare Revenue account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fuel Expense account\",\"corrective_action\":\"Investigate and reconcile Fuel Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Maintenance\\/Repair account\",\"corrective_action\":\"Investigate and reconcile Maintenance\\/Repair account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Miscellaneous Expense account\",\"corrective_action\":\"Investigate and reconcile Miscellaneous Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Platform Commission account\",\"corrective_action\":\"Investigate and reconcile Platform Commission account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Training Cost account\",\"corrective_action\":\"Investigate and reconcile Training Cost account balance\",\"status\":\"pending\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity\",\"corrective_action\":\"Reconcile balance sheet accounts and identify missing entries\",\"status\":\"pending\"}]', '2025-10-16 08:05:19'),
(6, 'AUD-20251016-8749', 'October 2025 Audit Report', 'Custom', 'Financial Audit Team', 'Financial Department', '2025-10-01', '2025-10-31', '2025-10-16 08:08:59', NULL, '[{\"element\":\"Journal Entries\",\"compliance\":\"Unbalanced journal entries detected\",\"corrective_action\":\"Review and correct unbalanced entries to ensure debit = credit\",\"status\":\"pending\"},{\"element\":\"Journal Entries\",\"compliance\":\"Missing transaction types in journal entries\",\"corrective_action\":\"Update journal entries with proper transaction type categorization\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payable account\",\"corrective_action\":\"Investigate and reconcile Accounts Payable account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary Fee Revenue account\",\"corrective_action\":\"Investigate and reconcile Boundary Fee Revenue account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Cash account\",\"corrective_action\":\"Investigate and reconcile Cash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in eCash account\",\"corrective_action\":\"Investigate and reconcile eCash account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Facility Cost account\",\"corrective_action\":\"Investigate and reconcile Facility Cost account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fare Revenue account\",\"corrective_action\":\"Investigate and reconcile Fare Revenue account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fuel Expense account\",\"corrective_action\":\"Investigate and reconcile Fuel Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Maintenance\\/Repair account\",\"corrective_action\":\"Investigate and reconcile Maintenance\\/Repair account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Miscellaneous Expense account\",\"corrective_action\":\"Investigate and reconcile Miscellaneous Expense account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Platform Commission account\",\"corrective_action\":\"Investigate and reconcile Platform Commission account balance\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Training Cost account\",\"corrective_action\":\"Investigate and reconcile Training Cost account balance\",\"status\":\"pending\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity\",\"corrective_action\":\"Reconcile balance sheet accounts and identify missing entries\",\"status\":\"pending\"}]', '2025-10-16 08:08:59'),
(7, 'AUD-20251016-7485', 'October 2025 Audit Report', 'Custom', 'Financial Audit Team', 'Financial Department', '2025-10-01', '2025-10-31', '2025-10-16 08:09:18', '2026-02-11 08:45:25', '[{\"element\":\"Journal Entries\",\"compliance\":\"Unbalanced journal entries detected\",\"corrective_action\":\"Review and correct unbalanced entries to ensure debit = credit\",\"status\":\"done\"},{\"element\":\"Journal Entries\",\"compliance\":\"Missing transaction types in journal entries\",\"corrective_action\":\"Update journal entries with proper transaction type categorization\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Accounts Payable account\",\"corrective_action\":\"Investigate and reconcile Accounts Payable account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Boundary Fee Revenue account\",\"corrective_action\":\"Investigate and reconcile Boundary Fee Revenue account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Cash account\",\"corrective_action\":\"Investigate and reconcile Cash account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in eCash account\",\"corrective_action\":\"Investigate and reconcile eCash account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Facility Cost account\",\"corrective_action\":\"Investigate and reconcile Facility Cost account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fare Revenue account\",\"corrective_action\":\"Investigate and reconcile Fare Revenue account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Fuel Expense account\",\"corrective_action\":\"Investigate and reconcile Fuel Expense account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Maintenance\\/Repair account\",\"corrective_action\":\"Investigate and reconcile Maintenance\\/Repair account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Miscellaneous Expense account\",\"corrective_action\":\"Investigate and reconcile Miscellaneous Expense account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Platform Commission account\",\"corrective_action\":\"Investigate and reconcile Platform Commission account balance\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"Significant balance difference in Training Cost account\",\"corrective_action\":\"Investigate and reconcile Training Cost account balance\",\"status\":\"done\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity\",\"corrective_action\":\"Reconcile balance sheet accounts and identify missing entries\",\"status\":\"done\"}]', '2025-10-16 08:09:18'),
(8, 'AUD-20260211-4208', 'Q1 2026 Audit Report', 'Q1 2026', 'Financial Audit Team', 'Financial Department', '2026-01-01', '2026-03-31', '2026-02-11 14:11:13', '2026-02-13 11:20:47', '[{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Cash on Hand\",\"corrective_action\":\"Audit detail of Cash on Hand to ensure all entries are valid business expenses.\",\"status\":\"on_process\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Accounts Payable - Suppliers\",\"corrective_action\":\"Audit detail of Accounts Payable - Suppliers to ensure all entries are valid business expenses.\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Driver Wallet Payable\",\"corrective_action\":\"Audit detail of Driver Wallet Payable to ensure all entries are valid business expenses.\",\"status\":\"done\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Driver Earnings Payable\",\"corrective_action\":\"Audit detail of Driver Earnings Payable to ensure all entries are valid business expenses.\",\"status\":\"done\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity (Equation Failure)\",\"corrective_action\":\"Check retained earnings calculation and ensure all accounts are mapped correctly in hierarchy.\",\"status\":\"done\"}]', '2026-02-11 06:11:13'),
(16, 'AUD-20260213-7435', '2026 Audit Report', 'Q1 2026', 'Audit Team', 'Finance HQ', '2026-01-14', '2026-02-13', '2026-02-13 19:22:28', NULL, '[{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Cash on Hand\",\"corrective_action\":\"Audit detail of Cash on Hand to ensure all entries are valid business expenses.\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Accounts Receivable - Drivers\",\"corrective_action\":\"Audit detail of Accounts Receivable - Drivers to ensure all entries are valid business expenses.\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Platform Commission Revenue\",\"corrective_action\":\"Audit detail of Platform Commission Revenue to ensure all entries are valid business expenses.\",\"status\":\"pending\"},{\"element\":\"Ledger Accounts\",\"compliance\":\"High volume transaction activity detected in Driver Wallet Payable\",\"corrective_action\":\"Audit detail of Driver Wallet Payable to ensure all entries are valid business expenses.\",\"status\":\"pending\"},{\"element\":\"Balance Sheet\",\"compliance\":\"Assets do not equal Liabilities + Equity (Equation Failure)\",\"corrective_action\":\"Check retained earnings calculation and ensure all accounts are mapped correctly in hierarchy.\",\"status\":\"pending\"}]', '2026-02-13 11:22:28');

-- --------------------------------------------------------

--
-- Table structure for table `bank`
--

CREATE TABLE `bank` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(30) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `bank_account_name` varchar(100) NOT NULL,
  `bank_account_number` varchar(20) NOT NULL,
  `payment_due` date NOT NULL,
  `description` text NOT NULL,
  `document` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank`
--

INSERT INTO `bank` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `bank_name`, `bank_account_name`, `bank_account_number`, `payment_due`, `description`, `document`) VALUES
(100, 'BNK-772779', 'bori Cut', 'Financial', 'Bank Transfer', 'Account Payable', 950, '', '', '', '2025-08-22', 'Payment for invoice INV-772779', 0x313735353834343035345f62696c6c2e706466),
(101, 'BNK-993927', 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 200, '', '', '', '2025-08-25', 'Payment for invoice INV-993927', ''),
(102, 'BNK-993928', 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 200, '', '', '', '2025-08-25', 'Payment for invoice INV-993928', ''),
(115, 'BNK-424362', 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 1000, '', '', '', '2025-08-25', 'Payment for invoice INV-424362', ''),
(118, 'BNK-123492', 'lily chan', 'Financial', 'Bank Transfer', 'Account Payable', 3000, 'BDO', 'lily chan', '1234567891011213', '2025-09-22', 'Payment for invoice INV-123492', 0x313735363435383430325f62696c6c2e706466),
(119, 'BNK-123493', 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 12000, 'AUB', 'zoro', '1234567891011213', '2025-09-24', 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466),
(121, 'BNK-123493', 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 1000, 'AUB', 'zoro', '1234567891011213', '2025-09-24', 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466),
(143, 'BNK-123499', 'ussop', 'Core-2', 'Bank Transfer', 'Account Payable', 11000, 'AUB', 'brave warrior', '1234567891011213', '2025-09-02', 'Payment for invoice INV-123499', 0x313735363632333134305f62696c6c2e706466),
(147, 'BNK-INV-20250831-5297', 'test', 'Administrative', 'Bank Transfer', 'Account Payable', 1000, 'AUB', 'test admin', '1234567891011213', '2025-09-01', 'Payment for invoice INV-INV-20250831-5297', 0x313735363632393839385f62696c6c2e706466),
(149, 'BNK-INV-20250831-7780', 'test', 'Human Resource-4', 'Bank Transfer', 'Account Payable', 1000, 'AUB', 'test', '1234567891011213', '2025-09-14', 'Payment for invoice INV-INV-20250831-7780', 0x313735363633303534355f62696c6c2e706466),
(195, 'BNK-INV-20250904-5642', 'budget manager', 'Financial', 'Bank Transfer', 'Account Payable', 500, 'test', 'test', '12345678910', '2025-09-25', 'Payment for invoice INV-INV-20250904-5642', 0x313735363938393335305f62696c6c2e706466),
(219, 'BNK-790', 'test', 'Human Resource-4', 'Bank Transfer', 'Account Payable', 50, 'BDO', 'test', '1234567891011213', '2025-08-31', 'Payment for invoice 518790', ''),
(100, 'BNK-772779', 'bori Cut', 'Financial', 'Bank Transfer', 'Account Payable', 950, '', '', '', '2025-08-22', 'Payment for invoice INV-772779', 0x313735353834343035345f62696c6c2e706466),
(101, 'BNK-993927', 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 200, '', '', '', '2025-08-25', 'Payment for invoice INV-993927', ''),
(102, 'BNK-993928', 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 200, '', '', '', '2025-08-25', 'Payment for invoice INV-993928', ''),
(115, 'BNK-424362', 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 1000, '', '', '', '2025-08-25', 'Payment for invoice INV-424362', ''),
(118, 'BNK-123492', 'lily chan', 'Financial', 'Bank Transfer', 'Account Payable', 3000, 'BDO', 'lily chan', '1234567891011213', '2025-09-22', 'Payment for invoice INV-123492', 0x313735363435383430325f62696c6c2e706466),
(119, 'BNK-123493', 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 12000, 'AUB', 'zoro', '1234567891011213', '2025-09-24', 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466),
(121, 'BNK-123493', 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 1000, 'AUB', 'zoro', '1234567891011213', '2025-09-24', 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466),
(143, 'BNK-123499', 'ussop', 'Core-2', 'Bank Transfer', 'Account Payable', 11000, 'AUB', 'brave warrior', '1234567891011213', '2025-09-02', 'Payment for invoice INV-123499', 0x313735363632333134305f62696c6c2e706466),
(147, 'BNK-INV-20250831-5297', 'test', 'Administrative', 'Bank Transfer', 'Account Payable', 1000, 'AUB', 'test admin', '1234567891011213', '2025-09-01', 'Payment for invoice INV-INV-20250831-5297', 0x313735363632393839385f62696c6c2e706466),
(149, 'BNK-INV-20250831-7780', 'test', 'Human Resource-4', 'Bank Transfer', 'Account Payable', 1000, 'AUB', 'test', '1234567891011213', '2025-09-14', 'Payment for invoice INV-INV-20250831-7780', 0x313735363633303534355f62696c6c2e706466),
(195, 'BNK-INV-20250904-5642', 'budget manager', 'Financial', 'Bank Transfer', 'Account Payable', 500, 'test', 'test', '12345678910', '2025-09-25', 'Payment for invoice INV-INV-20250904-5642', 0x313735363938393335305f62696c6c2e706466),
(219, 'BNK-790', 'test', 'Human Resource-4', 'Bank Transfer', 'Account Payable', 50, 'BDO', 'test', '1234567891011213', '2025-08-31', 'Payment for invoice 518790', '');

-- --------------------------------------------------------

--
-- Table structure for table `budget_alerts`
--

CREATE TABLE `budget_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` enum('overspending','threshold','variance','forecast') NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `threshold_percentage` decimal(5,2) DEFAULT NULL,
  `current_percentage` decimal(5,2) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `message` text NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('active','resolved','dismissed') DEFAULT 'active',
  `acknowledged_by` varchar(255) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `resolved_by` varchar(255) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_allocations`
--

CREATE TABLE `budget_allocations` (
  `id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `annual_budget` decimal(10,2) DEFAULT 0.00,
  `category` varchar(255) NOT NULL,
  `from_allocated` date DEFAULT NULL,
  `to_allocated` date DEFAULT NULL,
  `allocated_amount` decimal(10,2) NOT NULL,
  `committed_amount` decimal(10,2) DEFAULT 0.00,
  `spent` decimal(10,2) NOT NULL,
  `remaining_balance` decimal(10,2) NOT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `coa_id_from` int(11) DEFAULT NULL,
  `coa_id_to` int(11) DEFAULT NULL,
  `from_account_name` varchar(255) DEFAULT NULL,
  `to_account_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_allocations`
--

INSERT INTO `budget_allocations` (`id`, `department`, `annual_budget`, `category`, `from_allocated`, `to_allocated`, `allocated_amount`, `committed_amount`, `spent`, `remaining_balance`, `status`, `coa_id_from`, `coa_id_to`, `from_account_name`, `to_account_name`) VALUES
(26, 'Financials', 125671.50, 'Tax Management', '2025-08-10', '2025-08-31', 83781.00, 0.00, 83781.00, 41890.50, 'active', NULL, NULL, NULL, NULL),
(27, 'Logistic-2', 753967.50, 'Vehicle Maintenance', '2025-08-31', '2025-09-07', 502645.00, 0.00, 500000.00, 253967.50, 'active', NULL, NULL, NULL, NULL),
(50, 'Administrative', 600000.00, 'Office Operations', '2026-02-13', '2026-12-21', 600000.00, 0.00, 0.00, 600000.00, 'active', 71, 146, 'Office Operations', 'Office Operations Cost'),
(54, 'Administrative', 100000.00, 'Legal & Compliance', '2026-02-13', '2026-12-21', 100000.00, 0.00, 0.00, 100000.00, 'active', 73, 148, 'Legal & Compliance', 'Legal & Compliance'),
(55, 'Administrative', 100000.00, 'Tax Payments', '2026-02-13', '2026-12-21', 100000.00, 0.00, 0.00, 100000.00, 'active', 89, 164, 'Tax Payments', 'Business Taxes'),
(56, 'Human Resource-1', 1000000.00, 'HR Systems', '2026-02-13', '2026-12-21', 1000000.00, 0.00, 0.00, 1000000.00, 'active', 80, 155, 'HR Systems', 'HR Systems'),
(57, 'Human Resource-1', 500000.00, 'Recruitment', '2026-02-13', '2026-12-21', 500000.00, 0.00, 0.00, 500000.00, 'active', 78, 153, 'Recruitment', 'Recruitment & Hiring'),
(58, 'Human Resource-1', 500000.00, 'Market Buffer', '2026-02-13', '2026-12-21', 500000.00, 0.00, 0.00, 500000.00, 'active', 85, 160, 'Market Buffer', 'Market Fluctuation Buffer'),
(59, 'Financials', 110000.00, 'Tax Payments', '2026-02-13', '2026-12-21', 110000.00, 0.00, 0.00, 110000.00, 'active', 89, 164, 'Tax Payments', 'Business Taxes'),
(60, 'Human Resource-4', 750000.00, 'Employee Salaries & Benefits', '2026-02-13', '2026-12-21', 750000.00, 0.00, 0.00, 750000.00, 'active', 76, 151, 'Employee Compensation', 'Employee Salaries & Benefits'),
(61, 'Human Resource-4', 999000.00, 'Payroll Administration', '2026-02-13', '2026-12-21', 999000.00, 0.00, 0.00, 999000.00, 'active', 77, 152, 'Payroll Processing', 'Payroll Administration'),
(62, 'Logistic-2', 120000.00, 'Parking & Toll Expenses', '2026-02-13', '2026-12-21', 120000.00, 0.00, 0.00, 120000.00, 'active', 54, 129, 'Parking & Tolls', 'Parking & Toll Expenses'),
(63, 'Logistic-2', 120000.00, 'Toll Road Expenses', '2026-02-13', '2026-12-21', 120000.00, 0.00, 0.00, 120000.00, 'active', 55, 130, 'Toll Fees', 'Toll Road Expenses'),
(64, 'Logistic-2', 850000.00, 'Tire Replacement', '2026-02-13', '2026-12-21', 850000.00, 0.00, 0.00, 850000.00, 'active', 50, 125, 'Parts Replacement', 'Tire Replacement'),
(65, 'Human Resource-3', 12000.00, 'Employee Salaries & Benefits', '2026-02-13', '2026-12-21', 12000.00, 0.00, 0.00, 12000.00, 'active', 76, 151, 'Employee Compensation', 'Employee Salaries & Benefits'),
(66, 'Human Resource-4', 60000.00, 'Support Staff Compensation', '2026-02-13', '2026-12-21', 60000.00, 0.00, 0.00, 60000.00, 'active', 75, 150, 'Support Staff', 'Support Staff Compensation');

-- --------------------------------------------------------

--
-- Table structure for table `budget_forecasts`
--

CREATE TABLE `budget_forecasts` (
  `id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `forecast_type` enum('monthly','quarterly','annual') DEFAULT 'monthly',
  `forecast_period` date NOT NULL,
  `forecasted_amount` decimal(15,2) NOT NULL,
  `actual_amount` decimal(15,2) DEFAULT 0.00,
  `confidence_level` int(11) DEFAULT 80,
  `assumptions` text DEFAULT NULL,
  `forecasted_by` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_forecasts`
--

INSERT INTO `budget_forecasts` (`id`, `department`, `category`, `forecast_type`, `forecast_period`, `forecasted_amount`, `actual_amount`, `confidence_level`, `assumptions`, `forecasted_by`, `created_at`, `updated_at`) VALUES
(1, 'Administrative', 'Administrative', 'monthly', '2026-02-17', 10000.00, 0.00, 77, '', 'System User', '2026-01-17 04:48:34', '2026-01-17 04:48:34'),
(2, 'Human Resource-1', 'Personnel & Workforce', 'quarterly', '0000-00-00', 10000.00, 9000.00, 86, 'none', 'System User', '2026-01-17 09:25:12', '2026-01-17 09:25:12');

-- --------------------------------------------------------

--
-- Table structure for table `budget_plans`
--

CREATE TABLE `budget_plans` (
  `id` int(11) NOT NULL,
  `plan_code` varchar(50) DEFAULT NULL,
  `plan_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `sub_category` varchar(255) NOT NULL,
  `plan_type` varchar(50) NOT NULL,
  `plan_year` int(11) NOT NULL,
  `plan_month` int(11) DEFAULT NULL,
  `planned_amount` decimal(15,2) NOT NULL,
  `gl_account_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `project_revenue` decimal(15,2) DEFAULT 0.00,
  `impact_percentage` decimal(5,2) DEFAULT 0.00,
  `taxation_adj` decimal(15,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` varchar(255) NOT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending_review','approved','archived','deleted') DEFAULT 'draft',
  `justification_doc` varchar(255) DEFAULT NULL,
  `source_plan_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `restored_from` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_plans`
--

INSERT INTO `budget_plans` (`id`, `plan_code`, `plan_name`, `department`, `category`, `sub_category`, `plan_type`, `plan_year`, `plan_month`, `planned_amount`, `gl_account_code`, `description`, `project_revenue`, `impact_percentage`, `taxation_adj`, `start_date`, `end_date`, `created_by`, `approved_by`, `status`, `justification_doc`, `source_plan_id`, `created_at`, `updated_at`, `restored_from`, `approved_at`, `deleted_at`) VALUES
(221, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 333333.34, '213003', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(222, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 333333.33, '213002', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(223, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 333333.33, '213001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(224, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accounts Payable', 'Service Payables', 'yearly', 2026, NULL, 1000000.00, '212001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(225, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accounts Payable', 'Supplier Payables', 'yearly', 2026, NULL, 500000.00, '211001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(226, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accounts Payable', 'Supplier Payables', 'yearly', 2026, NULL, 500000.00, '211002', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(227, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accrued Liabilities', 'Driver Payables', 'yearly', 2026, NULL, 1000000.00, '222001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(228, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accrued Liabilities', 'Employee Payables', 'yearly', 2026, NULL, 1000000.00, '223001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(229, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accrued Liabilities', 'Platform Payables', 'yearly', 2026, NULL, 1000000.00, '221001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(230, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Accrued Liabilities', 'Tax Payables', 'yearly', 2026, NULL, 1000000.00, '224001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(231, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Accident Reserves', 'yearly', 2026, NULL, 1000000.00, '572001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(232, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Emergency Repairs', 'yearly', 2026, NULL, 1000000.00, '571001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(233, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Equipment Maintenance', 'yearly', 2026, NULL, 1000000.00, '602001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(234, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Equipment Purchase', 'yearly', 2026, NULL, 1000000.00, '601001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(235, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Fuel & Energy', 'yearly', 2026, NULL, 1000000.00, '511001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(236, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Parking & Tolls', 'yearly', 2026, NULL, 1000000.00, '517001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(237, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Parts Replacement', 'yearly', 2026, NULL, 1000000.00, '513001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(238, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Registration & Licensing', 'yearly', 2026, NULL, 1000000.00, '516001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(239, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Toll Fees', 'yearly', 2026, NULL, 1000000.00, '518001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(240, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Vehicle Cleaning', 'yearly', 2026, NULL, 1000000.00, '514001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(241, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Vehicle Insurance', 'yearly', 2026, NULL, 1000000.00, '515001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(242, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Direct Operating Costs', 'Vehicle Maintenance', 'yearly', 2026, NULL, 1000000.00, '512001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(243, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Benefits Management', 'yearly', 2026, NULL, 1000000.00, '566001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(244, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Driver Compensation', 'yearly', 2026, NULL, 1000000.00, '521001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(245, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Driver Incentives', 'yearly', 2026, NULL, 1000000.00, '522001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(246, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Driver Insurance', 'yearly', 2026, NULL, 1000000.00, '525001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(247, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Driver Safety Gear', 'yearly', 2026, NULL, 1000000.00, '524001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(248, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Employee Compensation', 'yearly', 2026, NULL, 1000000.00, '561001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(249, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'General Overhead', 'yearly', 2026, NULL, 250000.00, '591004', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(250, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'General Overhead', 'yearly', 2026, NULL, 250000.00, '591003', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(251, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'General Overhead', 'yearly', 2026, NULL, 250000.00, '591001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(252, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'General Overhead', 'yearly', 2026, NULL, 250000.00, '591002', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(253, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'HR Systems', 'yearly', 2026, NULL, 1000000.00, '565001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(254, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Legal & Compliance', 'yearly', 2026, NULL, 1000000.00, '553001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(255, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Office Operations', 'yearly', 2026, NULL, 1000000.00, '551001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(256, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Payroll Processing', 'yearly', 2026, NULL, 1000000.00, '562001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(257, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Professional Services', 'yearly', 2026, NULL, 1000000.00, '552001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(258, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Recruitment', 'yearly', 2026, NULL, 1000000.00, '563001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(259, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Indirect Costs', 'Support Staff', 'yearly', 2026, NULL, 1000000.00, '555001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(260, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Long-term Liabilities', 'Loans Payable', 'yearly', 2026, NULL, 1000000.00, '231001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(261, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Supplies & Technology', 'Connectivity & Data', 'yearly', 2026, NULL, 1000000.00, '533001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(262, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Supplies & Technology', 'Hardware & Devices', 'yearly', 2026, NULL, 1000000.00, '534001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(263, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Supplies & Technology', 'Office Supplies', 'yearly', 2026, NULL, 1000000.00, '554001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(264, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Supplies & Technology', 'Platform Commissions', 'yearly', 2026, NULL, 1000000.00, '531001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(265, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Supplies & Technology', 'Software Licenses', 'yearly', 2026, NULL, 1000000.00, '535001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(266, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Supplies & Technology', 'Software Subscriptions', 'yearly', 2026, NULL, 1000000.00, '532001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(267, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Bank Charges', 'yearly', 2026, NULL, 1000000.00, '582001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(268, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Depreciation', 'yearly', 2026, NULL, 1000000.00, '581001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(269, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Interest Expense', 'yearly', 2026, NULL, 1000000.00, '583001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(270, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Market Buffer', 'yearly', 2026, NULL, 1000000.00, '574001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(271, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Permits & Licenses', 'yearly', 2026, NULL, 1000000.00, '585001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(272, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Regulatory Reserves', 'yearly', 2026, NULL, 1000000.00, '573001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(273, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Tax Payments', 'yearly', 2026, NULL, 500000.00, '584001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(274, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Taxes & Financial Costs', 'Tax Payments', 'yearly', 2026, NULL, 500000.00, '584002', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(275, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Accommodation', 'yearly', 2026, NULL, 1000000.00, '612001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(276, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Advertising', 'yearly', 2026, NULL, 1000000.00, '543001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(277, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Business Travel', 'yearly', 2026, NULL, 1000000.00, '611001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(278, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Customer Acquisition', 'yearly', 2026, NULL, 1000000.00, '541001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(279, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Digital Marketing', 'yearly', 2026, NULL, 1000000.00, '545001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(280, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Driver Acquisition', 'yearly', 2026, NULL, 1000000.00, '542001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(281, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Driver Training', 'yearly', 2026, NULL, 1000000.00, '523001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(282, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Referral Programs', 'yearly', 2026, NULL, 1000000.00, '544001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(283, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Staff Development', 'yearly', 2026, NULL, 1000000.00, '564001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(284, 'BATCH-20260212081918-7710', '2026 BUDGET', '', 'Transport & Training', 'Training & Education', 'yearly', 2026, NULL, 1000000.00, '613001', '2026 BUDGET', 65550000.00, 85.00, 6840000.00, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 00:19:18', '2026-02-12 00:19:18', NULL, NULL, NULL),
(285, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 16666666.68, '213003', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(286, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 16666666.66, '213002', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(287, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 16666666.66, '213001', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(288, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accounts Payable', 'Service Payables', 'yearly', 2026, NULL, 20000000.00, '212001', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(289, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accounts Payable', 'Supplier Payables', 'yearly', 2026, NULL, 5000000.00, '211001', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(290, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accounts Payable', 'Supplier Payables', 'yearly', 2026, NULL, 5000000.00, '211002', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(291, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accrued Liabilities', 'Driver Payables', 'yearly', 2026, NULL, 100000000.00, '222001', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(292, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accrued Liabilities', 'Employee Payables', 'yearly', 2026, NULL, 120000000.00, '223001', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(293, 'BATCH-20260212133359-2172', 'budget plan for 2026', '', 'Accrued Liabilities', 'Tax Payables', 'yearly', 2026, NULL, 5.00, '224001', 'budget plan for 2026', 345000005.75, 85.00, 36000000.60, '2026-02-12', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-12 05:33:59', '2026-02-12 05:33:59', NULL, NULL, NULL),
(294, 'BATCH-20260213112516-352D', 'FY 2025 Strategic Budget', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 14548044.00, '213003', 'budget plan', 50190751.80, 85.00, 5237295.84, '2026-02-13', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-13 03:25:16', '2026-02-13 03:25:16', NULL, NULL, NULL),
(295, 'BATCH-20260213112516-352D', 'FY 2025 Strategic Budget', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 14548044.00, '213002', 'budget plan', 50190751.80, 85.00, 5237295.84, '2026-02-13', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-13 03:25:16', '2026-02-13 03:25:16', NULL, NULL, NULL),
(296, 'BATCH-20260213112516-352D', 'FY 2025 Strategic Budget', '', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 14548044.00, '213001', 'budget plan', 50190751.80, 85.00, 5237295.84, '2026-02-13', '2026-12-31', 'User', NULL, 'approved', NULL, NULL, '2026-02-13 03:25:16', '2026-02-13 03:25:16', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `budget_plan_archive`
--

CREATE TABLE `budget_plan_archive` (
  `id` int(11) NOT NULL,
  `original_plan_id` int(11) DEFAULT NULL,
  `plan_code` varchar(50) DEFAULT NULL,
  `plan_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `sub_category` varchar(255) NOT NULL,
  `plan_type` enum('operational','capital','strategic','contingency','yearly') NOT NULL,
  `plan_year` int(11) NOT NULL,
  `plan_month` int(11) DEFAULT NULL,
  `planned_amount` decimal(15,2) NOT NULL,
  `gl_account_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_by` varchar(255) NOT NULL,
  `archive_reason` varchar(255) DEFAULT NULL,
  `archive_notes` text DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `restored` tinyint(1) DEFAULT 0,
  `restored_by` varchar(255) DEFAULT NULL,
  `restore_reason` text DEFAULT NULL,
  `restored_at` timestamp NULL DEFAULT NULL,
  `restored_plan_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_plan_archive`
--

INSERT INTO `budget_plan_archive` (`id`, `original_plan_id`, `plan_code`, `plan_name`, `department`, `category`, `sub_category`, `plan_type`, `plan_year`, `plan_month`, `planned_amount`, `gl_account_code`, `description`, `created_by`, `created_at`, `archived_by`, `archive_reason`, `archive_notes`, `archived_at`, `restored`, `restored_by`, `restore_reason`, `restored_at`, `restored_plan_id`) VALUES
(1, 40, NULL, '2026 Q2 Vehicle Maintenance', 'Administrative', 'Administrative', 'Office Rent & Utilities', 'operational', 2026, NULL, 154654.00, '1500', 'ajfjbaksjbdkf', 'System User', '2026-01-14 09:31:45', 'System', 'Other', '', '2026-01-14 09:31:45', 0, NULL, NULL, NULL, NULL),
(2, 54, NULL, 'Digital Transformation Initiative', 'Core-2', 'Fixed Assets', 'Office Equipment', 'capital', 2026, 8, 175000.00, '1500', 'Upgrade of technology infrastructure including computers, office machines, and furniture', 'Technology Director', '2026-01-22 05:49:10', 'System', 'completed', '', '2026-01-22 05:49:10', 0, NULL, NULL, NULL, NULL),
(3, 109, NULL, 'test', 'Strategic Planning', 'Accrued Liabilities', 'Employee Payables', 'yearly', 2026, NULL, 250.00, '223001', 'test', 'User', '2026-02-09 14:16:24', 'User', 'cancelled', '', '2026-02-09 14:16:24', 0, NULL, NULL, NULL, NULL),
(4, 108, NULL, 'test', 'Strategic Planning', 'Accrued Liabilities', 'Driver Payables', 'yearly', 2026, NULL, 250.00, '222001', 'test', 'User', '2026-02-09 14:16:36', 'User', 'completed', '', '2026-02-09 14:16:36', 0, NULL, NULL, NULL, NULL),
(5, 107, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Supplier Payables', 'yearly', 2026, NULL, 125.00, '211002', 'test', 'User', '2026-02-09 14:16:47', 'User', 'completed', '', '2026-02-09 14:16:47', 0, NULL, NULL, NULL, NULL),
(6, 97, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213001', 'test', 'User', '2026-02-09 14:17:09', 'User', 'cancelled', '', '2026-02-09 14:17:09', 0, NULL, NULL, NULL, NULL),
(7, 106, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Supplier Payables', 'yearly', 2026, NULL, 125.00, '211001', 'test', 'User', '2026-02-09 14:20:41', 'User', 'cancelled', '', '2026-02-09 14:20:41', 0, NULL, NULL, NULL, NULL),
(8, 83, NULL, 'TEST', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.34, '213003', 'TEST', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(9, 84, NULL, 'TEST', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213002', 'TEST', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(10, 85, NULL, 'TEST', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213001', 'TEST', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(11, 86, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.34, '213003', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(12, 87, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213002', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(13, 88, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213001', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(14, 89, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.34, '213003', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(15, 90, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213002', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(16, 91, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213001', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(17, 92, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213003', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(18, 93, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213002', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(19, 94, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213001', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(20, 95, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.34, '213003', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(21, 96, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213002', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(22, 98, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.34, '213003', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(23, 99, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213002', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(24, 100, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3.33, '213001', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(25, 101, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Service Payables', 'yearly', 2026, NULL, 10.00, '212001', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(26, 102, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 83.34, '213003', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(27, 103, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 83.33, '213002', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(28, 104, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 83.33, '213001', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(29, 105, NULL, 'test', 'Strategic Planning', 'Accounts Payable', 'Service Payables', 'yearly', 2026, NULL, 250.00, '212001', 'test', 'User', '2026-02-09 14:38:50', 'User', 'cancelled', '', '2026-02-09 14:38:50', 0, NULL, NULL, NULL, NULL),
(30, 80, NULL, 'LOCAL BUDGET', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3333.34, '213003', 'LB', 'User', '2026-02-09 14:39:21', 'User', 'cancelled', '', '2026-02-09 14:39:21', 0, NULL, NULL, NULL, NULL),
(31, 81, NULL, 'LOCAL BUDGET', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3333.33, '213002', 'LB', 'User', '2026-02-09 14:39:21', 'User', 'cancelled', '', '2026-02-09 14:39:21', 0, NULL, NULL, NULL, NULL),
(32, 82, NULL, 'LOCAL BUDGET', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 3333.33, '213001', 'LB', 'User', '2026-02-09 14:39:21', 'User', 'cancelled', '', '2026-02-09 14:39:21', 0, NULL, NULL, NULL, NULL),
(33, 76, NULL, 'Accounts Payable- Vendors', 'Strategic Planning', 'Accounts Payable', 'Supplier Payables', 'yearly', 2026, NULL, 125.00, '211002', 'test', 'User', '2026-02-09 14:39:40', 'User', 'cancelled', '', '2026-02-09 14:39:40', 0, NULL, NULL, NULL, NULL),
(34, 73, NULL, 'Driver Wallet Payable', 'Strategic Planning', 'Accounts Payable', 'Driver Payables', 'yearly', 2026, NULL, 83.33, '213001', 'test', 'User', '2026-02-09 14:39:56', 'User', 'cancelled', '', '2026-02-09 14:39:56', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `budget_plan_snapshots`
--

CREATE TABLE `budget_plan_snapshots` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `snapshot_date` date NOT NULL,
  `planned_amount` decimal(15,2) NOT NULL,
  `actual_amount` decimal(15,2) DEFAULT 0.00,
  `variance` decimal(15,2) DEFAULT 0.00,
  `snapshot_type` varchar(50) NOT NULL DEFAULT 'regular',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_proposals`
--

CREATE TABLE `budget_proposals` (
  `id` int(11) NOT NULL,
  `proposal_code` varchar(50) DEFAULT NULL,
  `plan_code` varchar(50) DEFAULT NULL,
  `proposal_title` varchar(255) DEFAULT NULL,
  `project_objectives` text DEFAULT NULL,
  `project_scope` text DEFAULT NULL,
  `project_deliverables` text DEFAULT NULL,
  `implementation_timeline` text DEFAULT NULL,
  `project_type` varchar(50) DEFAULT 'operational',
  `proposal_type` varchar(50) DEFAULT 'new',
  `department` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `plan_type` varchar(50) DEFAULT 'operational',
  `gl_account_code` varchar(20) DEFAULT NULL,
  `fiscal_year` int(11) NOT NULL,
  `quarter` int(11) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `total_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `direct_costs` decimal(15,2) DEFAULT 0.00,
  `indirect_costs` decimal(15,2) DEFAULT 0.00,
  `equipment_costs` decimal(15,2) DEFAULT 0.00,
  `travel_costs` decimal(15,2) DEFAULT 0.00,
  `contingency_percentage` decimal(5,2) DEFAULT 5.00,
  `contingency_amount` decimal(15,2) DEFAULT 0.00,
  `previous_budget` decimal(15,2) DEFAULT 0.00,
  `justification` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_case` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `justification_template_data` text DEFAULT NULL,
  `business_case_template_data` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expected_roi` decimal(5,2) DEFAULT NULL,
  `priority_level` varchar(20) DEFAULT 'medium',
  `submitted_by` varchar(100) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `status` enum('pending_review','submitted','approved','rejected','archived','draft') NOT NULL DEFAULT 'pending_review',
  `supporting_docs` longtext DEFAULT NULL,
  `detailed_breakdown` longtext DEFAULT NULL,
  `funding_sources` text DEFAULT NULL,
  `cost_sharing_details` text DEFAULT NULL,
  `team_members` text DEFAULT NULL,
  `executive_summary` text DEFAULT NULL,
  `approval_required` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `executive_approved_by` varchar(255) DEFAULT NULL,
  `executive_approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference_id` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `adjusted_amount` decimal(15,2) DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_proposals`
--

INSERT INTO `budget_proposals` (`id`, `proposal_code`, `plan_code`, `proposal_title`, `project_objectives`, `project_scope`, `project_deliverables`, `implementation_timeline`, `project_type`, `proposal_type`, `department`, `category`, `sub_category`, `plan_type`, `gl_account_code`, `fiscal_year`, `quarter`, `month`, `total_budget`, `direct_costs`, `indirect_costs`, `equipment_costs`, `travel_costs`, `contingency_percentage`, `contingency_amount`, `previous_budget`, `justification`, `business_case`, `justification_template_data`, `business_case_template_data`, `description`, `expected_roi`, `priority_level`, `submitted_by`, `submitted_at`, `status`, `supporting_docs`, `detailed_breakdown`, `funding_sources`, `cost_sharing_details`, `team_members`, `executive_summary`, `approval_required`, `start_date`, `end_date`, `duration_days`, `reviewed_by`, `reviewed_at`, `approved_by`, `approved_at`, `executive_approved_by`, `executive_approved_at`, `rejection_reason`, `created_at`, `reference_id`, `updated_at`, `adjusted_amount`, `archived_by`, `archived_at`) VALUES
(1, 'PROP-PEND-004', NULL, 'Driver Safety Training Program', NULL, NULL, NULL, NULL, 'operational', 'new', 'Human Resource-1', 'Driver Costs', 'Driver Training', 'operational', '5100', 2026, 1, NULL, 95000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, 'Comprehensive safety training for all drivers including new regulations.', NULL, NULL, NULL, NULL, 15.00, 'medium', 'Lisa Wang', '2026-01-12 00:00:00', 'rejected', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'System User', '2026-01-24 15:40:15', NULL, NULL, NULL, NULL, 'incomplete ka boi', '2026-01-12 05:42:27', NULL, '2026-01-24 07:40:15', NULL, NULL, NULL),
(2, 'PROP-PEND-008', NULL, 'Driver Safety Equipment 2025', NULL, NULL, NULL, NULL, 'operational', 'new', 'Human Resource-2', 'Driver Costs', 'Driver Safety Gear', 'operational', '5100', 2026, 1, NULL, 75000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, 'Annual refresh of safety equipment including vests and emergency kits.', NULL, NULL, NULL, NULL, 10.00, 'medium', 'Thomas Brown', '2026-01-13 00:00:00', '', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'System User', '2026-01-18 17:13:04', NULL, NULL, NULL, NULL, 'reject', '2026-01-13 05:42:27', NULL, '2026-01-24 05:05:31', NULL, NULL, NULL),
(8, 'PROP-20260115-89E070', NULL, 'Headquarters Operations 2025', NULL, NULL, NULL, NULL, 'operational', 'new', 'Administrative', 'Personnel & Workforce', 'Employee Salaries & Benefits', 'operational', NULL, 2026, NULL, NULL, 180000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Facilities Director', '2026-01-15 14:04:57', 'pending_review', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-15 06:04:57', NULL, '2026-01-22 04:28:03', NULL, NULL, NULL),
(9, 'PROP-20260115-89ED82', NULL, 'Vehicle Fleet Maintenance & Repairs', NULL, NULL, NULL, NULL, 'operational', 'new', 'Logistic-1', 'Vehicle Operations', 'Vehicle Maintenance', 'operational', NULL, 2026, NULL, NULL, 125000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Fleet Operations Manager', '2026-01-15 14:04:57', 'pending_review', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-15 06:04:57', NULL, '2026-01-22 04:28:16', NULL, NULL, NULL),
(10, 'PROP-20260115-8A0AC9', NULL, 'Fuel & Transportation Expenses', NULL, NULL, NULL, NULL, 'operational', 'new', 'Logistic-2', 'Vehicle Operations', 'Fuel Expenses', 'operational', NULL, 2026, NULL, NULL, 220000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Transport Manager', '2026-01-15 14:04:57', 'pending_review', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-15 06:04:57', NULL, '2026-01-22 04:28:28', NULL, NULL, NULL),
(11, 'PROP-20260115-8A18BA', NULL, 'Digital Transformation Initiative', NULL, NULL, NULL, NULL, 'operational', 'new', 'Core-2', 'Fixed Assets', 'Office Equipment', 'operational', NULL, 2026, NULL, NULL, 175000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Technology Director', '2026-01-15 14:04:57', 'pending_review', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-15 06:04:57', NULL, '2026-01-22 04:28:42', NULL, NULL, NULL),
(12, 'PROP-20260115-8A25C9', NULL, 'Professional Development & Certification', NULL, NULL, NULL, NULL, 'operational', 'new', 'Human Resource-4', 'Personnel & Workforce', 'Employee Salaries & Benefits', 'operational', NULL, 2026, NULL, NULL, 880000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'HR Development Manager', '2026-01-15 14:04:57', 'pending_review', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-15 06:04:57', NULL, '2026-01-22 04:28:54', NULL, NULL, NULL),
(13, 'PROP-20260122-812909', NULL, 'kjjvlivljvlhv', NULL, NULL, NULL, NULL, 'operational', 'new', 'Logistic-1', 'Vehicle Operations', 'Tire Replacement', 'operational', NULL, 2026, NULL, NULL, 52000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, 'HRTHBNR', 'BRTBRT', NULL, NULL, 'VFSBVD', 5.00, 'medium', 'System User', '2026-01-22 08:18:32', '', '[\"1769066312_1_Untitled document.pdf\"]', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-22 07:18:32', NULL, '2026-01-22 07:18:32', NULL, NULL, NULL),
(14, 'PROP-20260122-6F1F48', NULL, 'kjjvlivljvlhv', NULL, NULL, NULL, NULL, 'operational', 'new', 'Logistic-2', 'Vehicle Operations', 'Insurance Premiums', 'capital', NULL, 2026, NULL, NULL, 5000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, 'justified', 'case', NULL, NULL, 'desc', 3.00, 'medium', 'Ethan Magsaysay', '2026-01-22 08:33:26', 'rejected', '[\"1769067206_1_Untitled document.pdf\"]', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'rejected', '2026-01-22 07:33:27', NULL, '2026-01-24 07:38:06', NULL, NULL, NULL),
(15, 'PROP-20260122-3C326A', NULL, 'kjjvlivljvlhv', NULL, NULL, NULL, NULL, 'operational', 'new', 'Human Resource-2', 'Personnel & Workforce', 'Payroll Administration', 'capital', '5510', 2026, 1, 3, 5551165.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 51556.00, 'r65y4ey', 'gerge', NULL, NULL, 'gbseb', 5.00, 'medium', 'System User', '2026-01-22 10:08:51', 'approved', '[\"1769072931_1_Untitled document.pdf\",\"1769072931_2_Untitled document.pdf\"]', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'System User', '2026-01-24 15:39:02', 'System User', '2026-01-24 15:39:07', NULL, NULL, NULL, '2026-01-22 09:08:51', NULL, '2026-01-24 07:39:07', 5500000.00, NULL, NULL),
(16, 'PROP-20260122-621DCB', NULL, 'kjjvlivljvlhv', NULL, NULL, NULL, NULL, 'operational', 'new', 'Administrative', 'Other Expenses', 'Depreciation Expense', 'project', '5700', 2026, NULL, NULL, 30000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, 'khckh', '', NULL, NULL, '', 5.00, 'medium', 'System User', '2026-01-22 12:08:06', '', '[\"1769080086_1_Untitled document.pdf\"]', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'rejected', '2026-01-22 11:08:06', NULL, '2026-01-24 04:12:32', NULL, NULL, NULL),
(17, 'PROP-20260124-76726F', NULL, 'Q1 Marketing Campaign', NULL, NULL, NULL, NULL, 'operational', 'new', 'Human Resource-1', 'Marketing & Acquisition', 'Promotional Campaigns', 'operational', NULL, 2026, 1, 2, 10000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, 'Sample Award Justification\\r\\n\\r\\nNOTE: ALL  awards (regardless of amount, unless otherwise specified in an Union Agreement)  require a separate written justification to be attached to the AD 287-2.  \\r\\n\\r\\nThe following is step-by-step outline that describes the sections and verbage to justify an award: \\r\\n\\r\\n“During the period of (MMDDYYYY to MMDDYYY) (EMPLOYEE/GROUP NAME) (description of accomplishment-list the duties and accomplishment the employee has done to deserve this Award).\\r\\n\\r\\nThis exceeded expectations as identified in the current performance plan by:\\r\\n\\r\\nImproving quality.\\r\\nTimely completion of the project.\\r\\nIncreasing productivity.\\r\\nOvercoming adverse obstacles or working under unusual circumstances.\\r\\nUsing unusual creativity.\\r\\nSaving the Government time and/or money.\\r\\nIncreasing program effectiveness.\\r\\n\\r\\nAs a result:\\r\\n\\r\\nProject acceptance.\\r\\nSavings in time, money, and/or material.\\r\\nMore efficiency.\\r\\nEffectiveness.\\r\\nTechnological advancement.\\r\\nProductivity increase.\\r\\nImproved levels of cooperation that will result in . . .\\r\\n\\r\\nTherefore, we propose an award of (amount/hours).”', '📑 Business Case: Proposed Budget Allocation\\r\\n1. Executive Summary\\r\\nThis proposal seeks approval for a budget of [insert amount] to support [specific project, initiative, or department]. The investment will enable the organization to achieve [key objectives, e.g., improved efficiency, compliance, growth] while ensuring sustainable resource management.\\r\\n\\r\\n2. Problem Statement\\r\\n- Current challenges: [e.g., outdated systems, limited staff capacity, rising operational costs]\\r\\n- Risks of not investing: [e.g., reduced productivity, compliance issues, missed opportunities]\\r\\n3. Objectives\\r\\n- Enhance [efficiency, security, customer satisfaction, etc.]\\r\\n- Support organizational goals such as [digital transformation, expansion, cost savings]\\r\\n- Provide measurable outcomes within [timeframe]\\r\\n\\r\\n4. Proposed Solution\\r\\n- Allocate [budget amount] to fund [specific activities: software upgrades, staff training, new equipment, etc.]\\r\\n- Implementation timeline: [start date – end date]\\r\\n- Responsible team: [department or project lead]\\r\\n5. Financial Impact\\r\\n|  |  |  | \\r\\n|  |  |  | \\r\\n|  |  |  | \\r\\n|  |  |  | \\r\\n\\r\\n\\r\\nTotal Budget Requested: $XXX,XXX\\r\\nROI Projection: Savings of $XX,XXX annually, payback within [X years/months]\\r\\n\\r\\n6. Benefits\\r\\n- Tangible: Cost savings, efficiency gains, compliance assurance\\r\\n- Intangible: Improved employee morale, stronger customer trust, future scalability\\r\\n\\r\\n7. Risk Assessment\\r\\n- Risks: [budget overruns, implementation delays]\\r\\n- Mitigation: [regular monitoring, phased rollout, vendor support]\\r\\n\\r\\n8. Recommendation\\r\\nApproval of this budget will ensure the organization remains competitive, efficient, and aligned with strategic goals. The investment is justified by both the immediate operational improvements and long-term financial benefits.', NULL, NULL, 'marketing campaign', 2.00, 'medium', 'System User', '2026-01-24 08:48:23', 'pending_review', '[\"1769240903_1_MEMORANDUM-OF-AGREEMENT.docx\"]', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-24 07:48:23', NULL, '2026-01-24 07:48:23', NULL, NULL, NULL),
(18, 'PROP-20260124-309523', NULL, 'sddvs', NULL, NULL, NULL, NULL, 'operational', 'new', 'Core-1', 'Driver Costs', 'Driver Training', 'operational', NULL, 2026, NULL, NULL, 20000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, 'justification_strategic_alignment: khfk\\r\\n\\r\\njustification_business_need: jhfhm\\r\\n\\r\\njustification_current_situation: Current capabilities:khfk\\r\\nCurrent limitations: jgcjh\\r\\nImpact: hfmj\\r\\nGap analysis: jhgl,\\r\\n\\r\\njustification_proposed_solution: Our proposed solution involves implementing a new enterprise software platform with integrated modules for workflow automation, data analytics, and customer management. This will specifically address our efficiency challenges by automating manual processes and providing real-time insights. Key components include:\\r\\n1. Core platform implementation\\r\\n2. Data migration and integration\\r\\n3. User training and change management\\r\\n\\r\\njustification_alternatives: We evaluated the following alternatives:\\r\\n1. System upgrade: Rejected because limited scalability and high long-term costs\\r\\n2. Multiple point solutions: Rejected because integration challenges and higher total cost of ownership\\r\\n3. Outsourced solution: Rejected because lack of control and data security concerns\\r\\n\\r\\nSelected option provides the best balance of functionality, scalability, and cost-effectiveness.\\r\\n\\r\\njustification_risks: Identified risks:\\r\\n1. Implementation delays - Probability: Medium - Impact: High - Mitigation: Detailed project planning with buffer time\\r\\n2. User resistance - Probability: Medium - Impact: Medium - Mitigation: Comprehensive training and change management program\\r\\n3. Budget overrun - Probability: Low - Impact: High - Mitigation: Fixed-price contract with milestone payments\\r\\n\\r\\njustification_success_metrics: Success will be measured using the following KPIs:\\r\\n1. Process Efficiency: Target: 40% improvement - Timeline: 6 months post-implementation\\r\\n2. Error Reduction: Target: 60% reduction - Timeline: 3 months post-implementation\\r\\n3. User Satisfaction: Target: 85% satisfaction rate - Timeline: Quarterly surveys\\r\\n\\r\\nBaseline: Current manual processes with 65% efficiency\\r\\nTarget: Automated processes with 90% efficiency\\r\\nImprovement: 25% overall efficiency gain\\r\\n\\r\\njustification_timeline: Proposed timeline:\\r\\n- Month 1: Requirements gathering and vendor selection\\r\\n- Month 2-3: System configuration and customization\\r\\n- Month 4: User acceptance testing and training\\r\\n- Month 5: Go-live and initial support\\r\\n- Month 6-12: Optimization and advanced feature rollout', 'business_case_executive_summary: This ROI-focused business case presents a compelling investment opportunity with strong financial returns. The ₱[amount] investment in [proposal title] is projected to deliver [ROI]% ROI with a rapid payback period of [timeframe]. This proposal represents a high-return opportunity that directly impacts our bottom line.\\r\\n\\r\\nbusiness_case_financial_analysis: Investment Required: ₱[amount]\\r\\n- Direct Costs: ₱[amount]\\r\\n- Indirect Costs: ₱[amount]\\r\\n- Contingency: ₱[amount]\\r\\n\\r\\nRevenue Impact:\\r\\n- Direct Revenue: ₱[amount]/year from new capabilities\\r\\n- Incremental Revenue: ₱[amount]/year from upsell/cross-sell\\r\\n- Market Share Gain: ₱[amount]/year from competitive advantage\\r\\n\\r\\nCost Impact:\\r\\n- Direct Savings: ₱[amount]/year from efficiency\\r\\n- Avoided Costs: ₱[amount]/year from risk mitigation\\r\\n- Scalability Benefits: ₱[amount]/year from volume\\r\\n\\r\\nROI Details:\\r\\n- Year 1 ROI: [percentage]%\\r\\n- Year 2 ROI: [percentage]%\\r\\n- Year 3 ROI: [percentage]%\\r\\n- Cumulative ROI: [percentage]%\\r\\n\\r\\nbusiness_case_payback_period: Payback period: [number] months based on conservative estimates. Aggressive benefits realization could reduce payback to [number] months.\\r\\n\\r\\nbusiness_case_npv: NPV Analysis:\\r\\n- Discount Rate: 10%\\r\\n- NPV (3 years): ₱[amount]\\r\\n- IRR: [percentage]%\\r\\n- Profitability Index: [number]\\r\\n\\r\\nAll metrics indicate strong financial viability.\\r\\n\\r\\nbusiness_case_sensitivity: ROI Sensitivity to Key Variables:\\r\\n1. Revenue Impact: ±20% changes ROI by ±[percentage]%\\r\\n2. Cost Savings: ±15% changes ROI by ±[percentage]%\\r\\n3. Implementation Timeline: ±2 months changes ROI by ±[percentage]%\\r\\n\\r\\nEven in worst-case scenarios, ROI remains positive at [percentage]%.\\r\\n\\r\\nbusiness_case_non_financial: While financial returns are primary, significant non-financial benefits include:\\r\\n- Enhanced competitive positioning\\r\\n- Improved operational resilience\\r\\n- Better decision-making capabilities\\r\\n- Increased employee productivity\\r\\n- Enhanced customer satisfaction\\r\\n- Stronger compliance posture\\r\\n\\r\\nThese benefits further strengthen the investment case.\\r\\n\\r\\nbusiness_case_implementation: Fast-track Implementation:\\r\\n- Month 1: Quick win implementation for immediate benefits\\r\\n- Months 2-3: Core functionality deployment\\r\\n- Months 4-6: Advanced features and optimization\\r\\n\\r\\nResources:\\r\\n- Lean team: 3 FTEs\\r\\n- Agile methodology for rapid delivery\\r\\n- Phased benefits realization\\r\\n\\r\\nbusiness_case_success_criteria: Primary Success Criteria:\\r\\n- Achieve [percentage]% ROI within agreed timeframe\\r\\n- Realize 80% of projected benefits within 12 months\\r\\n- Maintain positive cash flow throughout implementation\\r\\n\\r\\nSecondary Metrics:\\r\\n- Customer satisfaction improvement\\r\\n- Process cycle time reduction\\r\\n- Error rate reduction\\r\\n- Employee adoption rate', NULL, NULL, '', 1.00, 'medium', 'System User', '2026-01-24 10:16:35', 'pending_review', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-24 09:16:35', NULL, '2026-01-24 09:16:35', NULL, NULL, NULL),
(19, 'PROP-20260124-434377', NULL, 'title', NULL, NULL, NULL, NULL, 'operational', 'new', 'Human Resource-2', 'Accrued Liabilities', 'Employee Payables', 'operational', '2120', 2026, NULL, NULL, 31000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, '', '', '{\\\"business_objective\\\":\\\"sbad\\\",\\\"problem_statement\\\":\\\"adfsdas\\\",\\\"proposed_solution\\\":\\\"adfd\\\",\\\"benefits\\\":\\\"asdvsdv\\\",\\\"risks\\\":\\\"vsdvsd\\\",\\\"alternatives\\\":\\\"vsdvsa\\\",\\\"implementation_timeline\\\":\\\"asvas\\\",\\\"success_metrics\\\":\\\"vaasd\\\"}', '{\\\"executive_summary\\\":\\\"jhvdj,sa\\\",\\\"project_description\\\":\\\"sdvsad\\\",\\\"market_analysis\\\":\\\"asdvd\\\",\\\"technical_requirements\\\":\\\"asdvsd\\\",\\\"financial_analysis\\\":\\\"svssd\\\",\\\"roi_calculation\\\":\\\"asdvasd\\\",\\\"implementation_plan\\\":\\\"vsdvs\\\",\\\"conclusion\\\":\\\"asdvsd\\\"}', '', 5.00, 'medium', 'System User', '2026-01-24 10:57:24', 'rejected', '[\"1769248644_1_MEMORANDUM-OF-AGREEMENT.docx\"]', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'System User', '2026-01-24 18:00:46', NULL, NULL, NULL, NULL, 'kulang ng docu', '2026-01-24 09:57:24', NULL, '2026-01-24 10:00:46', NULL, NULL, NULL),
(20, 'PROP-20260124-739BE4', NULL, 'kjjvlivljvlhv', NULL, NULL, NULL, NULL, 'operational', 'new', 'Core-1', 'Long-term Liabilities', 'Loans Payable', 'operational', '2200', 2026, NULL, NULL, 22545.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, '{\\\"problem_statement\\\":\\\"zgd\\\",\\\"current_situation\\\":\\\"asdgsadg\\\",\\\"proposed_solution\\\":\\\"asdg\\\",\\\"expected_outcomes\\\":\\\"dsg\\\",\\\"alternative_options\\\":\\\"asdg\\\",\\\"risks_mitigation\\\":\\\"ag\\\",\\\"timeline\\\":\\\"asg\\\"}', '{\\\"executive_summary\\\":\\\"adg\\\",\\\"strategic_alignment\\\":\\\"sadgwwg\\\",\\\"financial_analysis\\\":\\\"awg\\\",\\\"roi_calculation\\\":\\\"sag\\\",\\\"resource_requirements\\\":\\\"dfhsd\\\",\\\"implementation_plan\\\":\\\"sdfg\\\",\\\"success_metrics\\\":\\\"sdffgdf\\\",\\\"assumptions\\\":\\\"sdfgs\\\"}', NULL, NULL, '', 2.00, 'medium', 'System User', '2026-01-24 11:31:35', 'pending_review', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, '<NAME>', '2026-01-25 18:25:17', NULL, NULL, NULL, NULL, 'wala lang ulit', '2026-01-24 10:31:35', NULL, '2026-01-25 10:25:17', 21000.00, NULL, NULL),
(21, 'PROP-20260124-523165', NULL, 'kjjvlivljvlhv', NULL, NULL, NULL, NULL, 'operational', 'new', 'Human Resource-2', 'Vehicle Operations', 'Vehicle Maintenance', 'project', NULL, 2027, NULL, NULL, 266422.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, '{\"business_context\":\"jhfmjm\",\"problem_statement\":\"rhzer\",\"proposed_solution\":\"khckh\",\"expected_benefits\":\"zergER\",\"risks_and_mitigation\":\"eghzerg\",\"alternatives_considered\":\"argar\",\"implementation_timeline\":\"awrgargawr\",\"success_metrics\":\"aerarea\"}', '{\"executive_summary\":\"awrt\",\"strategic_alignment\":\"awta\",\"financial_analysis\":\"awrg\",\"cost_breakdown\":\"awtgaw\",\"roi_calculation\":\"aerga\",\"payback_period\":\"arwegaa\",\"resource_requirements\":\"awrga\",\"assumptions\":\"argaw\",\"contingency_plan\":\"aerhg\",\"recommendation\":\"wgt2eew\"}', NULL, NULL, '', 5.00, '0', 'System User', '2026-01-24 12:29:09', 'rejected', NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, '<NAME>', '2026-01-25 18:13:10', NULL, NULL, NULL, NULL, 'wala lang', '2026-01-24 11:29:09', NULL, '2026-01-25 10:13:10', NULL, NULL, NULL),
(22, 'PROP-20260129-10E327', NULL, 'TEST', 'TEST', 'As described in objectives', 'As described in objectives', 'As per start and end dates', 'capital', 'new', 'Administrative', 'Technology & Platform', 'App Platform Fees', 'operational', NULL, 2026, NULL, NULL, 9000000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'As described above', 'As described in justification', NULL, NULL, '', NULL, 'medium', 'User', '2026-01-29 06:44:17', 'pending_review', '[\"1769665457_0_Payslip_ID_ 1202503_2026-01-28.pdf\"]', NULL, 'Internal', 'N/A', 'As assigned', 'As described in objectives', 0, '2026-01-29', '2026-01-31', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-29 05:44:17', NULL, '2026-01-29 05:44:17', NULL, NULL, NULL),
(23, 'PROP-20260129-F133AA', NULL, 'TNVS', 'TEST', 'As described in objectives', 'As described in objectives', 'As per start and end dates', 'operational', 'new', 'Core-1', 'Technology & Platform', 'Mobile Device Expenses', 'operational', NULL, 2026, NULL, NULL, 5000000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'As described above', 'As described in justification', NULL, NULL, '', NULL, 'medium', 'User', '2026-01-29 06:57:51', 'pending_review', '[\"1769666271_0_Budget_Proposal_ViaHale.pdf\"]', NULL, 'Internal', 'N/A', 'As assigned', 'As described in objectives', 0, '2026-01-29', '2026-01-31', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-29 05:57:51', NULL, '2026-01-29 05:57:51', NULL, NULL, NULL),
(24, 'PROP-20260206-AEF4FD', NULL, 'Budget Proposal C.O', 'Budget', NULL, NULL, NULL, 'operational', 'new', 'Core-1', 'Taxes & Financial Costs', 'Market Buffer', 'operational', NULL, 2026, NULL, NULL, 20000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Ethan Magsaysay', '2026-02-06 13:44:26', 'pending_review', '[\"doc_1770356666_BudgetProposalSample.pdf\"]', NULL, NULL, NULL, NULL, NULL, 1, '2026-02-06', '2026-02-28', 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-06 05:44:26', 'BR-20260206-3670', '2026-02-06 05:44:26', NULL, NULL, NULL),
(25, 'PROP-20260206-1883ED', NULL, 'Supplies', 'Budget', NULL, NULL, NULL, 'operational', 'new', 'Core-2', 'Supplies & Technology', 'Office Supplies', 'operational', NULL, 2026, NULL, NULL, 5000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Ethan Magsaysay', '2026-02-06 21:13:53', 'pending_review', '[\"doc_1770383633_Budget_Proposal_ViaHale.pdf\"]', NULL, NULL, NULL, NULL, NULL, 1, '2026-02-06', '2026-02-06', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-06 13:13:53', 'BR-20260206-8775', '2026-02-06 13:13:53', NULL, NULL, NULL),
(26, 'PROP-20260206-218D74', NULL, '2026 BUDGET PROPOSAL', 'EXPENSES', NULL, NULL, NULL, 'operational', 'new', 'Human Resource-2', 'Transport & Training', 'Driver Training', 'operational', NULL, 2026, NULL, NULL, 12000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Ethan Magsaysay', '2026-02-06 22:53:54', 'pending_review', '[\"doc_1770389634_Budget_Proposal_ViaHale.pdf\"]', '[{\"account_code\":\"551001\",\"name\":\"Office Operations Cost\",\"category\":\"Indirect Costs\",\"subcategory\":\"Office Operations\",\"amount\":5000},{\"account_code\":\"512001\",\"name\":\"Maintenance & Servicing\",\"category\":\"Direct Operating Costs\",\"subcategory\":\"Vehicle Maintenance\",\"amount\":3000},{\"account_code\":\"523001\",\"name\":\"Driver Training\",\"category\":\"Transport & Training\",\"subcategory\":\"Driver Training\",\"amount\":4000}]', NULL, NULL, NULL, NULL, 1, '2026-02-06', '2026-03-07', 29, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-06 14:53:54', 'BR-20260206-9469', '2026-02-06 14:53:54', NULL, NULL, NULL),
(27, 'PROP-20260206-27E252', NULL, 'TEST TITLE', 'TEST PURPOSE', NULL, NULL, NULL, 'operational', 'new', 'Human Resource-2', 'Transport & Training', 'Driver Training', 'operational', NULL, 2026, NULL, NULL, 5000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Ethan Magsaysay', '2026-02-06 23:06:58', 'approved', '[\"doc_1770390418_Budget_Proposal_ViaHale.pdf\"]', '[{\"account_code\":\"523001\",\"name\":\"Driver Training\",\"category\":\"Transport & Training\",\"subcategory\":\"Driver Training\",\"amount\":5000}]', NULL, NULL, NULL, NULL, 1, '2026-02-06', '2026-02-25', 19, NULL, NULL, 'User', '2026-02-10 23:54:31', NULL, NULL, NULL, '2026-02-06 15:06:58', 'BR-20260206-8608', '2026-02-10 15:54:31', NULL, NULL, NULL),
(28, 'PROP-20260206-55811E', NULL, 'ACE', 'ESU', NULL, NULL, NULL, 'operational', 'new', 'Core-1', 'Taxes & Financial Costs', 'Depreciation', 'operational', NULL, 2026, NULL, NULL, 2500.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Ethan Magsaysay', '2026-02-06 23:09:09', 'rejected', '[\"doc_1770390549_Budget_Proposal_ViaHale.pdf\"]', '[{\"account_code\":\"581001\",\"name\":\"Depreciation Expense\",\"category\":\"Taxes & Financial Costs\",\"subcategory\":\"Depreciation\",\"amount\":2500}]', NULL, NULL, NULL, NULL, 1, '2026-02-06', '2026-03-04', 26, 'User', '2026-02-10 19:37:53', NULL, NULL, NULL, NULL, 'test', '2026-02-06 15:09:09', 'BR-20260206-4191', '2026-02-10 11:37:53', NULL, NULL, NULL),
(29, 'PROP-20260206-FC71E2', NULL, 'Program H2', 'PH2', NULL, NULL, NULL, 'operational', 'new', 'Human Resource-1', 'Supplies & Technology', 'Hardware & Devices', 'operational', NULL, 2026, NULL, NULL, 6500.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 'Ethan Magsaysay', '2026-02-06 23:15:59', 'approved', '[\"doc_1770390959_Budget_Proposal_ViaHale.pdf\"]', '[{\"account_code\":\"534001\",\"name\":\"Mobile Device Expenses\",\"category\":\"Supplies & Technology\",\"subcategory\":\"Hardware & Devices\",\"amount\":6500}]', NULL, NULL, NULL, NULL, 1, '2026-02-06', '2026-02-06', 0, NULL, NULL, 'User', '2026-02-10 23:54:00', NULL, NULL, NULL, '2026-02-06 15:15:59', 'BR-20260206-3890', '2026-02-10 15:54:00', NULL, NULL, NULL),
(30, 'PROP-2026-1333', NULL, '2026 BUDGET', '2026 BUDGET', '2026 BUDGET', '2026 BUDGET', '2026 BUDGET', 'yearly', 'new', 'Strategic Planning', 'Accounts Payable', 'Service Payables', 'operational', '212001', 2026, NULL, NULL, 5600000000000.00, 0.00, 0.00, 0.00, 0.00, 5.00, 0.00, 0.00, '2026', NULL, NULL, NULL, '2026 BUDGET', NULL, 'medium', 'User', '2026-02-08 00:48:23', 'approved', '[\"1770482903_Budget_Proposal_ViaHale.pdf\"]', NULL, NULL, NULL, NULL, '{\"gl_allocations\":{\"212001\":\"100000000000.00\",\"211001\":\"100000000000.00\",\"222001\":\"100000000000.00\",\"223001\":\"100000000000.00\",\"221001\":\"100000000000.00\",\"224001\":\"100000000000.00\",\"572001\":\"100000000000.00\",\"571001\":\"100000000000.00\",\"602001\":\"100000000000.00\",\"601001\":\"100000000000.00\",\"511001\":\"100000000000.00\",\"517001\":\"100000000000.00\",\"513001\":\"100000000000.00\",\"516001\":\"100000000000.00\",\"518001\":\"100000000000.00\",\"514001\":\"100000000000.00\",\"515001\":\"100000000000.00\",\"512001\":\"100000000000.00\",\"566001\":\"100000000000.00\",\"521001\":\"100000000000.00\",\"522001\":\"100000000000.00\",\"525001\":\"100000000000.00\",\"524001\":\"100000000000.00\",\"561001\":\"100000000000.00\",\"591004\":\"25000000000.00\",\"591003\":\"25000000000.00\",\"591001\":\"25000000000.00\",\"591002\":\"25000000000.00\",\"565001\":\"100000000000.00\",\"553001\":\"100000000000.00\",\"551001\":\"100000000000.00\",\"562001\":\"100000000000.00\",\"552001\":\"100000000000.00\",\"563001\":\"100000000000.00\",\"555001\":\"100000000000.00\",\"231001\":\"100000000000.00\",\"533001\":\"100000000000.00\",\"534001\":\"100000000000.00\",\"554001\":\"100000000000.00\",\"531001\":\"100000000000.00\",\"535001\":\"100000000000.00\",\"532001\":\"100000000000.00\",\"582001\":\"100000000000.00\",\"581001\":\"100000000000.00\",\"583001\":\"100000000000.00\",\"574001\":\"100000000000.00\",\"585001\":\"100000000000.00\",\"573001\":\"100000000000.00\",\"584001\":\"100000000000.00\",\"612001\":\"100000000000.00\",\"543001\":\"100000000000.00\",\"611001\":\"100000000000.00\",\"541001\":\"100000000000.00\",\"545001\":\"100000000000.00\",\"542001\":\"100000000000.00\",\"523001\":\"100000000000.00\",\"544001\":\"100000000000.00\",\"564001\":\"100000000000.00\",\"613001\":\"100000000000.00\"},\"additional_wages\":0,\"additional_tax\":0,\"total_revenue_plan\":0}', 1, '2026-02-07', '2026-12-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-07 16:48:23', NULL, '2026-02-07 16:48:23', NULL, NULL, NULL),
(42, 'PROP-20260210-E551B3', NULL, 'East Blue', 'EB', 'EB', 'As per budget description', 'As per project dates', 'operational', 'new', 'Administrative', 'Direct Operating Costs', 'Emergency Repairs', 'operational', NULL, 2026, NULL, NULL, 500.00, 0.00, 0.00, 0.00, 0.00, 5.00, 25.00, 0.00, 'EB', '', NULL, NULL, '', NULL, 'medium', 'User', '2026-02-10 10:42:06', 'approved', '[\"1770720126_0_Budget_Proposal_ViaHale.pdf\"]', NULL, '', '', '', '', 0, '2026-02-10', '2026-12-31', 324, NULL, NULL, 'User', '2026-02-10 19:37:35', NULL, NULL, NULL, '2026-02-10 10:42:06', NULL, '2026-02-10 11:37:35', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `budget_proposal_items`
--

CREATE TABLE `budget_proposal_items` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `item_type` enum('direct','indirect','equipment','travel','contingency','other') NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_cost` decimal(15,2) NOT NULL,
  `total_cost` decimal(15,2) NOT NULL,
  `timeline_month` int(2) DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `vendor_info` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_proposal_items`
--

INSERT INTO `budget_proposal_items` (`id`, `proposal_id`, `item_type`, `category`, `description`, `quantity`, `unit_cost`, `total_cost`, `timeline_month`, `justification`, `vendor_info`, `created_at`) VALUES
(1, 42, 'direct', 'direct', 'Referral Programs', 1, 250.00, 250.00, NULL, '', '', '2026-02-10 18:42:06'),
(2, 42, 'direct', 'direct', 'Emergency Repairs', 1, 250.00, 250.00, NULL, '', '', '2026-02-10 18:42:06');

-- --------------------------------------------------------

--
-- Table structure for table `budget_request`
--

CREATE TABLE `budget_request` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(30) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `detailed_breakdown` longtext DEFAULT NULL,
  `document` varchar(255) NOT NULL,
  `time_period` varchar(20) NOT NULL,
  `payment_due` date NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `bank_account_name` varchar(100) NOT NULL,
  `bank_account_number` varchar(255) NOT NULL,
  `ecash_provider` varchar(100) NOT NULL,
  `ecash_account_name` varchar(100) NOT NULL,
  `ecash_account_number` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_request`
--

INSERT INTO `budget_request` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `detailed_breakdown`, `document`, `time_period`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `status`, `rejection_reason`, `rejected_at`, `created_at`) VALUES
(1, 'BR-20260124-6539', 'Ethan Magsaysay', 'Human Resource-1', 'Cash', 'qwe', 511515, 'qwe', NULL, '', 'weekly', '2026-01-28', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 20:52:49'),
(2, 'BR-20260124-7901', 'Ethan Magsaysay', 'Core-1', 'Cash', 'qbbsf', 2652, 'sdfv', NULL, '', 'weekly', '2026-01-26', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 20:53:29'),
(4, 'BR-20260124-4515', 'Ethan Magsaysay', 'Core-1', 'Cash', 'ace', 25000, 'ace', NULL, '', 'weekly', '2026-01-28', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:00:10'),
(5, 'BR-20260124-3111', 'Ethan Magsaysay', 'Administrative', 'Cash', 'van', 4000, 'van', NULL, '', 'weekly', '2026-01-26', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:15:10'),
(6, 'BR-20260124-8000', 'Ethan Magsaysay', 'Administrative', 'Cash', 'van', 4000, 'van', NULL, '', 'weekly', '2026-01-26', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:15:16'),
(7, 'BR-20260124-2968', 'Ethan Magsaysay', 'Core-2', 'Cash', 'asd', 60000, 'sda', NULL, '', 'weekly', '2026-01-27', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:24:47'),
(8, 'BR-20260124-1657', 'Ethan Magsaysay', 'Human Resource-1', 'Cash', 'qqqw', 5000, 'qwqwqwwq', NULL, '', 'weekly', '2026-01-29', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:25:58'),
(9, 'BR-20260124-1170', 'Ethan Magsaysay', 'Human Resource-4', 'Cash', 'be', 3000, 'be', NULL, '', 'weekly', '2026-01-28', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:40:36'),
(10, 'BR-20260124-5832', 'Ethan Magsaysay', 'Core-2', 'Cash', 'xxxxx', 7000, 'xxxx', NULL, '', 'weekly', '2026-01-26', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:48:37'),
(11, 'BR-20260124-7022', 'Ethan Magsaysay', 'Core-2', 'Cash', 'xxxxx', 7000, 'xxxx', NULL, '', 'weekly', '2026-01-26', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:52:02'),
(12, 'BR-20260124-1119', 'Ethan Magsaysay', 'Human Resource-2', 'Cash', 'sdgsdg', 5000, 'sgdsg', NULL, '', 'weekly', '2026-01-27', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:57:48'),
(14, 'BR-20260124-9062', 'Ethan Magsaysay', 'Human Resource-1', 'Ecash', 'cel', 55000, 'cel', NULL, '', 'weekly', '2026-01-26', '', '', '', 'cel', 'cel', '09123456789', 'pending', NULL, NULL, '2026-01-24 22:13:52'),
(15, 'BR-20260124-4458', 'Ethan Magsaysay', 'Logistic-1', 'Cash', 'POGI', 6000, 'POGI', NULL, 'doc_1769267334_logo.png', 'weekly', '2026-01-30', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 23:08:54'),
(16, 'BR-20260125-2033', 'Ethan Magsaysay', 'Human Resource-1', 'Cash', 'MERALCO', 15000, 'BILLS', NULL, 'doc_1769326930_justificationexample.doc', 'weekly', '2026-02-03', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-25 15:42:10'),
(17, 'BR-20260129-4846', 'Ethan Magsaysay', 'Core-1', 'Cash', 'Transport & Training (Digital Marketing)', 10000, 'ACE - ESU', '[{\"account_code\":\"553001\",\"name\":\"Legal & Compliance\",\"category\":\"Indirect Costs\",\"subcategory\":\"Legal & Compliance\",\"amount\":2500},{\"account_code\":\"545001\",\"name\":\"Social Media Advertising\",\"category\":\"Transport & Training\",\"subcategory\":\"Digital Marketing\",\"amount\":7500}]', 'doc_1769701209_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-01-29', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-29 23:40:09'),
(18, 'BR-20260130-3052', 'Ethan Magsaysay', 'Core-2', 'Cash', 'Direct Operating Costs (Parking & Tolls)', 9000000, 'TEST - TEST', '[{\"account_code\":\"552001\",\"name\":\"Professional Services\",\"category\":\"Indirect Costs\",\"subcategory\":\"Professional Services\",\"amount\":0},{\"account_code\":\"517001\",\"name\":\"Parking & Toll Expenses\",\"category\":\"Direct Operating Costs\",\"subcategory\":\"Parking & Tolls\",\"amount\":0}]', 'doc_1769760716_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-01-30', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-30 16:11:56'),
(19, 'BR-20260201-7861', 'Ethan Magsaysay', 'Core-2', 'Cash', 'Taxes & Financial Costs (Interest Expense)', 15000, 'HR 2026 - Hoa', '[{\"account_code\":\"583001\",\"name\":\"Interest Expense\",\"category\":\"Taxes & Financial Costs\",\"subcategory\":\"Interest Expense\",\"amount\":0}]', 'doc_1769930267_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-01', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-01 15:17:47'),
(20, 'BR-20260206-6681', 'Ethan Magsaysay', 'Core-1', 'Cash', 'Direct Operating Costs (Equipment Purchase)', 20000, '2026 budget - budget', '[{\"account_code\":\"601001\",\"name\":\"Equipment Purchase\",\"category\":\"Direct Operating Costs\",\"subcategory\":\"Equipment Purchase\",\"amount\":20000}]', 'doc_1770347004_BudgetProposalSample.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 11:03:24'),
(21, 'BR-20260206-3670', 'Ethan Magsaysay', 'Core-1', 'Cash', 'Taxes & Financial Costs (Market Buffer)', 20000, 'Budget Proposal C.O - Budget', '[{\"account_code\":\"574001\",\"name\":\"Market Fluctuation Buffer\",\"category\":\"Taxes & Financial Costs\",\"subcategory\":\"Market Buffer\",\"amount\":20000}]', 'doc_1770356666_BudgetProposalSample.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 13:44:26'),
(22, 'BR-20260206-8775', 'Ethan Magsaysay', 'Core-2', 'Cash', 'Supplies & Technology (Office Supplies)', 5000, 'Supplies - Budget', '[{\"account_code\":\"554001\",\"name\":\"Office Supplies\",\"category\":\"Supplies & Technology\",\"subcategory\":\"Office Supplies\",\"amount\":5000}]', 'doc_1770383633_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 21:13:53'),
(23, 'BR-20260206-4496', 'Ethan Magsaysay', 'Core-1', 'Cash', 'Direct Operating Costs (Toll Fees)', 1200, '2026 Budget Proposal for Core 1', '[{\"account_code\":\"532001\",\"name\":\"GPS & Navigation Subscriptions\",\"category\":\"Supplies & Technology\",\"subcategory\":\"Software Subscriptions\",\"amount\":600},{\"account_code\":\"518001\",\"name\":\"Toll Road Expenses\",\"category\":\"Direct Operating Costs\",\"subcategory\":\"Toll Fees\",\"amount\":600}]', 'doc_1770388786_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 22:39:46'),
(24, 'BR-20260206-2589', 'Ethan Magsaysay', 'Human Resource-2', 'Cash', 'Supplies & Technology (Office Supplies)', 10000, 'Expenses for Utilities', '[{\"account_code\":\"554001\",\"name\":\"Office Supplies\",\"category\":\"Supplies & Technology\",\"subcategory\":\"Office Supplies\",\"amount\":10000}]', 'doc_1770389106_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 22:45:06'),
(25, 'BR-20260206-9469', 'Ethan Magsaysay', 'Human Resource-2', 'Cash', 'Transport & Training (Driver Training)', 12000, '2026 BUDGET PROPOSAL', '[{\"account_code\":\"551001\",\"name\":\"Office Operations Cost\",\"category\":\"Indirect Costs\",\"subcategory\":\"Office Operations\",\"amount\":5000},{\"account_code\":\"512001\",\"name\":\"Maintenance & Servicing\",\"category\":\"Direct Operating Costs\",\"subcategory\":\"Vehicle Maintenance\",\"amount\":3000},{\"account_code\":\"523001\",\"name\":\"Driver Training\",\"category\":\"Transport & Training\",\"subcategory\":\"Driver Training\",\"amount\":4000}]', 'doc_1770389634_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 22:53:54'),
(26, 'BR-20260206-8608', 'Ethan Magsaysay', 'Human Resource-2', 'Cash', 'Transport & Training (Driver Training)', 5000, 'TEST TITLE', '[{\"account_code\":\"523001\",\"name\":\"Driver Training\",\"category\":\"Transport & Training\",\"subcategory\":\"Driver Training\",\"amount\":5000}]', 'doc_1770390418_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 23:06:58'),
(27, 'BR-20260206-4191', 'Ethan Magsaysay', 'Core-1', 'Cash', 'Taxes & Financial Costs (Depreciation)', 2500, 'ACE', '[{\"account_code\":\"581001\",\"name\":\"Depreciation Expense\",\"category\":\"Taxes & Financial Costs\",\"subcategory\":\"Depreciation\",\"amount\":2500}]', 'doc_1770390549_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 23:09:09'),
(28, 'BR-20260206-3890', 'Ethan Magsaysay', 'Human Resource-1', 'Cash', 'Supplies & Technology (Hardware & Devices)', 6500, 'Program H2', '[{\"account_code\":\"534001\",\"name\":\"Mobile Device Expenses\",\"category\":\"Supplies & Technology\",\"subcategory\":\"Hardware & Devices\",\"amount\":6500}]', 'doc_1770390959_Budget_Proposal_ViaHale.pdf', 'weekly', '2026-02-06', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-02-06 23:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `budget_request_backup`
--

CREATE TABLE `budget_request_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(30) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` varchar(255) NOT NULL,
  `time_period` varchar(20) NOT NULL,
  `payment_due` date NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `bank_account_name` varchar(100) NOT NULL,
  `bank_account_number` varchar(255) NOT NULL,
  `ecash_provider` varchar(100) NOT NULL,
  `ecash_account_name` varchar(100) NOT NULL,
  `ecash_account_number` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_request_backup`
--

INSERT INTO `budget_request_backup` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `time_period`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `status`, `rejection_reason`, `rejected_at`, `created_at`) VALUES
(1, 'BR-20260124-6539', 'Ethan Magsaysay', 'Human Resource-1', 'Cash', 'qwe', 511515, 'qwe', '', 'weekly', '2026-01-28', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 20:52:49'),
(2, 'BR-20260124-7901', 'Ethan Magsaysay', 'Core-1', 'Cash', 'qbbsf', 2652, 'sdfv', '', 'weekly', '2026-01-26', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 20:53:29'),
(4, 'BR-20260124-4515', 'Ethan Magsaysay', 'Core-1', 'Cash', 'ace', 25000, 'ace', '', 'weekly', '2026-01-28', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:00:10'),
(1, 'BR-20260124-6539', 'Ethan Magsaysay', 'Human Resource-1', 'Cash', 'qwe', 511515, 'qwe', '', 'weekly', '2026-01-28', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 20:52:49'),
(2, 'BR-20260124-7901', 'Ethan Magsaysay', 'Core-1', 'Cash', 'qbbsf', 2652, 'sdfv', '', 'weekly', '2026-01-26', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 20:53:29'),
(4, 'BR-20260124-4515', 'Ethan Magsaysay', 'Core-1', 'Cash', 'ace', 25000, 'ace', '', 'weekly', '2026-01-28', '', '', '', '', '', '', 'pending', NULL, NULL, '2026-01-24 21:00:10');

-- --------------------------------------------------------

--
-- Table structure for table `cash`
--

CREATE TABLE `cash` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` blob NOT NULL,
  `payment_due` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash`
--

INSERT INTO `cash` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `payment_due`) VALUES
(26, 'CH-5617-2024', 'ABC SUPPLIER', 'Financial', 'Cash', 'Tax Payment', 120000, 'Dsada', 0x30, '2024-10-30'),
(50, 'CH-5214-2024', 'ABC SUPPLIER', 'Admininistrative', 'Cash', 'Tax Payment', 123333, 'Tax', 0x30, '2024-11-12'),
(59, 'CH-6129-2025', 'ABC SUPPLIER', 'Core-2', 'Cash', 'Equipment/Assets', 1233, 'Tax', 0x494e564f49434520415050524f56414c2e646f6378, '2025-03-08'),
(72, 'CH-2427-2025', 'Shine Buen', 'Human Resource-1', 'Cash', 'Bonuses', 2000, 'Flat', 0x28495445342946696e616c697a65642e646f6378, '2025-02-20'),
(73, 'CH-1614-2025', 'PhilBank', 'Financial', 'Cash', 'Tax Payment', 10000, 'Business Permit Fees', 0x414c4c4f52444f20524553554d452e706466, '2025-03-13'),
(75, 'CH-9519-2025', 'Naruto', 'Admininistrative', 'Cash', 'Facility Cost', 10000, 'Basta', '', '2025-03-30'),
(76, 'CH-165743', 'PhilBank', 'Admininistrative', 'Cash', 'Account Payable', 1000, 'Payment for invoice INV-165743', 0x637573746f64696f202831292e706466, '2025-04-23'),
(246, 'C-INV-20251015-9063', 'admin admin', 'Financial', 'Cash', 'Account Payable', 10000, 'Payment for invoice INV-20251015-9063', '', '2025-10-31'),
(26, 'CH-5617-2024', 'ABC SUPPLIER', 'Financial', 'Cash', 'Tax Payment', 120000, 'Dsada', 0x30, '2024-10-30'),
(50, 'CH-5214-2024', 'ABC SUPPLIER', 'Admininistrative', 'Cash', 'Tax Payment', 123333, 'Tax', 0x30, '2024-11-12'),
(59, 'CH-6129-2025', 'ABC SUPPLIER', 'Core-2', 'Cash', 'Equipment/Assets', 1233, 'Tax', 0x494e564f49434520415050524f56414c2e646f6378, '2025-03-08'),
(72, 'CH-2427-2025', 'Shine Buen', 'Human Resource-1', 'Cash', 'Bonuses', 2000, 'Flat', 0x28495445342946696e616c697a65642e646f6378, '2025-02-20'),
(73, 'CH-1614-2025', 'PhilBank', 'Financial', 'Cash', 'Tax Payment', 10000, 'Business Permit Fees', 0x414c4c4f52444f20524553554d452e706466, '2025-03-13'),
(75, 'CH-9519-2025', 'Naruto', 'Admininistrative', 'Cash', 'Facility Cost', 10000, 'Basta', '', '2025-03-30'),
(76, 'CH-165743', 'PhilBank', 'Admininistrative', 'Cash', 'Account Payable', 1000, 'Payment for invoice INV-165743', 0x637573746f64696f202831292e706466, '2025-04-23'),
(246, 'C-INV-20251015-9063', 'admin admin', 'Financial', 'Cash', 'Account Payable', 10000, 'Payment for invoice INV-20251015-9063', '', '2025-10-31');

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts_hierarchy`
--

CREATE TABLE `chart_of_accounts_hierarchy` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL COMMENT 'Parent account ID for hierarchy',
  `level` int(1) NOT NULL COMMENT '1=Type, 2=Category, 3=Subcategory, 4=Account',
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Asset','Liability','Equity','Revenue','Expense') DEFAULT NULL COMMENT 'Only for level 1 items',
  `description` text DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 0.00 COMMENT 'Current balance of the account',
  `allocated_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Budget allocated amount',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chart_of_accounts_hierarchy`
--

INSERT INTO `chart_of_accounts_hierarchy` (`id`, `parent_id`, `level`, `code`, `name`, `type`, `description`, `balance`, `allocated_amount`, `status`, `created_at`, `updated_at`, `is_archived`) VALUES
(1, NULL, 1, '100000', 'Assets', 'Asset', 'Resources owned by the business', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(2, NULL, 1, '200000', 'Liabilities', 'Liability', 'Obligations of the business', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(3, NULL, 1, '300000', 'Equity', 'Equity', 'Owner\'s interest in the business', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(4, NULL, 1, '400000', 'Revenue', 'Revenue', 'Income from business operations', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(5, NULL, 1, '500000', 'Expenses', 'Expense', 'Costs incurred in business operations', 62506.50, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:22:01', 0),
(6, 1, 2, '110000', 'Current Assets', 'Asset', 'Assets expected to be converted to cash within one year', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(7, 1, 2, '120000', 'Fixed Assets', 'Asset', 'Long-term tangible assets used in business operations', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(8, 1, 2, '130000', 'Technology Assets', 'Asset', 'Technology-related assets', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(9, 2, 2, '210000', 'Accounts Payable', 'Liability', 'Short-term obligations to suppliers and service providers', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(10, 2, 2, '220000', 'Accrued Liabilities', 'Liability', 'Expenses incurred but not yet paid', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(11, 2, 2, '230000', 'Long-term Liabilities', 'Liability', 'Obligations due after one year', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(12, 3, 2, '310000', 'Owner Equity', 'Equity', 'Owner\'s investment and earnings', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(13, 4, 2, '410000', 'Transportation Revenue', 'Revenue', 'Revenue from transportation services', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(14, 4, 2, '420000', 'Commission Revenue', 'Revenue', 'Revenue from commissions', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(15, 4, 2, '430000', 'Other Revenue', 'Revenue', 'Miscellaneous revenue sources', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(16, 5, 2, '510000', 'Direct Operating Costs', 'Expense', 'Expenses related to vehicle operations', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(17, 5, 2, '520000', 'Indirect Costs', 'Expense', 'Expenses related to driver compensation', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(18, 5, 2, '530000', 'Supplies & Technology', 'Expense', 'Technology-related expenses', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(19, 5, 2, '540000', 'Transport & Training', 'Expense', 'Marketing and customer acquisition expenses', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-10 08:34:23', 0),
(20, 5, 2, '550000', 'Taxes & Financial Costs', 'Expense', 'Administrative and support expenses', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(21, 5, 2, '560000', 'Deprecated - Merged with Indirect Costs', 'Expense', 'Employee-related expenses', 0.00, 0.00, 'inactive', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 1),
(22, 5, 2, '570000', 'Deprecated - Merged with Taxes & Financial', 'Expense', 'Reserves for unexpected expenses', 0.00, 0.00, 'inactive', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 1),
(23, 5, 2, '580000', 'Deprecated - Merged with Taxes & Financial', 'Expense', 'Miscellaneous operating expenses', 0.00, 0.00, 'inactive', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 1),
(24, 5, 2, '590000', 'Deprecated - Merged with Indirect Costs', 'Expense', 'Overhead and indirect expenses', 0.00, 0.00, 'inactive', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 1),
(25, 5, 2, '600000', 'Deprecated - Merged with Direct Operating', 'Expense', 'Equipment-related expenses', 0.00, 0.00, 'inactive', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 1),
(26, 5, 2, '610000', 'Deprecated - Merged with Transport & Training', 'Expense', 'Travel-related expenses', 0.00, 0.00, 'inactive', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 1),
(27, 6, 3, '111000', 'Cash & Cash Equivalents', 'Asset', 'Cash and cash-like assets', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(28, 6, 3, '112000', 'Accounts Receivable', 'Asset', 'Amounts owed by customers and drivers', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(29, 6, 3, '113000', 'Prepaid Items', 'Asset', 'Prepaid expenses and subscriptions', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(30, 7, 3, '121000', 'Vehicles & Equipment', 'Asset', 'Company-owned vehicles and equipment', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(31, 7, 3, '122000', 'Office Equipment', 'Asset', 'Office furniture and equipment', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(32, 8, 3, '131000', 'Technology Equipment', 'Asset', 'Mobile devices and hardware', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(33, 8, 3, '132000', 'Software Assets', 'Asset', 'Software licenses and applications', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(34, 9, 3, '211000', 'Supplier Payables', 'Liability', 'Amounts owed to suppliers', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(35, 9, 3, '212000', 'Service Payables', 'Liability', 'Amounts owed to service providers', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(36, 10, 3, '221000', 'Platform Payables', 'Liability', 'Commission owed to platforms', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(37, 10, 3, '222000', 'Driver Payables', 'Liability', 'Amounts owed to drivers', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(38, 10, 3, '223000', 'Employee Payables', 'Liability', 'Accrued salaries and wages', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(39, 10, 3, '224000', 'Tax Payables', 'Liability', 'Accrued taxes payable', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(40, 11, 3, '231000', 'Loans Payable', 'Liability', 'Long-term financing loans', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(41, 12, 3, '311000', 'Owner\'s Capital', 'Equity', 'Initial investment by owner', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(42, 12, 3, '312000', 'Owner\'s Drawings', 'Equity', 'Owner\'s withdrawals', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(43, 12, 3, '313000', 'Retained Earnings', 'Equity', 'Accumulated reinvested profits', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(44, 13, 3, '411000', 'Ride Fares', 'Revenue', 'Fare collection from rides', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(45, 13, 3, '412000', 'Corporate Services', 'Revenue', 'Corporate transportation contracts', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(46, 14, 3, '421000', 'Driver Commissions', 'Revenue', 'Commission from drivers', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(47, 15, 3, '431000', 'Miscellaneous Income', 'Revenue', 'Other miscellaneous income', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(48, 16, 3, '511000', 'Fuel & Energy', 'Expense', 'Fuel costs for vehicles', 877000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 13:43:31', 0),
(49, 16, 3, '512000', 'Vehicle Maintenance', 'Expense', 'Vehicle maintenance costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(50, 16, 3, '513000', 'Parts Replacement', 'Expense', 'Tire replacement costs', 150000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:02:02', 0),
(51, 16, 3, '514000', 'Vehicle Cleaning', 'Expense', 'Vehicle cleaning expenses', 123801.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 14:11:24', 0),
(52, 16, 3, '515000', 'Vehicle Insurance', 'Expense', 'Vehicle insurance costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(53, 16, 3, '516000', 'Registration & Licensing', 'Expense', 'Registration and licensing fees', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(54, 16, 3, '517000', 'Parking & Tolls', 'Expense', 'Parking expenses', 880000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:02:02', 0),
(55, 16, 3, '518000', 'Toll Fees', 'Expense', 'Toll road expenses', 880000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:02:02', 0),
(56, 17, 3, '521000', 'Driver Compensation', 'Expense', 'Commissions paid to drivers', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(57, 17, 3, '522000', 'Driver Incentives', 'Expense', 'Bonuses and incentives', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(58, 19, 3, '523000', 'Driver Training', 'Expense', 'Training programs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(59, 17, 3, '524000', 'Driver Safety Gear', 'Expense', 'Safety equipment', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(60, 17, 3, '525000', 'Driver Insurance', 'Expense', 'Health insurance costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(61, 18, 3, '531000', 'Platform Commissions', 'Expense', 'Platform commission fees', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(62, 18, 3, '532000', 'Software Subscriptions', 'Expense', 'Navigation app costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(63, 18, 3, '533000', 'Connectivity & Data', 'Expense', 'Mobile data expenses', 691790.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 13:58:45', 0),
(64, 18, 3, '534000', 'Hardware & Devices', 'Expense', 'Mobile phone costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(65, 18, 3, '535000', 'Software Licenses', 'Expense', 'Software subscription fees', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(66, 19, 3, '541000', 'Customer Acquisition', 'Expense', 'Customer acquisition marketing', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(67, 19, 3, '542000', 'Driver Acquisition', 'Expense', 'New driver bonuses', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(68, 19, 3, '543000', 'Advertising', 'Expense', 'Advertising campaigns', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(69, 19, 3, '544000', 'Referral Programs', 'Expense', 'Referral program costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(70, 19, 3, '545000', 'Digital Marketing', 'Expense', 'Social media marketing', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(71, 17, 3, '551000', 'Office Operations', 'Expense', 'Office overhead costs', 410095.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:22:01', 0),
(72, 17, 3, '552000', 'Professional Services', 'Expense', 'Consulting fees', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(73, 17, 3, '553000', 'Legal & Compliance', 'Expense', 'Legal compliance costs', 600000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 14:54:40', 0),
(74, 18, 3, '554000', 'Office Supplies', 'Expense', 'Office materials', 890000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 14:47:57', 0),
(75, 17, 3, '555000', 'Support Staff', 'Expense', 'Administrative staff salaries', 940000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:34:38', 0),
(76, 17, 3, '561000', 'Employee Compensation', 'Expense', 'Employee compensation', 238000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:14:15', 0),
(77, 17, 3, '562000', 'Payroll Processing', 'Expense', 'Payroll processing costs', 1000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:58:07', 0),
(78, 17, 3, '563000', 'Recruitment', 'Expense', 'Hiring expenses', 500000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:48:20', 0),
(79, 19, 3, '564000', 'Staff Development', 'Expense', 'Training programs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(80, 17, 3, '565000', 'HR Systems', 'Expense', 'HR software costs', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:48:20', 0),
(81, 17, 3, '566000', 'Benefits Management', 'Expense', 'Benefits management costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(82, 16, 3, '571000', 'Emergency Repairs', 'Expense', 'Unexpected repairs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(83, 16, 3, '572000', 'Accident Reserves', 'Expense', 'Accident-related reserves', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(84, 20, 3, '573000', 'Regulatory Reserves', 'Expense', 'Regulatory compliance reserves', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(85, 20, 3, '574000', 'Market Buffer', 'Expense', 'Market change buffers', 500000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:48:20', 0),
(86, 20, 3, '581000', 'Depreciation', 'Expense', 'Asset depreciation', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(87, 20, 3, '582000', 'Bank Charges', 'Expense', 'Bank service charges', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(88, 20, 3, '583000', 'Interest Expense', 'Expense', 'Loan interest expenses', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(89, 20, 3, '584000', 'Tax Payments', 'Expense', 'Business tax payments', 490000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:49:27', 0),
(90, 20, 3, '585000', 'Permits & Licenses', 'Expense', 'Permit and license fees', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(91, 17, 3, '591000', 'General Overhead', 'Expense', 'General overhead expenses', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(92, 16, 3, '601000', 'Equipment Purchase', 'Expense', 'Equipment purchases', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(93, 16, 3, '602000', 'Equipment Maintenance', 'Expense', 'Equipment maintenance', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(94, 19, 3, '611000', 'Business Travel', 'Expense', 'Travel transportation costs', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(95, 19, 3, '612000', 'Accommodation', 'Expense', 'Accommodation expenses', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(96, 19, 3, '613000', 'Training & Education', 'Expense', 'Training and development', 1000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 13:22:37', 0),
(97, 27, 4, '111001', 'Cash on Hand', 'Asset', 'Physical cash available for daily operations', 14456957.89, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-10 10:22:50', 0),
(98, 27, 4, '111002', 'Bank - BDO', 'Asset', 'BDO checking account', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-10 08:55:58', 0),
(99, 27, 4, '111003', 'Bank - Savings', 'Asset', 'Savings account for business reserves', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(100, 28, 4, '112001', 'Accounts Receivable - Drivers', 'Asset', 'Amounts owed by drivers for boundary fees', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(101, 28, 4, '112002', 'Accounts Receivable - Corporate', 'Asset', 'Amounts owed by corporate clients', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-10 09:21:05', 0),
(102, 29, 4, '113001', 'Prepaid Expenses', 'Asset', 'Prepaid insurance, licenses, and subscriptions', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(103, 30, 4, '121001', 'Service Vehicles', 'Asset', 'Company-owned vehicles for TNVS operations', 3155.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-12 05:49:51', 0),
(104, 30, 4, '121002', 'Accumulated Depreciation - Vehicles', 'Asset', 'Accumulated depreciation on service vehicles', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(105, 31, 4, '122001', 'Office Equipment', 'Asset', 'Computers, furniture, office machines', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(106, 31, 4, '122002', 'Accumulated Depreciation - Office Equipment', 'Asset', 'Accumulated depreciation on office equipment', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(107, 32, 4, '131001', 'Mobile Devices & Tablets', 'Asset', 'Mobile devices and tablets for drivers and staff', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-10 08:55:58', 0),
(108, 33, 4, '132001', 'Software Licenses', 'Asset', 'Purchased software licenses', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(109, 34, 4, '211001', 'Accounts Payable - Suppliers', 'Liability', 'Amounts owed to fuel and maintenance suppliers', 10630879.00, 10251500.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:19:55', 0),
(110, 35, 4, '212001', 'Accounts Payable - Service Providers', 'Liability', 'Amounts owed to various service providers', 30527490.00, 30503000.00, 'active', '2026-01-29 03:29:36', '2026-02-12 13:33:59', 0),
(111, 36, 4, '221001', 'Platform Commission Payable', 'Liability', 'Commission owed to ride-hailing platforms', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(112, 37, 4, '222001', 'Driver Commissions Payable', 'Liability', 'Commissions and incentives owed to drivers', 110505500.00, 110505500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 13:33:59', 0),
(113, 38, 4, '223001', 'Salaries Payable', 'Liability', 'Accrued salaries and wages to be paid', 131029401.88, 130502500.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:22:01', 0),
(114, 39, 4, '224001', 'Taxes Payable', 'Liability', 'Accrued taxes payable to government', 10535899.19, 10502505.00, 'active', '2026-01-29 03:29:36', '2026-02-12 13:33:59', 0),
(115, 40, 4, '231001', 'Vehicle Loans Payable', 'Liability', 'Long-term vehicle financing loans', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(116, 41, 4, '311001', 'Owner\'s Capital', 'Equity', 'Initial investment by business owner', 15000000.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-10 10:22:50', 0),
(117, 42, 4, '312001', 'Owner\'s Drawings', 'Equity', 'Owner\'s withdrawals from the business', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(118, 43, 4, '313001', 'Retained Earnings', 'Equity', 'Accumulated profits reinvested in the business', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(119, 44, 4, '411001', 'Transportation Revenue', 'Revenue', 'Fare collection from passenger rides', 631.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-12 05:49:51', 0),
(120, 45, 4, '412001', 'Corporate Account Revenue', 'Revenue', 'Revenue from corporate transportation contracts', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-02-10 09:21:05', 0),
(121, 46, 4, '421001', 'Platform Commission Revenue', 'Revenue', 'Commission earned from drivers', 0.00, 0.00, 'active', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(122, 47, 4, '431001', 'Other Income', 'Revenue', 'Other miscellaneous income', 0.00, 0.00, '', '2026-01-29 03:29:36', '2026-01-29 09:59:54', 0),
(123, 48, 4, '511001', 'Fuel & Energy Costs', 'Expense', 'Fuel costs for company vehicles', 10659304.00, 10631250.00, 'active', '2026-01-29 03:29:36', '2026-02-13 13:43:31', 0),
(124, 49, 4, '512001', 'Maintenance & Servicing', 'Expense', 'Regular vehicle maintenance and servicing', 11455331.00, 11388645.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:22:01', 0),
(125, 50, 4, '513001', 'Tire Replacement', 'Expense', 'Cost of tire replacement and repair', 11376913.00, 11353054.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:02:02', 0),
(126, 51, 4, '514001', 'Car Wash & Detailing', 'Expense', 'Vehicle cleaning and detailing expenses', 10503500.00, 10503500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(127, 52, 4, '515001', 'Insurance Premiums', 'Expense', 'Vehicle insurance premiums', 10505500.00, 10505500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(128, 53, 4, '516001', 'Vehicle Registration', 'Expense', 'Vehicle registration and licensing fees', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(129, 54, 4, '517001', 'Parking & Toll Expenses', 'Expense', 'Parking fees for company vehicles', 10622500.00, 10622500.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:02:02', 0),
(130, 55, 4, '518001', 'Toll Road Expenses', 'Expense', 'Highway and bridge toll fees', 10623000.00, 10623000.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:02:02', 0),
(131, 56, 4, '521001', 'Driver Payment', 'Expense', 'Commissions paid to drivers', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(132, 57, 4, '522001', 'Driver Incentives', 'Expense', 'Bonuses and incentives for drivers', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(133, 58, 4, '523001', 'Driver Training', 'Expense', 'Training programs for drivers', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(134, 59, 4, '524001', 'Driver Safety Gear', 'Expense', 'Safety equipment for drivers', 10505000.00, 10505000.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(135, 60, 4, '525001', 'Health Insurance for Drivers', 'Expense', 'Health insurance contributions for drivers', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(136, 61, 4, '531001', 'Platform Commission Fees', 'Expense', 'Fees paid to ride-hailing platforms', 10503000.00, 10503000.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(137, 62, 4, '532001', 'GPS & Navigation Subscriptions', 'Expense', 'Navigation app subscriptions', 10503250.00, 10503250.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(138, 63, 4, '533001', 'In-car Wi-Fi & Connectivity', 'Expense', 'Mobile data and connectivity expenses', 10745710.00, 10745710.00, 'active', '2026-01-29 03:29:36', '2026-02-13 13:58:45', 0),
(139, 64, 4, '534001', 'Mobile Device Expenses', 'Expense', 'Mobile phone plans and devices', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(140, 65, 4, '535001', 'Software Licenses', 'Expense', 'Software subscription fees', 10503005.00, 10503005.00, 'active', '2026-01-29 03:29:36', '2026-02-13 13:59:28', 0),
(141, 66, 4, '541001', 'Rider Acquisition Marketing', 'Expense', 'Marketing to acquire new riders', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(142, 67, 4, '542001', 'Driver Sign-up Bonuses', 'Expense', 'Bonuses for new driver sign-ups', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(143, 68, 4, '543001', 'Promotional Campaigns', 'Expense', 'Promotional and advertising campaigns', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(144, 69, 4, '544001', 'Referral Programs', 'Expense', 'Referral program expenses', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(145, 70, 4, '545001', 'Social Media Advertising', 'Expense', 'Social media marketing expenses', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(146, 71, 4, '551001', 'Office Operations Cost', 'Expense', 'Office rent and utility expenses', 11116134.00, 11102500.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:18:37', 0),
(147, 72, 4, '552001', 'Professional Services', 'Expense', 'Legal, accounting, and consulting fees', 10511400.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:19:55', 0),
(148, 73, 4, '553001', 'Legal & Compliance', 'Expense', 'Legal and regulatory compliance costs', 10915643.00, 10902500.00, 'active', '2026-01-29 03:29:36', '2026-02-13 14:54:40', 0),
(149, 74, 4, '554001', 'Office Supplies', 'Expense', 'Office supplies and materials', 10657824.00, 10612500.00, 'active', '2026-01-29 03:29:36', '2026-02-14 07:22:01', 0),
(150, 75, 4, '555001', 'Support Staff Compensation', 'Expense', 'Salaries for administrative support staff', 10587500.00, 10562500.00, 'active', '2026-01-29 03:29:36', '2026-02-14 06:04:53', 0),
(151, 76, 4, '561001', 'Employee Salaries & Benefits', 'Expense', 'Employee salaries, wages, and benefits', 11540389.73, 11264500.00, 'active', '2026-01-29 03:29:36', '2026-02-13 16:14:15', 0),
(152, 77, 4, '562001', 'Payroll Administration', 'Expense', 'Payroll processing and administration costs', 11501500.00, 11501500.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:58:07', 0),
(153, 78, 4, '563001', 'Recruitment & Hiring', 'Expense', 'Recruitment and hiring expenses', 11002500.00, 11002500.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:48:20', 0),
(154, 79, 4, '564001', 'Staff Development Programs', 'Expense', 'Employee training and development programs', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(155, 80, 4, '565001', 'HR Systems', 'Expense', 'HR software and system expenses', 11512000.00, 11512000.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:48:20', 0),
(156, 81, 4, '566001', 'Benefits Administration', 'Expense', 'Employee benefits administration costs', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(157, 82, 4, '571001', 'Emergency Repairs', 'Expense', 'Unexpected vehicle repairs', 10503000.00, 10503000.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(158, 83, 4, '572001', 'Accident Reserves', 'Expense', 'Reserves for accident-related expenses', 10505000.00, 10505000.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(159, 84, 4, '573001', 'Regulatory Changes Reserve', 'Expense', 'Reserve for regulatory changes compliance', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(160, 85, 4, '574001', 'Market Fluctuation Buffer', 'Expense', 'Buffer for market changes and competition', 11002500.00, 11002500.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:48:20', 0),
(161, 86, 4, '581001', 'Depreciation Expense', 'Expense', 'Depreciation of fixed assets', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(162, 87, 4, '582001', 'Bank Charges', 'Expense', 'Bank service charges and fees', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(163, 88, 4, '583001', 'Interest Expense', 'Expense', 'Interest on loans and financing', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(164, 89, 4, '584001', 'Business Taxes', 'Expense', 'Business tax payments', 5842638.00, 5826250.00, 'active', '2026-01-29 03:29:36', '2026-02-13 15:49:27', 0),
(165, 90, 4, '585001', 'Permit & License Fees', 'Expense', 'Business permits and license renewals', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(166, 91, 4, '591001', 'Office Rent', 'Expense', 'Office space rental costs', 2625625.00, 2625625.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(167, 91, 4, '591002', 'Utilities', 'Expense', 'Electricity, water, internet', 2625625.00, 2625625.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(168, 91, 4, '591003', 'Insurance', 'Expense', 'Business insurance premiums', 2625625.00, 2625625.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(169, 91, 4, '591004', 'Administrative Support', 'Expense', 'Administrative staff costs', 2625625.00, 2625625.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(170, 92, 4, '601001', 'Equipment Purchase', 'Expense', 'Purchase of machinery and equipment', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(171, 93, 4, '602001', 'Equipment Maintenance', 'Expense', 'Maintenance and repairs of equipment', 10503000.00, 10503000.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(172, 94, 4, '611001', 'Travel Expenses', 'Expense', 'Airfare, ground transportation', 10505653.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(173, 95, 4, '612001', 'Accommodation', 'Expense', 'Hotel and lodging expenses', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(174, 96, 4, '613001', 'Training & Development', 'Expense', 'Training courses and workshops', 10502500.00, 10502500.00, 'active', '2026-01-29 03:29:36', '2026-02-12 08:19:18', 0),
(216, 27, 4, '111004', 'Cash on Hand – Driver Settlements', NULL, NULL, 0.00, 0.00, 'active', '2026-02-07 10:02:38', '2026-02-07 10:02:38', 0),
(217, 27, 4, '111005', 'Cash in Bank – Driver Payout Account', NULL, NULL, 0.00, 0.00, 'active', '2026-02-07 10:02:38', '2026-02-07 10:02:38', 0),
(218, 28, 4, '112003', 'Driver Receivable – Cash Trip Remittances', NULL, NULL, 0.00, 0.00, 'active', '2026-02-07 10:04:10', '2026-02-07 10:04:10', 0),
(219, 9, 3, '213000', 'Driver Payables', 'Liability', NULL, 1000000.00, 0.00, 'active', '2026-02-07 10:05:20', '2026-02-08 09:25:04', 0),
(220, 219, 4, '213001', 'Driver Wallet Payable', 'Liability', NULL, 34154927.32, 34716377.32, 'active', '2026-02-07 10:05:20', '2026-02-13 11:25:16', 0),
(221, 219, 4, '213002', 'Driver Incentives / Bonus Payable', NULL, NULL, 34716377.32, 34716377.32, 'active', '2026-02-07 10:05:20', '2026-02-13 11:25:16', 0),
(222, 219, 4, '213003', 'Driver Earnings Payable', NULL, NULL, 35041287.36, 34716377.36, 'active', '2026-02-07 10:05:20', '2026-02-13 11:25:16', 0),
(223, 27, 4, '111006', 'Cash on Hand – Passenger Payments', NULL, NULL, 0.00, 0.00, 'active', '2026-02-07 10:09:37', '2026-02-07 10:09:37', 0),
(224, 27, 4, '111007', 'Cash in Bank – Passenger Collections', NULL, NULL, 0.00, 0.00, 'active', '2026-02-07 10:09:37', '2026-02-07 10:09:37', 0),
(225, 28, 4, '112004', 'Accounts Receivable – Passengers', NULL, NULL, 0.00, 0.00, 'active', '2026-02-07 10:10:55', '2026-02-07 10:10:55', 0),
(226, 89, 4, '584002', 'Payroll Tax', NULL, NULL, 5251250.00, 5251250.00, 'active', '2026-02-08 04:42:31', '2026-02-12 08:19:18', 0),
(227, 34, 4, '211002', 'Accounts Payable- Vendors', 'Liability', NULL, 10251500.00, 10251500.00, 'active', '2026-02-08 05:27:05', '2026-02-12 13:33:59', 0);

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL,
  `payment_id` varchar(50) NOT NULL,
  `passenger_name` varchar(100) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `mode_of_payment` varchar(50) NOT NULL,
  `payment_source` varchar(50) DEFAULT 'Driver Wallet',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`id`, `payment_id`, `passenger_name`, `ticket_number`, `payment_date`, `amount`, `mode_of_payment`, `payment_source`, `created_at`) VALUES
(1, 'PMT-2026-0001', 'Juan Dela Cruz', 'INV-2026-0408', '2026-02-12', 450.00, 'Credit', 'Driver Wallet', '2026-02-12 05:39:53'),
(2, 'PMT-2026-0002', 'Maria Makiling', 'INV-2026-0404', '2026-02-12', 125.50, 'Cash', 'Driver Wallet', '2026-02-12 05:39:53'),
(3, 'PMT-2026-0003', 'Rizal Mercado', 'INV-2026-0403', '2026-02-12', 890.00, 'Wallet Topup', 'Driver Wallet', '2026-02-12 05:39:53'),
(4, 'PMT-2026-0004', 'Antonio Luna', 'INV-2026-0410', '2026-02-12', 320.00, 'Credit', 'Driver Wallet', '2026-02-12 05:39:53'),
(5, 'PMT-2026-0005', 'Gabriela Silang', 'INV-2026-0412', '2026-02-12', 1500.00, 'Cash', 'Driver Wallet', '2026-02-12 05:39:53'),
(6, 'PMT-2026-0006', 'Andres Bonifacio', 'INV-2026-0415', '2026-02-12', 250.00, 'Credit', 'Driver Wallet', '2026-02-12 05:39:53'),
(7, 'PMT-2026-0007', 'Melchora Aquino', 'INV-2026-0418', '2026-02-12', 180.75, 'Cash', 'Driver Wallet', '2026-02-12 05:39:53'),
(8, 'PMT-2026-0008', 'Emilio Jacinto', 'INV-2026-0420', '2026-02-12', 540.00, 'Wallet Topup', 'Driver Wallet', '2026-02-12 05:39:53'),
(9, 'PMT-2026-0009', 'Apolinario Mabini', 'INV-2026-0422', '2026-02-12', 310.00, 'Credit', 'Driver Wallet', '2026-02-12 05:39:53'),
(10, 'PMT-2026-0010', 'Marcelo H. del Pilar', 'INV-2026-0425', '2026-02-12', 95.00, 'Cash', 'Driver Wallet', '2026-02-12 05:39:53'),
(11, 'PMT-2026-0011', 'Teresa Magbanua', 'INV-2026-0428', '2026-02-12', 1200.00, 'Credit', 'Driver Wallet', '2026-02-12 05:39:53'),
(12, 'PMT-2026-0012', 'Gregorio del Pilar', 'INV-2026-0430', '2026-02-12', 670.50, 'Cash', 'Driver Wallet', '2026-02-12 05:39:53'),
(13, 'PMT-2026-0013', 'Gomburza Triplets', 'INV-2026-0432', '2026-02-12', 440.00, 'Wallet Topup', 'Driver Wallet', '2026-02-12 05:39:53'),
(14, 'PMT-2026-0014', 'Juan Luna', 'INV-2026-0435', '2026-02-12', 210.25, 'Credit', 'Driver Wallet', '2026-02-12 05:39:53'),
(15, 'PMT-2026-0015', 'Josefa Llanes Escoda', 'INV-2026-0438', '2026-02-12', 880.00, 'Cash', 'Driver Wallet', '2026-02-12 05:39:53');

-- --------------------------------------------------------

--
-- Table structure for table `compliance_reports`
--

CREATE TABLE `compliance_reports` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `compliance_reports`
--

INSERT INTO `compliance_reports` (`id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, 'test', 'Regulations Summary:\nace: cv\ntest: test\n', '2025-10-14 16:39:52', '2025-10-14 16:39:52'),
(3, 'test', 'Regulations Summary:\nace: cv\ntest: test\nxamp: xamp\n', '2025-10-15 04:09:17', '2025-10-15 04:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `department_tokens`
--

CREATE TABLE `department_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `token_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `callback_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_tokens`
--

INSERT INTO `department_tokens` (`id`, `token`, `token_name`, `department`, `description`, `is_active`, `created_at`, `expires_at`, `last_used_at`, `usage_count`, `callback_url`) VALUES
(1, 'admin123token456', 'Administrative Main', 'Administrative', 'Primary token for Administrative department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', '2026-02-09 20:41:05', 73, NULL),
(2, 'core1token789xyz', 'Core-1 Primary', 'Core-1', 'Primary token for Core-1 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(3, 'core2tokenabc123', 'Core-2 Primary', 'Core-2', 'Primary token for Core-2 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(4, 'hr1tokendef456', 'HR-1 Main', 'Human Resource-1', 'Primary token for Human Resource-1 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(5, 'hr2tokenghi789', 'HR-2 Main', 'Human Resource-2', 'Primary token for Human Resource-2 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(6, 'hr3tokenjkl012', 'HR-3 Main', 'Human Resource-3', 'Primary token for Human Resource-3 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(7, 'hr4tokenmno345', 'HR-4 Main', 'Human Resource-4', 'Primary token for Human Resource-4 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(8, 'log1tokenpqr678', 'Logistic-1 Primary', 'Logistic-1', 'Primary token for Logistic-1 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(9, 'log2tokenstu901', 'Logistic-2 Primary', 'Logistic-2', 'Primary token for Logistic-2 department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(10, 'fintokenvwx234', 'Financial Main', 'Financial', 'Primary token for Financial department', 1, '2025-09-07 07:18:26', '2026-09-07 15:18:26', NULL, 0, NULL),
(0, 'f6e63ef203b9f8ee913733796cad7951', 'Core Vendor integration', 'Core-1', 'Core Vendor integration', 1, '2026-02-09 02:53:10', '2027-02-09 10:52:00', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dr`
--

CREATE TABLE `dr` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `invoice_id` varchar(255) DEFAULT NULL,
  `account_name` varchar(30) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` blob NOT NULL,
  `payment_due` date NOT NULL,
  `bank_account_number` varchar(25) NOT NULL,
  `bank_name` varchar(40) NOT NULL,
  `bank_account_name` varchar(100) NOT NULL,
  `ecash_provider` varchar(100) NOT NULL,
  `ecash_account_name` varchar(100) NOT NULL,
  `ecash_account_number` varchar(20) NOT NULL,
  `status` varchar(100) DEFAULT 'disbursed',
  `disbursed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `source_type` varchar(20) DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_source` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dr`
--

INSERT INTO `dr` (`id`, `reference_id`, `invoice_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `payment_due`, `bank_account_number`, `bank_name`, `bank_account_name`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `status`, `disbursed_at`, `archived`, `source_type`, `approved_by`, `approved_at`, `approval_source`) VALUES
(764, 'DR-1638-2025', NULL, 'Supplier', 'Logistic-1', 'Bank Transfer', 'Maintenance/Repair', 10000, 'car fixing', 0x3435313534363031335f313031313333393935303338333031325f383938353234373534303037373530363430335f6e2e6a7067, '2025-02-21', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:40:07', 1, NULL, NULL, NULL, NULL),
(765, 'DR-1991-2025', NULL, 'Supplier', 'Logistic-1', 'Bank Transfer', 'Maintenance/Repair', 10500, 'Car bumper', 0x6d6f6469666965645f696d6167652e706e67, '2025-02-21', '1231213131231223', 'BDO', '', '', '', '', 'disbursed', '2025-08-31 15:47:25', 1, NULL, NULL, NULL, NULL),
(766, 'DR-9188-2025', NULL, 'mr. Accountant', 'Financial', 'Bank Transfer', 'Tax Payment', 10000, 'Tax Vat', 0x3437363438353536335f3539363439383732393835363034365f383831323931303431303833383637313930365f6e2e706e67, '2025-02-20', '1923827332242123', 'BDO', '', '', '', '', 'disbursed', '2025-08-31 15:47:25', 1, NULL, NULL, NULL, NULL),
(768, 'DR-3185-2025', NULL, 'PhilBank', 'Financial', 'Cash', 'Tax Payment', 10000, 'Tax Vat', 0x30, '2025-02-27', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:47:25', 1, NULL, NULL, NULL, NULL),
(769, 'DR-1033-2025', NULL, 'BIR', 'Financial', 'Cash', 'Tax Payment', 12000, 'Income Tax', 0x30, '2025-02-28', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:47:25', 1, NULL, NULL, NULL, NULL),
(774, 'DR-165743', NULL, 'PhilBank', 'Admininistrative', 'Cash', 'Account Payable', 1000, 'Payment for invoice INV-165743', 0x637573746f64696f202831292e706466, '2025-04-23', 'undefined', 'undefined', '', '', '', '', 'disbursed', '2025-08-31 15:47:25', 1, NULL, NULL, NULL, NULL),
(777, 'DR-582811', NULL, 'Meralco Bills', 'Admininistrative', 'Cheque', 'Account Payable', 40196, 'Payment for invoice INV-582811', 0x656c65637472696369747962696c6c2e706466, '2025-04-23', 'undefined', 'undefined', '', '', '', '', 'disbursed', '2025-08-31 15:47:49', 1, NULL, NULL, NULL, NULL),
(778, 'DR-8416-2025', NULL, 'ABC SUPPLIER', 'Admininistrative', 'Bank Transfer', 'Extra', 0, 'Tax', 0x5765622053656375726974792e646f6378, '2025-04-12', '1234567889021321', 'BDO', '', '', '', '', 'disbursed', '2025-08-31 15:47:49', 1, NULL, NULL, NULL, NULL),
(779, 'DR-5410-2024', NULL, 'justine reyes', 'Human Resource-3', 'Cash', 'Extra', 123333, 'Tax', 0x43484150544552532d312d332d544e56532e646f6378, '2024-11-30', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:47:49', 1, NULL, NULL, NULL, NULL),
(780, 'DR-9695-2025', NULL, 'Supplier', 'Logistic-1', 'Bank Transfer', 'Maintenance/Repair', 38000, 'Car bumper 5', 0x656e68616e6365645f696d6167652e706e67, '2025-02-18', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:47:49', 1, NULL, NULL, NULL, NULL),
(781, 'DR-993926', NULL, 'test', 'Financial', 'Bank Transfer', 'Account Payable', 950, 'Payment for invoice INV-993926', '', '2025-08-22', '', '', '', '', '', '', 'disbursed', '2025-08-26 13:20:19', 0, NULL, NULL, NULL, NULL),
(782, 'DR-123461', NULL, 'admin 10', 'Administrative', 'Ecash', 'Account Payable', 10000, 'Payment for invoice INV-123461', 0x313735363232313233395f62696c6c2e706466, '2025-08-31', '', '', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(783, 'DR--123492', NULL, 'lily chan', 'Financial', 'Bank Transfer', 'Account Payable', 1000, 'Payment for invoice INV-123492', 0x313735363435383430325f62696c6c2e706466, '2025-09-22', 'lily chan', 'BDO', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(784, 'DR-311696', NULL, 'test 7', 'Core-1', 'Bank Transfer', 'Account Payable', 4000, 'Payment for invoice INV-311696', '', '2025-08-22', '1234567891011213', 'BDO', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(785, 'DR-557751', NULL, 'admin 3', 'Administrative', 'Ecash', 'Account Payable', 4000, 'Payment for invoice INV-557751', 0x313735353737373239325f57494e5f32303235303832305f31395f35315f30315f50726f2e6a7067, '2025-08-26', '', '', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(786, 'DR-662750', NULL, 'core2', 'Core-2', 'Ecash', 'Account Payable', 9500, 'Payment for invoice INV-662750', '', '2025-08-24', '', '', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(787, 'DR-903961', NULL, 'hr4', 'Human Resource-4', 'Cash', 'Account Payable', 8000, 'Payment for invoice INV-903961', '', '2025-08-25', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:48:52', 1, NULL, NULL, NULL, NULL),
(788, 'DR-chopper', NULL, 'Financial', 'Ecash', 'Account Payable', '1400', 0, '1756478329_bill.pdf', 0x323032352d30392d3130, '0000-00-00', '', '', '', '', '', '123495', 'disbursed', '2025-08-31 15:46:59', 1, NULL, NULL, NULL, NULL),
(789, 'DR--123493', NULL, 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 1000, 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466, '2025-09-24', '1234567891011213', 'AUB', '', '', '', '', 'disbursed', '2025-08-31 15:48:52', 1, NULL, NULL, NULL, NULL),
(790, 'DR-5068-2025', NULL, 'Shine Buen', 'Financial', 'Cash', 'Extra', 500, 'Tax', 0x4150504c494949492e646f6378, '2025-04-10', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:46:53', 1, NULL, NULL, NULL, NULL),
(791, 'DR--123493', NULL, 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 1000, 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466, '2025-09-24', '1234567891011213', 'AUB', '', '', '', '', 'disbursed', '2025-08-31 15:48:52', 1, NULL, NULL, NULL, NULL),
(792, 'DR-1757-2025', NULL, 'Juichiro', 'Financial', 'Cash', 'Maintenance/Repair', 12200, 'Tax', 0x34, '2025-02-15', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:46:30', 1, NULL, NULL, NULL, NULL),
(793, 'DR-chopper', NULL, 'Financial', 'Ecash', 'Account Payable', '4243', 0, '1756478329_bill.pdf', 0x323032352d30392d3130, '0000-00-00', '', '', '', '', '', '123495', 'disbursed', '2025-08-31 15:46:30', 1, NULL, NULL, NULL, NULL),
(794, 'DR--123493', NULL, 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 2000, 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466, '2025-09-24', '1234567891011213', 'AUB', '', '', '', '', 'disbursed', '2025-08-31 15:48:52', 1, NULL, NULL, NULL, NULL),
(795, 'DR-1385-2025', NULL, 'Santiago', 'Admininistrative', 'Cash', 'Extra', 1500, 'Tax', 0x42504d3130312e646f6378, '2025-04-05', '', '', '', '', '', '', 'disbursed', '2025-08-31 15:46:30', 1, NULL, NULL, NULL, NULL),
(796, 'DR-123501', NULL, 'franky', 'Human Resource-1', 'Ecash', 'Account Payable', 1000, 'Payment for invoice INV-123501', 0x313735363632333935375f62696c6c2e706466, '2025-09-10', '', '', '', 'Gcash', 'uranus', '12345678910111213', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(797, 'DR-INV-20250831-7070', NULL, 'test', 'Core-2', 'Ecash', 'Account Payable', 950, 'Payment for invoice INV-INV-20250831-7070', 0x313735363632383830375f62696c6c2e706466, '2025-08-31', '', '', '', 'Maya', 'test', '45430020145778122', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(798, 'DR-INV-20250831-4428', NULL, 'test', 'Financial', 'Ecash', 'Account Payable', 1000, 'Payment for invoice INV-INV-20250831-4428', 0x313735363633313633325f62696c6c2e706466, '2025-09-10', '', '', '', 'Gcash', 'test', '12345678910111213', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(799, 'DR--INV-20250831-2469', NULL, 'test', 'Logistic-2', 'Bank Transfer', 'Account Payable', 100, 'Payment for invoice INV-INV-20250831-2469', 0x313735363633333233345f62696c6c2e706466, '2025-08-31', '1234567891011213', 'AUB', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(800, 'DR--20250831-5969', NULL, 'test', 'Core-2', 'Bank Transfer', 'Account Payable', 200, 'Payment for invoice INV-20250831-5969', 0x313735363633383736385f62696c6c2e706466, '2025-08-31', '1234567891011213', 'BDO', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(801, 'DR-20250831-3107', NULL, 'test hr', 'Human Resource-2', 'Cash', 'Account Payable', 5000, 'Payment for invoice INV-20250831-3107', '', '2025-08-31', '', '', '', '', '', '', 'disbursed', '2025-08-31 11:38:02', 0, NULL, NULL, NULL, NULL),
(802, 'INV-20250831-4581', NULL, 'test', 'Financial', 'Ecash', 'Account Payable', 500, 'Payment for invoice INV-INV-20250831-4581', 0x313735363633363635375f62696c6c2e706466, '2025-09-24', '', '', '', 'Gcash', 'test', '13245678910111213', 'disbursed', '2025-09-01 09:05:01', 0, NULL, NULL, NULL, NULL),
(803, '-20250902-6532', NULL, 'budget manager', 'Financial', 'Bank Transfer', 'test', 2500, 'test', 0x313735363830363032305f62696c6c2e706466, '2025-09-23', '12345678910', 'test', '', '', '', '', 'disbursed', '2025-10-12 10:45:51', 1, NULL, NULL, NULL, NULL),
(804, '20250902-6806', NULL, 'budget manager', 'Financial', 'Ecash', 'test', 3654, 'test', 0x313735363830363138365f62696c6c2e706466, '2025-09-15', '', '', '', 'test', 'test', '12345678910', 'disbursed', '2025-09-02 09:44:23', 0, NULL, NULL, NULL, NULL),
(805, '0250902-7209', NULL, 'budget manager', 'Financial', 'Cash', 'test', 3200, 'test', 0x313735363830363333375f62696c6c2e706466, '2025-09-06', '', '', '', '', '', '', 'disbursed', '2025-09-02 09:46:20', 0, NULL, NULL, NULL, NULL),
(806, '-20250902-6749', NULL, 'budget manager', 'Financial', 'Bank Transfer', 'test', 3551, 'test', 0x313735363830363436365f62696c6c2e706466, '2025-09-26', '12354678910', 'test', '', '', '', '', 'disbursed', '2025-10-12 10:46:17', 1, NULL, NULL, NULL, NULL),
(807, 'DR--20250902-7408', NULL, 'budget manager', 'Financial', 'Bank Transfer', 'test', 3654, 'test', 0x313735363830363737355f62696c6c2e706466, '2025-09-10', '12345678910', 'test', '', '', '', '', 'disbursed', '2025-09-02 09:53:23', 0, NULL, NULL, NULL, NULL),
(808, 'DR-20250902-2433', NULL, 'budget manager', 'Financial', 'Bank Transfer', 'test', 4022, 'test', 0x313735363830363835385f62696c6c2e706466, '2025-09-16', '12345678910', 'test', '', '', '', '', 'disbursed', '2025-10-12 10:45:51', 1, NULL, NULL, NULL, NULL),
(809, 'C-20250904-9909', NULL, 'budget manager', 'Financial', 'Cash', 'Account Payable', 462, 'Payment for invoice INV-20250904-9909', '', '2025-09-24', '', '', '', '', '', '', 'disbursed', '2025-10-15 05:56:38', 0, NULL, NULL, NULL, NULL),
(810, 'C-20250904-3075', NULL, 'budget manager', 'Financial', 'Cash', 'Account Payable', 500, 'Payment for invoice INV-INV-20250904-3075', '', '2025-09-30', '', '', '', '', '', '', 'disbursed', '2025-10-15 05:57:04', 0, NULL, NULL, NULL, NULL),
(811, 'C-INV-20250831-6542', NULL, 'test', 'Administrative', 'Cash', 'Account Payable', 30, 'Payment for invoice INV-INV-20250831-6542', '', '2025-08-31', '', '', '', '', '', '', 'disbursed', '2025-10-15 06:04:18', 0, NULL, NULL, NULL, NULL),
(812, 'C-INV-20250831-6542', NULL, 'test', 'Administrative', 'Cash', 'Account Payable', 1000, 'Payment for invoice INV-INV-20250831-6542', '', '2025-08-31', '', '', '', '', '', '', 'disbursed', '2025-10-15 06:06:09', 0, NULL, NULL, NULL, NULL),
(813, 'BNK-20250831-6774', NULL, 'test', 'Core-2', 'Bank Transfer', 'Account Payable', 500, 'Payment for invoice INV-20250831-6774', '', '2025-09-03', '1234567891011213', 'BDO', 'test', '', '', '', 'disbursed', '2025-10-15 06:15:40', 0, NULL, NULL, NULL, NULL),
(814, 'BNK-20250831-6774', NULL, 'test', 'Core-2', 'Bank Transfer', 'Account Payable', 500, 'Payment for invoice INV-20250831-6774', '', '2025-09-03', '1234567891011213', 'BDO', 'test', '', '', '', 'disbursed', '2025-10-15 06:15:48', 0, NULL, NULL, NULL, NULL),
(815, 'EC-INV-20250831-1624', NULL, 'test', 'Core-1', 'Ecash', 'Account Payable', 4621, 'Payment for invoice INV-INV-20250831-1624', 0x313735363633353434395f62696c6c2e706466, '2025-08-31', '', '', '', 'Gcash', 'test', '12345678910111213', 'disbursed', '2025-10-15 06:21:58', 0, NULL, NULL, NULL, NULL),
(816, 'EC-20250831-6958', NULL, 'test', 'Logistic-2', 'Ecash', 'Account Payable', 5000, 'Payment for invoice INV-20250831-6958', 0x313735363633353033375f62696c6c2e706466, '2025-09-10', '', '', '', 'Gcash', 'test', '12345678910111213', 'disbursed', '2025-10-15 06:22:06', 0, NULL, NULL, NULL, NULL),
(817, 'BNK-424362', NULL, 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 1000, 'Payment for invoice INV-424362', '', '2025-08-25', '', '', '', '', '', '', 'disbursed', '2025-10-15 06:42:48', 0, NULL, NULL, NULL, NULL),
(818, 'EC-123487', NULL, 'nami', 'Logistic-1', 'Ecash', 'Account Payable', 15420, 'Payment for invoice INV-123487', '', '2025-09-05', '', '', '', '', '', '', 'disbursed', '2025-10-15 06:43:03', 0, NULL, NULL, NULL, NULL),
(819, 'CH-2018-2025', NULL, 'Shanks', 'None', 'Cash', 'Equipment/Assets', 1000, 'tires', 0x43484150544552532d312d332d544e56532e646f6378, '2025-02-10', '', '', '', '', '', '', 'disbursed', '2025-10-15 06:43:20', 0, NULL, NULL, NULL, NULL),
(820, 'BNK-20251015-8446', NULL, 'admin admin', 'Financial', 'Bank Transfer', 'audit fees', 50000, 'external audit preparation ', 0x313736303534333339365f3133343033313035313436373433393030392e6a7067, '2025-10-15', '1234-5678-9108', 'bdo', 'sanara c.', '', '', '', 'disbursed', '2025-10-16 06:01:23', 0, NULL, NULL, NULL, NULL),
(824, 'BNK-1136', NULL, 'test', 'Financial', 'Bank Transfer', 'Account Payable', 5, 'Payment for invoice 20250831-1136', '', '2025-08-31', '1234567891011213', 'BDO', 'test', '', '', '', 'disbursed', '2026-01-16 11:48:24', 0, 'payout', NULL, NULL, NULL),
(825, 'C-20251015-7578', NULL, 'admin admin', 'Human Resource-4', 'Cash', 'zoro', 900, 'zoro', '', '2025-10-24', '', '', '', '', '', '', 'disbursed', '2026-01-16 11:48:37', 0, 'payout', NULL, NULL, NULL),
(826, 'C-20250901-7277', NULL, 'test', 'Logistic-2', 'cash', 'test', 645, 'test', 0x313735363733373130395f62696c6c2e706466, '2025-09-01', '', '', '', '', '', '', 'disbursed', '2026-01-16 11:50:28', 0, 'payout', NULL, NULL, NULL),
(827, 'C-20250904-8893', NULL, 'budget manager', 'Financial', 'Cash', 'Account Payable', 5, 'Payment for invoice INV-20250904-8893', 0x313735363936383430335f62696c6c2e706466, '2025-09-30', '', '', '', '', '', '', 'disbursed', '2026-01-16 11:52:39', 0, 'payout', NULL, NULL, NULL),
(828, 'C-20250904-8893', NULL, 'budget manager', 'Financial', 'Cash', 'Account Payable', 250, 'Payment for invoice INV-20250904-8893', 0x313735363936383430335f62696c6c2e706466, '2025-09-30', '', '', '', '', '', '', 'disbursed', '2026-01-16 12:01:57', 0, 'payout', NULL, NULL, NULL),
(829, 'C-20250901-2715', NULL, 'test', 'Core-2', 'cash', 'test', 30, 'test', 0x313735363733363237325f62696c6c2e706466, '2025-09-23', '', '', '', '', '', '', 'disbursed', '2026-01-16 12:33:55', 0, 'payout', NULL, NULL, NULL),
(830, 'C-20251015-2558', NULL, 'admin admin', 'Human Resource-4', 'Cash', 'usop', 700, 'usop', '', '2025-10-25', '', '', '', '', '', '', 'disbursed', '2026-01-16 12:34:33', 0, 'payout', NULL, NULL, NULL),
(831, 'C-EM-20251015-7721', NULL, 'admin admin', 'Human Resource-3', 'Cash', 'mcdo', 1000, 'to eat', '', '2025-11-04', '', '', '', '', '', '', 'disbursed', '2026-01-16 12:37:45', 0, 'payout', NULL, NULL, NULL),
(832, 'C-20251015-2558', NULL, 'admin admin', 'Human Resource-4', 'Cash', 'usop', 700, 'usop', '', '2025-10-25', '', '', '', '', '', '', 'disbursed', '2026-01-16 13:05:39', 0, 'payout', NULL, NULL, NULL),
(833, 'C-20251015-4240', NULL, 'admin admin', 'Administrative', 'Cash', 'xamp', 500, 'xamp', '', '2025-10-31', '', '', '', '', '', '', 'disbursed', '2026-01-16 13:06:48', 0, 'payout', NULL, NULL, NULL),
(834, 'C-20250903-3553', NULL, 'budget manager', 'Financial', 'Cash', 'test', 5000, 'test', 0x313735363837383234305f62696c6c2e706466, '2025-09-03', '', '', '', '', '', '', 'disbursed', '2026-01-24 07:22:40', 0, 'payout', NULL, NULL, NULL),
(835, 'C-20260124-8255', NULL, 'Ethan Magsaysay', 'Human Resource-2', 'Cash', 'ACE', 5200, 'ACE', '', '2026-01-27', '', '', '', '', '', '', 'disbursed', '2026-01-24 13:59:53', 0, 'payout', NULL, NULL, NULL),
(836, 'C-VEN-INV-20251015-8788', NULL, 'admin admin', 'Core-1', 'Cash', 'Vendor Payment', 70000, 'Payment for vendor invoice INV-20251015-8788', '', '2025-10-17', '', '', '', '', '', '', 'disbursed', '2026-01-30 03:22:39', 0, 'payout', NULL, NULL, NULL),
(837, 'C-REIMB-20260130-6126', NULL, 'TEST', 'Human Resource-3', 'Cash', 'Direct Operating Costs - Emergency Repairs', 5000, 'Reimbursement: TEST', 0x313736393737313931335f4275646765745f50726f706f73616c5f56696148616c652e706466, '2026-01-30', '', '', '', '', '', '', 'disbursed', '2026-01-30 11:57:40', 0, 'payout', NULL, NULL, NULL),
(850, 'C-REIMB-20251219-1005', NULL, 'Sophia Mendoza', 'Accounts Payables', 'Cash', 'Software', 4500, '0', 0x2f75706c6f6164732f72656365697074732f736f6674776172655f313030352e706466, '2026-01-30', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:06:27', 0, 'payout', NULL, NULL, NULL),
(851, 'C-VEN-INV-20251015-8864', NULL, 'admin admin', 'Logistic-2', 'Cash', 'Vendor Payment', 1800, 'Payment for vendor invoice INV-20251015-8864', '', '2025-10-31', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:06:27', 0, 'payout', NULL, NULL, NULL),
(852, 'C-REIMB-20260128-9986', NULL, 'Juls', 'Financials', 'Cash', 'Other Expenses - Taxes', 50000, 'Reimbursement: taxes', 0x75706c6f6164732f72656365697074732f313736393538363031335f313736393532373038315f6a757374696669636174696f6e6578616d706c652e646f63, '2026-01-30', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:06:27', 0, 'payout', NULL, NULL, NULL),
(853, 'C-REIMB-20260127-3632', NULL, 'Ace', 'Financials', 'Cash', 'Other', 5000, 'Reimbursement: basta', 0x75706c6f6164732f72656365697074732f313736393532373038315f6a757374696669636174696f6e6578616d706c652e646f63, '2026-01-30', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:06:27', 0, 'payout', NULL, NULL, NULL),
(854, 'BNK-993928', NULL, 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 4000, 'Payment for invoice INV-993928', '', '2025-08-25', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:06:27', 0, 'payout', NULL, NULL, NULL),
(855, 'BNK-993927', NULL, 'test 5', 'Administrative', 'Bank Transfer', 'Account Payable', 4000, 'Payment for invoice INV-993927', '', '2025-08-25', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(856, 'C-638595', NULL, 'log1', 'Logistic-1', 'Cash', 'Account Payable', 7500, 'Payment for invoice INV-638595', '', '2025-08-27', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(857, 'BNK-638674', NULL, 'admin 2', 'Administrative', 'Bank Transfer', 'Account Payable', 20000, 'Payment for invoice INV-638674', 0x313735353737343631395f62696c6c2e706466, '2025-08-30', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(858, 'BNK-123492', NULL, 'lily chan', 'Financial', 'Bank Transfer', 'Account Payable', 2000, 'Payment for invoice INV-123492', 0x313735363435383430325f62696c6c2e706466, '2025-09-22', '1234567891011213', 'BDO', 'lily chan', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(859, 'BNK-123493', NULL, 'zoro', 'Financial', 'Bank Transfer', 'Account Payable', 4000, 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466, '2025-09-24', '1234567891011213', 'AUB', 'zoro', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(860, 'EC-123487', NULL, 'nami', 'Logistic-1', 'Ecash', 'Account Payable', 2345, 'Payment for invoice INV-123487', '', '2025-09-05', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(861, 'EC-123487', NULL, 'nami', 'Logistic-1', 'Ecash', 'Account Payable', 30000, 'Payment for invoice INV-123487', '', '2025-09-05', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(862, 'EC-123487', NULL, 'nami', 'Logistic-1', 'Ecash', 'Account Payable', 56000, 'Payment for invoice INV-123487', '', '2025-09-05', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(863, 'EC-123487', NULL, 'nami', 'Logistic-1', 'Ecash', 'Account Payable', 235, 'Payment for invoice INV-123487', '', '2025-09-05', '', '', '', '', '', '', 'disbursed', '2026-01-30 12:07:25', 0, 'payout', NULL, NULL, NULL),
(864, 'C-undefined20250904-8689', NULL, 'budget manager', 'Financial', 'Cash', 'Account Payable', 650, 'Payment for invoice undefined20250904-8689', 0x313735363938393933375f62696c6c2e706466, '2025-09-23', '', '', '', '', '', '', 'disbursed', '2026-01-31 09:24:12', 0, 'payout', NULL, NULL, NULL),
(865, 'EC-VEN-INV-20251015-9039', NULL, 'admin admin', 'Human Resource-3', 'Ecash', 'Vendor Payment', 70000, 'Payment for vendor invoice INV-20251015-9039', 0x313736303532383431335f5768697465616e64426c75654d6f6465726e4d696e696d616c697374426c616e6b50616765426f726465724134446f63756d656e742e706e67, '2025-11-18', '', '', '', 'test run', 'test run', '22444', 'disbursed', '2026-01-31 09:28:34', 0, 'payout', NULL, NULL, NULL),
(866, 'VEN-INV-20251015-8654', NULL, 'admin admin', 'Human Resource-1', 'Cash', 'Vendor Payment', 8000, 'Payment for vendor invoice INV-20251015-8654', '', '2025-10-15', '', '', '', '', '', '', 'disbursed', '2026-01-31 09:38:42', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 17:35:59', 'Payout Bulk Disburse'),
(867, 'C-INV-20251015-9694', NULL, 'admin admin', 'Administrative', 'Cash', 'Vendor Payment', 32000, 'Payment for vendor invoice INV-20251015-9694', '', '2025-10-16', '', '', '', '', '', '', 'disbursed', '2026-01-31 09:35:59', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 17:35:59', 'Payout Bulk Disburse'),
(868, 'C-20260130-9467', NULL, 'GLEN', 'Human Resource-3', 'Cash', 'Reimbursement', 4000, 'Reimbursement: HONRADO', 0x313736393737323335375f4275646765745f50726f706f73616c5f56696148616c652e706466, '2026-01-30', '', '', '', '', '', '', 'disbursed', '2026-01-31 09:36:30', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 17:36:30', 'Payout Bulk Disburse'),
(869, 'REIMB-20260130-9498', NULL, 'GLEN', 'Core-2', 'Cash', 'Reimbursement', 6000, 'Reimbursement: honrado', 0x313736393737323234365f4275646765745f50726f706f73616c5f56696148616c652e706466, '2026-01-30', '', '', '', '', '', '', 'disbursed', '2026-01-31 09:39:24', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 17:39:24', 'Payout Bulk Disburse'),
(870, 'PA-123496', NULL, 'brook', 'Financial', 'Ecash', 'Account Payable', 12345, 'Payment for invoice INV-123496', 0x313735363632313339345f62696c6c2e706466, '2025-09-30', '', '', '', '', '', '', 'disbursed', '2026-01-31 09:39:37', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 17:39:37', 'Payout Single Disburse'),
(871, 'PA-INV-20250831-5297', NULL, 'test', 'Administrative', 'Bank Transfer', 'Account Payable', 20, 'Payment for invoice INV-INV-20250831-5297', 0x313735363632393839385f62696c6c2e706466, '2025-09-01', '1234567891011213', 'AUB', 'test admin', '', '', '', 'disbursed', '2026-01-31 09:40:03', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 17:40:03', 'Payout Bulk Disburse'),
(872, 'PA-20250901-1439', NULL, 'test', 'Financial', 'cash', 'test', 1000, '0', 0x313735363732363431365f62696c6c2e706466, '2025-09-30', '', '', '', '', '', '', 'disbursed', '2026-01-31 09:40:03', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 17:40:03', 'Payout Bulk Disburse'),
(873, 'PA-123496', NULL, 'brook', 'Financial', 'Ecash', 'Account Payable', 7655, 'Payment for invoice INV-123496', 0x313735363632313339345f62696c6c2e706466, '2025-09-30', '', '', '', '', '', '', 'disbursed', '2026-01-31 11:04:33', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 19:04:33', 'Payout Bulk Disburse'),
(874, 'PA-123498', NULL, 'jinbei', 'Financial', 'Ecash', 'Account Payable', 3000, 'Payment for invoice INV-123498', 0x313735363632323938385f62696c6c2e706466, '2025-09-09', '', '', '', '', '', '', 'disbursed', '2026-01-31 11:04:33', 0, 'payout', 'Ethan Magsaysay', '2026-01-31 19:04:33', 'Payout Bulk Disburse'),
(879, 'VEN-INV-20260208-7876', NULL, 'TechFix IT Solutions', 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', 8000, 'Payment for vendor invoice INV-20260208-7876', 0x5b22313737303533303730315f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '246532102130', 'BDO', 'TechFix', '', '', '', 'disbursed', '2026-02-13 04:28:38', 0, 'payout', 'Ethan Magsaysay', '2026-02-13 12:28:38', 'Payout Single Disburse'),
(880, 'VEN-INV-20260208-7876', NULL, 'TechFix IT Solutions', 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', 8000, 'Payment for vendor invoice INV-20260208-7876', 0x5b22313737303533303730315f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '246532102130', 'BDO', 'TechFix', '', '', '', 'disbursed', '2026-02-13 04:28:49', 0, 'payout', 'Ethan Magsaysay', '2026-02-13 12:28:49', 'Payout Single Disburse'),
(881, 'PA-F29CB097', NULL, 'Liam Sebastian', 'Human Resource-1', 'Bank', 'Payroll', 22062, 'Payroll for Liam Sebastian - HR Assistant (Period: Dec 01 - Dec 15, 2025)', '', '2026-02-15', '', '', '', '', '', '', 'disbursed', '2026-02-13 11:37:14', 0, 'payout', 'Ethan Magsaysay', '2026-02-13 19:37:14', 'Payout Bulk Disburse'),
(882, 'PA-8E6E4B9A', NULL, 'Ana Royes', 'HR', 'Bank', 'Payroll', 22062, 'Payroll for Ana Royes - HR Assistant (Period: Dec 01 - Dec 15, 2025)', '', '2026-02-15', '', '', '', '', '', '', 'disbursed', '2026-02-13 11:37:14', 0, 'payout', 'Ethan Magsaysay', '2026-02-13 19:37:14', 'Payout Bulk Disburse'),
(883, 'REIMB-20260205-2112', NULL, 'Juan', 'Core-1', 'Cash', 'Reimbursement', 12000, 'Reimbursement: parts replacement', 0x313737303330333534325f696e766f6963652d56484c2d32303236303230312d373332372e706466, '2026-02-08', '', '', '', '', '', '', 'disbursed', '2026-02-13 11:37:24', 0, 'payout', 'Ethan Magsaysay', '2026-02-13 19:37:24', 'Payout Bulk Disburse'),
(884, 'D-20251111-7655', NULL, 'Lucas Matteo', 'Human Resource-3', 'Cash', 'Driver Payout', 3417, 'Weekly earnings withdrawal request', '', '2026-02-14', '', '', '', '', '', '', 'disbursed', '2026-02-14 05:54:21', 0, 'payout', 'Ethan Magsaysay', '2026-02-14 13:54:21', 'Payout Single Disburse'),
(885, 'D-20260128-6001', NULL, 'Ethan Gabriel', 'Logistic-1', 'Cash', 'Driver Payout', 2272, 'Weekly earnings withdrawal request', '', '2026-02-12', '', '', '', '', '', '', 'disbursed', '2026-02-14 05:54:37', 0, 'payout', 'Ethan Magsaysay', '2026-02-14 13:54:37', 'Payout Single Disburse'),
(886, 'DRV-D-20260213-3010', NULL, 'Alberto Garcia', 'Logistic-1', 'Bank', 'Driver Payout', 3341, 'Driver Payout for Alberto Garcia (ID: DRV-10010)', '', '2026-02-14', '', '', '', '', '', '', 'disbursed', '2026-02-14 06:04:00', 0, 'payout', 'Ethan Magsaysay', '2026-02-14 14:04:00', 'Payout Single Disburse'),
(887, 'VEN-INV-20260214-5002', NULL, 'PLDT Fibr Business', 'Core-1', 'Bank Transfer', 'Vendor Payment', 8900, 'Payment for vendor invoice INV-20260214-5002', 0x696e766f6963655f353030322e706466, '2026-02-28', '1357924680', 'Metrobank', 'PLDT Corporation', '', '', '', 'disbursed', '2026-02-14 07:50:37', 0, 'payout', 'Ethan Magsaysay', '2026-02-14 15:50:37', 'Payout Single Disburse'),
(888, 'VEN-INV-20260214-5001', NULL, 'CleanPro Services Inc.', 'Administrative', 'Bank Transfer', 'Vendor Payment', 11500, 'Payment for vendor invoice INV-20260214-5001', 0x696e766f6963655f353030312e706466, '2026-02-28', '9876543210', 'BDO', 'CleanPro Services', '', '', '', 'disbursed', '2026-02-14 07:58:34', 0, 'payout', 'Ethan Magsaysay', '2026-02-14 15:58:34', 'Payout Single Disburse');

--
-- Triggers `dr`
--
DELIMITER $$
CREATE TRIGGER `dr_id_trigger` BEFORE INSERT ON `dr` FOR EACH ROW BEGIN
    DECLARE next_id INT;

    -- Get the next numeric value
    SELECT COALESCE(MAX(CAST(SUBSTRING(id, 3) AS UNSIGNED)), 1000) + 1 INTO next_id
    FROM dr;

    -- Set the ID with the 'DR' prefix
    SET NEW.id = CONCAT('DR', next_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `driver_payouts`
--

CREATE TABLE `driver_payouts` (
  `id` int(11) NOT NULL,
  `payout_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `driver_id` varchar(50) NOT NULL,
  `driver_name` varchar(255) NOT NULL,
  `wallet_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gl_account` varchar(255) DEFAULT NULL,
  `expense_category` varchar(255) DEFAULT NULL,
  `expense_subcategory` varchar(255) DEFAULT NULL,
  `payout_type` varchar(100) DEFAULT 'Wallet Withdrawal',
  `description` text DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Paid','Rejected','Archived') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_date` datetime DEFAULT NULL,
  `paid_date` datetime DEFAULT NULL,
  `approver_notes` text DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT '',
  `bank_account_number` varchar(255) DEFAULT '',
  `bank_account_name` varchar(255) DEFAULT '',
  `ecash_provider` varchar(255) DEFAULT '',
  `ecash_account_name` varchar(255) DEFAULT '',
  `ecash_account_number` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `driver_payouts`
--

INSERT INTO `driver_payouts` (`id`, `payout_id`, `department`, `driver_id`, `driver_name`, `wallet_id`, `amount`, `gl_account`, `expense_category`, `expense_subcategory`, `payout_type`, `description`, `document`, `status`, `created_at`, `approved_date`, `paid_date`, `approver_notes`, `rejected_reason`, `approved_by`, `bank_name`, `bank_account_number`, `bank_account_name`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`) VALUES
(1, 'D-20251115-4541', 'Logistic-1', 'DRV-55772', 'Lucas Matteo', 'WALLET-55772', 3126.38, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2025-11-14 16:00:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(2, 'D-20251111-7655', 'Human Resource-3', 'DRV-45122', 'Lucas Matteo', 'WALLET-45122', 3416.73, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2025-11-10 16:00:00', '2026-02-14 12:52:55', NULL, 'Approved', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(3, 'D-20251220-8847', 'Financials', 'DRV-29951', 'Chloe Alexandra', 'WALLET-29951', 2704.52, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2025-12-19 16:00:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(4, 'D-20260128-6001', 'Logistic-1', 'DRV-52945', 'Ethan Gabriel', 'WALLET-52945', 2272.42, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-01-27 16:00:00', '2026-02-12 11:16:01', NULL, 'Approved', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(5, 'D-20260108-6492', 'Human Resource-1', 'DRV-37508', 'Olivia Grace', 'WALLET-37508', 2248.54, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-01-07 16:00:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(6, 'D-20260102-6581', 'Administrative', 'DRV-61025', 'Chloe Alexandra', 'WALLET-61025', 3425.45, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-01-01 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(7, 'D-20251007-3488', 'Human Resource-3', 'DRV-66249', 'Olivia Grace', 'WALLET-66249', 1381.61, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2025-10-06 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(8, 'D-20251019-8317', 'Human Resource-1', 'DRV-18889', 'Ethan Gabriel', 'WALLET-18889', 3631.70, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2025-10-18 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(9, 'D-20260115-0668', 'Logistic-2', 'DRV-68177', 'Lucas Matteo', 'WALLET-68177', 1013.65, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-01-14 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(10, 'D-20250901-5726', 'Core-2', 'DRV-74014', 'Emma Louise', 'WALLET-74014', 1173.18, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2025-08-31 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(11, 'D-20251006-2035', 'Human Resource-3', 'DRV-56582', 'Ethan Gabriel', 'WALLET-56582', 1824.96, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Paid', '2025-10-05 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(12, 'D-20251123-5813', 'Financials', 'DRV-77082', 'Lucas Matteo', 'WALLET-77082', 4605.24, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Paid', '2025-11-22 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(13, 'D-20251206-5301', 'Core-2', 'DRV-69484', 'Noah Alexander', 'WALLET-69484', 4551.31, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Paid', '2025-12-05 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(14, 'D-20260126-9399', 'Core-2', 'DRV-52058', 'Jacob Ryan', 'WALLET-52058', 3940.85, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Paid', '2026-01-25 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(15, 'D-20250903-7854', 'Human Resource-2', 'DRV-85886', 'Lucas Matteo', 'WALLET-85886', 1050.31, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Paid', '2025-09-02 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(16, 'D-20251117-3941', 'Human Resource-1', 'DRV-15275', 'Noah Alexander', 'WALLET-15275', 4428.99, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Rejected', '2025-11-16 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(17, 'D-20260126-8618', 'Human Resource-2', 'DRV-36031', 'Ethan Gabriel', 'WALLET-36031', 1994.04, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Rejected', '2026-01-25 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(18, 'D-20251024-7533', 'Core-1', 'DRV-87387', 'Chloe Alexandra', 'WALLET-87387', 3683.22, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Rejected', '2025-10-23 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(19, 'D-20260111-6393', 'Human Resource-4', 'DRV-93826', 'Emma Louise', 'WALLET-93826', 3434.00, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Rejected', '2026-01-10 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(20, 'D-20251021-1160', 'Human Resource-2', 'DRV-87048', 'Mason Taylor', 'WALLET-87048', 1120.31, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Rejected', '2025-10-20 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(21, 'D-20251004-9718', 'Core-2', 'DRV-31687', 'Ethan Gabriel', 'WALLET-31687', 2299.57, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Archived', '2025-10-03 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(22, 'D-20251220-0831', 'Financials', 'DRV-47996', 'Chloe Alexandra', 'WALLET-47996', 3136.93, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Archived', '2025-12-19 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(23, 'D-20251227-0362', 'Human Resource-4', 'DRV-26380', 'Olivia Grace', 'WALLET-26380', 3785.92, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Archived', '2025-12-26 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(24, 'D-20250902-4224', 'Core-2', 'DRV-28428', 'Emma Louise', 'WALLET-28428', 4518.80, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Archived', '2025-09-01 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(25, 'D-20250920-7572', 'Financials', 'DRV-31313', 'Mason Taylor', 'WALLET-31313', 2236.26, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Archived', '2025-09-19 16:00:00', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(26, 'D-20260213-3001', 'Logistic-1', 'DRV-10001', 'Marco Alvarez', 'WALLET-10001', 2850.50, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 02:00:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(27, 'D-20260213-3002', 'Logistic-2', 'DRV-10002', 'Luis Santos', 'WALLET-10002', 3125.75, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 02:15:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(28, 'D-20260213-3003', 'Core-1', 'DRV-10003', 'Ramon Cruz', 'WALLET-10003', 2680.25, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 02:30:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(29, 'D-20260213-3004', 'Human Resource-1', 'DRV-10004', 'Diego Reyes', 'WALLET-10004', 3450.00, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 02:45:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(30, 'D-20260213-3005', 'Logistic-1', 'DRV-10005', 'Miguel Torres', 'WALLET-10005', 2975.80, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 03:00:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(31, 'D-20260213-3006', 'Administrative', 'DRV-10006', 'Gabriel Flores', 'WALLET-10006', 3220.45, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 03:15:00', '2026-02-14 15:37:55', NULL, 'Approved via bulk action', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(32, 'D-20260213-3007', 'Core-2', 'DRV-10007', 'Antonio Mendoza', 'WALLET-10007', 2540.90, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 03:30:00', '2026-02-14 15:37:26', NULL, 'Approved', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(33, 'D-20260213-3008', 'Logistic-2', 'DRV-10008', 'Ricardo Gomez', 'WALLET-10008', 3685.30, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 03:45:00', '2026-02-14 13:59:14', NULL, 'Approved', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(34, 'D-20260213-3009', 'Human Resource-2', 'DRV-10009', 'Fernando Lopez', 'WALLET-10009', 2890.15, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 04:00:00', '2026-02-14 13:54:02', NULL, 'Approved', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(35, 'D-20260213-3010', 'Logistic-1', 'DRV-10010', 'Alberto Garcia', 'WALLET-10010', 3340.70, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Paid', '2026-02-13 04:15:00', '2026-02-14 13:28:03', '2026-02-14 14:04:00', 'Approved', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(55, 'D-20260213-9010', 'Logistic-1', 'DRV-10010', 'Alberto Garcia', 'WALLET-10010', 3340.70, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'sample_receipt.pdf', 'Approved', '2026-02-13 04:15:00', '2026-02-14 13:53:00', NULL, 'Approved', NULL, 'Ethan Magsaysay', '', '', '', '', '', ''),
(56, 'D-20260214-8001', 'Logistic-1', 'DRV-20001', 'Mario Santos', 'WALLET-20001', 2850.75, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_001.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(57, 'D-20260214-8002', 'Logistic-2', 'DRV-20002', 'Pedro Reyes', 'WALLET-20002', 3125.50, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_002.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(58, 'D-20260214-8003', 'Logistic-1', 'DRV-20003', 'Juan Dela Cruz', 'WALLET-20003', 2680.25, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_003.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(59, 'D-20260214-8004', 'Logistic-2', 'DRV-20004', 'Carlos Garcia', 'WALLET-20004', 3450.00, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_004.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(60, 'D-20260214-8005', 'Logistic-1', 'DRV-20005', 'Roberto Cruz', 'WALLET-20005', 2975.80, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_005.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(61, 'D-20260214-8006', 'Logistic-2', 'DRV-20006', 'Antonio Lopez', 'WALLET-20006', 3220.45, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_006.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(62, 'D-20260214-8007', 'Logistic-1', 'DRV-20007', 'Miguel Torres', 'WALLET-20007', 2540.90, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_007.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(63, 'D-20260214-8008', 'Logistic-2', 'DRV-20008', 'Jose Ramos', 'WALLET-20008', 3685.30, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_008.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(64, 'D-20260214-8009', 'Logistic-1', 'DRV-20009', 'Luis Mendoza', 'WALLET-20009', 2890.15, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_009.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(65, 'D-20260214-8010', 'Logistic-2', 'DRV-20010', 'Diego Flores', 'WALLET-20010', 3340.70, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_010.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(66, 'D-20260214-8011', 'Logistic-1', 'DRV-20011', 'Ricardo Gomez', 'WALLET-20011', 2755.50, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_011.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(67, 'D-20260214-8012', 'Logistic-2', 'DRV-20012', 'Fernando Aquino', 'WALLET-20012', 3095.25, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_012.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(68, 'D-20260214-8013', 'Logistic-1', 'DRV-20013', 'Eduardo Bautista', 'WALLET-20013', 2620.80, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_013.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(69, 'D-20260214-8014', 'Logistic-2', 'DRV-20014', 'Alberto Castillo', 'WALLET-20014', 3380.15, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_014.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(70, 'D-20260214-8015', 'Logistic-1', 'DRV-20015', 'Ramon Domingo', 'WALLET-20015', 2845.90, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_015.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(71, 'D-20260214-8016', 'Logistic-2', 'DRV-20016', 'Gabriel Santos', 'WALLET-20016', 3155.40, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_016.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(72, 'D-20260214-8017', 'Logistic-1', 'DRV-20017', 'Manuel Rivera', 'WALLET-20017', 2490.60, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_017.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(73, 'D-20260214-8018', 'Logistic-2', 'DRV-20018', 'Rodrigo Pascual', 'WALLET-20018', 3520.85, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_018.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(74, 'D-20260214-8019', 'Logistic-1', 'DRV-20019', 'Ernesto Valdez', 'WALLET-20019', 2795.30, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_019.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(75, 'D-20260214-8020', 'Logistic-2', 'DRV-20020', 'Alfredo Morales', 'WALLET-20020', 3265.70, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_020.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(76, 'D-20260214-8021', 'Logistic-1', 'DRV-20021', 'Sergio Navarro', 'WALLET-20021', 2915.45, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_021.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(77, 'D-20260214-8022', 'Logistic-2', 'DRV-20022', 'Raul Mercado', 'WALLET-20022', 3185.90, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_022.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(78, 'D-20260214-8023', 'Logistic-1', 'DRV-20023', 'Vicente Salazar', 'WALLET-20023', 2705.20, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_023.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(79, 'D-20260214-8024', 'Logistic-2', 'DRV-20024', 'Gregorio Aguilar', 'WALLET-20024', 3425.60, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_024.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(80, 'D-20260214-8025', 'Logistic-1', 'DRV-20025', 'Arturo Villanueva', 'WALLET-20025', 2835.75, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_025.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(81, 'D-20260214-8026', 'Logistic-2', 'DRV-20026', 'Benito Santiago', 'WALLET-20026', 3295.30, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_026.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(82, 'D-20260214-8027', 'Logistic-1', 'DRV-20027', 'Danilo Jimenez', 'WALLET-20027', 2575.40, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_027.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(83, 'D-20260214-8028', 'Logistic-2', 'DRV-20028', 'Enrique Robles', 'WALLET-20028', 3605.85, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_028.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(84, 'D-20260214-8029', 'Logistic-1', 'DRV-20029', 'Federico Diaz', 'WALLET-20029', 2925.50, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_029.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', ''),
(85, 'D-20260214-8030', 'Logistic-2', 'DRV-20030', 'Guillermo Ocampo', 'WALLET-20030', 3445.95, 'Liability - Driver Wallet', 'Driver Expenses', 'Wallet Withdrawal', 'Wallet Withdrawal', 'Weekly earnings withdrawal request', 'receipt_030.pdf', 'Pending', '2026-02-14 07:49:36', NULL, NULL, NULL, NULL, NULL, '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `driver_wallets`
--

CREATE TABLE `driver_wallets` (
  `id` int(11) NOT NULL,
  `driver_id` varchar(50) NOT NULL,
  `driver_name` varchar(255) NOT NULL,
  `wallet_id` varchar(100) NOT NULL,
  `balance` decimal(12,2) DEFAULT 0.00,
  `total_earned` decimal(12,2) DEFAULT 0.00,
  `total_withdrawn` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_wallets`
--

INSERT INTO `driver_wallets` (`id`, `driver_id`, `driver_name`, `wallet_id`, `balance`, `total_earned`, `total_withdrawn`, `created_at`, `updated_at`) VALUES
(1, 'DRV-55772', 'Lucas Matteo', 'WALLET-55772', 3126.38, 3126.38, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(2, 'DRV-45122', 'Lucas Matteo', 'WALLET-45122', 3416.73, 3416.73, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(3, 'DRV-29951', 'Chloe Alexandra', 'WALLET-29951', 2704.52, 2704.52, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(4, 'DRV-52945', 'Ethan Gabriel', 'WALLET-52945', 2272.42, 2272.42, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(5, 'DRV-37508', 'Olivia Grace', 'WALLET-37508', 2248.54, 2248.54, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(6, 'DRV-61025', 'Chloe Alexandra', 'WALLET-61025', 3425.45, 3425.45, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(7, 'DRV-66249', 'Olivia Grace', 'WALLET-66249', 1381.61, 1381.61, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(8, 'DRV-18889', 'Ethan Gabriel', 'WALLET-18889', 3631.70, 3631.70, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(9, 'DRV-68177', 'Lucas Matteo', 'WALLET-68177', 1013.65, 1013.65, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(10, 'DRV-74014', 'Emma Louise', 'WALLET-74014', 1173.18, 1173.18, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(11, 'DRV-56582', 'Ethan Gabriel', 'WALLET-56582', 1824.96, 1824.96, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(12, 'DRV-77082', 'Lucas Matteo', 'WALLET-77082', 4605.24, 4605.24, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(13, 'DRV-69484', 'Noah Alexander', 'WALLET-69484', 4551.31, 4551.31, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(14, 'DRV-52058', 'Jacob Ryan', 'WALLET-52058', 3940.85, 3940.85, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(15, 'DRV-85886', 'Lucas Matteo', 'WALLET-85886', 1050.31, 1050.31, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(16, 'DRV-15275', 'Noah Alexander', 'WALLET-15275', 4428.99, 4428.99, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(17, 'DRV-36031', 'Ethan Gabriel', 'WALLET-36031', 1994.04, 1994.04, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(18, 'DRV-87387', 'Chloe Alexandra', 'WALLET-87387', 3683.22, 3683.22, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(19, 'DRV-93826', 'Emma Louise', 'WALLET-93826', 3434.00, 3434.00, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(20, 'DRV-87048', 'Mason Taylor', 'WALLET-87048', 1120.31, 1120.31, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(21, 'DRV-31687', 'Ethan Gabriel', 'WALLET-31687', 2299.57, 2299.57, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(22, 'DRV-47996', 'Chloe Alexandra', 'WALLET-47996', 3136.93, 3136.93, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(23, 'DRV-26380', 'Olivia Grace', 'WALLET-26380', 3785.92, 3785.92, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(24, 'DRV-28428', 'Emma Louise', 'WALLET-28428', 4518.80, 4518.80, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57'),
(25, 'DRV-31313', 'Mason Taylor', 'WALLET-31313', 2236.26, 2236.26, 0.00, '2026-02-11 10:36:33', '2026-02-12 02:01:57');

-- --------------------------------------------------------

--
-- Table structure for table `ecash`
--

CREATE TABLE `ecash` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` varchar(255) NOT NULL,
  `payment_due` date NOT NULL,
  `ecash_provider` varchar(40) NOT NULL,
  `ecash_account_name` varchar(255) NOT NULL,
  `ecash_account_number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ecash`
--

INSERT INTO `ecash` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `payment_due`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`) VALUES
(89, 'EC-993929', 'test 6', 'Human Resource-1', 'Ecash', 'Account Payable', 900, 'Payment for invoice INV-993929', '', '2025-08-26', '', '', ''),
(93, 'EC-993929', 'test 6', 'Human Resource-1', 'Ecash', 'Account Payable', 50, 'Payment for invoice INV-993929', '', '2025-08-26', '', '', ''),
(112, 'EC-123491', 'luffy', 'Financial', 'Ecash', 'Account Payable', 2468, 'Payment for invoice INV-123491', '', '0000-00-00', '', '', ''),
(125, 'EC-123495', 'chopper', 'Financial', 'Ecash', 'Account Payable', 2000, 'Payment for invoice INV-123495', '1756478329_bill.pdf', '2025-09-10', '', '', ''),
(126, 'EC-123495', 'chopper', 'Financial', 'Ecash', 'Account Payable', 3000, 'Payment for invoice INV-123495', '1756478329_bill.pdf', '2025-09-10', '', '', ''),
(127, 'EC-123495', 'chopper', 'Financial', 'Ecash', 'Account Payable', 1000, 'Payment for invoice INV-123495', '1756478329_bill.pdf', '2025-09-10', '', '', ''),
(128, 'EC-123495', 'chopper', 'Financial', 'Ecash', 'Account Payable', 550, 'Payment for invoice INV-123495', '1756478329_bill.pdf', '2025-09-10', '', '', ''),
(129, 'EC-123495', 'chopper', 'Financial', 'Ecash', 'Account Payable', 5240, 'Payment for invoice INV-123495', '1756478329_bill.pdf', '2025-09-10', '', '', ''),
(130, 'EC-123495', 'chopper', 'Financial', 'Ecash', 'Account Payable', 4235, 'Payment for invoice INV-123495', '1756478329_bill.pdf', '2025-09-10', '', '', ''),
(131, 'EC-123495', 'chopper', 'Financial', 'Ecash', 'Account Payable', 12450, 'Payment for invoice INV-123495', '1756478329_bill.pdf', '2025-09-10', '', '', ''),
(134, 'EC-123487', 'nami', 'Logistic-1', 'Ecash', 'Account Payable', 10000, 'Payment for invoice INV-123487', '', '2025-09-05', '', '', ''),
(144, 'EC-123501', 'franky', 'Human Resource-1', 'Ecash', 'Account Payable', 10000, 'Payment for invoice INV-123501', '1756623957_bill.pdf', '2025-09-10', 'Gcash', 'uranus', '12345678910111213'),
(152, 'EC-INV-123456', 'test1', 'Financial', 'Ecash', 'Account Payable', 30, 'Payment for invoice INV-INV-123456', '', '2025-08-31', '-', '-', '-');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `employee_type` enum('Contractual','Regular') NOT NULL DEFAULT 'Regular',
  `department` varchar(100) NOT NULL,
  `base_salary` decimal(12,2) DEFAULT 0.00,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `position`, `employee_type`, `department`, `base_salary`, `hourly_rate`, `created_at`, `updated_at`) VALUES
(1, '1202501', 'Ethan Gabriel', 'HR Manager', 'Regular', 'Human Resource-1', 900000.00, 432.69, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(2, '1202502', 'Sophia Nicole', 'HR Specialist', 'Regular', 'Human Resource-2', 550000.00, 264.42, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(3, '1202503', 'Liam Sebastian', 'HR Assistant', 'Contractual', 'Human Resource-1', 600000.00, 288.46, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(4, '2202501', 'Mason Taylor', 'Logistics Manager', 'Regular', 'Logistic-1', 750000.00, 360.58, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(5, '2202502', 'Olivia Grace', 'Warehouse Officer', 'Regular', 'Logistic-2', 500000.00, 240.38, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(6, '2202503', 'Lucas Matteo', 'Delivery Assistant', 'Contractual', 'Logistic-1', 600000.00, 288.46, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(7, '3202501', 'Chloe Alexandra', 'Financial Analyst', 'Regular', 'Financials', 700000.00, 336.54, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(8, '4202501', 'Nathan James', 'Operations Lead', 'Regular', 'Core-1', 800000.00, 384.62, '2026-02-10 10:22:49', '2026-02-10 10:22:49'),
(9, 'EMP-OPS-001', 'Juan Cruz', 'Operations Staff', 'Regular', 'Core-1', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(10, 'EMP-OPS-002', 'Maria Clara', 'Operations Lead', 'Regular', 'Core-2', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(11, 'EMP-MGT-003', 'Jose Rizal', 'Manager', 'Regular', 'Administrative', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(12, 'EMP-LOG-004', 'Andres Bonifacio', 'Driver', 'Contractual', 'Logistic-1', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(13, 'EMP-LOG-005', 'Emilio Aguinaldo', 'Helper', 'Contractual', 'Logistic-2', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(14, 'EMP-HR-006', 'Gabriela Silang', 'HR Associate', 'Regular', 'Human Resource-1', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(15, 'EMP-IT-007', 'Apolinario Mabini', 'IT Support', 'Regular', 'Core-1', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(16, 'EMP-IT-008', 'Antonio Luna', 'System Admin', 'Regular', 'Core-2', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(17, 'EMP-FIN-009', 'Melchora Aquino', 'Accountant', 'Regular', 'Financials', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(18, 'EMP-ADM-010', 'Gregorio del Pilar', 'Clerk', 'Contractual', 'Administrative', 0.00, 0.00, '2026-02-13 16:35:04', '2026-02-13 16:35:04'),
(19, 'EMP-B2-001', 'Alden Richards', 'Team Lead', 'Regular', 'Core-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(20, 'EMP-B2-002', 'Maine Mendoza', 'Recruiter', 'Regular', 'Human Resource-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(21, 'EMP-B2-003', 'Dingdong Dantes', 'Admin Officer', 'Regular', 'Administrative', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(22, 'EMP-B2-004', 'Marian Rivera', 'Finance Analyst', 'Regular', 'Financials', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(23, 'EMP-B2-005', 'Vice Ganda', 'Senior Dev', 'Contractual', 'Core-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(24, 'EMP-B2-006', 'Anne Curtis', 'Dispatcher', 'Regular', 'Logistic-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(25, 'EMP-B2-007', 'Vhong Navarro', 'Driver', 'Contractual', 'Logistic-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(26, 'EMP-B2-008', 'Jhong Hilario', 'Junior Dev', 'Regular', 'Core-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(27, 'EMP-B2-009', 'Karylle Tatlonghari', 'HR Manager', 'Regular', 'Human Resource-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(28, 'EMP-B2-010', 'Teddy Corpuz', 'Support Staff', 'Contractual', 'Core-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(29, 'EMP-B2-011', 'Jugs Jugueta', 'Sys Admin', 'Regular', 'IT Support', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(30, 'EMP-B2-012', 'Ryan Bang', 'Helper', 'Contractual', 'Logistic-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(31, 'EMP-B2-013', 'Amy Perez', 'Receptionist', 'Regular', 'Administrative', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(32, 'EMP-B2-014', 'Ogie Alcasid', 'Accountant', 'Regular', 'Financials', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(33, 'EMP-B2-015', 'Regine Velasquez', 'Operations Mgr', 'Regular', 'Core-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(34, 'EMP-B2-016', 'Piolo Pascual', 'Supervisor', 'Regular', 'Core-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(35, 'EMP-B2-017', 'Catriona Gray', 'Training Officer', 'Regular', 'Human Resource-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(36, 'EMP-B2-018', 'Pia Wurtzbach', 'Brand Manager', 'Regular', 'Marketing', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(37, 'EMP-B2-019', 'Manny Pacquiao', 'Logistics Head', 'Regular', 'Logistic-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(38, 'EMP-B2-020', 'Jinkee Pacquiao', 'Payroll Officer', 'Regular', 'Financials', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(39, 'EMP-B2-021', 'Coco Martin', 'Field Officer', 'Regular', 'Core-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(40, 'EMP-B2-022', 'Julia Montes', 'Data Analyst', 'Regular', 'Core-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(41, 'EMP-B2-023', 'Kathryn Bernardo', 'Secretary', 'Contractual', 'Administrative', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(42, 'EMP-B2-024', 'Daniel Padilla', 'Rider', 'Contractual', 'Logistic-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(43, 'EMP-B2-025', 'Liza Soberano', 'HR Assistant', 'Regular', 'Human Resource-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(44, 'EMP-B2-026', 'Enrique Gil', 'Network Eng', 'Regular', 'IT Support', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(45, 'EMP-B2-027', 'Joshua Garcia', 'Encoder', 'Contractual', 'Core-1', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(46, 'EMP-B2-028', 'Janella Salvador', 'Tester', 'Regular', 'Core-2', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(47, 'EMP-B2-029', 'Bea Alonzo', 'Auditor', 'Regular', 'Financials', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(48, 'EMP-B2-030', 'John Lloyd Cruz', 'Liaison', 'Regular', 'Administrative', 0.00, 0.00, '2026-02-14 07:46:25', '2026-02-14 07:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `error_detection_logs`
--

CREATE TABLE `error_detection_logs` (
  `id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `error_type` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `severity` enum('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'MEDIUM',
  `status` enum('Detected','Investigating','Resolved','Ignored') DEFAULT 'Detected',
  `detected_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_submissions`
--

CREATE TABLE `expense_submissions` (
  `id` int(11) NOT NULL,
  `report_title` varchar(255) DEFAULT NULL,
  `report_period` varchar(100) DEFAULT NULL,
  `report_description` text DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `prepared_by` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `expense_details` text DEFAULT NULL,
  `report_type` enum('individual','batch') DEFAULT NULL,
  `month` varchar(255) DEFAULT NULL,
  `year` varchar(255) DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_submissions`
--

INSERT INTO `expense_submissions` (`id`, `report_title`, `report_period`, `report_description`, `total_amount`, `prepared_by`, `department`, `expense_details`, `report_type`, `month`, `year`, `status`, `submitted_at`, `updated_at`) VALUES
(1, 'M11 2025 Expense Report', 'November 2025', 'ViaHale expenses on the month of November, Year 2025', 983000.00, 'Ruby Chan', 'Financial', '[]', 'batch', '2025-11-01', '2025-11-30', 'draft', '2025-09-23 03:51:38', '2025-09-23 04:13:31'),
(2, 'M825 HR1 Expense Report', 'august 2025', 'hr1 monthly payroll expense', 0.00, 'Ruby Chan', 'Human Resource-1', '[]', 'individual', 'all', 'all', '', '2025-09-23 07:05:58', '2025-09-23 12:41:31'),
(3, 'M825 HR1 Expense Report', 'august 2025', 'hr1 monthly payroll expense', 0.00, 'Ruby Chan', 'Financials', '[]', 'individual', 'all', 'all', '', '2025-09-23 07:22:33', '2025-09-23 12:41:21'),
(4, '2025 Expense Report', 'January-December 2025', '', 1010000.00, 'Ruby Chan', 'Human Resource-1', '[{\"id\":\"EXP-2025-001\",\"department\":\"Human Resource-1\",\"category\":\"Salaries\",\"description\":\"Monthly salary payment\",\"amount\":\"u20b1 100,000.00\",\"date\":\"2025-01-15\"},{\"id\":\"EXP-2025-002\",\"department\":\"Core-1\",\"category\":\"Equipments/Assets\",\"description\":\"New server purchase\",\"amount\":\"u20b1 150,000.00\",\"date\":\"2025-01-20\"},{\"id\":\"EXP-2025-003\",\"department\":\"Administrative\",\"category\":\"Facility Cost\",\"description\":\"Office rent payment\",\"amount\":\"u20b1 200,000.00\",\"date\":\"2025-02-05\"},{\"id\":\"EXP-2025-004\",\"department\":\"Logistics-1\",\"category\":\"Maintenance/Repair\",\"description\":\"Vehicle maintenance\",\"amount\":\"u20b1 30,000.00\",\"date\":\"2025-02-10\"},{\"id\":\"EXP-2025-005\",\"department\":\"Human Resource-2\",\"category\":\"Training Cost\",\"description\":\"Employee skills development\",\"amount\":\"u20b1 25,000.00\",\"date\":\"2025-03-15\"},{\"id\":\"EXP-2025-006\",\"department\":\"Human Resource-1\",\"category\":\"Salaries\",\"description\":\"Monthly salary payment\",\"amount\":\"u20b1 100,000.00\",\"date\":\"2025-03-20\"},{\"id\":\"EXP-2025-007\",\"department\":\"Core-1\",\"category\":\"Equipments/Assets\",\"description\":\"New server purchase\",\"amount\":\"u20b1 150,000.00\",\"date\":\"2025-04-05\"},{\"id\":\"EXP-2025-008\",\"department\":\"Administrative\",\"category\":\"Facility Cost\",\"description\":\"Office rent payment\",\"amount\":\"u20b1 200,000.00\",\"date\":\"2025-04-10\"},{\"id\":\"EXP-2025-009\",\"department\":\"Logistics-1\",\"category\":\"Maintenance/Repair\",\"description\":\"Vehicle maintenance\",\"amount\":\"u20b1 30,000.00\",\"date\":\"2025-05-15\"},{\"id\":\"EXP-2025-010\",\"department\":\"Human Resource-2\",\"category\":\"Training Cost\",\"description\":\"Employee skills development\",\"amount\":\"u20b1 25,000.00\",\"date\":\"2025-05-20\"}]', 'individual', '8', '2025', '', '2025-09-23 12:41:00', '2025-09-27 09:55:58');

-- --------------------------------------------------------

--
-- Table structure for table `general_ledger`
--

CREATE TABLE `general_ledger` (
  `id` int(11) NOT NULL,
  `gl_account_id` int(11) NOT NULL,
  `gl_account_code` varchar(20) NOT NULL,
  `gl_account_name` varchar(100) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `transaction_date` datetime NOT NULL,
  `journal_entry_id` int(11) NOT NULL COMMENT 'FK to journal_entries',
  `reference_id` varchar(50) NOT NULL COMMENT 'Journal Entry Number (JE-1, JE-2, etc.)',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'vendor_invoice, reimbursement, etc.',
  `original_reference` varchar(100) DEFAULT NULL COMMENT 'Original transaction ID (INV-XXX, REIMB-XXX, etc.)',
  `description` text DEFAULT NULL,
  `debit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `running_balance` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Calculated balance',
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `general_ledger`
--

INSERT INTO `general_ledger` (`id`, `gl_account_id`, `gl_account_code`, `gl_account_name`, `account_type`, `transaction_date`, `journal_entry_id`, `reference_id`, `reference_type`, `original_reference`, `description`, `debit_amount`, `credit_amount`, `running_balance`, `department`, `created_at`) VALUES
(1, 97, '111001', 'Cash on Hand', 'Asset', '2025-09-01 08:00:00', 1, 'OB-2025', 'other', 'JE-1', 'Initial Capital Setup', 15000000.00, 0.00, 14974497.00, NULL, '2026-02-10 10:22:50'),
(2, 116, '311001', 'Owner\'s Capital', 'Equity', '2025-09-01 08:00:00', 1, 'OB-2025', 'other', 'JE-1', 'Initial Capital Setup', 0.00, 15000000.00, 15000000.00, NULL, '2026-02-10 10:22:50'),
(3, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-11-16 00:00:00', 2, 'INV-20251116-5498', 'vendor_invoice', 'JE-2', 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', 12229.00, 0.00, 33231.00, NULL, '2026-02-10 10:22:50'),
(4, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-11-16 00:00:00', 2, 'INV-20251116-5498', 'vendor_invoice', 'JE-2', 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', 0.00, 12229.00, 129741.00, NULL, '2026-02-10 10:22:50'),
(5, 148, '553001', 'Legal & Compliance', 'Expense', '2025-12-05 00:00:00', 3, 'INV-20251205-0268', 'vendor_invoice', 'JE-3', 'Acquisition for vendor_invoice: Legal & Compliance (pending)', 13034.00, 0.00, 13034.00, NULL, '2026-02-10 10:22:50'),
(6, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-12-05 00:00:00', 3, 'INV-20251205-0268', 'vendor_invoice', 'JE-3', 'Acquisition for vendor_invoice: Legal & Compliance (pending)', 0.00, 13034.00, 152467.00, NULL, '2026-02-10 10:22:50'),
(7, 149, '554001', 'Office Supplies', 'Expense', '2026-01-06 00:00:00', 4, 'INV-20260106-4762', 'vendor_invoice', 'JE-4', 'Acquisition for vendor_invoice: Office Supplies (pending)', 19217.00, 0.00, 19217.00, NULL, '2026-02-10 10:22:50'),
(8, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-06 00:00:00', 4, 'INV-20260106-4762', 'vendor_invoice', 'JE-4', 'Acquisition for vendor_invoice: Office Supplies (pending)', 0.00, 19217.00, 205434.00, NULL, '2026-02-10 10:22:50'),
(9, 125, '513001', 'Tire Replacement', 'Expense', '2025-09-03 00:00:00', 5, 'INV-20250903-6865', 'vendor_invoice', 'JE-5', 'Acquisition for vendor_invoice: Tire Replacement (pending)', 18163.00, 0.00, 18163.00, NULL, '2026-02-10 10:22:50'),
(10, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-09-03 00:00:00', 5, 'INV-20250903-6865', 'vendor_invoice', 'JE-5', 'Acquisition for vendor_invoice: Tire Replacement (pending)', 0.00, 18163.00, 18163.00, NULL, '2026-02-10 10:22:50'),
(11, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-11-05 00:00:00', 6, 'INV-20251105-3449', 'vendor_invoice', 'JE-6', 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', 15602.00, 0.00, 15602.00, NULL, '2026-02-10 10:22:50'),
(12, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-11-05 00:00:00', 6, 'INV-20251105-3449', 'vendor_invoice', 'JE-6', 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', 0.00, 15602.00, 112112.00, NULL, '2026-02-10 10:22:50'),
(13, 125, '513001', 'Tire Replacement', 'Expense', '2025-09-16 00:00:00', 7, 'INV-20250916-5922', 'vendor_invoice', 'JE-7', 'Acquisition for vendor_invoice: Tire Replacement (approved)', 24769.00, 0.00, 42932.00, NULL, '2026-02-10 10:22:50'),
(14, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-09-16 00:00:00', 7, 'INV-20250916-5922', 'vendor_invoice', 'JE-7', 'Acquisition for vendor_invoice: Tire Replacement (approved)', 0.00, 24769.00, 42932.00, NULL, '2026-02-10 10:22:50'),
(15, 148, '553001', 'Legal & Compliance', 'Expense', '2025-09-22 00:00:00', 8, 'INV-20250922-1525', 'vendor_invoice', 'JE-8', 'Acquisition for vendor_invoice: Legal & Compliance (approved)', 9442.00, 0.00, 9442.00, NULL, '2026-02-10 10:22:50'),
(16, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-09-22 00:00:00', 8, 'INV-20250922-1525', 'vendor_invoice', 'JE-8', 'Acquisition for vendor_invoice: Legal & Compliance (approved)', 0.00, 9442.00, 52374.00, NULL, '2026-02-10 10:22:50'),
(17, 123, '511001', 'Fuel & Energy Costs', 'Expense', '2025-12-13 00:00:00', 9, 'INV-20251213-3259', 'vendor_invoice', 'JE-9', 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', 20411.00, 0.00, 28054.00, NULL, '2026-02-10 10:22:50'),
(18, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-12-13 00:00:00', 9, 'INV-20251213-3259', 'vendor_invoice', 'JE-9', 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', 0.00, 20411.00, 180521.00, NULL, '2026-02-10 10:22:50'),
(19, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-11-11 00:00:00', 10, 'INV-20251111-9562', 'vendor_invoice', 'JE-10', 'Acquisition for vendor_invoice: Maintenance & Servicing (approved)', 5400.00, 0.00, 21002.00, NULL, '2026-02-10 10:22:50'),
(20, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-11-11 00:00:00', 10, 'INV-20251111-9562', 'vendor_invoice', 'JE-10', 'Acquisition for vendor_invoice: Maintenance & Servicing (approved)', 0.00, 5400.00, 117512.00, NULL, '2026-02-10 10:22:50'),
(21, 123, '511001', 'Fuel & Energy Costs', 'Expense', '2025-12-08 00:00:00', 11, 'INV-20251208-3297', 'vendor_invoice', 'JE-11', 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', 7643.00, 0.00, 7643.00, NULL, '2026-02-10 10:22:50'),
(22, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-12-08 00:00:00', 11, 'INV-20251208-3297', 'vendor_invoice', 'JE-11', 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', 0.00, 7643.00, 160110.00, NULL, '2026-02-10 10:22:50'),
(23, 164, '584001', 'Business Taxes', 'Expense', '2026-01-20 00:00:00', 12, 'INV-20260120-4565', 'vendor_invoice', 'JE-12', 'Acquisition for vendor_invoice: Business Taxes (rejected)', 16388.00, 0.00, 16388.00, NULL, '2026-02-10 10:22:50'),
(24, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-20 00:00:00', 12, 'INV-20260120-4565', 'vendor_invoice', 'JE-12', 'Acquisition for vendor_invoice: Business Taxes (rejected)', 0.00, 16388.00, 228542.00, NULL, '2026-02-10 10:22:50'),
(25, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-01-13 00:00:00', 13, 'INV-20260113-8776', 'vendor_invoice', 'JE-13', 'Acquisition for vendor_invoice: Maintenance & Servicing (rejected)', 6720.00, 0.00, 14989.00, NULL, '2026-02-10 10:22:50'),
(26, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-13 00:00:00', 13, 'INV-20260113-8776', 'vendor_invoice', 'JE-13', 'Acquisition for vendor_invoice: Maintenance & Servicing (rejected)', 0.00, 6720.00, 212154.00, NULL, '2026-02-10 10:22:50'),
(27, 125, '513001', 'Tire Replacement', 'Expense', '2026-01-01 00:00:00', 14, 'INV-20260101-4424', 'vendor_invoice', 'JE-14', 'Acquisition for vendor_invoice: Tire Replacement (rejected)', 5696.00, 0.00, 5696.00, NULL, '2026-02-10 10:22:50'),
(28, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-01 00:00:00', 14, 'INV-20260101-4424', 'vendor_invoice', 'JE-14', 'Acquisition for vendor_invoice: Tire Replacement (rejected)', 0.00, 5696.00, 186217.00, NULL, '2026-02-10 10:22:50'),
(29, 148, '553001', 'Legal & Compliance', 'Expense', '2026-01-28 00:00:00', 15, 'INV-20260128-1784', 'vendor_invoice', 'JE-15', 'Acquisition for vendor_invoice: Legal & Compliance (rejected)', 13143.00, 0.00, 13143.00, NULL, '2026-02-10 10:22:50'),
(30, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-28 00:00:00', 15, 'INV-20260128-1784', 'vendor_invoice', 'JE-15', 'Acquisition for vendor_invoice: Legal & Compliance (rejected)', 0.00, 13143.00, 241685.00, NULL, '2026-02-10 10:22:50'),
(31, 123, '511001', 'Fuel & Energy Costs', 'Expense', '2025-09-27 00:00:00', 16, 'INV-20250927-4380', 'vendor_invoice', 'JE-16', 'Acquisition for vendor_invoice: Fuel & Energy Costs (rejected)', 10863.00, 0.00, 10863.00, NULL, '2026-02-10 10:22:50'),
(32, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-09-27 00:00:00', 16, 'INV-20250927-4380', 'vendor_invoice', 'JE-16', 'Acquisition for vendor_invoice: Fuel & Energy Costs (rejected)', 0.00, 10863.00, 63237.00, NULL, '2026-02-10 10:22:50'),
(33, 146, '551001', 'Office Operations Cost', 'Expense', '2025-11-02 00:00:00', 17, 'INV-20251102-3083', 'vendor_invoice', 'JE-17', 'Acquisition for vendor_invoice: Office Operations Cost (archived)', 5460.00, 0.00, 5460.00, NULL, '2026-02-10 10:22:50'),
(34, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-11-02 00:00:00', 17, 'INV-20251102-3083', 'vendor_invoice', 'JE-17', 'Acquisition for vendor_invoice: Office Operations Cost (archived)', 0.00, 5460.00, 96510.00, NULL, '2026-02-10 10:22:50'),
(35, 125, '513001', 'Tire Replacement', 'Expense', '2025-10-02 00:00:00', 18, 'INV-20251002-8028', 'vendor_invoice', 'JE-18', 'Acquisition for vendor_invoice: Tire Replacement (archived)', 5153.00, 0.00, 5153.00, NULL, '2026-02-10 10:22:50'),
(36, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-10-02 00:00:00', 18, 'INV-20251002-8028', 'vendor_invoice', 'JE-18', 'Acquisition for vendor_invoice: Tire Replacement (archived)', 0.00, 5153.00, 68390.00, NULL, '2026-02-10 10:22:50'),
(37, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-12-03 00:00:00', 19, 'INV-20251203-6849', 'vendor_invoice', 'JE-19', 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', 9692.00, 0.00, 9692.00, NULL, '2026-02-10 10:22:50'),
(38, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-12-03 00:00:00', 19, 'INV-20251203-6849', 'vendor_invoice', 'JE-19', 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', 0.00, 9692.00, 139433.00, NULL, '2026-02-10 10:22:50'),
(39, 123, '511001', 'Fuel & Energy Costs', 'Expense', '2025-10-03 00:00:00', 20, 'INV-20251003-1812', 'vendor_invoice', 'JE-20', 'Acquisition for vendor_invoice: Fuel & Energy Costs (archived)', 5341.00, 0.00, 5341.00, NULL, '2026-02-10 10:22:50'),
(40, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-10-03 00:00:00', 20, 'INV-20251003-1812', 'vendor_invoice', 'JE-20', 'Acquisition for vendor_invoice: Fuel & Energy Costs (archived)', 0.00, 5341.00, 73731.00, NULL, '2026-02-10 10:22:50'),
(41, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-10-10 00:00:00', 21, 'INV-20251010-5860', 'vendor_invoice', 'JE-21', 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', 17319.00, 0.00, 17319.00, NULL, '2026-02-10 10:22:50'),
(42, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-10-10 00:00:00', 21, 'INV-20251010-5860', 'vendor_invoice', 'JE-21', 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', 0.00, 17319.00, 91050.00, NULL, '2026-02-10 10:22:50'),
(43, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-01-15 00:00:00', 22, 'INV-20260115-4276', 'vendor_invoice', 'JE-22', 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', 9456.00, 0.00, 24445.00, NULL, '2026-02-10 10:22:50'),
(44, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-15 00:00:00', 22, 'INV-20260115-4276', 'vendor_invoice', 'JE-22', 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', 0.00, 9456.00, 221610.00, NULL, '2026-02-10 10:22:50'),
(45, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-15 00:00:00', 23, 'INV-20260115-4276', 'payment', 'JE-23', 'Cash Payment for vendor_invoice: INV-20260115-4276', 9456.00, 0.00, 212154.00, NULL, '2026-02-10 10:22:50'),
(46, 97, '111001', 'Cash on Hand', 'Asset', '2026-01-15 00:00:00', 23, 'INV-20260115-4276', 'payment', 'JE-23', 'Cash Payment for vendor_invoice: INV-20260115-4276', 0.00, 9456.00, 14508842.89, NULL, '2026-02-10 10:22:50'),
(47, 125, '513001', 'Tire Replacement', 'Expense', '2025-12-18 00:00:00', 24, 'INV-20251218-8743', 'vendor_invoice', 'JE-24', 'Acquisition for vendor_invoice: Tire Replacement (paid)', 23159.00, 0.00, 23159.00, NULL, '2026-02-10 10:22:50'),
(48, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-12-18 00:00:00', 24, 'INV-20251218-8743', 'vendor_invoice', 'JE-24', 'Acquisition for vendor_invoice: Tire Replacement (paid)', 0.00, 23159.00, 203680.00, NULL, '2026-02-10 10:22:50'),
(49, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-12-18 00:00:00', 25, 'INV-20251218-8743', 'payment', 'JE-25', 'Cash Payment for vendor_invoice: INV-20251218-8743', 23159.00, 0.00, 180521.00, NULL, '2026-02-10 10:22:50'),
(50, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-18 00:00:00', 25, 'INV-20251218-8743', 'payment', 'JE-25', 'Cash Payment for vendor_invoice: INV-20251218-8743', 0.00, 23159.00, 14611592.43, NULL, '2026-02-10 10:22:50'),
(51, 148, '553001', 'Legal & Compliance', 'Expense', '2025-11-30 00:00:00', 26, 'INV-20251130-6356', 'vendor_invoice', 'JE-26', 'Acquisition for vendor_invoice: Legal & Compliance (paid)', 21555.00, 0.00, 21555.00, NULL, '2026-02-10 10:22:50'),
(52, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-11-30 00:00:00', 26, 'INV-20251130-6356', 'vendor_invoice', 'JE-26', 'Acquisition for vendor_invoice: Legal & Compliance (paid)', 0.00, 21555.00, 151296.00, NULL, '2026-02-10 10:22:50'),
(53, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-11-30 00:00:00', 27, 'INV-20251130-6356', 'payment', 'JE-27', 'Cash Payment for vendor_invoice: INV-20251130-6356', 21555.00, 0.00, 129741.00, NULL, '2026-02-10 10:22:50'),
(54, 97, '111001', 'Cash on Hand', 'Asset', '2025-11-30 00:00:00', 27, 'INV-20251130-6356', 'payment', 'JE-27', 'Cash Payment for vendor_invoice: INV-20251130-6356', 0.00, 21555.00, 14785484.09, NULL, '2026-02-10 10:22:50'),
(55, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-01-11 00:00:00', 28, 'INV-20260111-5925', 'vendor_invoice', 'JE-28', 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', 8269.00, 0.00, 8269.00, NULL, '2026-02-10 10:22:50'),
(56, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-11 00:00:00', 28, 'INV-20260111-5925', 'vendor_invoice', 'JE-28', 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', 0.00, 8269.00, 213703.00, NULL, '2026-02-10 10:22:50'),
(57, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-01-11 00:00:00', 29, 'INV-20260111-5925', 'payment', 'JE-29', 'Cash Payment for vendor_invoice: INV-20260111-5925', 8269.00, 0.00, 205434.00, NULL, '2026-02-10 10:22:50'),
(58, 97, '111001', 'Cash on Hand', 'Asset', '2026-01-11 00:00:00', 29, 'INV-20260111-5925', 'payment', 'JE-29', 'Cash Payment for vendor_invoice: INV-20260111-5925', 0.00, 8269.00, 14518298.89, NULL, '2026-02-10 10:22:50'),
(59, 123, '511001', 'Fuel & Energy Costs', 'Expense', '2025-10-05 00:00:00', 30, 'INV-20251005-6511', 'vendor_invoice', 'JE-30', 'Acquisition for vendor_invoice: Fuel & Energy Costs (paid)', 13475.00, 0.00, 18816.00, NULL, '2026-02-10 10:22:50'),
(60, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-10-05 00:00:00', 30, 'INV-20251005-6511', 'vendor_invoice', 'JE-30', 'Acquisition for vendor_invoice: Fuel & Energy Costs (paid)', 0.00, 13475.00, 87206.00, NULL, '2026-02-10 10:22:50'),
(61, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2025-10-05 00:00:00', 31, 'INV-20251005-6511', 'payment', 'JE-31', 'Cash Payment for vendor_invoice: INV-20251005-6511', 13475.00, 0.00, 73731.00, NULL, '2026-02-10 10:22:50'),
(62, 97, '111001', 'Cash on Hand', 'Asset', '2025-10-05 00:00:00', 31, 'INV-20251005-6511', 'payment', 'JE-31', 'Cash Payment for vendor_invoice: INV-20251005-6511', 0.00, 13475.00, 14929092.00, NULL, '2026-02-10 10:22:50'),
(63, 172, '611001', 'Travel Expenses', 'Expense', '2025-11-18 00:00:00', 32, 'REIM-20251118-8974', 'reimbursement', 'JE-32', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 4770.00, 0.00, 4770.00, NULL, '2026-02-10 10:22:50'),
(64, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-11-18 00:00:00', 32, 'REIM-20251118-8974', 'reimbursement', 'JE-32', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 0.00, 4770.00, 16689.00, NULL, '2026-02-10 10:22:50'),
(65, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-09-11 00:00:00', 33, 'REIM-20250911-1515', 'reimbursement', 'JE-33', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', 1075.00, 0.00, 7416.00, NULL, '2026-02-10 10:22:50'),
(66, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-11 00:00:00', 33, 'REIM-20250911-1515', 'reimbursement', 'JE-33', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', 0.00, 1075.00, 1075.00, NULL, '2026-02-10 10:22:50'),
(67, 172, '611001', 'Travel Expenses', 'Expense', '2025-09-19 00:00:00', 34, 'REIM-20250919-9460', 'reimbursement', 'JE-34', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 1214.00, 0.00, 1214.00, NULL, '2026-02-10 10:22:50'),
(68, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-19 00:00:00', 34, 'REIM-20250919-9460', 'reimbursement', 'JE-34', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 0.00, 1214.00, 2289.00, NULL, '2026-02-10 10:22:50'),
(69, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-11-26 00:00:00', 35, 'REIM-20251126-3644', 'reimbursement', 'JE-35', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', 4480.00, 0.00, 37711.00, NULL, '2026-02-10 10:22:50'),
(70, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-11-26 00:00:00', 35, 'REIM-20251126-3644', 'reimbursement', 'JE-35', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', 0.00, 4480.00, 21169.00, NULL, '2026-02-10 10:22:50'),
(71, 172, '611001', 'Travel Expenses', 'Expense', '2025-10-02 00:00:00', 36, 'REIM-20251002-1083', 'reimbursement', 'JE-36', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 3572.00, 0.00, 3572.00, NULL, '2026-02-10 10:22:50'),
(72, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-02 00:00:00', 36, 'REIM-20251002-1083', 'reimbursement', 'JE-36', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 0.00, 3572.00, 5861.00, NULL, '2026-02-10 10:22:50'),
(73, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-09-10 00:00:00', 37, 'REIM-20250910-5879', 'reimbursement', 'JE-37', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Approved)', 3824.00, 0.00, 6341.00, NULL, '2026-02-10 10:22:50'),
(74, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-10 00:00:00', 37, 'REIM-20250910-5879', 'reimbursement', 'JE-37', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Approved)', 0.00, 3824.00, 3824.00, NULL, '2026-02-10 10:22:50'),
(75, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-10 00:00:00', 38, 'REIM-20250910-5879', 'payment', 'JE-38', 'Cash Payment for reimbursement: REIM-20250910-5879', 3824.00, 0.00, 0.00, NULL, '2026-02-10 10:22:50'),
(76, 97, '111001', 'Cash on Hand', 'Asset', '2025-09-10 00:00:00', 38, 'REIM-20250910-5879', 'payment', 'JE-38', 'Cash Payment for reimbursement: REIM-20250910-5879', 0.00, 3824.00, 14942567.00, NULL, '2026-02-10 10:22:50'),
(77, 149, '554001', 'Office Supplies', 'Expense', '2025-12-28 00:00:00', 39, 'REIM-20251228-6049', 'reimbursement', 'JE-39', 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', 3931.00, 0.00, 5118.00, NULL, '2026-02-10 10:22:50'),
(78, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-12-28 00:00:00', 39, 'REIM-20251228-6049', 'reimbursement', 'JE-39', 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', 0.00, 3931.00, 26287.00, NULL, '2026-02-10 10:22:50'),
(79, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-12-28 00:00:00', 40, 'REIM-20251228-6049', 'payment', 'JE-40', 'Cash Payment for reimbursement: REIM-20251228-6049', 3931.00, 0.00, 22356.00, NULL, '2026-02-10 10:22:50'),
(80, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-28 00:00:00', 40, 'REIM-20251228-6049', 'payment', 'JE-40', 'Cash Payment for reimbursement: REIM-20251228-6049', 0.00, 3931.00, 14606063.43, NULL, '2026-02-10 10:22:50'),
(81, 146, '551001', 'Office Operations Cost', 'Expense', '2025-09-05 00:00:00', 41, 'REIM-20250905-9998', 'reimbursement', 'JE-41', 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', 1935.00, 0.00, 1935.00, NULL, '2026-02-10 10:22:50'),
(82, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-05 00:00:00', 41, 'REIM-20250905-9998', 'reimbursement', 'JE-41', 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', 0.00, 1935.00, 1935.00, NULL, '2026-02-10 10:22:50'),
(83, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-05 00:00:00', 42, 'REIM-20250905-9998', 'payment', 'JE-42', 'Cash Payment for reimbursement: REIM-20250905-9998', 1935.00, 0.00, 0.00, NULL, '2026-02-10 10:22:50'),
(84, 97, '111001', 'Cash on Hand', 'Asset', '2025-09-05 00:00:00', 42, 'REIM-20250905-9998', 'payment', 'JE-42', 'Cash Payment for reimbursement: REIM-20250905-9998', 0.00, 1935.00, 14948908.00, NULL, '2026-02-10 10:22:50'),
(85, 149, '554001', 'Office Supplies', 'Expense', '2025-10-14 00:00:00', 43, 'REIM-20251014-5463', 'reimbursement', 'JE-43', 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', 2643.00, 0.00, 7888.00, NULL, '2026-02-10 10:22:50'),
(86, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-14 00:00:00', 43, 'REIM-20251014-5463', 'reimbursement', 'JE-43', 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', 0.00, 2643.00, 14562.00, NULL, '2026-02-10 10:22:50'),
(87, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-14 00:00:00', 44, 'REIM-20251014-5463', 'payment', 'JE-44', 'Cash Payment for reimbursement: REIM-20251014-5463', 2643.00, 0.00, 11919.00, NULL, '2026-02-10 10:22:50'),
(88, 97, '111001', 'Cash on Hand', 'Asset', '2025-10-14 00:00:00', 44, 'REIM-20251014-5463', 'payment', 'JE-44', 'Cash Payment for reimbursement: REIM-20251014-5463', 0.00, 2643.00, 14880135.00, NULL, '2026-02-10 10:22:50'),
(89, 146, '551001', 'Office Operations Cost', 'Expense', '2025-10-31 00:00:00', 45, 'REIM-20251031-4753', 'reimbursement', 'JE-45', 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', 2983.00, 0.00, 2983.00, NULL, '2026-02-10 10:22:50'),
(90, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-31 00:00:00', 45, 'REIM-20251031-4753', 'reimbursement', 'JE-45', 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', 0.00, 2983.00, 14902.00, NULL, '2026-02-10 10:22:50'),
(91, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-31 00:00:00', 46, 'REIM-20251031-4753', 'payment', 'JE-46', 'Cash Payment for reimbursement: REIM-20251031-4753', 2983.00, 0.00, 11919.00, NULL, '2026-02-10 10:22:50'),
(92, 97, '111001', 'Cash on Hand', 'Asset', '2025-10-31 00:00:00', 46, 'REIM-20251031-4753', 'payment', 'JE-46', 'Cash Payment for reimbursement: REIM-20251031-4753', 0.00, 2983.00, 14854272.00, NULL, '2026-02-10 10:22:50'),
(93, 149, '554001', 'Office Supplies', 'Expense', '2025-10-04 00:00:00', 47, 'REIM-20251004-6415', 'reimbursement', 'JE-47', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 2560.00, 0.00, 2560.00, NULL, '2026-02-10 10:22:50'),
(94, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-04 00:00:00', 47, 'REIM-20251004-6415', 'reimbursement', 'JE-47', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 0.00, 2560.00, 8421.00, NULL, '2026-02-10 10:22:50'),
(95, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-10-11 00:00:00', 48, 'REIM-20251011-3329', 'reimbursement', 'JE-48', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Rejected)', 2437.00, 0.00, 19756.00, NULL, '2026-02-10 10:22:50'),
(96, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-11 00:00:00', 48, 'REIM-20251011-3329', 'reimbursement', 'JE-48', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Rejected)', 0.00, 2437.00, 10858.00, NULL, '2026-02-10 10:22:50'),
(97, 149, '554001', 'Office Supplies', 'Expense', '2025-12-26 00:00:00', 49, 'REIM-20251226-2787', 'reimbursement', 'JE-49', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 1187.00, 0.00, 1187.00, NULL, '2026-02-10 10:22:50'),
(98, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-12-26 00:00:00', 49, 'REIM-20251226-2787', 'reimbursement', 'JE-49', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 0.00, 1187.00, 22356.00, NULL, '2026-02-10 10:22:50'),
(99, 149, '554001', 'Office Supplies', 'Expense', '2025-10-11 00:00:00', 50, 'REIM-20251011-2610', 'reimbursement', 'JE-50', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 1061.00, 0.00, 5245.00, NULL, '2026-02-10 10:22:50'),
(100, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-11 00:00:00', 50, 'REIM-20251011-2610', 'reimbursement', 'JE-50', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 0.00, 1061.00, 11919.00, NULL, '2026-02-10 10:22:50'),
(101, 146, '551001', 'Office Operations Cost', 'Expense', '2026-01-01 00:00:00', 51, 'REIM-20260101-3113', 'reimbursement', 'JE-51', 'Employee Reimbursement for reimbursement: Office Operations Cost (Rejected)', 2134.00, 0.00, 2134.00, NULL, '2026-02-10 10:22:50'),
(102, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2026-01-01 00:00:00', 51, 'REIM-20260101-3113', 'reimbursement', 'JE-51', 'Employee Reimbursement for reimbursement: Office Operations Cost (Rejected)', 0.00, 2134.00, 24490.00, NULL, '2026-02-10 10:22:50'),
(103, 172, '611001', 'Travel Expenses', 'Expense', '2026-01-09 00:00:00', 52, 'REIM-20260109-5751', 'reimbursement', 'JE-52', 'Employee Reimbursement for reimbursement: Travel Expenses (Processing)', 3153.00, 0.00, 3153.00, NULL, '2026-02-10 10:22:50'),
(104, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2026-01-09 00:00:00', 52, 'REIM-20260109-5751', 'reimbursement', 'JE-52', 'Employee Reimbursement for reimbursement: Travel Expenses (Processing)', 0.00, 3153.00, 27643.00, NULL, '2026-02-10 10:22:50'),
(105, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2026-01-09 00:00:00', 53, 'REIM-20260109-5751', 'payment', 'JE-53', 'Cash Payment for reimbursement: REIM-20260109-5751', 3153.00, 0.00, 24490.00, NULL, '2026-02-10 10:22:50'),
(106, 97, '111001', 'Cash on Hand', 'Asset', '2026-01-09 00:00:00', 53, 'REIM-20260109-5751', 'payment', 'JE-53', 'Cash Payment for reimbursement: REIM-20260109-5751', 0.00, 3153.00, 14526567.89, NULL, '2026-02-10 10:22:50'),
(107, 124, '512001', 'Maintenance & Servicing', 'Expense', '2025-09-08 00:00:00', 54, 'REIM-20250908-7503', 'reimbursement', 'JE-54', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Processing)', 2517.00, 0.00, 2517.00, NULL, '2026-02-10 10:22:50'),
(108, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-08 00:00:00', 54, 'REIM-20250908-7503', 'reimbursement', 'JE-54', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Processing)', 0.00, 2517.00, 2517.00, NULL, '2026-02-10 10:22:50'),
(109, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-09-08 00:00:00', 55, 'REIM-20250908-7503', 'payment', 'JE-55', 'Cash Payment for reimbursement: REIM-20250908-7503', 2517.00, 0.00, 0.00, NULL, '2026-02-10 10:22:50'),
(110, 97, '111001', 'Cash on Hand', 'Asset', '2025-09-08 00:00:00', 55, 'REIM-20250908-7503', 'payment', 'JE-55', 'Cash Payment for reimbursement: REIM-20250908-7503', 0.00, 2517.00, 14946391.00, NULL, '2026-02-10 10:22:50'),
(111, 146, '551001', 'Office Operations Cost', 'Expense', '2025-12-22 00:00:00', 56, 'REIM-20251222-0486', 'reimbursement', 'JE-56', 'Employee Reimbursement for reimbursement: Office Operations Cost (Processing)', 1598.00, 0.00, 1598.00, NULL, '2026-02-10 10:22:50'),
(112, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-12-22 00:00:00', 56, 'REIM-20251222-0486', 'reimbursement', 'JE-56', 'Employee Reimbursement for reimbursement: Office Operations Cost (Processing)', 0.00, 1598.00, 22767.00, NULL, '2026-02-10 10:22:50'),
(113, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-12-22 00:00:00', 57, 'REIM-20251222-0486', 'payment', 'JE-57', 'Cash Payment for reimbursement: REIM-20251222-0486', 1598.00, 0.00, 21169.00, NULL, '2026-02-10 10:22:50'),
(114, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-22 00:00:00', 57, 'REIM-20251222-0486', 'payment', 'JE-57', 'Cash Payment for reimbursement: REIM-20251222-0486', 0.00, 1598.00, 14609994.43, NULL, '2026-02-10 10:22:50'),
(115, 149, '554001', 'Office Supplies', 'Expense', '2025-10-10 00:00:00', 58, 'REIM-20251010-7648', 'reimbursement', 'JE-58', 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', 1624.00, 0.00, 4184.00, NULL, '2026-02-10 10:22:50'),
(116, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-10 00:00:00', 58, 'REIM-20251010-7648', 'reimbursement', 'JE-58', 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', 0.00, 1624.00, 10045.00, NULL, '2026-02-10 10:22:50'),
(117, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2025-10-10 00:00:00', 59, 'REIM-20251010-7648', 'payment', 'JE-59', 'Cash Payment for reimbursement: REIM-20251010-7648', 1624.00, 0.00, 8421.00, NULL, '2026-02-10 10:22:50'),
(118, 97, '111001', 'Cash on Hand', 'Asset', '2025-10-10 00:00:00', 59, 'REIM-20251010-7648', 'payment', 'JE-59', 'Cash Payment for reimbursement: REIM-20251010-7648', 0.00, 1624.00, 14882778.00, NULL, '2026-02-10 10:22:50'),
(119, 149, '554001', 'Office Supplies', 'Expense', '2026-01-08 00:00:00', 60, 'REIM-20260108-1530', 'reimbursement', 'JE-60', 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', 3007.00, 0.00, 22224.00, NULL, '2026-02-10 10:22:50'),
(120, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2026-01-08 00:00:00', 60, 'REIM-20260108-1530', 'reimbursement', 'JE-60', 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', 0.00, 3007.00, 27497.00, NULL, '2026-02-10 10:22:50'),
(121, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', '2026-01-08 00:00:00', 61, 'REIM-20260108-1530', 'payment', 'JE-61', 'Cash Payment for reimbursement: REIM-20260108-1530', 3007.00, 0.00, 24490.00, NULL, '2026-02-10 10:22:50'),
(122, 97, '111001', 'Cash on Hand', 'Asset', '2026-01-08 00:00:00', 61, 'REIM-20260108-1530', 'payment', 'JE-61', 'Cash Payment for reimbursement: REIM-20260108-1530', 0.00, 3007.00, 14529720.89, NULL, '2026-02-10 10:22:50'),
(123, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-11-15 00:00:00', 62, 'D-20251115-4541', 'driver_payment', 'JE-62', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', 19935.00, 0.00, -257453.00, NULL, '2026-02-10 10:22:50'),
(124, 222, '213003', 'Driver Earnings Payable', '', '2025-11-15 00:00:00', 62, 'D-20251115-4541', 'driver_payment', 'JE-62', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', 0.00, 19935.00, 140726.00, NULL, '2026-02-10 10:22:50'),
(125, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-11-11 00:00:00', 63, 'D-20251111-7655', 'driver_payment', 'JE-63', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', 28246.00, 0.00, -237518.00, NULL, '2026-02-10 10:22:50'),
(126, 222, '213003', 'Driver Earnings Payable', '', '2025-11-11 00:00:00', 63, 'D-20251111-7655', 'driver_payment', 'JE-63', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', 0.00, 28246.00, 120791.00, NULL, '2026-02-10 10:22:50'),
(127, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-12-20 00:00:00', 64, 'D-20251220-8847', 'driver_payment', 'JE-64', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Pending)', 15052.00, 0.00, -344638.00, NULL, '2026-02-10 10:22:50'),
(128, 222, '213003', 'Driver Earnings Payable', '', '2025-12-20 00:00:00', 64, 'D-20251220-8847', 'driver_payment', 'JE-64', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Pending)', 0.00, 15052.00, 178758.00, NULL, '2026-02-10 10:22:50'),
(129, 220, '213001', 'Driver Wallet Payable', 'Liability', '2026-01-28 00:00:00', 65, 'D-20260128-6001', 'driver_payment', 'JE-65', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Pending)', 20676.00, 0.00, -563974.00, NULL, '2026-02-10 10:22:50'),
(130, 222, '213003', 'Driver Earnings Payable', '', '2026-01-28 00:00:00', 65, 'D-20260128-6001', 'driver_payment', 'JE-65', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Pending)', 0.00, 20676.00, 324910.00, NULL, '2026-02-10 10:22:50'),
(131, 220, '213001', 'Driver Wallet Payable', 'Liability', '2026-01-08 00:00:00', 66, 'D-20260108-6492', 'driver_payment', 'JE-66', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Pending)', 26334.00, 0.00, -437189.00, NULL, '2026-02-10 10:22:50'),
(132, 222, '213003', 'Driver Earnings Payable', '', '2026-01-08 00:00:00', 66, 'D-20260108-6492', 'driver_payment', 'JE-66', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Pending)', 0.00, 26334.00, 250010.00, NULL, '2026-02-10 10:22:50'),
(133, 220, '213001', 'Driver Wallet Payable', 'Liability', '2026-01-02 00:00:00', 67, 'D-20260102-6581', 'driver_payment', 'JE-67', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Approved)', 21299.00, 0.00, -410855.00, NULL, '2026-02-10 10:22:50'),
(134, 222, '213003', 'Driver Earnings Payable', '', '2026-01-02 00:00:00', 67, 'D-20260102-6581', 'driver_payment', 'JE-67', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Approved)', 0.00, 21299.00, 244975.00, NULL, '2026-02-10 10:22:50'),
(135, 222, '213003', 'Driver Earnings Payable', '', '2026-01-02 00:00:00', 68, 'D-20260102-6581', 'payment', 'JE-68', 'Cash Payment for driver_payment: D-20260102-6581 (Chloe Alexandra)', 21299.00, 0.00, 223676.00, NULL, '2026-02-10 10:22:50'),
(136, 97, '111001', 'Cash on Hand', 'Asset', '2026-01-02 00:00:00', 68, 'D-20260102-6581', 'payment', 'JE-68', 'Cash Payment for driver_payment: D-20260102-6581 (Chloe Alexandra)', 0.00, 21299.00, 14532727.89, NULL, '2026-02-10 10:22:50'),
(137, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-10-07 00:00:00', 69, 'D-20251007-3488', 'driver_payment', 'JE-69', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Approved)', 19982.00, 0.00, -152038.00, NULL, '2026-02-10 10:22:50'),
(138, 222, '213003', 'Driver Earnings Payable', '', '2025-10-07 00:00:00', 69, 'D-20251007-3488', 'driver_payment', 'JE-69', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Approved)', 0.00, 19982.00, 78173.00, NULL, '2026-02-10 10:22:50'),
(139, 222, '213003', 'Driver Earnings Payable', '', '2025-10-07 00:00:00', 70, 'D-20251007-3488', 'payment', 'JE-70', 'Cash Payment for driver_payment: D-20251007-3488 (Olivia Grace)', 19982.00, 0.00, 58191.00, NULL, '2026-02-10 10:22:50'),
(140, 97, '111001', 'Cash on Hand', 'Asset', '2025-10-07 00:00:00', 70, 'D-20251007-3488', 'payment', 'JE-70', 'Cash Payment for driver_payment: D-20251007-3488 (Olivia Grace)', 0.00, 19982.00, 14884402.00, NULL, '2026-02-10 10:22:50'),
(141, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-10-19 00:00:00', 71, 'D-20251019-8317', 'driver_payment', 'JE-71', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Approved)', 22880.00, 0.00, -174918.00, NULL, '2026-02-10 10:22:50'),
(142, 222, '213003', 'Driver Earnings Payable', '', '2025-10-19 00:00:00', 71, 'D-20251019-8317', 'driver_payment', 'JE-71', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Approved)', 0.00, 22880.00, 81071.00, NULL, '2026-02-10 10:22:50'),
(143, 222, '213003', 'Driver Earnings Payable', '', '2025-10-19 00:00:00', 72, 'D-20251019-8317', 'payment', 'JE-72', 'Cash Payment for driver_payment: D-20251019-8317 (Ethan Gabriel)', 22880.00, 0.00, 58191.00, NULL, '2026-02-10 10:22:50'),
(144, 97, '111001', 'Cash on Hand', 'Asset', '2025-10-19 00:00:00', 72, 'D-20251019-8317', 'payment', 'JE-72', 'Cash Payment for driver_payment: D-20251019-8317 (Ethan Gabriel)', 0.00, 22880.00, 14857255.00, NULL, '2026-02-10 10:22:50'),
(145, 220, '213001', 'Driver Wallet Payable', 'Liability', '2026-01-15 00:00:00', 73, 'D-20260115-0668', 'driver_payment', 'JE-73', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Approved)', 23284.00, 0.00, -486369.00, NULL, '2026-02-10 10:22:50'),
(146, 222, '213003', 'Driver Earnings Payable', '', '2026-01-15 00:00:00', 73, 'D-20260115-0668', 'driver_payment', 'JE-73', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Approved)', 0.00, 23284.00, 299190.00, NULL, '2026-02-10 10:22:50'),
(147, 222, '213003', 'Driver Earnings Payable', '', '2026-01-15 00:00:00', 74, 'D-20260115-0668', 'payment', 'JE-74', 'Cash Payment for driver_payment: D-20260115-0668 (Lucas Matteo)', 23284.00, 0.00, 275906.00, NULL, '2026-02-10 10:22:50'),
(148, 97, '111001', 'Cash on Hand', 'Asset', '2026-01-15 00:00:00', 74, 'D-20260115-0668', 'payment', 'JE-74', 'Cash Payment for driver_payment: D-20260115-0668 (Lucas Matteo)', 0.00, 23284.00, 14485558.89, NULL, '2026-02-10 10:22:50'),
(149, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-09-01 00:00:00', 75, 'D-20250901-5726', 'driver_payment', 'JE-75', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Approved)', 25503.00, 0.00, -25503.00, NULL, '2026-02-10 10:22:50'),
(150, 222, '213003', 'Driver Earnings Payable', '', '2025-09-01 00:00:00', 75, 'D-20250901-5726', 'driver_payment', 'JE-75', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Approved)', 0.00, 25503.00, 25503.00, NULL, '2026-02-10 10:22:50'),
(151, 222, '213003', 'Driver Earnings Payable', '', '2025-09-01 00:00:00', 76, 'D-20250901-5726', 'payment', 'JE-76', 'Cash Payment for driver_payment: D-20250901-5726 (Emma Louise)', 25503.00, 0.00, 0.00, NULL, '2026-02-10 10:22:50'),
(152, 97, '111001', 'Cash on Hand', 'Asset', '2025-09-01 00:00:00', 76, 'D-20250901-5726', 'payment', 'JE-76', 'Cash Payment for driver_payment: D-20250901-5726 (Emma Louise)', 0.00, 25503.00, -25503.00, NULL, '2026-02-10 10:22:50'),
(153, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-10-06 00:00:00', 77, 'D-20251006-2035', 'driver_payment', 'JE-77', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Paid)', 24708.00, 0.00, -132056.00, NULL, '2026-02-10 10:22:50'),
(154, 222, '213003', 'Driver Earnings Payable', '', '2025-10-06 00:00:00', 77, 'D-20251006-2035', 'driver_payment', 'JE-77', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Paid)', 0.00, 24708.00, 82899.00, NULL, '2026-02-10 10:22:50'),
(155, 222, '213003', 'Driver Earnings Payable', '', '2025-10-06 00:00:00', 78, 'D-20251006-2035', 'payment', 'JE-78', 'Cash Payment for driver_payment: D-20251006-2035 (Ethan Gabriel)', 24708.00, 0.00, 58191.00, NULL, '2026-02-10 10:22:50'),
(156, 97, '111001', 'Cash on Hand', 'Asset', '2025-10-06 00:00:00', 78, 'D-20251006-2035', 'payment', 'JE-78', 'Cash Payment for driver_payment: D-20251006-2035 (Ethan Gabriel)', 0.00, 24708.00, 14904384.00, NULL, '2026-02-10 10:22:50'),
(157, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-11-23 00:00:00', 79, 'D-20251123-5813', 'driver_payment', 'JE-79', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', 26553.00, 0.00, -306986.00, NULL, '2026-02-10 10:22:50'),
(158, 222, '213003', 'Driver Earnings Payable', '', '2025-11-23 00:00:00', 79, 'D-20251123-5813', 'driver_payment', 'JE-79', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', 0.00, 26553.00, 190259.00, NULL, '2026-02-10 10:22:50'),
(159, 222, '213003', 'Driver Earnings Payable', '', '2025-11-23 00:00:00', 80, 'D-20251123-5813', 'payment', 'JE-80', 'Cash Payment for driver_payment: D-20251123-5813 (Lucas Matteo)', 26553.00, 0.00, 163706.00, NULL, '2026-02-10 10:22:50'),
(160, 97, '111001', 'Cash on Hand', 'Asset', '2025-11-23 00:00:00', 80, 'D-20251123-5813', 'payment', 'JE-80', 'Cash Payment for driver_payment: D-20251123-5813 (Lucas Matteo)', 0.00, 26553.00, 14807039.09, NULL, '2026-02-10 10:22:50'),
(161, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-12-06 00:00:00', 81, 'D-20251206-5301', 'driver_payment', 'JE-81', 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Paid)', 22600.00, 0.00, -329586.00, NULL, '2026-02-10 10:22:50'),
(162, 222, '213003', 'Driver Earnings Payable', '', '2025-12-06 00:00:00', 81, 'D-20251206-5301', 'driver_payment', 'JE-81', 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Paid)', 0.00, 22600.00, 186306.00, NULL, '2026-02-10 10:22:50'),
(163, 222, '213003', 'Driver Earnings Payable', '', '2025-12-06 00:00:00', 82, 'D-20251206-5301', 'payment', 'JE-82', 'Cash Payment for driver_payment: D-20251206-5301 (Noah Alexander)', 22600.00, 0.00, 163706.00, NULL, '2026-02-10 10:22:50'),
(164, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-06 00:00:00', 82, 'D-20251206-5301', 'payment', 'JE-82', 'Cash Payment for driver_payment: D-20251206-5301 (Noah Alexander)', 0.00, 22600.00, 14688146.90, NULL, '2026-02-10 10:22:50'),
(165, 220, '213001', 'Driver Wallet Payable', 'Liability', '2026-01-26 00:00:00', 83, 'D-20260126-9399', 'driver_payment', 'JE-83', 'Driver Wallet Withdrawal for driver_payment: Jacob Ryan (Paid)', 28601.00, 0.00, -514970.00, NULL, '2026-02-10 10:22:50'),
(166, 222, '213003', 'Driver Earnings Payable', '', '2026-01-26 00:00:00', 83, 'D-20260126-9399', 'driver_payment', 'JE-83', 'Driver Wallet Withdrawal for driver_payment: Jacob Ryan (Paid)', 0.00, 28601.00, 304507.00, NULL, '2026-02-10 10:22:50'),
(167, 222, '213003', 'Driver Earnings Payable', '', '2026-01-26 00:00:00', 84, 'D-20260126-9399', 'payment', 'JE-84', 'Cash Payment for driver_payment: D-20260126-9399 (Jacob Ryan)', 28601.00, 0.00, 275906.00, NULL, '2026-02-10 10:22:50'),
(168, 97, '111001', 'Cash on Hand', 'Asset', '2026-01-26 00:00:00', 84, 'D-20260126-9399', 'payment', 'JE-84', 'Cash Payment for driver_payment: D-20260126-9399 (Jacob Ryan)', 0.00, 28601.00, 14456957.89, NULL, '2026-02-10 10:22:50'),
(169, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-09-03 00:00:00', 85, 'D-20250903-7854', 'driver_payment', 'JE-85', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', 23654.00, 0.00, -64795.00, NULL, '2026-02-10 10:22:50'),
(170, 222, '213003', 'Driver Earnings Payable', '', '2025-09-03 00:00:00', 85, 'D-20250903-7854', 'driver_payment', 'JE-85', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', 0.00, 23654.00, 39292.00, NULL, '2026-02-10 10:22:50'),
(171, 222, '213003', 'Driver Earnings Payable', '', '2025-09-03 00:00:00', 86, 'D-20250903-7854', 'payment', 'JE-86', 'Cash Payment for driver_payment: D-20250903-7854 (Lucas Matteo)', 23654.00, 0.00, 15638.00, NULL, '2026-02-10 10:22:50'),
(172, 97, '111001', 'Cash on Hand', 'Asset', '2025-09-03 00:00:00', 86, 'D-20250903-7854', 'payment', 'JE-86', 'Cash Payment for driver_payment: D-20250903-7854 (Lucas Matteo)', 0.00, 23654.00, 14950843.00, NULL, '2026-02-10 10:22:50'),
(173, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-11-17 00:00:00', 87, 'D-20251117-3941', 'driver_payment', 'JE-87', 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Rejected)', 22980.00, 0.00, -280433.00, NULL, '2026-02-10 10:22:50'),
(174, 222, '213003', 'Driver Earnings Payable', '', '2025-11-17 00:00:00', 87, 'D-20251117-3941', 'driver_payment', 'JE-87', 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Rejected)', 0.00, 22980.00, 163706.00, NULL, '2026-02-10 10:22:50'),
(175, 220, '213001', 'Driver Wallet Payable', 'Liability', '2026-01-26 00:00:00', 88, 'D-20260126-8618', 'driver_payment', 'JE-88', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Rejected)', 28328.00, 0.00, -543298.00, NULL, '2026-02-10 10:22:50'),
(176, 222, '213003', 'Driver Earnings Payable', '', '2026-01-26 00:00:00', 88, 'D-20260126-8618', 'driver_payment', 'JE-88', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Rejected)', 0.00, 28328.00, 304234.00, NULL, '2026-02-10 10:22:50'),
(177, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-10-24 00:00:00', 89, 'D-20251024-7533', 'driver_payment', 'JE-89', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Rejected)', 15042.00, 0.00, -209272.00, NULL, '2026-02-10 10:22:50'),
(178, 222, '213003', 'Driver Earnings Payable', '', '2025-10-24 00:00:00', 89, 'D-20251024-7533', 'driver_payment', 'JE-89', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Rejected)', 0.00, 15042.00, 92545.00, NULL, '2026-02-10 10:22:50'),
(179, 220, '213001', 'Driver Wallet Payable', 'Liability', '2026-01-11 00:00:00', 90, 'D-20260111-6393', 'driver_payment', 'JE-90', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Rejected)', 25896.00, 0.00, -463085.00, NULL, '2026-02-10 10:22:50'),
(180, 222, '213003', 'Driver Earnings Payable', '', '2026-01-11 00:00:00', 90, 'D-20260111-6393', 'driver_payment', 'JE-90', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Rejected)', 0.00, 25896.00, 275906.00, NULL, '2026-02-10 10:22:50'),
(181, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-10-21 00:00:00', 91, 'D-20251021-1160', 'driver_payment', 'JE-91', 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Rejected)', 19312.00, 0.00, -194230.00, NULL, '2026-02-10 10:22:50'),
(182, 222, '213003', 'Driver Earnings Payable', '', '2025-10-21 00:00:00', 91, 'D-20251021-1160', 'driver_payment', 'JE-91', 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Rejected)', 0.00, 19312.00, 77503.00, NULL, '2026-02-10 10:22:50'),
(183, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-10-04 00:00:00', 92, 'D-20251004-9718', 'driver_payment', 'JE-92', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Archived)', 16043.00, 0.00, -107348.00, NULL, '2026-02-10 10:22:50'),
(184, 222, '213003', 'Driver Earnings Payable', '', '2025-10-04 00:00:00', 92, 'D-20251004-9718', 'driver_payment', 'JE-92', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Archived)', 0.00, 16043.00, 58191.00, NULL, '2026-02-10 10:22:50'),
(185, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-12-20 00:00:00', 93, 'D-20251220-0831', 'driver_payment', 'JE-93', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Archived)', 19294.00, 0.00, -363932.00, NULL, '2026-02-10 10:22:50'),
(186, 222, '213003', 'Driver Earnings Payable', '', '2025-12-20 00:00:00', 93, 'D-20251220-0831', 'driver_payment', 'JE-93', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Archived)', 0.00, 19294.00, 198052.00, NULL, '2026-02-10 10:22:50'),
(187, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-12-27 00:00:00', 94, 'D-20251227-0362', 'driver_payment', 'JE-94', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Archived)', 25624.00, 0.00, -389556.00, NULL, '2026-02-10 10:22:50'),
(188, 222, '213003', 'Driver Earnings Payable', '', '2025-12-27 00:00:00', 94, 'D-20251227-0362', 'driver_payment', 'JE-94', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Archived)', 0.00, 25624.00, 223676.00, NULL, '2026-02-10 10:22:50'),
(189, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-09-02 00:00:00', 95, 'D-20250902-4224', 'driver_payment', 'JE-95', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Archived)', 15638.00, 0.00, -41141.00, NULL, '2026-02-10 10:22:50'),
(190, 222, '213003', 'Driver Earnings Payable', '', '2025-09-02 00:00:00', 95, 'D-20250902-4224', 'driver_payment', 'JE-95', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Archived)', 0.00, 15638.00, 15638.00, NULL, '2026-02-10 10:22:50'),
(191, 220, '213001', 'Driver Wallet Payable', 'Liability', '2025-09-20 00:00:00', 96, 'D-20250920-7572', 'driver_payment', 'JE-96', 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Archived)', 26510.00, 0.00, -91305.00, NULL, '2026-02-10 10:22:50'),
(192, 222, '213003', 'Driver Earnings Payable', '', '2025-09-20 00:00:00', 96, 'D-20250920-7572', 'driver_payment', 'JE-96', 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Archived)', 0.00, 26510.00, 42148.00, NULL, '2026-02-10 10:22:50'),
(193, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 97, 'PAY-20251115-3862', 'payroll', 'JE-97', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 36886.83, 0.00, 36886.83, NULL, '2026-02-10 10:22:50'),
(194, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 97, 'PAY-20251115-3862', 'payroll', 'JE-97', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 35042.49, 35042.49, NULL, '2026-02-10 10:22:50'),
(195, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 97, 'PAY-20251115-3862', 'payroll', 'JE-97', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 1844.34, 1844.34, NULL, '2026-02-10 10:22:50'),
(196, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-31 00:00:00', 98, 'PAY-20251231-1136', 'payroll', 'JE-98', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (pending)', 22475.70, 0.00, 192416.18, NULL, '2026-02-10 10:22:50'),
(197, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-31 00:00:00', 98, 'PAY-20251231-1136', 'payroll', 'JE-98', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (pending)', 0.00, 21351.91, 406377.05, NULL, '2026-02-10 10:22:50'),
(198, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-31 00:00:00', 98, 'PAY-20251231-1136', 'payroll', 'JE-98', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (pending)', 0.00, 1123.79, 29220.51, NULL, '2026-02-10 10:22:50');
INSERT INTO `general_ledger` (`id`, `gl_account_id`, `gl_account_code`, `gl_account_name`, `account_type`, `transaction_date`, `journal_entry_id`, `reference_id`, `reference_type`, `original_reference`, `description`, `debit_amount`, `credit_amount`, `running_balance`, `department`, `created_at`) VALUES
(199, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-15 00:00:00', 99, 'PAY-20251215-9504', 'payroll', 'JE-99', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (pending)', 24591.22, 0.00, 24591.22, NULL, '2026-02-10 10:22:50'),
(200, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 99, 'PAY-20251215-9504', 'payroll', 'JE-99', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (pending)', 0.00, 23361.66, 300338.81, NULL, '2026-02-10 10:22:50'),
(201, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-15 00:00:00', 99, 'PAY-20251215-9504', 'payroll', 'JE-99', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (pending)', 0.00, 1229.56, 20829.26, NULL, '2026-02-10 10:22:50'),
(202, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 100, 'PAY-20251115-6409', 'payroll', 'JE-100', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 31100.03, 0.00, 67986.86, NULL, '2026-02-10 10:22:50'),
(203, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 100, 'PAY-20251115-6409', 'payroll', 'JE-100', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 29545.03, 64587.52, NULL, '2026-02-10 10:22:50'),
(204, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 100, 'PAY-20251115-6409', 'payroll', 'JE-100', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 1555.00, 3399.34, NULL, '2026-02-10 10:22:50'),
(205, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 101, 'PAY-20251115-8693', 'payroll', 'JE-101', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 21994.77, 0.00, 89981.63, NULL, '2026-02-10 10:22:50'),
(206, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 101, 'PAY-20251115-8693', 'payroll', 'JE-101', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 20895.03, 85482.55, NULL, '2026-02-10 10:22:50'),
(207, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 101, 'PAY-20251115-8693', 'payroll', 'JE-101', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 1099.74, 4499.08, NULL, '2026-02-10 10:22:50'),
(208, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-30 00:00:00', 102, 'PAY-20251130-8655', 'payroll', 'JE-102', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (pending)', 24158.53, 0.00, 240978.69, NULL, '2026-02-10 10:22:50'),
(209, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 102, 'PAY-20251130-8655', 'payroll', 'JE-102', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (pending)', 0.00, 22950.60, 208249.84, NULL, '2026-02-10 10:22:50'),
(210, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-30 00:00:00', 102, 'PAY-20251130-8655', 'payroll', 'JE-102', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (pending)', 0.00, 1207.93, 12048.94, NULL, '2026-02-10 10:22:50'),
(211, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 103, 'PAY-20251115-2777', 'payroll', 'JE-103', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 28185.23, 0.00, 118166.86, NULL, '2026-02-10 10:22:50'),
(212, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 103, 'PAY-20251115-2777', 'payroll', 'JE-103', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 26775.97, 112258.52, NULL, '2026-02-10 10:22:50'),
(213, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 103, 'PAY-20251115-2777', 'payroll', 'JE-103', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 1409.26, 5908.34, NULL, '2026-02-10 10:22:50'),
(214, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 104, 'PAY-20251115-0962', 'payroll', 'JE-104', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 31731.15, 0.00, 149898.01, NULL, '2026-02-10 10:22:50'),
(215, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 104, 'PAY-20251115-0962', 'payroll', 'JE-104', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 30144.59, 142403.11, NULL, '2026-02-10 10:22:50'),
(216, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 104, 'PAY-20251115-0962', 'payroll', 'JE-104', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 0.00, 1586.56, 7494.90, NULL, '2026-02-10 10:22:50'),
(217, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-30 00:00:00', 105, 'PAY-20251130-6796', 'payroll', 'JE-105', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 34615.20, 0.00, 275593.89, NULL, '2026-02-10 10:22:50'),
(218, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 105, 'PAY-20251130-6796', 'payroll', 'JE-105', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 0.00, 32884.44, 241134.28, NULL, '2026-02-10 10:22:50'),
(219, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-30 00:00:00', 105, 'PAY-20251130-6796', 'payroll', 'JE-105', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 0.00, 1730.76, 13779.70, NULL, '2026-02-10 10:22:50'),
(220, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 106, 'PAY-20251130-6796', 'payment', 'JE-106', 'Cash Payment for payroll: PAY-20251130-6796', 31584.44, 0.00, 209549.84, NULL, '2026-02-10 10:22:50'),
(221, 97, '111001', 'Cash on Hand', 'Asset', '2025-11-30 00:00:00', 106, 'PAY-20251130-6796', 'payment', 'JE-106', 'Cash Payment for payroll: PAY-20251130-6796', 0.00, 31584.44, 14753899.65, NULL, '2026-02-10 10:22:50'),
(222, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 107, 'PAY-20251115-5970', 'payroll', 'JE-107', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (approved)', 23136.75, 0.00, 173034.76, NULL, '2026-02-10 10:22:50'),
(223, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 107, 'PAY-20251115-5970', 'payroll', 'JE-107', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (approved)', 0.00, 21979.91, 164383.02, NULL, '2026-02-10 10:22:50'),
(224, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 107, 'PAY-20251115-5970', 'payroll', 'JE-107', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (approved)', 0.00, 1156.84, 8651.74, NULL, '2026-02-10 10:22:50'),
(225, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 108, 'PAY-20251115-5970', 'payment', 'JE-108', 'Cash Payment for payroll: PAY-20251115-5970', 20679.91, 0.00, 143703.11, NULL, '2026-02-10 10:22:50'),
(226, 97, '111001', 'Cash on Hand', 'Asset', '2025-11-15 00:00:00', 108, 'PAY-20251115-5970', 'payment', 'JE-108', 'Cash Payment for payroll: PAY-20251115-5970', 0.00, 20679.91, 14833592.09, NULL, '2026-02-10 10:22:50'),
(227, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-15 00:00:00', 109, 'PAY-20251215-9626', 'payroll', 'JE-109', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 24230.64, 0.00, 48821.86, NULL, '2026-02-10 10:22:50'),
(228, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 109, 'PAY-20251215-9626', 'payroll', 'JE-109', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 0.00, 23019.11, 323357.92, NULL, '2026-02-10 10:22:50'),
(229, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-15 00:00:00', 109, 'PAY-20251215-9626', 'payroll', 'JE-109', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 0.00, 1211.53, 22040.79, NULL, '2026-02-10 10:22:50'),
(230, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 110, 'PAY-20251215-9626', 'payment', 'JE-110', 'Cash Payment for payroll: PAY-20251215-9626', 21719.11, 0.00, 301638.81, NULL, '2026-02-10 10:22:50'),
(231, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-15 00:00:00', 110, 'PAY-20251215-9626', 'payment', 'JE-110', 'Cash Payment for payroll: PAY-20251215-9626', 0.00, 21719.11, 14666427.79, NULL, '2026-02-10 10:22:50'),
(232, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-31 00:00:00', 111, 'PAY-20251231-6299', 'payroll', 'JE-111', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 31550.75, 0.00, 223966.93, NULL, '2026-02-10 10:22:50'),
(233, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-31 00:00:00', 111, 'PAY-20251231-6299', 'payroll', 'JE-111', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 0.00, 29973.21, 436350.26, NULL, '2026-02-10 10:22:50'),
(234, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-31 00:00:00', 111, 'PAY-20251231-6299', 'payroll', 'JE-111', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 0.00, 1577.54, 30798.05, NULL, '2026-02-10 10:22:50'),
(235, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-31 00:00:00', 112, 'PAY-20251231-6299', 'payment', 'JE-112', 'Cash Payment for payroll: PAY-20251231-6299', 28673.21, 0.00, 407677.05, NULL, '2026-02-10 10:22:50'),
(236, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-31 00:00:00', 112, 'PAY-20251231-6299', 'payment', 'JE-112', 'Cash Payment for payroll: PAY-20251231-6299', 0.00, 28673.21, 14577390.22, NULL, '2026-02-10 10:22:50'),
(237, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-30 00:00:00', 113, 'PAY-20251130-5014', 'payroll', 'JE-113', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 20732.78, 0.00, 296326.67, NULL, '2026-02-10 10:22:50'),
(238, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 113, 'PAY-20251130-5014', 'payroll', 'JE-113', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 0.00, 19696.14, 229245.98, NULL, '2026-02-10 10:22:50'),
(239, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-30 00:00:00', 113, 'PAY-20251130-5014', 'payroll', 'JE-113', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 0.00, 1036.64, 14816.34, NULL, '2026-02-10 10:22:50'),
(240, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 114, 'PAY-20251130-5014', 'payment', 'JE-114', 'Cash Payment for payroll: PAY-20251130-5014', 18396.14, 0.00, 210849.84, NULL, '2026-02-10 10:22:50'),
(241, 97, '111001', 'Cash on Hand', 'Asset', '2025-11-30 00:00:00', 114, 'PAY-20251130-5014', 'payment', 'JE-114', 'Cash Payment for payroll: PAY-20251130-5014', 0.00, 18396.14, 14735503.51, NULL, '2026-02-10 10:22:50'),
(242, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-31 00:00:00', 115, 'PAY-20251231-2610', 'payroll', 'JE-115', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 25961.40, 0.00, 249928.33, NULL, '2026-02-10 10:22:50'),
(243, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-31 00:00:00', 115, 'PAY-20251231-2610', 'payroll', 'JE-115', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 0.00, 24663.33, 432340.38, NULL, '2026-02-10 10:22:50'),
(244, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-31 00:00:00', 115, 'PAY-20251231-2610', 'payroll', 'JE-115', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 0.00, 1298.07, 32096.12, NULL, '2026-02-10 10:22:50'),
(245, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-31 00:00:00', 116, 'PAY-20251231-2610', 'payment', 'JE-116', 'Cash Payment for payroll: PAY-20251231-2610', 23363.33, 0.00, 408977.05, NULL, '2026-02-10 10:22:50'),
(246, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-31 00:00:00', 116, 'PAY-20251231-2610', 'payment', 'JE-116', 'Cash Payment for payroll: PAY-20251231-2610', 0.00, 23363.33, 14554026.89, NULL, '2026-02-10 10:22:50'),
(247, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-30 00:00:00', 117, 'PAY-20251130-6633', 'payroll', 'JE-117', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 27428.01, 0.00, 323754.68, NULL, '2026-02-10 10:22:50'),
(248, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 117, 'PAY-20251130-6633', 'payroll', 'JE-117', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 0.00, 26056.61, 236906.45, NULL, '2026-02-10 10:22:50'),
(249, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-30 00:00:00', 117, 'PAY-20251130-6633', 'payroll', 'JE-117', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 0.00, 1371.40, 16187.74, NULL, '2026-02-10 10:22:50'),
(250, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 118, 'PAY-20251130-6633', 'payment', 'JE-118', 'Cash Payment for payroll: PAY-20251130-6633', 24756.61, 0.00, 212149.84, NULL, '2026-02-10 10:22:50'),
(251, 97, '111001', 'Cash on Hand', 'Asset', '2025-11-30 00:00:00', 118, 'PAY-20251130-6633', 'payment', 'JE-118', 'Cash Payment for payroll: PAY-20251130-6633', 0.00, 24756.61, 14710746.90, NULL, '2026-02-10 10:22:50'),
(252, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-15 00:00:00', 119, 'PAY-20251215-5911', 'payroll', 'JE-119', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 34711.96, 0.00, 83533.82, NULL, '2026-02-10 10:22:50'),
(253, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 119, 'PAY-20251215-5911', 'payroll', 'JE-119', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 0.00, 32976.36, 334615.17, NULL, '2026-02-10 10:22:50'),
(254, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-15 00:00:00', 119, 'PAY-20251215-5911', 'payroll', 'JE-119', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 0.00, 1735.60, 23776.39, NULL, '2026-02-10 10:22:50'),
(255, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 120, 'PAY-20251215-5911', 'payment', 'JE-120', 'Cash Payment for payroll: PAY-20251215-5911', 31676.36, 0.00, 302938.81, NULL, '2026-02-10 10:22:50'),
(256, 97, '111001', 'Cash on Hand', 'Asset', '2025-12-15 00:00:00', 120, 'PAY-20251215-5911', 'payment', 'JE-120', 'Cash Payment for payroll: PAY-20251215-5911', 0.00, 31676.36, 14634751.43, NULL, '2026-02-10 10:22:50'),
(257, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-30 00:00:00', 121, 'PAY-20251130-8976', 'payroll', 'JE-121', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 36237.79, 0.00, 359992.47, NULL, '2026-02-10 10:22:50'),
(258, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 121, 'PAY-20251130-8976', 'payroll', 'JE-121', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 0.00, 34425.90, 246575.74, NULL, '2026-02-10 10:22:50'),
(259, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-30 00:00:00', 121, 'PAY-20251130-8976', 'payroll', 'JE-121', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 0.00, 1811.89, 17999.63, NULL, '2026-02-10 10:22:50'),
(260, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 122, 'PAY-20251115-2697', 'payroll', 'JE-122', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 24194.43, 0.00, 197229.19, NULL, '2026-02-10 10:22:50'),
(261, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 122, 'PAY-20251115-2697', 'payroll', 'JE-122', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 0.00, 22984.71, 166687.82, NULL, '2026-02-10 10:22:50'),
(262, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 122, 'PAY-20251115-2697', 'payroll', 'JE-122', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 0.00, 1209.72, 9861.46, NULL, '2026-02-10 10:22:50'),
(263, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-15 00:00:00', 123, 'PAY-20251215-1555', 'payroll', 'JE-123', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 24591.22, 0.00, 108125.04, NULL, '2026-02-10 10:22:50'),
(264, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 123, 'PAY-20251215-1555', 'payroll', 'JE-123', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 0.00, 23361.66, 326300.47, NULL, '2026-02-10 10:22:50'),
(265, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-15 00:00:00', 123, 'PAY-20251215-1555', 'payroll', 'JE-123', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 0.00, 1229.56, 25005.95, NULL, '2026-02-10 10:22:50'),
(266, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-30 00:00:00', 124, 'PAY-20251130-5174', 'payroll', 'JE-124', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 32001.48, 0.00, 391993.95, NULL, '2026-02-10 10:22:50'),
(267, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-30 00:00:00', 124, 'PAY-20251130-5174', 'payroll', 'JE-124', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 0.00, 30401.41, 276977.15, NULL, '2026-02-10 10:22:50'),
(268, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-30 00:00:00', 124, 'PAY-20251130-5174', 'payroll', 'JE-124', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 0.00, 1600.07, 19599.70, NULL, '2026-02-10 10:22:50'),
(269, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-11-15 00:00:00', 125, 'PAY-20251115-0468', 'payroll', 'JE-125', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 19590.97, 0.00, 216820.16, NULL, '2026-02-10 10:22:50'),
(270, 113, '223001', 'Salaries Payable', 'Liability', '2025-11-15 00:00:00', 125, 'PAY-20251115-0468', 'payroll', 'JE-125', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 0.00, 18611.42, 185299.24, NULL, '2026-02-10 10:22:50'),
(271, 114, '224001', 'Taxes Payable', 'Liability', '2025-11-15 00:00:00', 125, 'PAY-20251115-0468', 'payroll', 'JE-125', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 0.00, 979.55, 10841.01, NULL, '2026-02-10 10:22:50'),
(272, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-31 00:00:00', 126, 'PAY-20251231-9305', 'payroll', 'JE-126', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (rejected)', 25961.40, 0.00, 275889.73, NULL, '2026-02-10 10:22:50'),
(273, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-31 00:00:00', 126, 'PAY-20251231-9305', 'payroll', 'JE-126', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (rejected)', 0.00, 24663.33, 433640.38, NULL, '2026-02-10 10:22:50'),
(274, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-31 00:00:00', 126, 'PAY-20251231-9305', 'payroll', 'JE-126', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (rejected)', 0.00, 1298.07, 33394.19, NULL, '2026-02-10 10:22:51'),
(275, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-15 00:00:00', 127, 'PAY-20251215-3522', 'payroll', 'JE-127', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 29026.58, 0.00, 137151.62, NULL, '2026-02-10 10:22:51'),
(276, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 127, 'PAY-20251215-3522', 'payroll', 'JE-127', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 0.00, 27575.25, 353875.72, NULL, '2026-02-10 10:22:51'),
(277, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-15 00:00:00', 127, 'PAY-20251215-3522', 'payroll', 'JE-127', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 0.00, 1451.33, 26457.28, NULL, '2026-02-10 10:22:51'),
(278, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2025-12-15 00:00:00', 128, 'PAY-20251215-1857', 'payroll', 'JE-128', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 32788.86, 0.00, 169940.48, NULL, '2026-02-10 10:22:51'),
(279, 113, '223001', 'Salaries Payable', 'Liability', '2025-12-15 00:00:00', 128, 'PAY-20251215-1857', 'payroll', 'JE-128', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 0.00, 31149.42, 385025.14, NULL, '2026-02-10 10:22:51'),
(280, 114, '224001', 'Taxes Payable', 'Liability', '2025-12-15 00:00:00', 128, 'PAY-20251215-1857', 'payroll', 'JE-128', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 0.00, 1639.44, 28096.72, NULL, '2026-02-10 10:22:51'),
(281, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-02-10 23:21:35', 129, 'JE-129', 'vendor_invoice', 'INV-20251116-5498', 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 12229.00, 0.00, 12229.00, 'Administrative', '2026-02-10 15:21:35'),
(282, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-10 23:21:35', 129, 'JE-129', 'vendor_invoice', 'INV-20251116-5498', 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 0.00, 12229.00, 253914.00, 'Administrative', '2026-02-10 15:21:35'),
(283, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-12 09:14:45', 130, 'JE-130', 'vendor_invoice', 'INV-20260201-7327', 'Vendor invoice for direct operating costs - SpeedFix Auto Service Center', 57048.80, 0.00, 196865.20, 'Logistic-1', '2026-02-12 01:14:45'),
(284, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-12 09:14:45', 130, 'JE-130', 'vendor_invoice', 'INV-20260201-7327', 'Vendor invoice for direct operating costs - SpeedFix Auto Service Center', 0.00, 57048.80, 253914.00, 'Logistic-1', '2026-02-12 01:14:45'),
(285, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2026-02-12 09:21:56', 131, 'JE-131', 'pr', 'PR-1202503-20251215', 'Payroll for December 2025 - HR Assistant', 22061.66, 0.00, 297951.39, 'Human Resource-1', '2026-02-12 01:21:56'),
(286, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-12 09:21:56', 131, 'JE-131', 'pr', 'PR-1202503-20251215', 'Payroll for December 2025 - HR Assistant', 0.00, 22061.66, 455702.04, 'Human Resource-1', '2026-02-12 01:21:56'),
(287, 151, '561001', 'Employee Salaries & Benefits', 'Expense', '2026-02-12 09:53:40', 132, 'JE-132', 'pr', 'PR-1202502-20251231', 'Payroll for December 2025 - HR Specialist', 20051.91, 0.00, 318003.30, 'Human Resource-2', '2026-02-12 01:53:40'),
(288, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-12 09:53:40', 132, 'JE-132', 'pr', 'PR-1202502-20251231', 'Payroll for December 2025 - HR Specialist', 0.00, 20051.91, 475753.95, 'Human Resource-2', '2026-02-12 01:53:40'),
(289, 100, '112001', 'Accounts Receivable - Drivers', 'Asset', '2026-02-12 00:00:00', 134, 'JE-20260212-0001', 'AR_INV', 'INIT-REV-1770886913', 'Initial Revenue Recognition for Dashboard', 180000.00, 0.00, 180000.00, 'Operations', '2026-02-12 09:01:53'),
(290, 121, '421001', 'Platform Commission Revenue', 'Revenue', '2026-02-12 00:00:00', 134, 'JE-20260212-0001', 'AR_INV', 'INIT-REV-1770886913', 'Initial Revenue Recognition for Dashboard', 0.00, 180000.00, 180000.00, 'Operations', '2026-02-12 09:01:53'),
(291, 5, '500000', 'Expenses', 'Expense', '2026-02-13 22:05:17', 135, 'JE-20260213', 'vendor_invoice', 'INV-2026-TEST-01', 'Vendor invoice for office supplies - ViaHale Supplier Co.', 25500.00, 0.00, 35056.00, 'Logistic 1', '2026-02-13 14:05:17'),
(292, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-13 22:05:17', 135, 'JE-20260213', 'vendor_invoice', 'INV-2026-TEST-01', 'Vendor invoice for office supplies - ViaHale Supplier Co.', 0.00, 25500.00, 10530914.00, 'Logistic 1', '2026-02-13 14:05:17'),
(293, 125, '513001', 'Tire Replacement', 'Expense', '2026-02-13 22:22:06', 136, 'JE-20260214', 'vendor_invoice', 'INV-20250903-6865', 'Vendor invoice for direct operating costs - AIG Insurance Phils', 18163.00, 0.00, 10526913.00, 'Logistic-1', '2026-02-13 14:22:06'),
(294, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-13 22:22:06', 136, 'JE-20260214', 'vendor_invoice', 'INV-20250903-6865', 'Vendor invoice for direct operating costs - AIG Insurance Phils', 0.00, 18163.00, 10549077.00, 'Logistic-1', '2026-02-13 14:22:06'),
(295, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-02-13 23:29:04', 137, 'JE-20260215', 'vendor_invoice', 'INV-20251105-3449', 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 15602.00, 0.00, 11422031.00, 'Logistic-1', '2026-02-13 15:29:04'),
(296, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-13 23:29:04', 137, 'JE-20260215', 'vendor_invoice', 'INV-20251105-3449', 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 0.00, 15602.00, 10564679.00, 'Logistic-1', '2026-02-13 15:29:04'),
(297, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-02-14 12:02:59', 138, 'JE-20260216', 'vendor_invoice', 'INV-20260213-1001', 'Vendor invoice for direct operating costs - AutoParts Supply Co.', 8500.00, 0.00, 11430531.00, 'Logistic-1', '2026-02-14 04:02:59'),
(298, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-14 12:02:59', 138, 'JE-20260216', 'vendor_invoice', 'INV-20260213-1001', 'Vendor invoice for direct operating costs - AutoParts Supply Co.', 0.00, 8500.00, 10573179.00, 'Logistic-1', '2026-02-14 04:02:59'),
(299, 149, '554001', 'Office Supplies', 'Expense', '2026-02-14 12:03:56', 139, 'JE-20260217', 'vendor_invoice', 'INV-20260213-1002', 'Vendor invoice for supplies & technology - Office Depot Manila', 12300.00, 0.00, 10647024.00, 'Administrative', '2026-02-14 04:03:56'),
(300, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-14 12:03:56', 139, 'JE-20260217', 'vendor_invoice', 'INV-20260213-1002', 'Vendor invoice for supplies & technology - Office Depot Manila', 0.00, 12300.00, 10585479.00, 'Administrative', '2026-02-14 04:03:56'),
(301, 71, '551000', 'Office Operations', 'Expense', '2026-02-14 12:48:13', 140, 'JE-20260218', 'reimbursement', 'REIM-20260214-103', 'Reimbursement for office operations cost purchased by Ana Clara', 4500.00, 0.00, 404495.00, 'Human Resource-1', '2026-02-14 04:48:13'),
(302, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 12:48:13', 140, 'JE-20260218', 'reimbursement', 'REIM-20260214-103', 'Reimbursement for office operations cost purchased by Ana Clara', 0.00, 4500.00, 130960751.38, 'Human Resource-1', '2026-02-14 04:48:13'),
(303, 150, '555001', 'Support Staff Compensation', 'Expense', '2026-02-14 14:04:53', 141, 'JE-20260219', 'vendor_invoice', 'INV-20260213-1003', 'Vendor invoice for supplies & technology - TechSolutions Inc.', 25000.00, 0.00, 10587500.00, 'Human Resource-1', '2026-02-14 06:04:53'),
(304, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-14 14:04:53', 141, 'JE-20260219', 'vendor_invoice', 'INV-20260213-1003', 'Vendor invoice for supplies & technology - TechSolutions Inc.', 0.00, 25000.00, 10610479.00, 'Human Resource-1', '2026-02-14 06:04:53'),
(305, 146, '551001', 'Office Operations Cost', 'Expense', '2026-02-14 15:18:37', 142, 'JE-20260220', 'vendor_invoice', 'INV-20260214-5001', 'Vendor invoice for indirect costs - CleanPro Services Inc.', 11500.00, 0.00, 11116134.00, 'Administrative', '2026-02-14 07:18:37'),
(306, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-14 15:18:37', 142, 'JE-20260220', 'vendor_invoice', 'INV-20260214-5001', 'Vendor invoice for indirect costs - CleanPro Services Inc.', 0.00, 11500.00, 10621979.00, 'Administrative', '2026-02-14 07:18:37'),
(307, 147, '552001', 'Professional Services', 'Expense', '2026-02-14 15:19:55', 143, 'JE-20260221', 'vendor_invoice', 'INV-20260214-5002', 'Vendor invoice for indirect costs - PLDT Fibr Business', 8900.00, 0.00, 10511400.00, 'Core-1', '2026-02-14 07:19:55'),
(308, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', '2026-02-14 15:19:55', 143, 'JE-20260221', 'vendor_invoice', 'INV-20260214-5002', 'Vendor invoice for indirect costs - PLDT Fibr Business', 0.00, 8900.00, 10630879.00, 'Core-1', '2026-02-14 07:19:55'),
(309, 149, '554001', 'Office Supplies', 'Expense', '2026-02-14 15:20:29', 144, 'JE-20260222', 'reimbursement', 'REIM-20260214-101', 'Reimbursement for office supplies purchased by Maria Santos', 3500.00, 0.00, 10650524.00, 'Core-1', '2026-02-14 07:20:29'),
(310, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:20:29', 144, 'JE-20260222', 'reimbursement', 'REIM-20260214-101', 'Reimbursement for office supplies purchased by Maria Santos', 0.00, 3500.00, 130964251.38, 'Core-1', '2026-02-14 07:20:29'),
(311, 5, '500000', 'Expenses', 'Expense', '2026-02-14 15:21:29', 145, 'JE-20260223', 'reimbursement', 'REIM-20260214-102', 'Reimbursement for travel expenses purchased by Jose Reyes', 8200.50, 0.00, 43256.50, 'Logistic-1', '2026-02-14 07:21:29'),
(312, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:21:29', 145, 'JE-20260223', 'reimbursement', 'REIM-20260214-102', 'Reimbursement for travel expenses purchased by Jose Reyes', 0.00, 8200.50, 130972451.88, 'Logistic-1', '2026-02-14 07:21:29'),
(313, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-02-14 15:22:01', 146, 'JE-20260224', 'reimbursement', 'REIM-20260214-104', 'Reimbursement for maintenance & servicing purchased by Rafael Garcia', 15000.00, 0.00, 11445531.00, 'Maintenance', '2026-02-14 07:22:01'),
(314, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:22:01', 146, 'JE-20260224', 'reimbursement', 'REIM-20260214-104', 'Reimbursement for maintenance & servicing purchased by Rafael Garcia', 0.00, 15000.00, 130987451.88, 'Maintenance', '2026-02-14 07:22:01'),
(315, 5, '500000', 'Expenses', 'Expense', '2026-02-14 15:22:01', 147, 'JE-20260225', 'reimbursement', 'REIM-20260214-105', 'Reimbursement for travel expenses purchased by Lito Lapid', 6750.00, 0.00, 50006.50, 'Logistic-2', '2026-02-14 07:22:01'),
(316, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:22:01', 147, 'JE-20260225', 'reimbursement', 'REIM-20260214-105', 'Reimbursement for travel expenses purchased by Lito Lapid', 0.00, 6750.00, 130994201.88, 'Logistic-2', '2026-02-14 07:22:01'),
(317, 149, '554001', 'Office Supplies', 'Expense', '2026-02-14 15:22:01', 148, 'JE-20260226', 'reimbursement', 'REIM-20260214-106', 'Reimbursement for office supplies purchased by Grace Tan', 3200.00, 0.00, 10653724.00, 'Administrative', '2026-02-14 07:22:01'),
(318, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:22:01', 148, 'JE-20260226', 'reimbursement', 'REIM-20260214-106', 'Reimbursement for office supplies purchased by Grace Tan', 0.00, 3200.00, 130997401.88, 'Administrative', '2026-02-14 07:22:01'),
(319, 71, '551000', 'Office Operations', 'Expense', '2026-02-14 15:22:01', 149, 'JE-20260227', 'reimbursement', 'REIM-20260214-107', 'Reimbursement for office operations cost purchased by Mark Bautista', 5600.00, 0.00, 410095.00, 'Core-2', '2026-02-14 07:22:01'),
(320, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:22:01', 149, 'JE-20260227', 'reimbursement', 'REIM-20260214-107', 'Reimbursement for office operations cost purchased by Mark Bautista', 0.00, 5600.00, 131003001.88, 'Core-2', '2026-02-14 07:22:01'),
(321, 149, '554001', 'Office Supplies', 'Expense', '2026-02-14 15:22:01', 150, 'JE-20260228', 'reimbursement', 'REIM-20260214-108', 'Reimbursement for office supplies purchased by Sarah Geronimo', 4100.00, 0.00, 10657824.00, 'Human Resource-2', '2026-02-14 07:22:01'),
(322, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:22:01', 150, 'JE-20260228', 'reimbursement', 'REIM-20260214-108', 'Reimbursement for office supplies purchased by Sarah Geronimo', 0.00, 4100.00, 131007101.88, 'Human Resource-2', '2026-02-14 07:22:01'),
(323, 124, '512001', 'Maintenance & Servicing', 'Expense', '2026-02-14 15:22:01', 151, 'JE-20260229', 'reimbursement', 'REIM-20260214-109', 'Reimbursement for maintenance & servicing purchased by Coco Martin', 9800.00, 0.00, 11455331.00, 'Logistic-1', '2026-02-14 07:22:01'),
(324, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:22:01', 151, 'JE-20260229', 'reimbursement', 'REIM-20260214-109', 'Reimbursement for maintenance & servicing purchased by Coco Martin', 0.00, 9800.00, 131016901.88, 'Logistic-1', '2026-02-14 07:22:01'),
(325, 5, '500000', 'Expenses', 'Expense', '2026-02-14 15:22:01', 152, 'JE-20260230', 'reimbursement', 'REIM-20260214-110', 'Reimbursement for travel expenses purchased by Regine Velasquez', 12500.00, 0.00, 62506.50, 'Core-1', '2026-02-14 07:22:01'),
(326, 113, '223001', 'Salaries Payable', 'Liability', '2026-02-14 15:22:01', 152, 'JE-20260230', 'reimbursement', 'REIM-20260214-110', 'Reimbursement for travel expenses purchased by Regine Velasquez', 0.00, 12500.00, 131029401.88, 'Core-1', '2026-02-14 07:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `journal_number` varchar(50) NOT NULL COMMENT 'JE-1, JE-2, JE-3, etc.',
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `reference_type` enum('vendor_invoice','reimbursement','payroll','driver_payment','receivable','payment','adjustment','other') NOT NULL,
  `reference_id` varchar(100) DEFAULT NULL COMMENT 'Invoice ID, Reimbursement ID, Payroll ID, etc.',
  `description` text DEFAULT NULL,
  `total_debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_balanced` tinyint(1) GENERATED ALWAYS AS (`total_debit` = `total_credit`) STORED,
  `status` enum('draft','posted','reversed') DEFAULT 'posted',
  `created_by` varchar(100) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `journal_number`, `transaction_date`, `reference_type`, `reference_id`, `description`, `total_debit`, `total_credit`, `status`, `created_by`, `posted_at`, `created_at`, `updated_at`) VALUES
(1, 'JE-1', '2025-09-01 08:00:00', 'other', 'OB-2025', 'Initial Capital Setup', 15000000.00, 15000000.00, 'posted', 'System', '2025-09-01 08:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(2, 'JE-2', '2025-11-16 00:00:00', 'vendor_invoice', 'INV-20251116-5498', 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', 12229.00, 12229.00, 'posted', 'System', '2025-11-16 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(3, 'JE-3', '2025-12-05 00:00:00', 'vendor_invoice', 'INV-20251205-0268', 'Acquisition for vendor_invoice: Legal & Compliance (pending)', 13034.00, 13034.00, 'posted', 'System', '2025-12-05 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(4, 'JE-4', '2026-01-06 00:00:00', 'vendor_invoice', 'INV-20260106-4762', 'Acquisition for vendor_invoice: Office Supplies (pending)', 19217.00, 19217.00, 'posted', 'System', '2026-01-06 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(5, 'JE-5', '2025-09-03 00:00:00', 'vendor_invoice', 'INV-20250903-6865', 'Acquisition for vendor_invoice: Tire Replacement (pending)', 18163.00, 18163.00, 'posted', 'System', '2025-09-03 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(6, 'JE-6', '2025-11-05 00:00:00', 'vendor_invoice', 'INV-20251105-3449', 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', 15602.00, 15602.00, 'posted', 'System', '2025-11-05 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(7, 'JE-7', '2025-09-16 00:00:00', 'vendor_invoice', 'INV-20250916-5922', 'Acquisition for vendor_invoice: Tire Replacement (approved)', 24769.00, 24769.00, 'posted', 'System', '2025-09-16 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(8, 'JE-8', '2025-09-22 00:00:00', 'vendor_invoice', 'INV-20250922-1525', 'Acquisition for vendor_invoice: Legal & Compliance (approved)', 9442.00, 9442.00, 'posted', 'System', '2025-09-22 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(9, 'JE-9', '2025-12-13 00:00:00', 'vendor_invoice', 'INV-20251213-3259', 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', 20411.00, 20411.00, 'posted', 'System', '2025-12-13 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(10, 'JE-10', '2025-11-11 00:00:00', 'vendor_invoice', 'INV-20251111-9562', 'Acquisition for vendor_invoice: Maintenance & Servicing (approved)', 5400.00, 5400.00, 'posted', 'System', '2025-11-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(11, 'JE-11', '2025-12-08 00:00:00', 'vendor_invoice', 'INV-20251208-3297', 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', 7643.00, 7643.00, 'posted', 'System', '2025-12-08 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(12, 'JE-12', '2026-01-20 00:00:00', 'vendor_invoice', 'INV-20260120-4565', 'Acquisition for vendor_invoice: Business Taxes (rejected)', 16388.00, 16388.00, 'posted', 'System', '2026-01-20 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(13, 'JE-13', '2026-01-13 00:00:00', 'vendor_invoice', 'INV-20260113-8776', 'Acquisition for vendor_invoice: Maintenance & Servicing (rejected)', 6720.00, 6720.00, 'posted', 'System', '2026-01-13 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(14, 'JE-14', '2026-01-01 00:00:00', 'vendor_invoice', 'INV-20260101-4424', 'Acquisition for vendor_invoice: Tire Replacement (rejected)', 5696.00, 5696.00, 'posted', 'System', '2026-01-01 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(15, 'JE-15', '2026-01-28 00:00:00', 'vendor_invoice', 'INV-20260128-1784', 'Acquisition for vendor_invoice: Legal & Compliance (rejected)', 13143.00, 13143.00, 'posted', 'System', '2026-01-28 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(16, 'JE-16', '2025-09-27 00:00:00', 'vendor_invoice', 'INV-20250927-4380', 'Acquisition for vendor_invoice: Fuel & Energy Costs (rejected)', 10863.00, 10863.00, 'posted', 'System', '2025-09-27 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(17, 'JE-17', '2025-11-02 00:00:00', 'vendor_invoice', 'INV-20251102-3083', 'Acquisition for vendor_invoice: Office Operations Cost (archived)', 5460.00, 5460.00, 'posted', 'System', '2025-11-02 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(18, 'JE-18', '2025-10-02 00:00:00', 'vendor_invoice', 'INV-20251002-8028', 'Acquisition for vendor_invoice: Tire Replacement (archived)', 5153.00, 5153.00, 'posted', 'System', '2025-10-02 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(19, 'JE-19', '2025-12-03 00:00:00', 'vendor_invoice', 'INV-20251203-6849', 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', 9692.00, 9692.00, 'posted', 'System', '2025-12-03 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(20, 'JE-20', '2025-10-03 00:00:00', 'vendor_invoice', 'INV-20251003-1812', 'Acquisition for vendor_invoice: Fuel & Energy Costs (archived)', 5341.00, 5341.00, 'posted', 'System', '2025-10-03 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(21, 'JE-21', '2025-10-10 00:00:00', 'vendor_invoice', 'INV-20251010-5860', 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', 17319.00, 17319.00, 'posted', 'System', '2025-10-10 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(22, 'JE-22', '2026-01-15 00:00:00', 'vendor_invoice', 'INV-20260115-4276', 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', 9456.00, 9456.00, 'posted', 'System', '2026-01-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(23, 'JE-23', '2026-01-15 00:00:00', 'payment', 'INV-20260115-4276', 'Cash Payment for vendor_invoice: INV-20260115-4276', 9456.00, 9456.00, 'posted', 'System', '2026-01-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(24, 'JE-24', '2025-12-18 00:00:00', 'vendor_invoice', 'INV-20251218-8743', 'Acquisition for vendor_invoice: Tire Replacement (paid)', 23159.00, 23159.00, 'posted', 'System', '2025-12-18 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(25, 'JE-25', '2025-12-18 00:00:00', 'payment', 'INV-20251218-8743', 'Cash Payment for vendor_invoice: INV-20251218-8743', 23159.00, 23159.00, 'posted', 'System', '2025-12-18 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(26, 'JE-26', '2025-11-30 00:00:00', 'vendor_invoice', 'INV-20251130-6356', 'Acquisition for vendor_invoice: Legal & Compliance (paid)', 21555.00, 21555.00, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(27, 'JE-27', '2025-11-30 00:00:00', 'payment', 'INV-20251130-6356', 'Cash Payment for vendor_invoice: INV-20251130-6356', 21555.00, 21555.00, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(28, 'JE-28', '2026-01-11 00:00:00', 'vendor_invoice', 'INV-20260111-5925', 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', 8269.00, 8269.00, 'posted', 'System', '2026-01-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(29, 'JE-29', '2026-01-11 00:00:00', 'payment', 'INV-20260111-5925', 'Cash Payment for vendor_invoice: INV-20260111-5925', 8269.00, 8269.00, 'posted', 'System', '2026-01-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(30, 'JE-30', '2025-10-05 00:00:00', 'vendor_invoice', 'INV-20251005-6511', 'Acquisition for vendor_invoice: Fuel & Energy Costs (paid)', 13475.00, 13475.00, 'posted', 'System', '2025-10-05 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(31, 'JE-31', '2025-10-05 00:00:00', 'payment', 'INV-20251005-6511', 'Cash Payment for vendor_invoice: INV-20251005-6511', 13475.00, 13475.00, 'posted', 'System', '2025-10-05 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(32, 'JE-32', '2025-11-18 00:00:00', 'reimbursement', 'REIM-20251118-8974', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 4770.00, 4770.00, 'posted', 'System', '2025-11-18 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(33, 'JE-33', '2025-09-11 00:00:00', 'reimbursement', 'REIM-20250911-1515', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', 1075.00, 1075.00, 'posted', 'System', '2025-09-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(34, 'JE-34', '2025-09-19 00:00:00', 'reimbursement', 'REIM-20250919-9460', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 1214.00, 1214.00, 'posted', 'System', '2025-09-19 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(35, 'JE-35', '2025-11-26 00:00:00', 'reimbursement', 'REIM-20251126-3644', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', 4480.00, 4480.00, 'posted', 'System', '2025-11-26 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(36, 'JE-36', '2025-10-02 00:00:00', 'reimbursement', 'REIM-20251002-1083', 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', 3572.00, 3572.00, 'posted', 'System', '2025-10-02 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(37, 'JE-37', '2025-09-10 00:00:00', 'reimbursement', 'REIM-20250910-5879', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Approved)', 3824.00, 3824.00, 'posted', 'System', '2025-09-10 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(38, 'JE-38', '2025-09-10 00:00:00', 'payment', 'REIM-20250910-5879', 'Cash Payment for reimbursement: REIM-20250910-5879', 3824.00, 3824.00, 'posted', 'System', '2025-09-10 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(39, 'JE-39', '2025-12-28 00:00:00', 'reimbursement', 'REIM-20251228-6049', 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', 3931.00, 3931.00, 'posted', 'System', '2025-12-28 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(40, 'JE-40', '2025-12-28 00:00:00', 'payment', 'REIM-20251228-6049', 'Cash Payment for reimbursement: REIM-20251228-6049', 3931.00, 3931.00, 'posted', 'System', '2025-12-28 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(41, 'JE-41', '2025-09-05 00:00:00', 'reimbursement', 'REIM-20250905-9998', 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', 1935.00, 1935.00, 'posted', 'System', '2025-09-05 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(42, 'JE-42', '2025-09-05 00:00:00', 'payment', 'REIM-20250905-9998', 'Cash Payment for reimbursement: REIM-20250905-9998', 1935.00, 1935.00, 'posted', 'System', '2025-09-05 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(43, 'JE-43', '2025-10-14 00:00:00', 'reimbursement', 'REIM-20251014-5463', 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', 2643.00, 2643.00, 'posted', 'System', '2025-10-14 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(44, 'JE-44', '2025-10-14 00:00:00', 'payment', 'REIM-20251014-5463', 'Cash Payment for reimbursement: REIM-20251014-5463', 2643.00, 2643.00, 'posted', 'System', '2025-10-14 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(45, 'JE-45', '2025-10-31 00:00:00', 'reimbursement', 'REIM-20251031-4753', 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', 2983.00, 2983.00, 'posted', 'System', '2025-10-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(46, 'JE-46', '2025-10-31 00:00:00', 'payment', 'REIM-20251031-4753', 'Cash Payment for reimbursement: REIM-20251031-4753', 2983.00, 2983.00, 'posted', 'System', '2025-10-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(47, 'JE-47', '2025-10-04 00:00:00', 'reimbursement', 'REIM-20251004-6415', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 2560.00, 2560.00, 'posted', 'System', '2025-10-04 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(48, 'JE-48', '2025-10-11 00:00:00', 'reimbursement', 'REIM-20251011-3329', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Rejected)', 2437.00, 2437.00, 'posted', 'System', '2025-10-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(49, 'JE-49', '2025-12-26 00:00:00', 'reimbursement', 'REIM-20251226-2787', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 1187.00, 1187.00, 'posted', 'System', '2025-12-26 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(50, 'JE-50', '2025-10-11 00:00:00', 'reimbursement', 'REIM-20251011-2610', 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', 1061.00, 1061.00, 'posted', 'System', '2025-10-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(51, 'JE-51', '2026-01-01 00:00:00', 'reimbursement', 'REIM-20260101-3113', 'Employee Reimbursement for reimbursement: Office Operations Cost (Rejected)', 2134.00, 2134.00, 'posted', 'System', '2026-01-01 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(52, 'JE-52', '2026-01-09 00:00:00', 'reimbursement', 'REIM-20260109-5751', 'Employee Reimbursement for reimbursement: Travel Expenses (Processing)', 3153.00, 3153.00, 'posted', 'System', '2026-01-09 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(53, 'JE-53', '2026-01-09 00:00:00', 'payment', 'REIM-20260109-5751', 'Cash Payment for reimbursement: REIM-20260109-5751', 3153.00, 3153.00, 'posted', 'System', '2026-01-09 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(54, 'JE-54', '2025-09-08 00:00:00', 'reimbursement', 'REIM-20250908-7503', 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Processing)', 2517.00, 2517.00, 'posted', 'System', '2025-09-08 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(55, 'JE-55', '2025-09-08 00:00:00', 'payment', 'REIM-20250908-7503', 'Cash Payment for reimbursement: REIM-20250908-7503', 2517.00, 2517.00, 'posted', 'System', '2025-09-08 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(56, 'JE-56', '2025-12-22 00:00:00', 'reimbursement', 'REIM-20251222-0486', 'Employee Reimbursement for reimbursement: Office Operations Cost (Processing)', 1598.00, 1598.00, 'posted', 'System', '2025-12-22 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(57, 'JE-57', '2025-12-22 00:00:00', 'payment', 'REIM-20251222-0486', 'Cash Payment for reimbursement: REIM-20251222-0486', 1598.00, 1598.00, 'posted', 'System', '2025-12-22 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(58, 'JE-58', '2025-10-10 00:00:00', 'reimbursement', 'REIM-20251010-7648', 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', 1624.00, 1624.00, 'posted', 'System', '2025-10-10 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(59, 'JE-59', '2025-10-10 00:00:00', 'payment', 'REIM-20251010-7648', 'Cash Payment for reimbursement: REIM-20251010-7648', 1624.00, 1624.00, 'posted', 'System', '2025-10-10 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(60, 'JE-60', '2026-01-08 00:00:00', 'reimbursement', 'REIM-20260108-1530', 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', 3007.00, 3007.00, 'posted', 'System', '2026-01-08 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(61, 'JE-61', '2026-01-08 00:00:00', 'payment', 'REIM-20260108-1530', 'Cash Payment for reimbursement: REIM-20260108-1530', 3007.00, 3007.00, 'posted', 'System', '2026-01-08 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(62, 'JE-62', '2025-11-15 00:00:00', 'driver_payment', 'D-20251115-4541', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', 19935.00, 19935.00, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(63, 'JE-63', '2025-11-11 00:00:00', 'driver_payment', 'D-20251111-7655', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', 28246.00, 28246.00, 'posted', 'System', '2025-11-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(64, 'JE-64', '2025-12-20 00:00:00', 'driver_payment', 'D-20251220-8847', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Pending)', 15052.00, 15052.00, 'posted', 'System', '2025-12-20 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(65, 'JE-65', '2026-01-28 00:00:00', 'driver_payment', 'D-20260128-6001', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Pending)', 20676.00, 20676.00, 'posted', 'System', '2026-01-28 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(66, 'JE-66', '2026-01-08 00:00:00', 'driver_payment', 'D-20260108-6492', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Pending)', 26334.00, 26334.00, 'posted', 'System', '2026-01-08 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(67, 'JE-67', '2026-01-02 00:00:00', 'driver_payment', 'D-20260102-6581', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Approved)', 21299.00, 21299.00, 'posted', 'System', '2026-01-02 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(68, 'JE-68', '2026-01-02 00:00:00', 'payment', 'D-20260102-6581', 'Cash Payment for driver_payment: D-20260102-6581 (Chloe Alexandra)', 21299.00, 21299.00, 'posted', 'System', '2026-01-02 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(69, 'JE-69', '2025-10-07 00:00:00', 'driver_payment', 'D-20251007-3488', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Approved)', 19982.00, 19982.00, 'posted', 'System', '2025-10-07 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(70, 'JE-70', '2025-10-07 00:00:00', 'payment', 'D-20251007-3488', 'Cash Payment for driver_payment: D-20251007-3488 (Olivia Grace)', 19982.00, 19982.00, 'posted', 'System', '2025-10-07 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(71, 'JE-71', '2025-10-19 00:00:00', 'driver_payment', 'D-20251019-8317', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Approved)', 22880.00, 22880.00, 'posted', 'System', '2025-10-19 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(72, 'JE-72', '2025-10-19 00:00:00', 'payment', 'D-20251019-8317', 'Cash Payment for driver_payment: D-20251019-8317 (Ethan Gabriel)', 22880.00, 22880.00, 'posted', 'System', '2025-10-19 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(73, 'JE-73', '2026-01-15 00:00:00', 'driver_payment', 'D-20260115-0668', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Approved)', 23284.00, 23284.00, 'posted', 'System', '2026-01-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(74, 'JE-74', '2026-01-15 00:00:00', 'payment', 'D-20260115-0668', 'Cash Payment for driver_payment: D-20260115-0668 (Lucas Matteo)', 23284.00, 23284.00, 'posted', 'System', '2026-01-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(75, 'JE-75', '2025-09-01 00:00:00', 'driver_payment', 'D-20250901-5726', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Approved)', 25503.00, 25503.00, 'posted', 'System', '2025-09-01 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(76, 'JE-76', '2025-09-01 00:00:00', 'payment', 'D-20250901-5726', 'Cash Payment for driver_payment: D-20250901-5726 (Emma Louise)', 25503.00, 25503.00, 'posted', 'System', '2025-09-01 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(77, 'JE-77', '2025-10-06 00:00:00', 'driver_payment', 'D-20251006-2035', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Paid)', 24708.00, 24708.00, 'posted', 'System', '2025-10-06 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(78, 'JE-78', '2025-10-06 00:00:00', 'payment', 'D-20251006-2035', 'Cash Payment for driver_payment: D-20251006-2035 (Ethan Gabriel)', 24708.00, 24708.00, 'posted', 'System', '2025-10-06 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(79, 'JE-79', '2025-11-23 00:00:00', 'driver_payment', 'D-20251123-5813', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', 26553.00, 26553.00, 'posted', 'System', '2025-11-23 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(80, 'JE-80', '2025-11-23 00:00:00', 'payment', 'D-20251123-5813', 'Cash Payment for driver_payment: D-20251123-5813 (Lucas Matteo)', 26553.00, 26553.00, 'posted', 'System', '2025-11-23 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(81, 'JE-81', '2025-12-06 00:00:00', 'driver_payment', 'D-20251206-5301', 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Paid)', 22600.00, 22600.00, 'posted', 'System', '2025-12-06 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(82, 'JE-82', '2025-12-06 00:00:00', 'payment', 'D-20251206-5301', 'Cash Payment for driver_payment: D-20251206-5301 (Noah Alexander)', 22600.00, 22600.00, 'posted', 'System', '2025-12-06 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(83, 'JE-83', '2026-01-26 00:00:00', 'driver_payment', 'D-20260126-9399', 'Driver Wallet Withdrawal for driver_payment: Jacob Ryan (Paid)', 28601.00, 28601.00, 'posted', 'System', '2026-01-26 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(84, 'JE-84', '2026-01-26 00:00:00', 'payment', 'D-20260126-9399', 'Cash Payment for driver_payment: D-20260126-9399 (Jacob Ryan)', 28601.00, 28601.00, 'posted', 'System', '2026-01-26 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(85, 'JE-85', '2025-09-03 00:00:00', 'driver_payment', 'D-20250903-7854', 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', 23654.00, 23654.00, 'posted', 'System', '2025-09-03 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(86, 'JE-86', '2025-09-03 00:00:00', 'payment', 'D-20250903-7854', 'Cash Payment for driver_payment: D-20250903-7854 (Lucas Matteo)', 23654.00, 23654.00, 'posted', 'System', '2025-09-03 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(87, 'JE-87', '2025-11-17 00:00:00', 'driver_payment', 'D-20251117-3941', 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Rejected)', 22980.00, 22980.00, 'posted', 'System', '2025-11-17 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(88, 'JE-88', '2026-01-26 00:00:00', 'driver_payment', 'D-20260126-8618', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Rejected)', 28328.00, 28328.00, 'posted', 'System', '2026-01-26 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(89, 'JE-89', '2025-10-24 00:00:00', 'driver_payment', 'D-20251024-7533', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Rejected)', 15042.00, 15042.00, 'posted', 'System', '2025-10-24 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(90, 'JE-90', '2026-01-11 00:00:00', 'driver_payment', 'D-20260111-6393', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Rejected)', 25896.00, 25896.00, 'posted', 'System', '2026-01-11 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(91, 'JE-91', '2025-10-21 00:00:00', 'driver_payment', 'D-20251021-1160', 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Rejected)', 19312.00, 19312.00, 'posted', 'System', '2025-10-21 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(92, 'JE-92', '2025-10-04 00:00:00', 'driver_payment', 'D-20251004-9718', 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Archived)', 16043.00, 16043.00, 'posted', 'System', '2025-10-04 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(93, 'JE-93', '2025-12-20 00:00:00', 'driver_payment', 'D-20251220-0831', 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Archived)', 19294.00, 19294.00, 'posted', 'System', '2025-12-20 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(94, 'JE-94', '2025-12-27 00:00:00', 'driver_payment', 'D-20251227-0362', 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Archived)', 25624.00, 25624.00, 'posted', 'System', '2025-12-27 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(95, 'JE-95', '2025-09-02 00:00:00', 'driver_payment', 'D-20250902-4224', 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Archived)', 15638.00, 15638.00, 'posted', 'System', '2025-09-02 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(96, 'JE-96', '2025-09-20 00:00:00', 'driver_payment', 'D-20250920-7572', 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Archived)', 26510.00, 26510.00, 'posted', 'System', '2025-09-20 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(97, 'JE-97', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-3862', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 36886.83, 36886.83, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(98, 'JE-98', '2025-12-31 00:00:00', 'payroll', 'PAY-20251231-1136', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (pending)', 22475.70, 22475.70, 'posted', 'System', '2025-12-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(99, 'JE-99', '2025-12-15 00:00:00', 'payroll', 'PAY-20251215-9504', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (pending)', 24591.22, 24591.22, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(100, 'JE-100', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-6409', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 31100.03, 31100.03, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(101, 'JE-101', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-8693', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 21994.77, 21994.77, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(102, 'JE-102', '2025-11-30 00:00:00', 'payroll', 'PAY-20251130-8655', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (pending)', 24158.53, 24158.53, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(103, 'JE-103', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-2777', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 28185.23, 28185.23, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(104, 'JE-104', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-0962', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', 31731.15, 31731.15, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(105, 'JE-105', '2025-11-30 00:00:00', 'payroll', 'PAY-20251130-6796', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 34615.20, 34615.20, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(106, 'JE-106', '2025-11-30 00:00:00', 'payment', 'PAY-20251130-6796', 'Cash Payment for payroll: PAY-20251130-6796', 31584.44, 31584.44, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(107, 'JE-107', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-5970', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (approved)', 23136.75, 23136.75, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(108, 'JE-108', '2025-11-15 00:00:00', 'payment', 'PAY-20251115-5970', 'Cash Payment for payroll: PAY-20251115-5970', 20679.91, 20679.91, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(109, 'JE-109', '2025-12-15 00:00:00', 'payroll', 'PAY-20251215-9626', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 24230.64, 24230.64, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(110, 'JE-110', '2025-12-15 00:00:00', 'payment', 'PAY-20251215-9626', 'Cash Payment for payroll: PAY-20251215-9626', 21719.11, 21719.11, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(111, 'JE-111', '2025-12-31 00:00:00', 'payroll', 'PAY-20251231-6299', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 31550.75, 31550.75, 'posted', 'System', '2025-12-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(112, 'JE-112', '2025-12-31 00:00:00', 'payment', 'PAY-20251231-6299', 'Cash Payment for payroll: PAY-20251231-6299', 28673.21, 28673.21, 'posted', 'System', '2025-12-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(113, 'JE-113', '2025-11-30 00:00:00', 'payroll', 'PAY-20251130-5014', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 20732.78, 20732.78, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(114, 'JE-114', '2025-11-30 00:00:00', 'payment', 'PAY-20251130-5014', 'Cash Payment for payroll: PAY-20251130-5014', 18396.14, 18396.14, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(115, 'JE-115', '2025-12-31 00:00:00', 'payroll', 'PAY-20251231-2610', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', 25961.40, 25961.40, 'posted', 'System', '2025-12-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(116, 'JE-116', '2025-12-31 00:00:00', 'payment', 'PAY-20251231-2610', 'Cash Payment for payroll: PAY-20251231-2610', 23363.33, 23363.33, 'posted', 'System', '2025-12-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(117, 'JE-117', '2025-11-30 00:00:00', 'payroll', 'PAY-20251130-6633', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', 27428.01, 27428.01, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(118, 'JE-118', '2025-11-30 00:00:00', 'payment', 'PAY-20251130-6633', 'Cash Payment for payroll: PAY-20251130-6633', 24756.61, 24756.61, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(119, 'JE-119', '2025-12-15 00:00:00', 'payroll', 'PAY-20251215-5911', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', 34711.96, 34711.96, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(120, 'JE-120', '2025-12-15 00:00:00', 'payment', 'PAY-20251215-5911', 'Cash Payment for payroll: PAY-20251215-5911', 31676.36, 31676.36, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(121, 'JE-121', '2025-11-30 00:00:00', 'payroll', 'PAY-20251130-8976', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 36237.79, 36237.79, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(122, 'JE-122', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-2697', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 24194.43, 24194.43, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(123, 'JE-123', '2025-12-15 00:00:00', 'payroll', 'PAY-20251215-1555', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 24591.22, 24591.22, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(124, 'JE-124', '2025-11-30 00:00:00', 'payroll', 'PAY-20251130-5174', 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', 32001.48, 32001.48, 'posted', 'System', '2025-11-30 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(125, 'JE-125', '2025-11-15 00:00:00', 'payroll', 'PAY-20251115-0468', 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', 19590.97, 19590.97, 'posted', 'System', '2025-11-15 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(126, 'JE-126', '2025-12-31 00:00:00', 'payroll', 'PAY-20251231-9305', 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (rejected)', 25961.40, 25961.40, 'posted', 'System', '2025-12-31 00:00:00', '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(127, 'JE-127', '2025-12-15 00:00:00', 'payroll', 'PAY-20251215-3522', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 29026.58, 29026.58, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:51', '2026-02-10 10:22:51'),
(128, 'JE-128', '2025-12-15 00:00:00', 'payroll', 'PAY-20251215-1857', 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', 32788.86, 32788.86, 'posted', 'System', '2025-12-15 00:00:00', '2026-02-10 10:22:51', '2026-02-10 10:22:51'),
(129, 'JE-129', '2026-02-10 23:21:35', 'vendor_invoice', 'INV-20251116-5498', 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 12229.00, 12229.00, 'posted', 'System', '2026-02-10 23:21:35', '2026-02-10 15:21:35', '2026-02-10 15:21:35'),
(130, 'JE-130', '2026-02-12 09:14:45', 'vendor_invoice', 'INV-20260201-7327', 'Vendor invoice for direct operating costs - SpeedFix Auto Service Center', 57048.80, 57048.80, 'posted', 'System', '2026-02-12 09:14:45', '2026-02-12 01:14:45', '2026-02-12 01:14:45'),
(131, 'JE-131', '2026-02-12 09:21:56', '', 'PR-1202503-20251215', 'Payroll for December 2025 - HR Assistant', 22061.66, 22061.66, 'posted', 'System', '2026-02-12 09:21:56', '2026-02-12 01:21:56', '2026-02-12 01:21:56'),
(132, 'JE-132', '2026-02-12 09:53:40', '', 'PR-1202502-20251231', 'Payroll for December 2025 - HR Specialist', 20051.91, 20051.91, 'posted', 'System', '2026-02-12 09:53:40', '2026-02-12 01:53:40', '2026-02-12 01:53:40'),
(134, 'JE-20260212-0001', '2026-02-12 00:00:00', '', 'INIT-REV-1770886913', 'Initial Revenue Recognition for Dashboard', 180000.00, 180000.00, 'posted', 'System', '2026-02-12 17:01:53', '2026-02-12 09:01:53', '2026-02-12 09:01:53'),
(135, 'JE-20260213', '2026-02-13 22:05:17', 'vendor_invoice', 'INV-2026-TEST-01', 'Vendor invoice for office supplies - ViaHale Supplier Co.', 25500.00, 25500.00, 'posted', 'System', '2026-02-13 22:05:17', '2026-02-13 14:05:17', '2026-02-13 14:05:17'),
(136, 'JE-20260214', '2026-02-13 22:22:06', 'vendor_invoice', 'INV-20250903-6865', 'Vendor invoice for direct operating costs - AIG Insurance Phils', 18163.00, 18163.00, 'posted', 'System', '2026-02-13 22:22:06', '2026-02-13 14:22:06', '2026-02-13 14:22:06'),
(137, 'JE-20260215', '2026-02-13 23:29:04', 'vendor_invoice', 'INV-20251105-3449', 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 15602.00, 15602.00, 'posted', 'System', '2026-02-13 23:29:04', '2026-02-13 15:29:04', '2026-02-13 15:29:04'),
(138, 'JE-20260216', '2026-02-14 12:02:59', 'vendor_invoice', 'INV-20260213-1001', 'Vendor invoice for direct operating costs - AutoParts Supply Co.', 8500.00, 8500.00, 'posted', 'System', '2026-02-14 12:02:59', '2026-02-14 04:02:59', '2026-02-14 04:02:59'),
(139, 'JE-20260217', '2026-02-14 12:03:56', 'vendor_invoice', 'INV-20260213-1002', 'Vendor invoice for supplies & technology - Office Depot Manila', 12300.00, 12300.00, 'posted', 'System', '2026-02-14 12:03:56', '2026-02-14 04:03:56', '2026-02-14 04:03:56'),
(140, 'JE-20260218', '2026-02-14 12:48:13', 'reimbursement', 'REIM-20260214-103', 'Reimbursement for office operations cost purchased by Ana Clara', 4500.00, 4500.00, 'posted', 'System', '2026-02-14 12:48:13', '2026-02-14 04:48:13', '2026-02-14 04:48:13'),
(141, 'JE-20260219', '2026-02-14 14:04:53', 'vendor_invoice', 'INV-20260213-1003', 'Vendor invoice for supplies & technology - TechSolutions Inc.', 25000.00, 25000.00, 'posted', 'System', '2026-02-14 14:04:53', '2026-02-14 06:04:53', '2026-02-14 06:04:53'),
(142, 'JE-20260220', '2026-02-14 15:18:37', 'vendor_invoice', 'INV-20260214-5001', 'Vendor invoice for indirect costs - CleanPro Services Inc.', 11500.00, 11500.00, 'posted', 'System', '2026-02-14 15:18:37', '2026-02-14 07:18:37', '2026-02-14 07:18:37'),
(143, 'JE-20260221', '2026-02-14 15:19:55', 'vendor_invoice', 'INV-20260214-5002', 'Vendor invoice for indirect costs - PLDT Fibr Business', 8900.00, 8900.00, 'posted', 'System', '2026-02-14 15:19:55', '2026-02-14 07:19:55', '2026-02-14 07:19:55'),
(144, 'JE-20260222', '2026-02-14 15:20:29', 'reimbursement', 'REIM-20260214-101', 'Reimbursement for office supplies purchased by Maria Santos', 3500.00, 3500.00, 'posted', 'System', '2026-02-14 15:20:29', '2026-02-14 07:20:29', '2026-02-14 07:20:29'),
(145, 'JE-20260223', '2026-02-14 15:21:29', 'reimbursement', 'REIM-20260214-102', 'Reimbursement for travel expenses purchased by Jose Reyes', 8200.50, 8200.50, 'posted', 'System', '2026-02-14 15:21:29', '2026-02-14 07:21:29', '2026-02-14 07:21:29'),
(146, 'JE-20260224', '2026-02-14 15:22:01', 'reimbursement', 'REIM-20260214-104', 'Reimbursement for maintenance & servicing purchased by Rafael Garcia', 15000.00, 15000.00, 'posted', 'System', '2026-02-14 15:22:01', '2026-02-14 07:22:01', '2026-02-14 07:22:01'),
(147, 'JE-20260225', '2026-02-14 15:22:01', 'reimbursement', 'REIM-20260214-105', 'Reimbursement for travel expenses purchased by Lito Lapid', 6750.00, 6750.00, 'posted', 'System', '2026-02-14 15:22:01', '2026-02-14 07:22:01', '2026-02-14 07:22:01'),
(148, 'JE-20260226', '2026-02-14 15:22:01', 'reimbursement', 'REIM-20260214-106', 'Reimbursement for office supplies purchased by Grace Tan', 3200.00, 3200.00, 'posted', 'System', '2026-02-14 15:22:01', '2026-02-14 07:22:01', '2026-02-14 07:22:01'),
(149, 'JE-20260227', '2026-02-14 15:22:01', 'reimbursement', 'REIM-20260214-107', 'Reimbursement for office operations cost purchased by Mark Bautista', 5600.00, 5600.00, 'posted', 'System', '2026-02-14 15:22:01', '2026-02-14 07:22:01', '2026-02-14 07:22:01'),
(150, 'JE-20260228', '2026-02-14 15:22:01', 'reimbursement', 'REIM-20260214-108', 'Reimbursement for office supplies purchased by Sarah Geronimo', 4100.00, 4100.00, 'posted', 'System', '2026-02-14 15:22:01', '2026-02-14 07:22:01', '2026-02-14 07:22:01'),
(151, 'JE-20260229', '2026-02-14 15:22:01', 'reimbursement', 'REIM-20260214-109', 'Reimbursement for maintenance & servicing purchased by Coco Martin', 9800.00, 9800.00, 'posted', 'System', '2026-02-14 15:22:01', '2026-02-14 07:22:01', '2026-02-14 07:22:01'),
(152, 'JE-20260230', '2026-02-14 15:22:01', 'reimbursement', 'REIM-20260214-110', 'Reimbursement for travel expenses purchased by Regine Velasquez', 12500.00, 12500.00, 'posted', 'System', '2026-02-14 15:22:01', '2026-02-14 07:22:01', '2026-02-14 07:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` int(11) NOT NULL,
  `journal_entry_id` int(11) NOT NULL,
  `line_number` int(3) NOT NULL COMMENT 'Line sequence in journal entry',
  `gl_account_id` int(11) NOT NULL COMMENT 'FK to chart_of_accounts_hierarchy',
  `gl_account_code` varchar(20) NOT NULL COMMENT 'Denormalized for performance',
  `gl_account_name` varchar(100) NOT NULL COMMENT 'Denormalized for performance',
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `debit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `journal_entry_lines`
--

INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `line_number`, `gl_account_id`, `gl_account_code`, `gl_account_name`, `account_type`, `debit_amount`, `credit_amount`, `description`, `department`, `created_at`) VALUES
(1, 1, 1, 97, '111001', 'Cash on Hand', 'Asset', 15000000.00, 0.00, 'Initial Capital Setup', NULL, '2026-02-10 10:22:50'),
(2, 1, 2, 116, '311001', 'Owner\'s Capital', 'Equity', 0.00, 15000000.00, 'Initial Capital Setup', NULL, '2026-02-10 10:22:50'),
(3, 2, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 12229.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', NULL, '2026-02-10 10:22:50'),
(4, 2, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 12229.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', NULL, '2026-02-10 10:22:50'),
(5, 3, 1, 148, '553001', 'Legal & Compliance', 'Expense', 13034.00, 0.00, 'Acquisition for vendor_invoice: Legal & Compliance (pending)', NULL, '2026-02-10 10:22:50'),
(6, 3, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 13034.00, 'Acquisition for vendor_invoice: Legal & Compliance (pending)', NULL, '2026-02-10 10:22:50'),
(7, 4, 1, 149, '554001', 'Office Supplies', 'Expense', 19217.00, 0.00, 'Acquisition for vendor_invoice: Office Supplies (pending)', NULL, '2026-02-10 10:22:50'),
(8, 4, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 19217.00, 'Acquisition for vendor_invoice: Office Supplies (pending)', NULL, '2026-02-10 10:22:50'),
(9, 5, 1, 125, '513001', 'Tire Replacement', 'Expense', 18163.00, 0.00, 'Acquisition for vendor_invoice: Tire Replacement (pending)', NULL, '2026-02-10 10:22:50'),
(10, 5, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 18163.00, 'Acquisition for vendor_invoice: Tire Replacement (pending)', NULL, '2026-02-10 10:22:50'),
(11, 6, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 15602.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', NULL, '2026-02-10 10:22:50'),
(12, 6, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 15602.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (pending)', NULL, '2026-02-10 10:22:50'),
(13, 7, 1, 125, '513001', 'Tire Replacement', 'Expense', 24769.00, 0.00, 'Acquisition for vendor_invoice: Tire Replacement (approved)', NULL, '2026-02-10 10:22:50'),
(14, 7, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 24769.00, 'Acquisition for vendor_invoice: Tire Replacement (approved)', NULL, '2026-02-10 10:22:50'),
(15, 8, 1, 148, '553001', 'Legal & Compliance', 'Expense', 9442.00, 0.00, 'Acquisition for vendor_invoice: Legal & Compliance (approved)', NULL, '2026-02-10 10:22:50'),
(16, 8, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 9442.00, 'Acquisition for vendor_invoice: Legal & Compliance (approved)', NULL, '2026-02-10 10:22:50'),
(17, 9, 1, 123, '511001', 'Fuel & Energy Costs', 'Expense', 20411.00, 0.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', NULL, '2026-02-10 10:22:50'),
(18, 9, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 20411.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', NULL, '2026-02-10 10:22:50'),
(19, 10, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 5400.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (approved)', NULL, '2026-02-10 10:22:50'),
(20, 10, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 5400.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (approved)', NULL, '2026-02-10 10:22:50'),
(21, 11, 1, 123, '511001', 'Fuel & Energy Costs', 'Expense', 7643.00, 0.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', NULL, '2026-02-10 10:22:50'),
(22, 11, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 7643.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (approved)', NULL, '2026-02-10 10:22:50'),
(23, 12, 1, 164, '584001', 'Business Taxes', 'Expense', 16388.00, 0.00, 'Acquisition for vendor_invoice: Business Taxes (rejected)', NULL, '2026-02-10 10:22:50'),
(24, 12, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 16388.00, 'Acquisition for vendor_invoice: Business Taxes (rejected)', NULL, '2026-02-10 10:22:50'),
(25, 13, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 6720.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (rejected)', NULL, '2026-02-10 10:22:50'),
(26, 13, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 6720.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (rejected)', NULL, '2026-02-10 10:22:50'),
(27, 14, 1, 125, '513001', 'Tire Replacement', 'Expense', 5696.00, 0.00, 'Acquisition for vendor_invoice: Tire Replacement (rejected)', NULL, '2026-02-10 10:22:50'),
(28, 14, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 5696.00, 'Acquisition for vendor_invoice: Tire Replacement (rejected)', NULL, '2026-02-10 10:22:50'),
(29, 15, 1, 148, '553001', 'Legal & Compliance', 'Expense', 13143.00, 0.00, 'Acquisition for vendor_invoice: Legal & Compliance (rejected)', NULL, '2026-02-10 10:22:50'),
(30, 15, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 13143.00, 'Acquisition for vendor_invoice: Legal & Compliance (rejected)', NULL, '2026-02-10 10:22:50'),
(31, 16, 1, 123, '511001', 'Fuel & Energy Costs', 'Expense', 10863.00, 0.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (rejected)', NULL, '2026-02-10 10:22:50'),
(32, 16, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 10863.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (rejected)', NULL, '2026-02-10 10:22:50'),
(33, 17, 1, 146, '551001', 'Office Operations Cost', 'Expense', 5460.00, 0.00, 'Acquisition for vendor_invoice: Office Operations Cost (archived)', NULL, '2026-02-10 10:22:50'),
(34, 17, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 5460.00, 'Acquisition for vendor_invoice: Office Operations Cost (archived)', NULL, '2026-02-10 10:22:50'),
(35, 18, 1, 125, '513001', 'Tire Replacement', 'Expense', 5153.00, 0.00, 'Acquisition for vendor_invoice: Tire Replacement (archived)', NULL, '2026-02-10 10:22:50'),
(36, 18, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 5153.00, 'Acquisition for vendor_invoice: Tire Replacement (archived)', NULL, '2026-02-10 10:22:50'),
(37, 19, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 9692.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', NULL, '2026-02-10 10:22:50'),
(38, 19, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 9692.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', NULL, '2026-02-10 10:22:50'),
(39, 20, 1, 123, '511001', 'Fuel & Energy Costs', 'Expense', 5341.00, 0.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (archived)', NULL, '2026-02-10 10:22:50'),
(40, 20, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 5341.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (archived)', NULL, '2026-02-10 10:22:50'),
(41, 21, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 17319.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', NULL, '2026-02-10 10:22:50'),
(42, 21, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 17319.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (archived)', NULL, '2026-02-10 10:22:50'),
(43, 22, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 9456.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', NULL, '2026-02-10 10:22:50'),
(44, 22, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 9456.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', NULL, '2026-02-10 10:22:50'),
(45, 23, 1, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 9456.00, 0.00, 'Cash Payment for vendor_invoice: INV-20260115-4276', NULL, '2026-02-10 10:22:50'),
(46, 23, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 9456.00, 'Cash Payment for vendor_invoice: INV-20260115-4276', NULL, '2026-02-10 10:22:50'),
(47, 24, 1, 125, '513001', 'Tire Replacement', 'Expense', 23159.00, 0.00, 'Acquisition for vendor_invoice: Tire Replacement (paid)', NULL, '2026-02-10 10:22:50'),
(48, 24, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 23159.00, 'Acquisition for vendor_invoice: Tire Replacement (paid)', NULL, '2026-02-10 10:22:50'),
(49, 25, 1, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 23159.00, 0.00, 'Cash Payment for vendor_invoice: INV-20251218-8743', NULL, '2026-02-10 10:22:50'),
(50, 25, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 23159.00, 'Cash Payment for vendor_invoice: INV-20251218-8743', NULL, '2026-02-10 10:22:50'),
(51, 26, 1, 148, '553001', 'Legal & Compliance', 'Expense', 21555.00, 0.00, 'Acquisition for vendor_invoice: Legal & Compliance (paid)', NULL, '2026-02-10 10:22:50'),
(52, 26, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 21555.00, 'Acquisition for vendor_invoice: Legal & Compliance (paid)', NULL, '2026-02-10 10:22:50'),
(53, 27, 1, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 21555.00, 0.00, 'Cash Payment for vendor_invoice: INV-20251130-6356', NULL, '2026-02-10 10:22:50'),
(54, 27, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 21555.00, 'Cash Payment for vendor_invoice: INV-20251130-6356', NULL, '2026-02-10 10:22:50'),
(55, 28, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 8269.00, 0.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', NULL, '2026-02-10 10:22:50'),
(56, 28, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 8269.00, 'Acquisition for vendor_invoice: Maintenance & Servicing (paid)', NULL, '2026-02-10 10:22:50'),
(57, 29, 1, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 8269.00, 0.00, 'Cash Payment for vendor_invoice: INV-20260111-5925', NULL, '2026-02-10 10:22:50'),
(58, 29, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 8269.00, 'Cash Payment for vendor_invoice: INV-20260111-5925', NULL, '2026-02-10 10:22:50'),
(59, 30, 1, 123, '511001', 'Fuel & Energy Costs', 'Expense', 13475.00, 0.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (paid)', NULL, '2026-02-10 10:22:50'),
(60, 30, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 13475.00, 'Acquisition for vendor_invoice: Fuel & Energy Costs (paid)', NULL, '2026-02-10 10:22:50'),
(61, 31, 1, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 13475.00, 0.00, 'Cash Payment for vendor_invoice: INV-20251005-6511', NULL, '2026-02-10 10:22:50'),
(62, 31, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 13475.00, 'Cash Payment for vendor_invoice: INV-20251005-6511', NULL, '2026-02-10 10:22:50'),
(63, 32, 1, 172, '611001', 'Travel Expenses', 'Expense', 4770.00, 0.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', NULL, '2026-02-10 10:22:50'),
(64, 32, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 4770.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', NULL, '2026-02-10 10:22:50'),
(65, 33, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 1075.00, 0.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', NULL, '2026-02-10 10:22:50'),
(66, 33, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 1075.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', NULL, '2026-02-10 10:22:50'),
(67, 34, 1, 172, '611001', 'Travel Expenses', 'Expense', 1214.00, 0.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', NULL, '2026-02-10 10:22:50'),
(68, 34, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 1214.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', NULL, '2026-02-10 10:22:50'),
(69, 35, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 4480.00, 0.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', NULL, '2026-02-10 10:22:50'),
(70, 35, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 4480.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Pending)', NULL, '2026-02-10 10:22:50'),
(71, 36, 1, 172, '611001', 'Travel Expenses', 'Expense', 3572.00, 0.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', NULL, '2026-02-10 10:22:50'),
(72, 36, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 3572.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Pending)', NULL, '2026-02-10 10:22:50'),
(73, 37, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 3824.00, 0.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Approved)', NULL, '2026-02-10 10:22:50'),
(74, 37, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 3824.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Approved)', NULL, '2026-02-10 10:22:50'),
(75, 38, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 3824.00, 0.00, 'Cash Payment for reimbursement: REIM-20250910-5879', NULL, '2026-02-10 10:22:50'),
(76, 38, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 3824.00, 'Cash Payment for reimbursement: REIM-20250910-5879', NULL, '2026-02-10 10:22:50'),
(77, 39, 1, 149, '554001', 'Office Supplies', 'Expense', 3931.00, 0.00, 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', NULL, '2026-02-10 10:22:50'),
(78, 39, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 3931.00, 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', NULL, '2026-02-10 10:22:50'),
(79, 40, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 3931.00, 0.00, 'Cash Payment for reimbursement: REIM-20251228-6049', NULL, '2026-02-10 10:22:50'),
(80, 40, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 3931.00, 'Cash Payment for reimbursement: REIM-20251228-6049', NULL, '2026-02-10 10:22:50'),
(81, 41, 1, 146, '551001', 'Office Operations Cost', 'Expense', 1935.00, 0.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', NULL, '2026-02-10 10:22:50'),
(82, 41, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 1935.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', NULL, '2026-02-10 10:22:50'),
(83, 42, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 1935.00, 0.00, 'Cash Payment for reimbursement: REIM-20250905-9998', NULL, '2026-02-10 10:22:50'),
(84, 42, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 1935.00, 'Cash Payment for reimbursement: REIM-20250905-9998', NULL, '2026-02-10 10:22:50'),
(85, 43, 1, 149, '554001', 'Office Supplies', 'Expense', 2643.00, 0.00, 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', NULL, '2026-02-10 10:22:50'),
(86, 43, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 2643.00, 'Employee Reimbursement for reimbursement: Office Supplies (Approved)', NULL, '2026-02-10 10:22:50'),
(87, 44, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 2643.00, 0.00, 'Cash Payment for reimbursement: REIM-20251014-5463', NULL, '2026-02-10 10:22:50'),
(88, 44, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 2643.00, 'Cash Payment for reimbursement: REIM-20251014-5463', NULL, '2026-02-10 10:22:50'),
(89, 45, 1, 146, '551001', 'Office Operations Cost', 'Expense', 2983.00, 0.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', NULL, '2026-02-10 10:22:50'),
(90, 45, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 2983.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Approved)', NULL, '2026-02-10 10:22:50'),
(91, 46, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 2983.00, 0.00, 'Cash Payment for reimbursement: REIM-20251031-4753', NULL, '2026-02-10 10:22:50'),
(92, 46, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 2983.00, 'Cash Payment for reimbursement: REIM-20251031-4753', NULL, '2026-02-10 10:22:50'),
(93, 47, 1, 149, '554001', 'Office Supplies', 'Expense', 2560.00, 0.00, 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', NULL, '2026-02-10 10:22:50'),
(94, 47, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 2560.00, 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', NULL, '2026-02-10 10:22:50'),
(95, 48, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 2437.00, 0.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Rejected)', NULL, '2026-02-10 10:22:50'),
(96, 48, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 2437.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Rejected)', NULL, '2026-02-10 10:22:50'),
(97, 49, 1, 149, '554001', 'Office Supplies', 'Expense', 1187.00, 0.00, 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', NULL, '2026-02-10 10:22:50'),
(98, 49, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 1187.00, 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', NULL, '2026-02-10 10:22:50'),
(99, 50, 1, 149, '554001', 'Office Supplies', 'Expense', 1061.00, 0.00, 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', NULL, '2026-02-10 10:22:50'),
(100, 50, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 1061.00, 'Employee Reimbursement for reimbursement: Office Supplies (Rejected)', NULL, '2026-02-10 10:22:50'),
(101, 51, 1, 146, '551001', 'Office Operations Cost', 'Expense', 2134.00, 0.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Rejected)', NULL, '2026-02-10 10:22:50'),
(102, 51, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 2134.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Rejected)', NULL, '2026-02-10 10:22:50'),
(103, 52, 1, 172, '611001', 'Travel Expenses', 'Expense', 3153.00, 0.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Processing)', NULL, '2026-02-10 10:22:50'),
(104, 52, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 3153.00, 'Employee Reimbursement for reimbursement: Travel Expenses (Processing)', NULL, '2026-02-10 10:22:50'),
(105, 53, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 3153.00, 0.00, 'Cash Payment for reimbursement: REIM-20260109-5751', NULL, '2026-02-10 10:22:50'),
(106, 53, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 3153.00, 'Cash Payment for reimbursement: REIM-20260109-5751', NULL, '2026-02-10 10:22:50'),
(107, 54, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 2517.00, 0.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Processing)', NULL, '2026-02-10 10:22:50'),
(108, 54, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 2517.00, 'Employee Reimbursement for reimbursement: Maintenance & Servicing (Processing)', NULL, '2026-02-10 10:22:50'),
(109, 55, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 2517.00, 0.00, 'Cash Payment for reimbursement: REIM-20250908-7503', NULL, '2026-02-10 10:22:50'),
(110, 55, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 2517.00, 'Cash Payment for reimbursement: REIM-20250908-7503', NULL, '2026-02-10 10:22:50'),
(111, 56, 1, 146, '551001', 'Office Operations Cost', 'Expense', 1598.00, 0.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Processing)', NULL, '2026-02-10 10:22:50'),
(112, 56, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 1598.00, 'Employee Reimbursement for reimbursement: Office Operations Cost (Processing)', NULL, '2026-02-10 10:22:50'),
(113, 57, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 1598.00, 0.00, 'Cash Payment for reimbursement: REIM-20251222-0486', NULL, '2026-02-10 10:22:50'),
(114, 57, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 1598.00, 'Cash Payment for reimbursement: REIM-20251222-0486', NULL, '2026-02-10 10:22:50'),
(115, 58, 1, 149, '554001', 'Office Supplies', 'Expense', 1624.00, 0.00, 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', NULL, '2026-02-10 10:22:50'),
(116, 58, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 1624.00, 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', NULL, '2026-02-10 10:22:50'),
(117, 59, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 1624.00, 0.00, 'Cash Payment for reimbursement: REIM-20251010-7648', NULL, '2026-02-10 10:22:50'),
(118, 59, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 1624.00, 'Cash Payment for reimbursement: REIM-20251010-7648', NULL, '2026-02-10 10:22:50'),
(119, 60, 1, 149, '554001', 'Office Supplies', 'Expense', 3007.00, 0.00, 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', NULL, '2026-02-10 10:22:50'),
(120, 60, 2, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 0.00, 3007.00, 'Employee Reimbursement for reimbursement: Office Supplies (Processing)', NULL, '2026-02-10 10:22:50'),
(121, 61, 1, 110, '212001', 'Accounts Payable - Service Providers', 'Liability', 3007.00, 0.00, 'Cash Payment for reimbursement: REIM-20260108-1530', NULL, '2026-02-10 10:22:50'),
(122, 61, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 3007.00, 'Cash Payment for reimbursement: REIM-20260108-1530', NULL, '2026-02-10 10:22:50'),
(123, 62, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 19935.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', NULL, '2026-02-10 10:22:50'),
(124, 62, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 19935.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', NULL, '2026-02-10 10:22:50'),
(125, 63, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 28246.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', NULL, '2026-02-10 10:22:50'),
(126, 63, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 28246.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Pending)', NULL, '2026-02-10 10:22:50'),
(127, 64, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 15052.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Pending)', NULL, '2026-02-10 10:22:50'),
(128, 64, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 15052.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Pending)', NULL, '2026-02-10 10:22:50'),
(129, 65, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 20676.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Pending)', NULL, '2026-02-10 10:22:50'),
(130, 65, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 20676.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Pending)', NULL, '2026-02-10 10:22:50'),
(131, 66, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 26334.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Pending)', NULL, '2026-02-10 10:22:50'),
(132, 66, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 26334.00, 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Pending)', NULL, '2026-02-10 10:22:50'),
(133, 67, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 21299.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Approved)', NULL, '2026-02-10 10:22:50'),
(134, 67, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 21299.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Approved)', NULL, '2026-02-10 10:22:50'),
(135, 68, 1, 222, '213003', 'Driver Earnings Payable', '', 21299.00, 0.00, 'Cash Payment for driver_payment: D-20260102-6581 (Chloe Alexandra)', NULL, '2026-02-10 10:22:50'),
(136, 68, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 21299.00, 'Cash Payment for driver_payment: D-20260102-6581 (Chloe Alexandra)', NULL, '2026-02-10 10:22:50'),
(137, 69, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 19982.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Approved)', NULL, '2026-02-10 10:22:50'),
(138, 69, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 19982.00, 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Approved)', NULL, '2026-02-10 10:22:50'),
(139, 70, 1, 222, '213003', 'Driver Earnings Payable', '', 19982.00, 0.00, 'Cash Payment for driver_payment: D-20251007-3488 (Olivia Grace)', NULL, '2026-02-10 10:22:50'),
(140, 70, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 19982.00, 'Cash Payment for driver_payment: D-20251007-3488 (Olivia Grace)', NULL, '2026-02-10 10:22:50'),
(141, 71, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 22880.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Approved)', NULL, '2026-02-10 10:22:50'),
(142, 71, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 22880.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Approved)', NULL, '2026-02-10 10:22:50'),
(143, 72, 1, 222, '213003', 'Driver Earnings Payable', '', 22880.00, 0.00, 'Cash Payment for driver_payment: D-20251019-8317 (Ethan Gabriel)', NULL, '2026-02-10 10:22:50'),
(144, 72, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 22880.00, 'Cash Payment for driver_payment: D-20251019-8317 (Ethan Gabriel)', NULL, '2026-02-10 10:22:50'),
(145, 73, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 23284.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Approved)', NULL, '2026-02-10 10:22:50'),
(146, 73, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 23284.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Approved)', NULL, '2026-02-10 10:22:50'),
(147, 74, 1, 222, '213003', 'Driver Earnings Payable', '', 23284.00, 0.00, 'Cash Payment for driver_payment: D-20260115-0668 (Lucas Matteo)', NULL, '2026-02-10 10:22:50'),
(148, 74, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 23284.00, 'Cash Payment for driver_payment: D-20260115-0668 (Lucas Matteo)', NULL, '2026-02-10 10:22:50'),
(149, 75, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 25503.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Approved)', NULL, '2026-02-10 10:22:50'),
(150, 75, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 25503.00, 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Approved)', NULL, '2026-02-10 10:22:50'),
(151, 76, 1, 222, '213003', 'Driver Earnings Payable', '', 25503.00, 0.00, 'Cash Payment for driver_payment: D-20250901-5726 (Emma Louise)', NULL, '2026-02-10 10:22:50'),
(152, 76, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 25503.00, 'Cash Payment for driver_payment: D-20250901-5726 (Emma Louise)', NULL, '2026-02-10 10:22:50'),
(153, 77, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 24708.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Paid)', NULL, '2026-02-10 10:22:50'),
(154, 77, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 24708.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Paid)', NULL, '2026-02-10 10:22:50'),
(155, 78, 1, 222, '213003', 'Driver Earnings Payable', '', 24708.00, 0.00, 'Cash Payment for driver_payment: D-20251006-2035 (Ethan Gabriel)', NULL, '2026-02-10 10:22:50'),
(156, 78, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 24708.00, 'Cash Payment for driver_payment: D-20251006-2035 (Ethan Gabriel)', NULL, '2026-02-10 10:22:50'),
(157, 79, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 26553.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', NULL, '2026-02-10 10:22:50'),
(158, 79, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 26553.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', NULL, '2026-02-10 10:22:50'),
(159, 80, 1, 222, '213003', 'Driver Earnings Payable', '', 26553.00, 0.00, 'Cash Payment for driver_payment: D-20251123-5813 (Lucas Matteo)', NULL, '2026-02-10 10:22:50'),
(160, 80, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 26553.00, 'Cash Payment for driver_payment: D-20251123-5813 (Lucas Matteo)', NULL, '2026-02-10 10:22:50'),
(161, 81, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 22600.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Paid)', NULL, '2026-02-10 10:22:50'),
(162, 81, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 22600.00, 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Paid)', NULL, '2026-02-10 10:22:50'),
(163, 82, 1, 222, '213003', 'Driver Earnings Payable', '', 22600.00, 0.00, 'Cash Payment for driver_payment: D-20251206-5301 (Noah Alexander)', NULL, '2026-02-10 10:22:50'),
(164, 82, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 22600.00, 'Cash Payment for driver_payment: D-20251206-5301 (Noah Alexander)', NULL, '2026-02-10 10:22:50'),
(165, 83, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 28601.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Jacob Ryan (Paid)', NULL, '2026-02-10 10:22:50'),
(166, 83, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 28601.00, 'Driver Wallet Withdrawal for driver_payment: Jacob Ryan (Paid)', NULL, '2026-02-10 10:22:50'),
(167, 84, 1, 222, '213003', 'Driver Earnings Payable', '', 28601.00, 0.00, 'Cash Payment for driver_payment: D-20260126-9399 (Jacob Ryan)', NULL, '2026-02-10 10:22:50'),
(168, 84, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 28601.00, 'Cash Payment for driver_payment: D-20260126-9399 (Jacob Ryan)', NULL, '2026-02-10 10:22:50'),
(169, 85, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 23654.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', NULL, '2026-02-10 10:22:50'),
(170, 85, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 23654.00, 'Driver Wallet Withdrawal for driver_payment: Lucas Matteo (Paid)', NULL, '2026-02-10 10:22:50'),
(171, 86, 1, 222, '213003', 'Driver Earnings Payable', '', 23654.00, 0.00, 'Cash Payment for driver_payment: D-20250903-7854 (Lucas Matteo)', NULL, '2026-02-10 10:22:50'),
(172, 86, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 23654.00, 'Cash Payment for driver_payment: D-20250903-7854 (Lucas Matteo)', NULL, '2026-02-10 10:22:50'),
(173, 87, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 22980.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Rejected)', NULL, '2026-02-10 10:22:50'),
(174, 87, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 22980.00, 'Driver Wallet Withdrawal for driver_payment: Noah Alexander (Rejected)', NULL, '2026-02-10 10:22:50'),
(175, 88, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 28328.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Rejected)', NULL, '2026-02-10 10:22:50'),
(176, 88, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 28328.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Rejected)', NULL, '2026-02-10 10:22:50'),
(177, 89, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 15042.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Rejected)', NULL, '2026-02-10 10:22:50'),
(178, 89, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 15042.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Rejected)', NULL, '2026-02-10 10:22:50'),
(179, 90, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 25896.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Rejected)', NULL, '2026-02-10 10:22:50'),
(180, 90, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 25896.00, 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Rejected)', NULL, '2026-02-10 10:22:50'),
(181, 91, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 19312.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Rejected)', NULL, '2026-02-10 10:22:50'),
(182, 91, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 19312.00, 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Rejected)', NULL, '2026-02-10 10:22:50'),
(183, 92, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 16043.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Archived)', NULL, '2026-02-10 10:22:50'),
(184, 92, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 16043.00, 'Driver Wallet Withdrawal for driver_payment: Ethan Gabriel (Archived)', NULL, '2026-02-10 10:22:50'),
(185, 93, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 19294.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Archived)', NULL, '2026-02-10 10:22:50'),
(186, 93, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 19294.00, 'Driver Wallet Withdrawal for driver_payment: Chloe Alexandra (Archived)', NULL, '2026-02-10 10:22:50'),
(187, 94, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 25624.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Archived)', NULL, '2026-02-10 10:22:50'),
(188, 94, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 25624.00, 'Driver Wallet Withdrawal for driver_payment: Olivia Grace (Archived)', NULL, '2026-02-10 10:22:50'),
(189, 95, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 15638.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Archived)', NULL, '2026-02-10 10:22:50'),
(190, 95, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 15638.00, 'Driver Wallet Withdrawal for driver_payment: Emma Louise (Archived)', NULL, '2026-02-10 10:22:50'),
(191, 96, 1, 220, '213001', 'Driver Wallet Payable', 'Liability', 26510.00, 0.00, 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Archived)', NULL, '2026-02-10 10:22:50'),
(192, 96, 2, 222, '213003', 'Driver Earnings Payable', '', 0.00, 26510.00, 'Driver Wallet Withdrawal for driver_payment: Mason Taylor (Archived)', NULL, '2026-02-10 10:22:50'),
(193, 97, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 36886.83, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(194, 97, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 35042.49, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(195, 97, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1844.34, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(196, 98, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 22475.70, 0.00, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(197, 98, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 21351.91, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(198, 98, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1123.79, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(199, 99, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 24591.22, 0.00, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(200, 99, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 23361.66, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(201, 99, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1229.56, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(202, 100, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 31100.03, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(203, 100, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 29545.03, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(204, 100, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1555.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(205, 101, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 21994.77, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(206, 101, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 20895.03, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(207, 101, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1099.74, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(208, 102, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 24158.53, 0.00, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(209, 102, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 22950.60, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(210, 102, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1207.93, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(211, 103, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 28185.23, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(212, 103, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 26775.97, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(213, 103, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1409.26, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(214, 104, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 31731.15, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(215, 104, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 30144.59, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(216, 104, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1586.56, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (pending)', NULL, '2026-02-10 10:22:50'),
(217, 105, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 34615.20, 0.00, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(218, 105, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 32884.44, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(219, 105, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1730.76, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(220, 106, 1, 113, '223001', 'Salaries Payable', 'Liability', 31584.44, 0.00, 'Cash Payment for payroll: PAY-20251130-6796', NULL, '2026-02-10 10:22:50'),
(221, 106, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 31584.44, 'Cash Payment for payroll: PAY-20251130-6796', NULL, '2026-02-10 10:22:50'),
(222, 107, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 23136.75, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(223, 107, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 21979.91, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(224, 107, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1156.84, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(225, 108, 1, 113, '223001', 'Salaries Payable', 'Liability', 20679.91, 0.00, 'Cash Payment for payroll: PAY-20251115-5970', NULL, '2026-02-10 10:22:50'),
(226, 108, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 20679.91, 'Cash Payment for payroll: PAY-20251115-5970', NULL, '2026-02-10 10:22:50'),
(227, 109, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 24230.64, 0.00, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(228, 109, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 23019.11, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(229, 109, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1211.53, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(230, 110, 1, 113, '223001', 'Salaries Payable', 'Liability', 21719.11, 0.00, 'Cash Payment for payroll: PAY-20251215-9626', NULL, '2026-02-10 10:22:50'),
(231, 110, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 21719.11, 'Cash Payment for payroll: PAY-20251215-9626', NULL, '2026-02-10 10:22:50'),
(232, 111, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 31550.75, 0.00, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(233, 111, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 29973.21, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(234, 111, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1577.54, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(235, 112, 1, 113, '223001', 'Salaries Payable', 'Liability', 28673.21, 0.00, 'Cash Payment for payroll: PAY-20251231-6299', NULL, '2026-02-10 10:22:50'),
(236, 112, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 28673.21, 'Cash Payment for payroll: PAY-20251231-6299', NULL, '2026-02-10 10:22:50'),
(237, 113, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 20732.78, 0.00, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(238, 113, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 19696.14, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(239, 113, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1036.64, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(240, 114, 1, 113, '223001', 'Salaries Payable', 'Liability', 18396.14, 0.00, 'Cash Payment for payroll: PAY-20251130-5014', NULL, '2026-02-10 10:22:50'),
(241, 114, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 18396.14, 'Cash Payment for payroll: PAY-20251130-5014', NULL, '2026-02-10 10:22:50'),
(242, 115, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 25961.40, 0.00, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(243, 115, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 24663.33, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(244, 115, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1298.07, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(245, 116, 1, 113, '223001', 'Salaries Payable', 'Liability', 23363.33, 0.00, 'Cash Payment for payroll: PAY-20251231-2610', NULL, '2026-02-10 10:22:50'),
(246, 116, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 23363.33, 'Cash Payment for payroll: PAY-20251231-2610', NULL, '2026-02-10 10:22:50'),
(247, 117, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 27428.01, 0.00, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(248, 117, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 26056.61, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(249, 117, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1371.40, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(250, 118, 1, 113, '223001', 'Salaries Payable', 'Liability', 24756.61, 0.00, 'Cash Payment for payroll: PAY-20251130-6633', NULL, '2026-02-10 10:22:50'),
(251, 118, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 24756.61, 'Cash Payment for payroll: PAY-20251130-6633', NULL, '2026-02-10 10:22:50'),
(252, 119, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 34711.96, 0.00, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(253, 119, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 32976.36, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(254, 119, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1735.60, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (approved)', NULL, '2026-02-10 10:22:50'),
(255, 120, 1, 113, '223001', 'Salaries Payable', 'Liability', 31676.36, 0.00, 'Cash Payment for payroll: PAY-20251215-5911', NULL, '2026-02-10 10:22:50'),
(256, 120, 2, 97, '111001', 'Cash on Hand', 'Asset', 0.00, 31676.36, 'Cash Payment for payroll: PAY-20251215-5911', NULL, '2026-02-10 10:22:50'),
(257, 121, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 36237.79, 0.00, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(258, 121, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 34425.90, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(259, 121, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1811.89, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(260, 122, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 24194.43, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(261, 122, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 22984.71, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(262, 122, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1209.72, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(263, 123, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 24591.22, 0.00, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(264, 123, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 23361.66, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(265, 123, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1229.56, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(266, 124, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 32001.48, 0.00, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(267, 124, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 30401.41, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(268, 124, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1600.07, 'Payroll Period: 2025-11-16 to 2025-11-30 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(269, 125, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 19590.97, 0.00, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(270, 125, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 18611.42, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(271, 125, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 979.55, 'Payroll Period: 2025-11-01 to 2025-11-15 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(272, 126, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 25961.40, 0.00, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(273, 126, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 24663.33, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(274, 126, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1298.07, 'Payroll Period: 2025-12-16 to 2025-12-31 for payroll (rejected)', NULL, '2026-02-10 10:22:50'),
(275, 127, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 29026.58, 0.00, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:51'),
(276, 127, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 27575.25, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:51'),
(277, 127, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1451.33, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:51'),
(278, 128, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 32788.86, 0.00, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:51'),
(279, 128, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 31149.42, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:51'),
(280, 128, 3, 114, '224001', 'Taxes Payable', 'Liability', 0.00, 1639.44, 'Payroll Period: 2025-12-01 to 2025-12-15 for payroll (rejected)', NULL, '2026-02-10 10:22:51'),
(281, 129, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 12229.00, 0.00, 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 'Administrative', '2026-02-10 15:21:35'),
(282, 129, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 12229.00, 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 'Administrative', '2026-02-10 15:21:35'),
(283, 130, 1, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 57048.80, 0.00, 'Vendor invoice for direct operating costs - SpeedFix Auto Service Center', 'Logistic-1', '2026-02-12 01:14:45'),
(284, 130, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 57048.80, 'Vendor invoice for direct operating costs - SpeedFix Auto Service Center', 'Logistic-1', '2026-02-12 01:14:45'),
(285, 131, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 22061.66, 0.00, 'Payroll for December 2025 - HR Assistant', 'Human Resource-1', '2026-02-12 01:21:56'),
(286, 131, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 22061.66, 'Payroll for December 2025 - HR Assistant', 'Human Resource-1', '2026-02-12 01:21:56');
INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `line_number`, `gl_account_id`, `gl_account_code`, `gl_account_name`, `account_type`, `debit_amount`, `credit_amount`, `description`, `department`, `created_at`) VALUES
(287, 132, 1, 151, '561001', 'Employee Salaries & Benefits', 'Expense', 20051.91, 0.00, 'Payroll for December 2025 - HR Specialist', 'Human Resource-2', '2026-02-12 01:53:40'),
(288, 132, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 20051.91, 'Payroll for December 2025 - HR Specialist', 'Human Resource-2', '2026-02-12 01:53:40'),
(291, 134, 1, 100, '112001', 'Accounts Receivable - Drivers', 'Asset', 180000.00, 0.00, 'Initial Revenue Recognition for Dashboard', 'Operations', '2026-02-12 09:01:53'),
(292, 134, 2, 121, '421001', 'Platform Commission Revenue', 'Revenue', 0.00, 180000.00, 'Initial Revenue Recognition for Dashboard', 'Operations', '2026-02-12 09:01:53'),
(293, 135, 1, 5, '500000', 'Expenses', 'Expense', 25500.00, 0.00, 'Vendor invoice for office supplies - ViaHale Supplier Co.', 'Logistic 1', '2026-02-13 14:05:17'),
(294, 135, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 25500.00, 'Vendor invoice for office supplies - ViaHale Supplier Co.', 'Logistic 1', '2026-02-13 14:05:17'),
(295, 136, 1, 125, '513001', 'Tire Replacement', 'Expense', 18163.00, 0.00, 'Vendor invoice for direct operating costs - AIG Insurance Phils', 'Logistic-1', '2026-02-13 14:22:06'),
(296, 136, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 18163.00, 'Vendor invoice for direct operating costs - AIG Insurance Phils', 'Logistic-1', '2026-02-13 14:22:06'),
(297, 137, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 15602.00, 0.00, 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 'Logistic-1', '2026-02-13 15:29:04'),
(298, 137, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 15602.00, 'Vendor invoice for direct operating costs - Rapid Fleet Maintenance', 'Logistic-1', '2026-02-13 15:29:04'),
(299, 138, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 8500.00, 0.00, 'Vendor invoice for direct operating costs - AutoParts Supply Co.', 'Logistic-1', '2026-02-14 04:02:59'),
(300, 138, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 8500.00, 'Vendor invoice for direct operating costs - AutoParts Supply Co.', 'Logistic-1', '2026-02-14 04:02:59'),
(301, 139, 1, 149, '554001', 'Office Supplies', 'Expense', 12300.00, 0.00, 'Vendor invoice for supplies & technology - Office Depot Manila', 'Administrative', '2026-02-14 04:03:56'),
(302, 139, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 12300.00, 'Vendor invoice for supplies & technology - Office Depot Manila', 'Administrative', '2026-02-14 04:03:56'),
(303, 140, 1, 71, '551000', 'Office Operations', 'Expense', 4500.00, 0.00, 'Reimbursement for office operations cost purchased by Ana Clara', 'Human Resource-1', '2026-02-14 04:48:13'),
(304, 140, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 4500.00, 'Reimbursement for office operations cost purchased by Ana Clara', 'Human Resource-1', '2026-02-14 04:48:13'),
(305, 141, 1, 150, '555001', 'Support Staff Compensation', 'Expense', 25000.00, 0.00, 'Vendor invoice for supplies & technology - TechSolutions Inc.', 'Human Resource-1', '2026-02-14 06:04:53'),
(306, 141, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 25000.00, 'Vendor invoice for supplies & technology - TechSolutions Inc.', 'Human Resource-1', '2026-02-14 06:04:53'),
(307, 142, 1, 146, '551001', 'Office Operations Cost', 'Expense', 11500.00, 0.00, 'Vendor invoice for indirect costs - CleanPro Services Inc.', 'Administrative', '2026-02-14 07:18:37'),
(308, 142, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 11500.00, 'Vendor invoice for indirect costs - CleanPro Services Inc.', 'Administrative', '2026-02-14 07:18:37'),
(309, 143, 1, 147, '552001', 'Professional Services', 'Expense', 8900.00, 0.00, 'Vendor invoice for indirect costs - PLDT Fibr Business', 'Core-1', '2026-02-14 07:19:55'),
(310, 143, 2, 109, '211001', 'Accounts Payable - Suppliers', 'Liability', 0.00, 8900.00, 'Vendor invoice for indirect costs - PLDT Fibr Business', 'Core-1', '2026-02-14 07:19:55'),
(311, 144, 1, 149, '554001', 'Office Supplies', 'Expense', 3500.00, 0.00, 'Reimbursement for office supplies purchased by Maria Santos', 'Core-1', '2026-02-14 07:20:29'),
(312, 144, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 3500.00, 'Reimbursement for office supplies purchased by Maria Santos', 'Core-1', '2026-02-14 07:20:29'),
(313, 145, 1, 5, '500000', 'Expenses', 'Expense', 8200.50, 0.00, 'Reimbursement for travel expenses purchased by Jose Reyes', 'Logistic-1', '2026-02-14 07:21:29'),
(314, 145, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 8200.50, 'Reimbursement for travel expenses purchased by Jose Reyes', 'Logistic-1', '2026-02-14 07:21:29'),
(315, 146, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 15000.00, 0.00, 'Reimbursement for maintenance & servicing purchased by Rafael Garcia', 'Maintenance', '2026-02-14 07:22:01'),
(316, 146, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 15000.00, 'Reimbursement for maintenance & servicing purchased by Rafael Garcia', 'Maintenance', '2026-02-14 07:22:01'),
(317, 147, 1, 5, '500000', 'Expenses', 'Expense', 6750.00, 0.00, 'Reimbursement for travel expenses purchased by Lito Lapid', 'Logistic-2', '2026-02-14 07:22:01'),
(318, 147, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 6750.00, 'Reimbursement for travel expenses purchased by Lito Lapid', 'Logistic-2', '2026-02-14 07:22:01'),
(319, 148, 1, 149, '554001', 'Office Supplies', 'Expense', 3200.00, 0.00, 'Reimbursement for office supplies purchased by Grace Tan', 'Administrative', '2026-02-14 07:22:01'),
(320, 148, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 3200.00, 'Reimbursement for office supplies purchased by Grace Tan', 'Administrative', '2026-02-14 07:22:01'),
(321, 149, 1, 71, '551000', 'Office Operations', 'Expense', 5600.00, 0.00, 'Reimbursement for office operations cost purchased by Mark Bautista', 'Core-2', '2026-02-14 07:22:01'),
(322, 149, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 5600.00, 'Reimbursement for office operations cost purchased by Mark Bautista', 'Core-2', '2026-02-14 07:22:01'),
(323, 150, 1, 149, '554001', 'Office Supplies', 'Expense', 4100.00, 0.00, 'Reimbursement for office supplies purchased by Sarah Geronimo', 'Human Resource-2', '2026-02-14 07:22:01'),
(324, 150, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 4100.00, 'Reimbursement for office supplies purchased by Sarah Geronimo', 'Human Resource-2', '2026-02-14 07:22:01'),
(325, 151, 1, 124, '512001', 'Maintenance & Servicing', 'Expense', 9800.00, 0.00, 'Reimbursement for maintenance & servicing purchased by Coco Martin', 'Logistic-1', '2026-02-14 07:22:01'),
(326, 151, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 9800.00, 'Reimbursement for maintenance & servicing purchased by Coco Martin', 'Logistic-1', '2026-02-14 07:22:01'),
(327, 152, 1, 5, '500000', 'Expenses', 'Expense', 12500.00, 0.00, 'Reimbursement for travel expenses purchased by Regine Velasquez', 'Core-1', '2026-02-14 07:22:01'),
(328, 152, 2, 113, '223001', 'Salaries Payable', 'Liability', 0.00, 12500.00, 'Reimbursement for travel expenses purchased by Regine Velasquez', 'Core-1', '2026-02-14 07:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(64) NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `request_type` varchar(100) NOT NULL,
  `sender_role` varchar(32) NOT NULL,
  `recipient_role` varchar(32) NOT NULL,
  `department` varchar(64) DEFAULT NULL,
  `status` varchar(16) DEFAULT 'pending',
  `is_read` tinyint(1) DEFAULT 0,
  `user_role` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `reference_id`, `message`, `timestamp`, `request_type`, `sender_role`, `recipient_role`, `department`, `status`, `is_read`, `user_role`) VALUES
(1, '', 'Added new payable amount request with Invoice ID: PA-INV-20250831-4581', '2025-09-01 17:03:24', '', '', '', NULL, 'pending', 0, '0'),
(2, '', 'Added new payable with Invoice ID: 20250901-8093', '2025-09-01 17:51:10', '', '', '', NULL, 'pending', 0, '0'),
(3, '', 'New budget request submitted.<br>Reference ID: BR-20250901-8869', '2025-09-01 19:45:19', '', '', '', NULL, 'pending', 0, '0'),
(4, '', 'New budget request submitted.<br>Reference ID: BR-20250901-4873', '2025-09-01 19:47:43', '', '', '', NULL, 'pending', 0, '0'),
(5, '', 'New budget request submitted.<br>Reference ID: BR-20250901-4316', '2025-09-01 19:48:20', '', '', '', NULL, 'pending', 0, '0'),
(6, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6791', '2025-09-01 19:48:56', '', '', '', NULL, 'pending', 0, '0'),
(7, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6965', '2025-09-01 20:21:11', '', '', '', NULL, 'pending', 0, '0'),
(8, '', 'New payable request submitted.<br>Reference ID: INV-20250901-6237', '2025-09-01 20:21:50', '', '', '', NULL, 'pending', 0, '0'),
(9, '', 'Added new payable amount request with Invoice ID: PA-INV-20250901-6237', '2025-09-01 20:24:43', '', '', '', NULL, 'pending', 0, '0'),
(10, '', 'New payable request submitted.<br>Reference ID: INV-20250901-6261', '2025-09-01 20:40:06', '', '', '', NULL, 'pending', 0, '0'),
(11, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6743', '2025-09-01 21:07:51', '', '', '', NULL, 'pending', 0, '0'),
(12, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-6729', '2025-09-01 21:11:11', '', '', '', NULL, 'pending', 0, '0'),
(13, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-4866', '2025-09-01 21:39:28', '', '', '', NULL, 'pending', 0, '0'),
(14, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-3838', '2025-09-01 21:40:46', '', '', '', NULL, 'pending', 0, '0'),
(15, '', 'New budget request submitted.<br>Reference ID: BR-20250901-7678', '2025-09-01 21:41:25', '', '', '', NULL, 'pending', 0, '0'),
(16, '', 'New budget request submitted.<br>Reference ID: BR-20250901-2989', '2025-09-01 21:55:16', '', '', '', NULL, 'pending', 0, '0'),
(17, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-3810', '2025-09-01 21:55:50', '', '', '', NULL, 'pending', 0, '0'),
(18, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-2796', '2025-09-01 21:56:51', '', '', '', NULL, 'pending', 0, '0'),
(19, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-6094', '2025-09-01 21:57:18', '', '', '', NULL, 'pending', 0, '0'),
(20, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-9696', '2025-09-01 22:16:01', '', '', '', NULL, 'pending', 0, '0'),
(21, '', 'New budget request submitted.<br>Reference ID: BR-20250901-1819', '2025-09-01 22:17:34', '', '', '', NULL, 'pending', 0, '0'),
(22, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-2715', '2025-09-01 22:17:52', '', '', '', NULL, 'pending', 0, '0'),
(23, '', 'New budget request submitted.<br>Reference ID: BR-20250901-5871', '2025-09-01 22:20:18', '', '', '', NULL, 'pending', 0, '0'),
(24, '', 'New budget request submitted.<br>Reference ID: BR-20250901-4729', '2025-09-01 22:20:47', '', '', '', NULL, 'pending', 0, '0'),
(25, '', 'New budget request submitted.<br>Reference ID: BR-20250901-5967', '2025-09-01 22:21:10', '', '', '', NULL, 'pending', 0, '0'),
(26, '', 'New budget request submitted.<br>Reference ID: BR-20250901-9124', '2025-09-01 22:21:38', '', '', '', NULL, 'pending', 0, '0'),
(27, '', 'New budget request submitted.<br>Reference ID: BR-20250901-1178', '2025-09-01 22:22:01', '', '', '', NULL, 'pending', 0, '0'),
(28, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6879', '2025-09-01 22:22:22', '', '', '', NULL, 'pending', 0, '0'),
(29, '', 'New payable request submitted.<br>Reference ID: INV-20250901-4214', '2025-09-01 22:22:43', '', '', '', NULL, 'pending', 0, '0'),
(30, '', 'New budget request submitted.<br>Reference ID: BR-20250901-5596', '2025-09-01 22:23:51', '', '', '', NULL, 'pending', 0, '0'),
(31, '', 'New budget request submitted.<br>Reference ID: BR-20250901-3294', '2025-09-01 22:24:37', '', '', '', NULL, 'pending', 0, '0'),
(32, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-7277', '2025-09-01 22:31:49', '', '', '', NULL, 'pending', 0, '0'),
(33, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-1608', '2025-09-01 22:32:11', '', '', '', NULL, 'pending', 0, '0'),
(34, '', 'New budget request submitted.<br>Reference ID: BR-20250901-3314', '2025-09-01 22:35:45', '', '', '', NULL, 'pending', 0, '0'),
(35, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-3032', '2025-09-01 22:37:10', '', '', '', NULL, 'pending', 0, '0'),
(36, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-6794', '2025-09-01 22:37:28', '', '', '', NULL, 'pending', 0, '0'),
(37, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-2522', '2025-09-01 22:38:13', '', '', '', NULL, 'pending', 0, '0'),
(38, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-2659', '2025-09-01 22:39:07', '', '', '', NULL, 'pending', 0, '0'),
(39, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-8586', '2025-09-01 22:39:23', '', '', '', NULL, 'pending', 0, '0'),
(40, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-3077', '2025-09-01 23:37:35', '', '', '', NULL, 'pending', 0, '0'),
(41, '', 'New emergency request submitted.<br>Reference ID: EM-20250902-5082', '2025-09-02 13:43:28', '', '', '', NULL, 'pending', 0, '0'),
(42, '', 'New emergency request submitted.<br>Reference ID: EM-20250902-2282', '2025-09-02 14:00:06', '', '', '', NULL, 'pending', 0, '0'),
(43, '', 'New payable request submitted.<br>Reference ID: INV-20250902-5021', '2025-09-02 14:11:42', '', '', '', NULL, 'pending', 0, '0'),
(44, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250902-4586', '2025-09-02 14:15:59', '', '', '', NULL, 'pending', 0, '0'),
(45, '', 'New budget request submitted.<br>Reference ID: BR-20250902-3123', '2025-09-02 15:11:24', '', '', '', NULL, 'pending', 0, '0'),
(46, '', 'New budget request submitted.<br>Reference ID: BR-20250902-1386', '2025-09-02 15:16:13', '', '', '', NULL, 'pending', 0, '0'),
(47, '', 'New budget request submitted.<br>Reference ID: BR-20250902-3579', '2025-09-02 17:27:49', '', '', '', NULL, 'pending', 0, '0'),
(48, '', 'New budget request submitted.<br>Reference ID: BR-20250902-6532', '2025-09-02 17:40:20', '', '', '', NULL, 'pending', 0, '0'),
(49, '', 'New budget request submitted.<br>Reference ID: BR-20250902-6806', '2025-09-02 17:43:06', '', '', '', NULL, 'pending', 0, '0'),
(50, '', 'New budget request submitted.<br>Reference ID: BR-20250902-7209', '2025-09-02 17:45:37', '', '', '', NULL, 'pending', 0, '0'),
(51, '', 'New budget request submitted.<br>Reference ID: BR-20250902-6749', '2025-09-02 17:47:46', '', '', '', NULL, 'pending', 0, '0'),
(52, '', 'New budget request submitted.<br>Reference ID: BR-20250902-7408', '2025-09-02 17:52:55', '', '', '', NULL, 'pending', 0, '0'),
(53, '', 'New budget request submitted.<br>Reference ID: BR-20250902-2433', '2025-09-02 17:54:18', '', '', '', NULL, 'pending', 0, '0'),
(54, '', 'New budget request submitted.<br>Reference ID: BR-20250903-9225', '2025-09-03 13:28:00', 'budget', '', '', NULL, 'pending', 0, '0'),
(55, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250903-3798', '2025-09-03 13:29:02', 'petty_cash', '', '', NULL, 'pending', 0, '0'),
(56, '', 'New payable request submitted.<br>Reference ID: INV-20250903-1262', '2025-09-03 13:34:13', 'payable', '', '', NULL, 'pending', 0, '0'),
(57, '', 'New emergency request submitted.<br>Reference ID: EM-20250903-1831', '2025-09-03 13:35:55', 'emergency', '', '', NULL, 'pending', 0, '0'),
(58, '', 'New budget request submitted.<br>Reference ID: BR-20250903-7575', '2025-09-03 13:41:04', 'budget', '', '', NULL, 'pending', 0, '0'),
(59, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250903-3553', '2025-09-03 13:44:00', 'Petty Cash', '', '', NULL, 'pending', 0, '0'),
(60, '', 'New emergency request submitted.<br>Reference ID: EM-20250903-6320', '2025-09-03 13:44:29', 'Emergency Disburse', '', '', NULL, 'pending', 0, '0'),
(61, '', 'New payable request submitted.<br>Reference ID: INV-20250903-2949', '2025-09-03 13:45:01', 'AP', '', '', NULL, 'pending', 0, '0'),
(62, '', 'New budget request submitted.<br>Reference ID: BR-20250903-3148', '2025-09-03 13:45:52', 'Budget Request', '', '', NULL, 'pending', 0, '0'),
(63, '', 'Added new payable amount request with Invoice ID: PA-INV-20250903-2949', '2025-09-04 14:12:42', '', '', '', NULL, 'pending', 0, ''),
(64, '', 'New payable request submitted.<br>Reference ID: INV-20250904-8893', '2025-09-04 14:46:44', 'AP', '', '', NULL, 'pending', 0, ''),
(65, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-8893', '2025-09-04 14:47:09', '', '', '', NULL, 'pending', 0, ''),
(66, '', 'New payable request submitted.<br>Reference ID: INV-20250904-3286', '2025-09-04 18:45:29', 'AP', '', '', NULL, 'pending', 0, ''),
(67, '', 'New payable request submitted.<br>Reference ID: INV-20250904-1624', '2025-09-04 18:57:24', 'AP', '', '', NULL, 'pending', 0, ''),
(68, '', 'New payable request submitted.<br>Reference ID: INV-20250904-5117', '2025-09-04 19:09:31', 'AP', '', '', NULL, 'pending', 0, ''),
(69, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-5117', '2025-09-04 19:10:55', '', '', '', NULL, 'pending', 0, ''),
(70, '', 'New payable request submitted.<br>Reference ID: INV-20250904-8297', '2025-09-04 20:23:05', 'AP', '', '', NULL, 'pending', 0, ''),
(71, '', 'New payable request submitted.<br>Reference ID: INV-20250904-5642', '2025-09-04 20:35:50', 'AP', '', '', NULL, 'pending', 0, ''),
(72, '', 'New payable request submitted.<br>Reference ID: INV-20250904-7250', '2025-09-04 20:37:40', 'AP', '', '', NULL, 'pending', 0, ''),
(73, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-7250', '2025-09-04 20:40:53', '', '', '', NULL, 'pending', 0, ''),
(74, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-5642', '2025-09-04 20:42:48', '', '', '', NULL, 'pending', 0, ''),
(75, '', 'New payable request submitted.<br>Reference ID: undefined20250904-8689', '2025-09-04 20:45:37', 'AP', '', '', NULL, 'pending', 0, ''),
(76, '', 'New payable request submitted.<br>Reference ID: INV-20250904-9909', '2025-09-04 20:46:14', 'AP', '', '', NULL, 'pending', 0, ''),
(77, '', 'New payable request submitted.<br>Reference ID: IN-20250904-4699', '2025-09-04 20:47:28', 'AP', '', '', NULL, 'pending', 0, ''),
(78, '', 'New payable request submitted.<br>Reference ID: INV-20250904-2177', '2025-09-04 23:49:21', 'AP', '', '', NULL, 'pending', 0, ''),
(79, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-2177', '2025-09-05 00:34:40', '', '', '', NULL, 'pending', 0, ''),
(80, '', 'Added new payable amount request with Invoice ID: PA-20250901-6261', '2025-09-05 00:44:35', '', '', '', NULL, 'pending', 0, ''),
(81, '', 'New payable request submitted.<br>Reference ID: INV-20250904-3075', '2025-09-05 00:51:27', 'AP', '', '', NULL, 'pending', 0, ''),
(82, '', 'Added new payable amount request with Invoice ID: PA-20250904-3075', '2025-09-05 00:52:00', '', '', '', NULL, 'pending', 0, ''),
(83, '', 'New payable request submitted.<br>Reference ID: INV-20250904-9835', '2025-09-05 00:52:40', 'AP', '', '', NULL, 'pending', 0, ''),
(84, '', 'Added new payable amount request with Invoice ID: PA-20250904-9835', '2025-09-05 15:48:03', '', '', '', NULL, 'pending', 0, ''),
(85, '', 'Added new payable amount request with Invoice ID: PA-20250901-6261', '2025-09-05 15:48:21', '', '', '', NULL, 'pending', 0, ''),
(86, '', 'Added new receivable request with Invoice ID: 202509052946', '2025-09-05 21:36:54', '', '', '', NULL, 'pending', 0, ''),
(87, '', 'Added new receivable request with Invoice ID: 20250905-9998', '2025-09-05 22:09:47', '', '', '', NULL, 'pending', 0, ''),
(88, '', 'Added new receivable request with Invoice ID: INV-20250905-2222', '2025-09-05 22:22:10', '', '', '', NULL, 'pending', 0, ''),
(89, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:40:40', '', '', '', NULL, 'pending', 0, ''),
(90, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:40:58', '', '', '', NULL, 'pending', 0, ''),
(91, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:41:20', '', '', '', NULL, 'pending', 0, ''),
(92, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:41:31', '', '', '', NULL, 'pending', 0, ''),
(93, '', 'Added new receivable request with Invoice ID: 20250905-5374', '2025-09-05 22:42:01', '', '', '', NULL, 'pending', 0, ''),
(94, '', 'Added new receivable request with Invoice ID: 202509058853', '2025-09-05 22:43:04', '', '', '', NULL, 'pending', 0, ''),
(95, '', 'Added new receivable request with Invoice ID: 202509059636', '2025-09-05 22:43:54', '', '', '', NULL, 'pending', 0, ''),
(96, '', 'Added new receivable request with Invoice ID: 20250905-9998', '2025-09-05 22:44:31', '', '', '', NULL, 'pending', 0, ''),
(97, '', 'Added new receivable request with Invoice ID: 20250805-9998', '2025-09-05 22:44:42', '', '', '', NULL, 'pending', 0, ''),
(98, '', 'Added new receivable request with Invoice ID: INV-20250905-5312', '2025-09-05 23:19:19', '', '', '', NULL, 'pending', 0, ''),
(99, '', 'Added new receivable request with Invoice ID: INV-20250905-5123', '2025-09-06 00:08:38', '', '', '', NULL, 'pending', 0, ''),
(100, '', 'Added new receivable request with Invoice ID: INV-20250905-1503', '2025-09-06 00:16:07', '', '', '', NULL, 'pending', 0, ''),
(101, '', 'Added new receivable request with Invoice ID: INV-20250905-6469', '2025-09-06 00:19:55', '', '', '', NULL, 'pending', 0, ''),
(102, '', 'Added new receivable request with Invoice ID: INV-20250905-4744', '2025-09-06 00:27:05', '', '', '', NULL, 'pending', 0, ''),
(103, '', 'Added new receivable request with Invoice ID: INV-20250905-2424', '2025-09-06 00:27:46', '', '', '', NULL, 'pending', 0, ''),
(104, '', 'Added new receivable request with Invoice ID: INV-20250905-8155', '2025-09-06 00:28:12', '', '', '', NULL, 'pending', 0, ''),
(105, '', 'Added new receivable request with Invoice ID: INV-20250905-5189', '2025-09-06 00:28:47', '', '', '', NULL, 'pending', 0, ''),
(106, '', 'Added new receivable request with Invoice ID: INV-20250905-6471', '2025-09-06 00:29:13', '', '', '', NULL, 'pending', 0, ''),
(107, '', 'Added new receivable request with Invoice ID: INV-20250905-1394', '2025-09-06 00:29:35', '', '', '', NULL, 'pending', 0, ''),
(108, '', 'Added new receivable request with Invoice ID: INV-20250905-5748', '2025-09-06 00:30:05', '', '', '', NULL, 'pending', 0, ''),
(109, '', 'Added new receivable request with Invoice ID: INV-20250905-6907', '2025-09-06 02:33:16', '', '', '', NULL, 'pending', 0, ''),
(110, '', 'Added new receivable request with Invoice ID: INV-20250905-2091', '2025-09-06 03:57:00', '', '', '', NULL, 'pending', 0, ''),
(111, '', 'Added new receivable request with Invoice ID: INV-20250905-9455', '2025-09-06 04:03:15', '', '', '', NULL, 'pending', 0, ''),
(112, '', 'Added new receivable request with Invoice ID: INV-20250907-2814', '2025-09-07 16:47:31', '', '', '', NULL, 'pending', 0, ''),
(113, '', 'New budget request submitted.<br>Reference ID: BR-20250907-3080', '2025-09-07 21:00:44', 'Budget Request', '', '', NULL, 'pending', 0, ''),
(114, '', 'New budget request submitted.<br>Reference ID: BR-20250907-5223', '2025-09-07 22:12:20', 'Budget Request', '', '', NULL, 'pending', 0, ''),
(115, '', 'New payable request submitted.<br>Reference ID: INV-20250907-9484', '2025-09-08 03:04:41', 'AP', '', '', NULL, 'pending', 0, ''),
(116, '', 'New payable request submitted.<br>Reference ID: INV-20250907-7056', '2025-09-08 03:30:58', 'AP', '', '', NULL, 'pending', 0, ''),
(117, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:39:25', '', '', '', NULL, 'pending', 0, ''),
(118, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:45:04', '', '', '', NULL, 'pending', 0, ''),
(119, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:45:10', '', '', '', NULL, 'pending', 0, ''),
(120, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:45:16', '', '', '', NULL, 'pending', 0, ''),
(121, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:58:47', '', '', '', NULL, 'pending', 0, ''),
(122, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:59:06', '', '', '', NULL, 'pending', 0, ''),
(123, '', 'New audit report generated: 2025 Expense Report', '2025-09-27 22:36:02', '', '', '', NULL, 'pending', 0, ''),
(124, '', 'New audit report generated: 2025 Expense Report', '2025-09-27 22:37:20', '', '', '', NULL, 'pending', 0, ''),
(125, '', 'New audit report generated: 2025 Expense Report', '2025-09-27 23:42:55', '', '', '', NULL, 'pending', 0, ''),
(126, '', 'New audit report generated: Q9 2025 Expense Report', '2025-09-27 23:44:30', '', '', '', NULL, 'pending', 0, ''),
(129, '', 'Added new payable amount request with Invoice ID: PA-20250901-4214', '2025-10-15 00:45:13', '', '', '', NULL, 'pending', 0, ''),
(1, '', 'Added new payable amount request with Invoice ID: PA-INV-20250831-4581', '2025-09-01 17:03:24', '', '', '', NULL, 'pending', 0, '0'),
(2, '', 'Added new payable with Invoice ID: 20250901-8093', '2025-09-01 17:51:10', '', '', '', NULL, 'pending', 0, '0'),
(3, '', 'New budget request submitted.<br>Reference ID: BR-20250901-8869', '2025-09-01 19:45:19', '', '', '', NULL, 'pending', 0, '0'),
(4, '', 'New budget request submitted.<br>Reference ID: BR-20250901-4873', '2025-09-01 19:47:43', '', '', '', NULL, 'pending', 0, '0'),
(5, '', 'New budget request submitted.<br>Reference ID: BR-20250901-4316', '2025-09-01 19:48:20', '', '', '', NULL, 'pending', 0, '0'),
(6, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6791', '2025-09-01 19:48:56', '', '', '', NULL, 'pending', 0, '0'),
(7, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6965', '2025-09-01 20:21:11', '', '', '', NULL, 'pending', 0, '0'),
(8, '', 'New payable request submitted.<br>Reference ID: INV-20250901-6237', '2025-09-01 20:21:50', '', '', '', NULL, 'pending', 0, '0'),
(9, '', 'Added new payable amount request with Invoice ID: PA-INV-20250901-6237', '2025-09-01 20:24:43', '', '', '', NULL, 'pending', 0, '0'),
(10, '', 'New payable request submitted.<br>Reference ID: INV-20250901-6261', '2025-09-01 20:40:06', '', '', '', NULL, 'pending', 0, '0'),
(11, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6743', '2025-09-01 21:07:51', '', '', '', NULL, 'pending', 0, '0'),
(12, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-6729', '2025-09-01 21:11:11', '', '', '', NULL, 'pending', 0, '0'),
(13, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-4866', '2025-09-01 21:39:28', '', '', '', NULL, 'pending', 0, '0'),
(14, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-3838', '2025-09-01 21:40:46', '', '', '', NULL, 'pending', 0, '0'),
(15, '', 'New budget request submitted.<br>Reference ID: BR-20250901-7678', '2025-09-01 21:41:25', '', '', '', NULL, 'pending', 0, '0'),
(16, '', 'New budget request submitted.<br>Reference ID: BR-20250901-2989', '2025-09-01 21:55:16', '', '', '', NULL, 'pending', 0, '0'),
(17, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-3810', '2025-09-01 21:55:50', '', '', '', NULL, 'pending', 0, '0'),
(18, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-2796', '2025-09-01 21:56:51', '', '', '', NULL, 'pending', 0, '0'),
(19, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-6094', '2025-09-01 21:57:18', '', '', '', NULL, 'pending', 0, '0'),
(20, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-9696', '2025-09-01 22:16:01', '', '', '', NULL, 'pending', 0, '0'),
(21, '', 'New budget request submitted.<br>Reference ID: BR-20250901-1819', '2025-09-01 22:17:34', '', '', '', NULL, 'pending', 0, '0'),
(22, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-2715', '2025-09-01 22:17:52', '', '', '', NULL, 'pending', 0, '0'),
(23, '', 'New budget request submitted.<br>Reference ID: BR-20250901-5871', '2025-09-01 22:20:18', '', '', '', NULL, 'pending', 0, '0'),
(24, '', 'New budget request submitted.<br>Reference ID: BR-20250901-4729', '2025-09-01 22:20:47', '', '', '', NULL, 'pending', 0, '0'),
(25, '', 'New budget request submitted.<br>Reference ID: BR-20250901-5967', '2025-09-01 22:21:10', '', '', '', NULL, 'pending', 0, '0'),
(26, '', 'New budget request submitted.<br>Reference ID: BR-20250901-9124', '2025-09-01 22:21:38', '', '', '', NULL, 'pending', 0, '0'),
(27, '', 'New budget request submitted.<br>Reference ID: BR-20250901-1178', '2025-09-01 22:22:01', '', '', '', NULL, 'pending', 0, '0'),
(28, '', 'New budget request submitted.<br>Reference ID: BR-20250901-6879', '2025-09-01 22:22:22', '', '', '', NULL, 'pending', 0, '0'),
(29, '', 'New payable request submitted.<br>Reference ID: INV-20250901-4214', '2025-09-01 22:22:43', '', '', '', NULL, 'pending', 0, '0'),
(30, '', 'New budget request submitted.<br>Reference ID: BR-20250901-5596', '2025-09-01 22:23:51', '', '', '', NULL, 'pending', 0, '0'),
(31, '', 'New budget request submitted.<br>Reference ID: BR-20250901-3294', '2025-09-01 22:24:37', '', '', '', NULL, 'pending', 0, '0'),
(32, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-7277', '2025-09-01 22:31:49', '', '', '', NULL, 'pending', 0, '0'),
(33, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-1608', '2025-09-01 22:32:11', '', '', '', NULL, 'pending', 0, '0'),
(34, '', 'New budget request submitted.<br>Reference ID: BR-20250901-3314', '2025-09-01 22:35:45', '', '', '', NULL, 'pending', 0, '0'),
(35, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-3032', '2025-09-01 22:37:10', '', '', '', NULL, 'pending', 0, '0'),
(36, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-6794', '2025-09-01 22:37:28', '', '', '', NULL, 'pending', 0, '0'),
(37, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-2522', '2025-09-01 22:38:13', '', '', '', NULL, 'pending', 0, '0'),
(38, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-2659', '2025-09-01 22:39:07', '', '', '', NULL, 'pending', 0, '0'),
(39, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250901-8586', '2025-09-01 22:39:23', '', '', '', NULL, 'pending', 0, '0'),
(40, '', 'New emergency request submitted.<br>Reference ID: EM-20250901-3077', '2025-09-01 23:37:35', '', '', '', NULL, 'pending', 0, '0'),
(41, '', 'New emergency request submitted.<br>Reference ID: EM-20250902-5082', '2025-09-02 13:43:28', '', '', '', NULL, 'pending', 0, '0'),
(42, '', 'New emergency request submitted.<br>Reference ID: EM-20250902-2282', '2025-09-02 14:00:06', '', '', '', NULL, 'pending', 0, '0'),
(43, '', 'New payable request submitted.<br>Reference ID: INV-20250902-5021', '2025-09-02 14:11:42', '', '', '', NULL, 'pending', 0, '0'),
(44, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250902-4586', '2025-09-02 14:15:59', '', '', '', NULL, 'pending', 0, '0'),
(45, '', 'New budget request submitted.<br>Reference ID: BR-20250902-3123', '2025-09-02 15:11:24', '', '', '', NULL, 'pending', 0, '0'),
(46, '', 'New budget request submitted.<br>Reference ID: BR-20250902-1386', '2025-09-02 15:16:13', '', '', '', NULL, 'pending', 0, '0'),
(47, '', 'New budget request submitted.<br>Reference ID: BR-20250902-3579', '2025-09-02 17:27:49', '', '', '', NULL, 'pending', 0, '0'),
(48, '', 'New budget request submitted.<br>Reference ID: BR-20250902-6532', '2025-09-02 17:40:20', '', '', '', NULL, 'pending', 0, '0'),
(49, '', 'New budget request submitted.<br>Reference ID: BR-20250902-6806', '2025-09-02 17:43:06', '', '', '', NULL, 'pending', 0, '0'),
(50, '', 'New budget request submitted.<br>Reference ID: BR-20250902-7209', '2025-09-02 17:45:37', '', '', '', NULL, 'pending', 0, '0'),
(51, '', 'New budget request submitted.<br>Reference ID: BR-20250902-6749', '2025-09-02 17:47:46', '', '', '', NULL, 'pending', 0, '0'),
(52, '', 'New budget request submitted.<br>Reference ID: BR-20250902-7408', '2025-09-02 17:52:55', '', '', '', NULL, 'pending', 0, '0'),
(53, '', 'New budget request submitted.<br>Reference ID: BR-20250902-2433', '2025-09-02 17:54:18', '', '', '', NULL, 'pending', 0, '0'),
(54, '', 'New budget request submitted.<br>Reference ID: BR-20250903-9225', '2025-09-03 13:28:00', 'budget', '', '', NULL, 'pending', 0, '0'),
(55, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250903-3798', '2025-09-03 13:29:02', 'petty_cash', '', '', NULL, 'pending', 0, '0'),
(56, '', 'New payable request submitted.<br>Reference ID: INV-20250903-1262', '2025-09-03 13:34:13', 'payable', '', '', NULL, 'pending', 0, '0'),
(57, '', 'New emergency request submitted.<br>Reference ID: EM-20250903-1831', '2025-09-03 13:35:55', 'emergency', '', '', NULL, 'pending', 0, '0'),
(58, '', 'New budget request submitted.<br>Reference ID: BR-20250903-7575', '2025-09-03 13:41:04', 'budget', '', '', NULL, 'pending', 0, '0'),
(59, '', 'New petty_cash request submitted.<br>Reference ID: PC-20250903-3553', '2025-09-03 13:44:00', 'Petty Cash', '', '', NULL, 'pending', 0, '0'),
(60, '', 'New emergency request submitted.<br>Reference ID: EM-20250903-6320', '2025-09-03 13:44:29', 'Emergency Disburse', '', '', NULL, 'pending', 0, '0'),
(61, '', 'New payable request submitted.<br>Reference ID: INV-20250903-2949', '2025-09-03 13:45:01', 'AP', '', '', NULL, 'pending', 0, '0'),
(62, '', 'New budget request submitted.<br>Reference ID: BR-20250903-3148', '2025-09-03 13:45:52', 'Budget Request', '', '', NULL, 'pending', 0, '0'),
(63, '', 'Added new payable amount request with Invoice ID: PA-INV-20250903-2949', '2025-09-04 14:12:42', '', '', '', NULL, 'pending', 0, ''),
(64, '', 'New payable request submitted.<br>Reference ID: INV-20250904-8893', '2025-09-04 14:46:44', 'AP', '', '', NULL, 'pending', 0, ''),
(65, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-8893', '2025-09-04 14:47:09', '', '', '', NULL, 'pending', 0, ''),
(66, '', 'New payable request submitted.<br>Reference ID: INV-20250904-3286', '2025-09-04 18:45:29', 'AP', '', '', NULL, 'pending', 0, ''),
(67, '', 'New payable request submitted.<br>Reference ID: INV-20250904-1624', '2025-09-04 18:57:24', 'AP', '', '', NULL, 'pending', 0, ''),
(68, '', 'New payable request submitted.<br>Reference ID: INV-20250904-5117', '2025-09-04 19:09:31', 'AP', '', '', NULL, 'pending', 0, ''),
(69, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-5117', '2025-09-04 19:10:55', '', '', '', NULL, 'pending', 0, ''),
(70, '', 'New payable request submitted.<br>Reference ID: INV-20250904-8297', '2025-09-04 20:23:05', 'AP', '', '', NULL, 'pending', 0, ''),
(71, '', 'New payable request submitted.<br>Reference ID: INV-20250904-5642', '2025-09-04 20:35:50', 'AP', '', '', NULL, 'pending', 0, ''),
(72, '', 'New payable request submitted.<br>Reference ID: INV-20250904-7250', '2025-09-04 20:37:40', 'AP', '', '', NULL, 'pending', 0, ''),
(73, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-7250', '2025-09-04 20:40:53', '', '', '', NULL, 'pending', 0, ''),
(74, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-5642', '2025-09-04 20:42:48', '', '', '', NULL, 'pending', 0, ''),
(75, '', 'New payable request submitted.<br>Reference ID: undefined20250904-8689', '2025-09-04 20:45:37', 'AP', '', '', NULL, 'pending', 0, ''),
(76, '', 'New payable request submitted.<br>Reference ID: INV-20250904-9909', '2025-09-04 20:46:14', 'AP', '', '', NULL, 'pending', 0, ''),
(77, '', 'New payable request submitted.<br>Reference ID: IN-20250904-4699', '2025-09-04 20:47:28', 'AP', '', '', NULL, 'pending', 0, ''),
(78, '', 'New payable request submitted.<br>Reference ID: INV-20250904-2177', '2025-09-04 23:49:21', 'AP', '', '', NULL, 'pending', 0, ''),
(79, '', 'Added new payable amount request with Invoice ID: PA-INV-20250904-2177', '2025-09-05 00:34:40', '', '', '', NULL, 'pending', 0, ''),
(80, '', 'Added new payable amount request with Invoice ID: PA-20250901-6261', '2025-09-05 00:44:35', '', '', '', NULL, 'pending', 0, ''),
(81, '', 'New payable request submitted.<br>Reference ID: INV-20250904-3075', '2025-09-05 00:51:27', 'AP', '', '', NULL, 'pending', 0, ''),
(82, '', 'Added new payable amount request with Invoice ID: PA-20250904-3075', '2025-09-05 00:52:00', '', '', '', NULL, 'pending', 0, ''),
(83, '', 'New payable request submitted.<br>Reference ID: INV-20250904-9835', '2025-09-05 00:52:40', 'AP', '', '', NULL, 'pending', 0, ''),
(84, '', 'Added new payable amount request with Invoice ID: PA-20250904-9835', '2025-09-05 15:48:03', '', '', '', NULL, 'pending', 0, ''),
(85, '', 'Added new payable amount request with Invoice ID: PA-20250901-6261', '2025-09-05 15:48:21', '', '', '', NULL, 'pending', 0, ''),
(86, '', 'Added new receivable request with Invoice ID: 202509052946', '2025-09-05 21:36:54', '', '', '', NULL, 'pending', 0, ''),
(87, '', 'Added new receivable request with Invoice ID: 20250905-9998', '2025-09-05 22:09:47', '', '', '', NULL, 'pending', 0, ''),
(88, '', 'Added new receivable request with Invoice ID: INV-20250905-2222', '2025-09-05 22:22:10', '', '', '', NULL, 'pending', 0, ''),
(89, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:40:40', '', '', '', NULL, 'pending', 0, ''),
(90, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:40:58', '', '', '', NULL, 'pending', 0, ''),
(91, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:41:20', '', '', '', NULL, 'pending', 0, ''),
(92, '', 'Added new receivable request with Invoice ID: INV-20250905-8990', '2025-09-05 22:41:31', '', '', '', NULL, 'pending', 0, ''),
(93, '', 'Added new receivable request with Invoice ID: 20250905-5374', '2025-09-05 22:42:01', '', '', '', NULL, 'pending', 0, ''),
(94, '', 'Added new receivable request with Invoice ID: 202509058853', '2025-09-05 22:43:04', '', '', '', NULL, 'pending', 0, ''),
(95, '', 'Added new receivable request with Invoice ID: 202509059636', '2025-09-05 22:43:54', '', '', '', NULL, 'pending', 0, ''),
(96, '', 'Added new receivable request with Invoice ID: 20250905-9998', '2025-09-05 22:44:31', '', '', '', NULL, 'pending', 0, ''),
(97, '', 'Added new receivable request with Invoice ID: 20250805-9998', '2025-09-05 22:44:42', '', '', '', NULL, 'pending', 0, ''),
(98, '', 'Added new receivable request with Invoice ID: INV-20250905-5312', '2025-09-05 23:19:19', '', '', '', NULL, 'pending', 0, ''),
(99, '', 'Added new receivable request with Invoice ID: INV-20250905-5123', '2025-09-06 00:08:38', '', '', '', NULL, 'pending', 0, ''),
(100, '', 'Added new receivable request with Invoice ID: INV-20250905-1503', '2025-09-06 00:16:07', '', '', '', NULL, 'pending', 0, ''),
(101, '', 'Added new receivable request with Invoice ID: INV-20250905-6469', '2025-09-06 00:19:55', '', '', '', NULL, 'pending', 0, ''),
(102, '', 'Added new receivable request with Invoice ID: INV-20250905-4744', '2025-09-06 00:27:05', '', '', '', NULL, 'pending', 0, ''),
(103, '', 'Added new receivable request with Invoice ID: INV-20250905-2424', '2025-09-06 00:27:46', '', '', '', NULL, 'pending', 0, ''),
(104, '', 'Added new receivable request with Invoice ID: INV-20250905-8155', '2025-09-06 00:28:12', '', '', '', NULL, 'pending', 0, ''),
(105, '', 'Added new receivable request with Invoice ID: INV-20250905-5189', '2025-09-06 00:28:47', '', '', '', NULL, 'pending', 0, ''),
(106, '', 'Added new receivable request with Invoice ID: INV-20250905-6471', '2025-09-06 00:29:13', '', '', '', NULL, 'pending', 0, ''),
(107, '', 'Added new receivable request with Invoice ID: INV-20250905-1394', '2025-09-06 00:29:35', '', '', '', NULL, 'pending', 0, ''),
(108, '', 'Added new receivable request with Invoice ID: INV-20250905-5748', '2025-09-06 00:30:05', '', '', '', NULL, 'pending', 0, ''),
(109, '', 'Added new receivable request with Invoice ID: INV-20250905-6907', '2025-09-06 02:33:16', '', '', '', NULL, 'pending', 0, ''),
(110, '', 'Added new receivable request with Invoice ID: INV-20250905-2091', '2025-09-06 03:57:00', '', '', '', NULL, 'pending', 0, ''),
(111, '', 'Added new receivable request with Invoice ID: INV-20250905-9455', '2025-09-06 04:03:15', '', '', '', NULL, 'pending', 0, ''),
(112, '', 'Added new receivable request with Invoice ID: INV-20250907-2814', '2025-09-07 16:47:31', '', '', '', NULL, 'pending', 0, ''),
(113, '', 'New budget request submitted.<br>Reference ID: BR-20250907-3080', '2025-09-07 21:00:44', 'Budget Request', '', '', NULL, 'pending', 0, '0'),
(114, '', 'New budget request submitted.<br>Reference ID: BR-20250907-5223', '2025-09-07 22:12:20', 'Budget Request', '', '', NULL, 'pending', 0, '0'),
(115, '', 'New payable request submitted.<br>Reference ID: INV-20250907-9484', '2025-09-08 03:04:41', 'AP', '', '', NULL, 'pending', 0, ''),
(116, '', 'New payable request submitted.<br>Reference ID: INV-20250907-7056', '2025-09-08 03:30:58', 'AP', '', '', NULL, 'pending', 0, ''),
(117, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:39:25', '', '', '', NULL, 'pending', 0, ''),
(118, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:45:04', '', '', '', NULL, 'pending', 0, ''),
(119, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:45:10', '', '', '', NULL, 'pending', 0, ''),
(120, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:45:16', '', '', '', NULL, 'pending', 0, ''),
(121, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:58:47', '', '', '', NULL, 'pending', 0, ''),
(122, '', 'New audit report generated on 2025-09-27', '2025-09-27 21:59:06', '', '', '', NULL, 'pending', 0, ''),
(123, '', 'New audit report generated: 2025 Expense Report', '2025-09-27 22:36:02', '', '', '', NULL, 'pending', 0, ''),
(124, '', 'New audit report generated: 2025 Expense Report', '2025-09-27 22:37:20', '', '', '', NULL, 'pending', 0, ''),
(125, '', 'New audit report generated: 2025 Expense Report', '2025-09-27 23:42:55', '', '', '', NULL, 'pending', 0, ''),
(126, '', 'New audit report generated: Q9 2025 Expense Report', '2025-09-27 23:44:30', '', '', '', NULL, 'pending', 0, ''),
(129, '', 'Added new payable amount request with Invoice ID: PA-20250901-4214', '2025-10-15 00:45:13', '', '', '', NULL, 'pending', 0, ''),
(0, '', 'New Request Budget request submitted.<br>Reference ID: BR-20260124-1904', '2026-01-24 17:21:40', 'Request Budget', '', '', NULL, 'pending', 0, ''),
(0, '', 'New Request Budget request submitted.<br>Reference ID: BR-20260124-1904', '2026-01-24 17:21:42', 'Request Budget', '', '', NULL, 'pending', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `pa`
--

CREATE TABLE `pa` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `driver_id` varchar(50) DEFAULT NULL,
  `account_name` varchar(30) NOT NULL,
  `vendor_address` text DEFAULT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `payout_type` varchar(50) DEFAULT NULL,
  `source_module` varchar(50) DEFAULT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` blob DEFAULT NULL,
  `payment_due` date NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `submitted_date` date DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `from_payable` tinyint(1) DEFAULT 0,
  `bank_account_number` varchar(20) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `ecash_provider` varchar(50) DEFAULT NULL,
  `ecash_account_name` varchar(100) DEFAULT NULL,
  `ecash_account_number` varchar(20) DEFAULT NULL,
  `vendor_id` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `wallet_id` varchar(100) DEFAULT NULL,
  `is_misclassified` tinyint(1) DEFAULT 0,
  `approval_source` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending Disbursement'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pa`
--

INSERT INTO `pa` (`id`, `reference_id`, `driver_id`, `account_name`, `vendor_address`, `requested_department`, `mode_of_payment`, `expense_categories`, `transaction_type`, `payout_type`, `source_module`, `amount`, `description`, `document`, `payment_due`, `requested_at`, `submitted_date`, `approved_date`, `bank_name`, `from_payable`, `bank_account_number`, `bank_account_name`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `vendor_id`, `supplier_name`, `employee_id`, `wallet_id`, `is_misclassified`, `approval_source`, `approved_by`, `approved_at`, `status`) VALUES
(90, 'PA-993928', NULL, 'test 5', NULL, 'Administrative', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 4000, 'Payment for invoice INV-993928', '', '2025-08-25', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(91, 'PA-993927', NULL, 'test 5', NULL, 'Administrative', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 4000, 'Payment for invoice INV-993927', '', '2025-08-25', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(92, 'PA-638595', NULL, 'log1', NULL, 'Logistic-1', 'Cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 7500, 'Payment for invoice INV-638595', '', '2025-08-27', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(99, 'PA-638674', NULL, 'admin 2', NULL, 'Administrative', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 20000, 'Payment for invoice INV-638674', 0x313735353737343631395f62696c6c2e706466, '2025-08-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(117, 'PA-123492', NULL, 'lily chan', NULL, 'Financial', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 2000, 'Payment for invoice INV-123492', 0x313735363435383430325f62696c6c2e706466, '2025-09-22', '2026-01-30 11:00:49', NULL, NULL, 'BDO', 1, '1234567891011213', 'lily chan', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(120, 'PA-123493', NULL, 'zoro', NULL, 'Financial', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 4000, 'Payment for invoice INV-123493', 0x313735363436363639335f62696c6c2e706466, '2025-09-24', '2026-01-30 11:00:49', NULL, NULL, 'AUB', 1, '1234567891011213', 'zoro', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(136, 'PA-123487', NULL, 'nami', NULL, 'Logistic-1', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 2345, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(137, 'PA-123487', NULL, 'nami', NULL, 'Logistic-1', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 30000, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(138, 'PA-123487', NULL, 'nami', NULL, 'Logistic-1', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 56000, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(139, 'PA-123487', NULL, 'nami', NULL, 'Logistic-1', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 235, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(140, 'PA-123496', NULL, 'brook', NULL, 'Financial', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 12345, 'Payment for invoice INV-123496', 0x313735363632313339345f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(141, 'PA-123496', NULL, 'brook', NULL, 'Financial', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 7655, 'Payment for invoice INV-123496', 0x313735363632313339345f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(142, 'PA-123498', NULL, 'jinbei', NULL, 'Financial', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 3000, 'Payment for invoice INV-123498', 0x313735363632323938385f62696c6c2e706466, '2025-09-09', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(148, 'PA-INV-20250831-5297', NULL, 'test', NULL, 'Administrative', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 20, 'Payment for invoice INV-INV-20250831-5297', 0x313735363632393839385f62696c6c2e706466, '2025-09-01', '2026-01-30 11:00:49', NULL, NULL, 'AUB', 1, '1234567891011213', 'test admin', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(160, 'PA-20250901-1439', NULL, 'test', NULL, 'Financial', 'cash', 'test', 'Payroll', 'Payroll', 'Payroll', 1000, '0', 0x313735363732363431365f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(161, 'PA-20250901-8869', NULL, 'budget manager', NULL, 'Financial', 'cash', 'test', 'Payroll', 'Payroll', 'Payroll', 1000, '0', '', '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(162, 'PA-20250901-4873', NULL, 'budget manager', NULL, 'Financial', 'cash', 'test', 'Payroll', 'Payroll', 'Payroll', 1000, '0', 0x313735363732373236335f62696c6c2e706466, '2025-09-29', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(163, 'PA-20250901-4316', NULL, 'budget manager', NULL, 'Financial', 'ecash', 'test', 'Payroll', 'Payroll', 'Payroll', 1000, '0', 0x313735363732373330305f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', 'test', 'test', '12345678910', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(164, 'PA-20250901-6791', NULL, 'test', NULL, 'Financial', 'cash', 'test', 'Payroll', 'Payroll', 'Payroll', 1000, '0', '', '2025-09-17', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(167, 'PA-INV-20250901-6237', NULL, 'test', NULL, 'Administrative', 'cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 500, 'Payment for invoice INV-INV-20250901-6237', '', '2025-09-19', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(168, 'EM-20250901-6729', NULL, 'test', NULL, 'Human Resource-2', 'bank', 'test', NULL, NULL, NULL, 462, 'test', '', '2025-09-23', '2025-09-01 07:11:11', NULL, NULL, 'test', 0, '1123014567889', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(169, 'EM-20250901-3838', NULL, 'test', NULL, 'Human Resource-1', 'cash', 'test', NULL, NULL, NULL, 4684, 'test', 0x313735363733343034365f62696c6c2e706466, '2025-09-16', '2025-09-01 07:40:46', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(170, 'EM-20250901-2796', NULL, 'test', NULL, 'Core-2', 'bank', 'test', NULL, NULL, NULL, 300, 'test', 0x313735363733353031315f62696c6c2e706466, '2025-09-01', '2025-09-01 07:56:51', NULL, NULL, 'test', 0, '12345678910', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(171, 'EM-20250901-9696', NULL, 'test', NULL, 'Human Resource-2', 'cash', 'test', NULL, NULL, NULL, 200, 'test', 0x313735363733363136315f62696c6c2e706466, '2025-09-30', '2025-09-01 08:16:01', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(172, 'EM-20250901-1608', NULL, 'test', NULL, 'Core-1', 'cash', 'test', NULL, NULL, NULL, 3222, 'test', 0x313735363733373133315f62696c6c2e706466, '2025-09-01', '2025-09-01 08:32:11', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(173, 'EM-20250901-6794', NULL, 'budget manager', NULL, 'Financial', 'cash', 'test', NULL, NULL, NULL, 6500, 'test', '', '2025-09-01', '2025-09-01 08:37:28', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(174, 'EM-20250901-2522', NULL, 'test', NULL, 'Financial', 'cash', 'test', NULL, NULL, NULL, 566, 'test', 0x313735363733373439335f62696c6c2e706466, '2025-09-01', '2025-09-01 08:38:13', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(175, 'EM-20250901-3077', NULL, 'budget manager', NULL, 'Financial', 'cash', 'test', NULL, NULL, NULL, 5000, 'test', 0x313735363734313035355f62696c6c2e706466, '2025-09-01', '2025-09-01 09:37:35', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(176, 'EM-20250902-5082', NULL, 'budget manager', NULL, 'Financial', 'cash', 'test', NULL, NULL, NULL, 6500, 'test', 0x313735363739313830385f62696c6c2e706466, '2025-09-02', '2025-09-02 05:52:16', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(177, 'EM-20250902-2282', NULL, 'budget manager', NULL, 'Financial', 'cash', 'test again', NULL, NULL, NULL, 5000, 'test again', 0x313735363739323830365f62696c6c2e706466, '2025-09-02', '2025-09-02 00:00:06', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(178, 'PA-20250902-1386', NULL, 'disburse officer', NULL, 'Financial', 'ecash', 'test 2', 'Payroll', 'Payroll', 'Payroll', 5000, 'test 2', 0x313735363739373337335f62696c6c2e706466, '2025-09-15', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', 'Gcash', 'test 2', '12345678910', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(179, 'PA-20250902-3123', NULL, 'disburse officer', NULL, 'Financial', 'bank', 'test', 'Payroll', 'Payroll', 'Payroll', 50000, 'test', 0x313735363739373038345f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, 'test', 1, '12345678910', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(180, 'PA-20250901-3314', NULL, 'budget manager', NULL, 'Financial', 'cash', 'test', 'Payroll', 'Payroll', 'Payroll', 3000, 'test', 0x313735363733373334355f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(181, 'PA-20250901-1819', NULL, 'test', NULL, 'Financial', 'cash', 'test', 'Payroll', 'Payroll', 'Payroll', 200, 'test', 0x313735363733363235345f62696c6c2e706466, '2025-09-17', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(188, 'EM-20250903-1831', NULL, 'budget manager', NULL, 'Financial', 'Ecash', 'test', NULL, NULL, NULL, 300, 'test', 0x313735363837373735355f62696c6c2e706466, '2025-09-03', '2025-09-02 23:35:55', NULL, NULL, '', 1, '', '', 'test', 'test', '12345678910', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(189, 'EM-20250903-6320', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'test', NULL, NULL, NULL, 3000, 'test', 0x313735363837383236395f62696c6c2e706466, '2025-09-03', '2025-09-02 23:44:29', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(190, 'PA-INV-20250903-2949', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 123, 'Payment for invoice INV-INV-20250903-2949', 0x313735363837383330315f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(191, 'PA-INV-20250904-8893', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 6000, 'Payment for invoice INV-INV-20250904-8893', 0x313735363936383430335f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(192, 'EM-20250904-4772', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'test', NULL, NULL, NULL, 300, 'test', 0x313735363938323437395f62696c6c2e706466, '2025-09-04', '2025-09-04 04:41:19', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(193, 'PA-INV-20250904-5117', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 300, 'Payment for invoice INV-INV-20250904-5117', 0x313735363938343137315f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(194, 'PA-INV-20250904-7250', NULL, 'budget manager', NULL, 'Financial', 'Ecash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 6500, 'Payment for invoice INV-INV-20250904-7250', 0x313735363938393436305f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', 'test', 'test', '12345678910', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(202, 'PA-20250901-4214', NULL, 'test', NULL, 'Logistic-2', 'cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 1000, 'Payment for invoice INV-20250901-4214', '', '2025-09-24', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(203, 'undefined20250904-8689', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'Account Payable', NULL, NULL, NULL, 650, 'Payment for invoice undefined20250904-8689', 0x313735363938393933375f62696c6c2e706466, '2025-09-23', '2025-10-14 17:36:16', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(204, 'PA-20250904-8297', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 5200, 'Payment for invoice INV-20250904-8297', 0x313735363938383538355f62696c6c2e706466, '2025-09-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(205, 'PA-20250904-1624', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 5000, 'Payment for invoice INV-20250904-1624', 0x313735363938333434345f62696c6c2e706466, '2025-09-29', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(207, 'PA-20251014-3935', NULL, 'admin admin', NULL, 'Financial', 'Cash', 'test', 'Payroll', 'Payroll', 'Payroll', 123, 'test', 0x313736303436343130385f616b697368612e706466, '2025-10-15', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(208, 'PA-20251014-3935', NULL, 'admin admin', NULL, 'Financial', 'Cash', 'test', 'Payroll', 'Payroll', 'Payroll', 123, 'test', 0x313736303436343130335f616b697368612e706466, '2025-10-15', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(209, 'PA-20250904-4783', NULL, 'budget manager', NULL, 'Financial', 'Cash', 'test', 'Payroll', 'Payroll', 'Payroll', 200, 'test', 0x313735363938323439365f62696c6c2e706466, '2025-09-04', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(210, 'PA-20250831-6774', NULL, 'test', NULL, 'Core-2', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 500, 'Payment for invoice INV-20250831-6774', '', '2025-09-03', '2026-01-30 11:00:49', NULL, NULL, 'BDO', 1, '1234567891011213', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(211, 'PA-20250831-6774', NULL, 'test', NULL, 'Core-2', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 500, 'Payment for invoice INV-20250831-6774', '', '2025-09-03', '2026-01-30 11:00:49', NULL, NULL, 'BDO', 1, '1234567891011213', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(212, 'PA-20250831-6774', NULL, 'test', NULL, 'Core-2', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 500, 'Payment for invoice INV-20250831-6774', '', '2025-09-03', '2026-01-30 11:00:49', NULL, NULL, 'BDO', 1, '1234567891011213', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(213, 'PA-20250831-6774', NULL, 'test', NULL, 'Core-2', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 500, 'Payment for invoice INV-20250831-6774', '', '2025-09-03', '2026-01-30 11:00:49', NULL, NULL, 'BDO', 1, '1234567891011213', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(214, 'PA-20250831-6774', NULL, 'test', NULL, 'Core-2', 'Bank Transfer', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 500, 'Payment for invoice INV-20250831-6774', '', '2025-09-03', '2026-01-30 11:00:49', NULL, NULL, 'BDO', 1, '1234567891011213', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(218, '518790', NULL, 'test', NULL, 'Human Resource-4', 'Bank Transfer', 'Account Payable', NULL, NULL, NULL, 500, 'Payment for invoice 518790', '', '2025-08-31', '2025-10-15 01:43:31', NULL, NULL, 'BDO', 1, '1234567891011213', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(221, 'EM-20251015-1838', NULL, 'admin admin', NULL, 'Human Resource-3', 'Cash', 'west', NULL, NULL, NULL, 3000, 'west', '', '2025-10-20', '2025-10-15 05:50:56', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(230, '20250831-1136', NULL, 'test', NULL, 'Financial', 'Bank Transfer', 'Account Payable', NULL, NULL, NULL, 5, 'Payment for invoice 20250831-1136', '', '2025-08-31', '2025-10-15 08:00:56', NULL, NULL, 'BDO', 1, '1234567891011213', 'test', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(240, 'PA-20251015-2477', NULL, 'admin admin', NULL, 'Core-1', 'Cash', 'project support', 'Payroll', 'Payroll', 'Payroll', 650, ' printing project reports', 0x313736303534353037305f3133333938313531363037383538393732362e6a7067, '2025-10-16', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(241, 'PA-20251015-7052', NULL, 'Miguel Reyes', NULL, 'Core-1', 'Ecash', 'maintenance', 'Payroll', 'Payroll', 'Payroll', 632, 'maintenance', 0x313736303534303937325f6c6f676f2e706e67, '2025-10-28', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', 'core', 'core1', '09123456789', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(242, 'PA-20251015-1863', NULL, 'Miguel Reyes', NULL, 'Core-1', 'Cash', 'project materials', 'Payroll', 'Payroll', 'Payroll', 19000, ' procurement of training materials', 0x313736303534313433385f3133333931313731303334383534313834382e6a7067, '2025-10-30', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(243, 'PA-20251015-8941', NULL, 'Sofia Dela Cruz', NULL, 'Administrative', 'Cash', 'office needs', 'Payroll', 'Payroll', 'Payroll', 1200, 'refill printer ink', '', '2025-10-15', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(247, 'PA-20251015-9788', NULL, 'Maria Santos', NULL, 'Human Resource-1', 'Cash', 'Benefits', 'Payroll', 'Payroll', 'Payroll', 50000, 'Mandatory government contributions', '', '2025-10-19', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(251, 'PA-INV-20251015-7617', NULL, 'admin admin', NULL, 'Administrative', 'Cash', 'Account Payable', 'Payroll', 'Payroll', 'Payroll', 300, 'Payment for invoice INV-20251015-7617', '', '2026-02-27', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(252, 'PA-C80DAE72', NULL, 'Liza Mendoza', NULL, 'Logistics', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 30000, 'Payroll for Liza Mendoza - Delivery Assistant (Period: Nov 01 - Nov 15, 2025)', '', '2026-01-31', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(253, 'PA-1114B700', NULL, 'Carlos Garcia', NULL, 'Logistics', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 37500, 'Payroll for Carlos Garcia - Logistics Manager (Period: Nov 01 - Nov 15, 2025)', '', '2026-01-31', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(254, 'VEN-INV-20251015-9694', NULL, 'admin admin', NULL, 'Administrative', 'Cash', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 32000, 'Payment for vendor invoice INV-20251015-9694', '', '2025-10-16', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, 'admin admin', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(255, 'VEN-INV-20251015-9039', NULL, 'admin admin', NULL, 'Human Resource-3', 'Ecash', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 70000, 'Payment for vendor invoice INV-20251015-9039', 0x313736303532383431335f5768697465616e64426c75654d6f6465726e4d696e696d616c697374426c616e6b50616765426f726465724134446f63756d656e742e706e67, '2025-11-18', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', 'test run', 'test run', '22444', NULL, 'admin admin', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(256, 'VEN-INV-20251015-8864', NULL, 'admin admin', NULL, 'Logistic-2', 'Cash', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 1800, 'Payment for vendor invoice INV-20251015-8864', '', '2025-10-31', '2026-01-30 11:00:49', NULL, NULL, '', 1, '', '', '', '', '', NULL, 'admin admin', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(258, 'REIMB-20260128-9986', NULL, 'Juls', NULL, 'Financials', 'Cash', 'Other Expenses - Taxes', 'Reimbursement', 'Reimbursement', 'Reimbursement', 50000, 'Reimbursement: taxes', 0x75706c6f6164732f72656365697074732f313736393538363031335f313736393532373038315f6a757374696669636174696f6e6578616d706c652e646f63, '2026-01-30', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, '369121', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(259, 'REIMB-20260127-3632', NULL, 'Ace', NULL, 'Financials', 'Cash', 'Other', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5000, 'Reimbursement: basta', 0x75706c6f6164732f72656365697074732f313736393532373038315f6a757374696669636174696f6e6578616d706c652e646f63, '2026-01-30', '2026-01-30 11:00:49', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, '123456', NULL, 0, 'Reimbursement Module', '0', '2026-01-30 11:51:17', 'Pending Disbursement'),
(260, 'REIMB-20251219-1005', NULL, 'Sophia Mendoza', NULL, 'Accounts Payables', 'Cash', 'Software', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4500, '0', 0x2f75706c6f6164732f72656365697074732f736f6674776172655f313030352e706466, '2026-01-30', '2026-01-30 11:02:40', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, '3302501', NULL, 0, 'Reimbursement Module', '0', '2026-01-30 12:02:40', 'Pending Disbursement'),
(261, 'REIMB-20260130-6126', NULL, 'TEST', NULL, 'Human Resource-3', 'Cash', 'Direct Operating Costs - Emergency Repairs', NULL, NULL, NULL, 5000, 'Reimbursement: TEST', 0x313736393737313931335f4275646765745f50726f706f73616c5f56696148616c652e706466, '2026-01-30', '2026-01-30 11:18:44', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(262, 'REIMB-20260130-9467', NULL, 'GLEN', NULL, 'Human Resource-3', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4000, 'Reimbursement: HONRADO', 0x313736393737323335375f4275646765745f50726f706f73616c5f56696148616c652e706466, '2026-01-30', '2026-01-30 11:26:05', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-01-30 19:26:05', 'Pending Disbursement'),
(263, 'PA-03140209', NULL, 'Juan Dela Cruz', NULL, 'HR', 'Bank', 'Payroll', NULL, NULL, NULL, 27500, 'Payroll for Juan Dela Cruz - HR Specialist (Period: Nov 01 - Nov 15, 2025)', '', '2026-02-02', '2026-01-30 04:39:31', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(264, 'REIMB-20260131-9380', NULL, 'Juanito Alfonso', NULL, 'Core-1', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6000, 'Reimbursement: subscription', 0x313736393832393233335f6469736275727365642d7265636f7264732e706466, '2026-01-31', '2026-01-31 04:24:45', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-01-31 12:24:45', 'Pending Disbursement'),
(265, 'REIMB-20260130-5029', NULL, 'LANCE', NULL, 'Human Resource-3', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5000, 'Reimbursement: LR', 0x313736393737333435345f4275646765745f50726f706f73616c5f56696148616c652e706466, '2026-01-31', '2026-01-31 04:24:45', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-01-31 12:24:45', 'Pending Disbursement'),
(266, 'VEN-INV-20251015-7328', NULL, 'Ethan Magsaysay', NULL, 'Administrative', 'Cash', 'Vendor Payment', NULL, NULL, NULL, 500, 'Payment for vendor invoice INV-20251015-7328', '', '2025-10-24', '2026-01-31 07:00:58', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(267, 'VEN-INV-20260203-1636', NULL, 'JGH Motor Company', NULL, 'Human Resource-1', 'Cash', 'Vendor Payment', NULL, NULL, NULL, 30000, 'Payment for vendor invoice INV-20260203-1636', 0x5b22313737303130313937315f696e766f6963652d56484c2d32303236303230312d373332372e706466222c22313737303130313937315f7265696d62757273656d656e745f7265706f72745f323032362d30312d33315430362d35302d35392e706466225d, '2026-02-28', '2026-02-05 15:26:07', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(268, 'VEN-INV-20260207-0526', NULL, 'Ace ', NULL, 'Logistic 1', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 100, 'Payment for vendor invoice INV-20260207-0526', 0x5b22313737303434323930325f696e766f6963652d56484c2d32303236303230312d373332372e706466222c22313737303434323930325f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-02-07', '2026-02-07 11:18:25', NULL, NULL, 'BDO', 1, '1123456789', 'Mario Reyes', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(269, 'VEN-INV-20260207-0319', NULL, 'SpeedFix Auto Service Center', NULL, 'Logistic 1', 'Cash', 'Vendor Payment', NULL, NULL, NULL, 5000, 'Payment for vendor invoice INV-20260207-0319', 0x5b22313737303434343232365f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-02-15', '2026-02-07 11:18:25', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(270, 'VEN-INV-20260207-0428', NULL, 'JGH Motor Company', NULL, 'Logistic 1', 'Cash', 'Vendor Payment', NULL, NULL, NULL, 2300, 'Payment for vendor invoice INV-20260207-0428', 0x5b22313737303434343839305f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-02-16', '2026-02-07 11:18:25', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(271, 'REIMB-20260130-9498', NULL, 'GLEN', NULL, 'Core-2', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6000, 'Reimbursement: honrado', 0x313736393737323234365f4275646765745f50726f706f73616c5f56696148616c652e706466, '2026-02-07', '2026-02-07 11:18:52', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-07 19:18:52', 'Pending Disbursement'),
(272, 'VEN-INV-20260207-3232', NULL, 'JGH Motor Company', NULL, 'Logistic 1', 'Cash', 'Vendor Payment', NULL, NULL, NULL, 2300, 'Payment for vendor invoice INV-20260207-3232', 0x5b22313737303434343938375f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-02-16', '2026-02-07 11:20:44', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(273, 'REIMB-20260205-2112', NULL, 'Juan', NULL, 'Core-1', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12000, 'Reimbursement: parts replacement', 0x313737303330333534325f696e766f6963652d56484c2d32303236303230312d373332372e706466, '2026-02-07', '2026-02-07 16:45:41', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-08 00:45:41', 'Pending Disbursement'),
(285, 'VEN-INV-20260208-7876', NULL, 'TechFix IT Solutions', NULL, 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 8000, 'Payment for vendor invoice INV-20260208-7876', 0x5b22313737303533303730315f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '2026-02-08 06:29:40', NULL, NULL, 'BDO', 1, '246532102130', 'TechFix', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(286, 'VEN-INV-20260208-9971', NULL, 'TechFix IT Solutions', NULL, 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 6750, 'Payment for vendor invoice INV-20260208-9971', 0x5b22313737303533303138365f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '2026-02-08 06:39:05', NULL, NULL, 'BDO', 1, '246532102130', 'TechFix', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(287, 'VEN-INV-20260208-7876', NULL, 'TechFix IT Solutions', NULL, 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 8000, 'Payment for vendor invoice INV-20260208-7876', 0x5b22313737303533303730315f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '2026-02-08 06:43:27', NULL, NULL, 'BDO', 1, '246532102130', 'TechFix', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(288, 'VEN-INV-20260208-9971', NULL, 'TechFix IT Solutions', NULL, 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 6750, 'Payment for vendor invoice INV-20260208-9971', 0x5b22313737303533303138365f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '2026-02-08 07:11:29', NULL, NULL, 'BDO', 1, '246532102130', 'TechFix', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(289, 'REIMB-20260131-6900', NULL, 'Jose Rizal', NULL, 'Core-2', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 32103, 'Reimbursement: hardware', 0x313736393833383532335f70617961626c65735f72656365697074735f6469736275727365642e706466, '2026-02-08', '2026-02-08 07:13:11', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-08 15:13:11', 'Pending Disbursement'),
(290, 'VEN-INV-20260208-7876', NULL, 'TechFix IT Solutions', NULL, 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 8000, 'Payment for vendor invoice INV-20260208-7876', 0x5b22313737303533303730315f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '2026-02-08 07:16:52', NULL, NULL, 'BDO', 1, '246532102130', 'TechFix', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(291, 'VEN-INV-20260208-9971', NULL, 'TechFix IT Solutions', NULL, 'Human Resource-3', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 6750, 'Payment for vendor invoice INV-20260208-9971', 0x5b22313737303533303138365f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-03-02', '2026-02-08 07:21:19', NULL, NULL, 'BDO', 1, '246532102130', 'TechFix', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(292, 'REIMB-20251218-1004', NULL, 'Carlos Gomez', NULL, 'Logistics', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3200, 'Reimbursement: Safety equipment and uniforms for warehouse team', 0x2f75706c6f6164732f72656365697074732f7361666574795f313030342e706e67, '2026-02-08', '2026-02-08 07:26:03', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-08 15:26:03', 'Pending Disbursement'),
(293, 'REIMB-20251217-1003', NULL, 'Ana Reyes', NULL, 'HR', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3500, 'Reimbursement: HR Certification renewal and materials', 0x2f75706c6f6164732f72656365697074732f747261696e696e675f313030332e706466, '2026-02-08', '2026-02-08 07:26:41', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-08 15:26:41', 'Pending Disbursement'),
(296, 'REIMB-20260205-2112', NULL, 'Juan', NULL, 'Core-1', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12000, 'Reimbursement: parts replacement', 0x313737303330333534325f696e766f6963652d56484c2d32303236303230312d373332372e706466, '2026-02-08', '2026-02-08 07:33:24', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-08 15:33:24', 'Pending Disbursement'),
(300, 'REIMB-20260131-6900', NULL, 'Jose Rizal', NULL, 'Core-2', 'Cash', 'Reimbursement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 32103, 'Reimbursement: hardware', 0x313736393833383532335f70617961626c65735f72656365697074735f6469736275727365642e706466, '2026-02-08', '2026-02-08 09:38:25', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-08 17:38:25', 'Pending Disbursement'),
(304, 'PA-E1BB06F4', NULL, 'Maria Santos', NULL, 'HR', 'Bank', 'Payroll', NULL, NULL, NULL, 40000, 'Payroll for Maria Santos - HR Manager (Period: Nov 01 - Nov 15, 2025)', '', '2026-02-11', '2026-02-08 03:02:03', NULL, NULL, '', 0, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(305, 'VEN-INV-20260209-9019', NULL, 'Antigravity Test Vendor', NULL, 'Administrative', 'Cash', 'Vendor Payment', NULL, NULL, NULL, 5000, 'Payment for vendor invoice INV-20260209-9019', 0x5b5d, '2026-03-11', '2026-02-09 03:32:29', NULL, NULL, '', 1, '', '', '', 'Mary Doe', '09123456789', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(306, 'VEN-INV-20251116-5498', NULL, 'Rapid Fleet Maintenance', NULL, 'Administrative', 'Cash', 'Vendor Payment', NULL, NULL, NULL, 12229, 'Payment for vendor invoice INV-20251116-5498', 0x73616d706c655f726563656970742e706466, '2025-11-16', '2026-02-10 15:21:35', NULL, NULL, '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(307, 'VEN-INV-20260201-7327', NULL, 'SpeedFix Auto Service Center', NULL, 'Logistic-1', 'Bank Transfer', 'Vendor Payment', NULL, NULL, NULL, 57049, 'Payment for vendor invoice INV-20260201-7327', 0x5b22313737303739343730345f696e766f6963652d56484c2d32303236303230312d373332372e706466225d, '2026-02-15', '2026-02-12 01:14:45', NULL, NULL, 'BDO', 1, '246532102130', 'SpeedFix', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(311, 'PA-78C8A047', NULL, 'Chloe Alexandra', NULL, 'Financials', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 25476, 'Payroll for Chloe Alexandra - Financial Analyst (Period: Nov 01 - Nov 15, 2025)', '', '2026-02-16', '2026-02-13 05:12:56', '2026-02-13', '2026-02-13', '', 0, '', '', '', '', '', NULL, NULL, '3202501', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(312, 'PA-FED1D160', NULL, 'Ethan Gabriel', NULL, 'Human Resource-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 33742, 'Payroll for Ethan Gabriel - HR Manager (Period: Nov 01 - Nov 15, 2025)', '', '2026-02-16', '2026-02-13 05:39:31', '2026-02-13', '2026-02-13', '', 0, '', '', '', '', '', NULL, NULL, '1202501', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(313, 'VEN-INV-2026-TEST-01', NULL, 'ViaHale Supplier Co.', NULL, 'Logistic 1', 'Bank Transfer', 'Vendor Payment', 'Vendor', 'Vendor', 'Vendor', 25500, 'Payment for vendor invoice INV-2026-TEST-01', 0x5b5d, '2026-03-15', '2026-02-13 14:05:17', '2026-02-13', '2026-02-13', '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(314, 'VEN-INV-20250903-6865', NULL, 'AIG Insurance Phils', NULL, 'Logistic-1', 'Bank Transfer', 'Vendor Payment', 'Vendor', 'Vendor', 'Vendor', 18163, 'Payment for vendor invoice INV-20250903-6865', 0x73616d706c655f726563656970742e706466, '2025-09-03', '2026-02-13 14:22:06', '2025-09-03', '2026-02-13', '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(315, 'VEN-INV-20251105-3449', NULL, 'Rapid Fleet Maintenance', '45 Shaw Blvd, Pasig City', 'Logistic-1', 'Bank Transfer', 'Vendor Payment', 'Vendor', 'Vendor', 'Vendor', 15602, 'Payment for vendor invoice INV-20251105-3449', 0x73616d706c655f726563656970742e706466, '2025-11-05', '2026-02-13 15:29:04', '2025-11-05', '2026-02-13', '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(316, 'REIMB-20260213-1001', NULL, 'Maria Santos', NULL, 'Core-1', 'Cash', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3500, 'Reimbursement: Purchase of office supplies and stationery', 0x313737303939363639315f73616d706c655f72656365697074312e706466, '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(317, 'REIMB-20260213-1002', NULL, 'Juan dela Cruz', NULL, 'Logistic-1', 'Cash', 'Fuel & Energy', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4200, 'Reimbursement: Fuel expenses for delivery trips', 0x313737303939363639315f73616d706c655f72656365697074322e706466, '2026-02-21', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(318, 'REIMB-20260213-1003', NULL, 'Pedro Garcia', NULL, 'Logistic-2', 'Bank Transfer', 'Vehicle Maintenance', 'Reimbursement', 'Reimbursement', 'Reimbursement', 8500, 'Reimbursement: Vehicle oil change and minor repairs', 0x313737303939363639315f73616d706c655f72656365697074332e706466, '2026-02-22', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(319, 'REIMB-20260213-1004', NULL, 'Ana Lopez', NULL, 'Human Resource-1', 'Cash', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5600, 'Reimbursement: Transportation and accommodation for HR training', 0x313737303939363639315f73616d706c655f72656365697074342e706466, '2026-02-23', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(320, 'REIMB-20260213-1005', NULL, 'Carlos Reyes', NULL, 'Logistic-1', 'Cash', 'Parts Replacement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12300, 'Reimbursement: Replacement of worn-out vehicle parts', 0x313737303939363639315f73616d706c655f72656365697074352e706466, '2026-02-24', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(321, 'REIMB-20260213-1006', NULL, 'Lisa Mendoza', NULL, 'Administrative', 'Bank Transfer', 'Software', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6800, 'Reimbursement: Annual software subscription renewal', 0x313737303939363639315f73616d706c655f72656365697074362e706466, '2026-02-25', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(322, 'REIMB-20260213-1007', NULL, 'Jose Navarro', NULL, 'Human Resource-2', 'Cash', 'Training & Development', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4500, 'Reimbursement: Employee training seminar fees', 0x313737303939363639315f73616d706c655f72656365697074372e706466, '2026-02-26', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(323, 'REIMB-20260213-1008', NULL, 'Emma Cruz', NULL, 'Core-2', 'Cash', 'Office Equipment', 'Reimbursement', 'Reimbursement', 'Reimbursement', 9200, 'Reimbursement: Purchase of computer peripherals and accessories', 0x313737303939363639315f73616d706c655f72656365697074382e706466, '2026-02-27', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(324, 'REIMB-20260213-1009', NULL, 'Roberto Torres', NULL, 'Logistic-2', 'Bank Transfer', 'Emergency Repairs', 'Reimbursement', 'Reimbursement', 'Reimbursement', 15600, 'Reimbursement: Emergency vehicle repair after breakdown', 0x313737303939363639315f73616d706c655f72656365697074392e706466, '2026-02-28', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(325, 'REIMB-20260213-1010', NULL, 'Sofia Ramos', NULL, 'Administrative', 'Cash', 'Utilities', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3800, 'Reimbursement: Office utility bills payment', 0x313737303939363639315f73616d706c655f72656365697074392e706466, '2026-03-01', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(326, 'VEN-INV-20260213-1001', NULL, 'AutoParts Supply Co.', NULL, 'Logistic-1', 'Cash', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 8500, 'Payment for vendor invoice INV-20260213-1001 - Vehicle spare parts', 0x73616d706c655f646f63756d656e742e706466, '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'AutoParts Supply Co.', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(327, 'VEN-INV-20260213-1002', NULL, 'Office Depot Manila', NULL, 'Administrative', 'Bank Transfer', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 12300, 'Payment for vendor invoice INV-20260213-1002 - Office furniture', 0x73616d706c655f646f63756d656e742e706466, '2026-02-21', '2026-02-13 02:15:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Office Depot Manila', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(328, 'VEN-INV-20260213-1003', NULL, 'TechSolutions Inc.', NULL, 'Human Resource-1', 'Bank Transfer', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 25000, 'Payment for vendor invoice INV-20260213-1003 - IT equipment', 0x73616d706c655f646f63756d656e742e706466, '2026-02-22', '2026-02-13 02:30:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'TechSolutions Inc.', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(329, 'VEN-INV-20260213-1004', NULL, 'Globe Telecom', NULL, 'Core-1', 'Bank Transfer', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 15600, 'Payment for vendor invoice INV-20260213-1004 - Monthly internet services', 0x73616d706c655f646f63756d656e742e706466, '2026-02-23', '2026-02-13 02:45:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Globe Telecom', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(330, 'VEN-INV-20260213-1005', NULL, 'Shell Makati', NULL, 'Logistic-2', 'Cash', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 18900, 'Payment for vendor invoice INV-20260213-1005 - Fuel purchase', 0x73616d706c655f646f63756d656e742e706466, '2026-02-24', '2026-02-13 03:00:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Shell Makati', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(331, 'VEN-INV-20260213-1006', NULL, 'Metro Cleaning Services', NULL, 'Administrative', 'Bank Transfer', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 9500, 'Payment for vendor invoice INV-20260213-1006 - Janitorial services', 0x73616d706c655f646f63756d656e742e706466, '2026-02-25', '2026-02-13 03:15:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Metro Cleaning Services', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(332, 'VEN-INV-20260213-1007', NULL, 'Manila Water Company', NULL, 'Core-2', 'Bank Transfer', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 6800, 'Payment for vendor invoice INV-20260213-1007 - Water bill payment', 0x73616d706c655f646f63756d656e742e706466, '2026-02-26', '2026-02-13 03:30:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Manila Water Company', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(333, 'VEN-INV-20260213-1008', NULL, 'Meralco', NULL, 'Administrative', 'Bank Transfer', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 22400, 'Payment for vendor invoice INV-20260213-1008 - Electricity bill', 0x73616d706c655f646f63756d656e742e706466, '2026-02-27', '2026-02-13 03:45:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Meralco', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(334, 'VEN-INV-20260213-1009', NULL, 'Security Plus Agency', NULL, 'Human Resource-2', 'Cash', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 28000, 'Payment for vendor invoice INV-20260213-1009 - Security services', 0x73616d706c655f646f63756d656e742e706466, '2026-02-28', '2026-02-13 04:00:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Security Plus Agency', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(335, 'VEN-INV-20260213-1010', NULL, 'Rapid Fleet Maintenance', NULL, 'Logistic-1', 'Bank Transfer', 'Vendor Payment', 'Vendor Payment', 'Vendor Payment', 'Accounts Payable', 16500, 'Payment for vendor invoice INV-20260213-1010 - Vehicle maintenance', 0x73616d706c655f646f63756d656e742e706466, '2026-03-01', '2026-02-13 04:15:00', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Rapid Fleet Maintenance', NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(336, 'REIMB-20260213-2001', NULL, 'Maria Santos', NULL, 'Core-1', 'Cash', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3500, 'Reimbursement: Purchase of office supplies and stationery', 0x73616d706c655f72656365697074312e706466, '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-001', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:05:00', 'Pending Disbursement'),
(337, 'REIMB-20260213-2002', NULL, 'Juan dela Cruz', NULL, 'Logistic-1', 'Cash', 'Fuel & Energy', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4200, 'Reimbursement: Fuel expenses for delivery trips', 0x73616d706c655f72656365697074322e706466, '2026-02-21', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-002', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:20:00', 'Pending Disbursement'),
(338, 'REIMB-20260213-2003', NULL, 'Pedro Garcia', NULL, 'Logistic-2', 'Bank Transfer', 'Vehicle Maintenance', 'Reimbursement', 'Reimbursement', 'Reimbursement', 8500, 'Reimbursement: Vehicle oil change and minor repairs', 0x73616d706c655f72656365697074332e706466, '2026-02-22', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-003', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:35:00', 'Pending Disbursement'),
(339, 'REIMB-20260213-2004', NULL, 'Ana Lopez', NULL, 'Human Resource-1', 'Cash', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5600, 'Reimbursement: Transportation and accommodation for HR training', 0x73616d706c655f72656365697074342e706466, '2026-02-23', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-004', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:50:00', 'Pending Disbursement'),
(340, 'REIMB-20260213-2005', NULL, 'Carlos Reyes', NULL, 'Logistic-1', 'Cash', 'Parts Replacement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12300, 'Reimbursement: Replacement of worn-out vehicle parts', 0x73616d706c655f72656365697074352e706466, '2026-02-24', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-005', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:05:00', 'Pending Disbursement'),
(341, 'REIMB-20260213-2006', NULL, 'Lisa Mendoza', NULL, 'Administrative', 'Bank Transfer', 'Software', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6800, 'Reimbursement: Annual software subscription renewal', 0x73616d706c655f72656365697074362e706466, '2026-02-25', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-006', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:20:00', 'Pending Disbursement');
INSERT INTO `pa` (`id`, `reference_id`, `driver_id`, `account_name`, `vendor_address`, `requested_department`, `mode_of_payment`, `expense_categories`, `transaction_type`, `payout_type`, `source_module`, `amount`, `description`, `document`, `payment_due`, `requested_at`, `submitted_date`, `approved_date`, `bank_name`, `from_payable`, `bank_account_number`, `bank_account_name`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `vendor_id`, `supplier_name`, `employee_id`, `wallet_id`, `is_misclassified`, `approval_source`, `approved_by`, `approved_at`, `status`) VALUES
(342, 'REIMB-20260213-2007', NULL, 'Jose Navarro', NULL, 'Human Resource-2', 'Cash', 'Training & Development', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4500, 'Reimbursement: Employee training seminar fees', 0x73616d706c655f72656365697074372e706466, '2026-02-26', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-007', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:35:00', 'Pending Disbursement'),
(343, 'REIMB-20260213-2008', NULL, 'Emma Cruz', NULL, 'Core-2', 'Cash', 'Office Equipment', 'Reimbursement', 'Reimbursement', 'Reimbursement', 9200, 'Reimbursement: Purchase of computer peripherals and accessories', 0x73616d706c655f72656365697074382e706466, '2026-02-27', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-008', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:50:00', 'Pending Disbursement'),
(344, 'REIMB-20260213-2009', NULL, 'Roberto Torres', NULL, 'Logistic-2', 'Bank Transfer', 'Emergency Repairs', 'Reimbursement', 'Reimbursement', 'Reimbursement', 15600, 'Reimbursement: Emergency vehicle repair after breakdown', 0x73616d706c655f72656365697074392e706466, '2026-02-28', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-009', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:05:00', 'Pending Disbursement'),
(345, 'REIMB-20260213-2010', NULL, 'Sofia Ramos', NULL, 'Administrative', 'Cash', 'Utilities', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3800, 'Reimbursement: Office utility bills payment', 0x73616d706c655f726563656970743130, '2026-03-01', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-010', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:20:00', 'Pending Disbursement'),
(346, 'PA-20260213-4001', NULL, 'Juan Dela Cruz', NULL, 'HR', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 35000, 'Payroll for Juan Dela Cruz - HR Specialist (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(347, 'PA-20260213-4002', NULL, 'Maria Santos', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 42000, 'Payroll for Maria Santos - Administrative Manager (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(348, 'PA-20260213-4003', NULL, 'Pedro Gonzales', NULL, 'Logistic-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 38000, 'Payroll for Pedro Gonzales - Logistics Supervisor (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(349, 'PA-20260213-4004', NULL, 'Ana Reyes', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 32000, 'Payroll for Ana Reyes - Accountant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(350, 'PA-20260213-4005', NULL, 'Carlos Mendoza', NULL, 'Human Resource-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 36500, 'Payroll for Carlos Mendoza - HR Officer (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(351, 'PA-20260213-4006', NULL, 'Lisa Torres', NULL, 'Core-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 33000, 'Payroll for Lisa Torres - Finance Officer (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(352, 'PA-20260213-4007', NULL, 'Roberto Cruz', NULL, 'Logistic-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 30000, 'Payroll for Roberto Cruz - Delivery Assistant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(353, 'PA-20260213-4008', NULL, 'Elena Garcia', NULL, 'Human Resource-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 34500, 'Payroll for Elena Garcia - Recruitment Specialist (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(354, 'PA-20260213-4009', NULL, 'Miguel Flores', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 31000, 'Payroll for Miguel Flores - Office Assistant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(355, 'PA-20260213-4010', NULL, 'Sofia Lopez', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 37500, 'Payroll for Sofia Lopez - Senior Accountant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(356, 'REIMB-20260213-2001', NULL, 'Maria Santos', NULL, 'Core-1', 'Cash', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3500, 'Reimbursement: Purchase of office supplies and stationery', 0x73616d706c655f72656365697074312e706466, '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-001', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:05:00', 'Pending Disbursement'),
(357, 'REIMB-20260213-2002', NULL, 'Juan dela Cruz', NULL, 'Logistic-1', 'Cash', 'Fuel & Energy', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4200, 'Reimbursement: Fuel expenses for delivery trips', 0x73616d706c655f72656365697074322e706466, '2026-02-21', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-002', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:20:00', 'Pending Disbursement'),
(358, 'REIMB-20260213-2003', NULL, 'Pedro Garcia', NULL, 'Logistic-2', 'Bank Transfer', 'Vehicle Maintenance', 'Reimbursement', 'Reimbursement', 'Reimbursement', 8500, 'Reimbursement: Vehicle oil change and minor repairs', 0x73616d706c655f72656365697074332e706466, '2026-02-22', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-003', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:35:00', 'Pending Disbursement'),
(359, 'REIMB-20260213-2004', NULL, 'Ana Lopez', NULL, 'Human Resource-1', 'Cash', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5600, 'Reimbursement: Transportation and accommodation for HR training', 0x73616d706c655f72656365697074342e706466, '2026-02-23', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-004', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:50:00', 'Pending Disbursement'),
(360, 'REIMB-20260213-2005', NULL, 'Carlos Reyes', NULL, 'Logistic-1', 'Cash', 'Parts Replacement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12300, 'Reimbursement: Replacement of worn-out vehicle parts', 0x73616d706c655f72656365697074352e706466, '2026-02-24', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-005', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:05:00', 'Pending Disbursement'),
(361, 'REIMB-20260213-2006', NULL, 'Lisa Mendoza', NULL, 'Administrative', 'Bank Transfer', 'Software', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6800, 'Reimbursement: Annual software subscription renewal', 0x73616d706c655f72656365697074362e706466, '2026-02-25', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-006', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:20:00', 'Pending Disbursement'),
(362, 'REIMB-20260213-2007', NULL, 'Jose Navarro', NULL, 'Human Resource-2', 'Cash', 'Training & Development', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4500, 'Reimbursement: Employee training seminar fees', 0x73616d706c655f72656365697074372e706466, '2026-02-26', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-007', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:35:00', 'Pending Disbursement'),
(363, 'REIMB-20260213-2008', NULL, 'Emma Cruz', NULL, 'Core-2', 'Cash', 'Office Equipment', 'Reimbursement', 'Reimbursement', 'Reimbursement', 9200, 'Reimbursement: Purchase of computer peripherals and accessories', 0x73616d706c655f72656365697074382e706466, '2026-02-27', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-008', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:50:00', 'Pending Disbursement'),
(364, 'REIMB-20260213-2009', NULL, 'Roberto Torres', NULL, 'Logistic-2', 'Bank Transfer', 'Emergency Repairs', 'Reimbursement', 'Reimbursement', 'Reimbursement', 15600, 'Reimbursement: Emergency vehicle repair after breakdown', 0x73616d706c655f72656365697074392e706466, '2026-02-28', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-009', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:05:00', 'Pending Disbursement'),
(365, 'REIMB-20260213-2010', NULL, 'Sofia Ramos', NULL, 'Administrative', 'Cash', 'Utilities', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3800, 'Reimbursement: Office utility bills payment', 0x73616d706c655f726563656970743130, '2026-03-01', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-010', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:20:00', 'Pending Disbursement'),
(366, 'REIMB-20260213-2001', NULL, 'Maria Santos', NULL, 'Core-1', 'Cash', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3500, 'Reimbursement: Purchase of office supplies and stationery', 0x73616d706c655f72656365697074312e706466, '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-001', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:05:00', 'Pending Disbursement'),
(367, 'REIMB-20260213-2002', NULL, 'Juan dela Cruz', NULL, 'Logistic-1', 'Cash', 'Fuel & Energy', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4200, 'Reimbursement: Fuel expenses for delivery trips', 0x73616d706c655f72656365697074322e706466, '2026-02-21', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-002', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:20:00', 'Pending Disbursement'),
(368, 'REIMB-20260213-2003', NULL, 'Pedro Garcia', NULL, 'Logistic-2', 'Bank Transfer', 'Vehicle Maintenance', 'Reimbursement', 'Reimbursement', 'Reimbursement', 8500, 'Reimbursement: Vehicle oil change and minor repairs', 0x73616d706c655f72656365697074332e706466, '2026-02-22', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-003', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:35:00', 'Pending Disbursement'),
(369, 'REIMB-20260213-2004', NULL, 'Ana Lopez', NULL, 'Human Resource-1', 'Cash', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5600, 'Reimbursement: Transportation and accommodation for HR training', 0x73616d706c655f72656365697074342e706466, '2026-02-23', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-004', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:50:00', 'Pending Disbursement'),
(370, 'REIMB-20260213-2005', NULL, 'Carlos Reyes', NULL, 'Logistic-1', 'Cash', 'Parts Replacement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12300, 'Reimbursement: Replacement of worn-out vehicle parts', 0x73616d706c655f72656365697074352e706466, '2026-02-24', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-005', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:05:00', 'Pending Disbursement'),
(371, 'REIMB-20260213-2006', NULL, 'Lisa Mendoza', NULL, 'Administrative', 'Bank Transfer', 'Software', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6800, 'Reimbursement: Annual software subscription renewal', 0x73616d706c655f72656365697074362e706466, '2026-02-25', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-006', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:20:00', 'Pending Disbursement'),
(372, 'REIMB-20260213-2007', NULL, 'Jose Navarro', NULL, 'Human Resource-2', 'Cash', 'Training & Development', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4500, 'Reimbursement: Employee training seminar fees', 0x73616d706c655f72656365697074372e706466, '2026-02-26', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-007', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:35:00', 'Pending Disbursement'),
(373, 'REIMB-20260213-2008', NULL, 'Emma Cruz', NULL, 'Core-2', 'Cash', 'Office Equipment', 'Reimbursement', 'Reimbursement', 'Reimbursement', 9200, 'Reimbursement: Purchase of computer peripherals and accessories', 0x73616d706c655f72656365697074382e706466, '2026-02-27', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-008', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:50:00', 'Pending Disbursement'),
(374, 'REIMB-20260213-2009', NULL, 'Roberto Torres', NULL, 'Logistic-2', 'Bank Transfer', 'Emergency Repairs', 'Reimbursement', 'Reimbursement', 'Reimbursement', 15600, 'Reimbursement: Emergency vehicle repair after breakdown', 0x73616d706c655f72656365697074392e706466, '2026-02-28', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-009', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:05:00', 'Pending Disbursement'),
(375, 'REIMB-20260213-2010', NULL, 'Sofia Ramos', NULL, 'Administrative', 'Cash', 'Utilities', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3800, 'Reimbursement: Office utility bills payment', 0x73616d706c655f726563656970743130, '2026-03-01', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-010', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:20:00', 'Pending Disbursement'),
(376, 'PA-20260213-4001', NULL, 'Juan Dela Cruz', NULL, 'HR', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 35000, 'Payroll for Juan Dela Cruz - HR Specialist (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(377, 'PA-20260213-4002', NULL, 'Maria Santos', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 42000, 'Payroll for Maria Santos - Administrative Manager (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(378, 'PA-20260213-4003', NULL, 'Pedro Gonzales', NULL, 'Logistic-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 38000, 'Payroll for Pedro Gonzales - Logistics Supervisor (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(379, 'PA-20260213-4004', NULL, 'Ana Reyes', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 32000, 'Payroll for Ana Reyes - Accountant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(380, 'PA-20260213-4005', NULL, 'Carlos Mendoza', NULL, 'Human Resource-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 36500, 'Payroll for Carlos Mendoza - HR Officer (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(381, 'PA-20260213-4006', NULL, 'Lisa Torres', NULL, 'Core-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 33000, 'Payroll for Lisa Torres - Finance Officer (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(382, 'PA-20260213-4007', NULL, 'Roberto Cruz', NULL, 'Logistic-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 30000, 'Payroll for Roberto Cruz - Delivery Assistant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(383, 'PA-20260213-4008', NULL, 'Elena Garcia', NULL, 'Human Resource-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 34500, 'Payroll for Elena Garcia - Recruitment Specialist (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(384, 'PA-20260213-4009', NULL, 'Miguel Flores', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 31000, 'Payroll for Miguel Flores - Office Assistant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(385, 'PA-20260213-4010', NULL, 'Sofia Lopez', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 37500, 'Payroll for Sofia Lopez - Senior Accountant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(386, 'REIMB-20260213-2001', NULL, 'Maria Santos', NULL, 'Core-1', 'Cash', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3500, 'Reimbursement: Purchase of office supplies and stationery', 0x73616d706c655f72656365697074312e706466, '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-001', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:05:00', 'Pending Disbursement'),
(387, 'REIMB-20260213-2002', NULL, 'Juan dela Cruz', NULL, 'Logistic-1', 'Cash', 'Fuel & Energy', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4200, 'Reimbursement: Fuel expenses for delivery trips', 0x73616d706c655f72656365697074322e706466, '2026-02-21', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-002', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:20:00', 'Pending Disbursement'),
(388, 'REIMB-20260213-2003', NULL, 'Pedro Garcia', NULL, 'Logistic-2', 'Bank Transfer', 'Vehicle Maintenance', 'Reimbursement', 'Reimbursement', 'Reimbursement', 8500, 'Reimbursement: Vehicle oil change and minor repairs', 0x73616d706c655f72656365697074332e706466, '2026-02-22', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-003', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:35:00', 'Pending Disbursement'),
(389, 'REIMB-20260213-2004', NULL, 'Ana Lopez', NULL, 'Human Resource-1', 'Cash', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5600, 'Reimbursement: Transportation and accommodation for HR training', 0x73616d706c655f72656365697074342e706466, '2026-02-23', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-004', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 10:50:00', 'Pending Disbursement'),
(390, 'REIMB-20260213-2005', NULL, 'Carlos Reyes', NULL, 'Logistic-1', 'Cash', 'Parts Replacement', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12300, 'Reimbursement: Replacement of worn-out vehicle parts', 0x73616d706c655f72656365697074352e706466, '2026-02-24', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-005', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:05:00', 'Pending Disbursement'),
(391, 'REIMB-20260213-2006', NULL, 'Lisa Mendoza', NULL, 'Administrative', 'Bank Transfer', 'Software', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6800, 'Reimbursement: Annual software subscription renewal', 0x73616d706c655f72656365697074362e706466, '2026-02-25', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-006', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:20:00', 'Pending Disbursement'),
(392, 'REIMB-20260213-2007', NULL, 'Jose Navarro', NULL, 'Human Resource-2', 'Cash', 'Training & Development', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4500, 'Reimbursement: Employee training seminar fees', 0x73616d706c655f72656365697074372e706466, '2026-02-26', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-007', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:35:00', 'Pending Disbursement'),
(393, 'REIMB-20260213-2008', NULL, 'Emma Cruz', NULL, 'Core-2', 'Cash', 'Office Equipment', 'Reimbursement', 'Reimbursement', 'Reimbursement', 9200, 'Reimbursement: Purchase of computer peripherals and accessories', 0x73616d706c655f72656365697074382e706466, '2026-02-27', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-008', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 11:50:00', 'Pending Disbursement'),
(394, 'REIMB-20260213-2009', NULL, 'Roberto Torres', NULL, 'Logistic-2', 'Bank Transfer', 'Emergency Repairs', 'Reimbursement', 'Reimbursement', 'Reimbursement', 15600, 'Reimbursement: Emergency vehicle repair after breakdown', 0x73616d706c655f72656365697074392e706466, '2026-02-28', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-009', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:05:00', 'Pending Disbursement'),
(395, 'REIMB-20260213-2010', NULL, 'Sofia Ramos', NULL, 'Administrative', 'Cash', 'Utilities', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3800, 'Reimbursement: Office utility bills payment', 0x73616d706c655f726563656970743130, '2026-03-01', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EMP-010', NULL, 0, 'Reimbursement Module', 'Ethan Magsaysay', '2026-02-13 12:20:00', 'Pending Disbursement'),
(396, 'PA-20260213-4001', NULL, 'Juan Dela Cruz', NULL, 'HR', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 35000, 'Payroll for Juan Dela Cruz - HR Specialist (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(397, 'PA-20260213-4002', NULL, 'Maria Santos', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 42000, 'Payroll for Maria Santos - Administrative Manager (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(398, 'PA-20260213-4003', NULL, 'Pedro Gonzales', NULL, 'Logistic-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 38000, 'Payroll for Pedro Gonzales - Logistics Supervisor (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(399, 'PA-20260213-4004', NULL, 'Ana Reyes', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 32000, 'Payroll for Ana Reyes - Accountant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 02:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(400, 'PA-20260213-4005', NULL, 'Carlos Mendoza', NULL, 'Human Resource-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 36500, 'Payroll for Carlos Mendoza - HR Officer (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(401, 'PA-20260213-4006', NULL, 'Lisa Torres', NULL, 'Core-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 33000, 'Payroll for Lisa Torres - Finance Officer (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(402, 'PA-20260213-4007', NULL, 'Roberto Cruz', NULL, 'Logistic-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 30000, 'Payroll for Roberto Cruz - Delivery Assistant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:30:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(403, 'PA-20260213-4008', NULL, 'Elena Garcia', NULL, 'Human Resource-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 34500, 'Payroll for Elena Garcia - Recruitment Specialist (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 03:45:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(404, 'PA-20260213-4009', NULL, 'Miguel Flores', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 31000, 'Payroll for Miguel Flores - Office Assistant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 04:00:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(405, 'PA-20260213-4010', NULL, 'Sofia Lopez', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 37500, 'Payroll for Sofia Lopez - Senior Accountant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-20', '2026-02-13 04:15:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(406, 'VEN-INV-20260213-1001', NULL, 'AutoParts Supply Co.', '123 Quezon Ave, Quezon City', 'Logistic-1', 'Cash', 'Vendor Payment', 'Vendor', 'Vendor', 'Vendor', 8500, 'Payment for vendor invoice INV-20260213-1001', 0x73616d706c655f696e766f6963652e706466, '2026-02-20', '2026-02-14 04:02:59', '2026-02-13', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(407, 'VEN-INV-20260213-1002', NULL, 'Office Depot Manila', '45 EDSA, Mandaluyong City', 'Administrative', 'Bank Transfer', 'Vendor Payment', 'Vendor', 'Vendor', 'Vendor', 12300, 'Payment for vendor invoice INV-20260213-1002', 0x73616d706c655f696e766f6963652e706466, '2026-02-21', '2026-02-14 04:03:56', '2026-02-13', '2026-02-14', 'BDO', 1, '1234567890', 'Office Depot Account', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(411, 'REIM-20260214-103', NULL, 'Ana Clara', NULL, 'Human Resource-1', 'CASH', 'Office Operations Cost', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4500, 'Reimbursement: Office Operations Cost - Recruitment event snacks and materials', NULL, '2026-02-21', '2026-02-14 04:48:13', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-HR-003', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 12:48:13', 'Pending Disbursement'),
(412, 'PA-D1E0628C', NULL, 'Gregorio del Pilar', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 21500, 'Payroll for Gregorio del Pilar - Clerk (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 20:50:26', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-ADM-010', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(415, 'DRV-D-20260213-9010', NULL, 'Alberto Garcia', NULL, 'Logistic-1', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 3341, 'Driver Payout for Alberto Garcia (ID: DRV-10010)', NULL, '2026-02-14', '2026-02-13 04:15:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10010', 'WALLET-10010', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(416, 'DRV-D-20260213-3009', NULL, 'Fernando Lopez', NULL, 'Human Resource-2', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 2890, 'Driver Payout for Fernando Lopez (ID: DRV-10009)', NULL, '2026-02-14', '2026-02-13 04:00:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10009', 'WALLET-10009', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(417, 'DRV-D-20260213-3008', NULL, 'Ricardo Gomez', NULL, 'Logistic-2', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 3685, 'Driver Payout for Ricardo Gomez (ID: DRV-10008)', NULL, '2026-02-14', '2026-02-13 03:45:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10008', 'WALLET-10008', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(418, 'VEN-INV-20260213-1003', NULL, 'TechSolutions Inc.', '88 BGC, Taguig City', 'Human Resource-1', 'Bank Transfer', 'Vendor Payment', 'Vendor', 'Vendor', 'Vendor', 25000, 'Payment for vendor invoice INV-20260213-1003', 0x73616d706c655f696e766f6963652e706466, '2026-02-22', '2026-02-14 06:04:53', '2026-02-13', '2026-02-14', 'BPI', 1, '0987654321', 'TechSolutions Inc', '', '', '', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(421, 'REIM-20260214-101', NULL, 'Maria Santos', NULL, 'Core-1', 'CASH', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3500, 'Reimbursement: Office Supplies - Monthly office stationery and printer ink', NULL, '2026-02-21', '2026-02-14 07:20:29', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-FN-001', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:20:29', 'Pending Disbursement'),
(422, 'REIM-20260214-102', NULL, 'Jose Reyes', NULL, 'Logistic-1', 'CASH', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 8201, 'Reimbursement: Travel Expenses - Fuel and meals for Batangas delivery route', NULL, '2026-02-21', '2026-02-14 07:21:29', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-LG-002', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:21:29', 'Pending Disbursement'),
(423, 'REIM-20260214-104', NULL, 'Rafael Garcia', NULL, 'Maintenance', 'CASH', 'Maintenance & Servicing', 'Reimbursement', 'Reimbursement', 'Reimbursement', 15000, 'Reimbursement: Maintenance & Servicing - Emergency AC repair for Server Room', NULL, '2026-02-21', '2026-02-14 07:22:01', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-MT-004', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:22:01', 'Pending Disbursement'),
(424, 'REIM-20260214-105', NULL, 'Lito Lapid', NULL, 'Logistic-2', 'CASH', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 6750, 'Reimbursement: Travel Expenses - Toll fees and overnight accommodation', NULL, '2026-02-21', '2026-02-14 07:22:01', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-LG-005', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:22:01', 'Pending Disbursement'),
(425, 'REIM-20260214-106', NULL, 'Grace Tan', NULL, 'Administrative', 'CASH', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 3200, 'Reimbursement: Office Supplies - New ergonomic chairs for reception', NULL, '2026-02-21', '2026-02-14 07:22:01', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-AD-006', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:22:01', 'Pending Disbursement'),
(426, 'REIM-20260214-107', NULL, 'Mark Bautista', NULL, 'Core-2', 'CASH', 'Office Operations Cost', 'Reimbursement', 'Reimbursement', 'Reimbursement', 5600, 'Reimbursement: Office Operations Cost - Team building venue reservation fee', NULL, '2026-02-21', '2026-02-14 07:22:01', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-CR-007', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:22:01', 'Pending Disbursement'),
(427, 'REIM-20260214-108', NULL, 'Sarah Geronimo', NULL, 'Human Resource-2', 'CASH', 'Office Supplies', 'Reimbursement', 'Reimbursement', 'Reimbursement', 4100, 'Reimbursement: Office Supplies - ID printing supplies and lanyards', NULL, '2026-02-21', '2026-02-14 07:22:01', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-HR-008', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:22:01', 'Pending Disbursement'),
(428, 'REIM-20260214-109', NULL, 'Coco Martin', NULL, 'Logistic-1', 'CASH', 'Maintenance & Servicing', 'Reimbursement', 'Reimbursement', 'Reimbursement', 9800, 'Reimbursement: Maintenance & Servicing - Truck A-105 tire replacement', NULL, '2026-02-21', '2026-02-14 07:22:01', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-LG-009', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:22:01', 'Pending Disbursement'),
(429, 'REIM-20260214-110', NULL, 'Regine Velasquez', NULL, 'Core-1', 'CASH', 'Travel Expenses', 'Reimbursement', 'Reimbursement', 'Reimbursement', 12500, 'Reimbursement: Travel Expenses - Client meeting expenses Cebu branch', NULL, '2026-02-21', '2026-02-14 07:22:01', '2026-02-14', '2026-02-14', '', 1, '', '', '', '', '', NULL, NULL, 'EMP-CR-010', NULL, 0, 'Reimbursement Approval', 'System', '2026-02-14 15:22:01', 'Pending Disbursement'),
(430, 'PA-9C9F9933', NULL, 'Jose Rizal', NULL, 'Administrative', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 28062, 'Payroll for Jose Rizal - Manager (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:22:47', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-MGT-003', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(431, 'PA-28ACAF92', NULL, 'Apolinario Mabini', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 22883, 'Payroll for Apolinario Mabini - IT Support (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:29:20', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-IT-007', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(432, 'PAY-60', NULL, 'Juan Cruz', NULL, 'Core-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 21875, 'Payroll for Juan Cruz - Operations Staff (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:35:55', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-OPS-001', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(433, 'PAY-67', NULL, 'Antonio Luna', NULL, 'Core-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 24833, 'Payroll for Antonio Luna - System Admin (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-IT-008', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(434, 'PAY-61', NULL, 'Maria Clara', NULL, 'Core-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 23583, 'Payroll for Maria Clara - Operations Lead (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-OPS-002', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(435, 'PAY-68', NULL, 'Melchora Aquino', NULL, 'Financials', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 28150, 'Payroll for Melchora Aquino - Accountant (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-FIN-009', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(436, 'PAY-65', NULL, 'Gabriela Silang', NULL, 'Human Resource-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 27150, 'Payroll for Gabriela Silang - HR Associate (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-HR-006', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(437, 'PAY-63', NULL, 'Andres Bonifacio', NULL, 'Logistic-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 25800, 'Payroll for Andres Bonifacio - Driver (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-LOG-004', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(438, 'PAY-6', NULL, 'Lucas Matteo', NULL, 'Logistic-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 21651, 'Payroll for Lucas Matteo - Delivery Assistant (Period: Nov 16 - Nov 30, 2025)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, '2202503', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(439, 'PAY-4', NULL, 'Mason Taylor', NULL, 'Logistic-1', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 28245, 'Payroll for Mason Taylor - Logistics Manager (Period: Nov 01 - Nov 15, 2025)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, '2202501', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(440, 'PAY-64', NULL, 'Emilio Aguinaldo', NULL, 'Logistic-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 21687, 'Payroll for Emilio Aguinaldo - Helper (Period: Feb 01 - Feb 15, 2026)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, 'EMP-LOG-005', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(441, 'PAY-5', NULL, 'Olivia Grace', NULL, 'Logistic-2', 'Bank', 'Payroll', 'Payroll', 'Payroll', 'Payroll', 19595, 'Payroll for Olivia Grace - Warehouse Officer (Period: Nov 01 - Nov 15, 2025)', '', '2026-02-17', '2026-02-13 23:36:27', '2026-02-14', '2026-02-14', '', 0, '', '', '', '', '', NULL, NULL, '2202502', NULL, 0, NULL, NULL, NULL, 'Pending Disbursement'),
(442, 'DRV-D-20260213-3007', NULL, 'Antonio Mendoza', NULL, 'Core-2', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 2541, 'Driver Payout for Antonio Mendoza (ID: DRV-10007)', NULL, '2026-02-14', '2026-02-13 03:30:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10007', 'WALLET-10007', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(443, 'DRV-D-20260213-3006', NULL, 'Gabriel Flores', NULL, 'Administrative', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 3220, 'Driver Payout for Gabriel Flores (ID: DRV-10006)', NULL, '2026-02-14', '2026-02-13 03:15:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10006', 'WALLET-10006', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(444, 'DRV-D-20260213-3005', NULL, 'Miguel Torres', NULL, 'Logistic-1', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 2976, 'Driver Payout for Miguel Torres (ID: DRV-10005)', NULL, '2026-02-14', '2026-02-13 03:00:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10005', 'WALLET-10005', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(445, 'DRV-D-20260213-3004', NULL, 'Diego Reyes', NULL, 'Human Resource-1', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 3450, 'Driver Payout for Diego Reyes (ID: DRV-10004)', NULL, '2026-02-14', '2026-02-13 02:45:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10004', 'WALLET-10004', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(446, 'DRV-D-20260213-3003', NULL, 'Ramon Cruz', NULL, 'Core-1', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 2680, 'Driver Payout for Ramon Cruz (ID: DRV-10003)', NULL, '2026-02-14', '2026-02-13 02:30:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10003', 'WALLET-10003', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(447, 'DRV-D-20260213-3002', NULL, 'Luis Santos', NULL, 'Logistic-2', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 3126, 'Driver Payout for Luis Santos (ID: DRV-10002)', NULL, '2026-02-14', '2026-02-13 02:15:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10002', 'WALLET-10002', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(448, 'DRV-D-20260213-3001', NULL, 'Marco Alvarez', NULL, 'Logistic-1', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 2850, 'Driver Payout for Marco Alvarez (ID: DRV-10001)', NULL, '2026-02-14', '2026-02-13 02:00:00', '2026-02-13', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-10001', 'WALLET-10001', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(449, 'DRV-D-20260108-6492', NULL, 'Olivia Grace', NULL, 'Human Resource-1', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 2249, 'Driver Payout for Olivia Grace (ID: DRV-37508)', NULL, '2026-02-14', '2026-01-07 16:00:00', '2026-01-08', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-37508', 'WALLET-37508', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(450, 'DRV-D-20251220-8847', NULL, 'Chloe Alexandra', NULL, 'Financials', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 2705, 'Driver Payout for Chloe Alexandra (ID: DRV-29951)', NULL, '2026-02-14', '2025-12-19 16:00:00', '2025-12-20', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-29951', 'WALLET-29951', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement'),
(451, 'DRV-D-20251115-4541', NULL, 'Lucas Matteo', NULL, 'Logistic-1', 'Bank', 'Driver Payout', 'Driver Payout', 'Driver', 'Driver Payable', 3126, 'Driver Payout for Lucas Matteo (ID: DRV-55772)', NULL, '2026-02-14', '2025-11-14 16:00:00', '2025-11-15', '2026-02-14', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'DRV-55772', 'WALLET-55772', 0, 'Driver Module', 'Ethan Magsaysay', NULL, 'Pending Disbursement');

-- --------------------------------------------------------

--
-- Table structure for table `payables_receipts`
--

CREATE TABLE `payables_receipts` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `payment_due` date DEFAULT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `bank_name` varchar(255) DEFAULT '',
  `bank_account_name` varchar(255) DEFAULT '',
  `bank_account_number` varchar(20) DEFAULT '',
  `ecash_provider` varchar(100) DEFAULT '',
  `ecash_account_name` varchar(100) DEFAULT '',
  `ecash_account_number` varchar(20) DEFAULT '',
  `disbursed_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('disbursed','cancelled','reversed') DEFAULT 'disbursed',
  `original_reference_id` varchar(255) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payables_receipts`
--

INSERT INTO `payables_receipts` (`id`, `reference_id`, `account_name`, `requested_department`, `expense_categories`, `mode_of_payment`, `amount`, `description`, `document`, `payment_due`, `requested_at`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `disbursed_date`, `status`, `original_reference_id`, `created_at`) VALUES
(1, 'BNK-1136', 'test', 'Financial', 'Account Payable', 'Bank Transfer', 5, 'Payment for invoice 20250831-1136', '', '2025-08-31', '2026-01-16 20:24:46', 'BDO', 'test', '1234567891011213', '', '', '', '2026-01-16 11:40:19', 'disbursed', '20250831-1136', '2025-10-15 16:15:05'),
(2, 'BNK-1136', 'test', 'Financial', 'Account Payable', 'Bank Transfer', 5, 'Payment for invoice 20250831-1136', '', '2025-08-31', '2026-01-16 20:24:46', 'BDO', 'test', '1234567891011213', '', '', '', '2026-01-16 11:47:27', 'disbursed', '20250831-1136', '2025-10-15 16:14:31'),
(3, 'EC-20251015-7260', 'admin admin', 'Human Resource-1', 'staff development', 'Ecash', 12500, 'seminar on labor compliance', '1760543069_133969447380988142.jpg', '2025-10-15', '2026-01-16 20:24:46', '', '', '', 'gcash', 'juan m.', '09175551123', '2026-01-16 11:48:11', 'disbursed', 'PA-20251015-7260', '2025-10-16 13:57:08'),
(4, 'BNK-20250831-1136', 'test', 'Financial', 'Account Payable', 'Bank Transfer', 5, 'Payment for invoice 20250831-1136', '', '2025-08-31', '2026-01-16 20:24:46', 'BDO', 'test', '1234567891011213', '', '', '', '2026-01-16 11:51:06', 'disbursed', 'PA-20250831-1136', '2025-10-15 16:41:56'),
(234, 'BNK-50831-1136', 'test', 'Financial', 'Account Payable', 'Bank Transfer', 5, 'Payment for invoice 20250831-1136', '', '2025-08-31', '2026-01-16 20:24:46', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-16 11:59:59', 'disbursed', '20250831-1136', '2025-10-15 16:30:55'),
(235, 'BNK-1136', 'test', 'Financial', 'Account Payable', 'Bank Transfer', 5, 'Payment for invoice 20250831-1136', NULL, '2025-08-31', '2026-01-16 20:24:46', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-16 19:48:24', 'disbursed', '', '2025-10-15 16:17:17'),
(236, 'C-20251015-7578', 'admin admin', 'Human Resource-4', 'zoro', 'Cash', 900, 'zoro', NULL, '2025-10-24', '2026-01-16 20:24:46', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-16 19:48:37', 'disbursed', '', '2025-10-15 14:36:27'),
(237, 'C-20250901-7277', 'test', 'Logistic-2', 'test', 'cash', 645, 'test', NULL, '2025-09-01', '2026-01-16 20:24:46', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-16 19:50:28', 'disbursed', '', '2025-10-15 14:38:43'),
(238, 'C-20250904-8893', 'budget manager', 'Financial', 'Account Payable', 'Cash', 5, 'Payment for invoice INV-20250904-8893', NULL, '2025-09-30', '2026-01-16 20:24:46', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-16 19:52:39', 'disbursed', '', '2025-10-15 14:40:13'),
(239, 'C-20250904-8893', 'budget manager', 'Financial', 'Account Payable', 'Cash', 250, 'Payment for invoice INV-20250904-8893', '1756968403_bill.pdf', '2025-09-30', '2026-01-16 20:24:46', '', '', '', '', '', '', '2026-01-16 20:01:58', 'disbursed', 'PA-20250904-8893', '2025-10-15 14:39:35'),
(240, 'C-20250901-2715', 'test', 'Core-2', 'test', 'cash', 30, 'test', '1756736272_bill.pdf', '2025-09-23', '2025-10-15 14:32:28', '', '', '', '', '', '', '2026-01-16 20:33:55', 'disbursed', 'PA-20250901-2715', '2026-01-16 20:33:55'),
(241, 'C-20251015-2558', 'admin admin', 'Human Resource-4', 'usop', 'Cash', 700, 'usop', '', '2025-10-25', '2025-10-15 14:31:43', '', '', '', '', '', '', '2026-01-16 20:34:33', 'disbursed', 'PA-20251015-2558', '2026-01-16 20:34:33'),
(242, 'C-EM-20251015-7721', 'admin admin', 'Human Resource-3', 'mcdo', 'Cash', 1000, 'to eat', '', '2025-11-04', '2025-10-15 14:27:33', '', '', '', '', '', '', '2026-01-16 20:37:45', 'disbursed', 'EM-20251015-7721', '2026-01-16 20:37:45'),
(243, 'C-20251015-2558', 'admin admin', 'Human Resource-4', 'usop', 'Cash', 700, 'usop', '', '2025-10-25', '2025-10-15 14:31:09', '', '', '', '', '', '', '2026-01-16 21:05:39', 'disbursed', 'PA-20251015-2558', '2026-01-16 21:05:39'),
(244, 'C-20251015-4240', 'admin admin', 'Administrative', 'xamp', 'Cash', 500, 'xamp', '', '2025-10-31', '2025-10-15 09:40:44', '', '', '', '', '', '', '2026-01-16 21:06:48', 'disbursed', 'PA-20251015-4240', '2026-01-16 21:06:48'),
(245, 'C-20250903-3553', 'budget manager', 'Financial', 'test', 'Cash', 5000, 'test', '1756878240_bill.pdf', '2025-09-03', '2025-10-15 09:42:33', '', '', '', '', '', '', '2026-01-24 15:22:40', 'disbursed', 'PA-20250903-3553', '2026-01-24 15:22:40'),
(246, 'C-20260124-8255', 'Ethan Magsaysay', 'Human Resource-2', 'ACE', 'Cash', 5200, 'ACE', '', '2026-01-27', '2026-01-24 21:59:27', '', '', '', '', '', '', '2026-01-24 21:59:53', 'disbursed', 'PA-20260124-8255', '2026-01-24 21:59:53'),
(247, 'C-VEN-INV-20251015-8788', 'admin admin', 'Core-1', 'Vendor Payment', 'Cash', 70000, 'Payment for vendor invoice INV-20251015-8788', '', '2025-10-17', '2026-01-28 14:12:00', '', '', '', '', '', '', '2026-01-30 11:22:39', 'disbursed', 'VEN-INV-20251015-8788', '2026-01-30 11:22:39'),
(248, 'C-VEN-INV-20251015-8864', 'admin admin', 'Logistic-2', 'Vendor Payment', 'Cash', 1800, 'Payment for vendor invoice INV-20251015-8864', '', '2025-10-31', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:06:27', 'disbursed', 'VEN-INV-20251015-8864', '2026-01-30 20:06:27'),
(249, 'BNK-993928', 'test 5', 'Administrative', 'Account Payable', 'Bank Transfer', 4000, 'Payment for invoice INV-993928', '', '2025-08-25', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:06:27', 'disbursed', 'PA-993928', '2026-01-30 20:06:27'),
(250, 'BNK-993927', 'test 5', 'Administrative', 'Account Payable', 'Bank Transfer', 4000, 'Payment for invoice INV-993927', '', '2025-08-25', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-993927', '2026-01-30 20:07:25'),
(251, 'C-638595', 'log1', 'Logistic-1', 'Account Payable', 'Cash', 7500, 'Payment for invoice INV-638595', '', '2025-08-27', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-638595', '2026-01-30 20:07:25'),
(252, 'BNK-638674', 'admin 2', 'Administrative', 'Account Payable', 'Bank Transfer', 20000, 'Payment for invoice INV-638674', '1755774619_bill.pdf', '2025-08-30', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-638674', '2026-01-30 20:07:25'),
(253, 'BNK-123492', 'lily chan', 'Financial', 'Account Payable', 'Bank Transfer', 2000, 'Payment for invoice INV-123492', '1756458402_bill.pdf', '2025-09-22', '2026-01-30 19:00:49', 'BDO', 'lily chan', '1234567891011213', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-123492', '2026-01-30 20:07:25'),
(254, 'BNK-123493', 'zoro', 'Financial', 'Account Payable', 'Bank Transfer', 4000, 'Payment for invoice INV-123493', '1756466693_bill.pdf', '2025-09-24', '2026-01-30 19:00:49', 'AUB', 'zoro', '1234567891011213', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-123493', '2026-01-30 20:07:25'),
(255, 'EC-123487', 'nami', 'Logistic-1', 'Account Payable', 'Ecash', 2345, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-123487', '2026-01-30 20:07:25'),
(256, 'EC-123487', 'nami', 'Logistic-1', 'Account Payable', 'Ecash', 30000, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-123487', '2026-01-30 20:07:25'),
(257, 'EC-123487', 'nami', 'Logistic-1', 'Account Payable', 'Ecash', 56000, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-123487', '2026-01-30 20:07:25'),
(258, 'EC-123487', 'nami', 'Logistic-1', 'Account Payable', 'Ecash', 235, 'Payment for invoice INV-123487', '', '2025-09-05', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-30 20:07:25', 'disbursed', 'PA-123487', '2026-01-30 20:07:25'),
(259, 'C-undefined20250904-8689', 'budget manager', 'Financial', 'Account Payable', 'Cash', 650, 'Payment for invoice undefined20250904-8689', '1756989937_bill.pdf', '2025-09-23', '2026-01-31 17:18:53', '', '', '', '', '', '', '2026-01-31 17:24:12', 'disbursed', 'undefined20250904-8689', '2026-01-31 17:24:12'),
(260, 'EC-VEN-INV-20251015-9039', 'admin admin', 'Human Resource-3', 'Vendor Payment', 'Ecash', 70000, 'Payment for vendor invoice INV-20251015-9039', '1760528413_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '2025-11-18', '2026-01-31 17:24:46', '', '', '', 'test run', 'test run', '22444', '2026-01-31 17:28:34', 'disbursed', 'VEN-INV-20251015-9039', '2026-01-31 17:28:34'),
(261, 'C-INV-20251015-8654', 'admin admin', 'Human Resource-1', 'Vendor Payment', 'Cash', 8000, 'Payment for vendor invoice INV-20251015-8654', '', '2025-10-15', '2026-01-30 19:53:27', '', '', '', '', '', '', '2026-01-31 17:35:59', 'disbursed', 'VEN-INV-20251015-8654', '2026-01-31 17:35:59'),
(262, 'C-INV-20251015-9694', 'admin admin', 'Administrative', 'Vendor Payment', 'Cash', 32000, 'Payment for vendor invoice INV-20251015-9694', '', '2025-10-16', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-31 17:35:59', 'disbursed', 'VEN-INV-20251015-9694', '2026-01-31 17:35:59'),
(263, 'PA-123496', 'brook', 'Financial', 'Account Payable', 'Ecash', 12345, 'Payment for invoice INV-123496', '1756621394_bill.pdf', '2025-09-30', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-31 17:39:37', 'disbursed', 'PA-123496', '2026-01-31 17:39:37'),
(264, 'PA-INV-20250831-5297', 'test', 'Administrative', 'Account Payable', 'Bank Transfer', 20, 'Payment for invoice INV-INV-20250831-5297', '1756629898_bill.pdf', '2025-09-01', '2026-01-30 19:00:49', 'AUB', 'test admin', '1234567891011213', '', '', '', '2026-01-31 17:40:03', 'disbursed', 'PA-INV-20250831-5297', '2026-01-31 17:40:03'),
(265, 'PA-123496', 'brook', 'Financial', 'Account Payable', 'Ecash', 7655, 'Payment for invoice INV-123496', '1756621394_bill.pdf', '2025-09-30', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-31 19:04:33', 'disbursed', 'PA-123496', '2026-01-31 19:04:33'),
(266, 'PA-123498', 'jinbei', 'Financial', 'Account Payable', 'Ecash', 3000, 'Payment for invoice INV-123498', '1756622988_bill.pdf', '2025-09-09', '2026-01-30 19:00:49', '', '', '', '', '', '', '2026-01-31 19:04:33', 'disbursed', 'PA-123498', '2026-01-31 19:04:33'),
(267, 'VEN-INV-20260208-7876', 'TechFix IT Solutions', 'Human Resource-3', 'Vendor Payment', 'Bank Transfer', 8000, '0', '[\"1770530701_invoice-VHL-20260201-7327.pdf\"]', '2026-03-02', '2026-02-08 17:59:24', 'BDO', 'TechFix', '246532102130', '', '', '', '2026-02-13 12:28:38', 'disbursed', 'VEN-INV-20260208-7876', '2026-02-13 12:28:38'),
(268, 'VEN-INV-20260208-7876', 'TechFix IT Solutions', 'Human Resource-3', 'Vendor Payment', 'Bank Transfer', 8000, '0', '[\"1770530701_invoice-VHL-20260201-7327.pdf\"]', '2026-03-02', '2026-02-08 17:41:41', 'BDO', 'TechFix', '246532102130', '', '', '', '2026-02-13 12:28:49', 'disbursed', 'VEN-INV-20260208-7876', '2026-02-13 12:28:49'),
(269, 'DRV-D-20260213-3010', 'Alberto Garcia', 'Logistic-1', 'Driver Payout', 'Bank', 3341, 'Driver Payout for Alberto Garcia (ID: DRV-10010)', '', '2026-02-14', '2026-02-13 12:15:00', '', '', '', '', '', '', '2026-02-14 14:04:00', 'disbursed', 'DRV-D-20260213-3010', '2026-02-14 14:04:00'),
(270, 'VEN-INV-20260214-5002', 'PLDT Fibr Business', 'Core-1', 'Vendor Payment', 'Bank Transfer', 8900, 'Payment for vendor invoice INV-20260214-5002', 'invoice_5002.pdf', '2026-02-28', '2026-02-14 15:19:55', 'Metrobank', 'PLDT Corporation', '1357924680', '', '', '', '2026-02-14 15:50:37', 'disbursed', 'VEN-INV-20260214-5002', '2026-02-14 15:50:37'),
(271, 'VEN-INV-20260214-5001', 'CleanPro Services Inc.', 'Administrative', 'Vendor Payment', 'Bank Transfer', 11500, 'Payment for vendor invoice INV-20260214-5001', 'invoice_5001.pdf', '2026-02-28', '2026-02-14 15:18:37', 'BDO', 'CleanPro Services', '9876543210', '', '', '', '2026-02-14 15:58:34', 'disbursed', 'VEN-INV-20260214-5001', '2026-02-14 15:58:34');

-- --------------------------------------------------------

--
-- Table structure for table `payment_schedule`
--

CREATE TABLE `payment_schedule` (
  `id` int(11) NOT NULL,
  `payout_id` varchar(50) NOT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH') NOT NULL,
  `auto_approved` tinyint(1) DEFAULT 0,
  `requires_review` tinyint(1) DEFAULT 1,
  `status` varchar(100) DEFAULT 'Pending',
  `priority` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_schedule`
--

INSERT INTO `payment_schedule` (`id`, `payout_id`, `scheduled_date`, `risk_level`, `auto_approved`, `requires_review`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 'PO-2026-TEST-001', '2026-02-02 20:24:08', 'LOW', 1, 0, 'Scheduled', 'Normal', '2026-02-01 12:24:08', '2026-02-01 12:24:08'),
(2, 'PO-2026-TEST-001', '2026-02-06 13:20:08', 'LOW', 1, 0, 'Scheduled', 'Normal', '2026-02-05 05:20:08', '2026-02-05 05:20:08');

-- --------------------------------------------------------

--
-- Table structure for table `payout_tracking`
--

CREATE TABLE `payout_tracking` (
  `id` int(11) NOT NULL,
  `payout_id` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payout_tracking`
--

INSERT INTO `payout_tracking` (`id`, `payout_id`, `action`, `user_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 'PO-2026-TEST-001', 'AI_VALIDATED', 1, 'Risk: LOW (Score: 15/100)', '127.0.0.1', '2026-02-01 12:24:08'),
(2, 'PO-2026-TEST-001', 'AI_VALIDATED', 1, 'Risk: LOW (Score: 15/100)', '127.0.0.1', '2026-02-05 05:20:08');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `scheduled_days` int(11) DEFAULT 0,
  `days_present` int(11) DEFAULT 0,
  `regular_hours` decimal(6,2) DEFAULT 0.00,
  `overtime_hours` decimal(6,2) DEFAULT 0.00,
  `absent_days` int(11) DEFAULT 0,
  `working_holidays` int(11) DEFAULT 0,
  `pto_days` int(11) DEFAULT 0,
  `base_salary` decimal(12,2) DEFAULT 0.00,
  `gross_salary` decimal(15,2) DEFAULT 0.00,
  `regular_pay` decimal(15,2) DEFAULT 0.00,
  `overtime_pay` decimal(15,2) DEFAULT 0.00,
  `holiday_pay` decimal(15,2) DEFAULT 0.00,
  `allowances` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `sss_amount` decimal(10,2) DEFAULT 0.00,
  `philhealth_amount` decimal(10,2) DEFAULT 0.00,
  `pagibig_amount` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_date` datetime DEFAULT NULL,
  `rejected_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`id`, `employee_id`, `pay_period_start`, `pay_period_end`, `scheduled_days`, `days_present`, `regular_hours`, `overtime_hours`, `absent_days`, `working_holidays`, `pto_days`, `base_salary`, `gross_salary`, `regular_pay`, `overtime_pay`, `holiday_pay`, `allowances`, `net_salary`, `tax_amount`, `sss_amount`, `philhealth_amount`, `pagibig_amount`, `other_deductions`, `status`, `approved_date`, `rejected_date`, `rejection_reason`, `created_at`) VALUES
(1, '1202501', '2025-11-01', '2025-11-15', 10, 10, 80.00, 3.00, 0, 1, 0, 900000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 33742.49, 1844.34, 800.00, 400.00, 100.00, 0.00, 'approved', '2026-02-13 21:39:31', NULL, NULL, '2025-11-14 16:00:00'),
(2, '1202502', '2025-12-16', '2025-12-31', 10, 10, 80.00, 4.00, 0, 0, 0, 550000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 20051.91, 1123.79, 800.00, 400.00, 100.00, 0.00, 'approved', '2026-02-12 09:53:40', NULL, NULL, '2025-12-30 16:00:00'),
(3, '1202503', '2025-12-01', '2025-12-15', 10, 10, 80.00, 3.00, 0, 1, 0, 600000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 22061.66, 1229.56, 800.00, 400.00, 100.00, 0.00, 'approved', '2026-02-12 09:21:56', NULL, NULL, '2025-12-14 16:00:00'),
(4, '2202501', '2025-11-01', '2025-11-15', 10, 10, 80.00, 5.00, 0, 0, 0, 750000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 28245.03, 1555.00, 800.00, 400.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2025-11-14 16:00:00'),
(5, '2202502', '2025-11-01', '2025-11-15', 10, 10, 80.00, 8.00, 0, 1, 0, 500000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 19595.03, 1099.74, 800.00, 400.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2025-11-14 16:00:00'),
(6, '2202503', '2025-11-16', '2025-11-30', 10, 10, 80.00, 3.00, 0, 0, 0, 600000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 21650.60, 1207.93, 800.00, 400.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2025-11-29 16:00:00'),
(7, '3202501', '2025-11-01', '2025-11-15', 10, 10, 80.00, 3.00, 0, 0, 0, 700000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25475.97, 1409.26, 800.00, 400.00, 100.00, 0.00, 'approved', '2026-02-13 21:12:56', NULL, NULL, '2025-11-14 16:00:00'),
(8, '4202501', '2025-11-01', '2025-11-15', 10, 10, 80.00, 2.00, 0, 0, 0, 800000.00, 24750.02, 0.00, 0.00, 0.00, 0.00, 30000.00, 1237.50, 800.00, 400.00, 100.00, 0.00, 'rejected', NULL, '2026-02-12 13:43:05', 'insufficient budget', '2025-11-14 16:00:00'),
(60, 'EMP-OPS-001', '2026-02-01', '2026-02-15', 10, 10, 80.00, 2.00, 0, 0, 0, 600000.00, 25375.00, 25000.00, 375.00, 0.00, 0.00, 21875.00, 2000.00, 1125.00, 375.00, 100.00, 0.00, 'approved', '2026-02-14 15:35:55', NULL, NULL, '2026-02-13 16:35:04'),
(61, 'EMP-OPS-002', '2026-02-01', '2026-02-15', 10, 10, 80.00, 0.00, 0, 0, 0, 650000.00, 27083.00, 27083.00, 0.00, 0.00, 0.00, 23583.00, 2100.00, 1200.00, 400.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2026-02-13 16:35:04'),
(62, 'EMP-MGT-003', '2026-02-01', '2026-02-15', 10, 10, 80.00, 5.00, 0, 0, 0, 850000.00, 36562.00, 35416.00, 1146.00, 0.00, 0.00, 28062.00, 6000.00, 1350.00, 550.00, 100.00, 0.00, 'approved', '2026-02-14 15:22:47', NULL, NULL, '2026-02-13 16:35:04'),
(63, 'EMP-LOG-004', '2026-02-01', '2026-02-15', 10, 10, 80.00, 3.50, 0, 0, 0, 700000.00, 29800.00, 29166.00, 634.00, 0.00, 0.00, 25800.00, 2500.00, 1300.00, 450.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2026-02-13 16:35:04'),
(64, 'EMP-LOG-005', '2026-02-01', '2026-02-15', 10, 10, 80.00, 1.00, 0, 0, 0, 600000.00, 25187.00, 25000.00, 187.00, 0.00, 0.00, 21687.00, 2000.00, 1125.00, 375.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2026-02-13 16:35:04'),
(65, 'EMP-HR-006', '2026-02-01', '2026-02-15', 10, 10, 80.00, 0.00, 0, 0, 0, 750000.00, 31250.00, 31250.00, 0.00, 0.00, 0.00, 27150.00, 2800.00, 1350.00, 500.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2026-02-13 16:35:04'),
(66, 'EMP-IT-007', '2026-02-01', '2026-02-15', 10, 10, 80.00, 4.00, 0, 0, 0, 620000.00, 26583.00, 25833.00, 750.00, 0.00, 0.00, 22883.00, 2200.00, 1150.00, 350.00, 100.00, 0.00, 'approved', '2026-02-14 15:29:20', NULL, NULL, '2026-02-13 16:35:04'),
(67, 'EMP-IT-008', '2026-02-01', '2026-02-15', 10, 10, 80.00, 2.50, 0, 0, 0, 680000.00, 28833.00, 28333.00, 500.00, 0.00, 0.00, 24833.00, 2500.00, 1250.00, 450.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2026-02-13 16:35:04'),
(68, 'EMP-FIN-009', '2026-02-01', '2026-02-15', 10, 10, 80.00, 1.50, 0, 0, 0, 800000.00, 33650.00, 33333.00, 317.00, 0.00, 0.00, 28150.00, 4000.00, 1350.00, 500.00, 100.00, 0.00, 'approved', '2026-02-14 15:36:27', NULL, NULL, '2026-02-13 16:35:04'),
(69, 'EMP-ADM-010', '2026-02-01', '2026-02-15', 10, 10, 80.00, 0.00, 0, 0, 0, 600000.00, 25000.00, 25000.00, 0.00, 0.00, 0.00, 21500.00, 2000.00, 1125.00, 375.00, 100.00, 0.00, 'approved', '2026-02-14 12:50:26', NULL, NULL, '2026-02-13 16:35:04'),
(70, 'EMP-B2-001', '2026-03-01', '2026-03-15', 11, 11, 88.00, 2.00, 0, 0, 0, 35000.00, 18500.00, 17500.00, 1000.00, 0.00, 0.00, 16500.00, 1000.00, 585.00, 315.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(71, 'EMP-B2-002', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 28000.00, 14000.00, 14000.00, 0.00, 0.00, 0.00, 12800.00, 500.00, 450.00, 250.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(72, 'EMP-B2-003', '2026-03-01', '2026-03-15', 11, 11, 88.00, 5.00, 0, 0, 0, 32000.00, 17500.00, 16000.00, 1500.00, 0.00, 0.00, 15300.00, 1200.00, 600.00, 300.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(73, 'EMP-B2-004', '2026-03-01', '2026-03-15', 11, 10, 80.00, 0.00, 0, 0, 0, 30000.00, 13636.00, 13636.00, 0.00, 0.00, 0.00, 12500.00, 600.00, 386.00, 150.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(74, 'EMP-B2-005', '2026-03-01', '2026-03-15', 11, 11, 88.00, 10.00, 0, 0, 0, 45000.00, 25000.00, 22500.00, 2500.00, 0.00, 0.00, 22000.00, 2000.00, 700.00, 300.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(75, 'EMP-B2-006', '2026-03-01', '2026-03-15', 11, 11, 88.00, 1.00, 0, 0, 0, 25000.00, 12700.00, 12500.00, 200.00, 0.00, 0.00, 11500.00, 400.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(76, 'EMP-B2-007', '2026-03-01', '2026-03-15', 11, 9, 72.00, 0.00, 0, 0, 0, 22000.00, 9000.00, 9000.00, 0.00, 0.00, 0.00, 8200.00, 200.00, 400.00, 150.00, 50.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(77, 'EMP-B2-008', '2026-03-01', '2026-03-15', 11, 11, 88.00, 3.00, 0, 0, 0, 27000.00, 14200.00, 13500.00, 700.00, 0.00, 0.00, 13000.00, 500.00, 450.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(78, 'EMP-B2-009', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 50000.00, 25000.00, 25000.00, 0.00, 0.00, 0.00, 21500.00, 2500.00, 700.00, 300.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(79, 'EMP-B2-010', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 20000.00, 10000.00, 10000.00, 0.00, 0.00, 0.00, 9200.00, 200.00, 400.00, 150.00, 50.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(80, 'EMP-B2-011', '2026-03-01', '2026-03-15', 11, 11, 88.00, 4.00, 0, 0, 0, 38000.00, 20200.00, 19000.00, 1200.00, 0.00, 0.00, 18000.00, 1500.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(81, 'EMP-B2-012', '2026-03-01', '2026-03-15', 11, 11, 88.00, 2.00, 0, 0, 0, 18000.00, 9300.00, 9000.00, 300.00, 0.00, 0.00, 8600.00, 100.00, 400.00, 150.00, 50.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(82, 'EMP-B2-013', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 24000.00, 12000.00, 12000.00, 0.00, 0.00, 0.00, 11000.00, 400.00, 400.00, 150.00, 50.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(83, 'EMP-B2-014', '2026-03-01', '2026-03-15', 11, 11, 88.00, 5.00, 0, 0, 0, 40000.00, 21500.00, 20000.00, 1500.00, 0.00, 0.00, 19000.00, 1800.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(84, 'EMP-B2-015', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 55000.00, 27500.00, 27500.00, 0.00, 0.00, 0.00, 24000.00, 2500.00, 700.00, 300.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(85, 'EMP-B2-016', '2026-03-01', '2026-03-15', 11, 11, 88.00, 2.00, 0, 0, 0, 42000.00, 21700.00, 21000.00, 700.00, 0.00, 0.00, 19500.00, 1500.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(86, 'EMP-B2-017', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 30000.00, 15000.00, 15000.00, 0.00, 0.00, 0.00, 13800.00, 600.00, 400.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(87, 'EMP-B2-018', '2026-03-01', '2026-03-15', 11, 11, 88.00, 6.00, 0, 0, 0, 48000.00, 25800.00, 24000.00, 1800.00, 0.00, 0.00, 22500.00, 2500.00, 600.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(88, 'EMP-B2-019', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 60000.00, 30000.00, 30000.00, 0.00, 0.00, 0.00, 26000.00, 3000.00, 700.00, 300.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(89, 'EMP-B2-020', '2026-03-01', '2026-03-15', 11, 11, 88.00, 1.00, 0, 0, 0, 33000.00, 16800.00, 16500.00, 300.00, 0.00, 0.00, 15500.00, 700.00, 400.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(90, 'EMP-B2-021', '2026-03-01', '2026-03-15', 11, 11, 88.00, 8.00, 0, 0, 0, 26000.00, 14600.00, 13000.00, 1600.00, 0.00, 0.00, 13000.00, 500.00, 400.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(91, 'EMP-B2-022', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 36000.00, 18000.00, 18000.00, 0.00, 0.00, 0.00, 16500.00, 800.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(92, 'EMP-B2-023', '2026-03-01', '2026-03-15', 11, 11, 88.00, 2.00, 0, 0, 0, 21000.00, 10900.00, 10500.00, 400.00, 0.00, 0.00, 10000.00, 300.00, 400.00, 150.00, 50.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(93, 'EMP-B2-024', '2026-03-01', '2026-03-15', 11, 11, 88.00, 10.00, 0, 0, 0, 19000.00, 11000.00, 9500.00, 1500.00, 0.00, 0.00, 10000.00, 200.00, 400.00, 150.00, 50.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(94, 'EMP-B2-025', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 29000.00, 14500.00, 14500.00, 0.00, 0.00, 0.00, 13000.00, 600.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(95, 'EMP-B2-026', '2026-03-01', '2026-03-15', 11, 11, 88.00, 5.00, 0, 0, 0, 46000.00, 24500.00, 23000.00, 1500.00, 0.00, 0.00, 21500.00, 2000.00, 600.00, 300.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(96, 'EMP-B2-027', '2026-03-01', '2026-03-15', 11, 11, 88.00, 3.00, 0, 0, 0, 23000.00, 12100.00, 11500.00, 600.00, 0.00, 0.00, 11000.00, 400.00, 400.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(97, 'EMP-B2-028', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 34000.00, 17000.00, 17000.00, 0.00, 0.00, 0.00, 15500.00, 800.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(98, 'EMP-B2-029', '2026-03-01', '2026-03-15', 11, 11, 88.00, 1.00, 0, 0, 0, 39000.00, 19800.00, 19500.00, 300.00, 0.00, 0.00, 18000.00, 1200.00, 500.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25'),
(99, 'EMP-B2-030', '2026-03-01', '2026-03-15', 11, 11, 88.00, 0.00, 0, 0, 0, 22000.00, 11000.00, 11000.00, 0.00, 0.00, 0.00, 10000.00, 300.00, 400.00, 200.00, 100.00, 0.00, 'pending', NULL, NULL, NULL, '2026-02-14 07:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `pettycash`
--

CREATE TABLE `pettycash` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` varchar(255) NOT NULL,
  `time_period` varchar(20) NOT NULL,
  `payment_due` date NOT NULL,
  `bank_name` varchar(40) NOT NULL,
  `bank_account_name` varchar(255) NOT NULL,
  `bank_account_number` varchar(25) NOT NULL,
  `ecash_provider` varchar(100) NOT NULL,
  `ecash_account_name` varchar(100) NOT NULL,
  `ecash_account_number` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `pettycash`
--

INSERT INTO `pettycash` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `time_period`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `created_at`) VALUES
(3, 'PC-20250901-4866', 'Juan Garcia', 'Human Resource-2', 'cash', 'Office Supplies', 1500, 'Bond papers, pens, and stationery', '1756733968_bill.pdf', '', '2025-09-24', '', '', '', '', '', '', '2025-09-01 15:39:28'),
(4, 'PC-20250901-3810', 'Juan Garcia', 'Human Resource-2', 'ecash', 'Transportation', 800, 'Grab fare for document delivery', '1756734949_bill.pdf', '', '2025-09-01', '', '', '', 'test', 'test', '12345678910', '2025-09-01 15:55:49'),
(8, 'PC-20250901-3032', 'Ethan Magsaysay', 'Financial', 'cash', 'Meals', 1200, 'Client meeting lunch', '1756737430_bill.pdf', '', '2025-09-01', '', '', '', '', '', '', '2025-09-01 16:37:10'),
(9, 'PC-20250901-2659', 'Elena Ramos', 'Core-2', 'cash', 'Postage', 450, 'Mailing important documents', '1756737547_bill.pdf', '', '2025-09-01', '', '', '', '', '', '', '2025-09-01 16:39:07'),
(10, 'PC-20250901-8586', 'Elena Ramos', 'Core-2', 'cash', 'Printing', 650, 'Photocopying reports', '1756737563_bill.pdf', '', '2025-09-16', '', '', '', '', '', '', '2025-09-01 16:39:23'),
(11, 'PC-20250902-4586', 'Ethan Magsaysay', 'Financial', 'bank', 'Office Supplies', 850, 'Toner and printer ink', '1756793759_bill.pdf', '', '2025-09-02', 'test', 'test', '12345678910', '', '', '', '2025-09-02 08:15:59'),
(12, 'PC-20250903-3798', 'Ethan Magsaysay', 'Financial', 'Ecash', 'Transportation', 500, 'Taxi fare for bank transaction', '1756877342_bill.pdf', '', '2025-09-03', '', '', '', 'test', 'test', '12345678910', '2025-09-03 07:29:02'),
(15, 'PC-20251015-1559', 'Miguel Reyes', 'Core-1', 'Cash', 'Emergency', 2000, 'Emergency office repair', '', '', '2025-10-30', '', '', '', '', '', '', '2025-10-15 05:49:24'),
(16, 'PC-20251015-1259', 'Luis Mendoza', 'Logistic-1', 'Cash', 'Maintenance', 3500, 'Vehicle minor repair', '', '', '2025-10-29', '', '', '', '', '', '', '2025-10-15 06:26:23'),
(17, 'PC-20251015-4697', 'Juan Garcia', 'Human Resource-2', 'Cash', 'Documentation', 600, 'Notarization of documents', '', '', '2025-10-15', '', '', '', '', '', '', '2025-10-15 11:08:37'),
(18, 'PC-20251015-4579', 'Ana Cruz', 'Human Resource-3', 'Cash', 'Books', 2500, 'Training materials and books', '', '', '2025-10-21', '', '', '', '', '', '', '2025-10-15 11:14:16'),
(19, 'PC-20251015-7240', 'Ana Cruz', 'Human Resource-3', 'Cash', 'ror', 3000, 'ror', '1760526905_download4.jpg', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 11:15:05'),
(20, 'PC-20251015-6452', 'Luis Mendoza', 'Logistic-1', 'Bank Transfer', 'car expenses', 1019, 'kotse nga', '1760527064_0_020.jpg', '', '2025-10-17', 'ewqeqwe', 'ewqeqwee', '2321312323', '', '', '', '2025-10-15 11:17:44'),
(21, 'PC-20251015-8025', 'Carmen Rivera', 'Logistic-2', 'Cash', 'parcels', 1468, 'qeqeee', '1760527179_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '', '2025-10-13', '', '', '', '', '', '', '2025-10-15 11:19:39'),
(22, 'PC-20251015-1214', 'Sofia Dela Cruz', 'Administrative', 'Cash', 'keys', 1786, 'boom', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 11:20:52'),
(23, 'PC-20251015-2420', 'Pedro Reyes', 'Human Resource-4', 'Cash', 'tao', 2025, 'tao ulit', '', '', '2025-10-13', '', '', '', '', '', '', '2025-10-15 11:21:18'),
(24, 'PC-20251015-5589', 'Juan Garcia', 'Human Resource-2', 'Cash', 'thing', 2267, 'thing ulit', '', '', '2025-10-15', '', '', '', '', '', '', '2025-10-15 11:22:06'),
(25, 'PC-20251015-5324', 'Ana Cruz', 'Human Resource-3', 'Cash', 'documents', 759, 'documents for office', '', '', '2025-10-28', '', '', '', '', '', '', '2025-10-15 11:22:43'),
(26, 'PC-20251015-2487', 'Juan Garcia', 'Human Resource-2', 'Ecash', 'test', 2497, 'test', '1760527472_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '', '2025-10-23', '', '', '', 'ewewe', 'ewewe', '2323233', '2025-10-15 11:24:32'),
(27, 'PC-20251015-2271', 'Miguel Reyes', 'Core-1', 'Cash', 'test', 1707, 'test', '', '', '2025-10-19', '', '', '', '', '', '', '2025-10-15 11:24:57'),
(28, 'PC-20251015-4060', 'Maria Santos', 'Human Resource-1', 'Cash', 'test', 546, 'test', '', '', '2025-08-07', '', '', '', '', '', '', '2025-10-15 11:25:20'),
(29, 'PC-20251015-8409', 'Pedro Reyes', 'Human Resource-4', 'Bank Transfer', 'testing', 1110, 'testing', '1760529066_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '', '2025-10-24', 'testing', 'testing', '388775', '', '', '', '2025-10-15 11:51:06'),
(30, 'PC-20251015-8450', 'Sofia Dela Cruz', 'Administrative', 'Cash', 'office supplies', 500, 'small stationery items', '', '', '2025-10-24', '', '', '', '', '', '', '2025-10-15 12:18:42'),
(31, 'PC-20251015-1914', 'Miguel Reyes', 'Core-1', 'Cash', 'vehicle cleaning suuplies', 780, 'small detailing items', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:20:23'),
(32, 'PC-20251015-4838', 'Elena Ramos', 'Core-2', 'Cash', 'Postage', 4000, 'Mailing documents', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:23:27'),
(33, 'PC-20251015-1207', 'Maria Santos', 'Human Resource-1', 'Cash', 'Job posting Fees', 3500, 'posting job openings', '', '', '2025-08-20', '', '', '', '', '', '', '2025-10-15 12:25:56'),
(34, 'PC-20251015-7726', 'Juan Garcia', 'Human Resource-2', 'Cash', 'printing cost', 450, 'printing resumes', '', '', '2025-08-08', '', '', '', '', '', '', '2025-10-15 12:26:50'),
(35, 'PC-20251015-4094', 'Ana Cruz', 'Human Resource-3', 'Cash', 'training materials', 1410, 'workbooks', '', '', '2025-04-17', '', '', '', '', '', '', '2025-10-15 12:27:51'),
(36, 'PC-20251015-5231', 'Pedro Reyes', 'Human Resource-4', 'Cash', 'refreshments', 200, 'training sessions', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:28:54'),
(37, 'PC-20251015-7524', 'Pedro Reyes', 'Human Resource-4', 'Cash', 'postage & delivery', 1000, 'documents to employees', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:35:17'),
(39, 'PC-20251015-7562', 'Ethan Magsaysay', 'Financial', 'Cash', 'small transactions fees', 2000, 'minor bank charges', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 13:34:09'),
(40, 'PC-20251015-1333', 'Miguel Reyes', 'Core-1', 'Cash', 'project support', 650, 'printing project report', '', '', '2025-10-17', '', '', '', '', '', '', '2025-10-15 13:35:38'),
(41, 'PC-20251015-6489', 'Ethan Magsaysay', 'Financial', 'Cash', 'Currency Exchange ', 700, 'amounts of currency', '', '', '2025-04-11', '', '', '', '', '', '', '2025-10-15 13:35:43'),
(42, 'PC-20251015-5162', 'Carmen Rivera', 'Logistic-2', 'Ecash', 'Tolls and parking', 300, 'parking fees & toll roads', '1760540148_13565c057ba8a7d3eee83bc702c87318.jpg', '', '2025-10-24', '', '', '', 'magsaysay', 'magsaysay', '1234242', '2025-10-15 14:55:48'),
(46, 'PC-20251015-4290', 'Juan Garcia', 'Human Resource-2', 'Cash', 'supplies', 400, 'bond paper purchase', '1760544130_133981516078589726.jpg', '', '2025-10-16', '', '', '', '', '', '', '2025-10-15 16:02:10'),
(47, 'PC-20251015-1644', 'Ana Cruz', 'Human Resource-3', 'Ecash', 'miscellaneous ', 600, 'emergency lunch for staff ', '', '', '2025-10-16', '', '', '', 'gcash', 'vina g.', '7389-9558-9043', '2025-10-15 16:04:57'),
(48, 'PC-20251015-1626', 'Luis Mendoza', 'Logistic-1', 'Cash', 'minor repair', 250, 'replace busted bulb', '1760544427_133993146453853632.jpg', '', '2025-10-16', '', '', '', '', '', '', '2025-10-15 16:07:07'),
(49, 'PC-20251015-5831', 'Elena Ramos', 'Core-2', 'Bank Transfer', 'transportation', 300, 'grab fare reimbursement', '1760544668_133988447163784439.jpg', '', '2025-10-16', 'bdo ', 'anna d.', '3454-8779-6745', '', '', '', '2025-10-15 16:11:08'),
(50, 'PC-20251015-2325', 'Carmen Rivera', 'Logistic-2', 'Cash', 'fuel', 1000, ' diesel top-up for delivery van', '1760544921_133988447163784439.jpg', '', '2025-10-16', '', '', '', '', '', '', '2025-10-15 16:15:21'),
(3, 'PC-20250901-4866', 'Juan Garcia', 'Human Resource-2', 'cash', 'Office Supplies', 1500, 'Bond papers, pens, and stationery', '1756733968_bill.pdf', '', '2025-09-24', '', '', '', '', '', '', '2025-09-01 15:39:28'),
(4, 'PC-20250901-3810', 'Juan Garcia', 'Human Resource-2', 'ecash', 'Transportation', 800, 'Grab fare for document delivery', '1756734949_bill.pdf', '', '2025-09-01', '', '', '', 'test', 'test', '12345678910', '2025-09-01 15:55:49'),
(8, 'PC-20250901-3032', 'Ethan Magsaysay', 'Financial', 'cash', 'Meals', 1200, 'Client meeting lunch', '1756737430_bill.pdf', '', '2025-09-01', '', '', '', '', '', '', '2025-09-01 16:37:10'),
(9, 'PC-20250901-2659', 'Elena Ramos', 'Core-2', 'cash', 'Postage', 450, 'Mailing important documents', '1756737547_bill.pdf', '', '2025-09-01', '', '', '', '', '', '', '2025-09-01 16:39:07'),
(10, 'PC-20250901-8586', 'Elena Ramos', 'Core-2', 'cash', 'Printing', 650, 'Photocopying reports', '1756737563_bill.pdf', '', '2025-09-16', '', '', '', '', '', '', '2025-09-01 16:39:23'),
(11, 'PC-20250902-4586', 'Ethan Magsaysay', 'Financial', 'bank', 'Office Supplies', 850, 'Toner and printer ink', '1756793759_bill.pdf', '', '2025-09-02', 'test', 'test', '12345678910', '', '', '', '2025-09-02 08:15:59'),
(12, 'PC-20250903-3798', 'Ethan Magsaysay', 'Financial', 'Ecash', 'Transportation', 500, 'Taxi fare for bank transaction', '1756877342_bill.pdf', '', '2025-09-03', '', '', '', 'test', 'test', '12345678910', '2025-09-03 07:29:02'),
(15, 'PC-20251015-1559', 'Miguel Reyes', 'Core-1', 'Cash', 'Emergency', 2000, 'Emergency office repair', '', '', '2025-10-30', '', '', '', '', '', '', '2025-10-15 05:49:24'),
(16, 'PC-20251015-1259', 'Luis Mendoza', 'Logistic-1', 'Cash', 'Maintenance', 3500, 'Vehicle minor repair', '', '', '2025-10-29', '', '', '', '', '', '', '2025-10-15 06:26:23'),
(17, 'PC-20251015-4697', 'Juan Garcia', 'Human Resource-2', 'Cash', 'Documentation', 600, 'Notarization of documents', '', '', '2025-10-15', '', '', '', '', '', '', '2025-10-15 11:08:37'),
(18, 'PC-20251015-4579', 'Ana Cruz', 'Human Resource-3', 'Cash', 'Books', 2500, 'Training materials and books', '', '', '2025-10-21', '', '', '', '', '', '', '2025-10-15 11:14:16'),
(19, 'PC-20251015-7240', 'Ana Cruz', 'Human Resource-3', 'Cash', 'ror', 3000, 'ror', '1760526905_download4.jpg', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 11:15:05'),
(20, 'PC-20251015-6452', 'Luis Mendoza', 'Logistic-1', 'Bank Transfer', 'car expenses', 1019, 'kotse nga', '1760527064_0_020.jpg', '', '2025-10-17', 'ewqeqwe', 'ewqeqwee', '2321312323', '', '', '', '2025-10-15 11:17:44'),
(21, 'PC-20251015-8025', 'Carmen Rivera', 'Logistic-2', 'Cash', 'parcels', 1468, 'qeqeee', '1760527179_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '', '2025-10-13', '', '', '', '', '', '', '2025-10-15 11:19:39'),
(22, 'PC-20251015-1214', 'Sofia Dela Cruz', 'Administrative', 'Cash', 'keys', 1786, 'boom', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 11:20:52'),
(23, 'PC-20251015-2420', 'Pedro Reyes', 'Human Resource-4', 'Cash', 'tao', 2025, 'tao ulit', '', '', '2025-10-13', '', '', '', '', '', '', '2025-10-15 11:21:18'),
(24, 'PC-20251015-5589', 'Juan Garcia', 'Human Resource-2', 'Cash', 'thing', 2267, 'thing ulit', '', '', '2025-10-15', '', '', '', '', '', '', '2025-10-15 11:22:06'),
(25, 'PC-20251015-5324', 'Ana Cruz', 'Human Resource-3', 'Cash', 'documents', 759, 'documents for office', '', '', '2025-10-28', '', '', '', '', '', '', '2025-10-15 11:22:43'),
(26, 'PC-20251015-2487', 'Juan Garcia', 'Human Resource-2', 'Ecash', 'test', 2497, 'test', '1760527472_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '', '2025-10-23', '', '', '', 'ewewe', 'ewewe', '2323233', '2025-10-15 11:24:32'),
(27, 'PC-20251015-2271', 'Miguel Reyes', 'Core-1', 'Cash', 'test', 1707, 'test', '', '', '2025-10-19', '', '', '', '', '', '', '2025-10-15 11:24:57'),
(28, 'PC-20251015-4060', 'Maria Santos', 'Human Resource-1', 'Cash', 'test', 546, 'test', '', '', '2025-08-07', '', '', '', '', '', '', '2025-10-15 11:25:20'),
(29, 'PC-20251015-8409', 'Pedro Reyes', 'Human Resource-4', 'Bank Transfer', 'testing', 1110, 'testing', '1760529066_WhiteandBlueModernMinimalistBlankPageBorderA4Document.png', '', '2025-10-24', 'testing', 'testing', '388775', '', '', '', '2025-10-15 11:51:06'),
(30, 'PC-20251015-8450', 'Sofia Dela Cruz', 'Administrative', 'Cash', 'office supplies', 500, 'small stationery items', '', '', '2025-10-24', '', '', '', '', '', '', '2025-10-15 12:18:42'),
(31, 'PC-20251015-1914', 'Miguel Reyes', 'Core-1', 'Cash', 'vehicle cleaning suuplies', 780, 'small detailing items', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:20:23'),
(32, 'PC-20251015-4838', 'Elena Ramos', 'Core-2', 'Cash', 'Postage', 4000, 'Mailing documents', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:23:27'),
(33, 'PC-20251015-1207', 'Maria Santos', 'Human Resource-1', 'Cash', 'Job posting Fees', 3500, 'posting job openings', '', '', '2025-08-20', '', '', '', '', '', '', '2025-10-15 12:25:56'),
(34, 'PC-20251015-7726', 'Juan Garcia', 'Human Resource-2', 'Cash', 'printing cost', 450, 'printing resumes', '', '', '2025-08-08', '', '', '', '', '', '', '2025-10-15 12:26:50'),
(35, 'PC-20251015-4094', 'Ana Cruz', 'Human Resource-3', 'Cash', 'training materials', 1410, 'workbooks', '', '', '2025-04-17', '', '', '', '', '', '', '2025-10-15 12:27:51'),
(36, 'PC-20251015-5231', 'Pedro Reyes', 'Human Resource-4', 'Cash', 'refreshments', 200, 'training sessions', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:28:54'),
(37, 'PC-20251015-7524', 'Pedro Reyes', 'Human Resource-4', 'Cash', 'postage & delivery', 1000, 'documents to employees', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 12:35:17'),
(39, 'PC-20251015-7562', 'Ethan Magsaysay', 'Financial', 'Cash', 'small transactions fees', 2000, 'minor bank charges', '', '', '2025-10-25', '', '', '', '', '', '', '2025-10-15 13:34:09'),
(40, 'PC-20251015-1333', 'Miguel Reyes', 'Core-1', 'Cash', 'project support', 650, 'printing project report', '', '', '2025-10-17', '', '', '', '', '', '', '2025-10-15 13:35:38'),
(41, 'PC-20251015-6489', 'Ethan Magsaysay', 'Financial', 'Cash', 'Currency Exchange ', 700, 'amounts of currency', '', '', '2025-04-11', '', '', '', '', '', '', '2025-10-15 13:35:43'),
(42, 'PC-20251015-5162', 'Carmen Rivera', 'Logistic-2', 'Ecash', 'Tolls and parking', 300, 'parking fees & toll roads', '1760540148_13565c057ba8a7d3eee83bc702c87318.jpg', '', '2025-10-24', '', '', '', 'magsaysay', 'magsaysay', '1234242', '2025-10-15 14:55:48'),
(46, 'PC-20251015-4290', 'Juan Garcia', 'Human Resource-2', 'Cash', 'supplies', 400, 'bond paper purchase', '1760544130_133981516078589726.jpg', '', '2025-10-16', '', '', '', '', '', '', '2025-10-15 16:02:10'),
(47, 'PC-20251015-1644', 'Ana Cruz', 'Human Resource-3', 'Ecash', 'miscellaneous ', 600, 'emergency lunch for staff ', '', '', '2025-10-16', '', '', '', 'gcash', 'vina g.', '7389-9558-9043', '2025-10-15 16:04:57'),
(48, 'PC-20251015-1626', 'Luis Mendoza', 'Logistic-1', 'Cash', 'minor repair', 250, 'replace busted bulb', '1760544427_133993146453853632.jpg', '', '2025-10-16', '', '', '', '', '', '', '2025-10-15 16:07:07'),
(49, 'PC-20251015-5831', 'Elena Ramos', 'Core-2', 'Bank Transfer', 'transportation', 300, 'grab fare reimbursement', '1760544668_133988447163784439.jpg', '', '2025-10-16', 'bdo ', 'anna d.', '3454-8779-6745', '', '', '', '2025-10-15 16:11:08'),
(50, 'PC-20251015-2325', 'Carmen Rivera', 'Logistic-2', 'Cash', 'fuel', 1000, ' diesel top-up for delivery van', '1760544921_133988447163784439.jpg', '', '2025-10-16', '', '', '', '', '', '', '2025-10-15 16:15:21');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_comments`
--

CREATE TABLE `proposal_comments` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal_comments`
--

INSERT INTO `proposal_comments` (`id`, `proposal_id`, `user_id`, `user_name`, `comment`, `attachment`, `is_internal`, `created_at`) VALUES
(1, 2, 3, 'Anonymous', 'hmgxkmhgcm', NULL, 0, '2026-01-17 10:27:40');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_stages`
--

CREATE TABLE `proposal_stages` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `stage_number` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','rejected') DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `decision` enum('approve','reject','request_changes') DEFAULT NULL,
  `decision_notes` text DEFAULT NULL,
  `completed_by` varchar(255) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rd`
--

CREATE TABLE `rd` (
  `id` int(11) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` blob NOT NULL,
  `payment_due` date NOT NULL,
  `rejected_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rd`
--

INSERT INTO `rd` (`id`, `account_name`, `requested_department`, `expense_categories`, `amount`, `description`, `document`, `payment_due`, `rejected_at`) VALUES
(5, 'justine reyes', 'Logistic', 'Tires', 1000, 'Flat', 0x706466, '2025-10-25', '2024-10-25 06:08:00'),
(6, 'Shine Buen', 'Financial', 'Repaint', 2000, 'Crashed ', 0x706466, '2025-12-25', '2024-10-25 06:30:43'),
(5, 'justine reyes', 'Logistic', 'Tires', 1000, 'Flat', 0x706466, '2025-10-25', '2024-10-25 06:08:00'),
(6, 'Shine Buen', 'Financial', 'Repaint', 2000, 'Crashed ', 0x706466, '2025-12-25', '2024-10-25 06:30:43');

-- --------------------------------------------------------

--
-- Table structure for table `receivable_receipt`
--

CREATE TABLE `receivable_receipt` (
  `receipt_id` int(11) NOT NULL,
  `driver_name` varchar(255) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `amount_receive` int(11) NOT NULL DEFAULT 0,
  `payment_date` datetime NOT NULL,
  `invoice_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receivable_receipt`
--

INSERT INTO `receivable_receipt` (`receipt_id`, `driver_name`, `payment_method`, `amount_receive`, `payment_date`, `invoice_id`, `status`) VALUES
(132446, 'Jose Manalo', 'Cash', 3000, '2025-04-03 00:00:00', '748350', 'collected'),
(176201, 'Jose Manalo', 'Cash', 50000, '2025-04-17 00:00:00', '813481', 'collected'),
(187112, 'Carlo Dalisay', 'Cash', 4000, '2025-04-04 00:00:00', '157081', 'collected'),
(220487, 'Cardo Dalisay', 'Cash', 50000, '2025-04-05 00:00:00', '813481', 'collected'),
(247578, 'Jose Manalo', 'Cash', 2000, '2025-04-03 00:00:00', '748350', 'collected'),
(346984, 'Carlo Dalisay', 'Cash', 1000, '2025-04-10 00:00:00', '157081', 'collected'),
(478181, 'Carlo Dalisay', 'Cash', 70000, '2025-04-03 00:00:00', '748350', 'collected'),
(132446, 'Jose Manalo', 'Cash', 3000, '2025-04-03 00:00:00', '748350', 'collected'),
(176201, 'Jose Manalo', 'Cash', 50000, '2025-04-17 00:00:00', '813481', 'collected'),
(187112, 'Carlo Dalisay', 'Cash', 4000, '2025-04-04 00:00:00', '157081', 'collected'),
(220487, 'Cardo Dalisay', 'Cash', 50000, '2025-04-05 00:00:00', '813481', 'collected'),
(247578, 'Jose Manalo', 'Cash', 2000, '2025-04-03 00:00:00', '748350', 'collected'),
(346984, 'Carlo Dalisay', 'Cash', 1000, '2025-04-10 00:00:00', '157081', 'collected'),
(478181, 'Carlo Dalisay', 'Cash', 70000, '2025-04-03 00:00:00', '748350', 'collected');

-- --------------------------------------------------------

--
-- Table structure for table `regulations`
--

CREATE TABLE `regulations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `requirement` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regulations`
--

INSERT INTO `regulations` (`id`, `name`, `description`, `requirement`, `created_at`) VALUES
(5, 'test', 'test', 'test', '2025-10-14 09:46:08'),
(6, 'xamp', 'xamp', 'xamp', '2025-10-14 17:29:06'),
(5, 'test', 'test', 'test', '2025-10-14 09:46:08'),
(6, 'xamp', 'xamp', 'xamp', '2025-10-14 17:29:06');

-- --------------------------------------------------------

--
-- Table structure for table `reimbursements`
--

CREATE TABLE `reimbursements` (
  `id` int(11) NOT NULL,
  `report_id` varchar(50) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `reimbursement_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Processing') DEFAULT 'Pending',
  `submitted_date` datetime DEFAULT current_timestamp(),
  `approved_date` datetime DEFAULT NULL,
  `approver_notes` text DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL,
  `check_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reimbursements`
--

INSERT INTO `reimbursements` (`id`, `report_id`, `employee_name`, `employee_id`, `department`, `reimbursement_type`, `amount`, `description`, `status`, `submitted_date`, `approved_date`, `approver_notes`, `processed_date`, `check_number`, `created_at`, `updated_at`) VALUES
(1, 'REIM-20251118-8974', 'Chloe Alexandra', '3202501', 'Financials', 'Travel Expenses', 4770.00, 'Reimbursement for Travel Expenses', 'Approved', '2025-11-18 00:00:00', '2026-02-12 01:59:43', 'Approved', NULL, NULL, '2026-02-10 10:22:50', '2026-02-11 17:59:43'),
(2, 'REIM-20250911-1515', 'Chloe Alexandra', '3202501', 'Financials', 'Maintenance & Servicing', 1075.00, 'Reimbursement for Maintenance & Servicing', 'Approved', '2025-09-11 00:00:00', '2026-02-12 01:49:24', 'Approved', NULL, NULL, '2026-02-10 10:22:50', '2026-02-11 17:49:24'),
(3, 'REIM-20250919-9460', 'Nathan James', '4202501', 'Core-1', 'Travel Expenses', 1214.00, 'Reimbursement for Travel Expenses', 'Approved', '2025-09-19 00:00:00', '2026-02-12 01:45:07', 'Approved', NULL, NULL, '2026-02-10 10:22:50', '2026-02-11 17:45:07'),
(4, 'REIM-20251126-3644', 'Mason Taylor', '2202501', 'Logistic-1', 'Maintenance & Servicing', 4480.00, 'Reimbursement for Maintenance & Servicing', 'Approved', '2025-11-26 00:00:00', '2026-02-12 02:03:55', 'Approved', NULL, NULL, '2026-02-10 10:22:50', '2026-02-11 18:03:55'),
(5, 'REIM-20251002-1083', 'Sophia Nicole', '1202502', 'Human Resource-2', 'Travel Expenses', 3572.00, 'Reimbursement for Travel Expenses', 'Approved', '2025-10-02 00:00:00', '2026-02-12 01:43:51', 'Approved', NULL, NULL, '2026-02-10 10:22:50', '2026-02-11 17:43:51'),
(6, 'REIM-20250910-5879', 'Lucas Matteo', '2202503', 'Logistic-1', 'Maintenance & Servicing', 3824.00, 'Reimbursement for Maintenance & Servicing', 'Approved', '2025-09-10 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(7, 'REIM-20251228-6049', 'Ethan Gabriel', '1202501', 'Human Resource-1', 'Office Supplies', 3931.00, 'Reimbursement for Office Supplies', 'Approved', '2025-12-28 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(8, 'REIM-20250905-9998', 'Nathan James', '4202501', 'Core-1', 'Office Operations Cost', 1935.00, 'Reimbursement for Office Operations Cost', 'Approved', '2025-09-05 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(9, 'REIM-20251014-5463', 'Sophia Nicole', '1202502', 'Human Resource-2', 'Office Supplies', 2643.00, 'Reimbursement for Office Supplies', 'Approved', '2025-10-14 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(10, 'REIM-20251031-4753', 'Mason Taylor', '2202501', 'Logistic-1', 'Office Operations Cost', 2983.00, 'Reimbursement for Office Operations Cost', 'Approved', '2025-10-31 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(11, 'REIM-20251004-6415', 'Lucas Matteo', '2202503', 'Logistic-1', 'Office Supplies', 2560.00, 'Reimbursement for Office Supplies', 'Rejected', '2025-10-04 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(12, 'REIM-20251011-3329', 'Sophia Nicole', '1202502', 'Human Resource-2', 'Maintenance & Servicing', 2437.00, 'Reimbursement for Maintenance & Servicing', 'Rejected', '2025-10-11 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(13, 'REIM-20251226-2787', 'Lucas Matteo', '2202503', 'Logistic-1', 'Office Supplies', 1187.00, 'Reimbursement for Office Supplies', 'Rejected', '2025-12-26 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(14, 'REIM-20251011-2610', 'Liam Sebastian', '1202503', 'Human Resource-1', 'Office Supplies', 1061.00, 'Reimbursement for Office Supplies', 'Rejected', '2025-10-11 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(15, 'REIM-20260101-3113', 'Liam Sebastian', '1202503', 'Human Resource-1', 'Office Operations Cost', 2134.00, 'Reimbursement for Office Operations Cost', 'Rejected', '2026-01-01 00:00:00', NULL, NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-10 10:22:50'),
(16, 'REIM-20260109-5751', 'Liam Sebastian', '1202503', 'Human Resource-1', 'Travel Expenses', 3153.00, 'Reimbursement for Travel Expenses', 'Processing', '2026-01-09 00:00:00', '2026-02-12 09:01:37', NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-12 12:33:31'),
(17, 'REIM-20250908-7503', 'Ethan Gabriel', '1202501', 'Human Resource-1', 'Maintenance & Servicing', 2517.00, 'Reimbursement for Maintenance & Servicing', 'Processing', '2025-09-08 00:00:00', '2026-02-12 09:01:37', NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-12 12:33:24'),
(18, 'REIM-20251222-0486', 'Mason Taylor', '2202501', 'Logistic-1', 'Office Operations Cost', 1598.00, 'Reimbursement for Office Operations Cost', 'Processing', '2025-12-22 00:00:00', '2026-02-12 09:01:37', NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-12 12:33:20'),
(19, 'REIM-20251010-7648', 'Sophia Nicole', '1202502', 'Human Resource-2', 'Office Supplies', 1624.00, 'Reimbursement for Office Supplies', 'Processing', '2025-10-10 00:00:00', '2026-02-12 09:01:37', NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-12 12:33:16'),
(20, 'REIM-20260108-1530', 'Chloe Alexandra', '3202501', 'Financials', 'Office Supplies', 3007.00, 'Reimbursement for Office Supplies', 'Processing', '2026-01-08 00:00:00', '2026-02-12 09:01:37', NULL, NULL, NULL, '2026-02-10 10:22:50', '2026-02-12 12:33:11'),
(21, 'REIMB-20260210-9898', 'Juanito Alfonso', '123456', 'Financials', 'Taxes & Financial Costs - Tax Payments', 5000.00, 'Tax Payment', 'Approved', '2026-02-10 23:23:22', '2026-02-12 09:01:37', 'Approved', NULL, NULL, '2026-02-10 15:23:22', '2026-02-12 01:01:37'),
(22, 'REIM-20260214-101', 'Maria Santos', 'EMP-FN-001', 'Core-1', 'Office Supplies', 3500.00, 'Monthly office stationery and printer ink', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:20:29', 'Approved', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:20:29'),
(23, 'REIM-20260214-102', 'Jose Reyes', 'EMP-LG-002', 'Logistic-1', 'Travel Expenses', 8200.50, 'Fuel and meals for Batangas delivery route', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:21:29', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:21:29'),
(24, 'REIM-20260214-103', 'Ana Clara', 'EMP-HR-003', 'Human Resource-1', 'Office Operations Cost', 4500.00, 'Recruitment event snacks and materials', 'Approved', '2026-02-14 00:21:34', '2026-02-14 12:48:13', 'Approved', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 04:48:13'),
(25, 'REIM-20260214-104', 'Rafael Garcia', 'EMP-MT-004', 'Maintenance', 'Maintenance & Servicing', 15000.00, 'Emergency AC repair for Server Room', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:22:01', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:22:01'),
(26, 'REIM-20260214-105', 'Lito Lapid', 'EMP-LG-005', 'Logistic-2', 'Travel Expenses', 6750.00, 'Toll fees and overnight accommodation', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:22:01', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:22:01'),
(27, 'REIM-20260214-106', 'Grace Tan', 'EMP-AD-006', 'Administrative', 'Office Supplies', 3200.00, 'New ergonomic chairs for reception', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:22:01', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:22:01'),
(28, 'REIM-20260214-107', 'Mark Bautista', 'EMP-CR-007', 'Core-2', 'Office Operations Cost', 5600.00, 'Team building venue reservation fee', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:22:01', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:22:01'),
(29, 'REIM-20260214-108', 'Sarah Geronimo', 'EMP-HR-008', 'Human Resource-2', 'Office Supplies', 4100.00, 'ID printing supplies and lanyards', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:22:01', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:22:01'),
(30, 'REIM-20260214-109', 'Coco Martin', 'EMP-LG-009', 'Logistic-1', 'Maintenance & Servicing', 9800.00, 'Truck A-105 tire replacement', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:22:01', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:22:01'),
(31, 'REIM-20260214-110', 'Regine Velasquez', 'EMP-CR-010', 'Core-1', 'Travel Expenses', 12500.00, 'Client meeting expenses Cebu branch', 'Approved', '2026-02-14 00:21:34', '2026-02-14 15:22:01', 'Approved via bulk action', NULL, NULL, '2026-02-13 16:21:34', '2026-02-14 07:22:01'),
(32, 'REIM-202603-001', 'Alden Richards', 'EMP-B2-001', 'Core-1', 'Travel Expenses', 4500.00, 'Grab to client meeting', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(33, 'REIM-202603-002', 'Maine Mendoza', 'EMP-B2-002', 'Human Resource-1', 'Office Supplies', 1200.50, 'Bond paper and ink', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(34, 'REIM-202603-003', 'Dingdong Dantes', 'EMP-B2-003', 'Administrative', 'Meals', 850.00, 'Overtime dinner', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(35, 'REIM-202603-004', 'Marian Rivera', 'EMP-B2-004', 'Financials', 'Travel Expenses', 2300.00, 'Taxi slip #8821', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(36, 'REIM-202603-005', 'Vice Ganda', 'EMP-B2-005', 'Core-2', 'Communication', 1500.00, 'Internet subsidy', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(37, 'REIM-202603-006', 'Anne Curtis', 'EMP-B2-006', 'Logistic-1', 'Gasoline', 3500.00, 'Full tank delivery van', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(38, 'REIM-202603-007', 'Vhong Navarro', 'EMP-B2-007', 'Logistic-2', 'Maintenance', 5000.00, 'Change oil Truck A', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(39, 'REIM-202603-008', 'Jhong Hilario', 'EMP-B2-008', 'Core-1', 'Meals', 600.00, 'Team lunch meeting', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(40, 'REIM-202603-009', 'Karylle Tatlonghari', 'EMP-B2-009', 'Human Resource-2', 'Training', 15000.00, 'HR Seminar Fee', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(41, 'REIM-202603-010', 'Teddy Corpuz', 'EMP-B2-010', 'Core-2', 'Office Supplies', 450.00, 'Ballpens and markers', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(42, 'REIM-202603-011', 'Jugs Jugueta', 'EMP-B2-011', 'IT Support', 'Hardware', 2800.00, 'Replacement mouse/kb', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(43, 'REIM-202603-012', 'Ryan Bang', 'EMP-B2-012', 'Logistic-1', 'Parking Fee', 120.00, 'Parking at Makati', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(44, 'REIM-202603-013', 'Amy Perez', 'EMP-B2-013', 'Administrative', 'Miscellaneous', 500.00, 'Cleaning materials', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(45, 'REIM-202603-014', 'Ogie Alcasid', 'EMP-B2-014', 'Financials', 'Travel Expenses', 1800.00, 'Bus fare to province', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(46, 'REIM-202603-015', 'Regine Velasquez', 'EMP-B2-015', 'Core-1', 'Representation', 8000.00, 'Client dinner', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(47, 'REIM-202603-016', 'Piolo Pascual', 'EMP-B2-016', 'Core-2', 'Communication', 2000.00, 'Postpaid plan allowance', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(48, 'REIM-202603-017', 'Catriona Gray', 'EMP-B2-017', 'Human Resource-1', 'Travel Expenses', 3200.00, 'Grab car expenses', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(49, 'REIM-202603-018', 'Pia Wurtzbach', 'EMP-B2-018', 'Marketing', 'Advertising', 12000.00, 'FB Ads boost payment', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(50, 'REIM-202603-019', 'Manny Pacquiao', 'EMP-B2-019', 'Logistic-2', 'Gasoline', 4500.00, 'Diesel for Fleet', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(51, 'REIM-202603-020', 'Jinkee Pacquiao', 'EMP-B2-020', 'Financials', 'Office Supplies', 3000.00, 'New heavy duty stapler', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(52, 'REIM-202603-021', 'Coco Martin', 'EMP-B2-021', 'Core-1', 'Meals', 1200.00, 'OT Meal allowance', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(53, 'REIM-202603-022', 'Julia Montes', 'EMP-B2-022', 'Core-2', 'Travel Expenses', 900.00, 'Transport refund', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(54, 'REIM-202603-023', 'Kathryn Bernardo', 'EMP-B2-023', 'Administrative', 'Office Supplies', 600.00, 'Paper clips/folders', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(55, 'REIM-202603-024', 'Daniel Padilla', 'EMP-B2-024', 'Logistic-1', 'Maintenance', 850.00, 'Motorcycle vulcanizing', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(56, 'REIM-202603-025', 'Liza Soberano', 'EMP-B2-025', 'Human Resource-2', 'Miscellaneous', 2500.00, 'Job fair booth fee', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(57, 'REIM-202603-026', 'Enrique Gil', 'EMP-B2-026', 'IT Support', 'Software', 5600.00, 'Annual domain renewal', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(58, 'REIM-202603-027', 'Joshua Garcia', 'EMP-B2-027', 'Core-1', 'Travel Expenses', 1100.00, 'Daily commute allowance', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(59, 'REIM-202603-028', 'Janella Salvador', 'EMP-B2-028', 'Core-2', 'Communication', 500.00, 'Load allowance', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(60, 'REIM-202603-029', 'Bea Alonzo', 'EMP-B2-029', 'Financials', 'Travel Expenses', 6700.00, 'Plane tix to Cebu branch', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25'),
(61, 'REIM-202603-030', 'John Lloyd Cruz', 'EMP-B2-030', 'Administrative', 'Repairs', 3500.00, 'Broken door knob repair', 'Pending', '2026-02-14 15:46:25', NULL, NULL, NULL, NULL, '2026-02-14 07:46:25', '2026-02-14 07:46:25');

--
-- Triggers `reimbursements`
--
DELIMITER $$
CREATE TRIGGER `after_reimbursement_approved` AFTER UPDATE ON `reimbursements` FOR EACH ROW BEGIN
    -- Only when status changes to Approved
    IF NEW.status = 'Approved' AND OLD.status != 'Approved' THEN
        
        -- Check if not already in pa table
        IF NOT EXISTS (
            SELECT 1 FROM pa 
            WHERE reference_id = NEW.report_id 
            AND transaction_type = 'Reimbursement'
            LIMIT 1
        ) THEN
            
            -- Insert into pa table
            INSERT INTO pa (
                reference_id,
                account_name,
                requested_department,
                mode_of_payment,
                expense_categories,
                transaction_type,
                payout_type,
                source_module,
                amount,
                description,
                document,
                payment_due,
                employee_id,
                status,
                from_payable,
                bank_name,
                bank_account_number,
                bank_account_name,
                ecash_provider,
                ecash_account_name,
                ecash_account_number,
                approved_by,
                approved_at,
                approval_source,
                requested_at,
                submitted_date,
                approved_date
            ) VALUES (
                NEW.report_id,                          -- reference_id
                NEW.employee_name,                      -- account_name  
                NEW.department,                         -- requested_department
                'CASH',                                 -- mode_of_payment
                NEW.reimbursement_type,                 -- expense_categories
                'Reimbursement',                        -- transaction_type
                'Reimbursement',                        -- payout_type
                'Reimbursement',                        -- source_module
                NEW.amount,                             -- amount
                CONCAT('Reimbursement: ', NEW.reimbursement_type, 
                       IF(NEW.description IS NOT NULL AND NEW.description != '', 
                          CONCAT(' - ', NEW.description), '')),  -- description
                NULL,                                   -- document (NULL is ok now)
                COALESCE(NEW.processed_date, DATE_ADD(CURDATE(), INTERVAL 7 DAY)),  -- payment_due
                NEW.employee_id,                        -- employee_id
                'Pending Disbursement',                 -- status
                1,                                      -- from_payable
                '',                                     -- bank_name
                '',                                     -- bank_account_number
                '',                                     -- bank_account_name
                '',                                     -- ecash_provider
                '',                                     -- ecash_account_name
                '',                                     -- ecash_account_number
                'System',                               -- approved_by
                NEW.approved_date,                      -- approved_at
                'Reimbursement Approval',               -- approval_source
                NOW(),                                  -- requested_at
                NEW.submitted_date,                     -- submitted_date
                NEW.approved_date                       -- approved_date
            );
            
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reimbursement_receipts`
--

CREATE TABLE `reimbursement_receipts` (
  `id` int(11) NOT NULL,
  `report_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reimbursement_receipts`
--

INSERT INTO `reimbursement_receipts` (`id`, `report_id`, `file_name`, `file_path`, `file_type`, `file_size`, `uploaded_date`) VALUES
(1, 'REIMB-20251215-1001', 'transportation_receipt.pdf', '/uploads/receipts/transport_1001.pdf', 'pdf', 245000, '2025-12-15 09:30:00'),
(2, 'REIMB-20251216-1002', 'office_supplies.jpg', '/uploads/receipts/supplies_1002.jpg', 'jpg', 1250000, '2025-12-16 11:15:00'),
(3, 'REIMB-20251217-1003', 'training_certificate.pdf', '/uploads/receipts/training_1003.pdf', 'pdf', 320000, '2025-12-17 14:45:00'),
(4, 'REIMB-20251218-1004', 'safety_equipment.png', '/uploads/receipts/safety_1004.png', 'png', 1850000, '2025-12-18 10:20:00'),
(5, 'REIMB-20251219-1005', 'software_invoice.pdf', '/uploads/receipts/software_1005.pdf', 'pdf', 420000, '2025-12-19 16:30:00'),
(6, 'REIMB-20251205-2001', 'cebu_trip_expenses.pdf', '/uploads/receipts/travel_2001.pdf', 'pdf', 380000, '2025-12-05 08:45:00'),
(7, 'REIMB-20251206-2002', 'team_lunch_receipt.jpg', '/uploads/receipts/meals_2002.jpg', 'jpg', 950000, '2025-12-06 12:15:00'),
(8, 'REIMB-20251207-2003', 'computer_invoice.pdf', '/uploads/receipts/equipment_2003.pdf', 'pdf', 520000, '2025-12-07 09:30:00'),
(9, 'REIMB-20251208-2004', 'training_receipt.pdf', '/uploads/receipts/training_2004.pdf', 'pdf', 280000, '2025-12-08 13:40:00'),
(10, 'REIMB-20251209-2005', 'vehicle_maintenance.jpg', '/uploads/receipts/maintenance_2005.jpg', 'jpg', 2100000, '2025-12-09 10:50:00'),
(11, 'REIMB-20251210-2006', 'office_supplies_receipt.png', '/uploads/receipts/supplies_2006.png', 'png', 1200000, '2025-12-10 14:20:00'),
(12, 'REIMB-20251203-3001', 'mileage_log.pdf', '/uploads/receipts/mileage_3001.pdf', 'pdf', 310000, '2025-12-03 11:30:00'),
(13, 'REIMB-20251204-3002', 'dinner_receipt.jpg', '/uploads/receipts/dinner_3002.jpg', 'jpg', 1850000, '2025-12-04 15:45:00'),
(14, 'REIMB-20251212-4001', 'utility_bills.pdf', '/uploads/receipts/utilities_4001.pdf', 'pdf', 450000, '2025-12-12 09:15:00'),
(15, 'REIMB-20251213-4002', 'workshop_invoice.pdf', '/uploads/receipts/workshop_4002.pdf', 'pdf', 390000, '2025-12-13 13:25:00'),
(16, 'REIMB-20251214-4003', 'forklift_battery.jpg', '/uploads/receipts/equipment_4003.jpg', 'jpg', 3200000, '2025-12-14 16:40:00'),
(17, 'REIMB-20260127-3632', 'justificationexample.doc', 'uploads/receipts/1769527081_justificationexample.doc', 'doc', 28160, '2026-01-27 23:18:01'),
(18, 'REIMB-20260128-2720', 'justificationexample.doc', 'uploads/receipts/1769567054_justificationexample.doc', 'doc', 28160, '2026-01-28 10:24:14'),
(19, 'REIMB-20260128-9986', '1769527081_justificationexample.doc', 'uploads/receipts/1769586013_1769527081_justificationexample.doc', 'doc', 28160, '2026-01-28 15:40:13'),
(20, 'REIMB-20260130-6126', 'Budget_Proposal_ViaHale.pdf', '1769771913_Budget_Proposal_ViaHale.pdf', 'pdf', 230386, '2026-01-30 19:18:33'),
(21, 'REIMB-20260130-9498', 'Budget_Proposal_ViaHale.pdf', '1769772246_Budget_Proposal_ViaHale.pdf', 'pdf', 230386, '2026-01-30 19:24:06'),
(22, 'REIMB-20260130-9467', 'Budget_Proposal_ViaHale.pdf', '1769772357_Budget_Proposal_ViaHale.pdf', 'pdf', 230386, '2026-01-30 19:25:57'),
(23, 'REIMB-20260130-5029', 'Budget_Proposal_ViaHale.pdf', '1769773454_Budget_Proposal_ViaHale.pdf', 'pdf', 230386, '2026-01-30 19:44:14'),
(24, 'REIMB-20260131-9380', 'disbursed-records.pdf', '1769829233_disbursed-records.pdf', 'pdf', 19710, '2026-01-31 11:13:53'),
(25, 'REIMB-20260131-6900', 'payables_receipts_disbursed.pdf', '1769838523_payables_receipts_disbursed.pdf', 'pdf', 21190, '2026-01-31 13:48:43'),
(26, 'REIMB-20260131-6900', 'disbursed-records.pdf', '1769838523_disbursed-records.pdf', 'pdf', 19710, '2026-01-31 13:48:43'),
(27, 'REIMB-20260205-2112', 'invoice-VHL-20260201-7327.pdf', '1770303542_invoice-VHL-20260201-7327.pdf', 'pdf', 207218, '2026-02-05 22:59:02'),
(28, 'REIMB-20260205-2112', 'reimbursement_report_2026-01-31T06-50-59.pdf', '1770303542_reimbursement_report_2026-01-31T06-50-59.pdf', 'pdf', 15701, '2026-02-05 22:59:02'),
(29, 'REIMB-20260210-9898', 'invoice-VHL-20260201-7327.pdf', '1770737002_invoice-VHL-20260201-7327.pdf', 'pdf', 207218, '2026-02-10 23:23:22'),
(30, 'REIMB-20260210-9898', 'Journal_Entries_2026-02-10.pdf', '1770737002_Journal_Entries_2026-02-10.pdf', 'pdf', 176586, '2026-02-10 23:23:22');

-- --------------------------------------------------------

--
-- Table structure for table `reimbursement_timeline`
--

CREATE TABLE `reimbursement_timeline` (
  `id` int(11) NOT NULL,
  `report_id` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reimbursement_timeline`
--

INSERT INTO `reimbursement_timeline` (`id`, `report_id`, `action`, `notes`, `created_at`) VALUES
(1, 'REIMB-20251205-2001', 'submitted', 'Reimbursement submitted for approval', '2025-12-05 08:45:00'),
(2, 'REIMB-20251205-2001', 'reviewed', 'Reviewed by department head', '2025-12-06 10:30:00'),
(3, 'REIMB-20251205-2001', 'approved', 'Approved - Within budget guidelines', '2025-12-08 14:30:00'),
(4, 'REIMB-20251206-2002', 'submitted', 'Reimbursement submitted for approval', '2025-12-06 12:15:00'),
(5, 'REIMB-20251206-2002', 'approved', 'Approved - Team building expense', '2025-12-09 10:45:00'),
(6, 'REIMB-20251207-2003', 'submitted', 'Reimbursement submitted for approval', '2025-12-07 09:30:00'),
(7, 'REIMB-20251207-2003', 'reviewed', 'IT department verification completed', '2025-12-08 15:20:00'),
(8, 'REIMB-20251207-2003', 'approved', 'Approved - Necessary equipment upgrade', '2025-12-10 11:20:00'),
(9, 'REIMB-20251208-2004', 'submitted', 'Reimbursement submitted for approval', '2025-12-08 13:40:00'),
(10, 'REIMB-20251208-2004', 'approved', 'Approved - Training expense approved', '2025-12-11 15:10:00'),
(11, 'REIMB-20251209-2005', 'submitted', 'Reimbursement submitted for approval', '2025-12-09 10:50:00'),
(12, 'REIMB-20251209-2005', 'reviewed', 'Vehicle maintenance verified', '2025-12-10 14:30:00'),
(13, 'REIMB-20251209-2005', 'approved', 'Approved - Regular maintenance cost', '2025-12-12 09:25:00'),
(14, 'REIMB-20251210-2006', 'submitted', 'Reimbursement submitted for approval', '2025-12-10 14:20:00'),
(15, 'REIMB-20251210-2006', 'approved', 'Approved - Office supplies replenishment', '2025-12-13 16:45:00'),
(16, 'REIMB-20251203-3001', 'submitted', 'Reimbursement submitted for approval', '2025-12-03 11:30:00'),
(17, 'REIMB-20251203-3001', 'reviewed', 'Mileage calculation incorrect', '2025-12-05 09:15:00'),
(18, 'REIMB-20251203-3001', 'rejected', 'Rejected - Personal mileage claimed', '2025-12-06 10:15:00'),
(19, 'REIMB-20251204-3002', 'submitted', 'Reimbursement submitted for approval', '2025-12-04 15:45:00'),
(20, 'REIMB-20251204-3002', 'rejected', 'Rejected - Exceeds entertainment budget limit', '2025-12-07 14:20:00'),
(21, 'REIMB-20251212-4001', 'submitted', 'Reimbursement submitted for approval', '2025-12-12 09:15:00'),
(22, 'REIMB-20251212-4001', 'approved', 'Approved - Monthly utility bills', '2025-12-15 11:30:00'),
(23, 'REIMB-20251212-4001', 'processing', 'Payment being processed', '2025-12-16 09:45:00'),
(24, 'REIMB-20251213-4002', 'submitted', 'Reimbursement submitted for approval', '2025-12-13 13:25:00'),
(25, 'REIMB-20251213-4002', 'approved', 'Approved - Training registration fee', '2025-12-16 10:45:00'),
(26, 'REIMB-20251213-4002', 'processing', 'Awaiting bank transfer', '2025-12-17 14:20:00'),
(27, 'REIMB-20251214-4003', 'submitted', 'Reimbursement submitted for approval', '2025-12-14 16:40:00'),
(28, 'REIMB-20251214-4003', 'approved', 'Approved - Equipment maintenance', '2025-12-17 14:15:00'),
(29, 'REIMB-20251214-4003', 'processing', 'Check being prepared', '2025-12-18 11:30:00'),
(30, 'REIMB-20260127-3632', 'submitted', 'Reimbursement submitted for approval', '2026-01-27 23:18:01'),
(31, 'REIMB-20260128-2720', 'submitted', 'Reimbursement submitted for approval', '2026-01-28 10:24:14'),
(32, 'REIMB-20260128-2720', 'rejected', 'WALA LANG', '2026-01-28 10:25:10'),
(33, 'REIMB-20260128-9986', 'submitted', 'Reimbursement submitted for approval', '2026-01-28 15:40:13'),
(34, 'REIMB-20260128-9986', 'approved', 'Approved', '2026-01-30 17:18:06'),
(35, 'REIMB-20260127-3632', 'approved', 'Approved', '2026-01-30 18:51:17'),
(36, 'REIMB-20251219-1005', 'approved', 'Approved', '2026-01-30 19:02:40'),
(37, 'REIMB-20260130-6126', 'submitted', 'Reimbursement submitted for approval', '2026-01-30 19:18:33'),
(38, 'REIMB-20260130-6126', 'approved', 'Approved', '2026-01-30 19:18:44'),
(39, 'REIMB-20260130-9498', 'submitted', 'Reimbursement submitted for approval', '2026-01-30 19:24:06'),
(42, 'REIMB-20260130-9467', 'submitted', 'Reimbursement submitted for approval', '2026-01-30 19:25:57'),
(43, 'REIMB-20260130-9467', 'approved', 'Approved', '2026-01-30 19:26:05'),
(44, 'REIMB-20260130-5029', 'submitted', 'Reimbursement submitted for approval', '2026-01-30 19:44:14'),
(45, 'REIMB-20260131-9380', 'submitted', 'Reimbursement submitted for approval', '2026-01-31 11:13:53'),
(46, 'REIMB-20260131-9380', 'approved', 'Approved via bulk action', '2026-01-31 12:24:45'),
(47, 'REIMB-20260130-5029', 'approved', 'Approved via bulk action', '2026-01-31 12:24:45'),
(48, 'REIMB-20260131-6900', 'submitted', 'Reimbursement submitted for approval', '2026-01-31 13:48:43'),
(49, 'REIMB-20260205-2112', 'submitted', 'Reimbursement submitted for approval', '2026-02-05 22:59:02'),
(50, 'REIMB-20260130-9498', 'approved', 'Approved via bulk action', '2026-02-07 19:18:52'),
(51, 'REIMB-20260205-2112', 'approved', 'Approved', '2026-02-08 00:45:41'),
(52, 'REIMB-20260131-6900', 'approved', 'Approved', '2026-02-08 15:13:11'),
(53, 'REIMB-20251218-1004', 'approved', 'Approved', '2026-02-08 15:26:03'),
(54, 'REIMB-20251217-1003', 'approved', 'Approved', '2026-02-08 15:26:41'),
(57, 'REIMB-20260205-2112', 'approved', 'Approved', '2026-02-08 15:33:24'),
(58, 'REIMB-20260131-6900', 'approved', 'Approved', '2026-02-08 17:38:25'),
(59, 'REIMB-20260205-2112', 'approved', 'Approved', '2026-02-08 17:57:53'),
(60, 'REIMB-20260210-9898', 'submitted', 'Reimbursement submitted for approval', '2026-02-10 23:23:22'),
(61, 'REIM-20251002-1083', 'approved', 'Approved', '2026-02-12 01:43:51'),
(62, 'REIM-20250919-9460', 'approved', 'Approved', '2026-02-12 01:45:07'),
(63, 'REIM-20250911-1515', 'approved', 'Approved', '2026-02-12 01:49:24'),
(64, 'REIM-20251118-8974', 'approved', 'Approved', '2026-02-12 01:59:43'),
(65, 'REIM-20251126-3644', 'approved', 'Approved', '2026-02-12 02:03:55'),
(66, 'REIMB-20260210-9898', 'approved', 'Approved', '2026-02-12 09:01:37'),
(67, 'REIM-20260214-103', 'approved', 'Approved', '2026-02-14 12:48:13'),
(68, 'REIM-20260214-101', 'approved', 'Approved', '2026-02-14 15:20:29'),
(69, 'REIM-20260214-102', 'approved', 'Approved via bulk action', '2026-02-14 15:21:29'),
(70, 'REIM-20260214-104', 'approved', 'Approved via bulk action', '2026-02-14 15:22:01'),
(71, 'REIM-20260214-105', 'approved', 'Approved via bulk action', '2026-02-14 15:22:01'),
(72, 'REIM-20260214-106', 'approved', 'Approved via bulk action', '2026-02-14 15:22:01'),
(73, 'REIM-20260214-107', 'approved', 'Approved via bulk action', '2026-02-14 15:22:01'),
(74, 'REIM-20260214-108', 'approved', 'Approved via bulk action', '2026-02-14 15:22:01'),
(75, 'REIM-20260214-109', 'approved', 'Approved via bulk action', '2026-02-14 15:22:01'),
(76, 'REIM-20260214-110', 'approved', 'Approved via bulk action', '2026-02-14 15:22:01');

-- --------------------------------------------------------

--
-- Table structure for table `rejected_payables`
--

CREATE TABLE `rejected_payables` (
  `id` int(11) NOT NULL,
  `invoice_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `payment_due` date DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ecash_provider` varchar(100) DEFAULT NULL,
  `ecash_account_name` varchar(255) DEFAULT NULL,
  `ecash_account_number` varchar(50) DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `vendor_type` enum('Vendor','Supplier') DEFAULT 'Vendor',
  `vendor_address` text DEFAULT NULL,
  `gl_account` varchar(100) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `rejected_payables`
--

INSERT INTO `rejected_payables` (`id`, `invoice_id`, `department`, `vendor_name`, `payment_method`, `amount`, `description`, `document`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `rejected_reason`, `created_at`, `vendor_type`, `vendor_address`, `gl_account`, `invoice_date`) VALUES
(1, '123487', 'Human Resource-3', 'test 5', 'Ecash', 2000.00, 'extra', '1756638594_bill.pdf', '2025-09-16', '', '', '', 'Gcash', 'test5', '54230012452664514', 'test', '2026-01-31 14:57:54', 'Vendor', NULL, NULL, NULL),
(2, '123487', 'Human Resource-3', 'test 5', 'Ecash', 2000.00, 'extra', '1756638594_bill.pdf', '2025-09-16', '', '', '', 'Gcash', 'test5', '54230012452664514', 'test', '2026-01-31 14:57:54', 'Vendor', NULL, NULL, NULL),
(3, '123487', 'Human Resource-3', 'test 5', 'Ecash', 2000.00, 'extra', '1756638594_bill.pdf', '2025-09-16', '', '', '', 'Gcash', 'test5', '54230012452664514', 'test', '2026-01-31 14:57:54', 'Vendor', NULL, NULL, NULL),
(7, '202601314269', 'Human Resource-3', 'Jose Rizal', 'Cash', 13266.00, 'ashsdvlj', '[\"1769870054_reimbursement_report_2026-01-31T06-50-24.pdf\"]', '2026-02-28', '', '', '', '', '', '', 'invalid', '2026-01-31 15:00:42', 'Vendor', NULL, NULL, NULL),
(9, '202601319630', 'Human Resource-3', 'Jose Rizal', 'Cash', 89313.00, 'awfaee', '[]', '2026-01-31', '', '', '', '', '', '', 'invalid', '2026-01-31 15:02:00', 'Vendor', NULL, NULL, NULL),
(10, '202601315289', 'Human Resource-2', 'Juan Alfonso', 'Cash', 31135.00, 'cashjv ,dn ', '[\"1769870521_reimbursement_report_2026-01-31T06-50-59.pdf\",\"1769870521_disbursed-records.pdf\"]', '2026-01-31', '', '', '', '', '', '', 'insufficient budget', '2026-02-05 14:13:01', 'Vendor', NULL, NULL, NULL),
(11, 'INV-20260201-7327', 'Logistic-1', 'SpeedFix Auto Service Center', 'Bank Transfer', 57048.80, 'Equipment Purchase', '[\"1770794704_invoice-VHL-20260201-7327.pdf\"]', '2026-02-15', 'BDO', 'SpeedFix', '246532102130', '', '', '', 'insufficient budget', '2026-02-12 13:41:39', 'Supplier', '456 Banawe Street, Quezon City', '211001 - Accounts Payable - Suppliers', '2026-02-01');

-- --------------------------------------------------------

--
-- Table structure for table `rejected_pettycash`
--

CREATE TABLE `rejected_pettycash` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(50) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `requested_department` varchar(100) NOT NULL,
  `mode_of_payment` varchar(50) NOT NULL,
  `expense_categories` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `time_period` varchar(20) DEFAULT NULL,
  `payment_due` date DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `ecash_provider` varchar(50) DEFAULT NULL,
  `ecash_account_name` varchar(100) DEFAULT NULL,
  `ecash_account_number` varchar(50) DEFAULT NULL,
  `rejected_reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rejected_pettycash`
--

INSERT INTO `rejected_pettycash` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `time_period`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `rejected_reason`, `created_at`) VALUES
(2, 'PC-6151-2025', 'test', 'Core-1', 'Cash', 'Petty Cash Allowance', 950.00, 'extra', '', 'Weekly', '2025-08-16', '', '', '', '', '', '', 'no file\\r\\n', '2025-10-03 21:29:57'),
(3, 'PC-4440-2025', 'test1', 'Financial', 'Cash', 'Petty Cash Allowance', 1000.00, 'extra', '', 'Weekly', '2025-08-16', '', '', '', '', '', '', 'no file', '2025-10-03 21:30:11'),
(4, 'PC-20251015-5255', 'Ethan Magsaysay', 'Financial', 'Ecash', '   ', 500.00, ' office needs calculator replacement', '1760543769_133993146453853632.jpg', '', '2025-10-15', '', '', '', 'maya', 'mei b.', '6785-0975-4574', 'no category', '2025-10-15 22:24:10'),
(5, 'PC-20251015-7274', 'Maria Santos', 'Human Resource-1', 'Cash', 'hh', 950.00, 'snacks for  orientation', '1760543910_134017226729730780.jpg', '', '2025-10-15', '', '', '', '', '', '', 'no category', '2025-10-15 22:24:35'),
(2, 'PC-6151-2025', 'test', 'Core-1', 'Cash', 'Petty Cash Allowance', 950.00, 'extra', '', 'Weekly', '2025-08-16', '', '', '', '', '', '', 'no file\\r\\n', '2025-10-03 21:29:57'),
(3, 'PC-4440-2025', 'test1', 'Financial', 'Cash', 'Petty Cash Allowance', 1000.00, 'extra', '', 'Weekly', '2025-08-16', '', '', '', '', '', '', 'no file', '2025-10-03 21:30:11'),
(4, 'PC-20251015-5255', 'Ethan Magsaysay', 'Financial', 'Ecash', '   ', 500.00, ' office needs calculator replacement', '1760543769_133993146453853632.jpg', '', '2025-10-15', '', '', '', 'maya', 'mei b.', '6785-0975-4574', 'no category', '2025-10-15 22:24:10'),
(5, 'PC-20251015-7274', 'Maria Santos', 'Human Resource-1', 'Cash', 'hh', 950.00, 'snacks for  orientation', '1760543910_134017226729730780.jpg', '', '2025-10-15', '', '', '', '', '', '', 'no category', '2025-10-15 22:24:35');

-- --------------------------------------------------------

--
-- Table structure for table `rejected_request`
--

CREATE TABLE `rejected_request` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(30) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `mode_of_payment` varchar(20) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` blob NOT NULL,
  `time_period` varchar(30) NOT NULL,
  `payment_due` date NOT NULL,
  `rejected_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rejected_reason` text NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `bank_account_name` varchar(255) NOT NULL,
  `bank_account_number` varchar(255) NOT NULL,
  `ecash_provider` varchar(255) NOT NULL,
  `ecash_account_name` varchar(255) NOT NULL,
  `ecash_account_number` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rejected_request`
--

INSERT INTO `rejected_request` (`id`, `reference_id`, `account_name`, `requested_department`, `mode_of_payment`, `expense_categories`, `amount`, `description`, `document`, `time_period`, `payment_due`, `rejected_at`, `rejected_reason`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`) VALUES
(2, 'BR-2728-2025', 'hr', 'Human Resource-2', 'Cash', 'Training Cost', 5000, 'training', '', 'Quarterly', '2025-08-18', '2025-08-15 10:10:56', 'no document', '', '', '', '', '', ''),
(3, 'BR-7923-2025', 'test', 'Financial', 'Cash', 'Tax Payment', 1000, 'tax', '', 'Monthly', '2025-08-30', '2025-08-15 12:58:19', 'no document', '', '', '', '', '', ''),
(4, 'BR-7213-2025', 'test', 'Financial', 'Cash', 'Tax Payment', 2000, 'tax', '', 'Monthly', '2025-08-30', '2025-08-15 12:58:38', 'no document', '', '', '', '', '', ''),
(13, 'BR-20250907-5223', 'test', 'Financial', 'Cash', 'test', 500, 'test', 0x313735373235343334305f62696c6c2e706466, 'monthly', '2025-09-30', '2025-10-02 07:33:12', 'test\\r\\n', '', '', '', '', '', ''),
(14, 'BR-20250907-3080', 'financial admin', 'Financial', 'Cash', 'tax ', 2000, 'tax', 0x313735373235303034345f62696c6c2e706466, 'monthly', '2025-09-30', '2025-10-02 07:34:41', 'test', '', '', '', '', '', ''),
(15, 'BR-20251015-9746', 'admin admin', 'Logistic-2', 'Cash', 'honda civic', 1600000, 'buy a car', '', 'monthly', '2025-10-23', '2025-10-15 00:27:14', 'No submitted document', '', '', '', '', '', ''),
(16, 'BR-20251015-7645', 'admin admin', 'Logistic-1', 'Cash', 'car', 15000, 'car', '', 'monthly', '2025-10-22', '2025-10-15 00:30:02', 'No submitted document', '', '', '', '', '', ''),
(17, 'BR-20251015-4451', 'admin admin', 'Financial', 'Cash', 'test', 350, 'test', '', 'weekly', '2025-10-16', '2025-10-15 00:38:29', 'No submitted document', '', '', '', '', '', ''),
(18, 'BR-20251015-5552', 'Luis Mendoza', 'Logistic-1', 'Cash', 'Postage & Courier', 5000, 'official correspondence', '', 'yearly', '2025-03-04', '2025-10-15 22:23:42', 'no submitted document', '', '', '', '', '', ''),
(2, 'BR-2728-2025', 'hr', 'Human Resource-2', 'Cash', 'Training Cost', 5000, 'training', '', 'Quarterly', '2025-08-18', '2025-08-15 10:10:56', 'no document', '', '', '', '', '', ''),
(3, 'BR-7923-2025', 'test', 'Financial', 'Cash', 'Tax Payment', 1000, 'tax', '', 'Monthly', '2025-08-30', '2025-08-15 12:58:19', 'no document', '', '', '', '', '', ''),
(4, 'BR-7213-2025', 'test', 'Financial', 'Cash', 'Tax Payment', 2000, 'tax', '', 'Monthly', '2025-08-30', '2025-08-15 12:58:38', 'no document', '', '', '', '', '', ''),
(13, 'BR-20250907-5223', 'test', 'Financial', 'Cash', 'test', 500, 'test', 0x313735373235343334305f62696c6c2e706466, 'monthly', '2025-09-30', '2025-10-02 07:33:12', 'test\\r\\n', '', '', '', '', '', ''),
(14, 'BR-20250907-3080', 'financial admin', 'Financial', 'Cash', 'tax ', 2000, 'tax', 0x313735373235303034345f62696c6c2e706466, 'monthly', '2025-09-30', '2025-10-02 07:34:41', 'test', '', '', '', '', '', ''),
(15, 'BR-20251015-9746', 'admin admin', 'Logistic-2', 'Cash', 'honda civic', 1600000, 'buy a car', '', 'monthly', '2025-10-23', '2025-10-15 00:27:14', 'No submitted document', '', '', '', '', '', ''),
(16, 'BR-20251015-7645', 'admin admin', 'Logistic-1', 'Cash', 'car', 15000, 'car', '', 'monthly', '2025-10-22', '2025-10-15 00:30:02', 'No submitted document', '', '', '', '', '', ''),
(17, 'BR-20251015-4451', 'admin admin', 'Financial', 'Cash', 'test', 350, 'test', '', 'weekly', '2025-10-16', '2025-10-15 00:38:29', 'No submitted document', '', '', '', '', '', ''),
(18, 'BR-20251015-5552', 'Luis Mendoza', 'Logistic-1', 'Cash', 'Postage & Courier', 5000, 'official correspondence', '', 'yearly', '2025-03-04', '2025-10-15 22:23:42', 'no submitted document', '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `requestor_notif`
--

CREATE TABLE `requestor_notif` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `rejection_reason` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requestor_notif`
--

INSERT INTO `requestor_notif` (`id`, `message`, `rejection_reason`, `timestamp`) VALUES
(1, 'Your budget request has been submitted. Reference ID: BR-20260124-6492', '', '2026-01-24 19:25:13'),
(2, 'Your budget request has been submitted. Reference ID: BR-20260124-1119', '', '2026-01-24 21:57:48'),
(3, 'Your budget request has been submitted. Reference ID: BR-20260124-8255', '', '2026-01-24 21:58:29'),
(4, 'Your budget request has been submitted. Reference ID: BR-20260124-9062', '', '2026-01-24 22:13:52'),
(5, 'Your budget request has been submitted. Reference ID: BR-20260124-4458', '', '2026-01-24 23:08:54'),
(6, 'Your budget request has been submitted. Reference ID: BR-20260125-2033', '', '2026-01-25 15:42:10'),
(7, 'Your budget request has been submitted. Reference ID: BR-20260129-4846', '', '2026-01-29 23:40:09'),
(8, 'Your budget request has been submitted. Reference ID: BR-20260130-3052', '', '2026-01-30 16:11:56'),
(9, 'Your budget request has been submitted. Reference ID: BR-20260201-7861', '', '2026-02-01 15:17:47'),
(10, 'Your budget request has been submitted. Reference ID: BR-20260206-6681', '', '2026-02-06 11:03:24'),
(11, 'Your budget request has been submitted. Reference ID: BR-20260206-3670', '', '2026-02-06 13:44:26'),
(12, 'Your budget request has been submitted. Reference ID: BR-20260206-8775', '', '2026-02-06 21:13:53'),
(13, 'Your budget request has been submitted. Reference ID: BR-20260206-4496', '', '2026-02-06 22:39:46'),
(14, 'Your budget request has been submitted. Reference ID: BR-20260206-2589', '', '2026-02-06 22:45:06'),
(15, 'Your budget request has been submitted. Reference ID: BR-20260206-9469', '', '2026-02-06 22:53:54'),
(16, 'Your budget request has been submitted. Reference ID: BR-20260206-8608', '', '2026-02-06 23:06:58'),
(17, 'Your budget request has been submitted. Reference ID: BR-20260206-4191', '', '2026-02-06 23:09:09'),
(18, 'Your budget request has been submitted. Reference ID: BR-20260206-3890', '', '2026-02-06 23:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role`, `description`) VALUES
(4, 'financial admin', 'Full access to all financial modules and user management'),
(5, 'budget manager', 'Manages budget allocations and oversight'),
(6, 'disburse officer', 'Handles payment disbursements and approvals'),
(7, 'collector', 'Manages collections and receivables'),
(8, 'auditor', 'Views audit logs and compliance reports'),
(4, 'financial admin', 'Full access to all financial modules and user management'),
(5, 'budget manager', 'Manages budget allocations and oversight'),
(6, 'disburse officer', 'Handles payment disbursements and approvals'),
(7, 'collector', 'Manages collections and receivables'),
(8, 'auditor', 'Views audit logs and compliance reports');

-- --------------------------------------------------------

--
-- Table structure for table `saved_reports`
--

CREATE TABLE `saved_reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `format` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_reports`
--

INSERT INTO `saved_reports` (`id`, `report_name`, `report_type`, `from_date`, `to_date`, `format`, `created_at`) VALUES
(2, 'Balance Sheet - Annual 2026', 'balance', '2026-01-01', '2026-12-31', 'pdf', '2026-02-11 02:22:31'),
(4, 'Income Statement - Annual 2026', 'income', '2026-01-01', '2026-12-31', 'pdf', '2026-02-11 02:24:00'),
(6, 'Trial Balance - (Sep 01, 2025 - Feb 11, 2026)', 'trial', '2025-09-01', '2026-02-11', 'pdf', '2026-02-11 03:33:10'),
(7, 'Trial Balance - January 2026', 'trial', '2026-01-01', '2026-01-31', 'pdf', '2026-02-11 03:33:51'),
(8, 'Income Statement - (Dec 31, 2025 - Feb 11, 2026)', 'income', '2025-12-31', '2026-02-11', 'pdf', '2026-02-11 03:38:07'),
(10, 'Trial Balance - January 2026', 'trial', '2026-01-01', '2026-01-31', 'pdf', '2026-02-11 03:41:13'),
(11, 'Income Statement - February 2026', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 03:47:55'),
(12, 'Trial Balance - February 2026', 'trial', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 03:48:04'),
(13, 'Trial Balance - Annual 2026', 'trial', '2026-01-01', '2026-12-31', 'pdf', '2026-02-11 03:49:15'),
(14, 'Cash Flow Statement - Annual 2026', 'cashflow', '2026-01-01', '2026-12-31', 'pdf', '2026-02-11 04:06:53'),
(15, 'Cash Flow Statement - Custom (Jan 01, 2025 - Dec 31, 2025)', 'cashflow', '2025-01-01', '2025-12-31', 'pdf', '2026-02-11 04:09:22'),
(16, 'Cash Flow Statement - February 2026', 'cashflow', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:10:42'),
(17, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:40:49'),
(18, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:40:51'),
(19, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:40:53'),
(20, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:41:01'),
(21, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:41:02'),
(22, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:41:02'),
(23, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:41:02'),
(24, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:41:14'),
(25, 'Income Statement - ', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:41:22'),
(26, 'Income Statement - January 2026', 'income', '2026-01-01', '2026-01-31', 'pdf', '2026-02-11 04:44:48'),
(27, 'Cash Flow Statement - Annual 2026', 'cashflow', '2026-01-01', '2026-12-31', 'pdf', '2026-02-11 04:45:26'),
(28, 'Income Statement - Annual 2026', 'income', '2026-01-01', '2026-12-31', 'pdf', '2026-02-11 04:47:15'),
(29, 'Income Statement - February 2026', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-11 04:48:58'),
(30, 'Income Statement - February 2026', 'income', '2026-02-01', '2026-02-28', 'excel', '2026-02-11 05:20:59'),
(31, 'Income Statement - February 2026', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-13 03:05:08'),
(32, 'Income Statement - February 2026', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-13 03:05:59'),
(33, 'Income Statement - February 2026', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-13 03:06:09'),
(34, 'Income Statement - February 2026', 'income', '2026-02-01', '2026-02-28', 'pdf', '2026-02-13 06:54:51'),
(35, 'Cash Flow Statement - January 2026', 'cashflow', '2026-01-01', '2026-01-31', 'pdf', '2026-02-13 10:27:27'),
(36, 'Trial Balance - January 2026', 'trial', '2026-01-01', '2026-01-31', 'pdf', '2026-02-13 10:29:47'),
(37, 'Balance Sheet - Annual 2026', 'balance', '2026-01-01', '2026-12-31', 'pdf', '2026-02-13 10:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `tr`
--

CREATE TABLE `tr` (
  `id` int(11) NOT NULL,
  `reference_id` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `requested_department` varchar(255) NOT NULL,
  `expense_categories` varchar(255) NOT NULL,
  `mode_of_payment` varchar(255) NOT NULL,
  `amount` bigint(24) NOT NULL,
  `description` text NOT NULL,
  `document` varchar(255) NOT NULL,
  `payment_due` date NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `bank_account_name` varchar(255) NOT NULL,
  `bank_account_number` varchar(20) NOT NULL,
  `ecash_provider` varchar(100) NOT NULL,
  `ecash_account_name` varchar(100) NOT NULL,
  `ecash_account_number` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected','disbursed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tr`
--

INSERT INTO `tr` (`id`, `reference_id`, `account_name`, `requested_department`, `expense_categories`, `mode_of_payment`, `amount`, `description`, `document`, `payment_due`, `bank_name`, `bank_account_name`, `bank_account_number`, `ecash_provider`, `ecash_account_name`, `ecash_account_number`, `status`) VALUES
(44, 'TR-1033-2025', 'BIR', 'Financial', 'Tax Payment', 'Cash', 12000, 'Income Tax', 'file-Gaa8W3gdU3gis5rxgBboEB_sticker.png', '2025-02-28', '', '', '', '', '', '', 'disbursed'),
(45, 'TR-1614-2025', 'PhilBank', 'Financial', 'Tax Payment', 'Cash', 10000, 'Business Permit Fees', 'ALLORDO RESUME.pdf', '2025-03-13', '', '', '', '', '', '', 'disbursed'),
(46, 'TR-7827-2025', 'PhilBank', 'Financial', 'Tax Payment', 'Cash', 10000, 'Excise Tax', '', '2025-04-04', '', '', '', '', '', '', 'pending'),
(44, 'TR-1033-2025', 'BIR', 'Financial', 'Tax Payment', 'Cash', 12000, 'Income Tax', 'file-Gaa8W3gdU3gis5rxgBboEB_sticker.png', '2025-02-28', '', '', '', '', '', '', 'disbursed'),
(45, 'TR-1614-2025', 'PhilBank', 'Financial', 'Tax Payment', 'Cash', 10000, 'Business Permit Fees', 'ALLORDO RESUME.pdf', '2025-03-13', '', '', '', '', '', '', 'disbursed'),
(46, 'TR-7827-2025', 'PhilBank', 'Financial', 'Tax Payment', 'Cash', 10000, 'Excise Tax', '', '2025-04-04', '', '', '', '', '', '', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `userss`
--

CREATE TABLE `userss` (
  `id` int(15) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gname` varchar(30) NOT NULL,
  `minitial` varchar(24) NOT NULL,
  `surname` varchar(25) NOT NULL,
  `address` varchar(255) NOT NULL,
  `age` int(2) NOT NULL,
  `contact` varchar(25) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'New User',
  `is_logged_in` tinyint(1) DEFAULT 0,
  `pin` varchar(255) NOT NULL,
  `account_status` enum('active','locked','suspended','new user','deactivated') DEFAULT 'new user',
  `registered_at` datetime DEFAULT current_timestamp(),
  `failed_attempts` int(11) DEFAULT 0,
  `profile_picture` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userss`
--

INSERT INTO `userss` (`id`, `username`, `password`, `gname`, `minitial`, `surname`, `address`, `age`, `contact`, `email`, `role`, `is_logged_in`, `pin`, `account_status`, `registered_at`, `failed_attempts`, `profile_picture`, `updated_at`, `otp_code`, `otp_expiry`) VALUES
(1, 'budget', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'budget', '', 'manager', 'address', 20, '09123456789', 'emi123@gmail.com', 'budget manager', 0, '123456', 'active', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 07:17:37', '212515', '2025-10-15 07:00:46'),
(2, 'disburse', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'disburse', '', 'officer', 'address', 20, '09123456789', 'emi123@gmail.com', 'financial admin', 1, '123456', 'suspended', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 07:17:29', NULL, NULL),
(3, 'Supremo360', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'Ethan', 'G.', 'Magsaysay', 'address', 35, '09123456789', 'viahalefin01@gmail.com', 'financial admin', 1, '123456', 'active', '2025-09-18 19:42:03', 0, 'uploads/profile_pictures/user_3_1760100787.jpeg', '2026-02-14 08:27:09', NULL, NULL),
(4, 'auditor', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'auditor', '', 'auditor', 'address', 20, '09123456789', '', 'auditor', 0, '123456', 'deactivated', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 21:32:30', NULL, NULL),
(5, 'Angge246', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'collector', 'V.', 'Montefalco', '#45 E. Rodriguez Jr. Avenue Brgy. Ugong Norte, Quezon City', 20, '09123456789', 'emi123@gmail.com', 'collector', 0, '123456', 'active', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 07:25:32', NULL, NULL),
(6, 'ruby', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'Ruby', '', 'Chan', 'address', 21, '09123456789', '', 'financial admin', 0, '246810', 'deactivated', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 21:01:03', NULL, NULL),
(30, 'budget', '$2y$10$gB426LIYiNN.ZbqFYJvtXOprjgZKMvScY6ShWJsHnJjnA/Y4JHdpy', 'budget', '', 'manager', 'budget', 21, '09123456789', 'budgetmanager@gmail.com', '', 0, '123456', 'new user', '2025-10-10 22:05:52', 0, NULL, '2025-10-14 22:50:46', '212515', '2025-10-15 07:00:46'),
(31, 'Emi960', '$2y$10$kSpbkIHQWJ3NoHU/0a2QAeACNyk9s3qg36Zup7FrEIoz4p491Q7Xu', 'Juli', '', 'Ace', 'blk 20 lot 23 February St., Brgy. 14, Novaliches, Quezon City', 21, '09123456789', 'emi123@gmail.com', 'financial admin', 0, '123456', 'locked', '2025-10-12 18:11:07', 0, NULL, '2025-10-15 21:31:46', NULL, NULL),
(34, 'JuanD20', '$2y$10$5rTphxResVcXv6V95zNID.9KcJyiEJZs.4VxGcYGmzk8brGeP5vsq', 'Juan', 'G.', 'Dela Cruz', 'blk 20 lot 23 February St., Brgy. 14, Novaliches, Quezon City', 25, '09123456789', 'viahalefin02@gmail.com', 'budget manager', 0, '123456', 'active', '2025-10-15 05:16:06', 0, 'uploads/profile_pictures/user_34_1760511377.jpg', '2026-02-12 09:50:46', NULL, NULL),
(35, 'MayC320', '$2y$10$QG7MH/o8MACdUS5ifXdL8.5FN7w6zP4fCY/v1enQbiyRha5GsRYWi', 'Mary', 'D.', 'Batumbakal', '123 Mabini Street   Barangay Malinis   Quezon City, Metro Manila   1100 Philippines', 30, '09246810123', 'viahalefin05@gmail.com', 'auditor', 1, '123123', 'active', '2025-10-15 07:44:30', 0, NULL, '2025-10-15 22:58:03', NULL, NULL),
(36, 'Maria453', '$2y$10$qfhA6HRFReclgcCKdykBQekl9i72Ds.TuRbywnFcNkIXo32XiYM0y', 'Maria', 'O.', 'Santos', 'Blk 12 Lot 7, Mabuhay Compound Sitio Bagong Pag-asa, Quezon City', 28, '094583211147', 'viahalefin03@gmail.com', 'disburse officer', 1, '123456', 'active', '2025-10-15 15:23:36', 0, NULL, '2025-10-15 22:45:21', NULL, NULL),
(37, 'Angge678', '$2y$10$WFcoZxkXehIhfYr4OOoy4uDmCVff6twTEMX465Ls5Hk0X2UsWROfa', 'Angelica', 'V.', 'Montefalco', '#45 E. Rodriguez Jr. Avenue Brgy. Ugong Norte, Quezon City', 26, '09123456789', 'viahalefin04@gmail.com', 'collector', 0, '123456', 'active', '2025-10-15 15:26:42', 0, NULL, '2025-10-16 21:37:18', NULL, NULL),
(1, 'budget', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'budget', '', 'manager', 'address', 20, '09123456789', 'emi123@gmail.com', 'budget manager', 0, '123456', 'active', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 07:17:37', '212515', '2025-10-15 07:00:46'),
(2, 'disburse', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'disburse', '', 'officer', 'address', 20, '09123456789', 'emi123@gmail.com', 'financial admin', 1, '123456', 'suspended', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 07:17:29', NULL, NULL),
(3, 'Supremo360', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'Ethan', 'G.', 'Magsaysay', 'address', 35, '09123456789', 'viahalefin01@gmail.com', 'financial admin', 1, '123456', 'active', '2025-09-18 19:42:03', 0, 'uploads/profile_pictures/user_3_1760100787.jpeg', '2026-02-14 08:27:09', NULL, NULL),
(4, 'auditor', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'auditor', '', 'auditor', 'address', 20, '09123456789', '', 'auditor', 0, '123456', 'deactivated', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 21:32:30', NULL, NULL),
(5, 'Angge246', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'collector', 'V.', 'Montefalco', '#45 E. Rodriguez Jr. Avenue Brgy. Ugong Norte, Quezon City', 20, '09123456789', 'emi123@gmail.com', 'collector', 0, '123456', 'active', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 07:25:32', NULL, NULL),
(6, 'ruby', '$2y$10$VI6Vv98QtjDh/dZrO614CektcAzmIlzyOARLpN8TPgtqVctKH7jcW', 'Ruby', '', 'Chan', 'address', 21, '09123456789', '', 'financial admin', 0, '246810', 'deactivated', '2025-09-18 19:42:03', 0, NULL, '2025-10-15 21:01:03', NULL, NULL),
(30, 'budget', '$2y$10$gB426LIYiNN.ZbqFYJvtXOprjgZKMvScY6ShWJsHnJjnA/Y4JHdpy', 'budget', '', 'manager', 'budget', 21, '09123456789', 'budgetmanager@gmail.com', '', 0, '123456', '', '2025-10-10 22:05:52', 0, NULL, '2025-10-14 22:50:46', '212515', '2025-10-15 07:00:46'),
(31, 'Emi960', '$2y$10$kSpbkIHQWJ3NoHU/0a2QAeACNyk9s3qg36Zup7FrEIoz4p491Q7Xu', 'Juli', '', 'Ace', 'blk 20 lot 23 February St., Brgy. 14, Novaliches, Quezon City', 21, '09123456789', 'emi123@gmail.com', 'financial admin', 0, '123456', 'locked', '2025-10-12 18:11:07', 0, NULL, '2025-10-15 21:31:46', NULL, NULL),
(34, 'JuanD20', '$2y$10$5rTphxResVcXv6V95zNID.9KcJyiEJZs.4VxGcYGmzk8brGeP5vsq', 'Juan', 'G.', 'Dela Cruz', 'blk 20 lot 23 February St., Brgy. 14, Novaliches, Quezon City', 25, '09123456789', 'viahalefin02@gmail.com', 'budget manager', 0, '123456', 'active', '2025-10-15 05:16:06', 0, 'uploads/profile_pictures/user_34_1760511377.jpg', '2026-02-12 09:50:46', NULL, NULL),
(35, 'MayC320', '$2y$10$QG7MH/o8MACdUS5ifXdL8.5FN7w6zP4fCY/v1enQbiyRha5GsRYWi', 'Mary', 'D.', 'Batumbakal', '123 Mabini Street   Barangay Malinis   Quezon City, Metro Manila   1100 Philippines', 30, '09246810123', 'viahalefin05@gmail.com', 'auditor', 1, '123123', 'active', '2025-10-15 07:44:30', 0, NULL, '2025-10-15 22:58:03', NULL, NULL),
(36, 'Maria453', '$2y$10$qfhA6HRFReclgcCKdykBQekl9i72Ds.TuRbywnFcNkIXo32XiYM0y', 'Maria', 'O.', 'Santos', 'Blk 12 Lot 7, Mabuhay Compound Sitio Bagong Pag-asa, Quezon City', 28, '094583211147', 'viahalefin03@gmail.com', 'disburse officer', 1, '123456', 'active', '2025-10-15 15:23:36', 0, NULL, '2025-10-15 22:45:21', NULL, NULL),
(37, 'Angge678', '$2y$10$WFcoZxkXehIhfYr4OOoy4uDmCVff6twTEMX465Ls5Hk0X2UsWROfa', 'Angelica', 'V.', 'Montefalco', '#45 E. Rodriguez Jr. Avenue Brgy. Ugong Norte, Quezon City', 26, '09123456789', 'viahalefin04@gmail.com', 'collector', 0, '123456', 'active', '2025-10-15 15:26:42', 0, NULL, '2025-10-16 21:37:18', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `permission`) VALUES
(2, 6, 'AUTHOREMAIL');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_ai_validation_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_ai_validation_stats` (
`validation_date` date
,`risk_level` enum('LOW','MEDIUM','HIGH')
,`total_validations` bigint(21)
,`avg_risk_score` decimal(14,4)
,`blocked_count` bigint(21)
,`review_count` bigint(21)
,`allowed_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_high_risk_payouts_today`
-- (See below for the actual view)
--
CREATE TABLE `v_high_risk_payouts_today` (
`payout_id` varchar(50)
,`risk_level` enum('LOW','MEDIUM','HIGH')
,`risk_score` int(11)
,`issues` text
,`recommendation` varchar(50)
,`scheduled_date` datetime
,`status` varchar(100)
,`checked_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_payment_schedule_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_payment_schedule_summary` (
`schedule_date` date
,`risk_level` enum('LOW','MEDIUM','HIGH')
,`total_payouts` bigint(21)
,`auto_approved_count` decimal(22,0)
,`review_required_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Structure for view `v_ai_validation_stats`
--
DROP TABLE IF EXISTS `v_ai_validation_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`FINRocxz315`@`localhost` SQL SECURITY DEFINER VIEW `v_ai_validation_stats`  AS SELECT cast(`ai_validation_logs`.`checked_at` as date) AS `validation_date`, `ai_validation_logs`.`risk_level` AS `risk_level`, count(0) AS `total_validations`, avg(`ai_validation_logs`.`risk_score`) AS `avg_risk_score`, count(case when `ai_validation_logs`.`recommendation` = 'BLOCK_PAYOUT' then 1 end) AS `blocked_count`, count(case when `ai_validation_logs`.`recommendation` = 'REQUIRE_REVIEW' then 1 end) AS `review_count`, count(case when `ai_validation_logs`.`recommendation` = 'ALLOW_PAYOUT' then 1 end) AS `allowed_count` FROM `ai_validation_logs` WHERE `ai_validation_logs`.`checked_at` >= curdate() - interval 30 day GROUP BY cast(`ai_validation_logs`.`checked_at` as date), `ai_validation_logs`.`risk_level` ORDER BY cast(`ai_validation_logs`.`checked_at` as date) DESC, `ai_validation_logs`.`risk_level` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_high_risk_payouts_today`
--
DROP TABLE IF EXISTS `v_high_risk_payouts_today`;

CREATE ALGORITHM=UNDEFINED DEFINER=`FINRocxz315`@`localhost` SQL SECURITY DEFINER VIEW `v_high_risk_payouts_today`  AS SELECT `avl`.`payout_id` AS `payout_id`, `avl`.`risk_level` AS `risk_level`, `avl`.`risk_score` AS `risk_score`, `avl`.`issues` AS `issues`, `avl`.`recommendation` AS `recommendation`, `ps`.`scheduled_date` AS `scheduled_date`, `ps`.`status` AS `status`, `avl`.`checked_at` AS `checked_at` FROM (`ai_validation_logs` `avl` left join `payment_schedule` `ps` on(`avl`.`payout_id` = `ps`.`payout_id`)) WHERE `avl`.`risk_level` = 'HIGH' AND cast(`avl`.`checked_at` as date) = curdate() ORDER BY `avl`.`risk_score` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_payment_schedule_summary`
--
DROP TABLE IF EXISTS `v_payment_schedule_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`FINRocxz315`@`localhost` SQL SECURITY DEFINER VIEW `v_payment_schedule_summary`  AS SELECT cast(`payment_schedule`.`scheduled_date` as date) AS `schedule_date`, `payment_schedule`.`risk_level` AS `risk_level`, count(0) AS `total_payouts`, sum(case when `payment_schedule`.`auto_approved` = 1 then 1 else 0 end) AS `auto_approved_count`, sum(case when `payment_schedule`.`requires_review` = 1 then 1 else 0 end) AS `review_required_count` FROM `payment_schedule` WHERE `payment_schedule`.`scheduled_date` >= curdate() GROUP BY cast(`payment_schedule`.`scheduled_date` as date), `payment_schedule`.`risk_level` ORDER BY cast(`payment_schedule`.`scheduled_date` as date) ASC, `payment_schedule`.`risk_level` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_audit_logs`
--
ALTER TABLE `ai_audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ai_system_health`
--
ALTER TABLE `ai_system_health`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_checked` (`checked_at`);

--
-- Indexes for table `ai_validation_logs`
--
ALTER TABLE `ai_validation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payout` (`payout_id`),
  ADD KEY `idx_risk` (`risk_level`),
  ADD KEY `idx_recommendation` (`recommendation`),
  ADD KEY `idx_checked` (`checked_at`),
  ADD KEY `idx_validation_date_risk` (`checked_at`,`risk_level`);

--
-- Indexes for table `ar`
--
ALTER TABLE `ar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archive_payables`
--
ALTER TABLE `archive_payables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `audit_reports`
--
ALTER TABLE `audit_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_alerts`
--
ALTER TABLE `budget_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_forecasts`
--
ALTER TABLE `budget_forecasts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forecast_period` (`forecast_period`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `budget_plans`
--
ALTER TABLE `budget_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_year` (`plan_year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`plan_type`),
  ADD KEY `idx_plan_code_main` (`plan_code`);

--
-- Indexes for table `budget_plan_archive`
--
ALTER TABLE `budget_plan_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_plan` (`original_plan_id`),
  ADD KEY `idx_archived_at` (`archived_at`);

--
-- Indexes for table `budget_plan_snapshots`
--
ALTER TABLE `budget_plan_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plan_id` (`plan_id`),
  ADD KEY `idx_snapshot_date` (`snapshot_date`);

--
-- Indexes for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proposal_code` (`proposal_code`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_reference_id` (`reference_id`),
  ADD KEY `idx_plan_code` (`plan_code`);

--
-- Indexes for table `budget_proposal_items`
--
ALTER TABLE `budget_proposal_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `budget_request`
--
ALTER TABLE `budget_request`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_id` (`reference_id`);

--
-- Indexes for table `chart_of_accounts_hierarchy`
--
ALTER TABLE `chart_of_accounts_hierarchy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dr`
--
ALTER TABLE `dr`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `driver_payouts`
--
ALTER TABLE `driver_payouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payout_id` (`payout_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_driver` (`driver_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `driver_wallets`
--
ALTER TABLE `driver_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `driver_id` (`driver_id`),
  ADD UNIQUE KEY `wallet_id` (`wallet_id`),
  ADD KEY `driver_id_2` (`driver_id`),
  ADD KEY `wallet_id_2` (`wallet_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `error_detection_logs`
--
ALTER TABLE `error_detection_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_record` (`record_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_detected` (`detected_at`);

--
-- Indexes for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gl_account` (`gl_account_id`,`transaction_date`),
  ADD KEY `idx_journal` (`journal_entry_id`),
  ADD KEY `idx_reference` (`reference_id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_account_type` (`account_type`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `journal_number` (`journal_number`),
  ADD KEY `idx_journal_number` (`journal_number`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_entry` (`journal_entry_id`),
  ADD KEY `idx_gl_account` (`gl_account_id`),
  ADD KEY `idx_account_type` (`account_type`);

--
-- Indexes for table `pa`
--
ALTER TABLE `pa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payables_receipts`
--
ALTER TABLE `payables_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reference_id` (`reference_id`),
  ADD KEY `original_reference_id` (`original_reference_id`),
  ADD KEY `disbursed_date` (`disbursed_date`);

--
-- Indexes for table `payment_schedule`
--
ALTER TABLE `payment_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payout` (`payout_id`),
  ADD KEY `idx_schedule` (`scheduled_date`),
  ADD KEY `idx_risk` (`risk_level`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_schedule_risk_status` (`scheduled_date`,`risk_level`,`status`);

--
-- Indexes for table `payout_tracking`
--
ALTER TABLE `payout_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payout` (`payout_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_tracking_payout_action` (`payout_id`,`action`,`created_at`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `proposal_comments`
--
ALTER TABLE `proposal_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proposal_id` (`proposal_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `proposal_stages`
--
ALTER TABLE `proposal_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proposal_id` (`proposal_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `reimbursements`
--
ALTER TABLE `reimbursements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_id` (`report_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_submitted_date` (`submitted_date`);

--
-- Indexes for table `reimbursement_receipts`
--
ALTER TABLE `reimbursement_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_id` (`report_id`);

--
-- Indexes for table `requestor_notif`
--
ALTER TABLE `requestor_notif`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saved_reports`
--
ALTER TABLE `saved_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `ai_audit_logs`
--
ALTER TABLE `ai_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_system_health`
--
ALTER TABLE `ai_system_health`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_validation_logs`
--
ALTER TABLE `ai_validation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ar`
--
ALTER TABLE `ar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `audit_reports`
--
ALTER TABLE `audit_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `budget_alerts`
--
ALTER TABLE `budget_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `budget_forecasts`
--
ALTER TABLE `budget_forecasts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `budget_plans`
--
ALTER TABLE `budget_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=297;

--
-- AUTO_INCREMENT for table `budget_plan_archive`
--
ALTER TABLE `budget_plan_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `budget_plan_snapshots`
--
ALTER TABLE `budget_plan_snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `budget_proposals`
--
ALTER TABLE `budget_proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `budget_proposal_items`
--
ALTER TABLE `budget_proposal_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `budget_request`
--
ALTER TABLE `budget_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `dr`
--
ALTER TABLE `dr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=889;

--
-- AUTO_INCREMENT for table `driver_payouts`
--
ALTER TABLE `driver_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `driver_wallets`
--
ALTER TABLE `driver_wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `error_detection_logs`
--
ALTER TABLE `error_detection_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_ledger`
--
ALTER TABLE `general_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=327;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=329;

--
-- AUTO_INCREMENT for table `pa`
--
ALTER TABLE `pa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=452;

--
-- AUTO_INCREMENT for table `payables_receipts`
--
ALTER TABLE `payables_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=272;

--
-- AUTO_INCREMENT for table `payment_schedule`
--
ALTER TABLE `payment_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payout_tracking`
--
ALTER TABLE `payout_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `proposal_comments`
--
ALTER TABLE `proposal_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `proposal_stages`
--
ALTER TABLE `proposal_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reimbursements`
--
ALTER TABLE `reimbursements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `requestor_notif`
--
ALTER TABLE `requestor_notif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `saved_reports`
--
ALTER TABLE `saved_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budget_plan_snapshots`
--
ALTER TABLE `budget_plan_snapshots`
  ADD CONSTRAINT `budget_plan_snapshots_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `budget_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_proposal_items`
--
ALTER TABLE `budget_proposal_items`
  ADD CONSTRAINT `budget_proposal_items_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `budget_proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD CONSTRAINT `general_ledger_ibfk_1` FOREIGN KEY (`gl_account_id`) REFERENCES `chart_of_accounts_hierarchy` (`id`),
  ADD CONSTRAINT `general_ledger_ibfk_2` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `journal_entry_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_entry_lines_ibfk_2` FOREIGN KEY (`gl_account_id`) REFERENCES `chart_of_accounts_hierarchy` (`id`);

--
-- Constraints for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
