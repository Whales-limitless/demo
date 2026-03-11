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

// Normalize quote variants: replace " (double quote) with '' (two single quotes)
// so that searching 7" also matches 7'' and vice versa.
// Also normalize curly/smart quotes and prime symbols to their ASCII equivalents.
$normalizedSearch = $search;
$normalizedSearch = str_replace(["\u{201C}", "\u{201D}", "\u{2033}", "\u{FF02}"], '"', $normalizedSearch); // smart double quotes, double prime, fullwidth
$normalizedSearch = str_replace(["\u{2018}", "\u{2019}", "\u{2032}", "\u{FF07}"], "'", $normalizedSearch); // smart single quotes, prime, fullwidth
// Build alternate search: swap " ↔ '' so both forms match
$altSearch = str_replace('"', "''", $normalizedSearch);
$altSearch2 = str_replace("''", '"', $normalizedSearch);
$normalizedLike = '%' . $normalizedSearch . '%';
$altLike = '%' . $altSearch . '%';
$altLike2 = '%' . $altSearch2 . '%';

// Search by product name only.
// No LOWER() wrapping — relies on utf8mb4_unicode_ci collation for case-insensitive matching.
// LEFT JOIN directly on category table with indexed cat_code for fast lookups.
$stmt = $connect->prepare("
    SELECT p.`id`, p.`barcode`, p.`name`, p.`stkcode`, p.`img1` AS image,
           p.`cat_code`, p.`rack`, COALESCE(p.`qoh`, 0) AS qoh,
           c.`cat_name` AS category_name
    FROM `PRODUCTS` p
    LEFT JOIN `category` c ON p.`cat_code` = c.`cat_code`
    WHERE (p.`name` LIKE ? OR p.`name` LIKE ? OR p.`name` LIKE ?)
      AND (p.`checked` != 'N' OR p.`checked` IS NULL)
    ORDER BY p.`name` ASC
    LIMIT 20
");
$stmt->bind_param("sss", $normalizedLike, $altLike, $altLike2);
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
