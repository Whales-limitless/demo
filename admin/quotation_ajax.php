<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

$action = $_POST['action'] ?? '';

// export_excel writes a binary stream; everything else returns JSON.
if ($action !== 'export_excel') {
    header('Content-Type: application/json; charset=utf-8');
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($action === 'export_excel') {
        header('Content-Type: text/plain');
        echo 'Unauthorized';
    } else {
        echo json_encode(['error' => 'Unauthorized']);
    }
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Generate next PO number: PO-YYYYMM-0001
function generateQuotationNumber($connect) {
    $prefix = 'QT-' . date('Ym') . '-';
    $result = $connect->query("SELECT `quotation_number` FROM `quotation` WHERE `quotation_number` LIKE '" . $connect->real_escape_string($prefix) . "%' ORDER BY `quotation_number` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['quotation_number'], -4));
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
    } else {
        $where .= " AND po.status != 'DONE'";
    }

    $sql = "SELECT po.*, s.name AS supplier_name FROM `quotation` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE $where ORDER BY po.id DESC LIMIT 200";
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
    $stmt = $connect->prepare("SELECT po.*, s.name AS supplier_name FROM `quotation` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$po) {
        echo json_encode(['error' => 'Quotation not found.']);
        exit;
    }

    $itemResult = $connect->query("SELECT poi.*, p.img1 AS product_image FROM `quotation_item` poi LEFT JOIN `PRODUCTS` p ON poi.barcode = p.barcode WHERE poi.quotation_id = " . intval($id) . " ORDER BY poi.id ASC");
    $items = [];
    if ($itemResult) {
        while ($r = $itemResult->fetch_assoc()) {
            $items[] = $r;
        }
    }

    echo json_encode(['po' => $po, 'items' => $items]);

// ==================== SEARCH PRODUCTS ====================
} elseif ($action === 'search_products') {
    $search = $_POST['q'] ?? '';
    if ($search === '') {
        echo json_encode(['products' => [], 'total' => 0]);
        exit;
    }

    $offset = max(0, intval($_POST['offset'] ?? 0));
    $limit = 50;

    // Normalize quote variants
    $normalizedSearch = $search;
    $normalizedSearch = str_replace(["\u{201C}", "\u{201D}", "\u{2033}", "\u{FF02}"], '"', $normalizedSearch);
    $normalizedSearch = str_replace(["\u{2018}", "\u{2019}", "\u{2032}", "\u{FF07}"], "'", $normalizedSearch);
    $altSearch = str_replace('"', "''", $normalizedSearch);
    $altSearch2 = str_replace("''", '"', $normalizedSearch);
    $normalizedLike = '%' . $normalizedSearch . '%';
    $altLike = '%' . $altSearch . '%';
    $altLike2 = '%' . $altSearch2 . '%';

    // Get total count — search by product name only, must have valid category (same as All Products page)
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
        echo json_encode(['error' => 'Customer and order date are required.']);
        exit;
    }
    if (empty($items)) {
        echo json_encode(['error' => 'At least one line item is required.']);
        exit;
    }

    $poNumber = generateQuotationNumber($connect);
    $expectedDateVal = $expectedDate !== '' ? $expectedDate : null;

    $stmt = $connect->prepare("INSERT INTO `quotation` (`quotation_number`,`supplier_id`,`order_date`,`expected_date`,`status`,`total_amount`,`remark`,`created_by`) VALUES (?,?,?,?,'DRAFT',0,?,?)");
    $stmt->bind_param("sissss", $poNumber, $supplierId, $orderDate, $expectedDateVal, $remark, $createdBy);

    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Failed to create quotation: ' . $connect->error]);
        $stmt->close();
        exit;
    }
    $newPoId = $connect->insert_id;
    $stmt->close();

    $itemStmt = $connect->prepare("INSERT INTO `quotation_item` (`quotation_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,?,?)");
    $prodNameStmt = $connect->prepare("UPDATE `PRODUCTS` SET `name` = ? WHERE `barcode` = ?");
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
        // Update product name in PRODUCTS table
        if ($barcode !== '' && $desc !== '') {
            $prodNameStmt->bind_param("ss", $desc, $barcode);
            $prodNameStmt->execute();
        }
    }
    $itemStmt->close();
    $prodNameStmt->close();

    $connect->query("UPDATE `quotation` SET `total_amount` = $totalAmount WHERE `id` = $newPoId");

    // Invalidate staff product cache after product name updates
    $cacheDir = sys_get_temp_dir() . '/pw_product_cache';
    @unlink($cacheDir . '/all_products.json');
    @unlink($cacheDir . '/pending_qty.json');
    echo json_encode(['success' => 'Quotation ' . $poNumber . ' created.', 'quotation_id' => $newPoId]);

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

    $chk = $connect->prepare("SELECT `status` FROM `quotation` WHERE `id` = ? LIMIT 1");
    $chk->bind_param("i", $id);
    $chk->execute();
    $chkResult = $chk->get_result();
    if ($chkResult->num_rows === 0) {
        echo json_encode(['error' => 'Quotation not found.']);
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

    $stmt = $connect->prepare("UPDATE `quotation` SET `supplier_id`=?,`order_date`=?,`expected_date`=?,`remark`=? WHERE `id`=?");
    $stmt->bind_param("isssi", $supplierId, $orderDate, $expectedDateVal, $remark, $id);
    $stmt->execute();
    $stmt->close();

    $delStmt = $connect->prepare("DELETE FROM `quotation_item` WHERE `quotation_id` = ?");
    $delStmt->bind_param("i", $id);
    $delStmt->execute();
    $delStmt->close();

    $itemStmt = $connect->prepare("INSERT INTO `quotation_item` (`quotation_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,?,?)");
    $prodNameStmt = $connect->prepare("UPDATE `PRODUCTS` SET `name` = ? WHERE `barcode` = ?");
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
        // Update product name in PRODUCTS table
        if ($barcode !== '' && $desc !== '') {
            $prodNameStmt->bind_param("ss", $desc, $barcode);
            $prodNameStmt->execute();
        }
    }
    $itemStmt->close();
    $prodNameStmt->close();

    $connect->query("UPDATE `quotation` SET `total_amount` = $totalAmount WHERE `id` = " . intval($id));

    // Invalidate staff product cache after product name updates
    $cacheDir = sys_get_temp_dir() . '/pw_product_cache';
    @unlink($cacheDir . '/all_products.json');
    @unlink($cacheDir . '/pending_qty.json');
    echo json_encode(['success' => 'Quotation updated.', 'quotation_id' => $id]);

