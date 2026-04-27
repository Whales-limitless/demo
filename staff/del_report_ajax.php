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

    $sql = "SELECT DRIVER, COUNT(*) AS total_orders, SUM(CAST(DISTANT AS DECIMAL(10,2))) AS total_distance, SUM(CAST(RETAIL AS DECIMAL(10,2))) AS total_distance_commission FROM `del_orderlist` $where GROUP BY DRIVER, DRIVERCODE ORDER BY DRIVER ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();

    // Get total approved installation commission for this user in the date range
    $instCommission = 0.00;
    $instCount = 0;
    // Auto-create table if needed (idempotent)
    $connect->query("CREATE TABLE IF NOT EXISTS `inst_job` (
      `ID` int(11) NOT NULL AUTO_INCREMENT,
      `USERCODE` varchar(50) NOT NULL,
      `USERNAME` varchar(80) NOT NULL,
      `IMAGE` varchar(200) NOT NULL DEFAULT '',
      `REMARK` text NOT NULL,
      `STATUS` varchar(1) NOT NULL DEFAULT 'P',
      `REJECT_REASON` text NOT NULL,
      `APPROVE_REASON` text NOT NULL,
      `COMMISSION` double(10,2) NOT NULL DEFAULT 0.00,
      `SUBMIT_DATETIME` datetime NOT NULL,
      `REVIEWED_BY` varchar(50) NOT NULL DEFAULT '',
      `REVIEWED_DATETIME` datetime DEFAULT NULL,
      PRIMARY KEY (`ID`),
      KEY `idx_user` (`USERCODE`),
      KEY `idx_status` (`STATUS`),
      KEY `idx_submit` (`SUBMIT_DATETIME`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $instStmt = $connect->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(COMMISSION),0) AS total FROM `inst_job` WHERE USERCODE = ? AND STATUS = 'A' AND DATE(SUBMIT_DATETIME) >= ? AND DATE(SUBMIT_DATETIME) <= ?");
    $instStmt->bind_param("sss", $driver, $startDate, $endDate);
    $instStmt->execute();
    $ir = $instStmt->get_result()->fetch_assoc();
    if ($ir) {
        $instCount = (int)$ir['cnt'];
        $instCommission = (float)$ir['total'];
    }
    $instStmt->close();

    // Merge installation commission onto each row, and total
    foreach ($rows as &$row) {
        $row['total_install_jobs'] = $instCount;
        $row['total_install_commission'] = number_format($instCommission, 2, '.', '');
        $row['total_distance_commission'] = number_format((float)$row['total_distance_commission'], 2, '.', '');
        $row['total_commission'] = number_format((float)$row['total_distance_commission'] + $instCommission, 2, '.', '');
    }
    unset($row);

    // If there are no delivery rows but there's an installation commission, return a synthetic row
    if (empty($rows) && $instCount > 0) {
        $rows[] = [
            'DRIVER' => '',
            'total_orders' => 0,
            'total_distance' => '0.00',
            'total_distance_commission' => '0.00',
            'total_install_jobs' => $instCount,
            'total_install_commission' => number_format($instCommission, 2, '.', ''),
            'total_commission' => number_format($instCommission, 2, '.', '')
        ];
    }

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
