<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

// Set JSON header for all actions except export_excel (which outputs binary)
if ($action !== 'export_excel') {
    header('Content-Type: application/json; charset=utf-8');
}

if ($action === 'record_multiple') {
    $itemsRaw = $_POST['items'] ?? '[]';
    $items = json_decode($itemsRaw, true);
    $adminUser = $_SESSION['admin_name'] ?? 'Admin';

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
    $uploadDir = __DIR__ . '/../staff/uploads/stock_loss/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    // Add image_path and LOSS_REASON columns if not exist
    $connect->query("ALTER TABLE `stockadj` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL");
    $connect->query("ALTER TABLE `stockadj` ADD COLUMN `LOSS_REASON` ENUM('SPOILAGE','DAMAGE','THEFT','EXPIRED','OTHER','ADJUSTMENT') DEFAULT 'ADJUSTMENT'");
    // Clear any error from ALTER (column already exists is expected)
    $connect->errno;

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

            // Insert stock loss record
            $imgVal = $imagePath ?? '';
            $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`BARCODE`,`PDESC`,`QTYADJ`,`REMARK`,`LOSS_REASON`,`image_path`) VALUES ('STOCKLOSS',?,?,?,?,?,'',?,?,?,?,?)");
            if (!$adjStmt) {
                throw new Exception('Prepare failed: ' . $connect->error);
            }
            $adjStmt->bind_param("ssssssdsss", $adminUser, $adminUser, $curDate, $curTime, $salnum, $desc, $negQty, $remark, $reason, $imgVal);
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

} elseif ($action === 'list_sessions') {
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
        LIMIT 500
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

    // Get items to clean up image files
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
        $filePath = __DIR__ . '/../staff/' . $imgPath;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    echo json_encode(['success' => $deleted . ' record(s) deleted successfully.']);

} elseif ($action === 'export_excel') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new Exception("$errstr in $errfile:$errline");
    });
    try {
    require_once __DIR__ . '/../staff/SimpleXlsxWriter.php';

    $type = $_POST['type'] ?? ''; // 'session' or 'monthly'
    $sessionId = trim($_POST['session_id'] ?? '');
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);

    $items = [];
    $title = 'Stock Loss';

    if ($type === 'session' && $sessionId !== '') {
        $like = $sessionId . '%';
        $stmt = $connect->prepare("SELECT `SDATE`, `STIME`, `PDESC`, `QTYADJ`, `LOSS_REASON`, `REMARK`, `USER`, `image_path` FROM `stockadj` WHERE `SALNUM` LIKE ? AND `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT' ORDER BY `ID` ASC");
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($r = $result->fetch_assoc()) $items[] = $r;
        $stmt->close();
        $title = 'StockLoss_' . $sessionId;
    } elseif ($type === 'monthly' && $month >= 1 && $year >= 2000) {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $stmt = $connect->prepare("SELECT `SDATE`, `STIME`, `PDESC`, `QTYADJ`, `LOSS_REASON`, `REMARK`, `USER`, `image_path` FROM `stockadj` WHERE `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT' AND `SDATE` >= ? AND `SDATE` <= ? ORDER BY `SDATE` ASC, `ID` ASC");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($r = $result->fetch_assoc()) $items[] = $r;
        $stmt->close();
        $months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $title = 'StockLoss_' . $months[$month] . '_' . $year;
    }

    if (empty($items)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No data to export.']);
        exit;
    }

    $xlsx = new SimpleXlsxWriter();
    $xlsx->setTitle('Stock Loss');
    $xlsx->setColWidths([5, 12, 14, 35, 8, 14, 25, 15]);

    // Header row
    $xlsx->addRow(['No', 'Date', 'Image', 'Description', 'Qty', 'Reason', 'Remark', 'Recorded By'], [2,2,2,2,2,2,2,2]);

    $imgDir = __DIR__ . '/../staff/';
    $totalQty = 0;
    foreach ($items as $i => $item) {
        $qty = abs(floatval($item['QTYADJ'] ?? 0));
        $totalQty += $qty;
        $xlsx->addRow([
            $i + 1,
            $item['SDATE'] ?? '',
            '', // image column placeholder
            $item['PDESC'] ?? '',
            $qty,
            $item['LOSS_REASON'] ?? '',
            $item['REMARK'] ?? '',
            $item['USER'] ?? ''
        ], [3,3,3,3,3,3,3,3]);

        if (!empty($item['image_path'])) {
            $imgPath = $imgDir . $item['image_path'];
            $xlsx->addImage($i + 1, 2, $imgPath, 70, 60); // row is 0-indexed for data, +1 for header
        }
    }

    // Total row
    $xlsx->addRow(['', '', '', 'Total:', $totalQty, '', '', ''], [0,0,0,1,1,0,0,0]);

    $tmpFile = $xlsx->generate();
    if (!$tmpFile) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to generate file.']);
        exit;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $title . '.xlsx"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo 'Export error: ' . $e->getMessage();
        exit;
    }

} elseif ($action === 'images_base64') {
    // Convert image paths to base64 for Excel export
    $paths = json_decode($_POST['paths'] ?? '[]', true);
    if (!is_array($paths)) { echo json_encode([]); exit; }

    $result = [];
    foreach ($paths as $path) {
        $filePath = __DIR__ . '/../staff/' . $path;
        if (!empty($path) && file_exists($filePath)) {
            $mime = mime_content_type($filePath);
            $data = base64_encode(file_get_contents($filePath));
            $result[$path] = 'data:' . $mime . ';base64,' . $data;
        } else {
            $result[$path] = '';
        }
    }
    echo json_encode($result);

} elseif ($action === 'monthly_summary') {
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    if ($month < 1 || $month > 12 || $year < 2000) {
        echo json_encode(['error' => 'Invalid month/year.']);
        exit;
    }

    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));

    // Get all items for the month
    $stmt = $connect->prepare("
        SELECT `SDATE`, `STIME`, `PDESC`, `QTYADJ`, `LOSS_REASON`, `REMARK`, `USER`, `image_path`,
               SUBSTRING_INDEX(`SALNUM`, '_', 1) AS session_id
        FROM `stockadj`
        WHERE `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT'
          AND `SDATE` >= ? AND `SDATE` <= ?
        ORDER BY `SDATE` ASC, `ID` ASC
    ");
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($r = $result->fetch_assoc()) {
        $r['QTYADJ'] = (float)$r['QTYADJ'];
        $items[] = $r;
    }
    $stmt->close();

    // Summary by reason
    $byReason = [];
    foreach ($items as $item) {
        $reason = $item['LOSS_REASON'] ?? 'OTHER';
        if (!isset($byReason[$reason])) {
            $byReason[$reason] = ['count' => 0, 'qty' => 0];
        }
        $byReason[$reason]['count']++;
        $byReason[$reason]['qty'] += abs($item['QTYADJ']);
    }

    echo json_encode([
        'items' => $items,
        'by_reason' => $byReason,
        'month' => $month,
        'year' => $year,
        'total_items' => count($items),
        'total_qty' => array_sum(array_map(function($i) { return abs($i['QTYADJ']); }, $items))
    ]);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
