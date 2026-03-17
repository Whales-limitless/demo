<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized";
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === "done") {
    $delid = $connect->real_escape_string($_POST['id'] ?? '');

    $sqlord = $connect->query("SELECT SDATE, ACCODE FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();

        // Check if any products in this order are in active stock take sessions
        $orderBarcodes = [];
        $barcodeQuery = $connect->query("SELECT DISTINCT BARCODE FROM `orderlist` WHERE SALNUM = '$delid' AND BARCODE <> 'PT' AND (PTYPE IS NULL OR PTYPE <> 'STOCKIN') AND QTY > 0");
        if ($barcodeQuery) {
            while ($br = $barcodeQuery->fetch_assoc()) {
                if (!empty(trim($br['BARCODE']))) {
                    $orderBarcodes[] = trim($br['BARCODE']);
                }
            }
        }

        if (!empty($orderBarcodes)) {
            $stPlaceholders = implode(',', array_fill(0, count($orderBarcodes), '?'));
            $stStmt = $connect->prepare("SELECT DISTINCT sti.`barcode`, p.`name`, st.`session_code`
                FROM `stock_take_item` sti
                INNER JOIN `stock_take` st ON st.`id` = sti.`stock_take_id` AND st.`status` IN ('DRAFT', 'SUBMITTED')
                LEFT JOIN `PRODUCTS` p ON p.`barcode` = sti.`barcode`
                WHERE sti.`barcode` IN ($stPlaceholders)");
            $stTypes = str_repeat('s', count($orderBarcodes));
            $stValues = array_values($orderBarcodes);
            $stStmt->bind_param($stTypes, ...$stValues);
            $stStmt->execute();
            $stResult = $stStmt->get_result();
            $blockedItems = [];
            while ($r = $stResult->fetch_assoc()) {
                $blockedItems[] = ($r['name'] ?? $r['barcode']) . ' (' . $r['session_code'] . ')';
            }
            $stStmt->close();

            if (!empty($blockedItems)) {
                echo "STOCK_TAKE_BLOCKED:" . json_encode($blockedItems);
                exit;
            }
        }

        $status = 'DONE';

        $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '$status' WHERE SALNUM = '$delid'");
        $affected1 = $connect->affected_rows;

        if ($result1 && $affected1 > 0) {
            // Reduce QOH for each item in this order (only PURCHASE orders, not STOCKIN)
            $itemsResult = $connect->query("SELECT BARCODE, QTY, PTYPE FROM `orderlist` WHERE SALNUM = '$delid' AND BARCODE <> 'PT'");
            if ($itemsResult) {
                while ($item = $itemsResult->fetch_assoc()) {
                    if (($item['PTYPE'] ?? '') !== 'STOCKIN' && $item['QTY'] > 0) {
                        $itemBarcode = $connect->real_escape_string($item['BARCODE']);
                        $itemQty = intval($item['QTY']);
                        $connect->query("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) - $itemQty WHERE `barcode` = '$itemBarcode'");
                    }
                }
            }
            echo "Saved.";
        } elseif ($result1 && $affected1 == 0) {
            echo "Error: No rows updated. Order may already be DONE.";
        } else {
            echo "Error: " . $connect->error;
        }
    } else {
        echo "Error: Order not found.";
    }

} elseif ($action === "delete") {
    $delid = $connect->real_escape_string($_POST['id'] ?? '');

    $sqlord = $connect->query("SELECT SDATE, ACCODE FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();
        $status = 'DELETED';

        $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '$status' WHERE SALNUM = '$delid'");
        $affected1 = $connect->affected_rows;

        if ($result1 && $affected1 > 0) {
            echo "Deleted.";
        } elseif ($result1 && $affected1 == 0) {
            echo "Error: No rows updated. Order may already be DELETED.";
        } else {
            echo "Error: " . $connect->error;
        }
    } else {
        echo "Error: Order not found.";
    }

} elseif ($action === "detail") {
    $delid = $connect->real_escape_string($_POST['id'] ?? '');

    $sqlord = $connect->query("SELECT ADMINRMK, TRANSNO FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();
        echo ($row['ADMINRMK'] ?? '') . "|" . ($row['TRANSNO'] ?? '');
    }

} elseif ($action === "success") {
    $remark = $connect->real_escape_string($_POST['remark'] ?? '');
    $transno = $connect->real_escape_string($_POST['rowtransno'] ?? '');
    $delid = $connect->real_escape_string($_POST['pid'] ?? '');

    $sqlord = $connect->query("SELECT SDATE, ACCODE FROM `orderlist` WHERE SALNUM = '$delid' LIMIT 1");

    if ($sqlord && $sqlord->num_rows > 0) {
        $row = $sqlord->fetch_assoc();
        $status = 'PAYMENT';

        $result1 = $connect->query("UPDATE `orderlist` SET STATUS = '$status', TRANSNO = '$transno', ADMINRMK = '$remark' WHERE SALNUM = '$delid'");
        $affected1 = $connect->affected_rows;

        if ($result1 && $affected1 > 0) {
            echo "Saved.";
        } elseif ($result1 && $affected1 == 0) {
            echo "Error: No rows updated.";
        } else {
            echo "Error: " . $connect->error;
        }
    } else {
        echo "Error: Order not found.";
    }

// ===================== EDIT ORDER (Purchase Date + Admin Remark) =====================
} elseif ($action === "edit_order") {
    $salnum = $connect->real_escape_string($_POST['salnum'] ?? '');
    $adminrmk = $connect->real_escape_string($_POST['adminrmk'] ?? '');
    $purchasedate = $_POST['purchasedate'] ?? '';

    // Validate date format if provided
    $pdateVal = 'NULL';
    if (!empty($purchasedate)) {
        $dt = DateTime::createFromFormat('Y-m-d', $purchasedate);
        if ($dt && $dt->format('Y-m-d') === $purchasedate) {
            $pdateVal = "'" . $connect->real_escape_string($purchasedate) . "'";
        }
    }

    $sqlord = $connect->query("SELECT ID FROM `orderlist` WHERE SALNUM = '$salnum' LIMIT 1");
    if ($sqlord && $sqlord->num_rows > 0) {
        $result = $connect->query("UPDATE `orderlist` SET ADMINRMK = '$adminrmk', PURCHASEDATE = $pdateVal WHERE SALNUM = '$salnum'");
        if ($result) {
            echo "Saved.";
        } else {
            echo "Error: " . $connect->error;
        }
    } else {
        echo "Error: Order not found.";
    }

// ===================== LIVE POLL (AJAX) =====================
} elseif ($action === "poll") {
    header('Content-Type: application/json; charset=utf-8');

    // Build branch name lookup
    $branchNames = [];
    $brRes = $connect->query("SELECT code, name FROM `branch`");
    if ($brRes) { while ($br = $brRes->fetch_assoc()) $branchNames[$br['code']] = $br['name']; }

    // Build member HP lookup
    $memberHP = [];
    $mRes = $connect->query("SELECT ACCODE, HP FROM `MEMBER`");
    if ($mRes) { while ($m = $mRes->fetch_assoc()) $memberHP[$m['ACCODE']] = $m['HP']; }

    // Aggregate orders directly from orderlist - simple query, no JOINs
    $orders = [];
    $orderResult = $connect->query("SELECT SALNUM, ACCODE, MAX(NAME) AS NAME, MAX(ADMINRMK) AS ADMINRMK, MAX(TXTTO) AS TXTTO, MAX(SDATE) AS SDATE, MAX(TTIME) AS TTIME, SUM(QTY) AS SUMQTY, MAX(PURCHASEDATE) AS PURCHASEDATE, MAX(branch_code) AS branch_code, MAX(PTYPE) AS PTYPE FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND BARCODE <> 'PT' GROUP BY SALNUM, ACCODE ORDER BY SALNUM DESC");
    if ($orderResult) {
        while ($r = $orderResult->fetch_assoc()) {
            $bc = $r['branch_code'] ?? '';
            $r['branch_name'] = $branchNames[$bc] ?? $bc;
            $r['HP'] = $memberHP[$r['ACCODE'] ?? ''] ?? '';
            $orders[] = $r;
        }
    }

    // Count new (unacknowledged) orders
    $newCount = 0;
    $q = $connect->query("SELECT COUNT(DISTINCT SALNUM) as cnt FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0'");
    if ($q && $row = $q->fetch_assoc()) {
        $newCount = (int)$row['cnt'];
    }

    $response = [
        'orders' => $orders,
        'new_count' => $newCount,
        'total' => count($orders),
        'ts' => time()
    ];
    if (!$orderResult) {
        $response['sql_error'] = $connect->error;
    }
    echo json_encode($response);

// ===================== VIEW ORDER DETAIL (AJAX) =====================
} elseif ($action === "view_order") {
    header('Content-Type: application/json; charset=utf-8');
    $salnum = $connect->real_escape_string($_POST['salnum'] ?? '');

    // Update view status
    $connect->query("UPDATE `orderlist` SET view_status = '1' WHERE SALNUM = '$salnum'");

    // Fetch order header
    $getdata = $connect->query("SELECT * FROM `orderlist` WHERE SALNUM = '$salnum' LIMIT 1");
    if (!$getdata || $getdata->num_rows === 0) {
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    $row = $getdata->fetch_assoc();
    $raccode    = $row['ACCODE'] ?? '';
    $rowname    = $row['NAME'] ?? '';
    $rowoutlet  = $row['OUTLET'] ?? '';
    $rowdate    = $row['SDATE'] ?? '';
    $rowttime   = $row['TTIME'] ?? '';
    $rowto      = $row['TXTTO'] ?? '';
    $rowbranchcode = $row['branch_code'] ?? '';
    $rowstatus  = $row['STATUS'] ?? '';

    // Branch name
    $rowbranchname = '';
    if ($rowbranchcode !== '') {
        $br_q = $connect->query("SELECT `name` FROM `branch` WHERE `code` = '" . $connect->real_escape_string($rowbranchcode) . "' LIMIT 1");
        if ($br_q && $br_row = $br_q->fetch_assoc()) $rowbranchname = $br_row['name'];
        else $rowbranchname = $rowbranchcode;
    }

    // Merchant info
    $mer_name = $mer_addr = '';
    $get_merchant = $connect->query("SELECT * FROM outlet WHERE CODE = '" . $connect->real_escape_string($rowoutlet) . "'");
    if ($get_merchant && $m_row = $get_merchant->fetch_assoc()) {
        $mer_name = $m_row['PDESC'] ?? '';
        $mer_addr = $m_row['ADDRESS'] ?? '';
    }

    // Get order items with rack info
    $grouped = [];
    $item_query = $connect->query("SELECT * FROM `orderlist` WHERE SALNUM = '$salnum' AND PDESC <> 'USE POINTS'");
    if ($item_query) {
        while ($irow = $item_query->fetch_assoc()) {
            $barcode = $connect->real_escape_string($irow['BARCODE'] ?? '');
            $rack_code = '';
            $rack_q = $connect->query("SELECT r.`code` FROM `rack_product` rp JOIN `rack` r ON rp.`rack_id` = r.`id` WHERE rp.`barcode` = '$barcode' AND r.`status` = 'ACTIVE' ORDER BY r.`code` ASC LIMIT 1");
            if ($rack_q && $rr = $rack_q->fetch_assoc()) $rack_code = $rr['code'];
            $rack_remark = '';
            $remark_q = $connect->query("SELECT `rack` FROM `PRODUCTS` WHERE `barcode` = '$barcode' LIMIT 1");
            if ($remark_q && $rr2 = $remark_q->fetch_assoc()) $rack_remark = $rr2['rack'] ?? '';

            $rackKey = !empty($rack_code) ? $rack_code : 'Unassigned';
            $grouped[$rackKey][] = [
                'barcode' => $irow['BARCODE'] ?? '',
                'pdesc'   => $irow['PDESC'] ?? '',
                'qty'     => (float)($irow['QTY'] ?? 0),
                'rack_remark' => $rack_remark
            ];
        }
    }
    // Sort rack groups
    uksort($grouped, function($a, $b) {
        if ($a === 'Unassigned') return 1;
        if ($b === 'Unassigned') return -1;
        return strcmp($a, $b);
    });
    foreach ($grouped as &$items) {
        usort($items, function($a, $b) { return strcmp($a['rack_remark'] ?? '', $b['rack_remark'] ?? ''); });
    }
    unset($items);

    echo json_encode([
        'salnum' => $salnum,
        'name' => $rowname,
        'date' => $rowdate,
        'time' => $rowttime,
        'to' => $rowto,
        'branch' => $rowbranchname,
        'status' => $rowstatus,
        'merchant_name' => $mer_name,
        'merchant_addr' => $mer_addr,
        'grouped_items' => $grouped
    ]);

// ===================== ACKNOWLEDGE SOUND =====================
} elseif ($action === "noted") {
    header('Content-Type: application/json; charset=utf-8');
    $connect->query("UPDATE `orderlist` SET SOUND = '1' WHERE SOUND = '0'");
    echo json_encode(['success' => true]);
}
?>
