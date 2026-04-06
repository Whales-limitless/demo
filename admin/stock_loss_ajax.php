<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$action = $_POST['action'] ?? '';

if ($action === 'search_products') {
    $search = $_POST['q'] ?? '';
    if ($search === '') {
        echo json_encode(['products' => [], 'total' => 0]);
        exit;
    }

    $offset = max(0, intval($_POST['offset'] ?? 0));
    $limit = 50;

    // Normalize quote variants
    $normalizedSearch = $search;
    $normalizedSearch = str_replace(["\u{201C}", "\u{201D}", "\u{2033}", "\u{FF02}"], '"', $normalizedSearch);
    $normalizedSearch = str_replace(["\u{2018}", "\u{2019}", "\u{2032}", "\u{FF07}"], "'", $normalizedSearch);
    $altSearch = str_replace('"', "''", $normalizedSearch);
    $altSearch2 = str_replace("''", '"', $normalizedSearch);
    $normalizedLike = '%' . $normalizedSearch . '%';
    $altLike = '%' . $altSearch . '%';
    $altLike2 = '%' . $altSearch2 . '%';

    $cntStmt = $connect->prepare("
        SELECT COUNT(DISTINCT p.`id`) AS cnt
        FROM `PRODUCTS` p
        WHERE (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?)
          AND (p.`checked` != 'N' OR p.`checked` IS NULL)
          AND EXISTS (SELECT 1 FROM `category` c WHERE c.`cat_code` = p.`cat_code` AND c.`sub_code` = p.`sub_code`)
    ");
    $cntStmt->bind_param("sss", $normalizedLike, $altLike, $altLike2);
    $cntStmt->execute();
    $total = (int)$cntStmt->get_result()->fetch_assoc()['cnt'];
    $cntStmt->close();

    $stmt = $connect->prepare("
        SELECT DISTINCT p.`id`, p.`barcode`, p.`name`, p.`img1` AS image, p.`uom`,
               COALESCE(p.`qoh`, 0) AS qoh, p.`cat_code`, p.`rack`,
               c.`cat_name` AS category_name
        FROM `PRODUCTS` p
        INNER JOIN `category` c ON p.`cat_code` = c.`cat_code` AND p.`sub_code` = c.`sub_code`
        WHERE (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?)
          AND (p.`checked` != 'N' OR p.`checked` IS NULL)
        ORDER BY p.`name` ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sssii", $normalizedLike, $altLike, $altLike2, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    $seen = [];
    while ($row = $result->fetch_assoc()) {
        if (isset($seen[$row['id']])) continue;
        $seen[$row['id']] = true;
        $row['id'] = (int)$row['id'];
        $row['qoh'] = (float)$row['qoh'];
        $products[] = $row;
    }
    $stmt->close();
    echo json_encode(['products' => $products, 'total' => $total]);

} elseif ($action === 'lookup') {
    $barcode = trim($_POST['barcode'] ?? '');
    if ($barcode === '') {
        echo json_encode(['name' => '']);
        exit;
    }
    $stmt = $connect->prepare("SELECT `name`, COALESCE(`qoh`, 0) AS qoh FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['name' => $row['name'] . ' (QOH: ' . $row['qoh'] . ')', 'qoh' => $row['qoh']]);
    } else {
        echo json_encode(['name' => 'Product not found', 'qoh' => 0]);
    }
    $stmt->close();

} elseif ($action === 'record') {
    $barcode = trim($_POST['barcode'] ?? '');
    $qty = floatval($_POST['qty'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $adminUser = $_SESSION['admin_name'] ?? 'Admin';

    if ($barcode === '' || $qty <= 0) {
        echo json_encode(['error' => 'Barcode and quantity are required.']);
        exit;
    }

    $validReasons = ['SPOILAGE', 'DAMAGE', 'THEFT', 'EXPIRED', 'OTHER'];
    if (!in_array($reason, $validReasons)) {
        echo json_encode(['error' => 'Invalid loss reason.']);
        exit;
    }

    // Look up product
    $stmt = $connect->prepare("SELECT `name` FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Product not found.']);
        $stmt->close();
        exit;
    }
    $product = $result->fetch_assoc();
    $stmt->close();

    $connect->begin_transaction();

    try {
        $curDate = date('Y-m-d');
        $curTime = date('H:i:s');
        $salnum = 'LOSS' . date('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $negQty = -$qty; // Negative because it's a loss
        $desc = substr($product['name'], 0, 48);

        // Insert stockadj record with LOSS_REASON
        $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`BARCODE`,`PDESC`,`QTYADJ`,`REMARK`,`LOSS_REASON`) VALUES ('STOCKLOSS',?,?,?,?,?,?,?,?,?,?)");
        $adjStmt->bind_param("sssssssdss", $adminUser, $adminUser, $curDate, $curTime, $salnum, $barcode, $desc, $negQty, $remark, $reason);
        if (!$adjStmt->execute()) {
            throw new Exception('Failed to insert adjustment: ' . $connect->error);
        }
        $adjStmt->close();

        // Deduct from PRODUCTS.qoh
        $qohStmt = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) - ? WHERE `barcode` = ?");
        $qohStmt->bind_param("ds", $qty, $barcode);
        $qohStmt->execute();
        $qohStmt->close();

        $connect->commit();
        echo json_encode(['success' => 'Stock loss recorded. ' . $qty . ' unit(s) deducted from ' . $barcode . '.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'record_multiple') {
    $itemsRaw = $_POST['items'] ?? '[]';
    $items = json_decode($itemsRaw, true);
    $adminUser = $_SESSION['admin_name'] ?? 'Admin';

    if (!is_array($items) || empty($items)) {
        echo json_encode(['error' => 'No items to record.']);
        exit;
    }

    $validReasons = ['SPOILAGE', 'DAMAGE', 'THEFT', 'EXPIRED', 'OTHER'];

    // Validate all items first
    foreach ($items as $idx => $item) {
        $barcode = trim($item['barcode'] ?? '');
        $qty = floatval($item['qty'] ?? 0);
        $reason = trim($item['reason'] ?? '');

        if ($barcode === '' || $qty <= 0) {
            echo json_encode(['error' => 'Invalid barcode or quantity for item #' . ($idx + 1) . '.']);
            exit;
        }
        if (!in_array($reason, $validReasons)) {
            echo json_encode(['error' => 'Invalid reason for item #' . ($idx + 1) . '.']);
            exit;
        }
    }

    // Ensure uploads directory exists for loss images
    $uploadDir = __DIR__ . '/../staff/uploads/stock_loss/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    // Try to add image_path column if not exist
    $connect->query("ALTER TABLE `stockadj` ADD COLUMN `image_path` VARCHAR(255) DEFAULT NULL");

    $connect->begin_transaction();

    try {
        $curDate = date('Y-m-d');
        $curTime = date('H:i:s');
        $batchId = 'LOSS' . date('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $recorded = 0;

        foreach ($items as $idx => $item) {
            $barcode = trim($item['barcode']);
            $qty = floatval($item['qty']);
            $reason = trim($item['reason']);
            $remark = trim($item['remark'] ?? '');

            // Look up product name
            $stmt = $connect->prepare("SELECT `name` FROM `PRODUCTS` WHERE `barcode` = ? LIMIT 1");
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception('Product not found: ' . $barcode);
            }
            $product = $result->fetch_assoc();
            $stmt->close();

            $negQty = -$qty;
            $desc = substr($product['name'], 0, 48);
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

            // Insert stock adjustment
            $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`BARCODE`,`PDESC`,`QTYADJ`,`REMARK`,`LOSS_REASON`,`image_path`) VALUES ('STOCKLOSS',?,?,?,?,?,?,?,?,?,?,?)");
            $adjStmt->bind_param("sssssssdsss", $adminUser, $adminUser, $curDate, $curTime, $salnum, $barcode, $desc, $negQty, $remark, $reason, $imagePath);
            if (!$adjStmt->execute()) {
                throw new Exception('Failed to insert adjustment for ' . $barcode . ': ' . $connect->error);
            }
            $adjStmt->close();

            // Deduct from PRODUCTS.qoh
            $qohStmt = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) - ? WHERE `barcode` = ?");
            $qohStmt->bind_param("ds", $qty, $barcode);
            $qohStmt->execute();
            $qohStmt->close();

            $recorded++;
        }

        $connect->commit();
        echo json_encode(['success' => $recorded . ' product(s) stock loss recorded successfully.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action === 'list_sessions') {
    // Group stock loss records by session (batch prefix from SALNUM)
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
        SELECT s.`ID`, s.`SDATE`, s.`STIME`, s.`BARCODE`, s.`PDESC`, s.`QTYADJ`, s.`LOSS_REASON`, s.`REMARK`, s.`USER`, s.`image_path`,
               p.`img1` AS product_image
        FROM `stockadj` s
        LEFT JOIN `PRODUCTS` p ON s.`BARCODE` = p.`barcode`
        WHERE s.`SALNUM` LIKE ?
          AND s.`LOSS_REASON` IS NOT NULL AND s.`LOSS_REASON` != 'ADJUSTMENT'
        ORDER BY s.`ID` ASC
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

    // Get all items in this session to revert QOH
    $stmt = $connect->prepare("
        SELECT `BARCODE`, `QTYADJ`, `image_path`
        FROM `stockadj`
        WHERE `SALNUM` LIKE ?
          AND `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT'
    ");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($r = $result->fetch_assoc()) {
        $items[] = $r;
    }
    $stmt->close();

    if (empty($items)) {
        echo json_encode(['error' => 'Session not found.']);
        exit;
    }

    $connect->begin_transaction();

    try {
        // Revert QOH for each item (QTYADJ is negative, so subtract it to add back)
        foreach ($items as $item) {
            $revertQty = abs((float)$item['QTYADJ']);
            $qohStmt = $connect->prepare("UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) + ? WHERE `barcode` = ?");
            $qohStmt->bind_param("ds", $revertQty, $item['BARCODE']);
            $qohStmt->execute();
            $qohStmt->close();

            // Delete image file if exists
            if (!empty($item['image_path'])) {
                $filePath = __DIR__ . '/../staff/' . $item['image_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }

        // Delete all stockadj records for this session
        $delStmt = $connect->prepare("DELETE FROM `stockadj` WHERE `SALNUM` LIKE ? AND `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT'");
        $delStmt->bind_param("s", $like);
        $delStmt->execute();
        $deleted = $delStmt->affected_rows;
        $delStmt->close();

        $connect->commit();
        echo json_encode(['success' => $deleted . ' item(s) deleted and QOH reverted successfully.']);

    } catch (Exception $e) {
        $connect->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
