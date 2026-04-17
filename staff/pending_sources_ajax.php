<?php
require_once __DIR__ . '/session_security.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$barcode = trim($_POST['barcode'] ?? $_GET['barcode'] ?? '');
if ($barcode === '') {
    echo json_encode(['error' => 'Missing barcode']);
    exit;
}

$stmt = $connect->prepare("
    SELECT SALNUM, NAME, SDATE, TTIME, QTY, TXTTO, PDESC
    FROM `orderlist`
    WHERE STATUS = 'PENDING' AND PTYPE = 'PURCHASE' AND QTY > 0 AND BARCODE = ?
    ORDER BY SDATE DESC, TTIME DESC
");
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();

$sources = [];
$totalQty = 0;
$pdesc = '';
while ($row = $result->fetch_assoc()) {
    $qty = (float)$row['QTY'];
    $totalQty += $qty;
    if ($pdesc === '' && !empty($row['PDESC'])) {
        $pdesc = $row['PDESC'];
    }
    $sources[] = [
        'SALNUM' => $row['SALNUM'],
        'NAME'   => $row['NAME'],
        'SDATE'  => $row['SDATE'],
        'TTIME'  => $row['TTIME'],
        'QTY'    => $qty,
        'TXTTO'  => $row['TXTTO'],
    ];
}
$stmt->close();

echo json_encode([
    'barcode'   => $barcode,
    'pdesc'     => $pdesc,
    'total_qty' => $totalQty,
    'sources'   => $sources,
]);
