<?php
require_once __DIR__ . '/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'dbconnection.php';
$connect->set_charset("utf8mb4");

$input = json_decode(file_get_contents('php://input'), true);
$barcodes = $input['barcodes'] ?? [];

if (empty($barcodes) || !is_array($barcodes)) {
    echo json_encode(['blocked' => false]);
    exit;
}

// Sanitize barcodes
$barcodes = array_map(function($b) use ($connect) {
    return $connect->real_escape_string(trim($b));
}, $barcodes);
$barcodes = array_filter($barcodes, function($b) { return $b !== ''; });

if (empty($barcodes)) {
    echo json_encode(['blocked' => false]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($barcodes), '?'));
$stmt = $connect->prepare("SELECT DISTINCT sti.`barcode`, p.`name`, st.`session_code`
    FROM `stock_take_item` sti
    INNER JOIN `stock_take` st ON st.`id` = sti.`stock_take_id` AND st.`status` IN ('DRAFT', 'SUBMITTED')
    LEFT JOIN `PRODUCTS` p ON p.`barcode` = sti.`barcode`
    WHERE sti.`barcode` IN ($placeholders)");
$types = str_repeat('s', count($barcodes));
$values = array_values($barcodes);
$stmt->bind_param($types, ...$values);
$stmt->execute();
$result = $stmt->get_result();

$blockedItems = [];
while ($r = $result->fetch_assoc()) {
    $blockedItems[] = [
        'barcode' => $r['barcode'],
        'name' => $r['name'] ?? $r['barcode'],
        'session_code' => $r['session_code']
    ];
}
$stmt->close();

echo json_encode([
    'blocked' => count($blockedItems) > 0,
    'blocked_items' => $blockedItems
]);
?>
