<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'dbconnection.php';
$connect->set_charset("utf8mb4");

// Trend config
$trendConfig = null;
$trendMap = [];

$tcRes = mysqli_query($connect, "SELECT * FROM `product_trend_config` WHERE `is_active` = 1 LIMIT 1");
if ($tcRes && $tcRow = mysqli_fetch_assoc($tcRes)) {
    $trendConfig = $tcRow;
    $df = mysqli_real_escape_string($connect, $tcRow['date_from']);
    $dt = mysqli_real_escape_string($connect, $tcRow['date_to']);

    $orderRes = mysqli_query($connect, "
        SELECT BARCODE, SUM(ABS(QTY)) AS total_ordered
        FROM `orderlist`
        WHERE `SDATE` BETWEEN '$df' AND '$dt'
          AND `STATUS` != 'DELETED'
        GROUP BY `BARCODE`
    ");
    if ($orderRes) {
        while ($oRow = mysqli_fetch_assoc($orderRes)) {
            $trendMap[$oRow['BARCODE']] = (int)$oRow['total_ordered'];
        }
    }
}

// Fetch all UOM conversions keyed by barcode
$uomMap = [];
$uomRes = mysqli_query($connect, "SELECT `barcode`, `from_uom`, `to_uom`, `conversion_factor` FROM `uom_conversion` ORDER BY `barcode`, `from_uom`");
if ($uomRes) {
    while ($uRow = mysqli_fetch_assoc($uomRes)) {
        $uRow['conversion_factor'] = floatval($uRow['conversion_factor']);
        $uomMap[$uRow['barcode']][] = $uRow;
    }
}

// Fetch all categories and subcategories in one query
$catSubMap = []; // cat_code => ['name' => ..., 'sort' => ..., 'subs' => [sub_code => ['name' => ..., 'sort' => ...]]]
$catSubRes = mysqli_query($connect, "SELECT cat_code, cat_name, sub_code, sub_cat, MIN(sort_no) AS sort_order FROM category GROUP BY cat_code, cat_name, sub_code, sub_cat ORDER BY MIN(sort_no) ASC, cat_name ASC, sub_cat ASC");
while ($row = mysqli_fetch_assoc($catSubRes)) {
    $cc = $row['cat_code'];
    if (!isset($catSubMap[$cc])) {
        $catSubMap[$cc] = ['name' => $row['cat_name'], 'sort' => $row['sort_order'], 'subs' => []];
    }
    $catSubMap[$cc]['subs'][$row['sub_code']] = ['name' => $row['sub_cat'], 'sort' => $row['sort_order']];
}

// Fetch all visible products in one query
$prodMap = []; // cat_code => sub_code => [products]
$prodRes = mysqli_query($connect, "SELECT id, name, description, stkcode AS sku, barcode, img1 AS image, rack AS rack_location, rack_updated_at, stock_in_at, IFNULL(qoh, 0) AS quantity, cat_code, sub_code FROM PRODUCTS WHERE (checked != 'N' OR checked IS NULL) ORDER BY name ASC");
while ($prod = mysqli_fetch_assoc($prodRes)) {
    $prod['id'] = intval($prod['id']);
    $prod['quantity'] = intval($prod['quantity']);
    $prod['inStock'] = $prod['quantity'] > 0;

    if ($trendConfig) {
        $ordered = $trendMap[$prod['barcode']] ?? 0;
        if ($ordered >= (int)$trendConfig['green_min']) {
            $prod['trend'] = 'green';
        } elseif ($ordered >= (int)$trendConfig['yellow_min']) {
            $prod['trend'] = 'yellow';
        } elseif ($ordered >= (int)$trendConfig['red_min']) {
            $prod['trend'] = 'red';
        } else {
            $prod['trend'] = 'black';
        }
        $prod['trend_qty'] = $ordered;
    } else {
        $prod['trend'] = null;
        $prod['trend_qty'] = 0;
    }

    $prod['uom_conversions'] = $uomMap[$prod['barcode']] ?? [];

    $cc = $prod['cat_code'];
    $sc = $prod['sub_code'];
    unset($prod['cat_code'], $prod['sub_code']);
    $prodMap[$cc][$sc][] = $prod;
}

// Assemble the response structure
$allCategories = [];
foreach ($catSubMap as $catCode => $catData) {
    $subcategories = [];
    foreach ($catData['subs'] as $subCode => $subData) {
        $products = $prodMap[$catCode][$subCode] ?? [];
        if (count($products) > 0) {
            $subcategories[] = [
                'id' => $subCode,
                'name' => $subData['name'],
                'products' => $products
            ];
        }
    }
    if (count($subcategories) > 0) {
        $allCategories[] = [
            'id' => $catCode,
            'name' => $catData['name'],
            'subcategories' => $subcategories
        ];
    }
}

// Fetch barcodes blocked by active stock take sessions (DRAFT or SUBMITTED)
$stockTakeBarcodes = [];
$stRes = mysqli_query($connect, "SELECT DISTINCT sti.`barcode` FROM `stock_take_item` sti INNER JOIN `stock_take` st ON st.`id` = sti.`stock_take_id` AND st.`status` IN ('DRAFT', 'SUBMITTED')");
if ($stRes) {
    while ($stRow = mysqli_fetch_assoc($stRes)) {
        $stockTakeBarcodes[] = $stRow['barcode'];
    }
}

echo json_encode(['categories' => $allCategories, 'stock_take_barcodes' => $stockTakeBarcodes]);
?>
