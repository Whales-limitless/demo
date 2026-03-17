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
                WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT'";
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
                " . ($hasGrn ? "COALESCE(grn_in.qty_grn_in, 0)" : "0") . " AS qty_grn_in
            FROM ($unionSql) combined
            LEFT JOIN `PRODUCTS` p ON combined.BARCODE $collate = p.barcode $collate
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTY > 0 AND STATUS = 'DONE' AND (PTYPE IS NULL OR PTYPE <> 'STOCKIN') THEN QTY ELSE 0 END) AS qty_out,
                    SUM(CASE WHEN PTYPE = 'STOCKIN' THEN QTY ELSE 0 END) AS qty_stockin
                FROM `orderlist`
                WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT'
                GROUP BY BARCODE
            ) orders ON combined.BARCODE $collate = orders.BARCODE $collate
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTYADJ > 0 THEN QTYADJ ELSE 0 END) AS adj_in,
                    SUM(CASE WHEN QTYADJ < 0 THEN ABS(QTYADJ) ELSE 0 END) AS adj_out
                FROM `stockadj`
                WHERE SDATE >= ? AND SDATE <= ?
                GROUP BY BARCODE
            ) adj ON combined.BARCODE $collate = adj.BARCODE $collate";

    $allParams = $unionParams;
    $allTypes = $unionTypes;
    // orders subquery
    $allParams[] = $startDate; $allParams[] = $endDate; $allTypes .= "ss";
    // adj subquery
    $allParams[] = $startDate; $allParams[] = $endDate; $allTypes .= "ss";

    if ($hasGrn) {
        $sql .= " LEFT JOIN (
                SELECT gi.barcode AS BARCODE, SUM(gi.qty_received) AS qty_grn_in
                FROM `grn_item` gi LEFT JOIN `grn` g ON gi.grn_id = g.id
                WHERE g.receive_date >= ? AND g.receive_date <= ?
                GROUP BY gi.barcode
            ) grn_in ON combined.BARCODE $collate = grn_in.BARCODE $collate";
        $allParams[] = $startDate; $allParams[] = $endDate; $allTypes .= "ss";
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
        $opening = $currentQoh + $out - $totalIn + $adjOut;
        $closing = $opening + $totalIn - $out - $adjOut;

        $productRows[$r['BARCODE']] = [
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
            WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT'
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

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
