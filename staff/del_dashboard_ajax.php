<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'items') {
    $id = intval($_POST['id'] ?? 0);
    // Get order number first
    $stmt = $connect->prepare("SELECT `ORDNO` FROM `del_orderlist` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$r) {
        echo json_encode(['error' => 'Order not found.']);
        exit;
    }

    $ordno = $r['ORDNO'];
    $stmt = $connect->prepare("SELECT `PDESC`, `QTY`, `UOM`, `INSTALL` FROM `del_orderlistdesc` WHERE `ORDERNO` = ? ORDER BY `PDESC` ASC");
    $stmt->bind_param("s", $ordno);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) { $items[] = $row; }
    $stmt->close();

    echo json_encode(['items' => $items]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
