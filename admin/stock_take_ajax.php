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
$adminUser = $_SESSION['admin_name'] ?? 'Admin';

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

if ($action === 'load_products') {
    $filterCat = trim($_POST['filter_cat'] ?? '');
    $filterSubCat = trim($_POST['filter_sub_cat'] ?? '');

    // Join with stock_take_item + stock_take to get last stock take date per product
    // Only consider sessions that are SUBMITTED or APPROVED (meaningful counts)
    $query = "SELECT p.`barcode`, p.`name`, COALESCE(p.`qoh`, 0) AS qoh,
              MAX(st.`created_at`) AS last_stock_take
              FROM `PRODUCTS` p
              LEFT JOIN `stock_take_item` sti ON sti.`barcode` = p.`barcode`
              LEFT JOIN `stock_take` st ON st.`id` = sti.`stock_take_id` AND st.`status` IN ('SUBMITTED', 'APPROVED')
              WHERE p.`checked` = 'Y'";
    $params = [];
    $types = '';

    if ($filterCat !== '') {
        $query .= " AND p.`cat` = ?";
        $params[] = $filterCat;
        $types .= 's';
    }
    if ($filterSubCat !== '') {
        $query .= " AND p.`sub_cat` = ?";
        $params[] = $filterSubCat;
        $types .= 's';
    }

    $query .= " GROUP BY p.`barcode`, p.`name`, p.`qoh` ORDER BY last_stock_take ASC, p.`name` ASC";

    $products = [];
    if (!empty($params)) {
        $stmt = $connect->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($r = $result->fetch_assoc()) {
            $products[] = $r;
        }
        $stmt->close();
    } else {
        $result = $connect->query($query);
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $products[] = $r;
            }
        }
    }

    echo json_encode(['success' => true, 'products' => $products]);

} elseif ($action === 'create') {
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? 'FULL');
    $filterCat = trim($_POST['filter_cat'] ?? '');
    $filterSubCat = trim($_POST['filter_sub_cat'] ?? '');
    $barcodesRaw = $_POST['barcodes'] ?? '[]';
    $selectedBarcodes = json_decode($barcodesRaw, true);

    if (!in_array($type, ['FULL', 'PARTIAL'])) {
        echo json_encode(['error' => 'Invalid stock take type.']);
        exit;
    }

    // Build product query
    if ($type === 'PARTIAL' && !empty($selectedBarcodes)) {
        // Use selected barcodes
        $placeholders = implode(',', array_fill(0, count($selectedBarcodes), '?'));
        $productQuery = "SELECT `barcode`, `name`, COALESCE(`qoh`, 0) AS qoh FROM `PRODUCTS` WHERE `checked` = 'Y' AND `barcode` IN ($placeholders) ORDER BY `name` ASC";
        $stmt = $connect->prepare($productQuery);
        $bindTypes = str_repeat('s', count($selectedBarcodes));
        $stmt->bind_param($bindTypes, ...$selectedBarcodes);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($r = $result->fetch_assoc()) {
            $products[] = $r;
        }
        $stmt->close();
    } else {
        // Full stock take - all active products
        $result = $connect->query("SELECT `barcode`, `name`, COALESCE(`qoh`, 0) AS qoh FROM `PRODUCTS` WHERE `checked` = 'Y' ORDER BY `cat` ASC, `name` ASC");
        $products = [];
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $products[] = $r;
            }
        }
    }

    if (count($products) === 0) {
        echo json_encode(['error' => 'No products found.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        $sessionCode = generateSessionCode($connect);
        $filterCatVal = ($type === 'PARTIAL' && $filterCat !== '') ? $filterCat : null;
        $filterSubCatVal = ($type === 'PARTIAL' && $filterSubCat !== '') ? $filterSubCat : null;

        // Create session header
        $stmt = $connect->prepare("INSERT INTO `stock_take` (`session_code`, `description`, `type`, `filter_cat`, `filter_sub_cat`, `status`, `created_by`, `created_at`) VALUES (?, ?, ?, ?, ?, 'DRAFT', ?, NOW())");
        $stmt->bind_param("ssssss", $sessionCode, $description, $type, $filterCatVal, $filterSubCatVal, $adminUser);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create session: ' . $connect->error);
        }
        $sessionId = $connect->insert_id;
        $stmt->close();

        // Insert items
        $itemStmt = $connect->prepare("INSERT INTO `stock_take_item` (`stock_take_id`, `barcode`, `product_desc`, `system_qty`, `status`) VALUES (?, ?, ?, ?, 'PENDING')");
        if (!$itemStmt) {
            throw new Exception('Failed to prepare item insert: ' . $connect->error);
        }

        $itemCount = 0;
        foreach ($products as $p) {
            $barcode = $p['barcode'];
            $desc = substr($p['name'], 0, 100);
            $qoh = floatval($p['qoh']);
            $itemStmt->bind_param("issd", $sessionId, $barcode, $desc, $qoh);
            if (!$itemStmt->execute()) {
                throw new Exception('Failed to insert item: ' . $connect->error);
            }
            $itemCount++;
        }
        $itemStmt->close();

        $connect->commit();
        echo json_encode(['success' => 'Session ' . $sessionCode . ' created with ' . $itemCount . ' items.', 'session_id' => $sessionId]);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'approve') {
    $sessionId = intval($_POST['session_id'] ?? 0);

    if ($sessionId <= 0) {
        echo json_encode(['error' => 'Invalid session.']);
        exit;
    }

    // Verify session exists and is SUBMITTED
    $chk = $connect->query("SELECT `status`, `session_code` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $session = $chk->fetch_assoc();
    if ($session['status'] !== 'SUBMITTED') {
        echo json_encode(['error' => 'Session must be in SUBMITTED status to approve.']);
        exit;
    }

    // Get items with variance
    $items = [];
    $itemResult = $connect->query("SELECT * FROM `stock_take_item` WHERE `stock_take_id` = $sessionId AND `status` = 'COUNTED' AND `variance` != 0 AND `adj_applied` = 0");
    if ($itemResult) {
        while ($r = $itemResult->fetch_assoc()) {
            $items[] = $r;
        }
    }

    $connect->begin_transaction();

    try {
        $adjustedCount = 0;

        if (count($items) > 0) {
            $updateQoh = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = ? WHERE `barcode` = ?");
            $insertAdj = $connect->prepare("INSERT INTO `stockadj` (`ACCODE`, `USER`, `OUTLET`, `SDATE`, `STIME`, `SALNUM`, `BARCODE`, `PDESC`, `QTYADJ`, `REMARK`) VALUES ('STOCKTAKE', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $markApplied = $connect->prepare("UPDATE `stock_take_item` SET `adj_applied` = 1 WHERE `id` = ?");

            $curDate = date('Y-m-d');
            $curTime = date('H:i:s');

            foreach ($items as $item) {
                $barcode = $item['barcode'];
                $countedQty = floatval($item['counted_qty']);
                $variance = floatval($item['variance']);
                $desc = substr($item['product_desc'], 0, 48);
                $salnum = 'ST' . date('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                $remark = 'Stock Take Adj: ' . $session['session_code'];

                // Set QOH to counted quantity
                $updateQoh->bind_param("ds", $countedQty, $barcode);
                if (!$updateQoh->execute()) {
                    throw new Exception('Failed to update QOH for ' . $barcode);
                }

                // Record in stockadj
                $insertAdj->bind_param("sssssssds", $adminUser, $adminUser, $curDate, $curTime, $salnum, $barcode, $desc, $variance, $remark);
                if (!$insertAdj->execute()) {
                    throw new Exception('Failed to record adjustment for ' . $barcode);
                }

                // Mark item as applied
                $markApplied->bind_param("i", $item['id']);
                $markApplied->execute();

                $adjustedCount++;
            }

            $updateQoh->close();
            $insertAdj->close();
            $markApplied->close();
        }

        // Mark session as APPROVED
        $approveStmt = $connect->prepare("UPDATE `stock_take` SET `status` = 'APPROVED', `approved_by` = ?, `approved_at` = NOW() WHERE `id` = ?");
        $approveStmt->bind_param("si", $adminUser, $sessionId);
        if (!$approveStmt->execute()) {
            throw new Exception('Failed to approve session.');
        }
        $approveStmt->close();

        $connect->commit();
        echo json_encode(['success' => 'Session approved. ' . $adjustedCount . ' product(s) adjusted.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'delete') {
    $sessionId = intval($_POST['session_id'] ?? 0);

    if ($sessionId <= 0) {
        echo json_encode(['error' => 'Invalid session.']);
        exit;
    }

    // Only allow deleting DRAFT sessions
    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $session = $chk->fetch_assoc();
    if ($session['status'] !== 'DRAFT') {
        echo json_encode(['error' => 'Only DRAFT sessions can be deleted.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        // Delete items first
        $connect->query("DELETE FROM `stock_take_item` WHERE `stock_take_id` = $sessionId");
        // Delete session
        $connect->query("DELETE FROM `stock_take` WHERE `id` = $sessionId AND `status` = 'DRAFT'");

        $connect->commit();
        echo json_encode(['success' => 'Session deleted.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
