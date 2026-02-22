<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'dbconnection.php';

// ===================== TREND CONFIG =====================
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Products - Inventory</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="components.css">
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

.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-title { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; text-transform: uppercase; }
.product-total { font-size: 13px; color: var(--text-muted); font-weight: 500; }

.toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }

.product-search { flex: 1; min-width: 180px; display: flex; align-items: center; background: var(--surface); border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; transition: border-color var(--transition); }
.product-search:focus-within { border-color: var(--primary); }
.product-search input { border: none; outline: none; padding: 10px 14px; font-family: 'DM Sans', sans-serif; font-size: 14px; width: 100%; color: var(--text); background: transparent; }
.product-search .search-icon { padding: 0 12px; color: var(--text-muted); flex-shrink: 0; }

/* Category sections */
.category-section { margin-bottom: 40px; }
.category-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid var(--primary); }
.category-header h2 { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; text-transform: uppercase; }
.category-header .cat-product-count { background: var(--primary); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 10px; }
.category-header a { margin-left: auto; font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600; transition: opacity var(--transition); }
.category-header a:hover { opacity: 0.7; }

.subcat-section { margin-bottom: 24px; }
.subcat-heading { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; margin-bottom: 12px; padding-left: 4px; color: var(--text-muted); }

.oos-section { margin-top: 40px; padding-top: 24px; border-top: 2px dashed #d1d5db; }
.oos-heading { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }
.oos-count { background: #fee2e2; color: var(--primary); font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 10px; }
.oos-section .product-card { opacity: 0.7; }
.oos-section .product-card:hover { opacity: 1; }

.product-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }

.product-card { background: var(--surface); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; transition: box-shadow var(--transition), transform var(--transition); }
.product-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.product-img-wrap { position: relative; overflow: hidden; }
.product-img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; transition: transform 0.4s ease; background: var(--bg); }
.product-card:hover .product-img { transform: scale(1.03); }

.no-img-product { width: 100%; aspect-ratio: 1; background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 11px; font-weight: 600; text-align: center; padding: 12px; font-family: 'DM Sans', sans-serif; }

