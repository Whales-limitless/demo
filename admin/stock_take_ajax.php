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

// Support both form-encoded and JSON body
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if ($jsonInput) { $_POST = array_merge($_POST, $jsonInput); }
}

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

// Ensure filter_sub_cat column exists in stock_take table
function ensureFilterSubCatColumn($connect) {
    $result = $connect->query("SHOW COLUMNS FROM `stock_take` LIKE 'filter_sub_cat'");
    if ($result && $result->num_rows === 0) {
        $connect->query("ALTER TABLE `stock_take` ADD COLUMN `filter_sub_cat` VARCHAR(50) DEFAULT NULL AFTER `filter_cat`");
    }
}

// Ensure status column exists in stock_take_item table
function ensureItemStatusColumn($connect) {
    $result = $connect->query("SHOW COLUMNS FROM `stock_take_item` LIKE 'status'");
    if ($result && $result->num_rows === 0) {
        $connect->query("ALTER TABLE `stock_take_item` ADD COLUMN `status` VARCHAR(10) NOT NULL DEFAULT 'PENDING' AFTER `adj_applied`");
    }
}

if ($action === 'get_products') {
    // Return products for a given sub-category with last stock take date
    $subCat = trim($_POST['sub_cat'] ?? '');
    if ($subCat === '') {
        echo json_encode(['error' => 'Sub-category required.']);
        exit;
    }

    $products = [];
    $stmt = $connect->prepare("
        SELECT p.`barcode`, p.`name`, COALESCE(p.`qoh`, 0) AS qoh,
            (SELECT MAX(sti.`counted_at`) FROM `stock_take_item` sti
             INNER JOIN `stock_take` st ON sti.`stock_take_id` = st.`id`
             WHERE sti.`barcode` = p.`barcode` AND sti.`counted_qty` IS NOT NULL
            ) AS last_stock_take
        FROM `PRODUCTS` p
        WHERE p.`checked` = 'Y' AND p.`barcode` IS NOT NULL AND p.`barcode` != '' AND p.`sub_cat` = ?
        ORDER BY p.`name` ASC
    ");
    $stmt->bind_param("s", $subCat);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) {
        if ($row['last_stock_take']) {
            $row['last_stock_take'] = date('d/m/Y', strtotime($row['last_stock_take']));
        }
        $products[] = $row;
    }
    $stmt->close();

    echo json_encode(['products' => $products]);

} elseif ($action === 'create') {
    $desc = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? 'FULL');
    $filterCat = trim($_POST['filter_cat'] ?? '');
    $filterSubCat = trim($_POST['filter_sub_cat'] ?? '');
    $selectedProducts = $_POST['products'] ?? [];
    $createdBy = $_SESSION['admin_name'] ?? 'Admin';

    if (!in_array($type, ['FULL', 'PARTIAL'])) $type = 'FULL';
    if (!is_array($selectedProducts)) $selectedProducts = [];

    // Ensure columns exist
    ensureFilterSubCatColumn($connect);
    ensureItemStatusColumn($connect);

    $sessionCode = generateSessionCode($connect);
    $filterCatVal = ($type === 'PARTIAL' && $filterCat !== '') ? $filterCat : null;
    $filterSubCatVal = ($type === 'PARTIAL' && $filterSubCat !== '') ? $filterSubCat : null;

    $connect->begin_transaction();

    try {
        $stmt = $connect->prepare("INSERT INTO `stock_take` (`session_code`,`description`,`type`,`filter_cat`,`filter_sub_cat`,`status`,`created_by`) VALUES (?,?,?,?,?,'DRAFT',?)");
        $stmt->bind_param("ssssss", $sessionCode, $desc, $type, $filterCatVal, $filterSubCatVal, $createdBy);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create session: ' . $connect->error);
        }
        $sessionId = $connect->insert_id;
        $stmt->close();

        // Populate stock_take_item with products
        if ($type === 'PARTIAL' && !empty($selectedProducts)) {
            // Use specific selected products
            $itemStmt = $connect->prepare("INSERT INTO `stock_take_item` (`stock_take_id`,`barcode`,`product_desc`,`system_qty`,`status`) VALUES (?,?,?,?,'PENDING')");
            foreach ($selectedProducts as $barcode) {
                $barcode = trim($barcode);
                if ($barcode === '') continue;
                $pResult = $connect->query("SELECT `barcode`, `name`, COALESCE(`qoh`, 0) AS qoh FROM `PRODUCTS` WHERE `barcode` = '" . $connect->real_escape_string($barcode) . "' AND `checked` = 'Y' LIMIT 1");
                if ($pResult && $p = $pResult->fetch_assoc()) {
                    $name = $p['name'];
                    $sysQty = floatval($p['qoh']);
                    $itemStmt->bind_param("issd", $sessionId, $barcode, $name, $sysQty);
                    $itemStmt->execute();
                }
            }
            $itemStmt->close();
        } else {
            // Include all products matching filter
            $where = "WHERE `checked` = 'Y' AND `barcode` IS NOT NULL AND `barcode` != ''";
            if ($filterSubCatVal) {
                $where .= " AND `sub_cat` = '" . $connect->real_escape_string($filterSubCatVal) . "'";
            } elseif ($filterCatVal) {
                $where .= " AND `cat` = '" . $connect->real_escape_string($filterCatVal) . "'";
            }

            $productResult = $connect->query("SELECT `barcode`, `name`, COALESCE(`qoh`, 0) AS qoh FROM `PRODUCTS` $where ORDER BY `name` ASC");
            if ($productResult && $productResult->num_rows > 0) {
                $itemStmt = $connect->prepare("INSERT INTO `stock_take_item` (`stock_take_id`,`barcode`,`product_desc`,`system_qty`,`status`) VALUES (?,?,?,?,'PENDING')");
                while ($p = $productResult->fetch_assoc()) {
                    $barcode = $p['barcode'];
                    $name = $p['name'];
                    $sysQty = floatval($p['qoh']);
                    $itemStmt->bind_param("issd", $sessionId, $barcode, $name, $sysQty);
                    $itemStmt->execute();
                }
                $itemStmt->close();
            }
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

    // Verify session is editable (DRAFT or SUBMITTED)
    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    if (!in_array($row['status'], ['DRAFT', 'SUBMITTED'])) {
        echo json_encode(['error' => 'Session is already approved and cannot be edited.']);
        exit;
    }

    $countedBy = $_SESSION['admin_name'] ?? 'Admin';

    $connect->begin_transaction();

    try {
        // Calculate variance directly in SQL to avoid nested queries
        $stmt = $connect->prepare("UPDATE `stock_take_item` SET `counted_qty`=?, `variance`= ? - `system_qty`, `remark`=?, `counted_by`=?, `counted_at`=NOW() WHERE `id`=? AND `stock_take_id`=?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $connect->error);
        }

        $updated = 0;
        foreach ($counts as $c) {
            $itemId = intval($c['id'] ?? 0);
            $countedQty = ($c['counted_qty'] !== '' && $c['counted_qty'] !== null) ? floatval($c['counted_qty']) : null;
            $remark = trim($c['remark'] ?? '');

            if ($itemId <= 0) continue;

            $stmt->bind_param("ddssii", $countedQty, $countedQty, $remark, $countedBy, $itemId, $sessionId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update item ' . $itemId . ': ' . $stmt->error);
            }
            $updated++;
        }
        $stmt->close();

        $connect->commit();
        echo json_encode(['success' => $updated . ' items updated.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

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
    if ($row['status'] !== 'SUBMITTED') {
        echo json_encode(['error' => 'Session must be SUBMITTED to apply adjustments.']);
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
            $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`BARCODE`,`PDESC`,`QTYADJ`,`REMARK`,`LOSS_REASON`) VALUES ('STOCKTAKE',?,?,?,?,?,?,?,?,?,'ADJUSTMENT')");

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

} elseif ($action === 'approve') {
    $sessionId = intval($_POST['session_id'] ?? 0);
    $approvedBy = $_SESSION['admin_name'] ?? 'Admin';

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
    if ($row['status'] !== 'SUBMITTED') {
        echo json_encode(['error' => 'Session must be SUBMITTED to approve.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        // Apply stock adjustments for all items with variance
        $curDate = date('Y-m-d');
        $curTime = date('H:i:s');
        $salnum = 'STADJ' . date('YmdHis');

        $itemsResult = $connect->query("SELECT * FROM `stock_take_item` WHERE `stock_take_id` = $sessionId AND `counted_qty` IS NOT NULL AND `variance` != 0 AND `adj_applied` = 0");

        $adjCount = 0;
        if ($itemsResult) {
            $updateQohStmt = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) + ? WHERE `barcode` = ?");
            $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`BARCODE`,`PDESC`,`QTYADJ`,`REMARK`,`LOSS_REASON`) VALUES ('STOCKTAKE',?,?,?,?,?,?,?,?,?,'ADJUSTMENT')");

            while ($item = $itemsResult->fetch_assoc()) {
                $variance = floatval($item['variance']);
                $barcode = $item['barcode'];
                $desc = $item['product_desc'];
                $rmk = 'Stock Take Adj: ' . $item['stock_take_id'] . ' | Sys:' . $item['system_qty'] . ' Count:' . $item['counted_qty'];

                $updateQohStmt->bind_param("ds", $variance, $barcode);
                $updateQohStmt->execute();

                $adjStmt->bind_param("sssssssds", $approvedBy, $approvedBy, $curDate, $curTime, $salnum, $barcode, $desc, $variance, $rmk);
                $adjStmt->execute();

                $connect->query("UPDATE `stock_take_item` SET `adj_applied` = 1 WHERE `id` = " . intval($item['id']));
                $adjCount++;
            }

            $updateQohStmt->close();
            $adjStmt->close();
        }

        // Mark session as APPROVED
        $stmt = $connect->prepare("UPDATE `stock_take` SET `status`='APPROVED', `approved_by`=?, `approved_at`=NOW(), `completed_by`=?, `completed_at`=NOW() WHERE `id`=?");
        $stmt->bind_param("ssi", $approvedBy, $approvedBy, $sessionId);
        $stmt->execute();
        $stmt->close();

        $connect->commit();
        echo json_encode(['success' => 'Stock take approved. ' . $adjCount . ' adjustments applied to stock.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
