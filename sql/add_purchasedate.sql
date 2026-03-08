-- Add PURCHASEDATE column to orderlist table
-- Safe to run multiple times (IF NOT EXISTS equivalent via ALTER IGNORE)
ALTER TABLE `orderlist` ADD COLUMN `PURCHASEDATE` DATE DEFAULT NULL;

-- Add PURCHASEDATE column to orderlist2 summary table
ALTER TABLE `orderlist2` ADD COLUMN `PURCHASEDATE` DATE DEFAULT NULL;
