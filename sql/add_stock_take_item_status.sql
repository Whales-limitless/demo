-- Add item-level status to track partial stock take submissions
-- PENDING = not yet counted, COUNTED = submitted by staff
ALTER TABLE `stock_take_item` ADD COLUMN `status` VARCHAR(10) NOT NULL DEFAULT 'PENDING' AFTER `adj_applied`;