// ==================== APPROVE PO ====================
} elseif ($action === 'approve') {
    $id = intval($_POST['id'] ?? 0);
    $approvedBy = $_SESSION['admin_name'] ?? 'Admin';

    $stmt = $connect->prepare("UPDATE `quotation` SET `status`='APPROVED', `approved_by`=?, `approved_date`=NOW() WHERE `id`=? AND `status`='DRAFT'");
    $stmt->bind_param("si", $approvedBy, $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => 'Quotation approved.']);
    } else {
        echo json_encode(['error' => 'Could not approve. Quotation may not be in DRAFT status.']);
    }
    $stmt->close();

// ==================== CANCEL PO ====================
} elseif ($action === 'cancel') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("UPDATE `quotation` SET `status`='CANCELLED' WHERE `id`=? AND `status` IN ('DRAFT','APPROVED')");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => 'Quotation cancelled.']);
    } else {
        echo json_encode(['error' => 'Could not cancel.']);
    }
    $stmt->close();

// ==================== MARK PO AS DONE ====================
} elseif ($action === 'mark_done') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("UPDATE `quotation` SET `status`='DONE' WHERE `id`=? AND `status` != 'DONE'");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => 'Quotation marked as done.']);
    } else {
        echo json_encode(['error' => 'Could not mark as done.']);
    }
    $stmt->close();

} else if ($action === 'update_product_name') {
    $barcode = trim($_POST['barcode'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($barcode === '') {
        echo json_encode(['error' => 'Barcode is required']);
        exit;
    }
    if ($name === '') {
        echo json_encode(['error' => 'Product name cannot be empty']);
        exit;
    }
    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `name` = ? WHERE `barcode` = ?");
    $stmt->bind_param("ss", $name, $barcode);
    $stmt->execute();
    $stmt->close();
    // Invalidate staff product cache after product name update
    $cacheDir = sys_get_temp_dir() . '/pw_product_cache';
    @unlink($cacheDir . '/all_products.json');
    @unlink($cacheDir . '/pending_qty.json');
    echo json_encode(['success' => true, 'name' => $name]);

// ==================== GET COMPANY SETTING (letterhead) ====================
} elseif ($action === 'get_company') {
    $connect->query("CREATE TABLE IF NOT EXISTS `company_setting` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `business_name` VARCHAR(255) NOT NULL DEFAULT '',
        `business_register_no` VARCHAR(100) NOT NULL DEFAULT '',
        `address_line1` VARCHAR(255) NOT NULL DEFAULT '',
        `address_line2` VARCHAR(255) NOT NULL DEFAULT '',
        `address_line3` VARCHAR(255) NOT NULL DEFAULT '',
        `tel_no` VARCHAR(50) NOT NULL DEFAULT '',
        `email` VARCHAR(150) NOT NULL DEFAULT '',
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $row = $connect->query("SELECT * FROM `company_setting` WHERE `id` = 1 LIMIT 1");
    $company = $row ? $row->fetch_assoc() : null;
    echo json_encode(['company' => $company ?: []]);

// ==================== PRODUCT IMAGES TO BASE64 (for PDF embedding) ====================
} elseif ($action === 'images_base64') {
    $paths = json_decode($_POST['paths'] ?? '[]', true);
    if (!is_array($paths)) { echo json_encode([]); exit; }
    $imgDir = __DIR__ . '/../img/';
    $result = [];
    foreach ($paths as $path) {
        $path = (string)$path;
        if ($path === '') continue;
        // Prevent directory traversal
        if (strpos($path, '..') !== false || strpos($path, '/') === 0 || strpos($path, '\\') !== false) {
            $result[$path] = '';
            continue;
        }
        $filePath = $imgDir . $path;
        if (file_exists($filePath)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($filePath) : 'image/jpeg';
            $data = base64_encode(file_get_contents($filePath));
            $result[$path] = 'data:' . $mime . ';base64,' . $data;
        } else {
            $result[$path] = '';
        }
    }
    echo json_encode($result);

// ==================== EXPORT EXCEL ====================
} elseif ($action === 'export_excel') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new Exception("$errstr in $errfile:$errline");
    });
    try {
        require_once __DIR__ . '/../staff/SimpleXlsxWriter.php';

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid PO id.']);
            exit;
        }

        $stmt = $connect->prepare("SELECT po.*, s.name AS supplier_name FROM `quotation` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $po = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$po) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Quotation not found.']);
            exit;
        }

        $itemRes = $connect->query("SELECT poi.*, p.img1 AS product_image FROM `quotation_item` poi LEFT JOIN `PRODUCTS` p ON poi.barcode = p.barcode WHERE poi.quotation_id = " . intval($id) . " ORDER BY poi.id ASC");
        $items = [];
        if ($itemRes) { while ($r = $itemRes->fetch_assoc()) $items[] = $r; }

        // Company header
        $cRow = $connect->query("SELECT * FROM `company_setting` WHERE `id` = 1 LIMIT 1");
        $company = $cRow ? $cRow->fetch_assoc() : [];

        $xlsx = new SimpleXlsxWriter();
        $xlsx->setTitle('Quotation');
        $xlsx->setColWidths([5, 14, 18, 38, 8, 10]);

        // Letterhead block
        $bizName = trim(($company['business_name'] ?? '') . ($company['business_register_no'] ? ' (' . $company['business_register_no'] . ')' : ''));
        if ($bizName !== '') $xlsx->addRow([$bizName], [1]);
        foreach (['address_line1','address_line2','address_line3'] as $k) {
            if (!empty($company[$k])) $xlsx->addRow([$company[$k]]);
        }
        $contactBits = [];
        if (!empty($company['tel_no'])) $contactBits[] = 'TEL NO: ' . $company['tel_no'];
        if (!empty($company['email'])) $contactBits[] = 'EMAIL: ' . $company['email'];
        if ($contactBits) $xlsx->addRow([implode('   |   ', $contactBits)]);
        $xlsx->addRow(['']);

        // PO meta
        $xlsx->addRow(['QUOTATION: ' . ($po['quotation_number'] ?? '')], [1]);
        $xlsx->addRow(['Status', $po['status'] ?? '', '', 'Created By', $po['created_by'] ?? '']);
        $xlsx->addRow(['Customer', $po['supplier_name'] ?? '', '', 'Approved By', $po['approved_by'] ?? '']);
        $xlsx->addRow(['Order Date', $po['order_date'] ?? '', '', 'Approved Date', $po['approved_date'] ?? '']);
        $xlsx->addRow(['Expected Date', $po['expected_date'] ?? '', '', 'Remark', $po['remark'] ?? '']);
        $xlsx->addRow(['']);

        // Item headers
        $xlsx->addRow(['No', 'Image', 'Barcode', 'Product', 'UOM', 'Qty'], [2,2,2,2,2,2]);

        $imgDir = __DIR__ . '/../img/';
        $totalQty = 0;
        foreach ($items as $i => $it) {
            $qty = floatval($it['qty_ordered'] ?? 0);
            $totalQty += $qty;
            $xlsx->addRow([
                $i + 1,
                '',
                $it['barcode'] ?? '',
                $it['product_desc'] ?? '',
                $it['uom'] ?? '',
                $qty,
            ], [3,3,3,3,3,3]);
        }
        $xlsx->addRow(['', '', '', 'Total:', '', $totalQty], [1,1,1,1,1,1]);

        // Re-attach images now that we know absolute row indices.
        // Recompute by counting prior rows.
        $headerRowsCount = 0; // letterhead block lines
        if ($bizName !== '') $headerRowsCount++;
        foreach (['address_line1','address_line2','address_line3'] as $k) {
            if (!empty($company[$k])) $headerRowsCount++;
        }
        if ($contactBits) $headerRowsCount++;
        // Plus separator + 5 meta rows + separator + table-header = 7
        $preItemRows = $headerRowsCount + 1 + 5 + 1 + 1; // last 1 = item-header row
        // SimpleXlsxWriter rows are 0-indexed counting from FIRST row added, but addImage row arg
        // refers to 0 = FIRST data row (header is row index 0 separately). Looking at stock_loss usage:
        //   header row added first, then items, image row $i+1 (so item 0 uses row 1).
        // Here our "item header" is the (preItemRows)th row added. So item i is at addImage row = preItemRows + i.
        foreach ($items as $i => $it) {
            if (!empty($it['product_image'])) {
                $imgPath = $imgDir . $it['product_image'];
                if (file_exists($imgPath)) {
                    $xlsx->addImage($preItemRows + $i, 1, $imgPath, 60, 50);
                }
            }
        }

        $tmpFile = $xlsx->generate();
        if (!$tmpFile) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to generate file.']);
            exit;
        }

        $title = 'QT_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $po['quotation_number'] ?? ('id_' . $id));
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $title . '.xlsx"');
        header('Content-Length: ' . filesize($tmpFile));
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo 'Export error: ' . $e->getMessage();
        exit;
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
