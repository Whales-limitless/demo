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

$userType = $_SESSION['user_type'] ?? 'S';
$userCode = $_SESSION['user_code'] ?? '';

$response = [
    'user_type' => $userType,
    'user_name' => $_SESSION['user_name'] ?? '',
    'user_code' => $userCode,
    'downloaded_at' => date('Y-m-d H:i:s'),
];

// Download delivery data for drivers (D) and admins (A)
if (($userType === 'D' || $userType === 'A') && $userCode !== '') {
    // Get all active orders for this driver
    $sql = "SELECT o.*, c.NAME AS CUSTNAME, c.HP AS CUSTPHONE, c.ADDRESS AS CUSTADDRESS
            FROM `del_orderlist` o
            LEFT JOIN `del_customer` c ON o.CUSTOMERCODE = c.CODE
            WHERE o.DRIVERCODE = ? AND o.STATUS = 'A'
            ORDER BY o.DELDATE DESC, o.ORDNO ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $userCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = [];
    while ($r = $result->fetch_assoc()) {
        $orders[] = $r;
    }
    $stmt->close();

    // Get items for each order
    foreach ($orders as &$order) {
        $ordno = $order['ORDNO'] ?? '';
        if ($ordno !== '') {
            $stmt2 = $connect->prepare("SELECT `ID`, `PDESC`, `QTY`, `UOM`, `INSTALL` FROM `del_orderlistdesc` WHERE `ORDERNO` = ? ORDER BY `PDESC` ASC");
            $stmt2->bind_param("s", $ordno);
            $stmt2->execute();
            $itemResult = $stmt2->get_result();
            $items = [];
            while ($item = $itemResult->fetch_assoc()) {
                $items[] = $item;
            }
            $stmt2->close();
            $order['items'] = $items;
        } else {
            $order['items'] = [];
        }
    }
    unset($order);

    $response['orders'] = $orders;
    $response['order_count'] = count($orders);
}

echo json_encode($response);
