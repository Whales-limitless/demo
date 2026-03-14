-- UOM Conversion table: defines conversion factors per product
CREATE TABLE IF NOT EXISTS `uom_conversion` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `barcode` VARCHAR(50) NOT NULL,
    `from_uom` VARCHAR(20) NOT NULL,
    `to_uom` VARCHAR(20) NOT NULL,
    `conversion_factor` DOUBLE(10,4) NOT NULL DEFAULT 1.0000,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_barcode_from_to` (`barcode`, `from_uom`, `to_uom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add UOM tracking columns to grn_item for audit trail
ALTER TABLE `grn_item`
    ADD COLUMN `receive_uom` VARCHAR(20) DEFAULT NULL AFTER `qty_rejected`,
    ADD COLUMN `qty_converted` DOUBLE(8,2) DEFAULT NULL AFTER `receive_uom`,
    ADD COLUMN `inventory_uom` VARCHAR(20) DEFAULT NULL AFTER `qty_converted`;
