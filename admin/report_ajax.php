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

    $sql = "SELECT
                combined.BARCODE,
                combined.description,
                COALESCE(p.qoh, 0) AS current_qoh,
                COALESCE(orders.qty_out, 0) AS qty_out,
                COALESCE(adj.adj_in, 0) AS adj_in,
                COALESCE(adj.adj_out, 0) AS adj_out
            FROM (
                SELECT BARCODE, PDESC AS description FROM `orderlist`
                WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT'
                " . ($search !== '' ? "AND (BARCODE LIKE ? OR PDESC LIKE ?)" : "") . "
                GROUP BY BARCODE, PDESC
                UNION
                SELECT sa.BARCODE, COALESCE(p2.pdesc, sa.BARCODE) AS description FROM `stockadj` sa
                LEFT JOIN `PRODUCTS` p2 ON sa.BARCODE = p2.barcode
                WHERE sa.SDATE >= ? AND sa.SDATE <= ?
                $adjSearchWhere
                GROUP BY sa.BARCODE
            ) combined
            LEFT JOIN `PRODUCTS` p ON combined.BARCODE = p.barcode
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTY > 0 AND STATUS = 'DONE' THEN QTY ELSE 0 END) AS qty_out
                FROM `orderlist`
                WHERE SDATE >= ? AND SDATE <= ? AND BARCODE <> 'PT'
                GROUP BY BARCODE
            ) orders ON combined.BARCODE = orders.BARCODE
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTYADJ > 0 THEN QTYADJ ELSE 0 END) AS adj_in,
                    SUM(CASE WHEN QTYADJ < 0 THEN ABS(QTYADJ) ELSE 0 END) AS adj_out
                FROM `stockadj`
                WHERE SDATE >= ? AND SDATE <= ?
                GROUP BY BARCODE
            ) adj ON combined.BARCODE = adj.BARCODE
            ORDER BY combined.description ASC";

    // Build params: combined(orderlist dates + search, stockadj dates + search), orders dates, adj dates
    $allParams = [$startDate, $endDate];
    $allTypes = "ss";
    if ($search !== '') {
        $allParams[] = $searchParam;
        $allParams[] = $searchParam;
        $allTypes .= "ss";
    }
    $allParams[] = $startDate;
    $allParams[] = $endDate;
    $allTypes .= "ss";
    if ($search !== '') {
        $allParams[] = $searchParam;
        $allParams[] = $searchParam;
        $allTypes .= "ss";
    }
    $allParams[] = $startDate;
    $allParams[] = $endDate;
    $allTypes .= "ss";
    $allParams[] = $startDate;
    $allParams[] = $endDate;
    $allTypes .= "ss";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $out = floatval($r['qty_out']);
        $adjIn = floatval($r['adj_in']);
        $adjOut = floatval($r['adj_out']);
        $currentQoh = floatval($r['current_qoh']);
        // Opening = current_qoh + out - in + adj_out - adj_in
        $opening = $currentQoh + $out - $adjIn + $adjOut;
        $closing = $opening - $out + $adjIn - $adjOut;

        $rows[] = [
            'description' => $r['description'] ?: $r['BARCODE'],
            'opening' => $opening,
            'in' => $adjIn,
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

    // Check which lookup tables exist to avoid query failure
    $hasOutlet = $connect->query("SHOW TABLES LIKE 'outlet'")->num_rows > 0;
    $hasBranch = $connect->query("SHOW TABLES LIKE 'branch'")->num_rows > 0;

    $branchCodeExpr = "COALESCE(NULLIF(o.OUTLET, ''), 'NO_BRANCH')";
    $joins = "";
    if ($hasOutlet && $hasBranch) {
        $branchNameExpr = "COALESCE(ot.PDESC, b.name, IF(o.OUTLET = '' OR o.OUTLET IS NULL, 'No Branch', o.OUTLET))";
        $joins = "LEFT JOIN `outlet` ot ON o.OUTLET COLLATE utf8mb4_unicode_ci = ot.CODE COLLATE utf8mb4_unicode_ci LEFT JOIN `branch` b ON o.OUTLET COLLATE utf8mb4_unicode_ci = b.code COLLATE utf8mb4_unicode_ci";
    } elseif ($hasOutlet) {
        $branchNameExpr = "COALESCE(ot.PDESC, IF(o.OUTLET = '' OR o.OUTLET IS NULL, 'No Branch', o.OUTLET))";
        $joins = "LEFT JOIN `outlet` ot ON o.OUTLET COLLATE utf8mb4_unicode_ci = ot.CODE COLLATE utf8mb4_unicode_ci";
    } elseif ($hasBranch) {
        $branchNameExpr = "COALESCE(b.name, IF(o.OUTLET = '' OR o.OUTLET IS NULL, 'No Branch', o.OUTLET))";
        $joins = "LEFT JOIN `branch` b ON o.OUTLET COLLATE utf8mb4_unicode_ci = b.code COLLATE utf8mb4_unicode_ci";
    } else {
        $branchNameExpr = "IF(o.OUTLET = '' OR o.OUTLET IS NULL, 'No Branch', o.OUTLET)";
    }

    $sql = "SELECT
                $branchCodeExpr AS branch_code,
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
