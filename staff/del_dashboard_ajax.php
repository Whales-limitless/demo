<?php
require_once __DIR__ . '/session_security.php';
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

} elseif ($action === 'vieworder') {
    $ordno = $_POST['ordno'] ?? '';
    if ($ordno === '') { echo json_encode(['error' => 'Missing order number.']); exit; }

    // Fetch order
    $stmt = $connect->prepare("SELECT * FROM `del_orderlist` WHERE `ORDNO` = ? LIMIT 1");
    $stmt->bind_param("s", $ordno);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) { echo json_encode(['error' => 'Order not found.']); exit; }

    // Customer details
    $customer = null;
    if (!empty($order['CUSTOMERCODE'])) {
        $stmt = $connect->prepare("SELECT `HP`, `ADDRESS` FROM `del_customer` WHERE `CODE` = ? LIMIT 1");
        $stmt->bind_param("s", $order['CUSTOMERCODE']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    // Signature check
    $safeOrdno = preg_replace('/[\/\\\\:*?"<>|]/', '_', $ordno);
    $sigPath = 'uploads/signatures/' . $safeOrdno . '.png';
    $hasSigFile = file_exists(__DIR__ . '/' . $sigPath);

    // Items
    $items = [];
    $stmt = $connect->prepare("SELECT `PDESC`, `QTY`, `UOM`, `INSTALL` FROM `del_orderlistdesc` WHERE `ORDERNO` = ? ORDER BY `PDESC` ASC");
    $stmt->bind_param("s", $ordno);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $items[] = $row; }
    $stmt->close();

    echo json_encode([
        'order' => [
            'ORDNO' => $order['ORDNO'],
            'DELDATE' => $order['DELDATE'] ?? '',
            'CUSTOMER' => $order['CUSTOMER'] ?? '',
            'DRIVER' => $order['DRIVER'] ?? '',
            'REMARK' => $order['REMARK'] ?? '',
            'LOCATION' => $order['LOCATION'] ?? '',
            'ID' => $order['ID']
        ],
        'customer' => $customer,
        'items' => $items,
        'hasSigFile' => $hasSigFile,
        'sigPath' => $hasSigFile ? $sigPath : null
    ]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
