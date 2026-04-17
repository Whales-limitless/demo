<?php
require_once __DIR__ . '/session_security.php';
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'dbconnection.php';

$cat_code = isset($_GET['cat']) ? clean($connect, $_GET['cat']) : '';

// Fetch category name from the category table
$cat_result = mysqli_query($connect, "SELECT cat_name FROM category WHERE cat_code = '" . mysqli_real_escape_string($connect, $cat_code) . "' LIMIT 1");
$cat_row = mysqli_fetch_assoc($cat_result);

if (!$cat_row) {
    header('Location: index.php');
    exit;
}

$category = ['id' => $cat_code, 'name' => $cat_row['cat_name']];

// ===================== TREND CONFIG =====================
// Load active trend config and compute order totals per barcode
$trendConfig = null;
$trendMap = []; // barcode => { color, total_ordered }

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

// Pending qty: use short-lived file cache shared with search endpoint
$pendingQtyMap = [];
$pendingCacheDir = sys_get_temp_dir() . '/pw_product_cache';
if (!is_dir($pendingCacheDir)) { @mkdir($pendingCacheDir, 0755, true); }
$pendingCacheFile = $pendingCacheDir . '/pending_qty.json';
$pendingCacheTTL = 10; // seconds

if (file_exists($pendingCacheFile) && (time() - filemtime($pendingCacheFile)) < $pendingCacheTTL) {
    $pendingQtyMap = json_decode(file_get_contents($pendingCacheFile), true) ?: [];
} else {
    $pendingRes = mysqli_query($connect, "SELECT BARCODE, SUM(QTY) AS pending_qty FROM `orderlist` WHERE STATUS = 'PENDING' AND PTYPE = 'PURCHASE' AND QTY > 0 GROUP BY BARCODE");
    if ($pendingRes) {
        while ($pRow = mysqli_fetch_assoc($pendingRes)) {
            $pendingQtyMap[$pRow['BARCODE']] = intval($pRow['pending_qty']);
        }
    }
    @file_put_contents($pendingCacheFile, json_encode($pendingQtyMap));
}

// Fetch subcategories for this category
$escaped_cat = mysqli_real_escape_string($connect, $cat_code);
$sub_result = mysqli_query($connect, "SELECT DISTINCT sub_code, sub_cat, MIN(sort_no) AS sort_order FROM category WHERE cat_code = '$escaped_cat' GROUP BY sub_code, sub_cat ORDER BY sort_order ASC, sub_cat ASC");
$subMap = [];
while ($sub = mysqli_fetch_assoc($sub_result)) {
    $subMap[$sub['sub_code']] = ['name' => $sub['sub_cat'], 'products' => []];
}

// Fetch all products for this category in one query
$prod_result = mysqli_query($connect, "SELECT id, name, description, stkcode AS sku, barcode, img1 AS image, rack AS rack_location, rack_updated_at, stock_in_at, IFNULL(qoh, 0) AS quantity, sub_code FROM PRODUCTS WHERE cat_code = '$escaped_cat' AND (checked != 'N' OR checked IS NULL) ORDER BY name ASC");
while ($prod = mysqli_fetch_assoc($prod_result)) {
    $prod['id'] = intval($prod['id']);
    $prod['quantity'] = intval($prod['quantity']);
    $pending = $pendingQtyMap[$prod['barcode']] ?? 0;
    $prod['pending_qty'] = $pending;
    $prod['available_qty'] = max(0, $prod['quantity'] - $pending);
    $prod['inStock'] = $prod['available_qty'] > 0;

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

    $sc = $prod['sub_code'];
    unset($prod['sub_code']);
    if (isset($subMap[$sc])) {
        $subMap[$sc]['products'][] = $prod;
    }
}

