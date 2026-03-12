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
               COALESCE(p.`qoh`, 0) AS qoh, p.`cat_code`, p.`rack`,
               c.`cat_name` AS category_name
        FROM `PRODUCTS` p
        LEFT JOIN `category` c ON p.`cat_code` = c.`cat_code`
        WHERE (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?
               OR p.`barcode` LIKE ? OR p.`barcode` LIKE ? OR p.`barcode` LIKE ?)
          AND (p.`checked` != 'N' OR p.`checked` IS NULL)
        ORDER BY p.`name` ASC
        LIMIT 20
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

} elseif ($action === 'search_products_paginated') {
    $search = trim($_POST['q'] ?? '');
    $page = max(1, intval($_POST['page'] ?? 1));
    $perPage = 20;

    $where = "(p.`checked` != 'N' OR p.`checked` IS NULL)";
    $params = [];
    $types = "";

    if ($search !== '') {
        $normalizedSearch = $search;
        $normalizedSearch = str_replace(["\u{201C}", "\u{201D}", "\u{2033}", "\u{FF02}"], '"', $normalizedSearch);
        $normalizedSearch = str_replace(["\u{2018}", "\u{2019}", "\u{2032}", "\u{FF07}"], "'", $normalizedSearch);
        $altSearch = str_replace('"', "''", $normalizedSearch);
        $altSearch2 = str_replace("''", '"', $normalizedSearch);
        $normalizedLike = '%' . $normalizedSearch . '%';
        $altLike = '%' . $altSearch . '%';
        $altLike2 = '%' . $altSearch2 . '%';

        $where .= " AND (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?
                   OR p.`barcode` LIKE ? OR p.`barcode` LIKE ? OR p.`barcode` LIKE ?)";
        $params = [$normalizedLike, $altLike, $altLike2, $normalizedLike, $altLike, $altLike2];
        $types = "ssssss";
    }

    // Count total
    $countSql = "SELECT COUNT(DISTINCT p.`id`) AS cnt FROM `PRODUCTS` p WHERE $where";
    $countStmt = $connect->prepare($countSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['cnt'];
    $countStmt->close();

    $pages = max(1, ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT p.`id`, p.`barcode`, p.`name`, p.`img1` AS image, p.`uom`,
                   COALESCE(p.`qoh`, 0) AS qoh, p.`cat_code`, p.`rack`,
                   c.`cat_name` AS category_name
            FROM `PRODUCTS` p
            LEFT JOIN `category` c ON p.`cat_code` = c.`cat_code`
            WHERE $where
            ORDER BY p.`name` ASC
            LIMIT ?, ?";
    $stmt = $connect->prepare($sql);
    $fetchTypes = $types . "ii";
    $fetchParams = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($fetchTypes, ...$fetchParams);
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

    echo json_encode([
        'products' => $products,
        'total' => (int)$total,
        'page' => (int)$page,
        'pages' => (int)$pages,
        'per_page' => (int)$perPage
    ]);

} elseif ($action === 'quick_create_product') {
    $name = trim($_POST['name'] ?? '');
    $uom = trim($_POST['uom'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');

    if ($name === '') {
        echo json_encode(['error' => 'Product name is required.']);
        exit;
    }

    // Auto-generate barcode if empty
    if ($barcode === '') {
        $barcode = 'NEW-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
    }

    // Check duplicate barcode
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

            // Create image resource
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
            'product' => [
                'id' => $newId,
                'barcode' => $barcode,
                'name' => $name,
                'uom' => $uom,
                'image' => $image,
                'qoh' => 0
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Failed to create product: ' . $connect->error]);
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
