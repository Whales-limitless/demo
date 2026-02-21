<?php
require_once 'dbconnection.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
    exit;
}

$orderType = isset($input['orderType']) ? strtoupper(clean($connect, $input['orderType'])) : 'PURCHASE';
$items = isset($input['items']) ? $input['items'] : [];

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'No items in order']);
    exit;
}

// Generate unique SALNUM: PW + YYYYMMDD + HHMMSS + 3-digit random
$salnum = 'PW' . date('YmdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

$curDate = date('Y-m-d');
$curTime = date('H:i:s');
$ptype = ($orderType === 'STOCKIN') ? 'STOCKIN' : 'PURCHASE';
$outlet = 'WEB';

$insertedCount = 0;
$errors = [];

foreach ($items as $item) {
    $barcode = clean($connect, $item['barcode'] ?? '');
    $name = clean($connect, $item['name'] ?? '');
    $sku = clean($connect, $item['sku'] ?? '');
    $qty = intval($item['qty'] ?? 1);

    if ($qty < 1) $qty = 1;

    // Look up product price from PRODUCTS table
    $retail = 0;
    $amount = 0;
    if ($barcode !== '') {
        $priceResult = mysqli_query($connect, "SELECT oriprice, disprice FROM PRODUCTS WHERE barcode = '" . mysqli_real_escape_string($connect, $barcode) . "' LIMIT 1");
        if ($priceResult && $priceRow = mysqli_fetch_assoc($priceResult)) {
            // Use discount price if available, otherwise original price
            $retail = ($priceRow['disprice'] > 0) ? $priceRow['disprice'] : $priceRow['oriprice'];
            $amount = $retail * $qty;
        }
    }

    $sql = "INSERT INTO `orderlist` (OUTLET, SDATE, ACCODE, NAME, SALNUM, BARCODE, PDESC, QTY, RETAIL, AMOUNT, REMARK, REDEEM, BILL, DELIVERY, PTYPE, TRANSNO, TDATE, TTIME, STATUS, PRINT, view_status, ADMINRMK, SOUND, TXTTO)
            VALUES (
                '$outlet',
                '$curDate',
                '',
                '',
                '$salnum',
                '" . mysqli_real_escape_string($connect, $barcode) . "',
                '" . mysqli_real_escape_string($connect, $name) . "',
                '$qty',
                '$retail',
                '$amount',
                '',
                '',
                '',
                '',
                '$ptype',
                '',
                '$curDate',
                '$curTime',
                'PENDING',
                0,
                '0',
                '',
                '0',
                ''
            )";

    if (mysqli_query($connect, $sql)) {
        $insertedCount++;
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
