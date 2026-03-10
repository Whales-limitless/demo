-- Add INSTALL_IMG column to del_orderlistdesc table for installation photos
-- Stores the filename of the installation photo taken by the driver
ALTER TABLE `del_orderlistdesc` ADD COLUMN `INSTALL_IMG` VARCHAR(200) NOT NULL DEFAULT '' AFTER `INSTALL`;
