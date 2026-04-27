<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Auto-create inst_job table if it doesn't exist
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

$action = $_POST['action'] ?? '';
$startDate = $_POST['start_date'] ?? date('Y-m-01');
$endDate = $_POST['end_date'] ?? date('Y-m-d');
$driver = trim($_POST['driver'] ?? '');
$location = trim($_POST['location'] ?? '');

if ($action === 'summary') {
    $where = "WHERE (STATUS = 'D' OR STATUS = 'C') AND DELDATE >= ? AND DELDATE <= ?";
    $params = [$startDate, $endDate];
    $types = "ss";

    if ($driver !== '') { $where .= " AND DRIVERCODE = ?"; $params[] = $driver; $types .= "s"; }
    if ($location !== '') { $where .= " AND LOCATION = ?"; $params[] = $location; $types .= "s"; }

    $sql = "SELECT DRIVER, DRIVERCODE, COUNT(*) AS total_orders, SUM(CAST(DISTANT AS DECIMAL(10,2))) AS total_distance, SUM(CAST(RETAIL AS DECIMAL(10,2))) AS total_distance_commission FROM `del_orderlist` $where GROUP BY DRIVER, DRIVERCODE ORDER BY DRIVER ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();

    // Fetch installation commission grouped per user code (approved jobs in the date range)
    $instWhere = "WHERE STATUS = 'A' AND DATE(SUBMIT_DATETIME) >= ? AND DATE(SUBMIT_DATETIME) <= ?";
    $instParams = [$startDate, $endDate];
    $instTypes = "ss";
    if ($driver !== '') { $instWhere .= " AND USERCODE = ?"; $instParams[] = $driver; $instTypes .= "s"; }

    $instSql = "SELECT USERCODE, USERNAME, COUNT(*) AS cnt, COALESCE(SUM(COMMISSION),0) AS total FROM `inst_job` $instWhere GROUP BY USERCODE, USERNAME";
    $instStmt = $connect->prepare($instSql);
    $instStmt->bind_param($instTypes, ...$instParams);
    $instStmt->execute();
    $instRes = $instStmt->get_result();
    $instMap = [];
    while ($ir = $instRes->fetch_assoc()) {
        $instMap[$ir['USERCODE']] = ['name' => $ir['USERNAME'], 'cnt' => (int)$ir['cnt'], 'total' => (float)$ir['total']];
    }
    $instStmt->close();

    // Merge installation commission into delivery summary rows
    foreach ($rows as &$row) {
        $code = $row['DRIVERCODE'];
        $instCnt = isset($instMap[$code]) ? $instMap[$code]['cnt'] : 0;
        $instTotal = isset($instMap[$code]) ? $instMap[$code]['total'] : 0.0;
        $distComm = (float)$row['total_distance_commission'];
        $row['total_distance_commission'] = number_format($distComm, 2, '.', '');
        $row['total_install_jobs'] = $instCnt;
        $row['total_install_commission'] = number_format($instTotal, 2, '.', '');
        $row['total_commission'] = number_format($distComm + $instTotal, 2, '.', '');
        if (isset($instMap[$code])) unset($instMap[$code]);
    }
    unset($row);

    // Drivers with installation commission but no delivery rows
    foreach ($instMap as $code => $info) {
        $rows[] = [
            'DRIVER' => $info['name'] ?: $code,
            'DRIVERCODE' => $code,
            'total_orders' => 0,
            'total_distance' => '0.00',
            'total_distance_commission' => '0.00',
            'total_install_jobs' => $info['cnt'],
            'total_install_commission' => number_format($info['total'], 2, '.', ''),
            'total_commission' => number_format($info['total'], 2, '.', '')
        ];
    }

    echo json_encode(['rows' => $rows]);

} elseif ($action === 'detailed') {
    $where = "WHERE (STATUS = 'D' OR STATUS = 'C') AND DELDATE >= ? AND DELDATE <= ?";
    $params = [$startDate, $endDate];
    $types = "ss";

    if ($driver !== '') { $where .= " AND DRIVERCODE = ?"; $params[] = $driver; $types .= "s"; }
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
