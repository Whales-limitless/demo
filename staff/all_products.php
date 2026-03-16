<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
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
.search-btn { background: var(--primary); border: none; padding: 0; width: 42px; height: 42px; cursor: pointer; color: #fff; display: grid; place-items: center; flex-shrink: 0; border-radius: 0 10px 10px 0; transition: background var(--transition); }
.search-btn:hover { background: var(--primary-dark); }

.search-results-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding: 12px 16px; background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-sm); }
.search-results-header h2 { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; }
.search-results-header .clear-search { font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600; cursor: pointer; background: none; border: none; font-family: 'DM Sans', sans-serif; }
.search-results-header .clear-search:hover { opacity: 0.7; }
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
.tag-rack { background: #fef3c7; color: #92400e; }
.tag-rack.unset { background: var(--bg); color: var(--text-muted); }
.tag-rack-remark { background: #e0f2fe; color: #0369a1; }
.tag-rack-date { background: #f3e8ff; color: #7c3aed; font-size: 9px; }
.tag-stock-in-date { background: #ecfdf5; color: #059669; font-size: 9px; }
.tag-btn { cursor: pointer; transition: all var(--transition); }
.tag-btn:hover { opacity: 0.8; transform: translateY(-1px); }
.tag-uom { background: #dbeafe; color: #1d4ed8; position: relative; }
.uom-icon { font-size: 12px; }
.uom-tooltip { display: none; position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%); background: #1e293b; color: #f8fafc; border-radius: 10px; padding: 10px 14px; min-width: 170px; z-index: 50; box-shadow: 0 4px 16px rgba(0,0,0,0.2); white-space: nowrap; }
.uom-tooltip::after { content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 6px solid transparent; border-top-color: #1e293b; }
.uom-tooltip.active { display: flex; flex-direction: column; gap: 4px; }
.uom-tooltip-title { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
.uom-tooltip-row { font-size: 12px; font-weight: 600; padding: 2px 0; }

.rack-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; justify-content: center; align-items: center; padding: 16px; }
.rack-modal-overlay.active { display: flex; }
.rack-modal { background: var(--surface); border-radius: var(--radius); padding: 24px; max-width: 400px; width: 100%; box-shadow: var(--shadow-lg); animation: fadeUp 0.25s ease; }
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

.qty-label { font-size: 16px; color: #1a1a1a; margin-bottom: 8px; font-weight: 800; }
.qty-label span { font-weight: 800; color: #1a1a1a; }

.product-actions { margin-top: auto; padding-top: 8px; }
.qty-row { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
.qty-btn { width: 30px; height: 30px; border: 1px solid #d1d5db; background: var(--surface); border-radius: 6px; font-size: 16px; cursor: pointer; display: grid; place-items: center; transition: all var(--transition); color: var(--text); flex-shrink: 0; }
.qty-btn:hover { border-color: var(--primary); color: var(--primary); }
.qty-input { flex: 1; min-width: 0; height: 30px; border: 1px solid #d1d5db; border-radius: 6px; text-align: center; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; outline: none; transition: border-color var(--transition); }
.qty-input:focus { border-color: var(--primary); }

.btn-add-cart { width: 100%; padding: 10px; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 13px; cursor: pointer; transition: all var(--transition); display: flex; align-items: center; justify-content: center; gap: 6px; }
.btn-add-cart.active { background: var(--primary); color: #fff; }
.btn-add-cart.active:hover { background: var(--primary-dark); transform: translateY(-1px); }
.btn-add-cart.stock-take { background: #fef3c7; color: #92400e; cursor: not-allowed; font-size: 11px; }
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
      <button class="search-btn" id="searchBtn" title="Search">
        <svg style="width:18px;height:18px;stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
    </div>
  </div>

  <div id="initialLoading" style="text-align:center;padding:60px 20px;color:var(--text-muted);">
    <div class="load-spinner" style="margin:0 auto 16px;"></div>
    <p style="font-size:15px;font-weight:500;">Loading products...</p>
  </div>
  <div id="productSections"></div>
  <div class="load-sentinel" id="loadSentinel" style="display:none;">
    <div class="load-spinner"></div>
    <span>Loading more products…</span>
  </div>
</main>

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

function showUomInfo(el, e) {
  e.stopPropagation();
  var tip = el.querySelector('.uom-tooltip');
  if (!tip) return;
  var isOpen = tip.classList.contains('active');
  // Close all other tooltips first
  document.querySelectorAll('.uom-tooltip.active').forEach(function(t) { t.classList.remove('active'); });
  if (!isOpen) tip.classList.add('active');
}
document.addEventListener('click', function() {
  document.querySelectorAll('.uom-tooltip.active').forEach(function(t) { t.classList.remove('active'); });
});

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

var allCategories = [];
var stockTakeBarcodes = {};

var trendLabels = { green: 'Hot', yellow: 'Moderate', red: 'Slow', black: 'Dead' };
var BATCH_SIZE = 2; // Categories per batch
var SEARCH_BATCH_SIZE = 50; // Products per batch in search mode
var loadedIndex = 0; // Next category index to render
var isLoading = false;
var isSearchMode = false;
var totalProducts = 0;
var dataLoaded = false;
var searchResults = []; // Scored results for batch rendering
var searchRenderedCount = 0; // How many search results rendered so far

// Flatten all products for search
var allProductsFlat = [];

function initProductData(categories) {
  allCategories = categories;
  allProductsFlat = [];
  totalProducts = 0;
  allCategories.forEach(function(cat) {
    cat.subcategories.forEach(function(sc) {
      totalProducts += sc.products.length;
      sc.products.forEach(function(p) {
        allProductsFlat.push(p);
      });
    });
  });
  document.getElementById('productTotal').textContent = totalProducts + ' products';
  document.getElementById('initialLoading').style.display = 'none';
  dataLoaded = true;
}

// Get URL query parameter
function getUrlParam(name) {
  var params = new URLSearchParams(window.location.search);
  return params.get(name) || '';
}

// Normalize quotes: treat " and '' as interchangeable, normalize smart quotes
function normalizeQuotes(s) {
  // Normalize smart/curly quotes and prime symbols to ASCII
  s = s.replace(/[\u201C\u201D\u2033\uFF02]/g, '"');
  s = s.replace(/[\u2018\u2019\u2032\uFF07]/g, "'");
  return s;
}

// Relevance scoring: higher = better match (search by product name only)
function scoreProduct(p, q) {
  var ql = normalizeQuotes(q.toLowerCase());
  var name = normalizeQuotes((p.name || '').toLowerCase());
  // Also check with " swapped to '' and vice versa
  var qlAlt = ql.replace(/"/g, "''");
  var qlAlt2 = ql.replace(/''/g, '"');

  var score = 0;

  // Exact name match (highest priority)
  if (name === ql || name === qlAlt || name === qlAlt2) score += 800;

  // Starts with (high priority)
  if (name.indexOf(ql) === 0 || name.indexOf(qlAlt) === 0 || name.indexOf(qlAlt2) === 0) score += 300;

  // Contains (lower priority)
  if (name.indexOf(ql) !== -1 || name.indexOf(qlAlt) !== -1 || name.indexOf(qlAlt2) !== -1) score += 60;

  return score;
}

function doRelevanceSearch(query) {
  var q = query;
  if (q.length === 0) {
    clearSearch();
    return;
  }

  isSearchMode = true;

  // Score and filter products
  var scored = [];
  allProductsFlat.forEach(function(p) {
    var s = scoreProduct(p, q);
    if (s > 0) scored.push({ product: p, score: s });
  });

  // Sort by score descending
  scored.sort(function(a, b) { return b.score - a.score; });

  // Store for batch rendering
  searchResults = scored;
  searchRenderedCount = 0;

  // Render header
  var sections = document.getElementById('productSections');
  var matchCount = scored.length;

  var headerHtml = '<div class="search-results-header">' +
    '<h2>Search: "' + escHtml(q) + '" (' + matchCount + ' found)</h2>' +
    '<button class="clear-search" onclick="clearSearch()">Clear Search</button>' +
  '</div>';

  if (matchCount === 0) {
    sections.innerHTML = headerHtml + '<div style="text-align:center;padding:40px 0;color:var(--text-muted);font-size:14px;">No products found.</div>';
    document.getElementById('loadSentinel').style.display = 'none';
  } else {
    sections.innerHTML = headerHtml + '<div class="product-grid" id="searchGrid"></div>';
    loadNextSearchBatch();
  }

  document.getElementById('productTotal').textContent = matchCount + ' results';
}

function loadNextSearchBatch() {
  if (!isSearchMode || searchRenderedCount >= searchResults.length) {
    document.getElementById('loadSentinel').style.display = 'none';
    return;
  }

  var grid = document.getElementById('searchGrid');
  var end = Math.min(searchRenderedCount + SEARCH_BATCH_SIZE, searchResults.length);
  var html = '';
  for (var i = searchRenderedCount; i < end; i++) {
    html += renderProductCard(searchResults[i].product, i);
  }
  grid.insertAdjacentHTML('beforeend', html);
  searchRenderedCount = end;

  if (searchRenderedCount < searchResults.length) {
    document.getElementById('loadSentinel').style.display = 'flex';
  } else {
    document.getElementById('loadSentinel').style.display = 'none';
  }
}

function clearSearch() {
  isSearchMode = false;
  searchResults = [];
  searchRenderedCount = 0;
  loadedIndex = 0;
  document.getElementById('productSearchInput').value = '';
  document.getElementById('productSections').innerHTML = '';
  document.getElementById('productTotal').textContent = totalProducts + ' products';
  // Remove query from URL without reload
  if (window.history.replaceState) {
    window.history.replaceState({}, '', window.location.pathname);
  }
  loadNextBatch();
}

function renderProductCard(p, index) {
  var imgHtml;
  if (p.image) {
    imgHtml = '<img class="product-img" src="/img/' + escAttr(p.image) + '" alt="' + escAttr(p.name) + '" loading="lazy">';
  } else {
    imgHtml = '<div class="no-img-product">' + escHtml(p.sku || 'NO IMAGE') + '</div>';
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
  if (p.uom_conversions && p.uom_conversions.length > 0) {
    tags += '<span class="tag tag-uom tag-btn" onclick="showUomInfo(this, event)"><span class="uom-icon">&#9878;</span> UOM' +
      '<span class="uom-tooltip"><span class="uom-tooltip-title">UOM Conversion</span>' +
      p.uom_conversions.map(function(u) {
        var factor = u.conversion_factor % 1 === 0 ? u.conversion_factor.toFixed(0) : u.conversion_factor;
        return '<span class="uom-tooltip-row">1 ' + escHtml(u.from_uom) + ' = ' + factor + ' ' + escHtml(u.to_uom) + '</span>';
      }).join('') +
      '</span></span>';
  }

  return '<div class="product-card" data-id="' + p.id + '" data-name="' + escAttr(p.name.toLowerCase()) + '" data-sku="' + escAttr((p.sku || '').toLowerCase()) + '" data-barcode="' + escAttr((p.barcode || '').toLowerCase()) + '" style="animation-delay:' + (index+1)*0.03 + 's">' +
    '<div class="product-img-wrap">' + imgHtml + badgeHtml + '</div>' +
    '<div class="product-info">' +
      '<div class="product-name">' + escHtml(p.name) + '</div>' +
      '<div class="product-tags">' + tags + '</div>' +
      '<div class="qty-label">Qty: <span>' + p.quantity + '</span></div>' +
      '<div class="product-actions">' +
        '<div class="qty-row">' +
          '<button class="qty-btn" onclick="updateQty(\'minus\',' + p.id + ')">−</button>' +
          '<input type="number" class="qty-input" id="qty_' + p.id + '" value="1" min="1" max="99">' +
          '<button class="qty-btn" onclick="updateQty(\'plus\',' + p.id + ')">+</button>' +
        '</div>' +
        (stockTakeBarcodes[p.barcode] ?
          '<button class="btn-add-cart stock-take" disabled>Active Stock Take Session</button>' :
          '<button class="btn-add-cart active" onclick="addToCart(' + p.id + ')">Add to Cart</button>') +
        '<div class="cart-feedback" id="feedback_' + p.id + '"></div>' +
      '</div>' +
    '</div>' +
  '</div>';
}

function renderCategoryHtml(cat) {
  var catProducts = 0;
  var catHtml = cat.subcategories.map(function(sc) {
    catProducts += sc.products.length;
    if (sc.products.length === 0) return '';
    return '<div class="subcat-section">' +
      '<h3 class="subcat-heading">' + escHtml(sc.name) + '</h3>' +
      '<div class="product-grid">' + sc.products.map(function(p, i) { return renderProductCard(p, i); }).join('') + '</div>' +
    '</div>';
  }).join('');

  if (catProducts === 0) return '';

  return '<div class="category-section" id="cat_' + cat.id + '">' +
    '<div class="category-header">' +
      '<h2>' + escHtml(cat.name) + '</h2>' +
      '<span class="cat-product-count">' + catProducts + '</span>' +
      '<a href="products.php?cat=' + encodeURIComponent(cat.id) + '">View Category →</a>' +
    '</div>' +
    catHtml +
  '</div>';
}

function loadNextBatch() {
  if (isLoading || isSearchMode) return;
  if (loadedIndex >= allCategories.length) {
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
    document.getElementById('loadSentinel').style.display = 'none';
  } else {
    document.getElementById('loadSentinel').style.display = 'flex';
  }
}

// ==================== INFINITE SCROLL ====================
var sentinel = document.getElementById('loadSentinel');
var observer = new IntersectionObserver(function(entries) {
  if (entries[0].isIntersecting) {
    if (isSearchMode) {
      loadNextSearchBatch();
    } else {
      loadNextBatch();
    }
  }
}, { rootMargin: '400px' }); // Trigger 400px before sentinel is visible

observer.observe(sentinel);

// Load product data via AJAX then render
fetch('all_products_ajax.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: ''
})
.then(function(r) { return r.json(); })
.then(function(data) {
  if (data.error) {
    document.getElementById('initialLoading').innerHTML = '<p style="color:#dc2626;">Failed to load products. Please refresh.</p>';
    return;
  }
  // Build stock take barcode lookup
  (data.stock_take_barcodes || []).forEach(function(b) { stockTakeBarcodes[b] = true; });
  initProductData(data.categories || []);

  var initialQuery = getUrlParam('q');
  if (initialQuery) {
    document.getElementById('productSearchInput').value = initialQuery;
    var navInput = document.getElementById('searchInput');
    if (navInput) navInput.value = initialQuery;
    doRelevanceSearch(initialQuery);
  } else {
    loadNextBatch();
  }
})
.catch(function() {
  document.getElementById('initialLoading').innerHTML = '<p style="color:#dc2626;">Failed to load products. Please refresh.</p>';
});

function renderAllForSearch() {
  // Render remaining categories that haven't been loaded yet
  var sections = document.getElementById('productSections');
  while (loadedIndex < allCategories.length) {
    var html = renderCategoryHtml(allCategories[loadedIndex]);
    if (html) sections.insertAdjacentHTML('beforeend', html);
    loadedIndex++;
  }
  document.getElementById('loadSentinel').style.display = 'none';
}

// Search button click
document.getElementById('searchBtn').addEventListener('click', function() {
  if (!dataLoaded) return;
  var q = document.getElementById('productSearchInput').value;
  if (q.length > 0) doRelevanceSearch(q);
});

// Enter key in search input
document.getElementById('productSearchInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    if (!dataLoaded) return;
    var q = this.value;
    if (q.length > 0) doRelevanceSearch(q);
  }
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
  if (!product) return;

  var qty = parseInt(document.getElementById('qty_' + productId).value) || 1;

  var cart = [];
  try { cart = JSON.parse(sessionStorage.getItem('cart') || '[]'); } catch(e) {}

  var existing = null;
  for (var i = 0; i < cart.length; i++) {
    if (cart[i].id === productId) { existing = cart[i]; break; }
  }

  if (existing) {
    existing.qty = existing.qty + qty;
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

// ==================== RACK MODALS ====================
var rackEditProductId = null;
var rackListCache = null;

function openRackModal(productId, currentRack) {
  rackEditProductId = productId;
  document.getElementById('rackModalSelect').value = currentRack || '';
  document.getElementById('rackModalOverlay').classList.add('active');

  // Load rack list if not cached
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
  allCategories.forEach(function(cat) {
    cat.subcategories.forEach(function(sc) {
      sc.products.forEach(function(p) {
        if (p.id === productId) {
          p.rack_location = newRack || null;
          p.rack_updated_at = nowStr;
        }
      });
    });
  });
  // Re-render the specific product card's tags
  var card = document.querySelector('.product-card[data-id="' + productId + '"]');
  if (card) {
    var tagsEl = card.querySelector('.product-tags');
    if (tagsEl) {
      var rackLabel = newRack ? 'Rack: ' + newRack : 'No Rack';
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

// Close modals on overlay click
document.getElementById('rackModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeRackModal();
});
document.getElementById('rackRemarkModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeRackRemarkModal();
});
</script>

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

</body>
</html>
