<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'dbconnection.php';
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

// ==================== PRODUCT SEARCH ====================
if ($action === 'search_products') {
    $search = $_POST['q'] ?? '';
    if ($search === '') {
        echo json_encode(['products' => [], 'total' => 0]);
        exit;
    }

    $offset = max(0, intval($_POST['offset'] ?? 0));
    $limit = 50;

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

// ==================== QUICK CREATE PRODUCT ====================
} elseif ($action === 'quick_create_product') {
    $name = trim($_POST['name'] ?? '');
    $uom = trim($_POST['uom'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');

    if ($name === '') {
        echo json_encode(['error' => 'Product name is required.']);
        exit;
    }

    if ($barcode === '') {
        $barcode = 'NEW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
    }

    $chk = $connect->prepare("SELECT `id` FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
    $chk->bind_param("s", $barcode);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Barcode already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    // Handle image upload
    $image = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $uploadDir = __DIR__ . '/../img/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mimeType, $allowedTypes) && $file['size'] <= 10 * 1024 * 1024) {
            $fileName = 'prod_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
            $destPath = $uploadDir . $fileName;
            switch ($mimeType) {
                case 'image/jpeg': $img = @imagecreatefromjpeg($file['tmp_name']); break;
                case 'image/png':  $img = @imagecreatefrompng($file['tmp_name']); break;
                case 'image/gif':  $img = @imagecreatefromgif($file['tmp_name']); break;
                case 'image/webp': $img = @imagecreatefromwebp($file['tmp_name']); break;
                default: $img = false;
            }
            if ($img) {
                $maxDim = 800;
                $origW = imagesx($img);
                $origH = imagesy($img);
                if ($origW > $maxDim || $origH > $maxDim) {
                    if ($origW >= $origH) { $newW = $maxDim; $newH = intval($origH * $maxDim / $origW); }
                    else { $newH = $maxDim; $newW = intval($origW * $maxDim / $origH); }
                    $resized = imagecreatetruecolor($newW, $newH);
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                    imagedestroy($img);
                    $img = $resized;
                }
                imagejpeg($img, $destPath, 75);
                imagedestroy($img);
                $image = $fileName;
            }
        }
    }

    $checked = 'Y';
    $qoh = 0.0;
    $empty = '';
    $stmt = $connect->prepare("INSERT INTO `PRODUCTS` (`barcode`,`code`,`name`,`description`,`cat`,`sub_cat`,`cat_code`,`sub_code`,`uom`,`rack`,`qoh`,`checked`,`img1`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssssdss", $barcode, $empty, $name, $empty, $empty, $empty, $empty, $empty, $uom, $empty, $qoh, $checked, $image);

    if ($stmt->execute()) {
        $newId = $connect->insert_id;
        echo json_encode([
            'success' => 'Product created.',
            'product' => ['id' => $newId, 'barcode' => $barcode, 'name' => $name, 'uom' => $uom, 'image' => $image, 'qoh' => 0]
        ]);
    } else {
        echo json_encode(['error' => 'Failed to create product: ' . $connect->error]);
    }
    $stmt->close();

// ==================== PO LIST ====================
} elseif ($action === 'list_po') {
    $status = trim($_POST['status'] ?? '');
    $where = "1=1";
    $params = [];
    $types = "";

    if ($status !== '') {
        $where .= " AND po.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $sql = "SELECT po.*, s.name AS supplier_name FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE $where ORDER BY po.id DESC LIMIT 100";
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

// ==================== CREATE PO ====================
} elseif ($action === 'create') {
    $supplierId = intval($_POST['supplier_id'] ?? 0);
    $orderDate = trim($_POST['order_date'] ?? '');
    $expectedDate = trim($_POST['expected_date'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);
    $createdBy = $_SESSION['user_name'] ?? 'Staff';

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

    $itemStmt = $connect->prepare("INSERT INTO `purchase_order_item` (`po_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,0,?)");
    $prodNameStmt = $connect->prepare("UPDATE `PRODUCTS` SET `name` = ? WHERE `barcode` = ?");
    foreach ($items as $item) {
        $barcode = trim($item['barcode'] ?? '');
        $desc = trim($item['product_desc'] ?? '');
        $qtyOrdered = floatval($item['qty_ordered'] ?? 0);
        $uom = trim($item['uom'] ?? '');
        $itemStmt->bind_param("issds", $newPoId, $barcode, $desc, $qtyOrdered, $uom);
        $itemStmt->execute();
        // Update product name in PRODUCTS table
        if ($barcode !== '' && $desc !== '') {
            $prodNameStmt->bind_param("ss", $desc, $barcode);
            $prodNameStmt->execute();
        }
    }
    $itemStmt->close();
    $prodNameStmt->close();

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

    $connect->query("DELETE FROM `purchase_order_item` WHERE `po_id` = $id");

    $itemStmt = $connect->prepare("INSERT INTO `purchase_order_item` (`po_id`,`barcode`,`product_desc`,`qty_ordered`,`unit_cost`,`uom`) VALUES (?,?,?,?,0,?)");
    $prodNameStmt = $connect->prepare("UPDATE `PRODUCTS` SET `name` = ? WHERE `barcode` = ?");
    foreach ($items as $item) {
        $barcode = trim($item['barcode'] ?? '');
        $desc = trim($item['product_desc'] ?? '');
        $qtyOrdered = floatval($item['qty_ordered'] ?? 0);
        $uom = trim($item['uom'] ?? '');
        $itemStmt->bind_param("issds", $id, $barcode, $desc, $qtyOrdered, $uom);
        $itemStmt->execute();
        // Update product name in PRODUCTS table
        if ($barcode !== '' && $desc !== '') {
            $prodNameStmt->bind_param("ss", $desc, $barcode);
            $prodNameStmt->execute();
        }
    }
    $itemStmt->close();
    $prodNameStmt->close();

    echo json_encode(['success' => 'PO updated.', 'po_id' => $id]);

// ==================== GET PO DETAIL ====================
} elseif ($action === 'get_po') {
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

    $itemResult = $connect->query("SELECT poi.*, p.img1 AS product_image FROM `purchase_order_item` poi LEFT JOIN `PRODUCTS` p ON poi.barcode = p.barcode WHERE poi.po_id = $id ORDER BY poi.id ASC");
    $items = [];
    if ($itemResult) {
        while ($r = $itemResult->fetch_assoc()) {
            $items[] = $r;
        }
    }

    echo json_encode(['po' => $po, 'items' => $items]);

// ==================== APPROVE PO ====================
} elseif ($action === 'approve') {
    $id = intval($_POST['id'] ?? 0);
    $approvedBy = $_SESSION['user_name'] ?? 'Staff';

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

// ==================== SUPPLIERS LIST (for dropdown) ====================
} elseif ($action === 'list_suppliers') {
    $result = $connect->query("SELECT `id`, `code`, `name` FROM `supplier` WHERE `status` = 'ACTIVE' ORDER BY `name` ASC");
    $list = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $list[] = $r;
        }
    }
    echo json_encode(['suppliers' => $list]);

// ==================== UOM LIST ====================
} elseif ($action === 'uom_list') {
    $rows = [];
    $result = $connect->query("SELECT `id`, `name`, `status` FROM `product_uom` ORDER BY `name` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

// ==================== UOM CREATE ====================
} elseif ($action === 'uom_create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { echo json_encode(['error' => 'Name is required.']); exit; }
    $stmt = $connect->prepare("INSERT INTO `product_uom` (`name`) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'UOM created.', 'id' => $stmt->insert_id]);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'UOM already exists.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

// ==================== UOM CONVERSION LOOKUP ====================
} elseif ($action === 'uom_conversion_lookup') {
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

    $barcode = trim($_POST['barcode'] ?? '');
    $fromUom = trim($_POST['from_uom'] ?? '');
    if ($barcode === '' || $fromUom === '') {
        echo json_encode(['found' => false]);
        exit;
    }

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

// ==================== CATEGORY LIST ====================
} elseif ($action === 'cat_list') {
    $rows = [];
    $result = $connect->query("SELECT `id`, `ccode`, `cat_name` AS `name`, COALESCE(`status`, 'ACTIVE') AS `status` FROM `cat_group` ORDER BY `sort_no` ASC, `cat_name` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

// ==================== SUBCATEGORY LIST ====================
} elseif ($action === 'subcat_list') {
    $catGroupId = intval($_POST['category_id'] ?? 0);
    $rows = [];
    if ($catGroupId > 0) {
        $cg = $connect->prepare("SELECT `ccode` FROM `cat_group` WHERE `id`=?");
        $cg->bind_param("i", $catGroupId);
        $cg->execute();
        $cgRow = $cg->get_result()->fetch_assoc();
        $cg->close();
        if ($cgRow) {
            $stmt = $connect->prepare("SELECT `id`, `cat_code` AS `category_id`, `sub_cat` AS `name`, `sub_code`, 'ACTIVE' AS `status` FROM `category` WHERE `cat_code`=? ORDER BY `sort_no` ASC, `sub_cat` ASC");
            $stmt->bind_param("s", $cgRow['ccode']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($r = $result->fetch_assoc()) {
                $rows[] = $r;
            }
            $stmt->close();
        }
    }
    echo json_encode($rows);

// ==================== RACK LIST ====================
} elseif ($action === 'rack_list') {
    $rows = [];
    $result = $connect->query("SELECT `id`, `code`, `description`, `status` FROM `rack` WHERE `status`='ACTIVE' ORDER BY `code` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

// ==================== CREATE PRODUCT (full) ====================
} elseif ($action === 'create_product') {
    $barcode = trim($_POST['barcode'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cat = trim($_POST['cat'] ?? '');
    $sub_cat = trim($_POST['sub_cat'] ?? '');
    $cat_code = trim($_POST['cat_code'] ?? '');
    $sub_code = trim($_POST['sub_code'] ?? '');
    $uom = trim($_POST['uom'] ?? '');
    $rack = trim($_POST['rack'] ?? '');
    $rack_remark = trim($_POST['rack_remark'] ?? '');
    $qoh = floatval($_POST['qoh'] ?? 0);
    $checked = trim($_POST['checked'] ?? 'Y');

    if ($barcode === '' || $name === '') {
        echo json_encode(['error' => 'Barcode and name are required.']);
        exit;
    }

    $chk = $connect->prepare("SELECT `id` FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
    $chk->bind_param("s", $barcode);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Barcode already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    // Handle image upload
    $image = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $uploadDir = __DIR__ . '/../img/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mimeType, $allowedTypes) && $file['size'] <= 10 * 1024 * 1024) {
            $fileName = 'prod_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
            $destPath = $uploadDir . $fileName;
            switch ($mimeType) {
                case 'image/jpeg': $img = @imagecreatefromjpeg($file['tmp_name']); break;
                case 'image/png':  $img = @imagecreatefrompng($file['tmp_name']); break;
                case 'image/gif':  $img = @imagecreatefromgif($file['tmp_name']); break;
                case 'image/webp': $img = @imagecreatefromwebp($file['tmp_name']); break;
                default: $img = false;
            }
            if ($img) {
                $maxDim = 800;
                $origW = imagesx($img);
                $origH = imagesy($img);
                if ($origW > $maxDim || $origH > $maxDim) {
                    if ($origW >= $origH) { $newW = $maxDim; $newH = intval($origH * $maxDim / $origW); }
                    else { $newH = $maxDim; $newW = intval($origW * $maxDim / $origH); }
                    $resized = imagecreatetruecolor($newW, $newH);
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                    imagedestroy($img);
                    $img = $resized;
                }
                imagejpeg($img, $destPath, 75);
                imagedestroy($img);
                $image = $fileName;
            }
        }
    }

    // Ensure rack_updated_at column exists
    $chkCol = $connect->query("SHOW COLUMNS FROM `PRODUCTS` LIKE 'rack_updated_at'");
    if ($chkCol && $chkCol->num_rows === 0) {
        $connect->query("ALTER TABLE `PRODUCTS` ADD COLUMN `rack_updated_at` DATETIME DEFAULT NULL AFTER `rack_remark`");
    }

    $rackUpdatedAt = ($rack !== '') ? date('Y-m-d H:i:s') : null;
    $stmt = $connect->prepare("INSERT INTO `PRODUCTS` (`barcode`,`code`,`name`,`description`,`cat`,`sub_cat`,`cat_code`,`sub_code`,`uom`,`rack`,`rack_remark`,`rack_updated_at`,`qoh`,`checked`,`img1`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssssssdss", $barcode, $code, $name, $description, $cat, $sub_cat, $cat_code, $sub_code, $uom, $rack, $rack_remark, $rackUpdatedAt, $qoh, $checked, $image);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product created successfully.', 'product' => ['barcode' => $barcode, 'name' => $name, 'uom' => $uom]]);
    } else {
        echo json_encode(['error' => 'Failed to create product: ' . $connect->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
