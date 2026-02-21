<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

function generateSessionCode($connect) {
    $prefix = 'ST-' . date('Ymd') . '-';
    $result = $connect->query("SELECT `session_code` FROM `stock_take` WHERE `session_code` LIKE '" . $connect->real_escape_string($prefix) . "%' ORDER BY `session_code` DESC LIMIT 1");
    $next = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $num = intval(substr($row['session_code'], -3));
        $next = $num + 1;
    }
    return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
}

if ($action === 'create') {
    $desc = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? 'FULL');
    $filterCat = trim($_POST['filter_cat'] ?? '');
    $createdBy = $_SESSION['admin_name'] ?? 'Admin';

    if (!in_array($type, ['FULL', 'PARTIAL'])) $type = 'FULL';

    $sessionCode = generateSessionCode($connect);
    $filterCatVal = ($type === 'PARTIAL' && $filterCat !== '') ? $filterCat : null;

    $connect->begin_transaction();

    try {
        $stmt = $connect->prepare("INSERT INTO `stock_take` (`session_code`,`description`,`type`,`filter_cat`,`status`,`created_by`) VALUES (?,?,?,?,'OPEN',?)");
        $stmt->bind_param("sssss", $sessionCode, $desc, $type, $filterCatVal, $createdBy);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create session: ' . $connect->error);
        }
        $sessionId = $connect->insert_id;
        $stmt->close();

        // Populate stock_take_item with current products
        $where = "WHERE `checked` = 'Y'";
        if ($filterCatVal) {
            $where .= " AND `cat` = '" . $connect->real_escape_string($filterCatVal) . "'";
        }

        $productResult = $connect->query("SELECT `barcode`, `name`, COALESCE(`qoh`, 0) AS qoh FROM `PRODUCTS` $where ORDER BY `barcode` ASC");
        if ($productResult && $productResult->num_rows > 0) {
            $itemStmt = $connect->prepare("INSERT INTO `stock_take_item` (`stock_take_id`,`barcode`,`product_desc`,`system_qty`) VALUES (?,?,?,?)");
            while ($p = $productResult->fetch_assoc()) {
                $barcode = $p['barcode'];
                $name = $p['name'];
                $sysQty = floatval($p['qoh']);
                $itemStmt->bind_param("issd", $sessionId, $barcode, $name, $sysQty);
                $itemStmt->execute();
            }
            $itemStmt->close();
        }

        $connect->commit();
        echo json_encode(['success' => 'Stock take session ' . $sessionCode . ' created.', 'session_id' => $sessionId]);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'save_counts') {
    $sessionId = intval($_POST['session_id'] ?? 0);
    $counts = json_decode($_POST['counts'] ?? '[]', true);

    if ($sessionId <= 0 || empty($counts)) {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    // Verify session is OPEN or IN_PROGRESS
    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    if (!in_array($row['status'], ['OPEN', 'IN_PROGRESS'])) {
        echo json_encode(['error' => 'Session is completed.']);
        exit;
    }

    $countedBy = $_SESSION['admin_name'] ?? 'Admin';

    $stmt = $connect->prepare("UPDATE `stock_take_item` SET `counted_qty`=?, `variance`=?, `remark`=?, `counted_by`=?, `counted_at`=NOW() WHERE `id`=? AND `stock_take_id`=?");

    $updated = 0;
    foreach ($counts as $c) {
        $itemId = intval($c['id'] ?? 0);
        $countedQty = ($c['counted_qty'] !== '' && $c['counted_qty'] !== null) ? floatval($c['counted_qty']) : null;
        $remark = trim($c['remark'] ?? '');

        if ($itemId <= 0) continue;

        // Get system_qty to calculate variance
        $sysResult = $connect->query("SELECT `system_qty` FROM `stock_take_item` WHERE `id` = $itemId LIMIT 1");
        if ($sysResult && $sysRow = $sysResult->fetch_assoc()) {
            $systemQty = floatval($sysRow['system_qty']);
            $variance = ($countedQty !== null) ? ($countedQty - $systemQty) : null;

            $stmt->bind_param("ddssi" . "i", $countedQty, $variance, $remark, $countedBy, $itemId, $sessionId);
            $stmt->execute();
            $updated++;
        }
    }
    $stmt->close();

    // Update session status to IN_PROGRESS if it was OPEN
    if ($row['status'] === 'OPEN') {
        $connect->query("UPDATE `stock_take` SET `status` = 'IN_PROGRESS' WHERE `id` = $sessionId AND `status` = 'OPEN'");
    }

    echo json_encode(['success' => $updated . ' items updated.']);

} elseif ($action === 'apply_adjustments') {
    $sessionId = intval($_POST['session_id'] ?? 0);

    if ($sessionId <= 0) {
        echo json_encode(['error' => 'Invalid session.']);
        exit;
    }

    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    if ($row['status'] !== 'IN_PROGRESS') {
        echo json_encode(['error' => 'Session must be IN PROGRESS to apply adjustments.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        $adminUser = $_SESSION['admin_name'] ?? 'Admin';
        $salnum = 'STADJ' . date('YmdHis');
        $curDate = date('Y-m-d');
        $curTime = date('H:i:s');

        // Get items with variance that haven't been adjusted yet
        $itemsResult = $connect->query("SELECT * FROM `stock_take_item` WHERE `stock_take_id` = $sessionId AND `counted_qty` IS NOT NULL AND `variance` != 0 AND `adj_applied` = 0");

        $adjCount = 0;
        if ($itemsResult) {
            $updateQohStmt = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) + ? WHERE `barcode` = ?");
            $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`IP`,`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`MNO`,`BARCODE`,`PDESC`,`LOOSE`,`PGROUP`,`PRODTYPE`,`QTYADJ`,`SERIALNUMBER`,`REMARK`,`LOSS_REASON`) VALUES ('','STOCKTAKE',?,?,?,?,?,'',?,?,0,'','',?,'',?,'ADJUSTMENT')");

            while ($item = $itemsResult->fetch_assoc()) {
                $variance = floatval($item['variance']);
                $barcode = $item['barcode'];
                $desc = $item['product_desc'];
                $rmk = 'Stock Take Adj: ' . $item['stock_take_id'] . ' | Sys:' . $item['system_qty'] . ' Count:' . $item['counted_qty'];

                // Update QOH (variance = counted - system, so adding variance adjusts to counted qty)
                $updateQohStmt->bind_param("ds", $variance, $barcode);
                $updateQohStmt->execute();

                // Insert stockadj record
                $adjStmt->bind_param("sssssssds", $adminUser, $adminUser, $curDate, $curTime, $salnum, $barcode, $desc, $variance, $rmk);
                $adjStmt->execute();

                // Mark as applied
                $connect->query("UPDATE `stock_take_item` SET `adj_applied` = 1 WHERE `id` = " . intval($item['id']));
                $adjCount++;
            }

            $updateQohStmt->close();
            $adjStmt->close();
        }

        $connect->commit();
        echo json_encode(['success' => $adjCount . ' adjustments applied to stock quantities.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'complete') {
    $sessionId = intval($_POST['session_id'] ?? 0);
    $completedBy = $_SESSION['admin_name'] ?? 'Admin';

    $stmt = $connect->prepare("UPDATE `stock_take` SET `status`='COMPLETED', `completed_by`=?, `completed_at`=NOW() WHERE `id`=? AND `status`='IN_PROGRESS'");
    $stmt->bind_param("si", $completedBy, $sessionId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => 'Stock take session completed.']);
    } else {
        echo json_encode(['error' => 'Session must be IN PROGRESS to complete.']);
    }
    $stmt->close();

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