.stock-badge { position: absolute; top: 8px; right: 8px; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.03em; }
.stock-badge.in-stock { background: var(--green-light); color: var(--green); border: 1px solid #bbf7d0; }
.stock-badge.out-of-stock { background: var(--red-light); color: var(--primary); border: 1px solid #fecaca; }

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
.product-name { font-size: 13px; font-weight: 600; line-height: 1.4; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.product-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 6px; }
.tag { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; }
.tag-sku { background: #ede9fe; color: #6d28d9; }
.tag-barcode { background: #e0f2fe; color: #0369a1; }
.tag-rack { background: #fef3c7; color: #92400e; }
.tag-rack.unset { background: var(--bg); color: var(--text-muted); }

.qty-label { font-size: 12px; color: var(--text-muted); margin-bottom: 8px; }
.qty-label span { font-weight: 700; color: var(--text); }

.product-actions { margin-top: auto; padding-top: 8px; }
.qty-row { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
.qty-btn { width: 30px; height: 30px; border: 1px solid #d1d5db; background: var(--surface); border-radius: 6px; font-size: 16px; cursor: pointer; display: grid; place-items: center; transition: all var(--transition); color: var(--text); flex-shrink: 0; }
.qty-btn:hover { border-color: var(--primary); color: var(--primary); }
.qty-input { flex: 1; min-width: 0; height: 30px; border: 1px solid #d1d5db; border-radius: 6px; text-align: center; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; outline: none; transition: border-color var(--transition); }
.qty-input:focus { border-color: var(--primary); }

.btn-add-cart { width: 100%; padding: 10px; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 13px; cursor: pointer; transition: all var(--transition); display: flex; align-items: center; justify-content: center; gap: 6px; }
.btn-add-cart.active { background: var(--primary); color: #fff; }
.btn-add-cart.active:hover { background: var(--primary-dark); transform: translateY(-1px); }
.btn-add-cart.disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
.cart-feedback { font-size: 12px; text-align: center; height: 16px; margin-top: 4px; }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); font-size: 15px; }

.load-sentinel { display: flex; align-items: center; justify-content: center; padding: 32px 0; gap: 10px; color: var(--text-muted); font-size: 14px; font-weight: 500; }
.load-spinner { width: 24px; height: 24px; border: 3px solid #e5e7eb; border-top-color: var(--primary); border-radius: 50%; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) { .main { padding: 16px 12px 80px; } .product-grid { gap: 10px; } .product-name { font-size: 12px; } .product-info { padding: 10px; } .page-title { font-size: 20px; } .toolbar { gap: 8px; } .category-header h2 { font-size: 17px; } }
@media (min-width: 993px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1200px) { .product-grid { grid-template-columns: repeat(4, 1fr); } }

@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.product-card { animation: fadeUp 0.35s ease both; }
</style>
</head>
<body>

<?php include('navbar.php'); ?>

<main class="main">
  <div class="page-header">
    <h1 class="page-title">All Products</h1>
    <span class="product-total" id="productTotal"></span>
  </div>

  <div class="toolbar">
    <div class="product-search">
      <svg class="search-icon" style="width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder="Search all products…" id="productSearchInput">
    </div>
  </div>

  <div id="productSections"></div>
  <div class="load-sentinel" id="loadSentinel" style="display:none;">
    <div class="load-spinner"></div>
    <span>Loading more products…</span>
  </div>
</main>

<?php include('mobile-bottombar.php'); ?>

<script>
var allCategories = <?php echo json_encode($allCategories); ?>;

var trendLabels = { green: 'Hot', yellow: 'Moderate', red: 'Slow', black: 'Dead' };
var BATCH_SIZE = 2; // Categories per batch
var loadedIndex = 0; // Next category index to render
var isLoading = false;
var allOOS = []; // Collected during rendering
var oosRendered = false;
var isSearchMode = false;
var totalProducts = 0;

// Pre-compute total and OOS list
(function() {
  allCategories.forEach(function(cat) {
    cat.subcategories.forEach(function(sc) {
      totalProducts += sc.products.length;
    });
  });
  document.getElementById('productTotal').textContent = totalProducts + ' products';
})();

function renderProductCard(p, index) {
  var imgHtml;
  if (p.image) {
    imgHtml = '<img class="product-img" src="/img/' + p.image + '" alt="' + p.name + '" loading="lazy">';
  } else {
    imgHtml = '<div class="no-img-product">' + (p.sku || 'NO IMAGE') + '</div>';
  }

  var badgeHtml;
  if (p.trend) {
    var tLabel = trendLabels[p.trend] || '';
    badgeHtml = '<span class="trend-badge trend-' + p.trend + '"><span class="trend-dot"></span>' + tLabel + '</span>';
  } else {
    var sc = p.inStock ? 'in-stock' : 'out-of-stock';
    var st = p.inStock ? 'In Stock' : 'Out of Stock';
    badgeHtml = '<span class="stock-badge ' + sc + '">' + st + '</span>';
  }

  var tags = '';
  if (p.sku) tags += '<span class="tag tag-sku">SKU: ' + p.sku + '</span>';
  if (p.barcode) tags += '<span class="tag tag-barcode">BC: ' + p.barcode + '</span>';
  if (p.rack_location) {
    tags += '<span class="tag tag-rack">Rack: ' + p.rack_location + '</span>';
  } else {
    tags += '<span class="tag tag-rack unset">No Rack</span>';
  }

  var bc = p.inStock ? 'active' : 'disabled';
  var bt = p.inStock ? 'Add to Cart' : 'Out of Stock';

  return '<div class="product-card" data-name="' + p.name.toLowerCase() + '" data-sku="' + (p.sku || '').toLowerCase() + '" data-barcode="' + (p.barcode || '').toLowerCase() + '" style="animation-delay:' + (index+1)*0.03 + 's">' +
    '<div class="product-img-wrap">' + imgHtml + badgeHtml + '</div>' +
    '<div class="product-info">' +
      '<div class="product-name">' + p.name + '</div>' +
      '<div class="product-tags">' + tags + '</div>' +
      '<div class="qty-label">Qty: <span>' + p.quantity + '</span></div>' +
      '<div class="product-actions">' +
        '<div class="qty-row">' +
          '<button class="qty-btn" onclick="updateQty(\'minus\',' + p.id + ')">−</button>' +
          '<input type="number" class="qty-input" id="qty_' + p.id + '" value="1" min="1" max="99">' +
          '<button class="qty-btn" onclick="updateQty(\'plus\',' + p.id + ')">+</button>' +
        '</div>' +
        '<button class="btn-add-cart ' + bc + '" ' + (p.inStock ? '' : 'disabled') + ' onclick="addToCart(' + p.id + ')">' + bt + '</button>' +
        '<div class="cart-feedback" id="feedback_' + p.id + '"></div>' +
      '</div>' +
    '</div>' +
  '</div>';
}

function renderCategoryHtml(cat) {
  var catProducts = 0;
  var catOOS = [];
  var catHtml = cat.subcategories.map(function(sc) {
    var inStock = sc.products.filter(function(p) { return p.inStock; });
    sc.products.filter(function(p) { return !p.inStock; }).forEach(function(p) {
      catOOS.push(Object.assign({}, p, { category: cat.name, subcategory: sc.name }));
    });
    catProducts += sc.products.length;
    if (inStock.length === 0) return '';
    return '<div class="subcat-section">' +
      '<h3 class="subcat-heading">' + sc.name + '</h3>' +
      '<div class="product-grid">' + inStock.map(function(p, i) { return renderProductCard(p, i); }).join('') + '</div>' +
    '</div>';
  }).join('');

  // Collect OOS for later
  catOOS.forEach(function(p) { allOOS.push(p); });

  if (catProducts === 0) return '';

  return '<div class="category-section" id="cat_' + cat.id + '">' +
    '<div class="category-header">' +
      '<h2>' + cat.name + '</h2>' +
      '<span class="cat-product-count">' + catProducts + '</span>' +
      '<a href="products.php?cat=' + encodeURIComponent(cat.id) + '">View Category →</a>' +
    '</div>' +
    catHtml +
  '</div>';
}

function loadNextBatch() {
  if (isLoading || isSearchMode) return;
  if (loadedIndex >= allCategories.length) {
    // All categories loaded - now render OOS if any
    if (!oosRendered && allOOS.length > 0) {
      oosRendered = true;
      var oosHtml = '<div class="oos-section"><div class="oos-heading">Out of Stock <span class="oos-count">' + allOOS.length + '</span></div>' +
        '<div class="product-grid">' + allOOS.map(function(p, i) { return renderProductCard(p, i); }).join('') + '</div></div>';
      document.getElementById('productSections').insertAdjacentHTML('beforeend', oosHtml);
    }
    document.getElementById('loadSentinel').style.display = 'none';
    return;
  }

  isLoading = true;
  var sections = document.getElementById('productSections');
  var end = Math.min(loadedIndex + BATCH_SIZE, allCategories.length);

  for (var i = loadedIndex; i < end; i++) {
    var html = renderCategoryHtml(allCategories[i]);
    if (html) sections.insertAdjacentHTML('beforeend', html);
  }

  loadedIndex = end;
  isLoading = false;

  // Check if more to load
  if (loadedIndex >= allCategories.length) {
    // Load OOS on next trigger
    if (!oosRendered && allOOS.length > 0) {
      document.getElementById('loadSentinel').style.display = 'flex';
    } else {
      document.getElementById('loadSentinel').style.display = 'none';
    }
  } else {
    document.getElementById('loadSentinel').style.display = 'flex';
  }
}

// ==================== INFINITE SCROLL ====================
var sentinel = document.getElementById('loadSentinel');
var observer = new IntersectionObserver(function(entries) {
  if (entries[0].isIntersecting && !isSearchMode) {
    loadNextBatch();
  }
}, { rootMargin: '400px' }); // Trigger 400px before sentinel is visible

observer.observe(sentinel);

// Initial render - first batch
loadNextBatch();

// ==================== SEARCH ====================
var searchDebounce = null;

function renderAllForSearch() {
  // Render remaining categories that haven't been loaded yet
  var sections = document.getElementById('productSections');
  while (loadedIndex < allCategories.length) {
    var html = renderCategoryHtml(allCategories[loadedIndex]);
    if (html) sections.insertAdjacentHTML('beforeend', html);
    loadedIndex++;
  }
  if (!oosRendered && allOOS.length > 0) {
    oosRendered = true;
    var oosHtml = '<div class="oos-section"><div class="oos-heading">Out of Stock <span class="oos-count">' + allOOS.length + '</span></div>' +
      '<div class="product-grid">' + allOOS.map(function(p, i) { return renderProductCard(p, i); }).join('') + '</div></div>';
    sections.insertAdjacentHTML('beforeend', oosHtml);
  }
  document.getElementById('loadSentinel').style.display = 'none';
}

function filterProducts(query) {
  var q = query.toLowerCase();

  if (q.length > 0) {
    isSearchMode = true;
    // Make sure everything is rendered before filtering
    if (loadedIndex < allCategories.length || !oosRendered) {
      renderAllForSearch();
    }
  } else {
    isSearchMode = false;
  }

  document.querySelectorAll('.product-card').forEach(function(card) {
    if (!q) { card.style.display = ''; return; }
    var nameMatch = card.getAttribute('data-name').indexOf(q) !== -1;
    var skuMatch = card.getAttribute('data-sku').indexOf(q) !== -1;
    var barcodeMatch = card.getAttribute('data-barcode').indexOf(q) !== -1;
    card.style.display = (nameMatch || skuMatch || barcodeMatch) ? '' : 'none';
  });

  // Show/hide empty sections
  document.querySelectorAll('.subcat-section').forEach(function(sec) {
    var any = Array.from(sec.querySelectorAll('.product-card')).some(function(c) { return c.style.display !== 'none'; });
    sec.style.display = any ? '' : 'none';
  });
  document.querySelectorAll('.category-section').forEach(function(sec) {
    var any = Array.from(sec.querySelectorAll('.product-card')).some(function(c) { return c.style.display !== 'none'; });
    sec.style.display = any ? '' : 'none';
  });
  document.querySelectorAll('.oos-section').forEach(function(sec) {
    var any = Array.from(sec.querySelectorAll('.product-card')).some(function(c) { return c.style.display !== 'none'; });
    sec.style.display = any ? '' : 'none';
  });
}

document.getElementById('productSearchInput').addEventListener('input', function() {
  var val = this.value;
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(function() { filterProducts(val); }, 200);
});

// ==================== CART ====================
function updateQty(action, id) {
  var input = document.getElementById('qty_' + id);
  var val = parseInt(input.value) || 1;
  if (action === 'plus' && val < 99) input.value = val + 1;
  if (action === 'minus' && val > 1) input.value = val - 1;
}

function findProduct(id) {
  var found = null;
  allCategories.forEach(function(cat) {
    cat.subcategories.forEach(function(sc) {
      sc.products.forEach(function(p) {
        if (p.id === id) found = p;
      });
    });
  });
  return found;
}

function addToCart(productId) {
  var product = findProduct(productId);
  if (!product || !product.inStock) return;

  var qty = parseInt(document.getElementById('qty_' + productId).value) || 1;

  var cart = [];
  try { cart = JSON.parse(sessionStorage.getItem('cart') || '[]'); } catch(e) {}

  var existing = null;
  for (var i = 0; i < cart.length; i++) {
    if (cart[i].id === productId) { existing = cart[i]; break; }
  }

  if (existing) {
    existing.qty = Math.min(existing.qty + qty, product.quantity);
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      sku: product.sku || '',
      barcode: product.barcode || '',
      img: product.image ? '/img/' + product.image : '',
      rack: product.rack_location || null,
      qty: qty,
      maxQty: product.quantity,
      checked: true
    });
  }

  sessionStorage.setItem('cart', JSON.stringify(cart));

  var fb = document.getElementById('feedback_' + productId);
  fb.style.color = '#16a34a';
  fb.textContent = 'Added to cart!';

  var badge = document.getElementById('cartBadge');
  badge.textContent = cart.length;

  setTimeout(function() { fb.textContent = ''; }, 2000);
}

// Init cart badge
(function() {
  try {
    var cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
    document.getElementById('cartBadge').textContent = cart.length;
  } catch(e) {}
})();
</script>
</body>
</html>
