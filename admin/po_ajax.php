<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

// Generate next PO number: PO-YYYYMM-0001
function generatePONumber($connect) {
    $prefix = 'PO-' . date('Ym') . '-';
    $result = $connect->query("SELECT `po_number` FROM `purchase_order` WHERE `po_number` LIKE '$prefix%' ORDER BY `po_number` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['po_number'], -4));
        $next = $num + 1;
    }
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// Generate next GRN number
function generateGRNNumber($connect) {
    $prefix = 'GRN-' . date('Ym') . '-';
    $result = $connect->query("SELECT `grn_number` FROM `grn` WHERE `grn_number` LIKE '$prefix%' ORDER BY `grn_number` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['grn_number'], -4));
        $next = $num + 1;
    }
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

if ($action === 'lookup_product') {
    $barcode = trim($_POST['barcode'] ?? '');
    if ($barcode === '') {
        echo json_encode(['error' => 'No barcode']);
        exit;
    }
    $stmt = $connect->prepare("SELECT `name`, `uom` FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['name' => '', 'uom' => '']);
    }
    $stmt->close();

} elseif ($action === 'create') {
    $supplierId = intval($_POST['supplier_id'] ?? 0);
    $orderDate = trim($_POST['order_date'] ?? '');
    $expectedDate = trim($_POST['expected_date'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);
    $createdBy = $_SESSION['admin_name'] ?? 'Admin';

    if ($supplierId <= 0 || $orderDate === '') {
        echo json_encode(['error' => 'Supplier and order date are required.']);
        exit;
    }
    if (empty($items)) {
        echo json_encode(['error' => 'At least one line item is required.']);
        exit;
    }

    $poNumber = generatePONumber($connect);

    $expectedDateVal = $expectedDate !== '' ? $expectedDate : null;

    $stmt = $connect->prepare("INSERT INTO `purchase_order` (`po_number`,`supplier_id`,`order_date`,`expected_date`,`status`,`total_amount`,`remark`,`created_by`) VALUES (?,?,?,?,'DRAFT',0,?,?)");
    $stmt->bind_param("sissss", $poNumber, $supplierId, $orderDate, $expectedDateVal, $remark, $createdBy);

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Failed to create PO: ' . $connect->error]);
        $stmt->close();
        exit;
    }
    $newPoId = $connect->insert_id;
    $stmt->close();

    // Insert items
    $itemStmt = $connect->prepare("INSERT INTO `purchase_order_item` (`po_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,0,?)");
    foreach ($items as $item) {
        $barcode = trim($item['barcode'] ?? '');
        $desc = trim($item['product_desc'] ?? '');
        $qtyOrdered = floatval($item['qty_ordered'] ?? 0);
        $uom = trim($item['uom'] ?? '');
        $itemStmt->bind_param("issds", $newPoId, $barcode, $desc, $qtyOrdered, $uom);
        $itemStmt->execute();
    }
    $itemStmt->close();

    echo json_encode(['success' => 'PO ' . $poNumber . ' created.', 'po_id' => $newPoId]);

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $supplierId = intval($_POST['supplier_id'] ?? 0);
    $orderDate = trim($_POST['order_date'] ?? '');
    $expectedDate = trim($_POST['expected_date'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);

    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid PO ID.']);
        exit;
    }

    // Verify still DRAFT
    $chk = $connect->query("SELECT `status` FROM `purchase_order` WHERE `id` = $id LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'PO not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    if ($row['status'] !== 'DRAFT') {
        echo json_encode(['error' => 'Only DRAFT POs can be edited.']);
        exit;
    }

    $expectedDateVal = $expectedDate !== '' ? $expectedDate : null;

    $stmt = $connect->prepare("UPDATE `purchase_order` SET `supplier_id`=?,`order_date`=?,`expected_date`=?,`total_amount`=0,`remark`=? WHERE `id`=?");
    $stmt->bind_param("isssi", $supplierId, $orderDate, $expectedDateVal, $remark, $id);
    $stmt->execute();
    $stmt->close();

    // Delete old items and re-insert
    $connect->query("DELETE FROM `purchase_order_item` WHERE `po_id` = $id");

    $itemStmt = $connect->prepare("INSERT INTO `purchase_order_item` (`po_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,0,?)");
    foreach ($items as $item) {
        $barcode = trim($item['barcode'] ?? '');
        $desc = trim($item['product_desc'] ?? '');
        $qtyOrdered = floatval($item['qty_ordered'] ?? 0);
        $uom = trim($item['uom'] ?? '');
        $itemStmt->bind_param("issds", $id, $barcode, $desc, $qtyOrdered, $uom);
        $itemStmt->execute();
    }
    $itemStmt->close();

    echo json_encode(['success' => 'PO updated.', 'po_id' => $id]);

} elseif ($action === 'approve') {
    $id = intval($_POST['id'] ?? 0);
    $approvedBy = $_SESSION['admin_name'] ?? 'Admin';

    $stmt = $connect->prepare("UPDATE `purchase_order` SET `status`='APPROVED', `approved_by`=?, `approved_date`=NOW() WHERE `id`=? AND `status`='DRAFT'");
    $stmt->bind_param("si", $approvedBy, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => 'PO approved.']);
    } else {
        echo json_encode(['error' => 'Could not approve. PO may not be in DRAFT status.']);
    }
    $stmt->close();

} elseif ($action === 'cancel') {
    $id = intval($_POST['id'] ?? 0);

    $stmt = $connect->prepare("UPDATE `purchase_order` SET `status`='CANCELLED' WHERE `id`=? AND `status` IN ('DRAFT','APPROVED')");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => 'PO cancelled.']);
    } else {
        echo json_encode(['error' => 'Could not cancel.']);
    }
    $stmt->close();

} elseif ($action === 'get_po_items') {
    // Return PO items for GRN receiving
    $poId = intval($_POST['po_id'] ?? 0);
    $result = $connect->query("SELECT poi.*, po.supplier_id, po.po_number FROM `purchase_order_item` poi JOIN `purchase_order` po ON poi.po_id = po.id WHERE poi.po_id = $poId ORDER BY poi.id ASC");
    $items = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $r['qty_pending'] = $r['qty_ordered'] - $r['qty_received'];
            $items[] = $r;
        }
    }
    echo json_encode($items);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
