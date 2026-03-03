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
        $where .= " AND (LOWER(`barcode`) LIKE ? OR LOWER(`code`) LIKE ? OR LOWER(`name`) LIKE ? OR LOWER(`cat`) LIKE ? OR LOWER(`sub_cat`) LIKE ? OR LOWER(`rack`) LIKE ?)";
        $like = '%' . strtolower($search) . '%';
        $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        $types .= "ssssss";
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
    $sql = "SELECT `id`, `barcode`, `code`, `name`, `cat`, `sub_cat`, COALESCE(`qoh`, 0) AS `qoh`, `uom`, `rack`, `checked`
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
    $stmt = $connect->prepare("SELECT * FROM `PRODUCTS` WHERE `id` = ? LIMIT 1");
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

    $stmt = $connect->prepare("INSERT INTO `PRODUCTS` (`barcode`,`code`,`name`,`description`,`cat`,`sub_cat`,`cat_code`,`sub_code`,`uom`,`rack`,`qoh`,`checked`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssssds", $barcode, $code, $name, $description, $cat, $sub_cat, $cat_code, $sub_code, $uom, $rack, $qoh, $checked);

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
    $qoh = floatval($_POST['qoh'] ?? 0);
    $checked = trim($_POST['checked'] ?? 'Y');

    if ($id <= 0 || $name === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `barcode`=?,`code`=?,`name`=?,`description`=?,`cat`=?,`sub_cat`=?,`cat_code`=?,`sub_code`=?,`uom`=?,`rack`=?,`qoh`=?,`checked`=? WHERE `id`=?");
    $stmt->bind_param("ssssssssssdsi", $barcode, $code, $name, $description, $cat, $sub_cat, $cat_code, $sub_code, $uom, $rack, $qoh, $checked, $id);

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

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
