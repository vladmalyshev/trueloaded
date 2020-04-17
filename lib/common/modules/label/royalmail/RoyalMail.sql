-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 01, 2019 at 02:44 PM
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
-- Table structure for table `royal_mail_enhancement_types`
--

DROP TABLE IF EXISTS `royal_mail_enhancement_types`;
CREATE TABLE `royal_mail_enhancement_types` (
  `enhancement_types_code` tinyint(1) NOT NULL DEFAULT '0',
  `enhancement_types_name` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `royal_mail_enhancement_types`
--

INSERT INTO `royal_mail_enhancement_types` (`enhancement_types_code`, `enhancement_types_name`) VALUES
(1, 'Consequential Loss £1000'),
(2, 'Consequential Loss £2500'),
(3, 'Consequential Loss £5000'),
(4, 'Consequential Loss £7500'),
(5, 'Consequential Loss £10000'),
(6, 'Recorded'),
(11, 'Consequential Loss £750'),
(12, 'Tracked Signature'),
(13, 'SMS Notification'),
(14, 'E-Mail Notification'),
(16, 'SMS & E-Mail Notification'),
(22, 'Local Collect'),
(24, 'Saturday Guaranteed');

-- --------------------------------------------------------

--
-- Table structure for table `royal_mail_service_formats`
--

DROP TABLE IF EXISTS `royal_mail_service_formats`;
CREATE TABLE `royal_mail_service_formats` (
  `service_types_code` char(1) NOT NULL DEFAULT '',
  `service_formats_code` char(1) NOT NULL DEFAULT '',
  `service_formats_name` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `royal_mail_service_formats`
--

INSERT INTO `royal_mail_service_formats` (`service_types_code`, `service_formats_code`, `service_formats_name`) VALUES
('1', 'F', 'Inland Large Letter'),
('1', 'L', 'Inland Letter'),
('1', 'N', 'Inland format Not Applicable'),
('1', 'P', 'Inland Parcel'),
('2', 'F', 'Inland Large Letter'),
('2', 'L', 'Inland Letter'),
('2', 'N', 'Inland format Not Applicable'),
('2', 'P', 'Inland Parcel'),
('D', 'F', 'Inland Large Letter'),
('D', 'L', 'Inland Letter'),
('D', 'N', 'Inland format Not Applicable'),
('D', 'P', 'Inland Parcel'),
('H', 'F', 'Inland Large Letter'),
('H', 'L', 'Inland Letter'),
('H', 'N', 'Inland format Not Applicable'),
('H', 'P', 'Inland Parcel'),
('I', 'E', 'International Parcel'),
('I', 'G', 'International Large Letter'),
('I', 'N', 'International Format Not Applicable'),
('I', 'P', 'International Letter'),
('R', 'F', 'Inland Large Letter'),
('R', 'L', 'Inland Letter'),
('R', 'N', 'Inland format Not Applicable'),
('R', 'P', 'Inland Parcel'),
('T', 'F', 'Inland Large Letter'),
('T', 'L', 'Inland Letter'),
('T', 'N', 'Inland format Not Applicable'),
('T', 'P', 'Inland Parcel');

-- --------------------------------------------------------

--
-- Table structure for table `royal_mail_service_matrix`
--

DROP TABLE IF EXISTS `royal_mail_service_matrix`;
CREATE TABLE `royal_mail_service_matrix` (
  `service_types_code` char(1) NOT NULL DEFAULT '',
  `service_offerings_code` char(3) NOT NULL DEFAULT '',
  `service_formats_code` char(1) NOT NULL DEFAULT '',
  `enhancement_types_code` tinyint(1) NOT NULL DEFAULT '0',
  `service_matrix_name` varchar(128) NOT NULL DEFAULT '',
  `service_matrix_status` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `royal_mail_service_matrix`
--

INSERT INTO `royal_mail_service_matrix` (`service_types_code`, `service_offerings_code`, `service_formats_code`, `enhancement_types_code`, `service_matrix_name`, `service_matrix_status`) VALUES
('1', 'CRL', 'F', 0, '', 0),
('1', 'CRL', 'F', 6, '', 0),
('1', 'CRL', 'P', 0, '', 0),
('1', 'CRL', 'P', 6, '', 0),
('1', 'FS1', 'F', 0, '', 0),
('1', 'PK1', 'P', 0, '', 0),
('1', 'PK1', 'P', 6, '', 0),
('1', 'PK3', 'F', 0, '', 0),
('1', 'PK3', 'F', 6, '', 0),
('1', 'PK3', 'P', 0, '', 0),
('1', 'PK3', 'P', 6, '', 0),
('1', 'PK9', 'F', 0, '', 0),
('1', 'PK9', 'F', 6, '', 0),
('1', 'PPF', 'P', 0, '', 0),
('1', 'PPF', 'P', 6, '', 0),
('1', 'PX0', 'A', 0, '', 0),
('1', 'PX0', 'F', 0, '', 0),
('1', 'PX0', 'P', 0, '', 0),
('1', 'PX1', 'A', 0, '', 0),
('1', 'PX1', 'F', 0, '', 0),
('1', 'PX1', 'P', 0, '', 0),
('1', 'PY1', 'F', 0, '', 0),
('1', 'PY3', 'F', 0, '', 0),
('1', 'PZ4', 'A', 0, '', 0),
('1', 'PZ4', 'F', 0, '', 0),
('1', 'PZ4', 'P', 0, '', 0),
('1', 'RM1', 'F', 0, '', 0),
('1', 'RM1', 'F', 6, '', 0),
('1', 'RM2', 'P', 0, '', 0),
('1', 'RM2', 'P', 6, '', 0),
('1', 'RM5', 'F', 0, '', 0),
('1', 'RM5', 'F', 6, '', 0),
('1', 'RM5', 'P', 0, '', 0),
('1', 'RM5', 'P', 6, '', 0),
('1', 'RM7', 'F', 0, '', 0),
('1', 'RM7', 'F', 6, '', 0),
('1', 'RM8', 'P', 0, '', 0),
('1', 'RM8', 'P', 6, '', 0),
('1', 'STL', 'F', 0, '', 0),
('1', 'STL', 'F', 6, '', 0),
('1', 'STL', 'L', 0, '', 0),
('1', 'STL', 'L', 6, '', 0),
('1', 'STL', 'P', 0, '', 0),
('1', 'STL', 'P', 6, '', 0),
('2', 'CRL', 'F', 0, '', 0),
('2', 'CRL', 'F', 6, '', 0),
('2', 'CRL', 'P', 0, '', 0),
('2', 'CRL', 'P', 6, '', 0),
('2', 'FS2', 'F', 0, '', 0),
('2', 'PK0', 'F', 0, '', 0),
('2', 'PK0', 'F', 6, '', 0),
('2', 'PK2', 'P', 0, '', 0),
('2', 'PK2', 'P', 6, '', 0),
('2', 'PK4', 'F', 0, '', 0),
('2', 'PK4', 'F', 6, '', 0),
('2', 'PK4', 'P', 0, '', 0),
('2', 'PK4', 'P', 6, '', 0),
('2', 'PPF', 'P', 0, '', 0),
('2', 'PPF', 'P', 6, '', 0),
('2', 'PX2', 'A', 0, '', 0),
('2', 'PX2', 'F', 0, '', 0),
('2', 'PX2', 'P', 0, '', 0),
('2', 'PY2', 'F', 0, '', 0),
('2', 'PY4', 'F', 0, '', 0),
('2', 'PZ5', 'A', 0, '', 0),
('2', 'PZ5', 'F', 0, '', 0),
('2', 'PZ5', 'P', 0, '', 0),
('2', 'RM0', 'P', 0, '', 0),
('2', 'RM0', 'P', 6, '', 0),
('2', 'RM3', 'F', 0, '', 0),
('2', 'RM3', 'F', 6, '', 0),
('2', 'RM4', 'P', 0, '', 0),
('2', 'RM4', 'P', 6, '', 0),
('2', 'RM6', 'F', 0, '', 0),
('2', 'RM6', 'F', 6, '', 0),
('2', 'RM6', 'P', 0, '', 0),
('2', 'RM6', 'P', 6, '', 0),
('2', 'RM9', 'F', 0, '', 0),
('2', 'RM9', 'F', 6, '', 0),
('2', 'STL', 'F', 0, '', 0),
('2', 'STL', 'F', 6, '', 0),
('2', 'STL', 'L', 0, '', 0),
('2', 'STL', 'L', 6, '', 0),
('2', 'STL', 'P', 0, '', 0),
('2', 'STL', 'P', 6, '', 0),
('D', 'SD1', 'N', 0, '', 0),
('D', 'SD1', 'N', 1, '', 0),
('D', 'SD1', 'N', 2, '', 0),
('D', 'SD1', 'N', 3, '', 0),
('D', 'SD1', 'N', 4, '', 0),
('D', 'SD1', 'N', 5, '', 0),
('D', 'SD1', 'N', 13, '', 0),
('D', 'SD1', 'N', 14, '', 0),
('D', 'SD1', 'N', 16, '', 0),
('D', 'SD1', 'N', 22, '', 0),
('D', 'SD1', 'N', 24, '', 0),
('D', 'SD2', 'N', 0, '', 0),
('D', 'SD2', 'N', 1, '', 0),
('D', 'SD2', 'N', 2, '', 0),
('D', 'SD2', 'N', 3, '', 0),
('D', 'SD2', 'N', 4, '', 0),
('D', 'SD2', 'N', 5, '', 0),
('D', 'SD2', 'N', 13, '', 0),
('D', 'SD2', 'N', 14, '', 0),
('D', 'SD2', 'N', 16, '', 0),
('D', 'SD2', 'N', 22, '', 0),
('D', 'SD2', 'N', 24, '', 0),
('D', 'SD3', 'N', 0, '', 0),
('D', 'SD3', 'N', 1, '', 0),
('D', 'SD3', 'N', 2, '', 0),
('D', 'SD3', 'N', 3, '', 0),
('D', 'SD3', 'N', 4, '', 0),
('D', 'SD3', 'N', 5, '', 0),
('D', 'SD3', 'N', 13, '', 0),
('D', 'SD3', 'N', 14, '', 0),
('D', 'SD3', 'N', 16, '', 0),
('D', 'SD3', 'N', 22, '', 0),
('D', 'SD3', 'N', 24, '', 0),
('D', 'SD4', 'N', 0, '', 0),
('D', 'SD4', 'N', 1, '', 0),
('D', 'SD4', 'N', 2, '', 0),
('D', 'SD4', 'N', 3, '', 0),
('D', 'SD4', 'N', 4, '', 0),
('D', 'SD4', 'N', 5, '', 0),
('D', 'SD4', 'N', 13, '', 0),
('D', 'SD4', 'N', 14, '', 0),
('D', 'SD4', 'N', 16, '', 0),
('D', 'SD4', 'N', 22, '', 0),
('D', 'SD4', 'N', 24, '', 0),
('D', 'SD5', 'N', 0, '', 0),
('D', 'SD5', 'N', 1, '', 0),
('D', 'SD5', 'N', 2, '', 0),
('D', 'SD5', 'N', 3, '', 0),
('D', 'SD5', 'N', 4, '', 0),
('D', 'SD5', 'N', 5, '', 0),
('D', 'SD5', 'N', 13, '', 0),
('D', 'SD5', 'N', 14, '', 0),
('D', 'SD5', 'N', 16, '', 0),
('D', 'SD5', 'N', 22, '', 0),
('D', 'SD5', 'N', 24, '', 0),
('D', 'SD6', 'N', 0, '', 0),
('D', 'SD6', 'N', 1, '', 0),
('D', 'SD6', 'N', 2, '', 0),
('D', 'SD6', 'N', 3, '', 0),
('D', 'SD6', 'N', 4, '', 0),
('D', 'SD6', 'N', 5, '', 0),
('D', 'SD6', 'N', 13, '', 0),
('D', 'SD6', 'N', 14, '', 0),
('D', 'SD6', 'N', 16, '', 0),
('D', 'SD6', 'N', 22, '', 0),
('D', 'SD6', 'N', 24, '', 0),
('H', 'BF1', 'E', 0, '', 0),
('H', 'BF1', 'G', 0, '', 0),
('H', 'BF1', 'P', 0, '', 0),
('H', 'BF2', 'E', 0, '', 0),
('H', 'BF2', 'G', 0, '', 0),
('H', 'BF2', 'P', 0, '', 0),
('H', 'BF7', 'N', 0, '', 0),
('H', 'BF8', 'N', 0, '', 0),
('H', 'BF9', 'N', 0, '', 0),
('I', 'DE1', 'E', 0, '', 0),
('I', 'DE3', 'E', 0, '', 0),
('I', 'DE4', 'E', 0, '', 0),
('I', 'DE6', 'E', 0, '', 0),
('I', 'DG1', 'G', 0, '', 0),
('I', 'DG3', 'G', 0, '', 0),
('I', 'DG4', 'G', 0, '', 0),
('I', 'DG6', 'G', 0, '', 0),
('I', 'DW1', 'E', 0, '', 0),
('I', 'IE1', 'E', 0, '', 0),
('I', 'IE3', 'E', 0, '', 0),
('I', 'IG1', 'G', 0, '', 0),
('I', 'IG3', 'G', 0, '', 0),
('I', 'IG4', 'G', 0, '', 0),
('I', 'IG6', 'G', 0, '', 0),
('I', 'MB1', 'E', 0, '', 0),
('I', 'MB1', 'N', 0, '', 0),
('I', 'MB2', 'N', 0, '', 0),
('I', 'MB3', 'N', 0, '', 0),
('I', 'MP0', 'E', 0, '', 0),
('I', 'MP1', 'E', 0, '', 0),
('I', 'MP4', 'E', 0, '', 0),
('I', 'MP5', 'E', 0, '', 0),
('I', 'MP6', 'E', 0, '', 0),
('I', 'MP7', 'E', 0, '', 0),
('I', 'MP8', 'E', 0, '', 0),
('I', 'MP9', 'E', 0, '', 0),
('I', 'MTA', 'E', 0, '', 0),
('I', 'MTB', 'E', 0, '', 0),
('I', 'MTC', 'G', 0, '', 0),
('I', 'MTC', 'P', 0, '', 0),
('I', 'MTD', 'G', 0, '', 0),
('I', 'MTD', 'P', 0, '', 0),
('I', 'MTE', 'E', 0, '', 0),
('I', 'MTF', 'E', 0, '', 0),
('I', 'MTG', 'G', 0, '', 0),
('I', 'MTH', 'G', 0, '', 0),
('I', 'MTI', 'G', 0, '', 0),
('I', 'MTI', 'P', 0, '', 0),
('I', 'MTJ', 'G', 0, '', 0),
('I', 'MTJ', 'P', 0, '', 0),
('I', 'MTK', 'G', 0, '', 0),
('I', 'MTL', 'G', 0, '', 0),
('I', 'MTM', 'G', 0, '', 0),
('I', 'MTM', 'P', 0, '', 0),
('I', 'MTN', 'G', 0, '', 0),
('I', 'MTN', 'P', 0, '', 0),
('I', 'MTO', 'G', 0, '', 0),
('I', 'MTP', 'G', 0, '', 0),
('I', 'MTQ', 'E', 0, '', 0),
('I', 'MTS', 'E', 0, '', 0),
('I', 'OLA', 'E', 0, '', 0),
('I', 'OLA', 'G', 0, '', 0),
('I', 'OLA', 'N', 0, '', 0),
('I', 'OLA', 'P', 0, '', 0),
('I', 'OLS', 'E', 0, '', 0),
('I', 'OLS', 'G', 0, '', 0),
('I', 'OLS', 'N', 0, '', 0),
('I', 'OLS', 'P', 0, '', 0),
('I', 'OSA', 'E', 0, '', 0),
('I', 'OSA', 'G', 0, '', 0),
('I', 'OSA', 'P', 0, '', 0),
('I', 'OSB', 'E', 0, '', 0),
('I', 'OSB', 'G', 0, '', 0),
('I', 'OSB', 'P', 0, '', 0),
('I', 'OTA', 'E', 0, '', 0),
('I', 'OTA', 'G', 0, '', 0),
('I', 'OTA', 'P', 0, '', 0),
('I', 'OTB', 'E', 0, '', 0),
('I', 'OTB', 'G', 0, '', 0),
('I', 'OTB', 'P', 0, '', 0),
('I', 'OTC', 'E', 0, '', 0),
('I', 'OTC', 'G', 0, '', 0),
('I', 'OTC', 'P', 0, '', 0),
('I', 'OTD', 'E', 0, '', 0),
('I', 'OTD', 'G', 0, '', 0),
('I', 'OTD', 'P', 0, '', 0),
('I', 'OZ1', 'N', 0, '', 0),
('I', 'OZ3', 'N', 0, '', 0),
('I', 'OZ4', 'N', 0, '', 0),
('I', 'OZ6', 'N', 0, '', 0),
('I', 'PS0', 'E', 0, '', 0),
('I', 'PS7', 'G', 0, '', 0),
('I', 'PS8', 'G', 0, '', 0),
('I', 'PS9', 'E', 0, '', 0),
('I', 'PSB', 'G', 0, '', 0),
('I', 'PSC', 'E', 0, '', 0),
('I', 'WE1', 'E', 0, '', 0),
('I', 'WE3', 'E', 0, '', 0),
('I', 'WG1', 'G', 0, '', 0),
('I', 'WG3', 'G', 0, '', 0),
('I', 'WG4', 'G', 0, '', 0),
('I', 'WG6', 'G', 0, '', 0),
('I', 'WW1', 'N', 0, '', 0),
('I', 'WW3', 'N', 0, '', 0),
('I', 'WW4', 'N', 0, '', 0),
('I', 'WW6', 'N', 0, '', 0),
('R', 'PT1', 'N', 0, '', 0),
('R', 'PT2', 'N', 0, '', 0),
('T', 'TPL', 'N', 0, '', 0),
('T', 'TPL', 'N', 13, '', 0),
('T', 'TPL', 'N', 14, '', 0),
('T', 'TPL', 'N', 16, '', 0),
('T', 'TPL', 'N', 22, '', 0),
('T', 'TPM', 'N', 0, '', 0),
('T', 'TPM', 'N', 13, '', 0),
('T', 'TPM', 'N', 14, '', 0),
('T', 'TPM', 'N', 16, '', 0),
('T', 'TPM', 'N', 22, '', 0),
('T', 'TPN', 'N', 0, '', 0),
('T', 'TPN', 'N', 13, '', 0),
('T', 'TPN', 'N', 14, '', 0),
('T', 'TPN', 'N', 16, '', 0),
('T', 'TPN', 'N', 22, '', 0),
('T', 'TPS', 'N', 0, '', 0),
('T', 'TPS', 'N', 13, '', 0),
('T', 'TPS', 'N', 14, '', 0),
('T', 'TPS', 'N', 16, '', 0),
('T', 'TPS', 'N', 22, '', 0),
('T', 'TRL', 'N', 0, '', 0),
('T', 'TRL', 'N', 13, '', 0),
('T', 'TRL', 'N', 14, '', 0),
('T', 'TRL', 'N', 16, '', 0),
('T', 'TRL', 'N', 22, '', 0),
('T', 'TRM', 'N', 0, '', 0),
('T', 'TRM', 'N', 13, '', 0),
('T', 'TRM', 'N', 14, '', 0),
('T', 'TRM', 'N', 16, '', 0),
('T', 'TRM', 'N', 22, '', 0),
('T', 'TRN', 'N', 0, '', 0),
('T', 'TRN', 'N', 13, '', 0),
('T', 'TRN', 'N', 14, '', 0),
('T', 'TRN', 'N', 16, '', 0),
('T', 'TRN', 'N', 22, '', 0),
('T', 'TRS', 'N', 0, '', 0),
('T', 'TRS', 'N', 13, '', 0),
('T', 'TRS', 'N', 14, '', 0),
('T', 'TRS', 'N', 16, '', 0),
('T', 'TRS', 'N', 22, '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `royal_mail_service_offerings`
--

DROP TABLE IF EXISTS `royal_mail_service_offerings`;
CREATE TABLE `royal_mail_service_offerings` (
  `service_offerings_code` char(3) NOT NULL DEFAULT '',
  `service_offerings_name` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `royal_mail_service_offerings`
--

INSERT INTO `royal_mail_service_offerings` (`service_offerings_code`, `service_offerings_name`) VALUES
('BF1', 'HM Forces Mail'),
('BF2', 'HM Forces Signed For'),
('BF7', 'HM Forces Special Delivery (£500)'),
('BF8', 'HM Forces Special Delivery (£1000)'),
('BF9', 'HM Forces Special Delivery (£2500)'),
('BPL', 'Royal Mail 1st/2nd Class'),
('BPR', 'Royal Mail 1st/2nd Class Signed For'),
('CRL', 'Royal Mail 24/ Royal Mail 48 Standard/Signed For (Parcel - Daily Rate Service)'),
('DE1', 'International Business Parcels Zero Sort High Volume Priority'),
('DE3', 'International Business Parcels Zero Sort High Vol Economy'),
('DE4', 'International Business Parcels Zero Srt Low Volume Priority'),
('DE6', 'International Business Parcels Zero Sort Low Vol Economy'),
('DG1', 'International Business Mail Large Letter Country Sort High Volume Priority'),
('DG3', 'International Business Mail Large Letter Ctry Sort High Vol Economy'),
('DG4', 'International Business Mail Large Letter Country Sort Low Volume Priority'),
('DG6', 'International Business Mail Large Letter Ctry Sort Low Vol Economy'),
('FS1', 'Royal Mail 24 Standard/Signed For Large Letter (Flat Rate Service)'),
('FS2', 'Royal Mail 48 Standard/Signed For Large Letter (Flat Rate Service)'),
('FS7', 'Royal Mail 24 (Presorted) (Large Letter)'),
('FS8', 'Royal Mail 48 (Presorted) (Large Letter)'),
('IE1', 'International Business Parcels Zone Sort Priority Service'),
('IE3', 'International Business Parcels Zone Sort Economy Service'),
('IG1', 'International Business Mail Large Letter Zone Sort Priority'),
('IG3', 'International Business Mail Large Letter Zone Sort Economy'),
('IG4', 'International Business Mail Large Letter Zone Sort Priority Machine'),
('IG6', 'International Business Mail Large Letter Zone Srt Economy Machine'),
('LA1', 'Special Delivery Guaranteed By 1PM LA (£500)'),
('LA2', 'Special Delivery Guaranteed By 1PM LA (£1000)'),
('LA3', 'Special Delivery Guaranteed By 1PM LA (£2500)'),
('LA4', 'Special Delivery Guaranteed By 9AM LA (£50)'),
('LA5', 'Special Delivery Guaranteed By 9AM LA (£1000)'),
('LA6', 'Special Delivery Guaranteed By 9AM LA (£2500)'),
('MB1', 'INTL BUS PARCELS PRINT DIRECT PRIORITY'),
('MB2', 'INTL BUS PARCELS PRINT DIRECT STANDARD'),
('MB3', 'INTL BUS PARCELS PRINT DIRECT ECONOMY'),
('MP0', 'International Business Parcels Signed Extra Compensation (Country Pricing)'),
('MP1', 'International Business Parcels Tracked (Zonal Pricing)'),
('MP4', 'International Business Parcels Tracked Extra Comp (Zonal Pricing)'),
('MP5', 'International Business Parcels Signed (Zonal Pricing)'),
('MP6', 'International Business Parcels Signed Extra Compensation (Zonal Pricing)'),
('MP7', 'International Business Parcels Tracked (Country Pricing)'),
('MP8', 'International Business Parcels Tracked Extra Comp (Country Pricing)'),
('MP9', 'International Business Parcels Signed (Country Pricing)'),
('MPB', 'International Business Parcel Tracked Boxable Extra Comp (Country Pricing)'),
('MPF', 'International Business Parcel Tracked High Vol. (Country Pricing)'),
('MPG', 'International Business Parcels Tracked & Signed High Vol. (Country Pricing)'),
('MPH', 'International Business Parcel Signed High Vol. (Country Pricing)'),
('MPI', 'International Business Parcel Tracked High Vol. Extra Comp (Country Pricing)'),
('MPJ', 'International Business Parcels Tracked & Signed High Vol. Extra Comp (Country Pricing)'),
('MPK', 'International Business Parcel Signed High Vol. Extra Comp (Country Pricing)'),
('MPL', 'International Business Mail Tracked High Vol. (Country Pricing)'),
('MPM', 'International Business Mail Tracked & Signed High Vol. (Country Pricing)'),
('MPN', 'International Business Mail Signed High Vol. (Country Pricing)'),
('MPO', 'International Business Mail Tracked High Vol. Extra Comp (Country Pricing)'),
('MPP', 'International Business Mail Tracked & Signed High Vol. Extra Comp (Country Pricing)'),
('MPQ', 'International Business Mail Signed High Vol. Extra Comp (Country Pricing)'),
('MPR', 'International Business Parcel Tracked Boxable (Country Pricing)'),
('MPT', 'International Business Parcel Tracked Boxable High Vol. (Country Pricing)'),
('MPU', 'International Business Parcel Tracked Boxable Extra Comp (Country Pricing)'),
('MPV', 'International Business Parcel Zero Sort Boxable Low Vol. Priority'),
('MPW', 'International Business Parcel Zero Sort Boxable Low Vol. Economy'),
('MPX', 'International Business Parcel Zero Sort Boxable High Vol. Priority'),
('MPY', 'International Business Parcel Zero Sort Boxable High Vol. Economy'),
('MTA', 'International Business Parcels Tracked & Signed (Zonal Pricing)'),
('MTB', 'International Business Parcels Tracked & Signed Extra Compensation (Zonal Pricing)'),
('MTC', 'International Business Mail Tracked & Signed (Zonal Pricing)'),
('MTD', 'International Business Mail Tracked & Signed Extra Compensation (Zonal Pricing)'),
('MTE', 'International Business Parcels Tracked & Signed (Country Pricing)'),
('MTF', 'International Business Parcels Tracked & Signed Extra Compensation (Country Pricing)'),
('MTG', 'International Business Mail Tracked & Signed (Country Pricing)'),
('MTH', 'International Business Mail Tracked & Signed Extra Compensation (Country Pricing)'),
('MTI', 'International Business Mail Tracked (Zonal Pricing)'),
('MTJ', 'International Business Mail Tracked Extra Comp (Zonal Pricing)'),
('MTK', 'International Business Mail Tracked (Country Pricing)'),
('MTL', 'International Business Mail Tracked Extra Comp (Country Pricing)'),
('MTM', 'International Business Mail Signed (Zonal Pricing)'),
('MTN', 'International Business Mail Signed Extra Compensation (Zonal Pricing)'),
('MTO', 'International Business Mail Signed (Country Pricing)'),
('MTP', 'International Business Mail Signed Extra Compensation (Country Pricing)'),
('MTQ', 'International Business Parcels Zone Sort Plus Priority'),
('MTS', 'International Business Parcels Zone Sort Plus Economoy'),
('MUA', 'INTL BUS PARCELS BOXABLE ZERO SORT PRI'),
('MUB', 'INTL BUS PARCELS BOXABLE ZERO SORT ECON'),
('MUC', 'INTL BUS PARCELS BOXABLE ZONE SORT PRI'),
('MUD', 'INTL BUS PARCELS BOXABLE ZONE SORT ECON'),
('MUE', 'INTL BUS PRCL TRCKD BOX ZERO SRT XTR CMP'),
('MUF', 'INTL BUS PARCELS TRACKED BOX ZERO SORT'),
('MUG', 'INTL BUS PARCELS TRACKED BOX ZONE SORT'),
('MUH', 'INTL BUS PRCL TRCKD BOX ZONE SRT XTR CMP'),
('MUI', 'INTL BUS PARCELS TRACKED ZERO SORT'),
('MUJ', 'INTL BUS PARCEL TRACKED ZERO SRT XTR CMP'),
('MUK', 'INTL BUS PARCEL TRACKD & SIGNED ZERO SRT'),
('MUL', 'INT BUS PRCL TRCKD & SGND ZRO SRT XT CMP'),
('MUM', 'INTL BUS PARCELS SIGNED ZERO SORT'),
('MUN', 'INTL BUS PARCEL SIGNED ZERO SORT XTR CMP'),
('MUO', 'INTL BUS MAIL TRACKED ZERO SORT'),
('MUP', 'INTL BUS MAIL TRACKED ZERO SORT XTRA CMP'),
('MUQ', 'INTL BUS MAIL TRACKED & SIGNED ZERO SORT'),
('MUR', 'INT BUS MAIL TRCKD & SGND ZRO SRT XT CMP'),
('MUS', 'INTL BUS MAIL SIGNED ZERO SORT'),
('MUT', 'INTL BUS MAIL SIGNED ZERO SORT XTRA COMP'),
('MUU', 'Intlernational Business Parcels Boxable Max Sort Priority'),
('MUV', 'International Buiness Prcls Boxable Max Sort Standard'),
('MUW', 'International Business Parcels Boxable Max Sort Economy'),
('OLA', 'International Standard On Account'),
('OLS', 'International Economy On Account'),
('OSA', 'International Signed On Account (Zonal Pricing)'),
('OSB', 'International Signed On Account Extra Compensation (Zonal Pricing)'),
('OTA', 'International Tracked On Account (Zonal Pricing)'),
('OTB', 'International Tracked On Account Extra Compensation (Zonal Pricing)'),
('OTC', 'International Tracked & Signed On Account (Zonal Pricing)'),
('OTD', 'International Tracked & Signed On Account Extra Compensation (Zonal Pricing)'),
('OZ1', 'International Business Mail Mixed Zone Sort Priority'),
('OZ3', 'International Business Mail Mixed Zone Sort Economy'),
('OZ4', 'International Business Mail Mixed Zone Sort Priority Machine'),
('OZ6', 'International Business Mail Mixed Zone Srt Economy Machine'),
('PK0', 'Royal Mail 48 (LL) Flat Rate'),
('PK1', 'Royal Mail 24 Standard/Signed For (Parcel – Sort8 - Flat Rate Service)'),
('PK2', 'Royal Mail 48 Standard/Signed For (Parcel – Sort8 - Flat Rate Service)'),
('PK3', 'Royal Mail 24 Standard/Signed For (Parcel - Sort8 - Daily Rate Service)'),
('PK4', 'Royal Mail 48 Standard/Signed For (Parcel - Sort8 - Daily Rate Service)'),
('PK7', 'Royal Mail 24 (Presorted) (P)'),
('PK8', 'Royal Mail 48 (Presorted) (P)'),
('PK9', 'Royal Mail 24 (LL) Flat Rate'),
('PKB', 'RM24 (Presorted) (P) Annual Flat Rate'),
('PKD', 'RM48 (Presorted) (P) Annual Flat Rate'),
('PKK', 'RM48 (Presorted) (LL) Annual Flat Rate'),
('PKM', 'RM24 (Presorted)(LL) Annual Flat Rate'),
('PPF', 'Royal Mail 24/48 Standard/Signed For (Packetpost- Flat Rate Service)'),
('PPJ', 'Parcelpost Flat Rate (Annual)'),
('PPS', 'RM24 (LL) Annual Flat Rate'),
('PPT', 'RM48 (LL) Annual Flat Rate'),
('PS0', 'International Business Parcels Max Sort Economy Service'),
('PS7', 'International Business Mail Large Letter Max Sort Priority Service'),
('PS8', 'International Business Mail Large Letter Max Sort Economy Service'),
('PS9', 'International Business Parcels Max Sort Priority Service'),
('PSB', 'International Business Mail Large Letter Max Sort Standard Service'),
('PSC', 'International Business Parcels Max Sort Standard Service'),
('RM0', 'Royal Mail 48 (Sort8)(P) Annual Flat Rate'),
('RM1', 'Royal Mail 24 (LL) Daily Rate'),
('RM2', 'Royal Mail 24 (P) Daily Rate'),
('RM3', 'Royal Mail 48 (LL) Daily Rate'),
('RM4', 'Royal Mail 48 (P) Daily Rate'),
('RM5', 'Royal Mail 24 (P) Annual Flat Rate'),
('RM6', 'Royal Mail 48 (P) Annual Flat Rate'),
('RM7', 'Royal Mail 24 (SORT8) (LL) Annual Flat Rate'),
('RM8', 'Royal Mail 24 (SORT8) (P) Annual Flat Rate'),
('RM9', 'Royal Mail 48 (SORT8) (LL) Annual Flat Rate'),
('SD1', 'Special Delivery Guaranteed By 1PM (£500)'),
('SD2', 'Special Delivery Guaranteed By 1PM (£1000)'),
('SD3', 'Special Delivery Guaranteed By 1PM (£2500)'),
('SD4', 'Special Delivery Guaranteed By 9AM (£50)'),
('SD5', 'Special Delivery Guaranteed By 9AM (£1000)'),
('SD6', 'Special Delivery Guaranteed By 9AM (£2500)'),
('STL', 'Royal Mail 1st Class/ 2nd Class  Standard/Signed For (Letters - Daily Rate service)'),
('TPL', 'Tracked 48 High Volume Signature/ No Signature'),
('TPM', 'Tracked 24 High Volume Signature/ No Signature'),
('TPN', 'Tracked 24 Signature/ No Signature'),
('TPS', 'Tracked 48 Signature/ No Signature'),
('TRL', 'Tracked Letter-Boxable 48 High Volume Signature'),
('TRM', 'Tracked Letter-Boxable 24 High Volume No Signature'),
('TRN', 'Tracked Letter-Boxable 24 No Signature'),
('TRS', 'Tracked Letter-Boxable 48 No Signature'),
('TSN', 'Tracked Returns 24'),
('TSS', 'Tracked Returns 48'),
('WE1', 'International Business Parcels Zero Sort Priority'),
('WE3', 'International Business Parcels Zero Sort Economy');

-- --------------------------------------------------------

--
-- Table structure for table `royal_mail_service_types`
--

DROP TABLE IF EXISTS `royal_mail_service_types`;
CREATE TABLE `royal_mail_service_types` (
  `service_types_code` char(1) NOT NULL DEFAULT '',
  `service_types_name` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `royal_mail_service_types`
--

INSERT INTO `royal_mail_service_types` (`service_types_code`, `service_types_name`) VALUES
('1', 'Royal Mail 24 / 1st Class'),
('2', 'Royal Mail 48 / 2nd Class'),
('D', 'Special Delivery Guaranteed'),
('H', 'HM Forces (BFPO)'),
('I', 'International'),
('R', 'Tracked Returns'),
('T', 'Royal Mail Tracked');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `royal_mail_enhancement_types`
--
ALTER TABLE `royal_mail_enhancement_types`
  ADD PRIMARY KEY (`enhancement_types_code`);

--
-- Indexes for table `royal_mail_service_formats`
--
ALTER TABLE `royal_mail_service_formats`
  ADD PRIMARY KEY (`service_types_code`,`service_formats_code`);

--
-- Indexes for table `royal_mail_service_matrix`
--
ALTER TABLE `royal_mail_service_matrix`
  ADD PRIMARY KEY (`service_types_code`,`service_offerings_code`,`service_formats_code`,`enhancement_types_code`);

--
-- Indexes for table `royal_mail_service_offerings`
--
ALTER TABLE `royal_mail_service_offerings`
  ADD PRIMARY KEY (`service_offerings_code`);

--
-- Indexes for table `royal_mail_service_types`
--
ALTER TABLE `royal_mail_service_types`
  ADD PRIMARY KEY (`service_types_code`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
