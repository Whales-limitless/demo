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

$action = $_POST['action'] ?? '';
$staffName = $_SESSION['user_name'] ?? 'Staff';
$staffOutlet = $_SESSION['user_outlet'] ?? 'WEB';

if ($action === 'search') {
    $q = trim($_POST['q'] ?? '');
    if ($q === '') {
        echo json_encode([]);
        exit;
    }
    $like = '%' . $q . '%';
    $stmt = $connect->prepare("SELECT `barcode`, `name`, COALESCE(`qoh`, 0) AS qoh FROM `PRODUCTS` WHERE (`barcode` LIKE ? OR `name` LIKE ?) AND (`checked` != 'N' OR `checked` IS NULL) ORDER BY CASE WHEN `barcode` = ? THEN 0 ELSE 1 END, `name` ASC LIMIT 20");
    $stmt->bind_param("sss", $like, $like, $q);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    while ($r = $result->fetch_assoc()) {
        $products[] = $r;
    }
    $stmt->close();
    echo json_encode($products);

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
        echo json_encode(['name' => $row['name'], 'qoh' => $row['qoh']]);
    } else {
        echo json_encode(['name' => '', 'qoh' => 0, 'error' => 'Product not found']);
    }
    $stmt->close();

} elseif ($action === 'record') {
    $barcode = trim($_POST['barcode'] ?? '');
    $qty = floatval($_POST['qty'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

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
        $negQty = -$qty;
        $desc = substr($product['name'], 0, 48);

        $adjStmt = $connect->prepare("INSERT INTO `stockadj` (`IP`,`ACCODE`,`USER`,`OUTLET`,`SDATE`,`STIME`,`SALNUM`,`MNO`,`BARCODE`,`PDESC`,`LOOSE`,`PGROUP`,`PRODTYPE`,`QTYADJ`,`SERIALNUMBER`,`REMARK`,`LOSS_REASON`) VALUES ('','STOCKLOSS',?,?,?,?,?,'',?,?,0,'','',?,'',?,?)");
        $adjStmt->bind_param("sssssssdss", $staffName, $staffOutlet, $curDate, $curTime, $salnum, $barcode, $desc, $negQty, $remark, $reason);
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

} elseif ($action === 'recent') {
    // Get recent stock losses recorded by this staff
    $losses = [];
    $stmt = $connect->prepare("SELECT `SDATE`, `BARCODE`, `PDESC`, `QTYADJ`, `LOSS_REASON`, `REMARK`, `USER` FROM `stockadj` WHERE `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT' AND `USER` = ? ORDER BY `ID` DESC LIMIT 50");
    $stmt->bind_param("s", $staffOutlet);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) {
        $losses[] = $r;
    }
    $stmt->close();

    // If no results for outlet, get all recent
    if (empty($losses)) {
        $result = $connect->query("SELECT `SDATE`, `BARCODE`, `PDESC`, `QTYADJ`, `LOSS_REASON`, `REMARK`, `USER` FROM `stockadj` WHERE `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT' ORDER BY `ID` DESC LIMIT 50");
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $losses[] = $r;
            }
        }
    }
    echo json_encode($losses);

} else {
    echo json_encode(['error' => 'Invalid action.']);
}
?>
