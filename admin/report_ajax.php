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
$startDate = $_POST['start_date'] ?? date('Y-m-01');
$endDate = $_POST['end_date'] ?? date('Y-m-d');

// ── Stock Movement Report ──
if ($action === 'stock_movement') {
    $search = trim($_POST['search'] ?? '');
    $selectedBranches = $_POST['branches'] ?? [];
    if (!is_array($selectedBranches)) $selectedBranches = [];
    // Filter out empty values
    $selectedBranches = array_values(array_filter($selectedBranches, function($v) { return trim($v) !== ''; }));
    $hasBranchFilter = !empty($selectedBranches);

    // Optional cutoff date: ignore all movements before this date (fresh start)
    $cutoffDate = isset($_POST['cutoff_date']) && $_POST['cutoff_date'] !== '' ? $_POST['cutoff_date'] : null;
    $hasCutoff = !empty($cutoffDate);

    $searchParam = '';
    if ($search !== '') {
        $searchParam = '%' . $search . '%';
    }

    $collate = "COLLATE utf8mb4_unicode_ci";

    // Check if grn/grn_item tables exist
    $hasGrn = $connect->query("SHOW TABLES LIKE 'grn'")->num_rows > 0
           && $connect->query("SHOW TABLES LIKE 'grn_item'")->num_rows > 0;

    // ── STEP 1: Get all products with movement (combined UNION) ──
    $unionSql = "SELECT BARCODE $collate AS BARCODE, PDESC AS description FROM `orderlist`
                WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT' AND SALNUM LIKE 'PW%'";
    $unionParams = [$startDate, $endDate];
    $unionTypes = "ss";
    if ($search !== '') {
        $unionSql .= " AND (BARCODE LIKE ? OR PDESC LIKE ?)";
        $unionParams[] = $searchParam;
        $unionParams[] = $searchParam;
        $unionTypes .= "ss";
    }
    $unionSql .= " GROUP BY BARCODE, PDESC";

    $unionSql .= " UNION SELECT sa.BARCODE $collate AS BARCODE, COALESCE(p2.name, sa.BARCODE) AS description FROM `stockadj` sa
                LEFT JOIN `PRODUCTS` p2 ON sa.BARCODE $collate = p2.barcode $collate
                WHERE sa.SDATE >= ? AND sa.SDATE <= ?";
    $unionParams[] = $startDate;
    $unionParams[] = $endDate;
    $unionTypes .= "ss";
    if ($search !== '') {
        $unionSql .= " AND (sa.BARCODE LIKE ? OR sa.BARCODE LIKE ?)";
        $unionParams[] = $searchParam;
        $unionParams[] = $searchParam;
        $unionTypes .= "ss";
    }
    $unionSql .= " GROUP BY sa.BARCODE";

    if ($hasGrn) {
        $unionSql .= " UNION SELECT gi.barcode $collate AS BARCODE, COALESCE(p3.name, gi.product_desc, gi.barcode) AS description
                FROM `grn_item` gi
                LEFT JOIN `grn` g ON gi.grn_id = g.id
                LEFT JOIN `PRODUCTS` p3 ON gi.barcode $collate = p3.barcode $collate
                WHERE g.receive_date >= ? AND g.receive_date <= ?";
        $unionParams[] = $startDate;
        $unionParams[] = $endDate;
        $unionTypes .= "ss";
        if ($search !== '') {
            $unionSql .= " AND (gi.barcode LIKE ? OR gi.product_desc LIKE ?)";
            $unionParams[] = $searchParam;
            $unionParams[] = $searchParam;
            $unionTypes .= "ss";
        }
        $unionSql .= " GROUP BY gi.barcode";
    }

    // ── STEP 2: Main query with total In/Out/Adj ──
    $sql = "SELECT combined.BARCODE, combined.description,
                COALESCE(p.qoh, 0) AS current_qoh,
                COALESCE(orders.qty_out, 0) AS qty_out,
                COALESCE(orders.qty_stockin, 0) AS qty_stockin,
                COALESCE(adj.adj_in, 0) AS adj_in,
                COALESCE(adj.adj_out, 0) AS adj_out,
                " . ($hasGrn ? "COALESCE(grn_in.qty_grn_in, 0)" : "0") . " AS qty_grn_in,
                COALESCE(orders_all.qty_out_all, 0) AS qty_out_all,
                COALESCE(orders_all.qty_stockin_all, 0) AS qty_stockin_all,
                COALESCE(adj_all.adj_in_all, 0) AS adj_in_all,
                COALESCE(adj_all.adj_out_all, 0) AS adj_out_all,
                " . ($hasGrn ? "COALESCE(grn_all.qty_grn_in_all, 0)" : "0") . " AS qty_grn_in_all,
                " . ($hasCutoff ? "COALESCE(orders_cutoff.qty_out_cutoff, 0)" : "0") . " AS qty_out_cutoff,
                " . ($hasCutoff ? "COALESCE(orders_cutoff.qty_stockin_cutoff, 0)" : "0") . " AS qty_stockin_cutoff,
                " . ($hasCutoff ? "COALESCE(adj_cutoff.adj_in_cutoff, 0)" : "0") . " AS adj_in_cutoff,
                " . ($hasCutoff ? "COALESCE(adj_cutoff.adj_out_cutoff, 0)" : "0") . " AS adj_out_cutoff,
                " . ($hasCutoff && $hasGrn ? "COALESCE(grn_cutoff.qty_grn_in_cutoff, 0)" : "0") . " AS qty_grn_in_cutoff
            FROM ($unionSql) combined
            LEFT JOIN `PRODUCTS` p ON combined.BARCODE $collate = p.barcode $collate
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTY > 0 AND STATUS = 'DONE' AND (PTYPE IS NULL OR PTYPE <> 'STOCKIN') THEN QTY ELSE 0 END) AS qty_out,
                    SUM(CASE WHEN PTYPE = 'STOCKIN' THEN QTY ELSE 0 END) AS qty_stockin
                FROM `orderlist`
                WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT' AND SALNUM LIKE 'PW%'
                GROUP BY BARCODE
            ) orders ON combined.BARCODE $collate = orders.BARCODE $collate
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTYADJ > 0 THEN QTYADJ ELSE 0 END) AS adj_in,
                    SUM(CASE WHEN QTYADJ < 0 THEN ABS(QTYADJ) ELSE 0 END) AS adj_out
                FROM `stockadj`
                WHERE SDATE >= ? AND SDATE <= ?
                GROUP BY BARCODE
            ) adj ON combined.BARCODE $collate = adj.BARCODE $collate
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTY > 0 AND STATUS = 'DONE' AND (PTYPE IS NULL OR PTYPE <> 'STOCKIN') THEN QTY ELSE 0 END) AS qty_out_all,
                    SUM(CASE WHEN PTYPE = 'STOCKIN' THEN QTY ELSE 0 END) AS qty_stockin_all
                FROM `orderlist`
                WHERE SDATE >= ? AND BARCODE <> 'PT' AND SALNUM LIKE 'PW%'
                GROUP BY BARCODE
            ) orders_all ON combined.BARCODE $collate = orders_all.BARCODE $collate
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTYADJ > 0 THEN QTYADJ ELSE 0 END) AS adj_in_all,
                    SUM(CASE WHEN QTYADJ < 0 THEN ABS(QTYADJ) ELSE 0 END) AS adj_out_all
                FROM `stockadj`
                WHERE SDATE >= ?
                GROUP BY BARCODE
            ) adj_all ON combined.BARCODE $collate = adj_all.BARCODE $collate";

    $allParams = $unionParams;
    $allTypes = $unionTypes;
    // orders subquery
    $allParams[] = $startDate; $allParams[] = $endDate; $allTypes .= "ss";
    // adj subquery
    $allParams[] = $startDate; $allParams[] = $endDate; $allTypes .= "ss";
    // orders_all subquery (from startDate to now, no end date - for accurate opening balance)
    $allParams[] = $startDate; $allTypes .= "s";
    // adj_all subquery (from startDate to now, no end date - for accurate opening balance)
    $allParams[] = $startDate; $allTypes .= "s";

    if ($hasGrn) {
        $sql .= " LEFT JOIN (
                SELECT gi.barcode AS BARCODE, SUM(gi.qty_received) AS qty_grn_in
                FROM `grn_item` gi LEFT JOIN `grn` g ON gi.grn_id = g.id
                WHERE g.receive_date >= ? AND g.receive_date <= ?
                GROUP BY gi.barcode
            ) grn_in ON combined.BARCODE $collate = grn_in.BARCODE $collate";
        $allParams[] = $startDate; $allParams[] = $endDate; $allTypes .= "ss";
        // grn_all subquery (from startDate to now, no end date - for accurate opening balance)
        $sql .= " LEFT JOIN (
                SELECT gi.barcode AS BARCODE, SUM(gi.qty_received) AS qty_grn_in_all
                FROM `grn_item` gi LEFT JOIN `grn` g ON gi.grn_id = g.id
                WHERE g.receive_date >= ?
                GROUP BY gi.barcode
            ) grn_all ON combined.BARCODE $collate = grn_all.BARCODE $collate";
        $allParams[] = $startDate; $allTypes .= "s";
    }

    // Cutoff (fresh start) subqueries - movements from cutoffDate to day before startDate
    if ($hasCutoff) {
        $sql .= " LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTY > 0 AND STATUS = 'DONE' AND (PTYPE IS NULL OR PTYPE <> 'STOCKIN') THEN QTY ELSE 0 END) AS qty_out_cutoff,
                    SUM(CASE WHEN PTYPE = 'STOCKIN' THEN QTY ELSE 0 END) AS qty_stockin_cutoff
                FROM `orderlist`
                WHERE SDATE >= ? AND SDATE < ? AND BARCODE <> 'PT' AND SALNUM LIKE 'PW%'
                GROUP BY BARCODE
            ) orders_cutoff ON combined.BARCODE $collate = orders_cutoff.BARCODE $collate
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTYADJ > 0 THEN QTYADJ ELSE 0 END) AS adj_in_cutoff,
                    SUM(CASE WHEN QTYADJ < 0 THEN ABS(QTYADJ) ELSE 0 END) AS adj_out_cutoff
                FROM `stockadj`
                WHERE SDATE >= ? AND SDATE < ?
                GROUP BY BARCODE
            ) adj_cutoff ON combined.BARCODE $collate = adj_cutoff.BARCODE $collate";
        $allParams[] = $cutoffDate; $allParams[] = $startDate; $allTypes .= "ss";
        $allParams[] = $cutoffDate; $allParams[] = $startDate; $allTypes .= "ss";
        if ($hasGrn) {
            $sql .= " LEFT JOIN (
                    SELECT gi.barcode AS BARCODE, SUM(gi.qty_received) AS qty_grn_in_cutoff
                    FROM `grn_item` gi LEFT JOIN `grn` g ON gi.grn_id = g.id
                    WHERE g.receive_date >= ? AND g.receive_date < ?
                    GROUP BY gi.barcode
                ) grn_cutoff ON combined.BARCODE $collate = grn_cutoff.BARCODE $collate";
            $allParams[] = $cutoffDate; $allParams[] = $startDate; $allTypes .= "ss";
        }
    }

    $sql .= " ORDER BY combined.description ASC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Query error: ' . $connect->error]);
        exit;
    }
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $result = $stmt->get_result();

    // Build base rows keyed by barcode
    $productRows = [];
    while ($r = $result->fetch_assoc()) {
        $out = floatval($r['qty_out']);
        $stockIn = floatval($r['qty_stockin']);
        $grnIn = floatval($r['qty_grn_in']);
        $adjIn = floatval($r['adj_in']);
        $adjOut = floatval($r['adj_out']);
        $currentQoh = floatval($r['current_qoh']);
        $totalIn = $stockIn + $grnIn + $adjIn;

        if ($hasCutoff) {
            // Fresh start: opening = net movements from cutoffDate to day before startDate
            // Treats stock as 0 at cutoffDate, ignoring all older data
            $outCutoff = floatval($r['qty_out_cutoff']);
            $stockInCutoff = floatval($r['qty_stockin_cutoff']);
            $grnInCutoff = floatval($r['qty_grn_in_cutoff']);
            $adjInCutoff = floatval($r['adj_in_cutoff']);
            $adjOutCutoff = floatval($r['adj_out_cutoff']);
            $opening = ($stockInCutoff + $grnInCutoff + $adjInCutoff) - $outCutoff - $adjOutCutoff;
        } else {
            // Normal: reverse ALL movements from startDate to NOW for accuracy
            $outAll = floatval($r['qty_out_all']);
            $stockInAll = floatval($r['qty_stockin_all']);
            $grnInAll = floatval($r['qty_grn_in_all']);
            $adjInAll = floatval($r['adj_in_all']);
            $adjOutAll = floatval($r['adj_out_all']);
            $totalInAll = $stockInAll + $grnInAll + $adjInAll;
            $opening = $currentQoh + $outAll - $totalInAll + $adjOutAll;
        }
        $closing = $opening + $totalIn - $out - $adjOut;

        $productRows[$r['BARCODE']] = [
            'barcode' => $r['BARCODE'],
            'description' => $r['description'] ?: $r['BARCODE'],
            'opening' => $opening,
            'in' => $totalIn,
            'out' => $out,
            'adj' => $adjIn - $adjOut,
            'closing' => $closing
        ];
    }
    $stmt->close();

    // ── STEP 3: If branch filter, get per-branch In/Out/Adj ──
    $branchInfo = [];
    if ($hasBranchFilter) {
        // Fetch branch names
        $branchPlaceholders = implode(',', array_fill(0, count($selectedBranches), '?'));
        $brStmt = $connect->prepare("SELECT `code`, `name` FROM `branch` WHERE `code` IN ($branchPlaceholders) ORDER BY `name` ASC");
        $brTypes = str_repeat('s', count($selectedBranches));
        $brStmt->bind_param($brTypes, ...$selectedBranches);
        $brStmt->execute();
        $brResult = $brStmt->get_result();
        while ($br = $brResult->fetch_assoc()) {
            $branchInfo[] = ['code' => $br['code'], 'name' => $br['name']];
        }
        $brStmt->close();

        // Per-branch orderlist data (In/Out)
        $branchInPlaceholders = implode(',', array_fill(0, count($selectedBranches), '?'));
        $brOrderSql = "SELECT BARCODE, COALESCE(branch_code, '') AS branch_code,
                SUM(CASE WHEN QTY > 0 AND STATUS = 'DONE' AND (PTYPE IS NULL OR PTYPE <> 'STOCKIN') THEN QTY ELSE 0 END) AS qty_out,
                SUM(CASE WHEN PTYPE = 'STOCKIN' THEN QTY ELSE 0 END) AS qty_stockin
            FROM `orderlist`
            WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT' AND SALNUM LIKE 'PW%'
                AND COALESCE(branch_code, '') IN ($branchInPlaceholders)
            GROUP BY BARCODE, branch_code";
        $brOrderParams = [$startDate, $endDate];
        $brOrderTypes = "ss";
        foreach ($selectedBranches as $bc) { $brOrderParams[] = $bc; $brOrderTypes .= "s"; }

        $brOrderStmt = $connect->prepare($brOrderSql);
        $brOrderStmt->bind_param($brOrderTypes, ...$brOrderParams);
        $brOrderStmt->execute();
        $brOrderResult = $brOrderStmt->get_result();

        // Initialize branch data for all products
        foreach ($productRows as $barcode => &$row) {
            $row['branches'] = [];
            foreach ($selectedBranches as $bc) {
                $row['branches'][$bc] = ['in' => 0, 'out' => 0, 'adj' => 0];
            }
        }
        unset($row);

        while ($r = $brOrderResult->fetch_assoc()) {
            $bc = $r['branch_code'];
            $barcode = $r['BARCODE'];
            if (isset($productRows[$barcode]) && isset($productRows[$barcode]['branches'][$bc])) {
                $productRows[$barcode]['branches'][$bc]['in'] += floatval($r['qty_stockin']);
                $productRows[$barcode]['branches'][$bc]['out'] += floatval($r['qty_out']);
            }
        }
        $brOrderStmt->close();

        // Per-branch stockadj data (Adj)
        $brAdjSql = "SELECT BARCODE, COALESCE(branch_code, '') AS branch_code,
                SUM(CASE WHEN QTYADJ > 0 THEN QTYADJ ELSE 0 END) AS adj_in,
                SUM(CASE WHEN QTYADJ < 0 THEN ABS(QTYADJ) ELSE 0 END) AS adj_out
            FROM `stockadj`
            WHERE SDATE >= ? AND SDATE <= ?
                AND COALESCE(branch_code, '') IN ($branchInPlaceholders)
            GROUP BY BARCODE, branch_code";
        $brAdjParams = [$startDate, $endDate];
        $brAdjTypes = "ss";
        foreach ($selectedBranches as $bc) { $brAdjParams[] = $bc; $brAdjTypes .= "s"; }

        $brAdjStmt = $connect->prepare($brAdjSql);
        $brAdjStmt->bind_param($brAdjTypes, ...$brAdjParams);
        $brAdjStmt->execute();
        $brAdjResult = $brAdjStmt->get_result();

        while ($r = $brAdjResult->fetch_assoc()) {
            $bc = $r['branch_code'];
            $barcode = $r['BARCODE'];
            if (isset($productRows[$barcode]) && isset($productRows[$barcode]['branches'][$bc])) {
                $productRows[$barcode]['branches'][$bc]['adj'] += floatval($r['adj_in']) - floatval($r['adj_out']);
                $productRows[$barcode]['branches'][$bc]['in'] += floatval($r['adj_in']);
            }
        }
        $brAdjStmt->close();
    }

    $rows = array_values($productRows);
    $response = ['rows' => $rows];
    if ($hasBranchFilter) {
        $response['branches'] = $branchInfo;
    }
    echo json_encode($response);