$subcategories = [];
foreach ($subMap as $subCode => $subData) {
    $subcategories[] = [
        'id' => $subCode,
        'name' => $subData['name'],
        'products' => $subData['products']
    ];
}
// Fetch barcodes blocked by active stock take sessions (DRAFT or SUBMITTED)
$stockTakeBarcodes = [];
$stRes = mysqli_query($connect, "SELECT DISTINCT sti.`barcode` FROM `stock_take_item` sti INNER JOIN `stock_take` st ON st.`id` = sti.`stock_take_id` AND st.`status` IN ('DRAFT', 'SUBMITTED')");
if ($stRes) {
    while ($stRow = mysqli_fetch_assoc($stRes)) {
        $stockTakeBarcodes[] = $stRow['barcode'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($category['name']); ?> - Inventory</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<link rel="stylesheet" href="components.css">
<link rel="stylesheet" href="pending_sources.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary: #C8102E;
  --primary-dark: #a00d24;
  --surface: #ffffff;
  --bg: #f3f4f6;
  --text: #1a1a1a;
  --text-muted: #6b7280;
  --green: #16a34a;
  --green-light: #f0fdf4;
  --red-light: #fef2f2;
  --radius: 14px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
  --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

html { scroll-behavior: smooth; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; }

.main { max-width: 1200px; margin: 0 auto; padding: 20px 16px 100px; }

.back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 500; margin-bottom: 12px; transition: color var(--transition); }
@media (hover: hover) and (pointer: fine) { .back-link:hover { color: var(--primary); } }

.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }

.filter-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--surface); border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 16px; font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; cursor: pointer; transition: all var(--transition); color: var(--text); flex-shrink: 0; }
.filter-btn:hover { border-color: var(--primary); color: var(--primary); }
.filter-btn .count-pill { background: var(--primary); color: #fff; font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 10px; margin-left: 2px; }

.product-search { flex: 1; min-width: 180px; display: flex; align-items: center; background: var(--surface); border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; transition: border-color var(--transition); }
.product-search:focus-within { border-color: var(--primary); }
.product-search input { border: none; outline: none; padding: 10px 14px; font-family: 'DM Sans', sans-serif; font-size: 14px; width: 100%; color: var(--text); background: transparent; }
.product-search .search-icon { padding: 0 12px; color: var(--text-muted); flex-shrink: 0; }

.active-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.filter-tag { display: inline-flex; align-items: center; gap: 6px; background: #fef2f2; color: var(--primary); border: 1px solid #fecaca; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; animation: fadeUp 0.2s ease; }
.filter-tag button { background: none; border: none; cursor: pointer; color: var(--primary); font-size: 14px; line-height: 1; padding: 0; }

/* Modal */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; justify-content: center; align-items: center; padding: 16px; }
.modal-overlay.active { display: flex; }
.modal { background: var(--surface); border-radius: var(--radius); width: 100%; max-width: 440px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); animation: modalIn 0.25s ease; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; }
.modal-close { background: none; border: none; cursor: pointer; width: 32px; height: 32px; display: grid; place-items: center; border-radius: 6px; color: var(--text-muted); transition: background var(--transition); }
.modal-close:hover { background: var(--bg); }
.modal-search { padding: 12px 20px; border-bottom: 1px solid #e5e7eb; }
.modal-search input { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color var(--transition); }
.modal-search input:focus { border-color: var(--primary); }
.modal-body { overflow-y: auto; padding: 8px 20px; flex: 1; }
.modal-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background var(--transition); font-size: 14px; }
.modal-item:last-child { border-bottom: none; }
.modal-item:hover { background: #f9fafb; margin: 0 -20px; padding: 10px 20px; }
.modal-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0; }
.modal-item label { cursor: pointer; flex: 1; }
.modal-item .subcat-count { background: var(--bg); color: var(--text-muted); font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 10px; flex-shrink: 0; }
.modal-footer { padding: 14px 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; align-items: center; }
.modal-footer .btn-primary { margin-left: auto; }
.btn { font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 14px; border: none; padding: 10px 24px; border-radius: 8px; cursor: pointer; transition: all var(--transition); }
.btn-primary { background: var(--primary); color: #fff; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-ghost { background: none; color: var(--text-muted); }
.btn-ghost:hover { background: var(--bg); }

/* Products */
.category-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; }
.subcat-section { margin-bottom: 32px; }
.subcat-heading { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid var(--primary); display: inline-block; }

.oos-section { margin-top: 40px; padding-top: 24px; border-top: 2px dashed #d1d5db; }
.oos-heading { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }
.oos-count { background: #fee2e2; color: var(--primary); font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 10px; }
.oos-section .product-card { opacity: 1; }

.product-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }

.product-card { background: var(--surface); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; -webkit-tap-highlight-color: transparent; touch-action: manipulation; }
.product-img-wrap { position: relative; overflow: hidden; }
.product-img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; background: var(--bg); }

@media (hover: hover) and (pointer: fine) {
  .product-card { transition: box-shadow var(--transition), transform var(--transition); }
  .product-img { transition: transform 0.4s ease; }
  .product-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
  .product-card:hover .product-img { transform: scale(1.03); }
}

.no-img-product { width: 100%; aspect-ratio: 1; background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 11px; font-weight: 600; text-align: center; padding: 12px; font-family: 'DM Sans', sans-serif; }

