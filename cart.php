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
<title>Cart - Inventory</title>
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
  --radius: 14px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
  --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

html { scroll-behavior: smooth; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; }

.page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
.page-header .back-btn { background: none; border: none; color: #fff; cursor: pointer; display: flex; align-items: center; gap: 6px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; padding: 8px 4px; transition: opacity var(--transition); }
.page-header .back-btn:hover { opacity: 0.8; }
.page-header .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; position: absolute; left: 50%; transform: translateX(-50%); }

.main { max-width: 700px; margin: 0 auto; padding: 16px 16px 120px; }

.select-bar { display: flex; align-items: center; justify-content: space-between; background: var(--surface); border-radius: 10px; padding: 12px 16px; margin-bottom: 12px; box-shadow: var(--shadow-sm); }
.select-bar label { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; cursor: pointer; }
.select-bar input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; }
.item-count { font-size: 13px; color: var(--text-muted); font-weight: 500; }

.cart-item { display: flex; align-items: flex-start; gap: 12px; background: var(--surface); border-radius: var(--radius); padding: 14px; margin-bottom: 10px; box-shadow: var(--shadow-sm); transition: all var(--transition); animation: fadeUp 0.3s ease both; }
.cart-item.unchecked { opacity: 0.5; }
.cart-item .item-checkbox { width: 18px; height: 18px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0; margin-top: 4px; }
.cart-item .item-img { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; flex-shrink: 0; background: var(--bg); }
.cart-item .no-img-thumb { width: 80px; height: 80px; border-radius: 10px; flex-shrink: 0; background: linear-gradient(135deg, #e5e7eb, #d1d5db); display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 600; color: #9ca3af; text-align: center; padding: 4px; }
.cart-item .item-details { flex: 1; min-width: 0; }
.cart-item .item-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.cart-item .item-name { font-size: 13px; font-weight: 600; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.cart-item .remove-btn { background: none; border: none; color: var(--primary); cursor: pointer; width: 28px; height: 28px; display: grid; place-items: center; border-radius: 50%; flex-shrink: 0; transition: background var(--transition); }
.cart-item .remove-btn:hover { background: #fef2f2; }

.item-tags { display: flex; flex-wrap: wrap; gap: 4px; margin: 4px 0; }
.item-tag { font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 3px; }
.item-tag-sku { background: #ede9fe; color: #6d28d9; }
.item-tag-rack { background: #fef3c7; color: #92400e; }
.item-tag-rack.unset { background: var(--bg); color: var(--text-muted); }

.qty-control { display: flex; align-items: center; gap: 0; margin-top: 8px; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; width: fit-content; }
.qty-control button { width: 36px; height: 34px; border: none; background: var(--bg); cursor: pointer; font-size: 16px; font-weight: 700; color: var(--text); display: grid; place-items: center; transition: background var(--transition); }
.qty-control button:hover { background: #e5e7eb; }
.qty-control input { width: 50px; height: 34px; border: none; border-left: 1px solid #d1d5db; border-right: 1px solid #d1d5db; text-align: center; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; outline: none; background: var(--surface); color: var(--text); }
.qty-control input::-webkit-outer-spin-button, .qty-control input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.qty-control input[type=number] { -moz-appearance: textfield; }
.cart-item .max-warning { font-size: 11px; color: var(--primary); font-style: italic; margin-top: 4px; }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state .empty-icon { font-size: 48px; margin-bottom: 12px; }
.empty-state p { font-size: 15px; margin-bottom: 20px; }
.empty-state a { display: inline-flex; align-items: center; gap: 6px; background: var(--primary); color: #fff; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background var(--transition); }
.empty-state a:hover { background: var(--primary-dark); }

.cart-footer { position: fixed; bottom: 0; left: 0; right: 0; background: var(--surface); border-top: 1px solid #e5e7eb; padding: 12px 16px; z-index: 100; box-shadow: 0 -4px 20px rgba(0,0,0,0.08); }
.cart-footer-inner { max-width: 700px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.cart-summary { font-size: 13px; color: var(--text-muted); }
.cart-summary strong { color: var(--text); font-size: 16px; }
.btn-next { background: var(--primary); color: #fff; border: none; padding: 14px 36px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 15px; cursor: pointer; transition: all var(--transition); display: flex; align-items: center; gap: 8px; }
.btn-next:hover { background: var(--primary-dark); transform: translateY(-1px); }
.btn-next:disabled { background: #d1d5db; cursor: not-allowed; transform: none; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 480px) { .cart-item .item-img, .cart-item .no-img-thumb { width: 68px; height: 68px; } .cart-item .item-name { font-size: 12px; } .btn-next { padding: 12px 24px; font-size: 14px; } }
</style>
</head>
<body>

<header class="page-header">
  <button class="back-btn" onclick="history.back()">
    <svg style="width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </button>
  <span class="page-title">Cart</span>
</header>

<main class="main">
  <div class="select-bar" id="selectBar">
    <label><input type="checkbox" id="selectAll" checked> Select All</label>
    <span class="item-count" id="itemCount"></span>
  </div>
  <div id="cartItems"></div>
  <div class="empty-state" id="emptyState" style="display:none;">
    <div class="empty-icon">📦</div>
    <p>Your cart is empty</p>
    <a href="index.php">Browse Categories</a>
  </div>
</main>

<div class="cart-footer" id="cartFooter">
  <div class="cart-footer-inner">
    <div class="cart-summary"><span id="selectedCount">0</span> items selected</div>
    <button class="btn-next" id="btnNext" onclick="goToConfirm()">
      Next
      <svg style="width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;"><polyline points="9 6 15 12 9 18"/></svg>
    </button>
  </div>
</div>

<script>
// Load cart from sessionStorage
var cartItems = [];
try {
  var stored = sessionStorage.getItem('cart');
  if (stored) {
    var parsed = JSON.parse(stored);
    if (parsed && parsed.length > 0) cartItems = parsed;
  }
} catch(e) {}

function saveCart() {
  sessionStorage.setItem('cart', JSON.stringify(cartItems));
}

function render() {
  var container = document.getElementById('cartItems');
  var emptyState = document.getElementById('emptyState');
  var selectBar = document.getElementById('selectBar');
  var footer = document.getElementById('cartFooter');

  if (cartItems.length === 0) {
    container.innerHTML = '';
    emptyState.style.display = '';
    selectBar.style.display = 'none';
    footer.style.display = 'none';
    return;
  }

  emptyState.style.display = 'none';
  selectBar.style.display = '';
  footer.style.display = '';

  container.innerHTML = cartItems.map(function(item, i) {
    var imgHtml = item.img
      ? '<img class="item-img" src="' + item.img + '" alt="' + item.name + '">'
      : '<div class="no-img-thumb">' + (item.sku || 'NO IMG') + '</div>';

    var tagsHtml = '';
    if (item.sku) tagsHtml += '<span class="item-tag item-tag-sku">SKU: ' + item.sku + '</span>';
    if (item.rack) tagsHtml += '<span class="item-tag item-tag-rack">Rack: ' + item.rack + '</span>';
    else tagsHtml += '<span class="item-tag item-tag-rack unset">No Rack</span>';

    var maxWarning = item.qty >= item.maxQty ? '<div class="max-warning">Max: ' + item.maxQty + ' available</div>' : '';

    return '<div class="cart-item ' + (item.checked ? '' : 'unchecked') + '" style="animation-delay:' + i*0.05 + 's" id="cartRow_' + item.id + '">' +
      '<input type="checkbox" class="item-checkbox" ' + (item.checked ? 'checked' : '') + ' onchange="toggleItem(' + item.id + ', this.checked)">' +
      imgHtml +
      '<div class="item-details">' +
        '<div class="item-top">' +
          '<div class="item-name">' + item.name + '</div>' +
          '<button class="remove-btn" onclick="removeItem(' + item.id + ')" title="Remove">' +
            '<svg style="width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
          '</button>' +
        '</div>' +
        '<div class="item-tags">' + tagsHtml + '</div>' +
        '<div class="qty-control">' +
          '<button onclick="changeQty(' + item.id + ',-1)">−</button>' +
          '<input type="number" id="qtyInput_' + item.id + '" value="' + item.qty + '" min="1" max="' + item.maxQty + '" onchange="setQty(' + item.id + ',this.value)">' +
          '<button onclick="changeQty(' + item.id + ',1)">+</button>' +
        '</div>' +
        maxWarning +
      '</div>' +
    '</div>';
  }).join('');

  updateCounts();
}

function toggleItem(id, checked) {
  var item = cartItems.find(function(i) { return i.id === id; });
  if (item) item.checked = checked;
  var row = document.getElementById('cartRow_' + id);
  if (row) row.classList.toggle('unchecked', !checked);
  updateSelectAll();
  updateCounts();
  saveCart();
}

function updateSelectAll() {
  document.getElementById('selectAll').checked = cartItems.every(function(i) { return i.checked; });
}

document.getElementById('selectAll').addEventListener('change', function() {
  var c = this.checked;
  cartItems.forEach(function(i) { i.checked = c; });
  saveCart();
  render();
});

function changeQty(id, delta) {
  var item = cartItems.find(function(i) { return i.id === id; });
  if (!item) return;
  var nq = item.qty + delta;
  if (nq < 1 || nq > item.maxQty) return;
  item.qty = nq;
  saveCart();
  render();
}

function setQty(id, val) {
  var item = cartItems.find(function(i) { return i.id === id; });
  if (!item) return;
  var v = parseInt(val) || 1;
  if (v < 1) v = 1;
  if (v > item.maxQty) v = item.maxQty;
  item.qty = v;
  saveCart();
  render();
}

function removeItem(id) {
  cartItems = cartItems.filter(function(i) { return i.id !== id; });
  saveCart();
  render();
}

function updateCounts() {
  var checked = cartItems.filter(function(i) { return i.checked; });
  document.getElementById('itemCount').textContent = cartItems.length + ' item(s)';
  document.getElementById('selectedCount').textContent = checked.length;
  document.getElementById('btnNext').disabled = checked.length === 0;
}

function goToConfirm() {
  var selected = cartItems.filter(function(i) { return i.checked; });
  if (selected.length === 0) return;
  sessionStorage.setItem('confirmItems', JSON.stringify(selected));
  window.location.href = 'confirm.php';
}

render();
</script>
</body>
</html>
