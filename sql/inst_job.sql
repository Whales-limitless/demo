-- Installation Job module
-- Stores delivery driver installation job submissions and admin approvals.
-- The PHP modules also CREATE TABLE IF NOT EXISTS at runtime so manual SQL is optional.

CREATE TABLE IF NOT EXISTS `inst_job` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERCODE` varchar(50) NOT NULL,
  `USERNAME` varchar(80) NOT NULL,
  `IMAGE` varchar(200) NOT NULL DEFAULT '',
  `REMARK` text NOT NULL,
  `STATUS` varchar(1) NOT NULL DEFAULT 'P',
  `REJECT_REASON` text NOT NULL,
  `APPROVE_REASON` text NOT NULL,
  `COMMISSION` double(10,2) NOT NULL DEFAULT 0.00,
  `SUBMIT_DATETIME` datetime NOT NULL,
  `REVIEWED_BY` varchar(50) NOT NULL DEFAULT '',
  `REVIEWED_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_user` (`USERCODE`),
  KEY `idx_status` (`STATUS`),
  KEY `idx_submit` (`SUBMIT_DATETIME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
