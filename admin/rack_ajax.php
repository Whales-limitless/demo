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

if ($action === 'list') {
    // List all distinct racks with product counts
    $racks = [];
    $result = $connect->query("
        SELECT COALESCE(NULLIF(`rack`, ''), 'Unassigned') AS rack_name,
               COUNT(*) AS product_count,
               SUM(COALESCE(`qoh`, 0)) AS total_qoh
        FROM `PRODUCTS`
        WHERE `checked` = 'Y'
        GROUP BY COALESCE(NULLIF(`rack`, ''), 'Unassigned')
        ORDER BY rack_name ASC
    ");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $racks[] = $r;
        }
    }
    echo json_encode($racks);

} elseif ($action === 'products') {
    // Get products for a specific rack
    $rack = trim($_POST['rack'] ?? '');
    if ($rack === 'Unassigned') {
        $result = $connect->query("SELECT `id`, `barcode`, `code`, `name`, `cat`, COALESCE(`qoh`, 0) AS qoh, `rack` FROM `PRODUCTS` WHERE `checked` = 'Y' AND (`rack` IS NULL OR `rack` = '') ORDER BY `name` ASC");
    } else {
        $stmt = $connect->prepare("SELECT `id`, `barcode`, `code`, `name`, `cat`, COALESCE(`qoh`, 0) AS qoh, `rack` FROM `PRODUCTS` WHERE `checked` = 'Y' AND `rack` = ? ORDER BY `name` ASC");
        $stmt->bind_param("s", $rack);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    $products = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $products[] = $r;
        }
    }
    if (isset($stmt)) $stmt->close();
    echo json_encode($products);

} elseif ($action === 'rename') {
    $oldRack = trim($_POST['old_rack'] ?? '');
    $newRack = trim($_POST['new_rack'] ?? '');

    if ($newRack === '') {
        echo json_encode(['error' => 'New rack name cannot be empty.']);
        exit;
    }

    if ($oldRack === 'Unassigned' || $oldRack === '') {
        echo json_encode(['error' => 'Cannot rename unassigned rack.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `rack` = ? WHERE `rack` = ?");
    $stmt->bind_param("ss", $newRack, $oldRack);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack renamed from "' . $oldRack . '" to "' . $newRack . '". ' . $stmt->affected_rows . ' product(s) updated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'assign') {
    $productId = intval($_POST['product_id'] ?? 0);
    $rack = trim($_POST['rack'] ?? '');

    if ($productId <= 0) {
        echo json_encode(['error' => 'Invalid product.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `rack` = ? WHERE `id` = ?");
    $stmt->bind_param("si", $rack, $productId);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product rack updated.']);
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'bulk_assign') {
    $productIds = json_decode($_POST['product_ids'] ?? '[]', true);
    $rack = trim($_POST['rack'] ?? '');

    if (empty($productIds)) {
        echo json_encode(['error' => 'No products selected.']);
        exit;
    }

    $updated = 0;
    $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `rack` = ? WHERE `id` = ?");
    foreach ($productIds as $pid) {
        $pid = intval($pid);
        if ($pid > 0) {
            $stmt->bind_param("si", $rack, $pid);
            $stmt->execute();
            $updated += $stmt->affected_rows;
        }
    }
    $stmt->close();

    echo json_encode(['success' => $updated . ' product(s) assigned to rack "' . $rack . '".']);

} elseif ($action === 'search_products') {
    $q = trim($_POST['q'] ?? '');
    if ($q === '') {
        echo json_encode([]);
        exit;
    }

    $like = '%' . $q . '%';
    $stmt = $connect->prepare("SELECT `id`, `barcode`, `name`, `rack` FROM `PRODUCTS` WHERE `checked` = 'Y' AND (`barcode` LIKE ? OR `name` LIKE ?) ORDER BY `name` ASC LIMIT 20");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($r = $result->fetch_assoc()) {
        $products[] = $r;
    }
    $stmt->close();
    echo json_encode($products);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
