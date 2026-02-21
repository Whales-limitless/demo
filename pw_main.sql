-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 21, 2026 at 02:08 PM
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
-- Table structure for table `parafile`
--

CREATE TABLE `parafile` (
  `ID` int(11) NOT NULL,
  `REC` varchar(100) NOT NULL,
  `ROWID` int(11) NOT NULL,
  `VISIT` varchar(10) NOT NULL,
  `REDEEM` varchar(15) NOT NULL DEFAULT '',
  `CLAIM` varchar(15) NOT NULL DEFAULT '',
  `MAXQTY` varchar(10) NOT NULL DEFAULT '',
  `DISCOUNT` varchar(5) NOT NULL DEFAULT '',
  `MEMPREFIX` varchar(10) NOT NULL DEFAULT '',
  `MINPRICE` double(9,2) NOT NULL DEFAULT 0.00,
  `THEME` varchar(10) NOT NULL DEFAULT '',
  `POINT` double(10,2) NOT NULL DEFAULT 0.00,
  `MEMBER` int(11) DEFAULT NULL,
  `STKIN` varchar(8) NOT NULL DEFAULT '',
  `STKOUT` varchar(8) NOT NULL DEFAULT '',
  `STKADJ` varchar(8) NOT NULL DEFAULT ''
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

-- --------------------------------------------------------

--
-- Table structure for table `stockadj`
--

CREATE TABLE `stockadj` (
  `ID` int(11) NOT NULL,
  `IP` varchar(4) NOT NULL,
  `ACCODE` varchar(20) NOT NULL,
  `USER` varchar(20) NOT NULL,
  `OUTLET` varchar(8) NOT NULL,
  `SDATE` date NOT NULL,
  `STIME` varchar(8) NOT NULL,
  `SALNUM` varchar(70) NOT NULL,
  `MNO` varchar(6) NOT NULL,
  `BARCODE` varchar(30) NOT NULL DEFAULT '',
  `PDESC` varchar(48) NOT NULL,
  `LOOSE` double(15,2) NOT NULL,
  `PGROUP` varchar(8) NOT NULL,
  `PRODTYPE` varchar(1) NOT NULL,
  `QTYADJ` double(8,2) NOT NULL,
  `SERIALNUMBER` varchar(30) NOT NULL,
  `CUSTOMER` varchar(10) NOT NULL DEFAULT '',
  `INVNO` varchar(20) NOT NULL DEFAULT '',
  `LOGISTIC` varchar(12) NOT NULL DEFAULT '',
  `ISSUE` varchar(20) NOT NULL DEFAULT '',
  `LOCAT` varchar(70) NOT NULL DEFAULT '',
  `BATCHNO` varchar(16) NOT NULL DEFAULT '',
  `EXPDATE` date DEFAULT NULL,
  `CQTY` double(8,2) NOT NULL DEFAULT 0.00,
  `REMARK` varchar(120) NOT NULL DEFAULT '',
  `PACKING` float(15,2) NOT NULL DEFAULT 0.00,
  `UOM` varchar(4) NOT NULL DEFAULT '',
  `CCODE` varchar(30) NOT NULL DEFAULT '',
  `CODE` varchar(30) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockin`
--

CREATE TABLE `stockin` (
  `ID` int(11) NOT NULL,
  `IP` varchar(4) NOT NULL,
  `ACCODE` varchar(20) NOT NULL,
  `USER` varchar(20) NOT NULL,
  `OUTLET` varchar(8) NOT NULL,
  `SDATE` date NOT NULL,
  `STIME` varchar(8) NOT NULL,
  `SALNUM` varchar(70) NOT NULL,
  `MNO` varchar(6) NOT NULL,
  `BARCODE` varchar(30) NOT NULL DEFAULT '',
  `PDESC` varchar(48) NOT NULL,
  `LOOSE` double(15,2) NOT NULL,
  `PGROUP` varchar(8) NOT NULL,
  `PRODTYPE` varchar(1) NOT NULL,
  `QTYIN` double(8,2) NOT NULL,
  `SERIALNUMBER` varchar(30) NOT NULL,
  `SUPPLIER` varchar(10) NOT NULL DEFAULT '',
  `SUPPNO` varchar(20) NOT NULL DEFAULT '',
  `LOGISTIC` varchar(12) NOT NULL DEFAULT '',
  `RECEIVED` varchar(20) NOT NULL DEFAULT '',
  `LOCAT` varchar(70) NOT NULL DEFAULT '',
  `BATCHNO` varchar(16) NOT NULL DEFAULT '',
  `EXPDATE` date DEFAULT NULL,
  `CQTY` double(8,2) NOT NULL DEFAULT 0.00,
  `REMARK` varchar(120) NOT NULL DEFAULT '',
  `PACKING` float(15,2) NOT NULL DEFAULT 0.00,
  `UOM` varchar(4) NOT NULL DEFAULT '',
  `CCODE` varchar(30) NOT NULL DEFAULT '',
  `CODE` varchar(30) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockout`
--

CREATE TABLE `stockout` (
  `ID` int(11) NOT NULL,
  `IP` varchar(4) NOT NULL,
  `ACCODE` varchar(20) NOT NULL,
  `USER` varchar(20) NOT NULL,
  `OUTLET` varchar(8) NOT NULL,
  `SDATE` date NOT NULL,
  `STIME` varchar(8) NOT NULL,
  `SALNUM` varchar(70) NOT NULL,
  `MNO` varchar(6) NOT NULL,
  `BARCODE` varchar(30) NOT NULL DEFAULT '',
  `PDESC` varchar(48) NOT NULL,
  `LOOSE` double(15,2) NOT NULL,
  `PGROUP` varchar(8) NOT NULL,
  `PRODTYPE` varchar(1) NOT NULL,
  `QTYOUT` double(8,2) NOT NULL,
  `SERIALNUMBER` varchar(30) NOT NULL,
  `CUSTOMER` varchar(10) NOT NULL DEFAULT '',
  `INVNO` varchar(20) NOT NULL DEFAULT '',
  `LOGISTIC` varchar(12) NOT NULL DEFAULT '',
  `ISSUE` varchar(20) NOT NULL DEFAULT '',
  `LOCAT` varchar(70) NOT NULL DEFAULT '',
  `BATCHNO` varchar(16) NOT NULL DEFAULT '',
  `EXPDATE` date DEFAULT NULL,
  `CQTY` double(8,2) NOT NULL DEFAULT 0.00,
  `REMARK` varchar(120) NOT NULL DEFAULT '',
  `PACKING` float(15,2) NOT NULL DEFAULT 0.00,
  `UOM` varchar(4) NOT NULL DEFAULT '',
  `CCODE` varchar(30) NOT NULL DEFAULT '',
  `STATUS` varchar(1) NOT NULL DEFAULT '',
  `DNAME` varchar(50) NOT NULL DEFAULT '',
  `PDATE` date DEFAULT NULL,
  `PTIME` time DEFAULT NULL,
  `SNAME` varchar(50) NOT NULL DEFAULT '',
  `DDATE` date DEFAULT NULL,
  `DTIME` time DEFAULT NULL,
  `CARTON` double(10,2) NOT NULL DEFAULT 0.00,
  `PFROM` varchar(50) NOT NULL DEFAULT '',
  `PFDATE` date DEFAULT NULL,
  `PFTIME` time DEFAULT NULL,
  `VNAME` varchar(50) NOT NULL DEFAULT '',
  `VDATE` date DEFAULT NULL,
  `VTIME` time DEFAULT NULL,
  `VEHICLE` varchar(20) NOT NULL DEFAULT '',
  `COLD` double(10,2) NOT NULL DEFAULT 0.00,
  `CODE` varchar(30) NOT NULL DEFAULT '',
  `IDATE` date DEFAULT NULL,
  `ITIME` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sysfile`
--

CREATE TABLE `sysfile` (
  `ID` int(11) NOT NULL,
  `USER1` varchar(60) NOT NULL DEFAULT '',
  `USER2` varchar(60) NOT NULL DEFAULT '',
  `USER_NAME` varchar(80) NOT NULL DEFAULT '',
  `LOGDATE` varchar(8) DEFAULT NULL,
  `LOGTIME` varchar(5) DEFAULT NULL,
  `OLDPASSWD` varchar(10) DEFAULT NULL,
  `WDATE` varchar(8) DEFAULT NULL,
  `LEVEL` float(15,2) NOT NULL DEFAULT 0.00,
  `USERNAME` varchar(25) DEFAULT NULL,
  `VDEPT` varchar(20) DEFAULT NULL,
  `PIN` varchar(1) DEFAULT NULL,
  `AUSER` varchar(8) DEFAULT NULL,
  `DBP` double(15,2) NOT NULL DEFAULT 0.00,
  `TYPE` varchar(1) DEFAULT NULL,
  `STATUS` varchar(1) DEFAULT NULL,
  `OUTLET` varchar(8) DEFAULT NULL,
  `DEPT` varchar(60) NOT NULL DEFAULT '',
  `PUSHID` mediumtext NOT NULL,
  `DEPTCODE` int(11) DEFAULT NULL
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
-- Indexes for table `parafile`
--
ALTER TABLE `parafile`
  ADD PRIMARY KEY (`ID`);

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
-- Indexes for table `stockadj`
--
ALTER TABLE `stockadj`
  ADD KEY `idx_sdate` (`SDATE`),
  ADD KEY `idx_barcode` (`BARCODE`);

--
-- Indexes for table `stockin`
--
ALTER TABLE `stockin`
  ADD KEY `idx_sdate` (`SDATE`),
  ADD KEY `idx_barcode` (`BARCODE`),
  ADD KEY `idx_outlet` (`OUTLET`);

--
-- Indexes for table `stockout`
--
ALTER TABLE `stockout`
  ADD KEY `idx_sdate` (`SDATE`),
  ADD KEY `idx_barcode` (`BARCODE`),
  ADD KEY `idx_outlet` (`OUTLET`);

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
