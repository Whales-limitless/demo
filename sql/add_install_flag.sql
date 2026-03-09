-- Add INSTALL column to del_orderlistdesc table
-- 'Y' = installation required, '' = no installation needed
ALTER TABLE `del_orderlistdesc` ADD COLUMN `INSTALL` VARCHAR(1) NOT NULL DEFAULT '' AFTER `UOM`;
