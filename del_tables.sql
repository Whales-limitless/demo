-- ============================================================
-- Delivery Module Tables (prefixed with del_)
-- For use in pw_main database
-- ============================================================

-- Delivery Locations (distance & commission lookup)
CREATE TABLE IF NOT EXISTS `del_location` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(100) NOT NULL DEFAULT '',
  `POSTCODE` varchar(20) NOT NULL DEFAULT '',
  `DISTANT` varchar(20) NOT NULL DEFAULT '',
  `RETAIL` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery Customers
CREATE TABLE IF NOT EXISTS `del_customer` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CODE` varchar(20) NOT NULL DEFAULT '',
  `NAME` varchar(100) NOT NULL DEFAULT '',
  `LOCATION` varchar(100) NOT NULL DEFAULT '',
  `ADDRESS` text DEFAULT NULL,
  `EMAIL` varchar(100) NOT NULL DEFAULT '',
  `HP` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drivers
CREATE TABLE IF NOT EXISTS `del_driver` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CODE` varchar(20) NOT NULL DEFAULT '',
  `NAME` varchar(100) NOT NULL DEFAULT '',
  `EMAIL` varchar(100) NOT NULL DEFAULT '',
  `ADDRESS` text DEFAULT NULL,
  `POSTCODE` varchar(20) NOT NULL DEFAULT '',
  `STATE` varchar(50) NOT NULL DEFAULT '',
  `AREA` varchar(100) NOT NULL DEFAULT '',
  `HP` varchar(30) NOT NULL DEFAULT '',
  `USERNAME` varchar(50) NOT NULL DEFAULT '',
  `PASSWORD` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery Orders
CREATE TABLE IF NOT EXISTS `del_orderlist` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORDNO` varchar(50) NOT NULL DEFAULT '',
  `DELDATE` date DEFAULT NULL,
  `DRIVERCODE` varchar(20) NOT NULL DEFAULT '',
  `DRIVER` varchar(100) NOT NULL DEFAULT '',
  `CUSTOMERCODE` varchar(20) NOT NULL DEFAULT '',
  `CUSTOMER` varchar(100) NOT NULL DEFAULT '',
  `LOCATION` varchar(100) NOT NULL DEFAULT '',
  `DISTANT` varchar(20) NOT NULL DEFAULT '',
  `RETAIL` varchar(20) NOT NULL DEFAULT '',
  `REMARK` text DEFAULT NULL,
  `STATUS` varchar(5) NOT NULL DEFAULT '',
  `IMG1` varchar(200) NOT NULL DEFAULT '',
  `IMG2` varchar(200) NOT NULL DEFAULT '',
  `IMG3` varchar(200) NOT NULL DEFAULT '',
  `DONEDATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery Order Line Items
CREATE TABLE IF NOT EXISTS `del_orderlistdesc` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORDERNO` varchar(50) NOT NULL DEFAULT '',
  `PDESC` varchar(200) NOT NULL DEFAULT '',
  `QTY` varchar(20) NOT NULL DEFAULT '',
  `UOM` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temporary Order Items (staging during creation)
CREATE TABLE IF NOT EXISTS `del_orderlisttemp` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORDERNO` varchar(50) NOT NULL DEFAULT '',
  `PDESC` varchar(200) NOT NULL DEFAULT '',
  `QTY` varchar(20) NOT NULL DEFAULT '',
  `UOM` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery Signatures
CREATE TABLE IF NOT EXISTS `del_sign` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORDNO` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Units of Measurement
CREATE TABLE IF NOT EXISTS `del_uom` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PDESC` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
