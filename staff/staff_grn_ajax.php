<?php
require_once __DIR__ . '/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'dbconnection.php';
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

// Ensure grn_item has UOM conversion columns
$chkCol = $connect->query("SHOW COLUMNS FROM `grn_item` LIKE 'receive_uom'");
if ($chkCol && $chkCol->num_rows === 0) {
    $connect->query("ALTER TABLE `grn_item` ADD COLUMN `receive_uom` VARCHAR(20) DEFAULT NULL AFTER `qty_rejected`");
    $connect->query("ALTER TABLE `grn_item` ADD COLUMN `qty_converted` DOUBLE(8,2) DEFAULT NULL AFTER `receive_uom`");
    $connect->query("ALTER TABLE `grn_item` ADD COLUMN `inventory_uom` VARCHAR(20) DEFAULT NULL AFTER `qty_converted`");
}

// Ensure PRODUCTS has stock_in_at column
$chkStockInAt = $connect->query("SHOW COLUMNS FROM `PRODUCTS` LIKE 'stock_in_at'");
if ($chkStockInAt && $chkStockInAt->num_rows === 0) {
    $connect->query("ALTER TABLE `PRODUCTS` ADD COLUMN `stock_in_at` DATETIME DEFAULT NULL AFTER `rack_updated_at`");
}

// Ensure uom_conversion table exists
$connect->query("CREATE TABLE IF NOT EXISTS `uom_conversion` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `barcode` VARCHAR(50) NOT NULL,
    `from_uom` VARCHAR(20) NOT NULL,
    `to_uom` VARCHAR(20) NOT NULL,
    `conversion_factor` DOUBLE(10,4) NOT NULL DEFAULT 1.0000,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_barcode_from_to` (`barcode`, `from_uom`, `to_uom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

// ==================== LIST GRN ====================
if ($action === 'list') {
    $result = $connect->query("SELECT g.*, s.name AS supplier_name, po.po_number FROM `grn` g LEFT JOIN `supplier` s ON g.supplier_id = s.id LEFT JOIN `purchase_order` po ON g.po_id = po.id ORDER BY g.id DESC LIMIT 200");
    $grns = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $grns[] = $r;
        }
    }
    echo json_encode(['grns' => $grns]);

// ==================== GET GRN DETAIL ====================
} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT g.*, s.name AS supplier_name, po.po_number FROM `grn` g LEFT JOIN `supplier` s ON g.supplier_id = s.id LEFT JOIN `purchase_order` po ON g.po_id = po.id WHERE g.id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $grn = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$grn) {
        echo json_encode(['error' => 'GRN not found.']);
        exit;
    }

    $itemResult = $connect->query("SELECT gi.*, p.img1 AS product_image FROM `grn_item` gi LEFT JOIN `PRODUCTS` p ON gi.barcode = p.barcode WHERE gi.grn_id = " . intval($id) . " ORDER BY gi.id ASC");
    $items = [];
    if ($itemResult) {
        while ($r = $itemResult->fetch_assoc()) {
            $items[] = $r;
        }
    }

    echo json_encode(['grn' => $grn, 'items' => $items]);

// ==================== LIST RECEIVABLE POS ====================
} elseif ($action === 'list_receivable_pos') {
    $result = $connect->query("SELECT po.id, po.po_number, s.name AS supplier_name, s.id AS supplier_id FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.status IN ('APPROVED','PARTIALLY_RECEIVED') ORDER BY po.id DESC");
    $pos = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $pos[] = $r;
        }
    }
    echo json_encode(['pos' => $pos]);

// ==================== GET PO ITEMS FOR RECEIVING ====================
} elseif ($action === 'get_po_items') {
    $poId = intval($_POST['po_id'] ?? 0);
    $stmt = $connect->prepare("SELECT po.*, s.name AS supplier_name, s.id AS supplier_id FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.id = ? AND po.status IN ('APPROVED','PARTIALLY_RECEIVED') LIMIT 1");
    $stmt->bind_param("i", $poId);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$po) {
        echo json_encode(['error' => 'PO not found or not in receivable status.']);
        exit;
    }

    $itemResult = $connect->query("SELECT poi.*, p.img1 AS product_image, p.rack FROM `purchase_order_item` poi LEFT JOIN `PRODUCTS` p ON poi.barcode = p.barcode WHERE poi.po_id = " . intval($poId) . " ORDER BY poi.id ASC");
    $items = [];
    if ($itemResult) {
        while ($r = $itemResult->fetch_assoc()) {
            $r['qty_pending'] = floatval($r['qty_ordered']) - floatval($r['qty_received']);
            $items[] = $r;
        }
    }
    echo json_encode(['po' => $po, 'items' => $items]);

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

