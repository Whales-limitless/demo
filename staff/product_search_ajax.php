<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'dbconnection.php';
$connect->set_charset("utf8mb4");

$search = trim($_GET['q'] ?? '');

if ($search === '') {
    echo json_encode(['products' => []]);
    exit;
}

$like = '%' . $search . '%';

// Search by product name only.
// No LOWER() wrapping — relies on utf8mb4_unicode_ci collation for case-insensitive matching.
// LEFT JOIN directly on category table with indexed cat_code for fast lookups.
$stmt = $connect->prepare("
    SELECT p.`id`, p.`barcode`, p.`name`, p.`stkcode`, p.`img1` AS image,
           p.`cat_code`, p.`rack`, COALESCE(p.`qoh`, 0) AS qoh,
           c.`cat_name` AS category_name
    FROM `PRODUCTS` p
    LEFT JOIN `category` c ON p.`cat_code` = c.`cat_code`
    WHERE p.`name` LIKE ?
      AND (p.`checked` != 'N' OR p.`checked` IS NULL)
    ORDER BY p.`name` ASC
    LIMIT 20
");
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
$seen = [];
while ($row = $result->fetch_assoc()) {
    // Deduplicate in case LEFT JOIN on category returns multiple rows
    if (isset($seen[$row['id']])) continue;
    $seen[$row['id']] = true;

    $row['id'] = (int)$row['id'];
    $row['qoh'] = (int)$row['qoh'];
    $row['inStock'] = $row['qoh'] > 0;
    $products[] = $row;
}
$stmt->close();

echo json_encode(['products' => $products]);
?>
