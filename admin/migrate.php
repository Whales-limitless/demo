<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

$results = [];

function runMigration($connect, $label, $sql) {
    global $results;
    if ($connect->query($sql)) {
        $results[] = ['ok', $label];
    } else {
        $err = $connect->error;
        if (strpos($err, 'already exists') !== false || strpos($err, 'Duplicate column') !== false) {
            $results[] = ['skip', $label . ' (already exists)'];
        } else {
            $results[] = ['fail', $label . ': ' . $err];
        }
    }
}

// --- Supplier Master ---
runMigration($connect, 'Create supplier table', "
CREATE TABLE IF NOT EXISTS `supplier` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT '',
  `phone` VARCHAR(50) DEFAULT '',
  `email` VARCHAR(100) DEFAULT '',
  `address` TEXT,
  `payment_terms` VARCHAR(50) DEFAULT '',
  `lead_time_days` INT DEFAULT 0,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Purchase Order Header ---
runMigration($connect, 'Create purchase_order table', "
CREATE TABLE IF NOT EXISTS `purchase_order` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_number` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_id` INT NOT NULL,
  `order_date` DATE NOT NULL,
  `expected_date` DATE DEFAULT NULL,
  `status` ENUM('DRAFT','APPROVED','PARTIALLY_RECEIVED','RECEIVED','CLOSED','CANCELLED') DEFAULT 'DRAFT',
  `total_amount` DOUBLE(15,2) DEFAULT 0.00,
  `remark` TEXT,
  `created_by` VARCHAR(50) DEFAULT '',
  `approved_by` VARCHAR(50) DEFAULT '',
  `approved_date` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Purchase Order Line Items ---
runMigration($connect, 'Create purchase_order_item table', "
CREATE TABLE IF NOT EXISTS `purchase_order_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `po_id` INT NOT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `product_desc` VARCHAR(100) DEFAULT '',
  `qty_ordered` DOUBLE(8,2) NOT NULL,
  `qty_received` DOUBLE(8,2) DEFAULT 0.00,
  `unit_cost` DOUBLE(10,2) NOT NULL,
  `uom` VARCHAR(20) DEFAULT '',
  `remark` VARCHAR(200) DEFAULT '',
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Goods Receiving Note Header ---
runMigration($connect, 'Create grn table', "
CREATE TABLE IF NOT EXISTS `grn` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grn_number` VARCHAR(50) NOT NULL UNIQUE,
  `po_id` INT DEFAULT NULL,
  `supplier_id` INT NOT NULL,
  `receive_date` DATE NOT NULL,
  `received_by` VARCHAR(50) DEFAULT '',
  `remark` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`po_id`) REFERENCES `purchase_order`(`id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- GRN Line Items ---
runMigration($connect, 'Create grn_item table', "
CREATE TABLE IF NOT EXISTS `grn_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grn_id` INT NOT NULL,
  `po_item_id` INT DEFAULT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `product_desc` VARCHAR(100) DEFAULT '',
  `qty_received` DOUBLE(8,2) NOT NULL,
  `qty_rejected` DOUBLE(8,2) DEFAULT 0.00,
  `unit_cost` DOUBLE(10,2) DEFAULT 0.00,
  `batch_no` VARCHAR(16) DEFAULT '',
  `exp_date` DATE DEFAULT NULL,
  `rack_location` VARCHAR(70) DEFAULT '',
  `remark` VARCHAR(200) DEFAULT '',
  FOREIGN KEY (`grn_id`) REFERENCES `grn`(`id`),
  FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_item`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Stock Take Session ---
runMigration($connect, 'Create stock_take table', "
CREATE TABLE IF NOT EXISTS `stock_take` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(200) DEFAULT '',
  `type` ENUM('FULL','PARTIAL') DEFAULT 'FULL',
  `filter_cat` VARCHAR(50) DEFAULT NULL,
  `filter_location` VARCHAR(70) DEFAULT NULL,
  `status` ENUM('OPEN','IN_PROGRESS','COMPLETED') DEFAULT 'OPEN',
  `created_by` VARCHAR(50) DEFAULT '',
  `completed_by` VARCHAR(50) DEFAULT '',
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Stock Take Line Items ---
runMigration($connect, 'Create stock_take_item table', "
CREATE TABLE IF NOT EXISTS `stock_take_item` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `stock_take_id` INT NOT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `product_desc` VARCHAR(100) DEFAULT '',
  `system_qty` DOUBLE(8,2) NOT NULL,
  `counted_qty` DOUBLE(8,2) DEFAULT NULL,
  `variance` DOUBLE(8,2) DEFAULT NULL,
  `adj_applied` TINYINT(1) DEFAULT 0,
  `remark` VARCHAR(200) DEFAULT '',
  `counted_by` VARCHAR(50) DEFAULT '',
  `counted_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`stock_take_id`) REFERENCES `stock_take`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- ALTER existing tables ---

// Add min_qty and max_qty to PRODUCTS
$colCheck = $connect->query("SHOW COLUMNS FROM `PRODUCTS` LIKE 'min_qty'");
if ($colCheck && $colCheck->num_rows === 0) {
    runMigration($connect, 'Add min_qty to PRODUCTS', "ALTER TABLE `PRODUCTS` ADD COLUMN `min_qty` DOUBLE(8,2) DEFAULT 0.00");
} else {
    $results[] = ['skip', 'Add min_qty to PRODUCTS (already exists)'];
}

$colCheck2 = $connect->query("SHOW COLUMNS FROM `PRODUCTS` LIKE 'max_qty'");
if ($colCheck2 && $colCheck2->num_rows === 0) {
    runMigration($connect, 'Add max_qty to PRODUCTS', "ALTER TABLE `PRODUCTS` ADD COLUMN `max_qty` DOUBLE(8,2) DEFAULT 0.00");
} else {
    $results[] = ['skip', 'Add max_qty to PRODUCTS (already exists)'];
}

// Add LOSS_REASON to stockadj
$colCheck3 = $connect->query("SHOW COLUMNS FROM `stockadj` LIKE 'LOSS_REASON'");
if ($colCheck3 && $colCheck3->num_rows === 0) {
    runMigration($connect, 'Add LOSS_REASON to stockadj', "ALTER TABLE `stockadj` ADD COLUMN `LOSS_REASON` ENUM('SPOILAGE','DAMAGE','THEFT','EXPIRED','OTHER','ADJUSTMENT') DEFAULT 'ADJUSTMENT'");
} else {
    $results[] = ['skip', 'Add LOSS_REASON to stockadj (already exists)'];
}

// Add PO/GRN counter fields to parafile
$colCheck4 = $connect->query("SHOW COLUMNS FROM `parafile` LIKE 'PO_NUM'");
if ($colCheck4 && $colCheck4->num_rows === 0) {
    runMigration($connect, 'Add PO_NUM to parafile', "ALTER TABLE `parafile` ADD COLUMN `PO_NUM` VARCHAR(8) NOT NULL DEFAULT ''");
} else {
    $results[] = ['skip', 'Add PO_NUM to parafile (already exists)'];
}

$colCheck5 = $connect->query("SHOW COLUMNS FROM `parafile` LIKE 'GRN_NUM'");
if ($colCheck5 && $colCheck5->num_rows === 0) {
    runMigration($connect, 'Add GRN_NUM to parafile', "ALTER TABLE `parafile` ADD COLUMN `GRN_NUM` VARCHAR(8) NOT NULL DEFAULT ''");
} else {
    $results[] = ['skip', 'Add GRN_NUM to parafile (already exists)'];
}

// --- Product Category ---
runMigration($connect, 'Create product_category table', "
CREATE TABLE IF NOT EXISTS `product_category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Product Sub Category ---
runMigration($connect, 'Create product_sub_category table', "
CREATE TABLE IF NOT EXISTS `product_sub_category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_cat_sub` (`category_id`, `name`),
  FOREIGN KEY (`category_id`) REFERENCES `product_category`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Product UOM ---
runMigration($connect, 'Create product_uom table', "
CREATE TABLE IF NOT EXISTS `product_uom` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Rack ---
runMigration($connect, 'Create rack table', "
CREATE TABLE IF NOT EXISTS `rack` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(200) DEFAULT '',
  `status` ENUM('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// --- Rack-Product Mapping ---
runMigration($connect, 'Create rack_product table', "
CREATE TABLE IF NOT EXISTS `rack_product` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rack_id` INT NOT NULL,
  `barcode` VARCHAR(50) NOT NULL,
  `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_rack_barcode` (`rack_id`, `barcode`),
  FOREIGN KEY (`rack_id`) REFERENCES `rack`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Database Migration</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4" style="max-width:700px;">
    <h2 class="mb-4">Database Migration Results</h2>
    <table class="table table-bordered">
        <thead class="table-dark"><tr><th>Status</th><th>Migration</th></tr></thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr class="<?php echo $r[0]==='ok' ? 'table-success' : ($r[0]==='skip' ? 'table-secondary' : 'table-danger'); ?>">
                <td><strong><?php echo strtoupper($r[0]); ?></strong></td>
                <td><?php echo htmlspecialchars($r[1]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
</div>
</body>
</html>
