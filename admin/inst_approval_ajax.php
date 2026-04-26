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

$adminPermission = $_SESSION['admin_permission'] ?? 'FULL';
$adminUser = $_SESSION['admin_user'] ?? 'admin';

// Auto-create table if it doesn't exist
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

if ($action === 'list') {
    $status = $_POST['status'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $userCode = $_POST['user'] ?? '';

    $where = "WHERE 1=1";
    $params = [];
    $types = "";

    if ($status === 'P' || $status === 'A' || $status === 'R') {
        $where .= " AND STATUS = ?";
        $params[] = $status;
        $types .= "s";
    }
    if ($startDate !== '') {
        $where .= " AND DATE(SUBMIT_DATETIME) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    if ($endDate !== '') {
        $where .= " AND DATE(SUBMIT_DATETIME) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    if ($userCode !== '') {
        $where .= " AND USERCODE = ?";
        $params[] = $userCode;
        $types .= "s";
    }

    $sql = "SELECT * FROM `inst_job` $where ORDER BY SUBMIT_DATETIME DESC, ID DESC";
    $stmt = $connect->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();

    // Counts
    $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $cntResult = $connect->query("SELECT STATUS, COUNT(*) AS cnt FROM `inst_job` GROUP BY STATUS");
    if ($cntResult) {
        while ($cr = $cntResult->fetch_assoc()) {
            if ($cr['STATUS'] === 'P') $counts['pending'] = (int)$cr['cnt'];
            elseif ($cr['STATUS'] === 'A') $counts['approved'] = (int)$cr['cnt'];
            elseif ($cr['STATUS'] === 'R') $counts['rejected'] = (int)$cr['cnt'];
        }
    }

    echo json_encode(['rows' => $rows, 'counts' => $counts]);

} elseif ($action === 'approve') {
    if ($adminPermission === 'VIEW') {
        echo json_encode(['error' => 'Insufficient permission.']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $commission = floatval($_POST['commission'] ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }

    $reviewed = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("UPDATE `inst_job` SET STATUS = 'A', APPROVE_REASON = ?, COMMISSION = ?, REVIEWED_BY = ?, REVIEWED_DATETIME = ? WHERE ID = ? AND STATUS = 'P'");
    $stmt->bind_param("sdssi", $reason, $commission, $adminUser, $reviewed, $id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Installation job approved.']);
        } else {
            echo json_encode(['error' => 'Job not found or already reviewed.']);
        }
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} elseif ($action === 'reject') {
    if ($adminPermission === 'VIEW') {
        echo json_encode(['error' => 'Insufficient permission.']);
        exit;
    }
    $id = intval($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID.']); exit; }

    $reviewed = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("UPDATE `inst_job` SET STATUS = 'R', REJECT_REASON = ?, COMMISSION = 0.00, REVIEWED_BY = ?, REVIEWED_DATETIME = ? WHERE ID = ? AND STATUS = 'P'");
    $stmt->bind_param("sssi", $reason, $adminUser, $reviewed, $id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => 'Installation job rejected.']);
        } else {
            echo json_encode(['error' => 'Job not found or already reviewed.']);
        }
    } else {
        echo json_encode(['error' => 'Failed: ' . $connect->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
