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

$action = $_POST['action'] ?? '';

// Check if branch_code column exists on orderlist
$hasBranchCol = false;
$colCheck = $connect->query("SHOW COLUMNS FROM `orderlist` LIKE 'branch_code'");
if ($colCheck && $colCheck->num_rows > 0) $hasBranchCol = true;

// Build branch name lookup
$branchNames = [];
$brRes = $connect->query("SELECT code, name FROM `branch`");
if ($brRes) { while ($br = $brRes->fetch_assoc()) $branchNames[$br['code']] = $br['name']; }

if ($action === 'list') {
    $startDate = $_POST['start_date'] ?? date('Y-m-01');
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    $search = trim($_POST['search'] ?? '');
    $type = $_POST['type'] ?? 'ALL';
    $branch = trim($_POST['branch'] ?? '');

    $typeClause = "PTYPE IN ('STOCKIN','PURCHASE')";
    if ($type === 'STOCKIN' || $type === 'PURCHASE') {
        $typeClause = "PTYPE = '" . $connect->real_escape_string($type) . "'";
    }

    $branchSelect = $hasBranchCol ? ", MAX(branch_code) AS branch_code" : "";

    $sqlParams = [$startDate, $endDate];
    $sqlTypes = "ss";

    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql = "SELECT SALNUM, MAX(NAME) AS NAME, MAX(SDATE) AS SDATE, MAX(TTIME) AS TTIME,
                       SUM(QTY) AS TOTAL_QTY, COUNT(*) AS ITEM_COUNT,
                       MAX(TXTTO) AS TXTTO, MAX(PTYPE) AS PTYPE $branchSelect
                FROM `orderlist`
                WHERE $typeClause AND SDATE BETWEEN ? AND ?
                  AND SALNUM IN (
                      SELECT DISTINCT SALNUM FROM `orderlist`
                      WHERE $typeClause AND SDATE BETWEEN ? AND ?
                        AND (SALNUM LIKE ? OR PDESC LIKE ? OR BARCODE LIKE ? OR NAME LIKE ?)
                  )
                GROUP BY SALNUM
                ORDER BY SDATE DESC, TTIME DESC";
        $sqlParams = [$startDate, $endDate, $startDate, $endDate, $like, $like, $like, $like];
        $sqlTypes = "ssssssss";
    } else {
        $sql = "SELECT SALNUM, MAX(NAME) AS NAME, MAX(SDATE) AS SDATE, MAX(TTIME) AS TTIME,
                       SUM(QTY) AS TOTAL_QTY, COUNT(*) AS ITEM_COUNT,
                       MAX(TXTTO) AS TXTTO, MAX(PTYPE) AS PTYPE $branchSelect
                FROM `orderlist`
                WHERE $typeClause AND SDATE BETWEEN ? AND ?
                GROUP BY SALNUM
                ORDER BY SDATE DESC, TTIME DESC";
    }

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Query error: ' . $connect->error]);
        exit;
    }
    $stmt->bind_param($sqlTypes, ...$sqlParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $bc = $row['branch_code'] ?? '';
        $row['branch_name'] = $branchNames[$bc] ?? $bc;
        if ($branch !== '' && $bc !== $branch) continue;
        $rows[] = $row;
    }
    $stmt->close();

    echo json_encode(['rows' => $rows]);
    exit;
}

if ($action === 'items') {
    $salnum = $_POST['salnum'] ?? '';
    if (empty($salnum)) {
        echo json_encode(['error' => 'Invalid order number']);
        exit;
    }
    $stmt = $connect->prepare("SELECT `BARCODE`, `PDESC`, `QTY` FROM `orderlist` WHERE `SALNUM` = ? AND `PTYPE` IN ('STOCKIN','PURCHASE') AND `BARCODE` <> 'PT' ORDER BY `ID` ASC");
    $stmt->bind_param("s", $salnum);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    echo json_encode(['items' => $items]);
    exit;
}

if ($action === 'branches') {
    $branches = [];
    foreach ($branchNames as $code => $name) {
        $branches[] = ['code' => $code, 'name' => $name];
    }
    echo json_encode(['branches' => $branches]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
