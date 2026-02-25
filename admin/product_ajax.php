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

    $stmt = $connect->prepare("INSERT INTO `PRODUCTS` (`barcode`,`code`,`name`,`description`,`cat`,`sub_cat`,`uom`,`rack`,`qoh`,`checked`) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssds", $barcode, $code, $name, $description, $cat, $sub_cat, $uom, $rack, $qoh, $checked);

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
    $uom = trim($_POST['uom'] ?? '');
    $rack = trim($_POST['rack'] ?? '');
    $qoh = floatval($_POST['qoh'] ?? 0);
    $checked = trim($_POST['checked'] ?? 'Y');

    if ($id <= 0 || $name === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `barcode`=?,`code`=?,`name`=?,`description`=?,`cat`=?,`sub_cat`=?,`uom`=?,`rack`=?,`qoh`=?,`checked`=? WHERE `id`=?");
    $stmt->bind_param("ssssssssdsi", $barcode, $code, $name, $description, $cat, $sub_cat, $uom, $rack, $qoh, $checked, $id);

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

// ===================== CATEGORY ACTIONS =====================

} elseif ($action === 'cat_list') {
    $rows = [];
    $result = $connect->query("SELECT `id`, `name`, `status` FROM `product_category` ORDER BY `name` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

} elseif ($action === 'cat_create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { echo json_encode(['error' => 'Name is required.']); exit; }
    $stmt = $connect->prepare("INSERT INTO `product_category` (`name`) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category created.', 'id' => $stmt->insert_id]);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'Category already exists.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'cat_update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_category` SET `name`=? WHERE `id`=?");
    $stmt->bind_param("si", $name, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category updated.']);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'Category name already exists.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'cat_delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_category` SET `status`='INACTIVE' WHERE `id`=?");
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
    $stmt = $connect->prepare("UPDATE `product_category` SET `status`='ACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category activated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== SUB CATEGORY ACTIONS =====================

} elseif ($action === 'subcat_list') {
    $catId = intval($_POST['category_id'] ?? 0);
    $rows = [];
    if ($catId > 0) {
        $stmt = $connect->prepare("SELECT `id`, `category_id`, `name`, `status` FROM `product_sub_category` WHERE `category_id`=? ORDER BY `name` ASC");
        $stmt->bind_param("i", $catId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
    } else {
        $result = $connect->query("SELECT s.`id`, s.`category_id`, s.`name`, s.`status`, c.`name` AS `cat_name` FROM `product_sub_category` s LEFT JOIN `product_category` c ON s.`category_id`=c.`id` ORDER BY c.`name`, s.`name` ASC");
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $rows[] = $r;
            }
        }
    }
    echo json_encode($rows);

} elseif ($action === 'subcat_create') {
    $catId = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($catId <= 0 || $name === '') { echo json_encode(['error' => 'Category and name are required.']); exit; }
    $stmt = $connect->prepare("INSERT INTO `product_sub_category` (`category_id`,`name`) VALUES (?,?)");
    $stmt->bind_param("is", $catId, $name);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Sub category created.', 'id' => $stmt->insert_id]);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'Sub category already exists under this category.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'subcat_update') {
    $id = intval($_POST['id'] ?? 0);
    $catId = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if ($id <= 0 || $catId <= 0 || $name === '') { echo json_encode(['error' => 'Invalid data.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_sub_category` SET `category_id`=?,`name`=? WHERE `id`=?");
    $stmt->bind_param("isi", $catId, $name, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Sub category updated.']);
    } else {
        if (strpos($connect->error, 'Duplicate') !== false) {
            echo json_encode(['error' => 'Sub category name already exists under this category.']);
        } else {
            echo json_encode(['error' => 'Failed: ' . $connect->error]);
        }
    }
    $stmt->close();

} elseif ($action === 'subcat_delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_sub_category` SET `status`='INACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Sub category deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'subcat_activate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    $stmt = $connect->prepare("UPDATE `product_sub_category` SET `status`='ACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Sub category activated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

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