// ── Opening Balance Detail (modal drill-down) ──
} elseif ($action === 'opening_balance_detail') {
    $barcode = trim($_POST['barcode'] ?? '');
    $cutoffDate = isset($_POST['cutoff_date']) && $_POST['cutoff_date'] !== '' ? $_POST['cutoff_date'] : null;
    $collate = "COLLATE utf8mb4_unicode_ci";

    if ($barcode === '') {
        echo json_encode(['error' => 'Barcode required']);
        exit;
    }

    $hasGrn = $connect->query("SHOW TABLES LIKE 'grn'")->num_rows > 0
           && $connect->query("SHOW TABLES LIKE 'grn_item'")->num_rows > 0;

    // Lower bound: cutoff date if set, otherwise all history
    $lowerBound = $cutoffDate ?: '1900-01-01';

    // Fetch all transactions before startDate (or between cutoff and startDate)
    $sql = "SELECT txn_date, txn_type, reference, qty_in, qty_out FROM (
            SELECT o.SDATE AS txn_date,
                CASE WHEN o.PTYPE = 'STOCKIN' THEN 'Stock In' ELSE 'Sale' END AS txn_type,
                o.SALNUM AS reference,
                CASE WHEN o.PTYPE = 'STOCKIN' THEN o.QTY ELSE 0 END AS qty_in,
                CASE WHEN o.PTYPE IS NULL OR o.PTYPE <> 'STOCKIN' THEN o.QTY ELSE 0 END AS qty_out
            FROM `orderlist` o
            WHERE o.BARCODE $collate = ? AND o.SDATE >= ? AND o.SDATE < ?
                AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT' AND o.SALNUM LIKE 'PW%'

            UNION ALL

            SELECT sa.SDATE AS txn_date,
                CONCAT('Adjustment', CASE WHEN sa.LOSS_REASON IS NOT NULL AND sa.LOSS_REASON <> '' AND sa.LOSS_REASON <> 'ADJUSTMENT'
                    THEN CONCAT(' (', sa.LOSS_REASON, ')') ELSE '' END) AS txn_type,
                sa.REMARK AS reference,
                CASE WHEN sa.QTYADJ > 0 THEN sa.QTYADJ ELSE 0 END AS qty_in,
                CASE WHEN sa.QTYADJ < 0 THEN ABS(sa.QTYADJ) ELSE 0 END AS qty_out
            FROM `stockadj` sa
            WHERE sa.BARCODE $collate = ? AND sa.SDATE >= ? AND sa.SDATE < ?";

    $params = [$barcode, $lowerBound, $startDate, $barcode, $lowerBound, $startDate];
    $types = "ssssss";

    if ($hasGrn) {
        $sql .= "
            UNION ALL

            SELECT g.receive_date AS txn_date,
                'GRN' AS txn_type,
                COALESCE(g.grn_number, '') AS reference,
                gi.qty_received AS qty_in,
                0 AS qty_out
            FROM `grn_item` gi
            LEFT JOIN `grn` g ON gi.grn_id = g.id
            WHERE gi.barcode $collate = ? AND g.receive_date >= ? AND g.receive_date < ?";
        $params[] = $barcode;
        $params[] = $lowerBound;
        $params[] = $startDate;
        $types .= "sss";
    }

    $sql .= ") AS combined ORDER BY txn_date ASC, qty_in DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Query error: ' . $connect->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $runningBalance = 0;
    while ($r = $result->fetch_assoc()) {
        $in = floatval($r['qty_in']);
        $out = floatval($r['qty_out']);
        $runningBalance += $in - $out;
        $rows[] = [
            'date' => $r['txn_date'],
            'type' => $r['txn_type'],
            'reference' => $r['reference'],
            'qty_in' => $in,
            'qty_out' => $out,
            'balance' => $runningBalance
        ];
    }
    $stmt->close();

    echo json_encode(['rows' => $rows, 'opening_balance' => $runningBalance]);

// ── Sales by Date Report ──
} elseif ($action === 'sales_by_date') {
    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

    $sql = "SELECT
                o.SDATE AS sale_date,
                COUNT(DISTINCT o.SALNUM) AS total_orders,
                SUM(o.QTY) AS total_qty,
                COUNT(DISTINCT o.BARCODE) AS unique_products
            FROM `orderlist` o
            $where
            GROUP BY o.SDATE
            ORDER BY o.SDATE DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

// ── Sales by Staff Report ──
} elseif ($action === 'sales_by_staff') {
    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

    $sql = "SELECT
                o.NAME AS staff_name,
                COUNT(DISTINCT o.SALNUM) AS total_orders,
                SUM(o.QTY) AS total_qty,
                COUNT(DISTINCT o.BARCODE) AS unique_products,
                MIN(o.SDATE) AS first_sale,
                MAX(o.SDATE) AS last_sale
            FROM `orderlist` o
            $where
            GROUP BY o.NAME
            ORDER BY total_qty DESC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

// ── Sales by Product Report ──
} elseif ($action === 'sales_by_product') {
    $search = trim($_POST['search'] ?? '');

    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

    if ($search !== '') {
        $where .= " AND (o.BARCODE LIKE ? OR o.PDESC LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $sql = "SELECT
                o.BARCODE,
                o.PDESC AS description,
                SUM(o.QTY) AS total_qty,
                COUNT(DISTINCT o.SALNUM) AS total_orders,
                COUNT(DISTINCT o.SDATE) AS days_sold
            FROM `orderlist` o
            $where
            GROUP BY o.BARCODE, o.PDESC
            ORDER BY total_qty DESC
            LIMIT 1000";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

// ── Sales by Branch Report ──
} elseif ($action === 'sales_by_branch') {
    $search = trim($_POST['search'] ?? '');
    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

    if ($search !== '') {
        $where .= " AND (o.BARCODE LIKE ? OR o.PDESC LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $branches = $_POST['branches'] ?? [];
    if (!empty($branches) && is_array($branches)) {
        $placeholders = implode(',', array_fill(0, count($branches), '?'));
        $where .= " AND o.branch_code IN ($placeholders)";
        foreach ($branches as $bc) {
            $params[] = $bc;
            $types .= "s";
        }
    }

    // Orders store the actual branch in `branch_code` column (e.g. BR0001),
    // while `OUTLET` is just the sales channel (always 'WEB').
    // Join via branch_code to get the real branch name.
    $hasBranch = $connect->query("SHOW TABLES LIKE 'branch'")->num_rows > 0;

    if ($hasBranch) {
        $branchNameExpr = "COALESCE(b.name, IF(o.branch_code = '' OR o.branch_code IS NULL, 'No Branch', o.branch_code))";
        $joins = "LEFT JOIN `branch` b ON o.branch_code COLLATE utf8mb4_unicode_ci = b.code COLLATE utf8mb4_unicode_ci";
    } else {
        $branchNameExpr = "IF(o.branch_code = '' OR o.branch_code IS NULL, 'No Branch', o.branch_code)";
        $joins = "";
    }

    $sql = "SELECT
                COALESCE(NULLIF(o.branch_code, ''), 'NO_BRANCH') AS branch_code,
                $branchNameExpr AS branch_name,
                COUNT(DISTINCT o.SALNUM) AS total_orders,
                SUM(o.QTY) AS total_qty,
                COUNT(DISTINCT o.BARCODE) AS unique_products,
                COUNT(DISTINCT o.NAME) AS staff_count
            FROM `orderlist` o
            $joins
            $where
            GROUP BY branch_code, branch_name
            ORDER BY total_qty DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Query error: ' . $connect->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

// ── Sales by Staff Detailed Report (grouped by branch, itemized orders) ──
} elseif ($action === 'sales_by_staff_detailed') {
    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

    $hasBranch = $connect->query("SHOW TABLES LIKE 'branch'")->num_rows > 0;

    if ($hasBranch) {
        $branchNameExpr = "COALESCE(b.name, IF(o.branch_code = '' OR o.branch_code IS NULL, 'No Branch', o.branch_code))";
        $joins = "LEFT JOIN `branch` b ON o.branch_code COLLATE utf8mb4_unicode_ci = b.code COLLATE utf8mb4_unicode_ci";
    } else {
        $branchNameExpr = "IF(o.branch_code = '' OR o.branch_code IS NULL, 'No Branch', o.branch_code)";
        $joins = "";
    }

    $sql = "SELECT
                COALESCE(NULLIF(o.branch_code, ''), 'NO_BRANCH') AS branch_code,
                $branchNameExpr AS branch_name,
                o.NAME AS staff_name,
                o.SALNUM AS order_no,
                o.SDATE AS sale_date,
                o.BARCODE AS barcode,
                o.PDESC AS product_desc,
                o.QTY AS qty
            FROM `orderlist` o
            $joins
            $where
            ORDER BY branch_name ASC, o.NAME ASC, o.SALNUM ASC, o.PDESC ASC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Query error: ' . $connect->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

// ── Sales by Branch Detailed Report (grouped by branch, itemized orders) ──
} elseif ($action === 'sales_by_branch_detailed') {
    $search = trim($_POST['search'] ?? '');
    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

    if ($search !== '') {
        $where .= " AND (o.BARCODE LIKE ? OR o.PDESC LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $branches = $_POST['branches'] ?? [];
    if (!empty($branches) && is_array($branches)) {
        $placeholders = implode(',', array_fill(0, count($branches), '?'));
        $where .= " AND o.branch_code IN ($placeholders)";
        foreach ($branches as $bc) {
            $params[] = $bc;
            $types .= "s";
        }
    }

    $hasBranch = $connect->query("SHOW TABLES LIKE 'branch'")->num_rows > 0;

    if ($hasBranch) {
        $branchNameExpr = "COALESCE(b.name, IF(o.branch_code = '' OR o.branch_code IS NULL, 'No Branch', o.branch_code))";
        $joins = "LEFT JOIN `branch` b ON o.branch_code COLLATE utf8mb4_unicode_ci = b.code COLLATE utf8mb4_unicode_ci";
    } else {
        $branchNameExpr = "IF(o.branch_code = '' OR o.branch_code IS NULL, 'No Branch', o.branch_code)";
        $joins = "";
    }

    $sql = "SELECT
                COALESCE(NULLIF(o.branch_code, ''), 'NO_BRANCH') AS branch_code,
                $branchNameExpr AS branch_name,
                o.SALNUM AS order_no,
                o.SDATE AS sale_date,
                o.NAME AS staff_name,
                o.BARCODE AS barcode,
                o.PDESC AS product_desc,
                o.QTY AS qty
            FROM `orderlist` o
            $joins
            $where
            ORDER BY branch_name ASC, o.SALNUM ASC, o.PDESC ASC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Query error: ' . $connect->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

// ── Stock Take Report ──
} elseif ($action === 'stock_take_report') {
    $category = trim($_POST['category'] ?? '');
    $subCategory = trim($_POST['sub_category'] ?? '');
    $statusFilter = trim($_POST['status_filter'] ?? '');
    $search = trim($_POST['search'] ?? '');

    $collate = "COLLATE utf8mb4_unicode_ci";

    // Get the latest SUBMITTED or APPROVED stock take per product
    $sql = "SELECT
                p.barcode,
                p.name AS product_name,
                CONCAT_WS(' > ', NULLIF(p.cat, ''), NULLIF(p.sub_cat, '')) AS category,
                COALESCE(p.qoh, 0) AS current_qoh,
                latest_st.last_stock_take,
                latest_st.counted_qty,
                latest_st.variance,
                latest_st.session_code,
                latest_st.session_status,
                latest_st.counted_by,
                CASE
                    WHEN latest_st.last_stock_take IS NULL THEN NULL
                    ELSE DATEDIFF(CURDATE(), latest_st.last_stock_take)
                END AS days_ago
            FROM `PRODUCTS` p
            LEFT JOIN (
                SELECT
                    sti.barcode,
                    sti.counted_qty,
                    sti.variance,
                    sti.counted_by,
                    COALESCE(sti.counted_at, st.created_at) AS last_stock_take,
                    st.session_code,
                    st.status AS session_status,
                    ROW_NUMBER() OVER (PARTITION BY sti.barcode ORDER BY COALESCE(sti.counted_at, st.created_at) DESC) AS rn
                FROM `stock_take_item` sti
                INNER JOIN `stock_take` st ON st.id = sti.stock_take_id
                WHERE st.status IN ('DRAFT', 'SUBMITTED', 'APPROVED')
                  AND (sti.status = 'COUNTED' OR sti.counted_at IS NOT NULL)
            ) latest_st ON latest_st.barcode $collate = p.barcode $collate AND latest_st.rn = 1
            WHERE p.checked = 'Y'";

    $params = [];
    $types = '';

    if ($category !== '') {
        $sql .= " AND p.cat = ?";
        $params[] = $category;
        $types .= 's';
    }
    if ($subCategory !== '') {
        $sql .= " AND p.sub_cat = ?";
        $params[] = $subCategory;
        $types .= 's';
    }
    if ($search !== '') {
        $sql .= " AND (p.barcode LIKE ? OR p.name LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }
    if ($statusFilter === 'never') {
        $sql .= " AND latest_st.last_stock_take IS NULL";
    } elseif ($statusFilter === 'taken') {
        $sql .= " AND latest_st.last_stock_take IS NOT NULL";
    } elseif ($statusFilter === 'overdue') {
        $sql .= " AND (latest_st.last_stock_take IS NULL OR DATEDIFF(CURDATE(), latest_st.last_stock_take) > 30)";
    }

    $sql .= " ORDER BY latest_st.last_stock_take IS NULL DESC, latest_st.last_stock_take ASC, p.name ASC";

    $rows = [];
    $totalCount = 0;
    $neverCount = 0;
    $takenCount = 0;
    $overdueCount = 0;

    if (!empty($types)) {
        $stmt = $connect->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $connect->query($sql);
    }

    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
            $totalCount++;
            if ($r['last_stock_take'] === null) {
                $neverCount++;
            } else {
                $takenCount++;
                if (intval($r['days_ago']) > 30) {
                    $overdueCount++;
                }
            }
        }
    }
    if (isset($stmt)) { $stmt->close(); }

    echo json_encode([
        'rows' => $rows,
        'summary' => [
            'total' => $totalCount,
            'never' => $neverCount,
            'taken' => $takenCount,
            'overdue' => $overdueCount
        ]
    ]);

// ── Stock Take Sub-Categories Helper ──
} elseif ($action === 'stock_take_subcategories') {
    $category = trim($_POST['category'] ?? '');
    $subCats = [];
    if ($category !== '') {
        $stmt = $connect->prepare("SELECT DISTINCT `sub_cat` FROM `PRODUCTS` WHERE `checked` = 'Y' AND `cat` = ? AND `sub_cat` != '' ORDER BY `sub_cat` ASC");
        $stmt->bind_param('s', $category);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($r = $result->fetch_assoc()) { $subCats[] = $r['sub_cat']; }
        $stmt->close();
    }
    echo json_encode(['sub_categories' => $subCats]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
