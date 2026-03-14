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

// ===================== IMAGE UPLOAD HELPER =====================

function handleProductImage($existingImage = '', $removeImage = false) {
    $uploadDir = __DIR__ . '/../img/';

    // If removing image, delete old file and return empty
    if ($removeImage && $existingImage !== '') {
        $oldPath = $uploadDir . $existingImage;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
        return '';
    }

    // If no new file uploaded, keep existing
    if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
        return $removeImage ? '' : $existingImage;
    }

    $file = $_FILES['product_image'];

    // Validate size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['error' => 'Image must be smaller than 10MB.'];
    }

    // Validate type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['error' => 'Only JPG, PNG, GIF, and WebP images are allowed.'];
    }

    // Generate unique filename
    $fileName = 'prod_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.jpg';
    $destPath = $uploadDir . $fileName;

    // Compress and save
    $result = compressProductImage($file['tmp_name'], $destPath, $mimeType);
    if ($result === false) {
        return ['error' => 'Failed to process image.'];
    }

    // Delete old image if replacing
    if ($existingImage !== '') {
        $oldPath = $uploadDir . $existingImage;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    return $fileName;
}

function compressProductImage($source, $destination, $mimeType) {
    // Create image resource based on mime type
    switch ($mimeType) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($source);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    // Resize if too large (max 800px on longest side - optimized for product images)
    $maxDim = 800;
    $origW = imagesx($image);
    $origH = imagesy($image);

    if ($origW > $maxDim || $origH > $maxDim) {
        if ($origW >= $origH) {
            $newW = $maxDim;
            $newH = intval($origH * $maxDim / $origW);
        } else {
            $newH = $maxDim;
            $newW = intval($origW * $maxDim / $origH);
        }
        $resized = imagecreatetruecolor($newW, $newH);
        // Preserve transparency for PNG/GIF
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($image);
        $image = $resized;
    }

    // Save as JPEG with 75% quality (good balance of quality and file size)
    $result = imagejpeg($image, $destination, 75);
    imagedestroy($image);

    return $result;
}

// ===================== ENSURE rack_remark COLUMN =====================
$chkCol = $connect->query("SHOW COLUMNS FROM `PRODUCTS` LIKE 'rack_remark'");
if ($chkCol && $chkCol->num_rows === 0) {
    $connect->query("ALTER TABLE `PRODUCTS` ADD COLUMN `rack_remark` VARCHAR(255) DEFAULT NULL AFTER `rack`");
}

// ===================== PRODUCT ACTIONS =====================

