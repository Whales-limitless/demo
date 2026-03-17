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
$startDate = $_POST['start_date'] ?? date('Y-m-01');
$endDate = $_POST['end_date'] ?? date('Y-m-d');
$driver = trim($_POST['driver'] ?? '');
$location = trim($_POST['location'] ?? '');

if ($driver === '') {
    echo json_encode(['error' => 'Driver code is required.']);
    exit;
}

if ($action === 'summary') {
    $where = "WHERE (STATUS = 'D' OR STATUS = 'C') AND DRIVERCODE = ? AND DELDATE >= ? AND DELDATE <= ?";
    $params = [$driver, $startDate, $endDate];
    $types = "sss";

    if ($location !== '') { $where .= " AND LOCATION = ?"; $params[] = $location; $types .= "s"; }

    $sql = "SELECT DRIVER, COUNT(*) AS total_orders, SUM(CAST(DISTANT AS DECIMAL(10,2))) AS total_distance, SUM(CAST(RETAIL AS DECIMAL(10,2))) AS total_commission FROM `del_orderlist` $where GROUP BY DRIVER, DRIVERCODE ORDER BY DRIVER ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

} elseif ($action === 'detailed') {
    $where = "WHERE (STATUS = 'D' OR STATUS = 'C') AND DRIVERCODE = ? AND DELDATE >= ? AND DELDATE <= ?";
    $params = [$driver, $startDate, $endDate];
    $types = "sss";

    if ($location !== '') { $where .= " AND LOCATION = ?"; $params[] = $location; $types .= "s"; }

    $sql = "SELECT ORDNO, DELDATE, DRIVER, CUSTOMER, LOCATION, DISTANT, RETAIL, CAST(DONEDATETIME AS TIME) AS DONETIME FROM `del_orderlist` $where ORDER BY DELDATE DESC, ORDNO ASC";
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
