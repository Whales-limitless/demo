<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

function generateGRNNumber($connect) {
    $prefix = 'GRN-' . date('Ym') . '-';
    $result = $connect->query("SELECT `grn_number` FROM `grn` WHERE `grn_number` LIKE '" . $connect->real_escape_string($prefix) . "%' ORDER BY `grn_number` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['grn_number'], -4));
        $next = $num + 1;
    }
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

if ($action === 'receive') {
    $poId = intval($_POST['po_id'] ?? 0);
    $supplierId = intval($_POST['supplier_id'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);
    $receivedBy = $_SESSION['admin_name'] ?? 'Admin';

    if ($supplierId <= 0) {
        echo json_encode(['error' => 'Invalid supplier.']);
        exit;
    }
    if (empty($items)) {
        echo json_encode(['error' => 'No items to receive.']);
        exit;
    }

    // Verify PO status
    if ($poId > 0) {
        $chk = $connect->query("SELECT `status` FROM `purchase_order` WHERE `id` = $poId LIMIT 1");
        if (!$chk || $chk->num_rows === 0) {
            echo json_encode(['error' => 'PO not found.']);
            exit;
        }
        $row = $chk->fetch_assoc();
        if (!in_array($row['status'], ['APPROVED', 'PARTIALLY_RECEIVED'])) {
            echo json_encode(['error' => 'PO is not in a receivable status.']);
            exit;
        }
    }

    $connect->begin_transaction();

    try {
        // Create GRN header
        $grnNumber = generateGRNNumber($connect);
        $receiveDate = date('Y-m-d');
        $poIdVal = $poId > 0 ? $poId : null;

        $stmt = $connect->prepare("INSERT INTO `grn` (`grn_number`,`po_id`,`supplier_id`,`receive_date`,`received_by`,`remark`) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("siisss", $grnNumber, $poIdVal, $supplierId, $receiveDate, $receivedBy, $remark);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create GRN: ' . $connect->error);
        }
        $grnId = $connect->insert_id;
        $stmt->close();

        // Insert GRN items + update PRODUCTS.qoh + update PO item qty_received
        $grnItemStmt = $connect->prepare("INSERT INTO `grn_item` (`grn_id`,`po_item_id`,`barcode`,`product_desc`,`qty_received`,`qty_rejected`,`unit_cost`,`batch_no`,`rack_location`) VALUES (?,?,?,?,?,?,0,?,?)");
        $updateQohStmt = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) + ? WHERE `barcode` = ?");
        $updatePoItemStmt = $connect->prepare("UPDATE `purchase_order_item` SET `qty_received` = `qty_received` + ? WHERE `id` = ?");

        foreach ($items as $item) {
            $poItemId = !empty($item['po_item_id']) ? intval($item['po_item_id']) : null;
            $barcode = trim($item['barcode'] ?? '');
            $desc = trim($item['product_desc'] ?? '');
            $qtyReceived = floatval($item['qty_received'] ?? 0);
            $qtyRejected = floatval($item['qty_rejected'] ?? 0);
            $batchNo = trim($item['batch_no'] ?? '');
            $rackLoc = trim($item['rack_location'] ?? '');

            // Insert GRN line item
            $grnItemStmt->bind_param("iissddss", $grnId, $poItemId, $barcode, $desc, $qtyReceived, $qtyRejected, $batchNo, $rackLoc);
            if (!$grnItemStmt->execute()) {
                throw new Exception('Failed to insert GRN item: ' . $connect->error);
            }

            // AUTO UPDATE QOH: Add received qty to PRODUCTS.qoh
            if ($qtyReceived > 0 && $barcode !== '') {
                $updateQohStmt->bind_param("ds", $qtyReceived, $barcode);
                $updateQohStmt->execute();
            }

            // Update PO item qty_received
            if ($poItemId && $qtyReceived > 0) {
                $updatePoItemStmt->bind_param("di", $qtyReceived, $poItemId);
                $updatePoItemStmt->execute();
            }
        }

        $grnItemStmt->close();
        $updateQohStmt->close();
        $updatePoItemStmt->close();

        // Update PO status based on receiving completeness
        if ($poId > 0) {
            $poItemsResult = $connect->query("SELECT SUM(`qty_ordered`) as total_ordered, SUM(`qty_received`) as total_received FROM `purchase_order_item` WHERE `po_id` = $poId");
            if ($poItemsResult && $poRow = $poItemsResult->fetch_assoc()) {
                $totalOrdered = floatval($poRow['total_ordered']);
                $totalReceived = floatval($poRow['total_received']);

                if ($totalReceived >= $totalOrdered) {
                    $connect->query("UPDATE `purchase_order` SET `status` = 'RECEIVED' WHERE `id` = $poId");
                } else {
                    $connect->query("UPDATE `purchase_order` SET `status` = 'PARTIALLY_RECEIVED' WHERE `id` = $poId");
                }
            }
        }

        $connect->commit();
        echo json_encode(['success' => 'GRN ' . $grnNumber . ' created. Stock quantities updated.', 'grn_id' => $grnId]);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
