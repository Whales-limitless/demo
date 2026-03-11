<?php
session_start();
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

    // Rebuild orderlist2 summary
    $connect->query("TRUNCATE TABLE `orderlist2`");
    $connect->query("INSERT INTO `orderlist2` (SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUMQTY,PURCHASEDATE) SELECT SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUM(QTY) AS SUMQTY,PURCHASEDATE FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND BARCODE <> 'PT' GROUP BY SALNUM,ACCODE ORDER BY SALNUM DESC");
    $connect->query("UPDATE orderlist2 AS b INNER JOIN MEMBER AS g ON b.ACCODE = g.ACCODE SET b.HP = g.HP");

    // Fetch orders
    $orders = [];
    $orderResult = $connect->query("SELECT * FROM `orderlist2` ORDER BY SALNUM DESC");
    if ($orderResult) {
        while ($r = $orderResult->fetch_assoc()) {
            $orders[] = $r;
        }
    }

    // Count new (unacknowledged) orders
    $newCount = 0;
    $q = $connect->query("SELECT COUNT(DISTINCT SALNUM) as cnt FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0'");
    if ($q && $row = $q->fetch_assoc()) {
        $newCount = (int)$row['cnt'];
    }

    echo json_encode([
        'orders' => $orders,
        'new_count' => $newCount,
        'total' => count($orders),
        'ts' => time()
    ]);

// ===================== ACKNOWLEDGE SOUND =====================
} elseif ($action === "noted") {
    header('Content-Type: application/json; charset=utf-8');
    $connect->query("UPDATE `orderlist` SET SOUND = '1' WHERE SOUND = '0'");
    echo json_encode(['success' => true]);
}
?>
