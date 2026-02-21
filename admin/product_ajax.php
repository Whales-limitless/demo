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

if ($action === 'get') {
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
    $cost = floatval($_POST['cost'] ?? 0);
    $oriprice = floatval($_POST['oriprice'] ?? 0);
    $disprice = floatval($_POST['disprice'] ?? 0);
    $uom = trim($_POST['uom'] ?? '');
    $rack = trim($_POST['rack'] ?? '');
    $min_qty = intval($_POST['min_qty'] ?? 0);
    $max_qty = intval($_POST['max_qty'] ?? 0);
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

    $stmt = $connect->prepare("INSERT INTO `PRODUCTS` (`barcode`,`code`,`name`,`description`,`cat`,`sub_cat`,`cost`,`oriprice`,`disprice`,`uom`,`rack`,`min_qty`,`max_qty`,`qoh`,`checked`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssdddssiids", $barcode, $code, $name, $description, $cat, $sub_cat, $cost, $oriprice, $disprice, $uom, $rack, $min_qty, $max_qty, $qoh, $checked);

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
    $cost = floatval($_POST['cost'] ?? 0);
    $oriprice = floatval($_POST['oriprice'] ?? 0);
    $disprice = floatval($_POST['disprice'] ?? 0);
    $uom = trim($_POST['uom'] ?? '');
    $rack = trim($_POST['rack'] ?? '');
    $min_qty = intval($_POST['min_qty'] ?? 0);
    $max_qty = intval($_POST['max_qty'] ?? 0);
    $qoh = floatval($_POST['qoh'] ?? 0);
    $checked = trim($_POST['checked'] ?? 'Y');

    if ($id <= 0 || $name === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `barcode`=?,`code`=?,`name`=?,`description`=?,`cat`=?,`sub_cat`=?,`cost`=?,`oriprice`=?,`disprice`=?,`uom`=?,`rack`=?,`min_qty`=?,`max_qty`=?,`qoh`=?,`checked`=? WHERE `id`=?");
    $stmt->bind_param("ssssssdddssiidsi", $barcode, $code, $name, $description, $cat, $sub_cat, $cost, $oriprice, $disprice, $uom, $rack, $min_qty, $max_qty, $qoh, $checked, $id);

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
