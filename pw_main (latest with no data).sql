-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 03, 2026 at 09:00 PM
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
-- Database: `pw_main`
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
-- Table structure for table `cat_group`
--

CREATE TABLE `cat_group` (
  `id` int(5) NOT NULL,
  `ccode` varchar(30) NOT NULL DEFAULT '',
  `cat_name` varchar(50) NOT NULL DEFAULT '',
  `cat_img` varchar(100) NOT NULL DEFAULT '',
  `main_page` varchar(10) NOT NULL DEFAULT '',
  `sort_no` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `del_customer`
--

CREATE TABLE `del_customer` (
  `ID` int(11) NOT NULL,
  `CODE` varchar(20) NOT NULL,
  `HP` varchar(12) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `LOCATION` varchar(100) NOT NULL,
  `ADDRESS` varchar(50) NOT NULL,
  `EMAIL` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `del_driver`
--

CREATE TABLE `del_driver` (
  `ID` int(11) NOT NULL,
  `CODE` varchar(20) NOT NULL,
  `HP` varchar(12) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `ADDRESS` varchar(50) NOT NULL,
  `POSTCODE` varchar(5) NOT NULL,
  `STATE` varchar(50) NOT NULL,
  `AREA` varchar(50) NOT NULL,
  `EMAIL` varchar(50) NOT NULL,
  `USERNAME` varchar(50) NOT NULL,
  `PASSWORD` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `del_location`
--

CREATE TABLE `del_location` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(60) NOT NULL,
  `POSTCODE` varchar(5) NOT NULL,
  `DISTANT` double(10,2) NOT NULL,
  `RETAIL` double(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `del_orderlist`
--

CREATE TABLE `del_orderlist` (
  `ID` int(11) NOT NULL,
  `ORDNO` varchar(50) NOT NULL DEFAULT '',
  `DELDATE` text NOT NULL,
  `DRIVERCODE` varchar(50) NOT NULL,
  `DRIVER` varchar(60) NOT NULL,
  `CUSTOMERCODE` varchar(60) NOT NULL,
  `CUSTOMER` varchar(60) NOT NULL,
  `LOCATION` varchar(100) NOT NULL,
  `DISTANT` double(10,2) NOT NULL,
  `RETAIL` double(10,2) NOT NULL,
  `REMARK` text NOT NULL,
  `IMG1` text NOT NULL,
  `IMG2` text NOT NULL,
  `IMG3` text NOT NULL,
  `DONEDATETIME` datetime NOT NULL,
  `STATUS` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `del_orderlistdesc`
--

CREATE TABLE `del_orderlistdesc` (
  `ID` int(11) NOT NULL,
  `ORDERNO` varchar(50) NOT NULL DEFAULT '',
  `PDESC` text NOT NULL,
  `QTY` double(10,2) NOT NULL,
  `UOM` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `del_orderlisttemp`
--

CREATE TABLE `del_orderlisttemp` (
  `ID` int(11) NOT NULL,
  `ORDERNO` varchar(20) NOT NULL,
  `PDESC` text NOT NULL,
  `QTY` double(10,2) NOT NULL,
  `UOM` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `del_sign`
--

CREATE TABLE `del_sign` (
  `ID` int(11) NOT NULL,
  `ORDNO` varchar(30) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `del_uom`
--

CREATE TABLE `del_uom` (
  `ID` int(11) NOT NULL,
  `PDESC` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grn`
--

CREATE TABLE `grn` (
  `id` int(11) NOT NULL,
  `grn_number` varchar(50) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `receive_date` date NOT NULL,
  `received_by` varchar(50) DEFAULT '',
  `remark` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grn_item`
--

CREATE TABLE `grn_item` (
  `id` int(11) NOT NULL,
  `grn_id` int(11) NOT NULL,
  `po_item_id` int(11) DEFAULT NULL,
  `barcode` varchar(50) NOT NULL,
  `product_desc` varchar(100) DEFAULT '',
  `qty_received` double(8,2) NOT NULL,
  `qty_rejected` double(8,2) DEFAULT 0.00,
  `unit_cost` double(10,2) DEFAULT 0.00,
  `batch_no` varchar(16) DEFAULT '',
  `exp_date` date DEFAULT NULL,
  `rack_location` varchar(70) DEFAULT '',
  `remark` varchar(200) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `MEMBER`
--

CREATE TABLE `MEMBER` (
  `ID` int(11) NOT NULL,
  `ACCODE` varchar(20) NOT NULL DEFAULT '',
  `HP` varchar(50) DEFAULT '',
  `EMAIL` varchar(100) DEFAULT '',
  `ADD1` varchar(255) DEFAULT '',
  `ADD2` varchar(255) DEFAULT '',
  `ADD3` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderlist`
--

CREATE TABLE `orderlist` (
  `ID` int(11) NOT NULL,
  `OUTLET` varchar(20) DEFAULT '',
  `SDATE` date DEFAULT NULL,
  `ACCODE` varchar(20) DEFAULT '',
  `NAME` varchar(100) DEFAULT '',
  `SALNUM` varchar(50) DEFAULT '',
  `BARCODE` varchar(50) DEFAULT '',
  `PDESC` varchar(100) DEFAULT '',
  `QTY` double(8,2) DEFAULT 0.00,
  `PTYPE` varchar(20) DEFAULT '',
  `TRANSNO` varchar(50) DEFAULT '',
  `TDATE` date DEFAULT NULL,
  `TTIME` time DEFAULT NULL,
  `STATUS` varchar(20) DEFAULT '',
  `PRINT` varchar(5) DEFAULT '',
  `view_status` varchar(20) DEFAULT '',
  `ADMINRMK` varchar(500) DEFAULT '',
  `SOUND` varchar(5) DEFAULT '',
  `TXTTO` varchar(100) DEFAULT '',
  `PURCHASEDATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orderlist2`
--

CREATE TABLE `orderlist2` (
  `ID` int(11) NOT NULL,
  `SALNUM` varchar(100) NOT NULL DEFAULT '',
  `ACCODE` varchar(20) NOT NULL DEFAULT '',
  `NAME` varchar(100) NOT NULL DEFAULT '',
  `ADMINRMK` mediumtext DEFAULT NULL,
  `TXTTO` varchar(200) NOT NULL DEFAULT '',
  `SDATE` date DEFAULT NULL,
  `TTIME` time DEFAULT NULL,
  `SUMQTY` int(11) NOT NULL DEFAULT 0,
  `HP` varchar(50) NOT NULL DEFAULT '',
  `PURCHASEDATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `outlet`
--

CREATE TABLE `outlet` (
  `ID` int(11) NOT NULL,
  `CODE` varchar(20) NOT NULL DEFAULT '',
  `PDESC` varchar(100) DEFAULT '',
  `ADDRESS` text DEFAULT NULL,
  `CONTACT` varchar(100) DEFAULT ''
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
  `STKADJ` varchar(8) NOT NULL DEFAULT '',
  `PO_NUM` varchar(8) NOT NULL DEFAULT '',
  `GRN_NUM` varchar(8) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PRODUCTS`
--

CREATE TABLE `PRODUCTS` (
  `id` int(11) NOT NULL,
  `cat_code` varchar(50) DEFAULT '',
  `sub_code` varchar(50) DEFAULT '',
  `barcode` varchar(50) DEFAULT '',
  `code` varchar(50) DEFAULT '',
  `cat` varchar(50) DEFAULT '',
  `sub_cat` varchar(50) DEFAULT '',
  `name` varchar(255) DEFAULT '',
  `description` text DEFAULT NULL,
  `img1` varchar(255) DEFAULT '',
  `qoh` double DEFAULT 0,
  `uom` varchar(20) DEFAULT '',
  `checked` varchar(5) DEFAULT 'Y',
  `stkcode` varchar(50) DEFAULT '',
  `rack` varchar(70) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_category`
--

CREATE TABLE `product_category` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_sub_category`
--

CREATE TABLE `product_sub_category` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_trend_config`
--

CREATE TABLE `product_trend_config` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `green_min` int(11) NOT NULL DEFAULT 50,
  `yellow_min` int(11) NOT NULL DEFAULT 10,
  `red_min` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_uom`
--

CREATE TABLE `product_uom` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order`
--

CREATE TABLE `purchase_order` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `status` enum('DRAFT','APPROVED','PARTIALLY_RECEIVED','RECEIVED','CLOSED','CANCELLED') DEFAULT 'DRAFT',
  `total_amount` double(15,2) DEFAULT 0.00,
  `remark` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT '',
  `approved_by` varchar(50) DEFAULT '',
  `approved_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_item`
--

CREATE TABLE `purchase_order_item` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `product_desc` varchar(100) DEFAULT '',
  `qty_ordered` double(8,2) NOT NULL,
  `qty_received` double(8,2) DEFAULT 0.00,
  `unit_cost` double(10,2) NOT NULL,
  `uom` varchar(20) DEFAULT '',
  `remark` varchar(200) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rack`
--

CREATE TABLE `rack` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(200) DEFAULT '',
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rack_product`
--

CREATE TABLE `rack_product` (
  `id` int(11) NOT NULL,
  `rack_id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockadj`
--

CREATE TABLE `stockadj` (
  `ID` int(11) NOT NULL,
  `ACCODE` varchar(20) NOT NULL,
  `USER` varchar(20) NOT NULL,
  `OUTLET` varchar(8) NOT NULL,
  `SDATE` date NOT NULL,
  `STIME` varchar(8) NOT NULL,
  `SALNUM` varchar(70) NOT NULL,
  `BARCODE` varchar(30) NOT NULL DEFAULT '',
  `PDESC` varchar(48) NOT NULL,
  `QTYADJ` double(8,2) NOT NULL,
  `REMARK` varchar(120) NOT NULL DEFAULT '',
  `LOSS_REASON` enum('SPOILAGE','DAMAGE','THEFT','EXPIRED','OTHER','ADJUSTMENT') DEFAULT 'ADJUSTMENT'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_take`
--

CREATE TABLE `stock_take` (
  `id` int(11) NOT NULL,
  `session_code` varchar(50) NOT NULL,
  `description` varchar(200) DEFAULT '',
  `type` enum('FULL','PARTIAL') DEFAULT 'FULL',
  `filter_cat` varchar(50) DEFAULT NULL,
  `filter_location` varchar(70) DEFAULT NULL,
  `status` enum('OPEN','IN_PROGRESS','COMPLETED','DRAFT','SUBMITTED','APPROVED') DEFAULT 'DRAFT',
  `created_by` varchar(50) DEFAULT '',
  `completed_by` varchar(50) DEFAULT '',
  `completed_at` datetime DEFAULT NULL,
  `submitted_by` varchar(50) DEFAULT '',
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT '',
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_take_item`
--

CREATE TABLE `stock_take_item` (
  `id` int(11) NOT NULL,
  `stock_take_id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `product_desc` varchar(100) DEFAULT '',
  `system_qty` double(8,2) NOT NULL,
  `counted_qty` double(8,2) DEFAULT NULL,
  `variance` double(8,2) DEFAULT NULL,
  `adj_applied` tinyint(1) DEFAULT 0,
  `remark` varchar(200) DEFAULT '',
  `counted_by` varchar(50) DEFAULT '',
  `counted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT '',
  `phone` varchar(50) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `address` text DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT '',
  `lead_time_days` int(11) DEFAULT 0,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `USERNAME` varchar(25) DEFAULT NULL,
  `TYPE` varchar(1) DEFAULT NULL,
  `STATUS` varchar(1) DEFAULT NULL,
  `OUTLET` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category_cat_code` (`cat_code`);

--
-- Indexes for table `cat_group`
--
ALTER TABLE `cat_group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `del_customer`
--
ALTER TABLE `del_customer`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `del_driver`
--
ALTER TABLE `del_driver`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `del_location`
--
ALTER TABLE `del_location`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `del_orderlist`
--
ALTER TABLE `del_orderlist`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `del_orderlistdesc`
--
ALTER TABLE `del_orderlistdesc`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `del_orderlisttemp`
--
ALTER TABLE `del_orderlisttemp`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `del_sign`
--
ALTER TABLE `del_sign`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `del_uom`
--
ALTER TABLE `del_uom`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `grn`
--
ALTER TABLE `grn`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grn_number` (`grn_number`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `grn_item`
--
ALTER TABLE `grn_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `po_item_id` (`po_item_id`);

--
-- Indexes for table `MEMBER`
--
ALTER TABLE `MEMBER`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_accode` (`ACCODE`);

--
-- Indexes for table `orderlist`
--
ALTER TABLE `orderlist`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_orderlist_barcode` (`BARCODE`),
  ADD KEY `idx_orderlist_sdate_status` (`SDATE`,`STATUS`);

--
-- Indexes for table `orderlist2`
--
ALTER TABLE `orderlist2`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `outlet`
--
ALTER TABLE `outlet`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_code` (`CODE`);

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
  ADD KEY `idx_products_barcode` (`barcode`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `idx_products_cat_code` (`cat_code`),
  ADD KEY `idx_products_checked` (`checked`),
  ADD KEY `idx_products_search` (`name`,`barcode`);

--
-- Indexes for table `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `product_sub_category`
--
ALTER TABLE `product_sub_category`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cat_sub` (`category_id`,`name`);

--
-- Indexes for table `product_trend_config`
--
ALTER TABLE `product_trend_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_uom`
--
ALTER TABLE `product_uom`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_order_item`
--
ALTER TABLE `purchase_order_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`);

--
-- Indexes for table `rack`
--
ALTER TABLE `rack`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `rack_product`
--
ALTER TABLE `rack_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rack_barcode` (`rack_id`,`barcode`);

--
-- Indexes for table `stockadj`
--
ALTER TABLE `stockadj`
  ADD KEY `idx_sdate` (`SDATE`),
  ADD KEY `idx_barcode` (`BARCODE`),
  ADD KEY `idx_stockadj_barcode` (`BARCODE`);

--
-- Indexes for table `stock_take`
--
ALTER TABLE `stock_take`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_code` (`session_code`);

--
-- Indexes for table `stock_take_item`
--
ALTER TABLE `stock_take_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sti_barcode` (`barcode`),
  ADD KEY `idx_sti_stock_take_id` (`stock_take_id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sysfile`
--
ALTER TABLE `sysfile`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cat_group`
--
ALTER TABLE `cat_group`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_customer`
--
ALTER TABLE `del_customer`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_driver`
--
ALTER TABLE `del_driver`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_location`
--
ALTER TABLE `del_location`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_orderlist`
--
ALTER TABLE `del_orderlist`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_orderlistdesc`
--
ALTER TABLE `del_orderlistdesc`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_orderlisttemp`
--
ALTER TABLE `del_orderlisttemp`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_sign`
--
ALTER TABLE `del_sign`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `del_uom`
--
ALTER TABLE `del_uom`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grn`
--
ALTER TABLE `grn`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grn_item`
--
ALTER TABLE `grn_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `MEMBER`
--
ALTER TABLE `MEMBER`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderlist`
--
ALTER TABLE `orderlist`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderlist2`
--
ALTER TABLE `orderlist2`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `outlet`
--
ALTER TABLE `outlet`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `PRODUCTS`
--
ALTER TABLE `PRODUCTS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_category`
--
ALTER TABLE `product_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_sub_category`
--
ALTER TABLE `product_sub_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_trend_config`
--
ALTER TABLE `product_trend_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_uom`
--
ALTER TABLE `product_uom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order`
--
ALTER TABLE `purchase_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_item`
--
ALTER TABLE `purchase_order_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rack`
--
ALTER TABLE `rack`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rack_product`
--
ALTER TABLE `rack_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_take`
--
ALTER TABLE `stock_take`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_take_item`
--
ALTER TABLE `stock_take_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sysfile`
--
ALTER TABLE `sysfile`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grn`
--
ALTER TABLE `grn`
  ADD CONSTRAINT `grn_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_order` (`id`),
  ADD CONSTRAINT `grn_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`);

--
-- Constraints for table `grn_item`
--
ALTER TABLE `grn_item`
  ADD CONSTRAINT `grn_item_ibfk_1` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`),
  ADD CONSTRAINT `grn_item_ibfk_2` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_item` (`id`);

--
-- Constraints for table `product_sub_category`
--
ALTER TABLE `product_sub_category`
  ADD CONSTRAINT `product_sub_category_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_category` (`id`);

--
-- Constraints for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD CONSTRAINT `purchase_order_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`id`);

--
-- Constraints for table `purchase_order_item`
--
ALTER TABLE `purchase_order_item`
  ADD CONSTRAINT `purchase_order_item_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_order` (`id`);

--
-- Constraints for table `rack_product`
--
ALTER TABLE `rack_product`
  ADD CONSTRAINT `rack_product_ibfk_1` FOREIGN KEY (`rack_id`) REFERENCES `rack` (`id`);

--
-- Constraints for table `stock_take_item`
--
ALTER TABLE `stock_take_item`
  ADD CONSTRAINT `stock_take_item_ibfk_1` FOREIGN KEY (`stock_take_id`) REFERENCES `stock_take` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