// ==================== SEARCH PRODUCTS ====================
} elseif ($action === 'search_products') {
    $search = trim($_POST['q'] ?? '');
    if ($search === '') {
        echo json_encode(['products' => []]);
        exit;
    }

    // Normalize quote variants (same logic as navSearch)
    $normalizedSearch = $search;
    $normalizedSearch = str_replace(["\u{201C}", "\u{201D}", "\u{2033}", "\u{FF02}"], '"', $normalizedSearch);
    $normalizedSearch = str_replace(["\u{2018}", "\u{2019}", "\u{2032}", "\u{FF07}"], "'", $normalizedSearch);
    $altSearch = str_replace('"', "''", $normalizedSearch);
    $altSearch2 = str_replace("''", '"', $normalizedSearch);
    $normalizedLike = '%' . $normalizedSearch . '%';
    $altLike = '%' . $altSearch . '%';
    $altLike2 = '%' . $altSearch2 . '%';

    $offset = max(0, intval($_POST['offset'] ?? 0));
    $limit = 50;

    // Get total count
    $cntStmt = $connect->prepare("
        SELECT COUNT(DISTINCT p.`id`) AS cnt
        FROM `PRODUCTS` p
        WHERE (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?)
          AND (p.`checked` != 'N' OR p.`checked` IS NULL)
          AND EXISTS (SELECT 1 FROM `category` c WHERE c.`cat_code` = p.`cat_code` AND c.`sub_code` = p.`sub_code`)
    ");
    $cntStmt->bind_param("sss", $normalizedLike, $altLike, $altLike2);
    $cntStmt->execute();
    $total = (int)$cntStmt->get_result()->fetch_assoc()['cnt'];
    $cntStmt->close();

    $stmt = $connect->prepare("
        SELECT DISTINCT p.`id`, p.`barcode`, p.`name`, p.`img1` AS image, p.`uom`,
               COALESCE(p.`qoh`, 0) AS qoh, p.`cat_code`, p.`rack`,
               c.`cat_name` AS category_name
        FROM `PRODUCTS` p
        INNER JOIN `category` c ON p.`cat_code` = c.`cat_code` AND p.`sub_code` = c.`sub_code`
        WHERE (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?)
          AND (p.`checked` != 'N' OR p.`checked` IS NULL)
        ORDER BY p.`name` ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sssii", $normalizedLike, $altLike, $altLike2, $limit, $offset);
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
    echo json_encode(['products' => $products, 'total' => $total]);

// ==================== RECEIVE GRN ====================
} elseif ($action === 'receive') {
    $poId = intval($_POST['po_id'] ?? 0);
    $supplierId = intval($_POST['supplier_id'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);
    $receivedBy = $_SESSION['user_name'] ?? 'Staff';

    if ($supplierId <= 0) {
        echo json_encode(['error' => 'Invalid supplier.']);
        exit;
    }
    if (empty($items)) {
        echo json_encode(['error' => 'No items to receive.']);
        exit;
    }

    if ($poId > 0) {
        $chk = $connect->prepare("SELECT `status` FROM `purchase_order` WHERE `id` = ? LIMIT 1");
        $chk->bind_param("i", $poId);
        $chk->execute();
        $chkResult = $chk->get_result();
        if ($chkResult->num_rows === 0) {
            echo json_encode(['error' => 'PO not found.']);
            $chk->close();
            exit;
        }
        $row = $chkResult->fetch_assoc();
        $chk->close();
        if (!in_array($row['status'], ['APPROVED', 'PARTIALLY_RECEIVED'])) {
            echo json_encode(['error' => 'PO is not in a receivable status.']);
            exit;
        }
    }

    $connect->begin_transaction();

    try {
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

        $grnItemStmt = $connect->prepare("INSERT INTO `grn_item` (`grn_id`,`po_item_id`,`barcode`,`product_desc`,`qty_received`,`qty_rejected`,`receive_uom`,`qty_converted`,`inventory_uom`,`unit_cost`,`batch_no`,`exp_date`,`rack_location`) VALUES (?,?,?,?,?,?,?,?,?,0,?,?,?)");
        $stockInAt = date('Y-m-d H:i:s');
        $updateQohStmt = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) + ?, `stock_in_at` = ? WHERE `barcode` = ?");
        $updatePoItemStmt = $connect->prepare("UPDATE `purchase_order_item` SET `qty_received` = COALESCE(`qty_received`, 0) + ? WHERE `id` = ?");

        foreach ($items as $item) {
            $poItemId = !empty($item['po_item_id']) ? intval($item['po_item_id']) : null;
            $barcode = trim($item['barcode'] ?? '');
            $desc = trim($item['product_desc'] ?? '');
            $qtyReceived = floatval($item['qty_received'] ?? 0);
            $qtyRejected = floatval($item['qty_rejected'] ?? 0);
            $receiveUom = trim($item['receive_uom'] ?? '');
            $convFactor = floatval($item['conversion_factor'] ?? 1);
            $qtyConverted = floatval($item['qty_converted'] ?? $qtyReceived);
            $inventoryUom = trim($item['inventory_uom'] ?? '');
            $batchNo = trim($item['batch_no'] ?? '');
            $expDate = trim($item['exp_date'] ?? '') !== '' ? trim($item['exp_date']) : null;
            $rackLoc = trim($item['rack_location'] ?? '');

            if ($qtyConverted <= 0) {
                $qtyConverted = $qtyReceived;
            }
            $receiveUomVal = $receiveUom !== '' ? $receiveUom : null;
            $inventoryUomVal = $inventoryUom !== '' ? $inventoryUom : null;
            $qtyConvertedVal = $qtyConverted > 0 ? $qtyConverted : null;

            $grnItemStmt->bind_param("iissddsdssss", $grnId, $poItemId, $barcode, $desc, $qtyReceived, $qtyRejected, $receiveUomVal, $qtyConvertedVal, $inventoryUomVal, $batchNo, $expDate, $rackLoc);
            if (!$grnItemStmt->execute()) {
                throw new Exception('Failed to insert GRN item: ' . $connect->error);
            }

            // Use converted qty for inventory update (base UOM)
            $stockQty = $qtyConverted > 0 ? $qtyConverted : $qtyReceived;
            if ($stockQty > 0 && $barcode !== '') {
                $updateQohStmt->bind_param("dss", $stockQty, $stockInAt, $barcode);
                $updateQohStmt->execute();
            }

            // PO item tracking stays in PO UOM
            if ($poItemId && $qtyReceived > 0) {
                $updatePoItemStmt->bind_param("di", $qtyReceived, $poItemId);
                $updatePoItemStmt->execute();
            }
        }

        $grnItemStmt->close();
        $updateQohStmt->close();
        $updatePoItemStmt->close();

        if ($poId > 0) {
            $poItemsResult = $connect->query("SELECT SUM(`qty_ordered`) as total_ordered, SUM(COALESCE(`qty_received`,0)) as total_received FROM `purchase_order_item` WHERE `po_id` = " . intval($poId));
            if ($poItemsResult && $poRow = $poItemsResult->fetch_assoc()) {
                $totalOrdered = floatval($poRow['total_ordered']);
                $totalReceived = floatval($poRow['total_received']);
                if ($totalReceived >= $totalOrdered) {
                    $connect->query("UPDATE `purchase_order` SET `status` = 'RECEIVED' WHERE `id` = " . intval($poId));
                } else {
                    $connect->query("UPDATE `purchase_order` SET `status` = 'PARTIALLY_RECEIVED' WHERE `id` = " . intval($poId));
                }
            }
        }

        $connect->commit();
        echo json_encode(['success' => 'GRN ' . $grnNumber . ' created. Stock quantities updated.', 'grn_id' => $grnId]);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

// ==================== UOM CONVERSION LOOKUP ====================
} elseif ($action === 'uom_conversion_lookup') {
    $barcode = trim($_POST['barcode'] ?? '');
    $fromUom = trim($_POST['from_uom'] ?? '');
    if ($barcode === '' || $fromUom === '') {
        echo json_encode(['found' => false]);
        exit;
    }

    // Get product base UOM
    $pStmt = $connect->prepare("SELECT `uom` FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
    $pStmt->bind_param("s", $barcode);
    $pStmt->execute();
    $pRow = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();
    $baseUom = $pRow ? trim($pRow['uom'] ?? '') : '';

    if ($fromUom === $baseUom || $baseUom === '') {
        echo json_encode(['found' => false, 'base_uom' => $baseUom]);
        exit;
    }

    $cStmt = $connect->prepare("SELECT `conversion_factor`, `to_uom` FROM `uom_conversion` WHERE `barcode` = ? AND `from_uom` = ? AND `to_uom` = ? LIMIT 1");
    $cStmt->bind_param("sss", $barcode, $fromUom, $baseUom);
    $cStmt->execute();
    $cRow = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();

    if ($cRow) {
        echo json_encode([
            'found' => true,
            'from_uom' => $fromUom,
            'to_uom' => $cRow['to_uom'],
            'conversion_factor' => floatval($cRow['conversion_factor']),
            'base_uom' => $baseUom
        ]);
    } else {
        echo json_encode(['found' => false, 'base_uom' => $baseUom]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
