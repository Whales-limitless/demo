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

$action = $_POST['action'] ?? '';
$staffName = $_SESSION['user_name'] ?? 'Staff';
$staffOutlet = $_SESSION['user_outlet'] ?? 'WEB';
$staffBranch = $_SESSION['user_branch_code'] ?? ($_SESSION['user_outlet'] ?? '');

if ($action === 'record_multiple') {
    $itemsRaw = $_POST['items'] ?? '[]';
    $items = json_decode($itemsRaw, true);

    if (!is_array($items) || empty($items)) {
        echo json_encode(['error' => 'No items to record.']);
        exit;
    }

    $validReasons = ['SPOILAGE', 'DAMAGE', 'THEFT', 'EXPIRED', 'OTHER'];

    // Validate all items
    foreach ($items as $idx => $item) {
        $desc = trim($item['description'] ?? '');
        $qty = floatval($item['qty'] ?? 0);
        $reason = trim($item['reason'] ?? '');

        if ($desc === '' || $qty <= 0) {
            echo json_encode(['error' => 'Description and quantity are required for item #' . ($idx + 1) . '.']);
            exit;
        }
        if (!in_array($reason, $validReasons)) {
            echo json_encode(['error' => 'Invalid reason for item #' . ($idx + 1) . '.']);
            exit;
        }
    }

    // Ensure uploads directory exists
    $uploadDir = __DIR__ . '/uploads/stock_loss/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    // Add columns if not exist (suppress error if already exists)
    @$connect->query("ALTER TABLE `stockadj` ADD COLUMN `branch_code` VARCHAR(20) DEFAULT NULL");
    @$connect->query("ALTER TABLE `stockadj` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL");

    $connect->begin_transaction();

    try {
        $curDate = date('Y-m-d');
        $curTime = date('H:i:s');
        $batchId = 'LOSS' . date('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $recorded = 0;

        foreach ($items as $idx => $item) {
            $desc = trim($item['description']);
            $qty = floatval($item['qty']);
            $reason = trim($item['reason']);
            $remark = trim($item['remark'] ?? '');
            $negQty = -$qty;
            $salnum = $batchId . '_' . ($idx + 1);

            // Handle image if provided
            $imagePath = null;
            $imageKey = 'image_' . $idx;
            if (!empty($_POST[$imageKey])) {
                $imageData = $_POST[$imageKey];
                if (preg_match('/^data:image\/(jpeg|png|gif|webp);base64,(.+)$/', $imageData, $matches)) {
                    $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                    $decoded = base64_decode($matches[2]);
                    if ($decoded !== false) {
                        $filename = $salnum . '.' . $ext;
                        $filepath = $uploadDir . $filename;
                        if (file_put_contents($filepath, $decoded)) {
                            $imagePath = 'uploads/stock_loss/' . $filename;
                        }
                    }
                }
            }

            // Insert stock loss record (no barcode, no QOH adjustment)
            $imgVal = $imagePath ?? '';
            $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`IP`,`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`MNO`,`BARCODE`,`PDESC`,`LOOSE`,`PGROUP`,`PRODTYPE`,`QTYADJ`,`SERIALNUMBER`,`REMARK`,`LOSS_REASON`,`branch_code`,`image_path`) VALUES ('','STOCKLOSS',?,?,?,?,?,'','',?,0,'','',?,'',?,?,?,?)");
            $adjStmt->bind_param("ssssssdssss", $staffName, $staffOutlet, $curDate, $curTime, $salnum, $desc, $negQty, $remark, $reason, $staffBranch, $imgVal);
            if (!$adjStmt->execute()) {
                throw new Exception('Failed to insert record: ' . $connect->error);
            }
            $adjStmt->close();

            $recorded++;
        }

        $connect->commit();
        echo json_encode(['success' => $recorded . ' stock loss record(s) saved successfully.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'recent' || $action === 'list_sessions') {
    $result = $connect->query("
        SELECT SUBSTRING_INDEX(`SALNUM`, '_', 1) AS session_id,
               MIN(`SDATE`) AS session_date,
               MIN(`STIME`) AS session_time,
               COUNT(*) AS item_count,
               SUM(ABS(`QTYADJ`)) AS total_qty,
               GROUP_CONCAT(DISTINCT `LOSS_REASON` ORDER BY `LOSS_REASON` SEPARATOR ',') AS reasons,
               MIN(`USER`) AS recorded_by
        FROM `stockadj`
        WHERE `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT'
        GROUP BY SUBSTRING_INDEX(`SALNUM`, '_', 1)
        ORDER BY MAX(`ID`) DESC
        LIMIT 100
    ");
    $sessions = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $r['item_count'] = (int)$r['item_count'];
            $r['total_qty'] = (float)$r['total_qty'];
            $sessions[] = $r;
        }
    }
    echo json_encode(['sessions' => $sessions]);

} elseif ($action === 'session_detail') {
    $sessionId = trim($_POST['session_id'] ?? '');
    if ($sessionId === '') {
        echo json_encode(['error' => 'Session ID required.']);
        exit;
    }
    $like = $sessionId . '%';
    $stmt = $connect->prepare("
        SELECT `ID`, `SDATE`, `STIME`, `PDESC`, `QTYADJ`, `LOSS_REASON`, `REMARK`, `USER`, `image_path`
        FROM `stockadj`
        WHERE `SALNUM` LIKE ?
          AND `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT'
        ORDER BY `ID` ASC
    ");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($r = $result->fetch_assoc()) {
        $r['QTYADJ'] = (float)$r['QTYADJ'];
        $items[] = $r;
    }
    $stmt->close();
    echo json_encode(['items' => $items]);

} elseif ($action === 'delete_session') {
    $sessionId = trim($_POST['session_id'] ?? '');
    if ($sessionId === '') {
        echo json_encode(['error' => 'Session ID required.']);
        exit;
    }

    $like = $sessionId . '%';

    // Get image paths for cleanup
    $stmt = $connect->prepare("
        SELECT `image_path`
        FROM `stockadj`
        WHERE `SALNUM` LIKE ?
          AND `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT'
    ");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($r = $result->fetch_assoc()) {
        if (!empty($r['image_path'])) $images[] = $r['image_path'];
    }
    $stmt->close();

    // Delete records
    $delStmt = $connect->prepare("DELETE FROM `stockadj` WHERE `SALNUM` LIKE ? AND `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT'");
    $delStmt->bind_param("s", $like);
    $delStmt->execute();
    $deleted = $delStmt->affected_rows;
    $delStmt->close();

    if ($deleted === 0) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }

    // Clean up image files
    foreach ($images as $imgPath) {
        $filePath = __DIR__ . '/' . $imgPath;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    echo json_encode(['success' => $deleted . ' record(s) deleted successfully.']);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
