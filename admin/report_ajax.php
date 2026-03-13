<?php
session_start();
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

    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

    if ($search !== '') {
        $where .= " AND (o.BARCODE LIKE ? OR o.PDESC LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    // Get stock movements grouped by product
    // Uses UNION of orderlist and stockadj to capture ALL products with movement
    // Opening = current_qoh + out - adj_in + adj_out (reverse from current)
    // Closing = opening - out + adj_in - adj_out

    // Build search condition for stockadj subquery too
    $adjSearchWhere = "";
    $adjSearchParams = [];
    $adjSearchTypes = "";
    if ($search !== '') {
        $adjSearchWhere = " AND (sa.BARCODE LIKE ? OR sa.BARCODE LIKE ?)";
        $adjSearchParams = [$searchParam, $searchParam];
        $adjSearchTypes = "ss";
    }

    $collate = "COLLATE utf8mb4_unicode_ci";

    // Check if grn/grn_item tables exist
    $hasGrn = $connect->query("SHOW TABLES LIKE 'grn'")->num_rows > 0
           && $connect->query("SHOW TABLES LIKE 'grn_item'")->num_rows > 0;

    // Build GRN subqueries for the UNION and the qty_in sum
    $grnUnion = "";
    $grnJoin = "";
    $grnSelect = "0 AS qty_grn_in";
    $grnUnionParams = [];
    $grnUnionTypes = "";
    $grnJoinParams = [];
    $grnJoinTypes = "";

    if ($hasGrn) {
        $grnSearchWhere = "";
        if ($search !== '') {
            $grnSearchWhere = "AND (gi.barcode LIKE ? OR gi.product_desc LIKE ?)";
        }
        $grnUnion = "UNION
                SELECT gi.barcode $collate AS BARCODE, COALESCE(p3.name, gi.product_desc, gi.barcode) AS description
                FROM `grn_item` gi
                LEFT JOIN `grn` g ON gi.grn_id = g.id
                LEFT JOIN `PRODUCTS` p3 ON gi.barcode $collate = p3.barcode $collate
                WHERE g.receive_date >= ? AND g.receive_date <= ?
                $grnSearchWhere
                GROUP BY gi.barcode";
        $grnUnionParams = [$startDate, $endDate];
        $grnUnionTypes = "ss";
        if ($search !== '') {
            $grnUnionParams[] = $searchParam;
            $grnUnionParams[] = $searchParam;
            $grnUnionTypes .= "ss";
        }

        $grnSelect = "COALESCE(grn_in.qty_grn_in, 0) AS qty_grn_in";
        $grnJoin = "LEFT JOIN (
                SELECT gi.barcode AS BARCODE,
                    SUM(gi.qty_received) AS qty_grn_in
                FROM `grn_item` gi
                LEFT JOIN `grn` g ON gi.grn_id = g.id
                WHERE g.receive_date >= ? AND g.receive_date <= ?
                GROUP BY gi.barcode
            ) grn_in ON combined.BARCODE $collate = grn_in.BARCODE $collate";
        $grnJoinParams = [$startDate, $endDate];
        $grnJoinTypes = "ss";
    }

    $sql = "SELECT
                combined.BARCODE,
                combined.description,
                COALESCE(p.qoh, 0) AS current_qoh,
                COALESCE(orders.qty_out, 0) AS qty_out,
                COALESCE(orders.qty_stockin, 0) AS qty_stockin,
                COALESCE(adj.adj_in, 0) AS adj_in,
                COALESCE(adj.adj_out, 0) AS adj_out,
                $grnSelect
            FROM (
                SELECT BARCODE $collate AS BARCODE, PDESC AS description FROM `orderlist`
                WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT'
                " . ($search !== '' ? "AND (BARCODE LIKE ? OR PDESC LIKE ?)" : "") . "
                GROUP BY BARCODE, PDESC
                UNION
                SELECT sa.BARCODE $collate AS BARCODE, COALESCE(p2.name, sa.BARCODE) AS description FROM `stockadj` sa
                LEFT JOIN `PRODUCTS` p2 ON sa.BARCODE $collate = p2.barcode $collate
                WHERE sa.SDATE >= ? AND sa.SDATE <= ?
                $adjSearchWhere
                GROUP BY sa.BARCODE
                $grnUnion
            ) combined
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
            ) adj ON combined.BARCODE $collate = adj.BARCODE $collate
            $grnJoin
            ORDER BY combined.description ASC";

    // Build params: combined(orderlist + stockadj + grn unions), then orders, adj, grn joins
    $allParams = [$startDate, $endDate];
    $allTypes = "ss";
    if ($search !== '') {
        $allParams[] = $searchParam;
        $allParams[] = $searchParam;
        $allTypes .= "ss";
    }
    // stockadj union dates + search
    $allParams[] = $startDate;
    $allParams[] = $endDate;
    $allTypes .= "ss";
    if ($search !== '') {
        $allParams[] = $searchParam;
        $allParams[] = $searchParam;
        $allTypes .= "ss";
    }
    // grn union params
    foreach ($grnUnionParams as $p) { $allParams[] = $p; }
    $allTypes .= $grnUnionTypes;
    // orders subquery dates
    $allParams[] = $startDate;
    $allParams[] = $endDate;
    $allTypes .= "ss";
    // adj subquery dates
    $allParams[] = $startDate;
    $allParams[] = $endDate;
    $allTypes .= "ss";
    // grn join params
    foreach ($grnJoinParams as $p) { $allParams[] = $p; }
    $allTypes .= $grnJoinTypes;

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'Query error: ' . $connect->error]);
        exit;
    }
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $out = floatval($r['qty_out']);
        $stockIn = floatval($r['qty_stockin']);
        $grnIn = floatval($r['qty_grn_in']);
        $adjIn = floatval($r['adj_in']);
        $adjOut = floatval($r['adj_out']);
        $currentQoh = floatval($r['current_qoh']);
        $totalIn = $stockIn + $grnIn + $adjIn;
        // Opening = current_qoh + out - totalIn + adj_out
        $opening = $currentQoh + $out - $totalIn + $adjOut;
        $closing = $opening + $totalIn - $out - $adjOut;

        $rows[] = [
            'description' => $r['description'] ?: $r['BARCODE'],
            'opening' => $opening,
            'in' => $totalIn,
            'out' => $out,
            'adj' => $adjIn - $adjOut,
            'closing' => $closing
        ];
    }
    $stmt->close();
    echo json_encode(['rows' => $rows]);

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
    $where = "WHERE o.SDATE >= ? AND o.SDATE <= ? AND o.STATUS = 'DONE' AND o.BARCODE <> 'PT'";
    $params = [$startDate, $endDate];
    $types = "ss";

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

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
