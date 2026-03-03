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
$imgDir = '../category_img/';

// ===================== LIST =====================

if ($action === 'list') {
    $rows = [];
    $result = $connect->query("
        SELECT cg.*, COUNT(c.id) AS sub_count,
               CASE WHEN cg.cat_img = '' OR cg.cat_img IS NULL THEN 'INACTIVE_IMG' ELSE 'HAS_IMG' END AS img_status
        FROM `cat_group` cg
        LEFT JOIN `category` c ON c.cat_code = cg.ccode
        GROUP BY cg.id
        ORDER BY cg.sort_no ASC, cg.cat_name ASC
    ");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            // cat_group doesn't have a status column by default, treat all as ACTIVE unless we add one
            if (!isset($r['status'])) $r['status'] = 'ACTIVE';
            $rows[] = $r;
        }
    }
    echo json_encode($rows);

// ===================== GET =====================

} elseif ($action === 'get') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $connect->prepare("SELECT * FROM `cat_group` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!isset($row['status'])) $row['status'] = 'ACTIVE';
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Category group not found.']);
    }
    $stmt->close();

// ===================== CREATE =====================

} elseif ($action === 'create') {
    $cat_name = trim($_POST['cat_name'] ?? '');
    $ccode = trim($_POST['ccode'] ?? '');
    $sort_no = intval($_POST['sort_no'] ?? 0);
    $main_page = trim($_POST['main_page'] ?? '');

    if ($cat_name === '' || $ccode === '') {
        echo json_encode(['error' => 'Name and code are required.']);
        exit;
    }

    // Check duplicate ccode
    $chk = $connect->prepare("SELECT `id` FROM `cat_group` WHERE `ccode` = ? LIMIT 1");
    $chk->bind_param("s", $ccode);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Category code already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    // Handle image upload
    $cat_img = '';
    if (isset($_FILES['cat_img']) && $_FILES['cat_img']['error'] === UPLOAD_ERR_OK) {
        $cat_img = handleImageUpload($_FILES['cat_img'], $imgDir);
        if ($cat_img === false) {
            echo json_encode(['error' => 'Failed to upload image.']);
            exit;
        }
    }

    $stmt = $connect->prepare("INSERT INTO `cat_group` (`ccode`,`cat_name`,`cat_img`,`main_page`,`sort_no`) VALUES (?,?,?,?,?)");
    $stmt->bind_param("ssssi", $ccode, $cat_name, $cat_img, $main_page, $sort_no);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category group created.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== UPDATE =====================

} elseif ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $cat_name = trim($_POST['cat_name'] ?? '');
    $ccode = trim($_POST['ccode'] ?? '');
    $sort_no = intval($_POST['sort_no'] ?? 0);
    $main_page = trim($_POST['main_page'] ?? '');

    if ($id <= 0 || $cat_name === '' || $ccode === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    // Check duplicate ccode (exclude self)
    $chk = $connect->prepare("SELECT `id` FROM `cat_group` WHERE `ccode` = ? AND `id` != ? LIMIT 1");
    $chk->bind_param("si", $ccode, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Category code already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    // Handle image upload
    if (isset($_FILES['cat_img']) && $_FILES['cat_img']['error'] === UPLOAD_ERR_OK) {
        $cat_img = handleImageUpload($_FILES['cat_img'], $imgDir);
        if ($cat_img === false) {
            echo json_encode(['error' => 'Failed to upload image.']);
            exit;
        }
        $stmt = $connect->prepare("UPDATE `cat_group` SET `ccode`=?,`cat_name`=?,`cat_img`=?,`main_page`=?,`sort_no`=? WHERE `id`=?");
        $stmt->bind_param("ssssii", $ccode, $cat_name, $cat_img, $main_page, $sort_no, $id);
    } else {
        // Update without changing image
        $stmt = $connect->prepare("UPDATE `cat_group` SET `ccode`=?,`cat_name`=?,`main_page`=?,`sort_no`=? WHERE `id`=?");
        $stmt->bind_param("sssii", $ccode, $cat_name, $main_page, $sort_no, $id);
    }

    if ($stmt->execute()) {
        // Also update cat_name in category table where cat_code matches (keep text in sync)
        $upd = $connect->prepare("UPDATE `category` SET `cat_name`=? WHERE `cat_code`=?");
        $upd->bind_param("ss", $cat_name, $ccode);
        $upd->execute();
        $upd->close();

        echo json_encode(['success' => 'Category group updated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

// ===================== DEACTIVATE (soft-delete via main_page flag) =====================
// Since cat_group doesn't have a status column, we'll add one if needed

} elseif ($action === 'deactivate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }

    // Ensure status column exists
    ensureStatusColumn($connect);

    $stmt = $connect->prepare("UPDATE `cat_group` SET `status`='INACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category group deactivated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'activate') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }

    ensureStatusColumn($connect);

    $stmt = $connect->prepare("UPDATE `cat_group` SET `status`='ACTIVE' WHERE `id`=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Category group activated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}

// ===================== HELPERS =====================

function handleImageUpload($file, $dir) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return false;

    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = date('YmdHis') . '.' . strtolower($ext);

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return $filename;
    }
    return false;
}

function ensureStatusColumn($connect) {
    // Add status column to cat_group if it doesn't exist
    $result = $connect->query("SHOW COLUMNS FROM `cat_group` LIKE 'status'");
    if ($result && $result->num_rows === 0) {
        $connect->query("ALTER TABLE `cat_group` ADD COLUMN `status` ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE'");
    }
}
?>
