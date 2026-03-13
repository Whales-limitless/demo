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

// Generate next PO number: PO-YYYYMM-0001
function generatePONumber($connect) {
    $prefix = 'PO-' . date('Ym') . '-';
    $result = $connect->query("SELECT `po_number` FROM `purchase_order` WHERE `po_number` LIKE '" . $connect->real_escape_string($prefix) . "%' ORDER BY `po_number` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['po_number'], -4));
        $next = $num + 1;
    }
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// ==================== LIST PO ====================
if ($action === 'list') {
    $status = trim($_POST['status'] ?? '');
    $where = "1=1";
    $params = [];
    $types = "";

    if ($status !== '') {
        $where .= " AND po.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql = "SELECT po.*, s.name AS supplier_name FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE $where ORDER BY po.id DESC LIMIT 200";
    $stmt = $connect->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $pos = [];
    while ($r = $result->fetch_assoc()) {
        $pos[] = $r;
    }
    $stmt->close();
    echo json_encode(['pos' => $pos]);

// ==================== GET PO DETAIL ====================
} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT po.*, s.name AS supplier_name FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$po) {
        echo json_encode(['error' => 'PO not found.']);
        exit;
    }

    $itemResult = $connect->query("SELECT poi.*, p.img1 AS product_image FROM `purchase_order_item` poi LEFT JOIN `PRODUCTS` p ON poi.barcode = p.barcode WHERE poi.po_id = " . intval($id) . " ORDER BY poi.id ASC");
    $items = [];
    if ($itemResult) {
        while ($r = $itemResult->fetch_assoc()) {
            $items[] = $r;
        }
    }

    echo json_encode(['po' => $po, 'items' => $items]);

// ==================== SEARCH PRODUCTS ====================
} elseif ($action === 'search_products') {
    $search = trim($_POST['q'] ?? '');
    if ($search === '') {
        echo json_encode(['products' => []]);
        exit;
    }

    // Normalize quote variants
    $normalizedSearch = $search;
    $normalizedSearch = str_replace(["\u{201C}", "\u{201D}", "\u{2033}", "\u{FF02}"], '"', $normalizedSearch);
    $normalizedSearch = str_replace(["\u{2018}", "\u{2019}", "\u{2032}", "\u{FF07}"], "'", $normalizedSearch);
    $altSearch = str_replace('"', "''", $normalizedSearch);
    $altSearch2 = str_replace("''", '"', $normalizedSearch);
    $normalizedLike = '%' . $normalizedSearch . '%';
    $altLike = '%' . $altSearch . '%';
    $altLike2 = '%' . $altSearch2 . '%';

    $stmt = $connect->prepare("
        SELECT p.`id`, p.`barcode`, p.`name`, p.`img1` AS image, p.`uom`,
               COALESCE(p.`qoh`, 0) AS qoh, p.`cat_code`, p.`rack`
        FROM `PRODUCTS` p
        WHERE (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?
               OR p.`barcode` LIKE ? OR p.`barcode` LIKE ? OR p.`barcode` LIKE ?)
          AND (p.`checked` != 'N' OR p.`checked` IS NULL)
        ORDER BY p.`name` ASC
        LIMIT 50
    ");
    $stmt->bind_param("ssssss", $normalizedLike, $altLike, $altLike2, $normalizedLike, $altLike, $altLike2);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    $seen = [];
    while ($row = $result->fetch_assoc()) {
        if (isset($seen[$row['id']])) continue;
        $seen[$row['id']] = true;
        $row['id'] = (int)$row['id'];
        $row['qoh'] = (float)$row['qoh'];
        $products[] = $row;
    }
    $stmt->close();
    echo json_encode(['products' => $products]);

// ==================== LIST SUPPLIERS ====================
} elseif ($action === 'list_suppliers') {
    $result = $connect->query("SELECT `id`, `code`, `name` FROM `supplier` WHERE `status` = 'ACTIVE' ORDER BY `name` ASC");
    $list = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $list[] = $r;
        }
    }
    echo json_encode(['suppliers' => $list]);

// ==================== CREATE PO ====================
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

    $itemStmt = $connect->prepare("INSERT INTO `purchase_order_item` (`po_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,?,?)");
    $totalAmount = 0;
    foreach ($items as $item) {
        $barcode = trim($item['barcode'] ?? '');
        $desc = trim($item['product_desc'] ?? '');
        $qtyOrdered = floatval($item['qty_ordered'] ?? 0);
        $unitCost = floatval($item['unit_cost'] ?? 0);
        $uom = trim($item['uom'] ?? '');
        $totalAmount += $qtyOrdered * $unitCost;
        $itemStmt->bind_param("issdds", $newPoId, $barcode, $desc, $qtyOrdered, $unitCost, $uom);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $connect->query("UPDATE `purchase_order` SET `total_amount` = $totalAmount WHERE `id` = $newPoId");

    echo json_encode(['success' => 'PO ' . $poNumber . ' created.', 'po_id' => $newPoId]);

// ==================== UPDATE PO ====================
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

    $chk = $connect->prepare("SELECT `status` FROM `purchase_order` WHERE `id` = ? LIMIT 1");
    $chk->bind_param("i", $id);
    $chk->execute();
    $chkResult = $chk->get_result();
    if ($chkResult->num_rows === 0) {
        echo json_encode(['error' => 'PO not found.']);
        $chk->close();
        exit;
    }
    $row = $chkResult->fetch_assoc();
    $chk->close();
    if ($row['status'] !== 'DRAFT') {
        echo json_encode(['error' => 'Only DRAFT POs can be edited.']);
        exit;
    }

    $expectedDateVal = $expectedDate !== '' ? $expectedDate : null;

    $stmt = $connect->prepare("UPDATE `purchase_order` SET `supplier_id`=?,`order_date`=?,`expected_date`=?,`remark`=? WHERE `id`=?");
    $stmt->bind_param("isssi", $supplierId, $orderDate, $expectedDateVal, $remark, $id);
    $stmt->execute();
    $stmt->close();

    $delStmt = $connect->prepare("DELETE FROM `purchase_order_item` WHERE `po_id` = ?");
    $delStmt->bind_param("i", $id);
    $delStmt->execute();
    $delStmt->close();

    $itemStmt = $connect->prepare("INSERT INTO `purchase_order_item` (`po_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,?,?)");
    $totalAmount = 0;
    foreach ($items as $item) {
        $barcode = trim($item['barcode'] ?? '');
        $desc = trim($item['product_desc'] ?? '');
        $qtyOrdered = floatval($item['qty_ordered'] ?? 0);
        $unitCost = floatval($item['unit_cost'] ?? 0);
        $uom = trim($item['uom'] ?? '');
        $totalAmount += $qtyOrdered * $unitCost;
        $itemStmt->bind_param("issdds", $id, $barcode, $desc, $qtyOrdered, $unitCost, $uom);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $connect->query("UPDATE `purchase_order` SET `total_amount` = $totalAmount WHERE `id` = " . intval($id));

    echo json_encode(['success' => 'PO updated.', 'po_id' => $id]);

// ==================== APPROVE PO ====================
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

// ==================== CANCEL PO ====================
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

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
