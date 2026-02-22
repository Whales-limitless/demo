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

if ($search === '' || strlen($search) < 1) {
    echo json_encode(['products' => []]);
    exit;
}

$like = '%' . strtolower($search) . '%';

$stmt = $connect->prepare("
    SELECT p.`id`, p.`barcode`, p.`name`, p.`stkcode`, p.`img1` AS image,
           p.`cat_code`, p.`rack`, COALESCE(p.`qoh`, 0) AS qoh,
           c.`cat_name` AS category_name
    FROM `PRODUCTS` p
    LEFT JOIN (
        SELECT `cat_code`, `cat_name` FROM `category` GROUP BY `cat_code`, `cat_name`
    ) c ON p.`cat_code` = c.`cat_code`
    WHERE (LOWER(p.`name`) LIKE ? OR LOWER(p.`barcode`) LIKE ?)
      AND (p.`checked` != 'N' OR p.`checked` IS NULL)
    ORDER BY
        CASE WHEN LOWER(p.`barcode`) = LOWER(?) THEN 0 ELSE 1 END,
        p.`name` ASC
    LIMIT 20
");
$stmt->bind_param("sss", $like, $like, $search);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['qoh'] = (int)$row['qoh'];
    $row['inStock'] = $row['qoh'] > 0;
    $products[] = $row;
}
$stmt->close();

echo json_encode(['products' => $products]);
?>
