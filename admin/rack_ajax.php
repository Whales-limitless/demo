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

// Ensure tables exist
$connect->query("CREATE TABLE IF NOT EXISTS `rack` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$connect->query("CREATE TABLE IF NOT EXISTS `rack_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rack_id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rack_barcode` (`rack_id`, `barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$action = $_POST['action'] ?? '';

if ($action === 'list_racks') {
    $racks = [];
    $result = $connect->query("SELECT r.*, (SELECT COUNT(*) FROM rack_product WHERE rack_id = r.id) as product_count FROM rack r ORDER BY r.code ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $racks[] = $row;
        }
    }
    echo json_encode(['racks' => $racks]);

} elseif ($action === 'create_rack') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');

    if ($code === '') {
        echo json_encode(['error' => 'Rack code is required.']);
        exit;
    }

    $chk = $connect->prepare("SELECT id FROM rack WHERE code = ? LIMIT 1");
    $chk->bind_param("s", $code);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Rack code already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $stmt = $connect->prepare("INSERT INTO rack (code, name) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $name);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack created.']);
    } else {
        echo json_encode(['error' => 'Failed to create rack: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'update_rack') {
    $id   = intval($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');

    if ($id <= 0 || $code === '') {
        echo json_encode(['error' => 'Rack ID and code are required.']);
        exit;
    }

    $chk = $connect->prepare("SELECT id FROM rack WHERE code = ? AND id != ? LIMIT 1");
    $chk->bind_param("si", $code, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Rack code already exists.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $stmt = $connect->prepare("UPDATE rack SET code = ?, name = ? WHERE id = ?");
    $stmt->bind_param("ssi", $code, $name, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack updated.']);
    } else {
        echo json_encode(['error' => 'Failed to update rack: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'delete_rack') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid rack ID.']);
        exit;
    }

    $connect->query("DELETE FROM rack_product WHERE rack_id = $id");
    $stmt = $connect->prepare("DELETE FROM rack WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Rack deleted.']);
    } else {
        echo json_encode(['error' => 'Failed to delete rack: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'get_rack_products') {
    $rack_id = intval($_POST['rack_id'] ?? 0);
    $products = [];
    $result = $connect->query("SELECT rp.id as link_id, rp.barcode, p.name, p.stkcode FROM rack_product rp LEFT JOIN PRODUCTS p ON rp.barcode = p.barcode WHERE rp.rack_id = $rack_id ORDER BY p.name ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    echo json_encode(['products' => $products]);

} elseif ($action === 'search_products') {
    $q = $connect->real_escape_string(trim($_POST['q'] ?? ''));
    $rack_id = intval($_POST['rack_id'] ?? 0);

    if (strlen($q) < 1) {
        echo json_encode(['products' => []]);
        exit;
    }

    $products = [];
    $sql = "SELECT p.barcode, p.name, p.stkcode FROM PRODUCTS p
            WHERE (p.barcode LIKE '%$q%' OR p.name LIKE '%$q%' OR p.stkcode LIKE '%$q%')
            AND p.barcode NOT IN (SELECT barcode FROM rack_product WHERE rack_id = $rack_id)
            ORDER BY p.name ASC LIMIT 20";
    $result = $connect->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    echo json_encode(['products' => $products]);

} elseif ($action === 'link_product') {
    $rack_id = intval($_POST['rack_id'] ?? 0);
    $barcode = trim($_POST['barcode'] ?? '');

    if ($rack_id <= 0 || $barcode === '') {
        echo json_encode(['error' => 'Rack ID and barcode are required.']);
        exit;
    }

    $chk = $connect->prepare("SELECT id FROM rack_product WHERE rack_id = ? AND barcode = ? LIMIT 1");
    $chk->bind_param("is", $rack_id, $barcode);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Product already linked to this rack.']);
        $chk->close();
        exit;
    }
    $chk->close();

    $stmt = $connect->prepare("INSERT INTO rack_product (rack_id, barcode) VALUES (?, ?)");
    $stmt->bind_param("is", $rack_id, $barcode);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product linked.']);
    } else {
        echo json_encode(['error' => 'Failed to link product: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'unlink_product') {
    $link_id = intval($_POST['link_id'] ?? 0);
    if ($link_id <= 0) {
        echo json_encode(['error' => 'Invalid link ID.']);
        exit;
    }

    $stmt = $connect->prepare("DELETE FROM rack_product WHERE id = ?");
    $stmt->bind_param("i", $link_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Product unlinked.']);
    } else {
        echo json_encode(['error' => 'Failed to unlink product: ' . $connect->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