if ($action === 'list') {
    $page = max(1, intval($_POST['page'] ?? 1));
    $perPage = max(10, min(100, intval($_POST['per_page'] ?? 50)));
    $search = trim($_POST['search'] ?? '');
    $catFilter = trim($_POST['cat'] ?? '');
    $statusFilter = trim($_POST['status'] ?? '');

    $where = "1=1";
    $params = [];
    $types = "";

    if ($search !== '') {
        // Normalize curly/smart quotes and prime symbols to ASCII equivalents
        $normalizedSearch = $search;
        $normalizedSearch = str_replace(["\u{201C}", "\u{201D}", "\u{2033}", "\u{FF02}"], '"', $normalizedSearch);
        $normalizedSearch = str_replace(["\u{2018}", "\u{2019}", "\u{2032}", "\u{FF07}"], "'", $normalizedSearch);

        // Build alternate search terms: swap " ↔ '' so both forms match
        $altSearch = str_replace('"', "''", $normalizedSearch);
        $altSearch2 = str_replace("''", '"', $normalizedSearch);
        $like = '%' . strtolower($normalizedSearch) . '%';
        $altLike = '%' . strtolower($altSearch) . '%';
        $altLike2 = '%' . strtolower($altSearch2) . '%';

        $where .= " AND ((LOWER(`barcode`) LIKE ? OR LOWER(`code`) LIKE ? OR LOWER(`name`) LIKE ? OR LOWER(`cat`) LIKE ? OR LOWER(`sub_cat`) LIKE ? OR LOWER(`rack`) LIKE ?)";
        $where .= " OR (LOWER(`barcode`) LIKE ? OR LOWER(`code`) LIKE ? OR LOWER(`name`) LIKE ? OR LOWER(`cat`) LIKE ? OR LOWER(`sub_cat`) LIKE ? OR LOWER(`rack`) LIKE ?)";
        $where .= " OR (LOWER(`barcode`) LIKE ? OR LOWER(`code`) LIKE ? OR LOWER(`name`) LIKE ? OR LOWER(`cat`) LIKE ? OR LOWER(`sub_cat`) LIKE ? OR LOWER(`rack`) LIKE ?))";
        $params = array_merge($params,
            [$like, $like, $like, $like, $like, $like],
            [$altLike, $altLike, $altLike, $altLike, $altLike, $altLike],
            [$altLike2, $altLike2, $altLike2, $altLike2, $altLike2, $altLike2]
        );
        $types .= "ssssssssssssssssss";
    }
    if ($catFilter !== '') {
        $where .= " AND `cat` = ?";
        $params[] = $catFilter;
        $types .= "s";
    }
    if ($statusFilter === 'active') {
        $where .= " AND (`checked` = 'Y' OR `checked` = '' OR `checked` IS NULL)";
    } elseif ($statusFilter === 'inactive') {
        $where .= " AND `checked` = 'N'";
    }

    // Count total
    $countSql = "SELECT COUNT(*) AS cnt FROM `PRODUCTS` WHERE $where";
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

    // Fetch page
    $sql = "SELECT `id`, `barcode`, `code`, `name`, `cat`, `sub_cat`, COALESCE(`qoh`, 0) AS `qoh`, `uom`, `rack`, `rack_remark`, `checked`, `img1` AS `image`
            FROM `PRODUCTS` WHERE $where ORDER BY `checked` DESC, `name` ASC LIMIT ?, ?";
    $stmt = $connect->prepare($sql);
    $fetchTypes = $types . "ii";
    $fetchParams = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($fetchTypes, ...$fetchParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($r = $result->fetch_assoc()) {
        $products[] = $r;
    }
    $stmt->close();

    echo json_encode([
        'products' => $products,
        'total' => (int)$total,
        'page' => (int)$page,
        'pages' => (int)$pages,
        'per_page' => (int)$perPage
    ]);

} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT *, `img1` AS `image` FROM `PRODUCTS` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Product not found.']);
    }
    $stmt->close();

} elseif ($action === 'create') {
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
    $removeImg = (trim($_POST['remove_image'] ?? '0') === '1');
    $imageResult = handleProductImage('', $removeImg);
    if (is_array($imageResult) && isset($imageResult['error'])) {
        echo json_encode($imageResult);
        exit;
    }
    $image = is_string($imageResult) ? $imageResult : '';

    $stmt = $connect->prepare("INSERT INTO `PRODUCTS` (`barcode`,`code`,`name`,`description`,`cat`,`sub_cat`,`cat_code`,`sub_code`,`uom`,`rack`,`rack_remark`,`qoh`,`checked`,`img1`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssssssdss", $barcode, $code, $name, $description, $cat, $sub_cat, $cat_code, $sub_code, $uom, $rack, $rack_remark, $qoh, $checked, $image);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product created successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to create product: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
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

    if ($id <= 0 || $name === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    // Handle image upload
    $existingImage = trim($_POST['existing_image'] ?? '');
    $removeImg = (trim($_POST['remove_image'] ?? '0') === '1');
    $imageResult = handleProductImage($existingImage, $removeImg);
    if (is_array($imageResult) && isset($imageResult['error'])) {
        echo json_encode($imageResult);
        exit;
    }
    $image = is_string($imageResult) ? $imageResult : $existingImage;

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `barcode`=?,`code`=?,`name`=?,`description`=?,`cat`=?,`sub_cat`=?,`cat_code`=?,`sub_code`=?,`uom`=?,`rack`=?,`rack_remark`=?,`qoh`=?,`checked`=?,`img1`=? WHERE `id`=?");
    $stmt->bind_param("sssssssssssdssi", $barcode, $code, $name, $description, $cat, $sub_cat, $cat_code, $sub_code, $uom, $rack, $rack_remark, $qoh, $checked, $image, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product updated successfully.']);
    } else {
        echo json_encode(['error' => 'Failed to update product: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid product ID.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `checked` = 'N' WHERE `id` = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== CATEGORY ACTIONS (uses cat_group table) =====================

} elseif ($action === 'cat_list') {
    // Returns cat_group entries as the category list for product form
    $rows = [];
    $result = $connect->query("SELECT `id`, `ccode`, `cat_name` AS `name`, COALESCE(`status`, 'ACTIVE') AS `status` FROM `cat_group` ORDER BY `sort_no` ASC, `cat_name` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

} elseif ($action === 'cat_create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { echo json_encode(['error' => 'Name is required.']); exit; }
    // Auto-assign next ccode
    $maxRes = $connect->query("SELECT MAX(CAST(`ccode` AS UNSIGNED)) AS mx FROM `cat_group`");
    $maxCode = 1;
    if ($maxRes && $row = $maxRes->fetch_assoc()) {
        $maxCode = intval($row['mx']) + 1;
    }
    $ccode = (string)$maxCode;
    $stmt = $connect->prepare("INSERT INTO `cat_group` (`ccode`,`cat_name`,`cat_img`,`main_page`,`sort_no`) VALUES (?,?,'','',0)");
    $stmt->bind_param("ss", $ccode, $name);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category created.', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'cat_update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }
    // Get current ccode to update category table too
    $cur = $connect->prepare("SELECT `ccode` FROM `cat_group` WHERE `id`=?");
    $cur->bind_param("i", $id);
    $cur->execute();
    $curRow = $cur->get_result()->fetch_assoc();
    $cur->close();
    $ccode = $curRow ? $curRow['ccode'] : '';

    $stmt = $connect->prepare("UPDATE `cat_group` SET `cat_name`=? WHERE `id`=?");
    $stmt->bind_param("si", $name, $id);
    if ($stmt->execute()) {
        // Sync cat_name in category table
        if ($ccode !== '') {
            $upd = $connect->prepare("UPDATE `category` SET `cat_name`=? WHERE `cat_code`=?");
            $upd->bind_param("ss", $name, $ccode);
            $upd->execute();
            $upd->close();
        }
        echo json_encode(['success' => 'Category updated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'cat_delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    // Ensure status column exists
    $chkCol = $connect->query("SHOW COLUMNS FROM `cat_group` LIKE 'status'");
    if ($chkCol && $chkCol->num_rows === 0) {
        $connect->query("ALTER TABLE `cat_group` ADD COLUMN `status` ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE'");
    }
    $stmt = $connect->prepare("UPDATE `cat_group` SET `status`='INACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'cat_activate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $chkCol = $connect->query("SHOW COLUMNS FROM `cat_group` LIKE 'status'");
    if ($chkCol && $chkCol->num_rows === 0) {
        $connect->query("ALTER TABLE `cat_group` ADD COLUMN `status` ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE'");
    }
    $stmt = $connect->prepare("UPDATE `cat_group` SET `status`='ACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category activated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== SUB CATEGORY ACTIONS (uses category table) =====================

} elseif ($action === 'subcat_list') {
    $catGroupId = intval($_POST['category_id'] ?? 0);
    $rows = [];
    if ($catGroupId > 0) {
        // Get ccode from cat_group id
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
    } else {
        // List all sub categories with parent name
        $result = $connect->query("SELECT c.`id`, c.`cat_code` AS `category_id`, c.`sub_cat` AS `name`, c.`sub_code`, c.`cat_name` AS `cat_name`, 'ACTIVE' AS `status` FROM `category` c ORDER BY c.`cat_name` ASC, c.`sort_no` ASC, c.`sub_cat` ASC");
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $rows[] = $r;
            }
        }
    }
    echo json_encode($rows);

} elseif ($action === 'subcat_create') {
    $catGroupId = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($catGroupId <= 0 || $name === '') { echo json_encode(['error' => 'Category and name are required.']); exit; }

    // Get cat_group info
    $cg = $connect->prepare("SELECT `ccode`, `cat_name` FROM `cat_group` WHERE `id`=?");
    $cg->bind_param("i", $catGroupId);
    $cg->execute();
    $cgRow = $cg->get_result()->fetch_assoc();
    $cg->close();
    if (!$cgRow) { echo json_encode(['error' => 'Category group not found.']); exit; }

    // Get next sub_code for this cat_code
    $maxSub = $connect->prepare("SELECT MAX(CAST(`sub_code` AS UNSIGNED)) AS mx FROM `category` WHERE `cat_code`=?");
    $maxSub->bind_param("s", $cgRow['ccode']);
    $maxSub->execute();
    $maxRow = $maxSub->get_result()->fetch_assoc();
    $maxSub->close();
    $nextSub = ($maxRow && $maxRow['mx']) ? intval($maxRow['mx']) + 1 : 1;
    $sub_code = (string)$nextSub;
    $ccode = $cgRow['ccode'] . $sub_code;

    // Get next sort_no
    $maxSort = $connect->prepare("SELECT MAX(`sort_no`) AS mx FROM `category` WHERE `cat_code`=?");
    $maxSort->bind_param("s", $cgRow['ccode']);
    $maxSort->execute();
    $sortRow = $maxSort->get_result()->fetch_assoc();
    $maxSort->close();
    $nextSort = ($sortRow && $sortRow['mx']) ? intval($sortRow['mx']) + 1 : 1;

    $stmt = $connect->prepare("INSERT INTO `category` (`cat_code`,`sub_code`,`ccode`,`cat_name`,`sub_cat`,`sort_no`) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("sssssi", $cgRow['ccode'], $sub_code, $ccode, $cgRow['cat_name'], $name, $nextSort);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Sub category created.', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'subcat_update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }
    $stmt = $connect->prepare("UPDATE `category` SET `sub_cat`=? WHERE `id`=?");
    $stmt->bind_param("si", $name, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Sub category updated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'subcat_delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    // Soft delete not available on category table - we delete the row
    // But first check if products reference this sub_cat
    $chk = $connect->prepare("SELECT c.`cat_code`, c.`sub_code` FROM `category` c WHERE c.`id`=?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $catRow = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($catRow) {
        $pChk = $connect->prepare("SELECT COUNT(*) AS cnt FROM `PRODUCTS` WHERE `cat_code`=? AND `sub_code`=?");
        $pChk->bind_param("ss", $catRow['cat_code'], $catRow['sub_code']);
        $pChk->execute();
        $pCount = $pChk->get_result()->fetch_assoc()['cnt'];
        $pChk->close();
        if ($pCount > 0) {
            echo json_encode(['error' => 'Cannot delete: ' . $pCount . ' product(s) use this sub category.']);
            exit;
        }
    }
    $stmt = $connect->prepare("DELETE FROM `category` WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Sub category deleted.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'subcat_activate') {
    // category table has no status column, so this is a no-op kept for compatibility
    echo json_encode(['success' => 'Sub category is active.']);

// ===================== UOM ACTIONS =====================

} elseif ($action === 'uom_list') {
    $rows = [];
    $result = $connect->query("SELECT `id`, `name`, `status` FROM `product_uom` ORDER BY `name` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

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

} elseif ($action === 'uom_update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_uom` SET `name`=? WHERE `id`=?");
    $stmt->bind_param("si", $name, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'UOM updated.']);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'UOM name already exists.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'uom_delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_uom` SET `status`='INACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'UOM deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'uom_activate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_uom` SET `status`='ACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'UOM activated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== RACK LIST (from new rack table) =====================

} elseif ($action === 'rack_list') {
    $rows = [];
    $result = $connect->query("SELECT `id`, `code`, `description`, `status` FROM `rack` WHERE `status`='ACTIVE' ORDER BY `code` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

// ===================== BULK RACK UPDATE =====================

} elseif ($action === 'bulk_update_rack') {
    $itemsJson = $_POST['items'] ?? '[]';
    $items = json_decode($itemsJson, true);
    if (!is_array($items) || count($items) === 0) {
        echo json_encode(['error' => 'No items to update.']);
        exit;
    }
    $updated = 0;
    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `rack` = ? WHERE `id` = ?");
    foreach ($items as $item) {
        $id = intval($item['id'] ?? 0);
        $rack = trim($item['rack'] ?? '');
        if ($id <= 0) continue;
        $stmt->bind_param("si", $rack, $id);
        if ($stmt->execute()) $updated++;
    }
    $stmt->close();
    echo json_encode(['success' => $updated . ' product(s) rack updated.', 'updated' => $updated]);
    exit;

// ===================== LEGACY ENDPOINTS =====================

} elseif ($action === 'categories') {
    $cats = [];
    $result = $connect->query("SELECT DISTINCT `cat` FROM `PRODUCTS` WHERE `cat` != '' ORDER BY `cat` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $cats[] = $r['cat'];
        }
    }
    echo json_encode($cats);

} elseif ($action === 'subcategories') {
    $cat = trim($_POST['cat'] ?? '');
    $subs = [];
    $stmt = $connect->prepare("SELECT DISTINCT `sub_cat` FROM `PRODUCTS` WHERE `cat` = ? AND `sub_cat` != '' ORDER BY `sub_cat` ASC");
    $stmt->bind_param("s", $cat);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) {
        $subs[] = $r['sub_cat'];
    }
    $stmt->close();
    echo json_encode($subs);

} elseif ($action === 'racks') {
    $racks = [];
    $result = $connect->query("SELECT DISTINCT `rack` FROM `PRODUCTS` WHERE `rack` IS NOT NULL AND `rack` != '' ORDER BY `rack` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $racks[] = $r['rack'];
        }
    }
    echo json_encode($racks);

// ===================== UOM CONVERSION ACTIONS =====================

} elseif ($action === 'uom_conversion_ensure_table') {
    // Auto-create table if not exists
    $connect->query("CREATE TABLE IF NOT EXISTS `uom_conversion` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `barcode` VARCHAR(50) NOT NULL,
        `from_uom` VARCHAR(20) NOT NULL,
        `to_uom` VARCHAR(20) NOT NULL,
        `conversion_factor` DOUBLE(10,4) NOT NULL DEFAULT 1.0000,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_barcode_from_to` (`barcode`, `from_uom`, `to_uom`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo json_encode(['success' => true]);

} elseif ($action === 'uom_conversion_list') {
    $barcode = trim($_POST['barcode'] ?? '');
    if ($barcode === '') { echo json_encode([]); exit; }

    // Ensure table exists
    $connect->query("CREATE TABLE IF NOT EXISTS `uom_conversion` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `barcode` VARCHAR(50) NOT NULL,
        `from_uom` VARCHAR(20) NOT NULL,
        `to_uom` VARCHAR(20) NOT NULL,
        `conversion_factor` DOUBLE(10,4) NOT NULL DEFAULT 1.0000,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_barcode_from_to` (`barcode`, `from_uom`, `to_uom`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $connect->prepare("SELECT `id`, `barcode`, `from_uom`, `to_uom`, `conversion_factor` FROM `uom_conversion` WHERE `barcode` = ? ORDER BY `from_uom` ASC");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $r['conversion_factor'] = floatval($r['conversion_factor']);
        $rows[] = $r;
    }
    $stmt->close();
    echo json_encode($rows);

} elseif ($action === 'uom_conversion_create') {
    $barcode = trim($_POST['barcode'] ?? '');
    $fromUom = trim($_POST['from_uom'] ?? '');
    $toUom = trim($_POST['to_uom'] ?? '');
    $factor = floatval($_POST['conversion_factor'] ?? 0);

    if ($barcode === '' || $fromUom === '' || $toUom === '') {
        echo json_encode(['error' => 'Barcode, From UOM, and To UOM are required.']);
        exit;
    }
    if ($fromUom === $toUom) {
        echo json_encode(['error' => 'From UOM and To UOM must be different.']);
        exit;
    }
    if ($factor <= 0) {
        echo json_encode(['error' => 'Conversion factor must be greater than 0.']);
        exit;
    }

    $stmt = $connect->prepare("INSERT INTO `uom_conversion` (`barcode`, `from_uom`, `to_uom`, `conversion_factor`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $barcode, $fromUom, $toUom, $factor);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Conversion added.', 'id' => $stmt->insert_id]);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'This conversion already exists for this product.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'uom_conversion_update') {
    $id = intval($_POST['id'] ?? 0);
    $factor = floatval($_POST['conversion_factor'] ?? 0);
    if ($id <= 0 || $factor <= 0) {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }
    $stmt = $connect->prepare("UPDATE `uom_conversion` SET `conversion_factor` = ? WHERE `id` = ?");
    $stmt->bind_param("di", $factor, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Conversion updated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'uom_conversion_delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("DELETE FROM `uom_conversion` WHERE `id` = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Conversion deleted.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'uom_conversion_lookup') {
    // Lookup conversion for a specific barcode + from_uom → product base UOM
    $barcode = trim($_POST['barcode'] ?? '');
    $fromUom = trim($_POST['from_uom'] ?? '');
    if ($barcode === '' || $fromUom === '') {
        echo json_encode(['found' => false]);
        exit;
    }

    // Ensure table exists
    $connect->query("CREATE TABLE IF NOT EXISTS `uom_conversion` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `barcode` VARCHAR(50) NOT NULL,
        `from_uom` VARCHAR(20) NOT NULL,
        `to_uom` VARCHAR(20) NOT NULL,
        `conversion_factor` DOUBLE(10,4) NOT NULL DEFAULT 1.0000,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_barcode_from_to` (`barcode`, `from_uom`, `to_uom`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Get product base UOM
    $pStmt = $connect->prepare("SELECT `uom` FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
    $pStmt->bind_param("s", $barcode);
    $pStmt->execute();
    $pRow = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();
    $baseUom = $pRow ? trim($pRow['uom'] ?? '') : '';

    // If from_uom matches base UOM, no conversion needed
    if ($fromUom === $baseUom || $baseUom === '') {
        echo json_encode(['found' => false, 'base_uom' => $baseUom]);
        exit;
    }

    // Look for conversion from_uom → base_uom
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

} elseif ($action === 'uom_conversion_bulk_lookup') {
    // Bulk lookup conversions for multiple barcodes at once (used by GRN form)
    $barcodesJson = $_POST['barcodes'] ?? '[]';
    $barcodes = json_decode($barcodesJson, true);
    if (!is_array($barcodes) || empty($barcodes)) {
        echo json_encode(['conversions' => []]);
        exit;
    }

    // Ensure table exists
    $connect->query("CREATE TABLE IF NOT EXISTS `uom_conversion` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `barcode` VARCHAR(50) NOT NULL,
        `from_uom` VARCHAR(20) NOT NULL,
        `to_uom` VARCHAR(20) NOT NULL,
        `conversion_factor` DOUBLE(10,4) NOT NULL DEFAULT 1.0000,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_barcode_from_to` (`barcode`, `from_uom`, `to_uom`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $placeholders = implode(',', array_fill(0, count($barcodes), '?'));
    $types = str_repeat('s', count($barcodes));

    // Get all conversions for these barcodes
    $stmt = $connect->prepare("SELECT uc.`barcode`, uc.`from_uom`, uc.`to_uom`, uc.`conversion_factor`, p.`uom` AS `base_uom`
        FROM `uom_conversion` uc
        LEFT JOIN `PRODUCTS` p ON uc.`barcode` = p.`barcode`
        WHERE uc.`barcode` IN ($placeholders)");
    $stmt->bind_param($types, ...$barcodes);
    $stmt->execute();
    $result = $stmt->get_result();
    $conversions = [];
    while ($r = $result->fetch_assoc()) {
        $r['conversion_factor'] = floatval($r['conversion_factor']);
        $conversions[] = $r;
    }
    $stmt->close();

    echo json_encode(['conversions' => $conversions]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