.stock-badge { position: absolute; top: 8px; right: 8px; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.03em; }
.stock-badge.in-stock { background: var(--green-light); color: var(--green); border: 1px solid #bbf7d0; }
.stock-badge.out-of-stock { background: var(--red-light); color: var(--primary); border: 1px solid #fecaca; }

/* Trend indicator badge */
.trend-badge { position: absolute; top: 8px; right: 8px; font-size: 9px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 4px; }
.trend-badge .trend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.trend-badge.trend-green { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.trend-badge.trend-green .trend-dot { background: #16a34a; }
.trend-badge.trend-yellow { background: #fefce8; color: #a16207; border: 1px solid #fde68a; }
.trend-badge.trend-yellow .trend-dot { background: #eab308; }
.trend-badge.trend-red { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.trend-badge.trend-red .trend-dot { background: #ef4444; }
.trend-badge.trend-black { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
.trend-badge.trend-black .trend-dot { background: #1a1a1a; }

.product-info { padding: 12px; display: flex; flex-direction: column; flex: 1; }
.product-name { font-size: 13px; font-weight: 600; line-height: 1.4; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; cursor: pointer; }
.product-name:hover { color: var(--primary); }

.product-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 6px; }
.tag { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
.tag-rack { background: #fef3c7; color: #92400e; }
.tag-rack.unset { background: var(--bg); color: var(--text-muted); }
.tag-rack-remark { background: #e0f2fe; color: #0369a1; }
.tag-rack-date { background: #f3e8ff; color: #7c3aed; font-size: 9px; }
.tag-stock-in-date { background: #ecfdf5; color: #059669; font-size: 9px; }
.tag-btn { cursor: pointer; transition: all var(--transition); }
.tag-btn:hover { opacity: 0.8; transform: translateY(-1px); }
.tag-uom { background: #dbeafe; color: #1d4ed8; }
.uom-icon { font-size: 12px; }
.uom-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 500; align-items: flex-end; justify-content: center; }
.uom-modal-overlay.active { display: flex; }
.uom-modal { background: #fff; border-radius: 20px 20px 0 0; width: 100%; max-width: 480px; padding: 0 0 24px; animation: slideUp 0.3s ease; }
.uom-modal-handle { width: 40px; height: 4px; background: #d1d5db; border-radius: 4px; margin: 12px auto 16px; }
.uom-modal-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; text-align: center; color: var(--text); padding: 0 20px; }
.uom-modal-product { font-size: 13px; color: var(--text-muted); text-align: center; margin: 4px 20px 16px; line-height: 1.4; }
.uom-modal-table { width: calc(100% - 40px); margin: 0 20px; border-collapse: collapse; }
.uom-modal-table th { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 12px; text-align: left; border-bottom: 2px solid #e5e7eb; }
.uom-modal-table td { font-size: 14px; font-weight: 600; color: var(--text); padding: 10px 12px; border-bottom: 1px solid #f3f4f6; }
.uom-modal-table tr:last-child td { border-bottom: none; }
.uom-modal-close { display: block; margin: 16px 20px 0; width: calc(100% - 40px); padding: 14px; border: none; border-radius: 12px; background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
.uom-modal-close:hover { background: #e5e7eb; }

.rack-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; justify-content: center; align-items: center; padding: 16px; }
.rack-modal-overlay.active { display: flex; }
.rack-modal { background: var(--surface); border-radius: var(--radius); padding: 24px; max-width: 400px; width: 100%; box-shadow: var(--shadow-lg); animation: fadeUp 0.25s ease; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.rack-modal h3 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 16px; }
.rack-modal label { font-size: 13px; font-weight: 600; color: var(--text); display: block; margin-bottom: 6px; }
.rack-modal input, .rack-modal select { width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color var(--transition); margin-bottom: 12px; }
.rack-modal input:focus, .rack-modal select:focus { border-color: var(--primary); }
.rack-modal-actions { display: flex; gap: 8px; margin-top: 8px; }
.rack-modal-actions button { flex: 1; padding: 12px; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 14px; cursor: pointer; transition: all var(--transition); }
.rack-modal-actions .btn-save { background: var(--primary); color: #fff; }
.rack-modal-actions .btn-save:hover { background: var(--primary-dark); }
.rack-modal-actions .btn-cancel { background: var(--bg); color: var(--text); }
.rack-modal-actions .btn-cancel:hover { background: #e5e7eb; }

.qty-label { font-size: 12px; color: var(--text-muted); margin-bottom: 8px; }
.qty-label span { font-weight: 700; color: var(--text); }

.product-actions { margin-top: auto; padding-top: 8px; }
.qty-row { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
.qty-btn { width: 30px; height: 30px; border: 1px solid #d1d5db; background: var(--surface); border-radius: 6px; font-size: 16px; cursor: pointer; display: grid; place-items: center; transition: all var(--transition); color: var(--text); }
.qty-btn:hover { border-color: var(--primary); color: var(--primary); }
.qty-input { flex: 1; min-width: 0; height: 30px; border: 1px solid #d1d5db; border-radius: 6px; text-align: center; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; outline: none; transition: border-color var(--transition); }
.qty-input:focus { border-color: var(--primary); }

.btn-add-cart { width: 100%; padding: 10px; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 13px; cursor: pointer; transition: all var(--transition); display: flex; align-items: center; justify-content: center; gap: 6px; }
.btn-add-cart.active { background: var(--primary); color: #fff; }
.btn-add-cart.active:hover { background: var(--primary-dark); transform: translateY(-1px); }
.btn-add-cart.disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
.btn-add-cart.stock-take { background: #fef3c7; color: #92400e; cursor: not-allowed; font-size: 11px; }
.cart-feedback { font-size: 12px; text-align: center; height: 16px; margin-top: 4px; }

@media (max-width: 768px) { .main { padding: 16px 12px 80px; } .product-grid { gap: 10px; } .product-name { font-size: 12px; } .product-info { padding: 10px; } .category-title { font-size: 20px; } .toolbar { gap: 8px; } }
@media (min-width: 993px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1200px) { .product-grid { grid-template-columns: repeat(4, 1fr); } }

/* Product Detail Modal */
.detail-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; align-items: center; justify-content: center; padding: 16px; }
.detail-modal-overlay.active { display: flex; }
.detail-modal { background: var(--surface); border-radius: var(--radius); width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg); animation: fadeUp 0.25s ease; }
.detail-modal-img { width: 100%; max-height: 60vh; object-fit: contain; display: block; border-radius: var(--radius) var(--radius) 0 0; background: #f3f4f6; }
.detail-modal-noimg { width: 100%; aspect-ratio: 1; background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 14px; font-weight: 600; border-radius: var(--radius) var(--radius) 0 0; }
.detail-modal-body { padding: 20px; }
.detail-modal-name { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 8px; line-height: 1.3; }
.detail-modal-desc { font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px; white-space: pre-line; }
.detail-modal-desc.empty { font-style: italic; color: #d1d5db; }
.detail-modal-uom-title { font-family: 'Outfit', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.detail-modal-uom-title .uom-icon { font-size: 16px; color: #1d4ed8; }
.detail-modal .uom-modal-table { width: 100%; margin: 0; }
.detail-modal-close { display: block; margin: 16px 20px 20px; width: calc(100% - 40px); padding: 14px; border: none; border-radius: 12px; background: var(--bg); color: var(--text); font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 700; cursor: pointer; transition: background 0.2s; }
.detail-modal-close:hover { background: #e5e7eb; }
.product-img-wrap { cursor: pointer; }

</style>
</head>
<body>

<?php include('navbar.php'); ?>

<main class="main">
  <a href="category.php" class="back-link">
    <svg style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Categories
  </a>

  <h1 class="category-title" id="categoryTitle"><?php echo htmlspecialchars($category['name']); ?></h1>

  <div class="toolbar">
    <button class="filter-btn" id="subcatFilterBtn">
      <svg style="width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46"/></svg>
      Subcategory
      <span class="count-pill" id="filterCount" style="display:none;">0</span>
    </button>
    <div class="product-search">
      <svg class="search-icon" style="width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder="Search products…" id="productSearchInput">
    </div>
  </div>

  <div class="active-filters" id="activeFilters"></div>
  <div id="productSections"></div>
</main>

<!-- Subcategory filter modal -->
<div class="modal-overlay" id="filterModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Filter Subcategories</h3>
      <button class="modal-close" id="modalClose">
        <svg style="width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-search"><input type="text" placeholder="Search subcategories…" id="modalSearchInput"></div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="selectAll">Select All</button>
      <button class="btn btn-ghost" id="clearAll" style="color:var(--primary);">Clear All</button>
      <button class="btn btn-primary" id="applyFilter">Apply</button>
    </div>
  </div>
</div>

<?php include('mobile-bottombar.php'); ?>

<script>
function escHtml(s) {
  var d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}
function escAttr(s) {
  return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function openUomModal(productName, conversions) {
  document.getElementById('uomModalProduct').textContent = productName;
  var tbody = document.getElementById('uomModalBody');
  tbody.innerHTML = conversions.map(function(u) {
    var factor = u.conversion_factor % 1 === 0 ? u.conversion_factor.toFixed(0) : u.conversion_factor;
    return '<tr><td>' + escHtml(u.from_uom) + '</td><td>' + escHtml(u.to_uom) + '</td><td>1 ' + escHtml(u.from_uom) + ' = ' + factor + ' ' + escHtml(u.to_uom) + '</td></tr>';
  }).join('');
  document.getElementById('uomModalOverlay').classList.add('active');
}
function closeUomModal() {
  document.getElementById('uomModalOverlay').classList.remove('active');
}

function openDetailModal(productId) {
  var p = findProduct(productId);
  if (!p) return;

  var imgEl = document.getElementById('detailModalImg');
  var noImgEl = document.getElementById('detailModalNoImg');
  if (p.image) {
    imgEl.src = '/img/' + p.image;
    imgEl.alt = p.name;
    imgEl.style.display = 'block';
    noImgEl.style.display = 'none';
  } else {
    imgEl.style.display = 'none';
    noImgEl.style.display = 'flex';
    noImgEl.textContent = p.sku || 'NO IMAGE';
  }

  document.getElementById('detailModalName').textContent = p.name;

  var descEl = document.getElementById('detailModalDesc');
  if (p.description) {
    descEl.textContent = p.description;
    descEl.classList.remove('empty');
  } else {
    descEl.textContent = 'No description available.';
    descEl.classList.add('empty');
  }

  var uomSection = document.getElementById('detailModalUom');
  if (p.uom_conversions && p.uom_conversions.length > 0) {
    var rows = p.uom_conversions.map(function(u) {
      var factor = u.conversion_factor % 1 === 0 ? u.conversion_factor.toFixed(0) : u.conversion_factor;
      return '<tr><td>' + escHtml(u.from_uom) + '</td><td>' + escHtml(u.to_uom) + '</td><td>1 ' + escHtml(u.from_uom) + ' = ' + factor + ' ' + escHtml(u.to_uom) + '</td></tr>';
    }).join('');
    uomSection.innerHTML = '<div class="detail-modal-uom-title"><span class="uom-icon">&#9878;</span> UOM Conversion</div>' +
      '<table class="uom-modal-table"><thead><tr><th>From</th><th>To</th><th>Conversion</th></tr></thead><tbody>' + rows + '</tbody></table>';
    uomSection.style.display = 'block';
  } else {
    uomSection.style.display = 'none';
  }

  document.getElementById('detailModalOverlay').classList.add('active');
}

function closeDetailModal() {
  document.getElementById('detailModalOverlay').classList.remove('active');
}

var subcategories = <?php echo json_encode($subcategories); ?>;
var highlightProductId = <?php echo isset($_GET['highlight']) ? intval($_GET['highlight']) : 'null'; ?>;
var stockTakeBarcodes = {};
<?php echo json_encode($stockTakeBarcodes); ?>.forEach(function(b) { stockTakeBarcodes[b] = true; });

// All subcategories selected by default
var selectedSubcats = {};
subcategories.forEach(function(sc) { selectedSubcats[sc.id] = true; });

function getProductImage(p) {
  if (p.image) return p.image;
  return null;
}

function formatRackDate(dt) {
  if (!dt) return '';
  var d = new Date(dt.replace(' ', 'T'));
  if (isNaN(d.getTime())) return dt;
  var day = ('0' + d.getDate()).slice(-2);
  var mon = ('0' + (d.getMonth() + 1)).slice(-2);
  var yr = d.getFullYear();
  var hr = ('0' + d.getHours()).slice(-2);
  var min = ('0' + d.getMinutes()).slice(-2);
  return day + '/' + mon + '/' + yr + ' ' + hr + ':' + min;
}

var trendLabels = { green: 'Hot', yellow: 'Moderate', red: 'Slow', black: 'Dead' };

function renderProductCard(p, index) {
  var imgHtml;
  if (p.image) {
    imgHtml = '<img class="product-img" src="/img/' + escAttr(p.image) + '" alt="' + escAttr(p.name) + '" loading="lazy" decoding="async">';
  } else {
    imgHtml = '<div class="no-img-product">' + escHtml(p.sku || 'NO IMAGE') + '</div>';
  }
  var imgWrapOnclick = ' onclick="openDetailModal(' + p.id + ')"';

  // Trend indicator badge (replaces stock badge when trend config is active)
  var badgeHtml;
  if (p.trend) {
    var tLabel = trendLabels[p.trend] || '';
    badgeHtml = '<span class="trend-badge trend-' + p.trend + '"><span class="trend-dot"></span>' + tLabel + '</span>';
  } else {
    // Fallback to stock badge when no trend config is active
    var sc = p.inStock ? 'in-stock' : 'out-of-stock';
    var st = p.inStock ? 'In Stock' : 'Out of Stock';
    badgeHtml = '<span class="stock-badge ' + sc + '">' + st + '</span>';
  }

  var tags = '';
  var rackLabel = p.rack_location ? 'Rack: ' + escHtml(p.rack_location) : 'No Rack';
  var rackClass = p.rack_location ? 'tag tag-rack tag-btn' : 'tag tag-rack unset tag-btn';
  tags += '<span class="' + rackClass + '" onclick="openRackModal(' + p.id + ', \'' + escAttr((p.rack_location || '').replace(/'/g, "\\'")) + '\')">&#9881; ' + rackLabel + '</span>';
  if (!p.rack_location) {
    tags += '<span class="tag tag-rack-remark tag-btn" onclick="openRackRemarkModal(' + p.id + ', \'' + escAttr((p.rack_location || '').replace(/'/g, "\\'")) + '\')">&#9998; Rack Remark</span>';
  }
  if (p.rack_updated_at) {
    tags += '<span class="tag tag-rack-date">Updated: ' + formatRackDate(p.rack_updated_at) + '</span>';
  }
  if (p.stock_in_at) {
    tags += '<span class="tag tag-stock-in-date">Stock In: ' + formatRackDate(p.stock_in_at) + '</span>';
  }
  var isStockTake = stockTakeBarcodes[p.barcode] || false;
  var bc, bt, btnDisabled;
  if (isStockTake) {
    bc = 'stock-take';
    bt = 'Active Stock Take Session';
    btnDisabled = true;
  } else {
    bc = 'active';
    bt = 'Add to Cart';
    btnDisabled = false;
  }

  return '<div class="product-card" data-id="' + p.id + '" data-name="' + escAttr(p.name.toLowerCase()) + '" data-sku="' + escAttr((p.sku || '').toLowerCase()) + '" data-barcode="' + escAttr((p.barcode || '').toLowerCase()) + '">' +
    '<div class="product-img-wrap"' + imgWrapOnclick + '>' + imgHtml + badgeHtml + '</div>' +
    '<div class="product-info">' +
      '<div class="product-name" onclick="openEditNameModal(' + p.id + ')">' + escHtml(p.name) + '</div>' +
      '<div class="product-tags">' + tags + '</div>' +
      '<div class="qty-label">Qty: <span>' + p.available_qty + '</span>' + (p.pending_qty > 0 ? ' <button type="button" class="pending-link" style="font-size:10px;" onclick="showPendingSources(\'' + escAttr(p.barcode || '') + '\', \'' + escAttr(p.name || '') + '\')">(' + p.pending_qty + ' pending)</button>' : '') + '</div>' +
      '<div class="product-actions">' +
        '<div class="qty-row">' +
          '<button class="qty-btn" onclick="updateQty(\'minus\',' + p.id + ')">−</button>' +
          '<input type="number" class="qty-input" id="qty_' + p.id + '" value="1" min="1" max="99">' +
          '<button class="qty-btn" onclick="updateQty(\'plus\',' + p.id + ')">+</button>' +
        '</div>' +
        '<button class="btn-add-cart ' + bc + '" ' + (btnDisabled ? 'disabled' : '') + ' onclick="addToCart(' + p.id + ')">' + bt + '</button>' +
        '<div class="cart-feedback" id="feedback_' + p.id + '"></div>' +
      '</div>' +
    '</div>' +
  '</div>';
}

function renderSections(filteredSubs) {
  var list = filteredSubs || subcategories;
  var sections = document.getElementById('productSections');

  if (list.length === 0) {
    sections.innerHTML = '<div style="text-align:center;padding:40px 0;color:var(--text-muted);font-size:14px;">No subcategories selected.</div>';
    return;
  }

  // If a product was highlighted from search suggestion, show it first
  var highlightHtml = '';
  if (highlightProductId) {
    var hlProduct = null;
    list.forEach(function(sc) {
      sc.products.forEach(function(p) {
        if (p.id === highlightProductId) hlProduct = p;
      });
    });
    if (hlProduct) {
      highlightHtml = '<div class="subcat-section">' +
        '<h3 class="subcat-heading">Search Result</h3>' +
        '<div class="product-grid">' + renderProductCard(hlProduct, 0) + '</div>' +
      '</div>';
    }
    // Clear so it only applies on first render
    highlightProductId = null;
  }

  var allOOS = [];
  var html = list.map(function(sc) {
    var inStock = sc.products.filter(function(p) { return p.inStock; });
    sc.products.filter(function(p) { return !p.inStock; }).forEach(function(p) {
      allOOS.push(Object.assign({}, p, {subcategory: sc.name}));
    });
    if (inStock.length === 0) return '';
    return '<div class="subcat-section" id="sub_' + sc.id + '">' +
      '<h3 class="subcat-heading">' + escHtml(sc.name) + '</h3>' +
      '<div class="product-grid">' + inStock.map(function(p, i) { return renderProductCard(p, i); }).join('') + '</div>' +
    '</div>';
  }).join('');

  var oosHtml = '';
  if (allOOS.length > 0) {
    oosHtml = '<div class="oos-section"><div class="oos-heading">Out of Stock <span class="oos-count">' + allOOS.length + '</span></div>' +
      '<div class="product-grid">' + allOOS.map(function(p, i) { return renderProductCard(p, i); }).join('') + '</div></div>';
  }

  sections.innerHTML = highlightHtml + html + oosHtml;

  var q = document.getElementById('productSearchInput').value;
  if (q) filterProducts(q);
}

// Subcategory filter modal
var filterModal = document.getElementById('filterModal');
var modalBody = document.getElementById('modalBody');
var modalSearch = document.getElementById('modalSearchInput');
var filterCountPill = document.getElementById('filterCount');

function openModal() { renderModalList(); filterModal.classList.add('active'); modalSearch.value = ''; }
function closeModal() { filterModal.classList.remove('active'); }

function renderModalList(query) {
  var q = (query || '').toLowerCase();
  var filtered = subcategories.filter(function(sc) { return sc.name.toLowerCase().indexOf(q) !== -1; });
  modalBody.innerHTML = filtered.map(function(sc) {
    return '<div class="modal-item" onclick="this.querySelector(\'input\').click()">' +
      '<input type="checkbox" id="chk_' + sc.id + '" ' + (selectedSubcats[sc.id] ? 'checked' : '') + ' onclick="event.stopPropagation(); toggleSubcat(' + sc.id + ', this.checked)">' +
      '<label for="chk_' + sc.id + '" onclick="event.preventDefault()">' + sc.name + '</label>' +
      '<span class="subcat-count">' + sc.products.length + '</span>' +
    '</div>';
  }).join('');
  if (!filtered.length) modalBody.innerHTML = '<p style="padding:20px 0;text-align:center;color:var(--text-muted);">No subcategories found</p>';
}

function toggleSubcat(id, checked) { if (checked) selectedSubcats[id] = true; else delete selectedSubcats[id]; }

function applySubcatFilter() {
  var selectedCount = Object.keys(selectedSubcats).length;
  if (selectedCount === subcategories.length || selectedCount === 0) {
    if (selectedCount === 0) renderSections([]);
    else renderSections();
  } else {
    renderSections(subcategories.filter(function(sc) { return selectedSubcats[sc.id]; }));
  }
  renderFilterTags();
  updateFilterCount();
  closeModal();
}

function renderFilterTags() {
  var c = document.getElementById('activeFilters');
  var selectedCount = Object.keys(selectedSubcats).length;
  if (selectedCount === 0 || selectedCount === subcategories.length) { c.innerHTML = ''; return; }
  c.innerHTML = Object.keys(selectedSubcats).map(function(id) {
    var sc = subcategories.find(function(s) { return s.id == id; });
    if (!sc) return '';
    return '<span class="filter-tag">' + sc.name + '<button onclick="removeSubcatFilter(' + id + ')">×</button></span>';
  }).join('');
}

function updateFilterCount() {
  var selectedCount = Object.keys(selectedSubcats).length;
  if (selectedCount > 0 && selectedCount < subcategories.length) {
    filterCountPill.textContent = selectedCount;
    filterCountPill.style.display = 'inline';
  } else {
    filterCountPill.style.display = 'none';
  }
}

function removeSubcatFilter(id) { delete selectedSubcats[id]; applySubcatFilter(); }

document.getElementById('subcatFilterBtn').addEventListener('click', openModal);
document.getElementById('modalClose').addEventListener('click', closeModal);
filterModal.addEventListener('click', function(e) { if (e.target === filterModal) closeModal(); });
modalSearch.addEventListener('input', function() { renderModalList(this.value); });
document.getElementById('applyFilter').addEventListener('click', applySubcatFilter);
document.getElementById('clearAll').addEventListener('click', function() { selectedSubcats = {}; renderModalList(modalSearch.value); });
document.getElementById('selectAll').addEventListener('click', function() { subcategories.forEach(function(sc) { selectedSubcats[sc.id] = true; }); renderModalList(modalSearch.value); });

// Normalize quotes: treat " and '' as interchangeable, normalize smart quotes
function normalizeQuotes(s) {
  s = s.replace(/[\u201C\u201D\u2033\uFF02]/g, '"');
  s = s.replace(/[\u2018\u2019\u2032\uFF07]/g, "'");
  return s;
}

// Product search (filters by name, SKU, or barcode)
function filterProducts(query) {
  var q = normalizeQuotes(query.toLowerCase());
  if (q.length === 0) {
    document.querySelectorAll('.product-card').forEach(function(card) { card.style.display = ''; });
    document.querySelectorAll('.subcat-section, .oos-section').forEach(function(sec) { sec.style.display = ''; });
    return;
  }
  var qAlt = q.replace(/"/g, "''");
  var qAlt2 = q.replace(/''/g, '"');
  document.querySelectorAll('.product-card').forEach(function(card) {
    var name = normalizeQuotes(card.getAttribute('data-name'));
    var sku = normalizeQuotes(card.getAttribute('data-sku'));
    var barcode = normalizeQuotes(card.getAttribute('data-barcode'));
    var nameMatch = name.indexOf(q) !== -1 || name.indexOf(qAlt) !== -1 || name.indexOf(qAlt2) !== -1;
    var skuMatch = sku.indexOf(q) !== -1 || sku.indexOf(qAlt) !== -1 || sku.indexOf(qAlt2) !== -1;
    var barcodeMatch = barcode.indexOf(q) !== -1 || barcode.indexOf(qAlt) !== -1 || barcode.indexOf(qAlt2) !== -1;
    card.style.display = (nameMatch || skuMatch || barcodeMatch) ? '' : 'none';
  });
  document.querySelectorAll('.subcat-section, .oos-section').forEach(function(sec) {
    var any = Array.from(sec.querySelectorAll('.product-card')).some(function(c) { return c.style.display !== 'none'; });
    sec.style.display = any ? '' : 'none';
  });
}

document.getElementById('productSearchInput').addEventListener('input', function() { filterProducts(this.value); });

// Cart functionality
function updateQty(action, id) {
  var input = document.getElementById('qty_' + id);
  var val = parseInt(input.value) || 1;
  if (action === 'plus' && val < 99) input.value = val + 1;
  if (action === 'minus' && val > 1) input.value = val - 1;
}

function findProduct(id) {
  var found = null;
  subcategories.forEach(function(sc) {
    sc.products.forEach(function(p) {
      if (p.id === id) found = p;
    });
  });
  return found;
}

function addToCart(productId) {
  var product = findProduct(productId);
  if (!product) return;

  var qty = parseInt(document.getElementById('qty_' + productId).value) || 1;

  // Read existing cart from sessionStorage
  var cart = [];
  try { cart = JSON.parse(sessionStorage.getItem('cart') || '[]'); } catch(e) {}

  // Check if product already in cart
  var existing = null;
  for (var i = 0; i < cart.length; i++) {
    if (cart[i].id === productId) { existing = cart[i]; break; }
  }

  if (existing) {
    existing.qty = Math.min(existing.qty + qty, product.available_qty);
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      sku: product.sku || '',
      barcode: product.barcode || '',
      img: product.image ? '/img/' + product.image : '',
      rack: product.rack_location || null,
      qty: Math.min(qty, product.available_qty),
      maxQty: product.available_qty,
      checked: true
    });
  }

  sessionStorage.setItem('cart', JSON.stringify(cart));

  // Visual feedback
  var fb = document.getElementById('feedback_' + productId);
  fb.style.color = '#16a34a';
  fb.textContent = 'Added to cart!';

  // Update cart badge
  var badge = document.getElementById('cartBadge');
  badge.textContent = cart.length;

  setTimeout(function() { fb.textContent = ''; }, 2000);
}

// Init cart badge from sessionStorage
(function() {
  try {
    var cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    document.getElementById('cartBadge').textContent = cart.length;
  } catch(e) {}
})();

// ==================== EDIT PRODUCT NAME MODAL ====================
var editNameProductId = null;

function openEditNameModal(productId) {
  var p = findProduct(productId);
  if (!p) return;
  editNameProductId = productId;
  document.getElementById('editNameInput').value = p.name;
  document.getElementById('editNameModalOverlay').classList.add('active');
  document.getElementById('editNameInput').focus();
  document.getElementById('editNameInput').select();
}

function closeEditNameModal() {
  document.getElementById('editNameModalOverlay').classList.remove('active');
  editNameProductId = null;
}

function saveProductName() {
  if (!editNameProductId) return;
  var val = document.getElementById('editNameInput').value.trim();
  if (!val) { alert('Product name cannot be empty'); return; }
  var btn = document.querySelector('#editNameModalOverlay .btn-save');
  btn.disabled = true;
  btn.textContent = 'Saving...';
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'product_name_ajax.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      btn.disabled = false;
      btn.textContent = 'Save';
      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success) {
            updateProductNameInData(editNameProductId, resp.name);
            closeEditNameModal();
          } else {
            alert('Failed: ' + (resp.error || 'Unknown error'));
          }
        } catch(e) { alert('Failed to update product name'); }
      } else {
        alert('Failed to update product name');
      }
    }
  };
  xhr.send('action=update_name&id=' + editNameProductId + '&name=' + encodeURIComponent(val));
}

function updateProductNameInData(productId, newName) {
  subcategories.forEach(function(sc) {
    sc.products.forEach(function(p) {
      if (p.id === productId) {
        p.name = newName;
      }
    });
  });
  var card = document.querySelector('.product-card[data-id="' + productId + '"]');
  if (card) {
    var nameEl = card.querySelector('.product-name');
    if (nameEl) nameEl.textContent = newName;
    card.setAttribute('data-name', newName.toLowerCase());
  }
}

// ==================== RACK MODALS ====================
var rackEditProductId = null;
var rackListCache = null;

function openRackModal(productId, currentRack) {
  rackEditProductId = productId;
  document.getElementById('rackModalSelect').value = currentRack || '';
  document.getElementById('rackModalOverlay').classList.add('active');

  if (rackListCache === null) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'product_rack_ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          rackListCache = JSON.parse(xhr.responseText);
          populateRackSelect(currentRack);
        } catch(e) { rackListCache = []; }
      }
    };
    xhr.send('action=rack_list');
  } else {
    populateRackSelect(currentRack);
  }
}

function populateRackSelect(currentVal) {
  var sel = document.getElementById('rackModalSelect');
  sel.innerHTML = '<option value="">-- No Rack --</option>';
  (rackListCache || []).forEach(function(r) {
    var opt = document.createElement('option');
    opt.value = r.code;
    opt.textContent = r.code + (r.description ? ' - ' + r.description : '');
    if (r.code === currentVal) opt.selected = true;
    sel.appendChild(opt);
  });
}

function closeRackModal() {
  document.getElementById('rackModalOverlay').classList.remove('active');
  rackEditProductId = null;
}

function saveRack() {
  if (!rackEditProductId) return;
  var val = document.getElementById('rackModalSelect').value.trim();
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'product_rack_ajax.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4 && xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          updateProductRackInData(rackEditProductId, resp.rack, resp.rack_updated_at);
          closeRackModal();
        } else {
          alert('Failed: ' + (resp.error || 'Unknown error'));
        }
      } catch(e) { alert('Failed to update rack'); }
    }
  };
  xhr.send('action=update_rack&id=' + rackEditProductId + '&rack=' + encodeURIComponent(val));
}

function openRackRemarkModal(productId, currentRack) {
  rackEditProductId = productId;
  document.getElementById('rackRemarkInput').value = currentRack || '';
  document.getElementById('rackRemarkModalOverlay').classList.add('active');
  document.getElementById('rackRemarkInput').focus();
}

function closeRackRemarkModal() {
  document.getElementById('rackRemarkModalOverlay').classList.remove('active');
  rackEditProductId = null;
}

function saveRackRemark() {
  if (!rackEditProductId) return;
  var val = document.getElementById('rackRemarkInput').value.trim();
  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'product_rack_ajax.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4 && xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          updateProductRackInData(rackEditProductId, resp.rack, resp.rack_updated_at);
          closeRackRemarkModal();
        } else {
          alert('Failed: ' + (resp.error || 'Unknown error'));
        }
      } catch(e) { alert('Failed to update rack remark'); }
    }
  };
  xhr.send('action=update_rack&id=' + rackEditProductId + '&rack=' + encodeURIComponent(val));
}

function updateProductRackInData(productId, newRack, rackUpdatedAt) {
  var nowStr = rackUpdatedAt || new Date().toISOString().slice(0,19).replace('T',' ');
  // Update the data model
  subcategories.forEach(function(sc) {
    sc.products.forEach(function(p) {
      if (p.id === productId) {
        p.rack_location = newRack || null;
        p.rack_updated_at = nowStr;
      }
    });
  });
  // Re-render the specific product card's tags
  var card = document.querySelector('.product-card[data-id="' + productId + '"]');
  if (card) {
    var tagsEl = card.querySelector('.product-tags');
    if (tagsEl) {
      var rackLabel = newRack ? 'Rack: ' + escHtml(newRack) : 'No Rack';
      var rackClass = newRack ? 'tag tag-rack tag-btn' : 'tag tag-rack unset tag-btn';
      var tagsInner = '<span class="' + rackClass + '" onclick="openRackModal(' + productId + ', \'' + (newRack || '').replace(/'/g, "\\'") + '\')">&#9881; ' + rackLabel + '</span>';
      if (!newRack) {
        tagsInner += '<span class="tag tag-rack-remark tag-btn" onclick="openRackRemarkModal(' + productId + ', \'' + (newRack || '').replace(/'/g, "\\'") + '\')">&#9998; Rack Remark</span>';
      }
      tagsInner += '<span class="tag tag-rack-date">Updated: ' + formatRackDate(nowStr) + '</span>';
      tagsEl.innerHTML = tagsInner;
    }
  }
}

// Render
renderSections();
updateFilterCount();
renderFilterTags();

// Close modals on overlay click (deferred until DOM ready)
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('rackModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeRackModal();
  });
  document.getElementById('rackRemarkModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeRackRemarkModal();
  });
  document.getElementById('editNameModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeEditNameModal();
  });
  document.getElementById('editNameInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') saveProductName();
  });
});
</script>

<!-- Edit Product Name Modal -->
<div class="rack-modal-overlay" id="editNameModalOverlay">
  <div class="rack-modal">
    <h3>Edit Product Name</h3>
    <label>Product Name</label>
    <input type="text" id="editNameInput" placeholder="Enter product name...">
    <div class="rack-modal-actions">
      <button class="btn-cancel" onclick="closeEditNameModal()">Cancel</button>
      <button class="btn-save" onclick="saveProductName()">Save</button>
    </div>
  </div>
</div>

<!-- Rack Edit Modal -->
<div class="rack-modal-overlay" id="rackModalOverlay">
  <div class="rack-modal">
    <h3>Rack Management</h3>
    <label>Select Rack</label>
    <select id="rackModalSelect"><option value="">-- No Rack --</option></select>
    <div class="rack-modal-actions">
      <button class="btn-cancel" onclick="closeRackModal()">Cancel</button>
      <button class="btn-save" onclick="saveRack()">Save</button>
    </div>
  </div>
</div>

<!-- Rack Remark Edit Modal -->
<div class="rack-modal-overlay" id="rackRemarkModalOverlay">
  <div class="rack-modal">
    <h3>Edit Rack Remark</h3>
    <label>Rack Remark</label>
    <input type="text" id="rackRemarkInput" placeholder="Enter rack remark...">
    <div class="rack-modal-actions">
      <button class="btn-cancel" onclick="closeRackRemarkModal()">Cancel</button>
      <button class="btn-save" onclick="saveRackRemark()">Save</button>
    </div>
  </div>
</div>

<!-- UOM Conversion Modal -->
<div class="uom-modal-overlay" id="uomModalOverlay" onclick="if(event.target===this)closeUomModal()">
  <div class="uom-modal">
    <div class="uom-modal-handle"></div>
    <div class="uom-modal-title">UOM Conversion</div>
    <div class="uom-modal-product" id="uomModalProduct"></div>
    <table class="uom-modal-table">
      <thead><tr><th>From</th><th>To</th><th>Conversion</th></tr></thead>
      <tbody id="uomModalBody"></tbody>
    </table>
    <button class="uom-modal-close" onclick="closeUomModal()">Close</button>
  </div>
</div>

<script src="pending_sources.js" defer></script>

<!-- Product Detail Modal -->
<div class="detail-modal-overlay" id="detailModalOverlay" onclick="if(event.target===this)closeDetailModal()">
  <div class="detail-modal">
    <img class="detail-modal-img" id="detailModalImg" src="" alt="">
    <div class="detail-modal-noimg" id="detailModalNoImg" style="display:none;"></div>
    <div class="detail-modal-body">
      <div class="detail-modal-name" id="detailModalName"></div>
      <div class="detail-modal-desc" id="detailModalDesc"></div>
      <div id="detailModalUom"></div>
    </div>
    <button class="detail-modal-close" onclick="closeDetailModal()">Close</button>
  </div>
</div>

</body>
</html>
