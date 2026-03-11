<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'items') {
    $grnId = intval($_POST['grn_id'] ?? 0);
    if ($grnId <= 0) {
        echo json_encode(['error' => 'Invalid GRN ID']);
        exit;
    }

    $items = [];
    $stmt = $connect->prepare("SELECT `barcode`, `product_desc`, `qty_received`, `qty_rejected`, `batch_no`, `rack_location`, `remark` FROM `grn_item` WHERE `grn_id` = ? ORDER BY `id` ASC");
    $stmt->bind_param("i", $grnId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    echo json_encode(['items' => $items]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
