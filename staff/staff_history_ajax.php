<?php
require_once __DIR__ . '/session_security.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'items') {
    $salnum = $connect->real_escape_string($_POST['salnum'] ?? '');
    if (empty($salnum)) {
        echo json_encode(['error' => 'Invalid order number']);
        exit;
    }

    $items = [];
    $result = $connect->query("SELECT `BARCODE`, `PDESC`, `QTY` FROM `orderlist` WHERE `SALNUM` = '$salnum' AND `PTYPE` IN ('STOCKIN','PURCHASE') AND `BARCODE` <> 'PT' ORDER BY `ID` ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }

    echo json_encode(['items' => $items]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
