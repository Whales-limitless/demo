<?php
require_once __DIR__ . '/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once 'dbconnection.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'update_rack') {
    $id = intval($_POST['id'] ?? 0);
    $rack = trim($_POST['rack'] ?? '');
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
        exit;
    }

    // Get the product's barcode for rack_product mapping
    $barcodeStmt = $connect->prepare("SELECT `barcode` FROM `PRODUCTS` WHERE `id` = ?");
    $barcodeStmt->bind_param("i", $id);
    $barcodeStmt->execute();
    $barcodeResult = $barcodeStmt->get_result()->fetch_assoc();
    $barcodeStmt->close();
    $barcode = $barcodeResult['barcode'] ?? '';

    // Ensure rack_updated_at column exists
    $chkCol = $connect->query("SHOW COLUMNS FROM `PRODUCTS` LIKE 'rack_updated_at'");
    if ($chkCol && $chkCol->num_rows === 0) {
        $connect->query("ALTER TABLE `PRODUCTS` ADD COLUMN `rack_updated_at` DATETIME DEFAULT NULL AFTER `rack`");
    }

    // Update the PRODUCTS.rack field and rack_updated_at
    $nowMY = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `rack` = ?, `rack_updated_at` = ? WHERE `id` = ?");
    $stmt->bind_param("ssi", $rack, $nowMY, $id);
    $updated = $stmt->execute();
    $stmt->close();

    if ($updated && $barcode !== '') {
        // Sync rack_product table: remove old mappings for this product
        $delStmt = $connect->prepare("DELETE FROM `rack_product` WHERE `barcode` = ?");
        $delStmt->bind_param("s", $barcode);
        $delStmt->execute();
        $delStmt->close();

        // If a rack code was selected, insert new mapping
        if ($rack !== '') {
            $rackStmt = $connect->prepare("SELECT `id` FROM `rack` WHERE `code` = ? AND `status` = 'ACTIVE' LIMIT 1");
            $rackStmt->bind_param("s", $rack);
            $rackStmt->execute();
            $rackRow = $rackStmt->get_result()->fetch_assoc();
            $rackStmt->close();

            if ($rackRow) {
                $insStmt = $connect->prepare("INSERT INTO `rack_product` (`rack_id`, `barcode`, `assigned_at`) VALUES (?, ?, ?)");
                $insStmt->bind_param("iss", $rackRow['id'], $barcode, $nowMY);
                $insStmt->execute();
                $insStmt->close();
            }
        }
    }

    if ($updated) {
        $now = date('Y-m-d H:i:s');
        echo json_encode(['success' => true, 'rack' => $rack, 'rack_updated_at' => $now]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }

} elseif ($action === 'rack_list') {
    $result = $connect->query("SELECT `id`, `code`, `description` FROM `rack` WHERE `status`='ACTIVE' ORDER BY `code` ASC");
    $racks = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $racks[] = $r;
        }
    }
    echo json_encode($racks);

} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
