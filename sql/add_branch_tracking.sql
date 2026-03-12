-- ============================================================
-- Branch management and activity tracking
-- ============================================================

-- 1. Create branch table
CREATE TABLE IF NOT EXISTS `branch` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branch_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add branch_code to sysfile (users) if not exists
-- sysfile.OUTLET already exists but we ensure it maps to branch.code
-- No alter needed for sysfile since OUTLET column already exists

-- 3. Add branch_code to orderlist (stock in / purchase tracking)
ALTER TABLE `orderlist` ADD COLUMN `branch_code` VARCHAR(20) DEFAULT NULL AFTER `TXTTO`;

-- 4. Add branch_code to stock_take (stock take tracking)
ALTER TABLE `stock_take` ADD COLUMN `branch_code` VARCHAR(20) DEFAULT NULL AFTER `approved_by`;

-- 5. Add branch_code to stockadj (stock loss tracking)
ALTER TABLE `stockadj` ADD COLUMN `branch_code` VARCHAR(20) DEFAULT NULL;
