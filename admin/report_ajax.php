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
    // Opening = QOH + total OUT in period - total IN in period (reverse calculation)
    // In = positive qty with STATUS = DONE
    // Out = negative qty or sales
    $sql = "SELECT
                o.BARCODE,
                o.PDESC AS description,
                COALESCE(p.qoh, 0) AS current_qoh,
                SUM(CASE WHEN o.QTY > 0 AND o.STATUS = 'DONE' THEN o.QTY ELSE 0 END) AS qty_out,
                0 AS qty_in,
                COALESCE(adj.adj_in, 0) AS adj_in,
                COALESCE(adj.adj_out, 0) AS adj_out
            FROM `orderlist` o
            LEFT JOIN `PRODUCTS` p ON o.BARCODE = p.barcode
            LEFT JOIN (
                SELECT BARCODE,
                    SUM(CASE WHEN QTYADJ > 0 THEN QTYADJ ELSE 0 END) AS adj_in,
                    SUM(CASE WHEN QTYADJ < 0 THEN ABS(QTYADJ) ELSE 0 END) AS adj_out
                FROM `stockadj`
                WHERE SDATE >= ? AND SDATE <= ?
                GROUP BY BARCODE
            ) adj ON o.BARCODE = adj.BARCODE
            $where
            GROUP BY o.BARCODE, o.PDESC
            ORDER BY o.PDESC ASC
            LIMIT 1000";

    $allParams = [$startDate, $endDate, ...$params];
    $allTypes = "ss" . $types;

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

    $sql = "SELECT
                COALESCE(NULLIF(o.OUTLET, ''), 'MAIN') AS branch_code,
                COALESCE(ot.PDESC, b.name, COALESCE(NULLIF(o.OUTLET, ''), 'MAIN')) AS branch_name,
                COUNT(DISTINCT o.SALNUM) AS total_orders,
                SUM(o.QTY) AS total_qty,
                COUNT(DISTINCT o.BARCODE) AS unique_products,
                COUNT(DISTINCT o.NAME) AS staff_count
            FROM `orderlist` o
            LEFT JOIN `outlet` ot ON o.OUTLET = ot.CODE
            LEFT JOIN `branch` b ON o.OUTLET = b.code
            $where
            GROUP BY branch_code
            ORDER BY total_qty DESC";

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
?>
