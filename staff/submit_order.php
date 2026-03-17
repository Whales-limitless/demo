<?php
require_once __DIR__ . '/session_security.php';
require_once 'dbconnection.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get logged-in user info for ACCODE and NAME
$userAccode = $_SESSION['user_code'] ?? '';
$userName = $_SESSION['user_name'] ?? '';

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

$orderType = isset($input['orderType']) ? strtoupper(clean($connect, $input['orderType'])) : 'PURCHASE';
$txtTo = isset($input['txtTo']) ? clean($connect, $input['txtTo']) : '';
$items = isset($input['items']) ? $input['items'] : [];

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'No items in order']);
    exit;
}

// Check if any items are in active stock take sessions (DRAFT/SUBMITTED)
$orderBarcodes = array_map(function($item) use ($connect) {
    return clean($connect, $item['barcode'] ?? '');
}, $items);
$orderBarcodes = array_filter($orderBarcodes, function($b) { return $b !== ''; });

if (!empty($orderBarcodes)) {
    $stPlaceholders = implode(',', array_fill(0, count($orderBarcodes), '?'));
    $stStmt = $connect->prepare("SELECT DISTINCT sti.`barcode`, p.`name`, st.`session_code`
        FROM `stock_take_item` sti
        INNER JOIN `stock_take` st ON st.`id` = sti.`stock_take_id` AND st.`status` IN ('DRAFT', 'SUBMITTED')
        LEFT JOIN `PRODUCTS` p ON p.`barcode` = sti.`barcode`
        WHERE sti.`barcode` IN ($stPlaceholders)");
    $stTypes = str_repeat('s', count($orderBarcodes));
    $stValues = array_values($orderBarcodes);
    $stStmt->bind_param($stTypes, ...$stValues);
    $stStmt->execute();
    $stResult = $stStmt->get_result();
    $blockedItems = [];
    while ($r = $stResult->fetch_assoc()) {
        $blockedItems[] = ($r['name'] ?? $r['barcode']) . ' (' . $r['session_code'] . ')';
    }
    $stStmt->close();

    if (!empty($blockedItems)) {
        echo json_encode([
            'success' => false,
            'error' => 'Cannot proceed. The following product(s) are currently under active stock take: ' . implode(', ', $blockedItems) . '. Please complete or approve the stock take session first.',
            'stock_take_blocked' => true,
            'blocked_items' => $blockedItems
        ]);
        exit;
    }
}

// Generate unique SALNUM: PW + YYYYMMDD + HHMMSS + 3-digit random
$salnum = 'PW' . date('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

$curDate = date('Y-m-d');
$curTime = date('H:i:s');
$ptype = ($orderType === 'STOCKIN') ? 'STOCKIN' : 'PURCHASE';
$outlet = 'WEB';
$branchCode = $_SESSION['user_branch_code'] ?? ($_SESSION['user_outlet'] ?? '');

$insertedCount = 0;
$errors = [];

foreach ($items as $item) {
    $barcode = clean($connect, $item['barcode'] ?? '');
    $name = clean($connect, $item['name'] ?? '');
    $sku = clean($connect, $item['sku'] ?? '');
    $qty = intval($item['qty'] ?? 1);

    if ($qty < 1) $qty = 1;

    $escapedAccode = mysqli_real_escape_string($connect, $userAccode);
    $escapedUserName = mysqli_real_escape_string($connect, $userName);

    $escapedBranch = mysqli_real_escape_string($connect, $branchCode);

    $sql = "INSERT INTO `orderlist` (OUTLET, SDATE, ACCODE, NAME, SALNUM, BARCODE, PDESC, QTY, PTYPE, TRANSNO, TDATE, TTIME, STATUS, PRINT, view_status, ADMINRMK, SOUND, TXTTO, branch_code)
            VALUES (
                '$outlet',
                '$curDate',
                '$escapedAccode',
                '$escapedUserName',
                '$salnum',
                '" . mysqli_real_escape_string($connect, $barcode) . "',
                '" . mysqli_real_escape_string($connect, $name) . "',
                '$qty',
                '$ptype',
                '',
                '$curDate',
                '$curTime',
                'PENDING',
                0,
                '0',
                '',
                '0',
                '" . mysqli_real_escape_string($connect, $txtTo) . "',
                '$escapedBranch'
            )";

    if (mysqli_query($connect, $sql)) {
        $insertedCount++;

        // Feature 2b: Auto-update PRODUCTS.qoh for STOCKIN orders
        if ($ptype === 'STOCKIN' && $barcode !== '' && $qty > 0) {
            $updateQoh = "UPDATE `PRODUCTS` SET `qoh` = COALESCE(`qoh`, 0) + $qty WHERE `barcode` = '" . mysqli_real_escape_string($connect, $barcode) . "'";
            mysqli_query($connect, $updateQoh);
        }
    } else {
        $errors[] = "Failed to insert item: " . $name . " - " . mysqli_error($connect);
    }
}

if ($insertedCount > 0 && empty($errors)) {
    echo json_encode(['success' => true, 'salnum' => $salnum, 'inserted' => $insertedCount]);
} elseif ($insertedCount > 0) {
    echo json_encode(['success' => true, 'salnum' => $salnum, 'inserted' => $insertedCount, 'warnings' => $errors]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to insert any items', 'details' => $errors]);
}
?>
