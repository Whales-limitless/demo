-- Quotation module (mirrors purchase_order schema, but tracks quotations).
-- Run this once on the live DB.

CREATE TABLE IF NOT EXISTS `quotation` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quotation_number` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_id` INT NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_date` DATE DEFAULT NULL,
    `status` ENUM('DRAFT','APPROVED','PARTIALLY_RECEIVED','RECEIVED','CLOSED','CANCELLED','DONE') DEFAULT 'DRAFT',
    `total_amount` DOUBLE(15,2) DEFAULT 0.00,
    `remark` TEXT,
    `created_by` VARCHAR(50) DEFAULT '',
    `approved_by` VARCHAR(50) DEFAULT '',
    `approved_date` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quotation_item` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quotation_id` INT NOT NULL,
    `barcode` VARCHAR(50) NOT NULL,
    `product_desc` VARCHAR(100) DEFAULT '',
    `qty_ordered` DOUBLE(8,2) NOT NULL,
    `qty_received` DOUBLE(8,2) DEFAULT 0.00,
    `unit_cost` DOUBLE(10,2) NOT NULL DEFAULT 0,
    `uom` VARCHAR(20) DEFAULT '',
    `remark` VARCHAR(200) DEFAULT '',
    FOREIGN KEY (`quotation_id`) REFERENCES `quotation`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
