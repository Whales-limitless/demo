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
$startDate = $_POST['start_date'] ?? date('Y-m-01');
$endDate = $_POST['end_date'] ?? date('Y-m-d');
$driver = trim($_POST['driver'] ?? '');
$location = trim($_POST['location'] ?? '');

if ($driver === '') {
    echo json_encode(['error' => 'Driver code is required.']);
    exit;
}

if ($action === 'summary') {
    $where = "WHERE (o.STATUS = 'D' OR o.STATUS = 'C') AND o.DRIVERCODE = ? AND o.DELDATE >= ? AND o.DELDATE <= ?";
    $params = [$driver, $startDate, $endDate];
    $types = "sss";

    if ($location !== '') { $where .= " AND o.LOCATION = ?"; $params[] = $location; $types .= "s"; }

    $sql = "SELECT o.DRIVER, COUNT(*) AS total_orders, SUM(CAST(CASE WHEN o.DISTANT IS NULL OR o.DISTANT = '' OR o.DISTANT = '0' OR o.DISTANT = '0.00' THEN IFNULL(l.DISTANT, 0) ELSE o.DISTANT END AS DECIMAL(10,2))) AS total_distance, SUM(CAST(CASE WHEN o.RETAIL IS NULL OR o.RETAIL = '' OR o.RETAIL = '0' OR o.RETAIL = '0.00' THEN IFNULL(l.RETAIL, 0) ELSE o.RETAIL END AS DECIMAL(10,2))) AS total_commission FROM `del_orderlist` o LEFT JOIN `del_location` l ON o.LOCATION = l.NAME $where GROUP BY o.DRIVER, o.DRIVERCODE ORDER BY o.DRIVER ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

} elseif ($action === 'detailed') {
    $where = "WHERE (o.STATUS = 'D' OR o.STATUS = 'C') AND o.DRIVERCODE = ? AND o.DELDATE >= ? AND o.DELDATE <= ?";
    $params = [$driver, $startDate, $endDate];
    $types = "sss";

    if ($location !== '') { $where .= " AND o.LOCATION = ?"; $params[] = $location; $types .= "s"; }

    $sql = "SELECT o.ORDNO, o.DELDATE, o.DRIVER, o.CUSTOMER, o.LOCATION, CASE WHEN o.DISTANT IS NULL OR o.DISTANT = '' OR o.DISTANT = '0' OR o.DISTANT = '0.00' THEN IFNULL(l.DISTANT, '0.00') ELSE o.DISTANT END AS DISTANT, CASE WHEN o.RETAIL IS NULL OR o.RETAIL = '' OR o.RETAIL = '0' OR o.RETAIL = '0.00' THEN IFNULL(l.RETAIL, '0.00') ELSE o.RETAIL END AS RETAIL, CAST(o.DONEDATETIME AS TIME) AS DONETIME FROM `del_orderlist` o LEFT JOIN `del_location` l ON o.LOCATION = l.NAME $where ORDER BY o.DELDATE DESC, o.ORDNO ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
