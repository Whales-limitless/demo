-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 22, 2026 at 05:46 PM
-- Server version: 10.3.39-MariaDB
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parkdeptmain`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `id` int(5) NOT NULL,
  `cat_code` varchar(10) NOT NULL,
  `sub_code` varchar(10) NOT NULL,
  `ccode` varchar(10) NOT NULL,
  `cat_name` varchar(50) NOT NULL,
  `sub_cat` varchar(50) NOT NULL,
  `sort_no` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderlist`
--

CREATE TABLE `orderlist` (
  `ID` int(11) NOT NULL,
  `OUTLET` varchar(30) NOT NULL DEFAULT '',
  `SDATE` date DEFAULT NULL,
  `ACCODE` varchar(20) NOT NULL DEFAULT '',
  `NAME` varchar(100) NOT NULL DEFAULT '',
  `SALNUM` varchar(100) NOT NULL DEFAULT '',
  `BARCODE` varchar(50) NOT NULL DEFAULT '',
  `PDESC` varchar(100) NOT NULL DEFAULT '',
  `QTY` varchar(10) NOT NULL DEFAULT '',
  `RETAIL` varchar(100) NOT NULL DEFAULT '',
  `AMOUNT` varchar(100) NOT NULL DEFAULT '',
  `REMARK` mediumtext DEFAULT NULL,
  `REDEEM` varchar(100) NOT NULL DEFAULT '',
  `BILL` varchar(100) NOT NULL DEFAULT '',
  `DELIVERY` varchar(100) NOT NULL DEFAULT '',
  `PTYPE` varchar(50) NOT NULL DEFAULT '',
  `TRANSNO` varchar(50) NOT NULL DEFAULT '',
  `TDATE` date DEFAULT NULL,
  `TTIME` time DEFAULT NULL,
  `STATUS` varchar(15) NOT NULL DEFAULT '',
  `PRINT` int(11) NOT NULL,
  `view_status` varchar(10) NOT NULL,
  `ADMINRMK` mediumtext DEFAULT NULL,
  `SOUND` varchar(2) DEFAULT '0',
  `TXTTO` varchar(200) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PRODUCTS`
--

CREATE TABLE `PRODUCTS` (
  `id` int(11) NOT NULL,
  `sort_no` int(11) DEFAULT NULL,
  `cat_code` varchar(10) NOT NULL DEFAULT '',
  `sub_code` varchar(10) NOT NULL DEFAULT '',
  `barcode` varchar(50) NOT NULL DEFAULT '',
  `code` varchar(50) NOT NULL DEFAULT '',
  `cat` varchar(50) NOT NULL DEFAULT '',
  `sub_cat` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL,
  `description` varchar(100) NOT NULL DEFAULT '',
  `img1` varchar(50) NOT NULL DEFAULT '',
  `img2` varchar(50) NOT NULL DEFAULT '',
  `img3` varchar(50) NOT NULL DEFAULT '',
  `img4` varchar(50) NOT NULL DEFAULT '',
  `img5` varchar(50) NOT NULL DEFAULT '',
  `currency` varchar(10) NOT NULL DEFAULT '',
  `cost` double(10,2) NOT NULL DEFAULT 0.00,
  `oriprice` double(10,2) NOT NULL DEFAULT 0.00,
  `disprice` double(10,2) NOT NULL DEFAULT 0.00,
  `mprice` double(10,2) NOT NULL DEFAULT 0.00,
  `sdate` varchar(15) NOT NULL DEFAULT '',
  `edate` varchar(15) NOT NULL DEFAULT '',
  `remark` mediumtext DEFAULT NULL,
  `qoh` int(11) DEFAULT NULL,
  `uom` varchar(50) NOT NULL DEFAULT '',
  `checked` varchar(2) NOT NULL DEFAULT '',
  `poption1` varchar(20) NOT NULL DEFAULT '',
  `poption2` varchar(20) NOT NULL DEFAULT '',
  `poption3` varchar(20) NOT NULL DEFAULT '',
  `poption4` varchar(20) NOT NULL DEFAULT '',
  `poption5` varchar(20) NOT NULL DEFAULT '',
  `sold` int(11) DEFAULT NULL,
  `dup` varchar(1) NOT NULL,
  `stkcode` varchar(50) NOT NULL DEFAULT '',
  `rack` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orderlist`
--
ALTER TABLE `orderlist`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_status_barcode` (`STATUS`,`BARCODE`),
  ADD KEY `idx_accode` (`ACCODE`),
  ADD KEY `idx_sound` (`SOUND`),
  ADD KEY `idx_sdate` (`SDATE`),
  ADD KEY `idx_transno` (`TRANSNO`),
  ADD KEY `idx_outlet` (`OUTLET`),
  ADD KEY `idx_salnum` (`SALNUM`),
  ADD KEY `idx_sdate_accode` (`SDATE`,`ACCODE`);

--
-- Indexes for table `PRODUCTS`
--
ALTER TABLE `PRODUCTS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_stkcode` (`stkcode`),
  ADD KEY `idx_checked_search` (`checked`,`name`,`sub_cat`,`barcode`,`stkcode`),
  ADD KEY `idx_checked` (`checked`),
  ADD KEY `idx_sub_cat` (`sub_cat`),
  ADD KEY `idx_cat_code` (`cat_code`),
  ADD KEY `idx_qoh` (`qoh`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderlist`
--
ALTER TABLE `orderlist`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PRODUCTS`
--
ALTER TABLE `PRODUCTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
