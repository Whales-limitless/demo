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

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
