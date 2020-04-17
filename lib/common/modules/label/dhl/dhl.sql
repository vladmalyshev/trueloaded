-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 05, 2019 at 08:14 PM
-- Server version: 10.1.37-MariaDB-1~jessie
-- PHP Version: 7.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `andrew_corporate`
--

-- --------------------------------------------------------

--
-- Table structure for table `dhl_global_product_codes`
--

DROP TABLE IF EXISTS `dhl_global_product_codes`;
CREATE TABLE `dhl_global_product_codes` (
  `global_product_code` char(1) NOT NULL DEFAULT '',
  `global_product_name` varchar(32) NOT NULL DEFAULT '',
  `product_content_code` char(3) NOT NULL DEFAULT '',
  `doc_indicator` char(1) NOT NULL,
  `global_product_title` varchar(128) NOT NULL,
  `global_product_status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `dhl_global_product_codes`
--

INSERT INTO `dhl_global_product_codes` (`global_product_code`, `global_product_name`, `product_content_code`, `doc_indicator`, `global_product_title`, `global_product_status`) VALUES
('0', 'LOGISTICS SERVICES', 'LOG', 'A', '', 0),
('1', 'DOMESTIC EXPRESS 12:00', 'DOT', 'Y', '', 0),
('2', 'B2C', 'BTC', 'Y', '', 0),
('3', 'B2C', 'B2C', 'N', '', 0),
('4', 'JETLINE', 'NFO', 'N', '', 0),
('5', 'SPRINTLINE', 'SPL', 'Y', '', 0),
('7', 'EXPRESS EASY', 'XED', 'Y', '', 0),
('8', 'EXPRESS EASY', 'XEP', 'N', '', 0),
('9', 'EUROPACK', 'EPA', 'Y', '', 0),
('A', 'AUTO REVERSALS', 'N/A', 'A', '', 0),
('B', 'BREAKBULK EXPRESS', 'BBX', 'Y', '', 0),
('C', 'MEDICAL EXPRESS', 'CMX', 'Y', '', 0),
('D', 'EXPRESS WORLDWIDE', 'DOX', 'Y', '', 0),
('E', 'EXPRESS 9:00', 'TDE', 'N', '', 0),
('F', 'FREIGHT WORLDWIDE', 'FRT', 'N', '', 0),
('G', 'DOMESTIC ECONOMY SELECT', 'DES', 'Y', '', 0),
('H', 'ECONOMY SELECT', 'ESI', 'N', '', 0),
('I', 'DOMESTIC EXPRESS 9:00', 'DOK', 'Y', '', 0),
('J', 'JUMBO BOX', 'JBX', 'N', '', 0),
('K', 'EXPRESS 9:00', 'TDK', 'Y', '', 0),
('L', 'EXPRESS 10:30', 'TDL', 'Y', '', 0),
('M', 'EXPRESS 10:30', 'TDM', 'N', '', 0),
('N', 'DOMESTIC EXPRESS', 'DOM', 'Y', '', 0),
('O', 'DOMESTIC EXPRESS 10:30', 'DOL', 'Y', '', 0),
('P', 'EXPRESS WORLDWIDE', 'WPX', 'N', '', 0),
('Q', 'MEDICAL EXPRESS', 'WMX', 'N', '', 0),
('R', 'GLOBALMAIL BUSINESS', 'GMB', 'Y', '', 0),
('S', 'SAME DAY', 'SDX', 'Y', '', 0),
('T', 'EXPRESS 12:00', 'TDT', 'Y', '', 0),
('U', 'EXPRESS WORLDWIDE', 'ECX', 'Y', '', 0),
('V', 'EUROPACK', 'EPP', 'N', '', 0),
('W', 'ECONOMY SELECT', 'ESU', 'Y', '', 0),
('X', 'EXPRESS ENVELOPE', 'XPD', 'Y', '', 0),
('Y', 'EXPRESS 12:00', 'TDY', 'N', '', 0),
('Z', 'Destination Charges', 'N/A', 'A', '', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dhl_global_product_codes`
--
ALTER TABLE `dhl_global_product_codes`
  ADD PRIMARY KEY (`global_product_code`) USING BTREE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
