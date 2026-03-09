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

// Fetch all categories with their subcategories and products
$cat_result = mysqli_query($connect, "SELECT DISTINCT cat_code, cat_name, MIN(sort_no) AS sort_order FROM category GROUP BY cat_code, cat_name ORDER BY sort_order ASC, cat_name ASC");
$allCategories = [];
while ($cat = mysqli_fetch_assoc($cat_result)) {
    $sub_result = mysqli_query($connect, "SELECT DISTINCT sub_code, sub_cat, MIN(sort_no) AS sort_order FROM category WHERE cat_code = '" . mysqli_real_escape_string($connect, $cat['cat_code']) . "' GROUP BY sub_code, sub_cat ORDER BY sort_order ASC, sub_cat ASC");
    $subcategories = [];
    while ($sub = mysqli_fetch_assoc($sub_result)) {
        $prod_result = mysqli_query($connect, "SELECT id, name, stkcode AS sku, barcode, img1 AS image, rack AS rack_location, IFNULL(qoh, 0) AS quantity FROM PRODUCTS WHERE cat_code = '" . mysqli_real_escape_string($connect, $cat['cat_code']) . "' AND sub_code = '" . mysqli_real_escape_string($connect, $sub['sub_code']) . "' ORDER BY name ASC");
        $products = [];
        while ($prod = mysqli_fetch_assoc($prod_result)) {
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

            $products[] = $prod;
        }
        if (count($products) > 0) {
            $subcategories[] = [
                'id' => $sub['sub_code'],
                'name' => $sub['sub_cat'],
                'products' => $products
            ];
        }
    }
    if (count($subcategories) > 0) {
        $allCategories[] = [
            'id' => $cat['cat_code'],
            'name' => $cat['cat_name'],
            'subcategories' => $subcategories
        ];
    }
}

echo json_encode(['categories' => $allCategories]);
?>
