<?php
require_once __DIR__ . '/session_security.php';
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

// Ensure status column exists in stock_take_item table
function ensureItemStatusColumn($connect) {
    $result = $connect->query("SHOW COLUMNS FROM `stock_take_item` LIKE 'status'");
    $exists = ($result && $result->num_rows > 0);
    if ($result) $result->free();
    if (!$exists) {
        $connect->query("ALTER TABLE `stock_take_item` ADD COLUMN `status` VARCHAR(10) NOT NULL DEFAULT 'PENDING' AFTER `adj_applied`");
    }
}

$action = $_POST['action'] ?? '';
$staffName = $_SESSION['user_name'] ?? 'Staff';
$staffBranch = $_SESSION['user_branch_code'] ?? ($_SESSION['user_outlet'] ?? '');

if ($action === 'list_sessions') {
    ensureItemStatusColumn($connect);
    // Show DRAFT sessions (for counting) and SUBMITTED sessions (view only)
    // Use a single query with LEFT JOIN to avoid nested queries on the same connection
    $sessions = [];
    $result = $connect->query("
        SELECT st.`id`, st.`session_code`, st.`description`, st.`type`, st.`filter_cat`, st.`status`, st.`created_by`, st.`created_at`,
            COUNT(sti.`id`) AS total,
            SUM(CASE WHEN sti.`status` = 'COUNTED' THEN 1 ELSE 0 END) AS counted
        FROM `stock_take` st
        LEFT JOIN `stock_take_item` sti ON sti.`stock_take_id` = st.`id`
        WHERE st.`status` IN ('DRAFT', 'SUBMITTED')
        GROUP BY st.`id`
        ORDER BY st.`id` DESC
    ");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $r['total'] = intval($r['total']);
            $r['counted'] = intval($r['counted']);
            $sessions[] = $r;
        }
        $result->free();
    }
    echo json_encode(['success' => true, 'sessions' => $sessions]);

} elseif ($action === 'get_items') {
    ensureItemStatusColumn($connect);
    $sessionId = intval($_POST['session_id'] ?? 0);
    if ($sessionId <= 0) {
        echo json_encode(['error' => 'Invalid session.']);
        exit;
    }

    $chk = $connect->query("SELECT `status`, `session_code`, `description` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        if ($chk) $chk->free();
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    $chk->free();
    if (!in_array($row['status'], ['DRAFT', 'SUBMITTED'])) {
        echo json_encode(['error' => 'Session is not available.']);
        exit;
    }

    $items = [];
    $result = $connect->query("SELECT sti.`id`, sti.`barcode`, sti.`product_desc`, sti.`system_qty`, sti.`counted_qty`, sti.`variance`, sti.`remark`, sti.`counted_by`, sti.`counted_at`, sti.`adj_applied`, sti.`status`, p.`id` AS product_id, p.`rack` AS rack_location FROM `stock_take_item` sti LEFT JOIN `PRODUCTS` p ON p.`barcode` = sti.`barcode` WHERE sti.`stock_take_id` = " . intval($sessionId) . " ORDER BY sti.`id` ASC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $r['description'] = $r['product_desc'];
            $r['item_status'] = $r['status'];
            $r['product_id'] = $r['product_id'] ? intval($r['product_id']) : null;
            $r['rack_location'] = $r['rack_location'] ?? '';
            if ($r['counted_at']) {
                $r['counted_at'] = date('d/m/Y H:i', strtotime($r['counted_at']));
            }
            $items[] = $r;
        }
        $result->free();
    }
    echo json_encode([
        'success' => true,
        'session_code' => $row['session_code'],
        'description' => $row['description'] ?? '',
        'status' => $row['status'],
        'items' => $items
    ]);

} elseif ($action === 'save_counts') {
    // Save counts without submitting (draft save) - does NOT change item status
    $sessionId = intval($_POST['session_id'] ?? 0);
    $countsRaw = $_POST['counts'] ?? '[]';
    $counts = is_array($countsRaw) ? $countsRaw : json_decode($countsRaw, true);

    if ($sessionId <= 0 || empty($counts)) {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        if ($chk) $chk->free();
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    $chk->free();
    if ($row['status'] !== 'DRAFT') {
        echo json_encode(['error' => 'Session is not available for counting.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        $stmt = $connect->prepare("UPDATE `stock_take_item` SET `counted_qty`=?, `variance`= ? - `system_qty`, `remark`=?, `counted_by`=?, `counted_at`=NOW() WHERE `id`=? AND `stock_take_id`=? AND `status`='PENDING'");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $connect->error);
        }

        $updated = 0;
        foreach ($counts as $c) {
            $itemId = intval($c['item_id'] ?? ($c['id'] ?? 0));
            $countedQty = ($c['counted_qty'] !== '' && $c['counted_qty'] !== null) ? floatval($c['counted_qty']) : null;
            $remark = trim($c['remark'] ?? '');

            if ($itemId <= 0) continue;

            $stmt->bind_param("ddssii", $countedQty, $countedQty, $remark, $staffName, $itemId, $sessionId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update item ' . $itemId . ': ' . $stmt->error);
            }
            $updated++;
        }
        $stmt->close();

        $connect->commit();
        echo json_encode(['success' => $updated . ' items saved.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'submit') {
    // Save counts AND mark items as COUNTED (partial submit)
    ensureItemStatusColumn($connect);
    $sessionId = intval($_POST['session_id'] ?? 0);
    $countsRaw = $_POST['counts'] ?? '[]';
    $counts = is_array($countsRaw) ? $countsRaw : json_decode($countsRaw, true);

    if ($sessionId <= 0 || empty($counts)) {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    $chk = $connect->query("SELECT `status` FROM `stock_take` WHERE `id` = $sessionId LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        if ($chk) $chk->free();
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }
    $row = $chk->fetch_assoc();
    $chk->free();
    if ($row['status'] !== 'DRAFT') {
        echo json_encode(['error' => 'Session is not available for submission.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        // Save counts and mark items as COUNTED
        $stmt = $connect->prepare("UPDATE `stock_take_item` SET `counted_qty`=?, `variance`= ? - `system_qty`, `remark`=?, `counted_by`=?, `counted_at`=NOW(), `status`='COUNTED' WHERE `id`=? AND `stock_take_id`=?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $connect->error);
        }

        $submitted = 0;
        foreach ($counts as $c) {
            $itemId = intval($c['item_id'] ?? ($c['id'] ?? 0));
            $countedQty = ($c['counted_qty'] !== '' && $c['counted_qty'] !== null) ? floatval($c['counted_qty']) : null;
            $remark = trim($c['remark'] ?? '');

            if ($itemId <= 0) continue;

            $stmt->bind_param("ddssii", $countedQty, $countedQty, $remark, $staffName, $itemId, $sessionId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update item ' . $itemId . ': ' . $stmt->error);
            }
            $submitted++;
        }
        $stmt->close();

        // Check if ALL items in the session are now COUNTED
        $checkResult = $connect->query("SELECT COUNT(*) AS remaining FROM `stock_take_item` WHERE `stock_take_id` = $sessionId AND `status` = 'PENDING'");
        $remaining = 0;
        if ($checkResult && $cr = $checkResult->fetch_assoc()) {
            $remaining = intval($cr['remaining']);
        }

        $sessionCompleted = false;
        if ($remaining === 0) {
            // All items submitted - mark session as SUBMITTED for admin review
            // Try to add branch_code column if not exists (safe for first run)
            $connect->query("ALTER TABLE `stock_take` ADD COLUMN `branch_code` VARCHAR(20) DEFAULT NULL AFTER `approved_by`");

            $submitStmt = $connect->prepare("UPDATE `stock_take` SET `status`='SUBMITTED', `submitted_by`=?, `submitted_at`=NOW(), `branch_code`=? WHERE `id`=? AND `status`='DRAFT'");
            $submitStmt->bind_param("ssi", $staffName, $staffBranch, $sessionId);
            $submitStmt->execute();
            $submitStmt->close();
            $sessionCompleted = true;
        }

        $connect->commit();
        echo json_encode([
            'success' => $submitted . ' items submitted.',
            'session_completed' => $sessionCompleted,
            'remaining' => $remaining
        ]);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'update_product_desc') {
    $itemId = intval($_POST['item_id'] ?? 0);
    $newDesc = trim($_POST['new_desc'] ?? '');

    if ($itemId <= 0 || $newDesc === '') {
        echo json_encode(['error' => 'Invalid data.']);
        exit;
    }

    // Get the barcode from the stock_take_item
    $stmt = $connect->prepare("SELECT `barcode` FROM `stock_take_item` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['error' => 'Item not found.']);
        exit;
    }

    $barcode = $row['barcode'];
    $connect->begin_transaction();

    try {
        // Update stock_take_item description
        $stmt = $connect->prepare("UPDATE `stock_take_item` SET `product_desc` = ? WHERE `id` = ?");
        $stmt->bind_param("si", $newDesc, $itemId);
        $stmt->execute();
        $stmt->close();

        // Update PRODUCTS.name so it stays in sync
        if ($barcode !== '') {
            $stmt = $connect->prepare("UPDATE `PRODUCTS` SET `name` = ? WHERE `barcode` = ?");
            $stmt->bind_param("ss", $newDesc, $barcode);
            $stmt->execute();
            $stmt->close();
        }

        $connect->commit();
        echo json_encode(['success' => true, 'new_desc' => $newDesc]);
    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
