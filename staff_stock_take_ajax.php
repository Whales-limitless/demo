<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Support both form-encoded and JSON body
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $jsonInput = json_decode(file_get_contents('php://input'), true);
    if ($jsonInput) {
        $_POST = array_merge($_POST, $jsonInput);
    }
}

$action = $_POST['action'] ?? '';
$staffName = $_SESSION['user_name'] ?? 'Staff';

if ($action === 'list_sessions') {
    // Show DRAFT sessions (for counting) and SUBMITTED sessions (view only)
    $sessions = [];
    $result = $connect->query("SELECT `id`, `session_code`, `description`, `type`, `filter_cat`, `status`, `created_by`, `created_at` FROM `stock_take` WHERE `status` IN ('DRAFT', 'SUBMITTED') ORDER BY `id` DESC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $sid = intval($r['id']);
            $countResult = $connect->query("SELECT COUNT(*) AS total, SUM(CASE WHEN `counted_qty` IS NOT NULL THEN 1 ELSE 0 END) AS counted FROM `stock_take_item` WHERE `stock_take_id` = $sid");
            if ($countResult && $cr = $countResult->fetch_assoc()) {
                $r['total'] = intval($cr['total']);
                $r['counted'] = intval($cr['counted']);
            }
            $sessions[] = $r;
        }
    }
    echo json_encode(['success' => true, 'sessions' => $sessions]);

} elseif ($action === 'get_items') {
    $sessionId = intval($_POST['session_id'] ?? 0);
    if ($sessionId <= 0) {
        echo json_encode(['error' => 'Invalid session.']);
        exit;
    }

    $chk = $connect->query("SELECT `status`, `session_code`, `description` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    if (!in_array($row['status'], ['DRAFT'])) {
        echo json_encode(['error' => 'Session is not available for counting.']);
        exit;
    }

    $items = [];
    $result = $connect->query("SELECT `id`, `barcode`, `product_desc`, `system_qty`, `counted_qty`, `variance`, `remark`, `counted_by`, `adj_applied` FROM `stock_take_item` WHERE `stock_take_id` = $sessionId ORDER BY `id` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $r['description'] = $r['product_desc'];
            $items[] = $r;
        }
    }
    echo json_encode([
        'success' => true,
        'session_code' => $row['session_code'],
        'description' => $row['description'] ?? '',
        'status' => $row['status'],
        'items' => $items
    ]);

} elseif ($action === 'save_counts') {
    // Save counts without submitting (draft save)
    $sessionId = intval($_POST['session_id'] ?? 0);
    $countsRaw = $_POST['counts'] ?? '[]';
    $counts = is_array($countsRaw) ? $countsRaw : json_decode($countsRaw, true);

    if ($sessionId <= 0 || empty($counts)) {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    if ($row['status'] !== 'DRAFT') {
        echo json_encode(['error' => 'Session is not available for counting.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE `stock_take_item` SET `counted_qty`=?, `variance`=?, `remark`=?, `counted_by`=?, `counted_at`=NOW() WHERE `id`=? AND `stock_take_id`=?");

    $updated = 0;
    foreach ($counts as $c) {
        $itemId = intval($c['item_id'] ?? ($c['id'] ?? 0));
        $countedQty = ($c['counted_qty'] !== '' && $c['counted_qty'] !== null) ? floatval($c['counted_qty']) : null;
        $remark = trim($c['remark'] ?? '');

        if ($itemId <= 0) continue;

        $sysResult = $connect->query("SELECT `system_qty` FROM `stock_take_item` WHERE `id` = $itemId LIMIT 1");
        if ($sysResult && $sysRow = $sysResult->fetch_assoc()) {
            $systemQty = floatval($sysRow['system_qty']);
            $variance = ($countedQty !== null) ? ($countedQty - $systemQty) : null;

            $stmt->bind_param("ddssii", $countedQty, $variance, $remark, $staffName, $itemId, $sessionId);
            $stmt->execute();
            $updated++;
        }
    }
    $stmt->close();

    echo json_encode(['success' => $updated . ' items saved.']);

} elseif ($action === 'submit') {
    // Save counts AND submit for admin review
    $sessionId = intval($_POST['session_id'] ?? 0);
    $countsRaw = $_POST['counts'] ?? '[]';
    $counts = is_array($countsRaw) ? $countsRaw : json_decode($countsRaw, true);

    if ($sessionId <= 0 || empty($counts)) {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    if ($row['status'] !== 'DRAFT') {
        echo json_encode(['error' => 'Session is not available for submission.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        // Save counts first
        $stmt = $connect->prepare("UPDATE `stock_take_item` SET `counted_qty`=?, `variance`=?, `remark`=?, `counted_by`=?, `counted_at`=NOW() WHERE `id`=? AND `stock_take_id`=?");

        foreach ($counts as $c) {
            $itemId = intval($c['item_id'] ?? ($c['id'] ?? 0));
            $countedQty = ($c['counted_qty'] !== '' && $c['counted_qty'] !== null) ? floatval($c['counted_qty']) : null;
            $remark = trim($c['remark'] ?? '');

            if ($itemId <= 0) continue;

            $sysResult = $connect->query("SELECT `system_qty` FROM `stock_take_item` WHERE `id` = $itemId LIMIT 1");
            if ($sysResult && $sysRow = $sysResult->fetch_assoc()) {
                $systemQty = floatval($sysRow['system_qty']);
                $variance = ($countedQty !== null) ? ($countedQty - $systemQty) : null;

                $stmt->bind_param("ddssii", $countedQty, $variance, $remark, $staffName, $itemId, $sessionId);
                $stmt->execute();
            }
        }
        $stmt->close();

        // Update session status to SUBMITTED
        $submitStmt = $connect->prepare("UPDATE `stock_take` SET `status`='SUBMITTED', `submitted_by`=?, `submitted_at`=NOW() WHERE `id`=? AND `status`='DRAFT'");
        $submitStmt->bind_param("si", $staffName, $sessionId);
        $submitStmt->execute();

        if ($submitStmt->affected_rows === 0) {
            throw new Exception('Failed to submit session.');
        }
        $submitStmt->close();

        $connect->commit();
        echo json_encode(['success' => 'Stock take submitted for review.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
