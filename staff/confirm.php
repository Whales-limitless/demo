<?php
require_once __DIR__ . '/session_security.php';
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
<title>Confirm Order - Inventory</title>
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
  --blue: #2563eb;
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

.type-toggle { background: var(--surface); border-radius: 12px; padding: 6px; display: flex; gap: 4px; box-shadow: var(--shadow-sm); margin-bottom: 20px; }
.type-toggle label { flex: 1; text-align: center; padding: 12px 16px; font-size: 14px; font-weight: 700; border-radius: 8px; cursor: pointer; transition: all var(--transition); color: var(--text-muted); position: relative; }
.type-toggle input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.type-toggle label:has(input:checked) { background: var(--primary); color: #fff; box-shadow: 0 2px 8px rgba(200,16,46,0.3); }
.type-toggle label:hover:not(:has(input:checked)) { background: var(--bg); }

.section-label { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.section-label .badge { background: var(--primary); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 10px; }

.summary-list { margin-bottom: 20px; }
.summary-item { display: flex; align-items: center; gap: 12px; background: var(--surface); border-radius: 12px; padding: 12px 14px; margin-bottom: 8px; box-shadow: var(--shadow-sm); animation: fadeUp 0.3s ease both; }
.summary-item .s-num { width: 28px; height: 28px; border-radius: 50%; background: var(--bg); display: grid; place-items: center; font-size: 12px; font-weight: 700; color: var(--text-muted); flex-shrink: 0; }
.summary-item .s-img { width: 52px; height: 52px; border-radius: 8px; object-fit: cover; flex-shrink: 0; background: var(--bg); }
.summary-item .s-no-img { width: 52px; height: 52px; border-radius: 8px; flex-shrink: 0; background: linear-gradient(135deg, #e5e7eb, #d1d5db); display: flex; align-items: center; justify-content: center; font-size: 8px; font-weight: 600; color: #9ca3af; text-align: center; }
.summary-item .s-details { flex: 1; min-width: 0; }
.summary-item .s-name { font-size: 13px; font-weight: 600; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.summary-item .s-meta { display: flex; align-items: center; gap: 6px; margin-top: 3px; font-size: 10px; flex-wrap: wrap; }
.summary-item .s-tag { padding: 1px 6px; border-radius: 3px; font-weight: 600; }
.summary-item .s-tag-sku { background: #ede9fe; color: #6d28d9; }
.summary-item .s-tag-rack { background: #fef3c7; color: #92400e; }
.summary-item .s-tag-rack.unset { background: var(--bg); color: var(--text-muted); }
.summary-item .s-qty { font-size: 15px; font-weight: 800; color: var(--text); flex-shrink: 0; min-width: 40px; text-align: right; }

.summary-card { background: var(--surface); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 16px; }
.summary-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 14px; }
.summary-row.total { border-top: 2px solid var(--text); margin-top: 8px; padding-top: 14px; font-weight: 800; font-size: 16px; }
.summary-row .label { color: var(--text-muted); }
.summary-row .value { font-weight: 700; }

.confirm-footer { position: fixed; bottom: 0; left: 0; right: 0; background: var(--surface); border-top: 1px solid #e5e7eb; padding: 12px 16px; z-index: 100; box-shadow: 0 -4px 20px rgba(0,0,0,0.08); }
.confirm-footer-inner { max-width: 700px; margin: 0 auto; }
.btn-confirm { width: 100%; padding: 16px; border: none; border-radius: 12px; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; cursor: pointer; transition: all var(--transition); display: flex; align-items: center; justify-content: center; gap: 8px; color: #fff; background: var(--primary); }
.btn-confirm:hover { background: var(--primary-dark); transform: translateY(-1px); }
.btn-confirm.stock-in { background: var(--blue); }
.btn-confirm.stock-in:hover { background: #1d4ed8; }


.success-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 500; justify-content: center; align-items: center; padding: 16px; }
.success-overlay.active { display: flex; }
.success-modal { background: var(--surface); border-radius: var(--radius); padding: 40px 32px; text-align: center; max-width: 380px; width: 100%; box-shadow: var(--shadow-lg); animation: modalIn 0.3s ease; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
.success-modal .check-icon { width: 64px; height: 64px; background: #f0fdf4; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 16px; }
.success-modal h3 { font-family: 'Outfit', sans-serif; font-size: 20px; margin-bottom: 8px; }
.success-modal p { font-size: 14px; color: var(--text-muted); margin-bottom: 24px; }
.success-modal .btn-done { background: var(--primary); color: #fff; border: none; padding: 12px 32px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 14px; cursor: pointer; transition: background var(--transition); }
.success-modal .btn-done:hover { background: var(--primary-dark); }

@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 480px) { .summary-item .s-img, .summary-item .s-no-img { width: 44px; height: 44px; } .summary-item .s-name { font-size: 12px; } .type-toggle label { padding: 10px 12px; font-size: 13px; } }
</style>
</head>
<body>

<header class="page-header">
  <button class="back-btn" onclick="history.back()">
    <svg style="width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </button>
  <span class="page-title">Confirm</span>
</header>

<main class="main">
  <div class="type-toggle" id="typeToggle">
    <label>
      <input type="radio" name="orderType" value="purchase" checked onchange="updateType()">
      Purchase
    </label>
    <label>
      <input type="radio" name="orderType" value="stockin" onchange="updateType()">
      Stock In
    </label>
  </div>

  <div class="section-label">Order Summary <span class="badge" id="totalBadge">0</span></div>
  <div class="summary-list" id="summaryList"></div>

  <div class="summary-card">
    <div class="summary-row"><span class="label">Total Items</span><span class="value" id="totalItems">0</span></div>
    <div class="summary-row"><span class="label">Total Quantity</span><span class="value" id="totalQty">0</span></div>
    <div class="summary-row total"><span class="label">Order Type</span><span class="value" id="orderTypeLabel">Purchase</span></div>
  </div>
</main>

<div class="confirm-footer">
  <div class="confirm-footer-inner">
    <div style="margin-bottom:10px;">
      <label for="txtTo" style="font-size:13px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:4px;">To:</label>
      <input type="text" id="txtTo" placeholder="Enter recipient..." style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:14px;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#d1d5db'">
    </div>
    <button class="btn-confirm" id="btnConfirm" onclick="handleConfirmClick()">
      <span id="btnText">Purchase</span>
    </button>
  </div>
</div>

<div class="success-overlay" id="successOverlay">
  <div class="success-modal">
    <div class="check-icon">
      <svg style="width:32px;height:32px;fill:none;stroke:#16a34a;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h3 id="successTitle">Order Submitted!</h3>
    <p id="successMsg">Your purchase order has been placed successfully.</p>
    <button class="btn-done" onclick="window.location.href='index.php'">Back to Home</button>
  </div>
</div>

<script>
// Load items from sessionStorage
var items = [];
try {
  var stored = sessionStorage.getItem('confirmItems');
  if (stored) {
    var parsed = JSON.parse(stored);
    if (parsed && parsed.length > 0) items = parsed;
  }
} catch(e) {}

// Redirect if no items
if (items.length === 0) {
  window.location.href = 'cart.php';
}

var orderType = 'purchase';

function render() {
  var list = document.getElementById('summaryList');
  list.innerHTML = items.map(function(item, i) {
    var imgHtml = item.img
      ? '<img class="s-img" src="' + item.img + '" alt="' + item.name + '">'
      : '<div class="s-no-img">' + (item.sku || 'N/A') + '</div>';

    var metaHtml = '';
    if (item.sku) metaHtml += '<span class="s-tag s-tag-sku">SKU: ' + item.sku + '</span>';
    if (item.rack) metaHtml += '<span class="s-tag s-tag-rack">Rack: ' + item.rack + '</span>';
    else metaHtml += '<span class="s-tag s-tag-rack unset">No Rack</span>';

    return '<div class="summary-item" style="animation-delay:' + i*0.04 + 's">' +
      '<span class="s-num">' + (i+1) + '</span>' +
      imgHtml +
      '<div class="s-details">' +
        '<div class="s-name">' + item.name + '</div>' +
        '<div class="s-meta">' + metaHtml + '</div>' +
      '</div>' +
      '<div class="s-qty">&times;' + item.qty + '</div>' +
    '</div>';
  }).join('');

  document.getElementById('totalBadge').textContent = items.length;
  document.getElementById('totalItems').textContent = items.length;
  document.getElementById('totalQty').textContent = items.reduce(function(s, i) { return s + i.qty; }, 0);
}

function updateType() {
  orderType = document.querySelector('input[name="orderType"]:checked').value;
  var btn = document.getElementById('btnConfirm');
  var btnText = document.getElementById('btnText');
  var typeLabel = document.getElementById('orderTypeLabel');
  if (orderType === 'purchase') {
    btnText.textContent = 'Purchase';
    btn.className = 'btn-confirm';
    typeLabel.textContent = 'Purchase';
  } else {
    btnText.textContent = 'Stock In';
    btn.className = 'btn-confirm stock-in';
    typeLabel.textContent = 'Stock In';
  }
}

function handleConfirmClick() {
  // Check for active stock take before proceeding
  checkStockTakeAndSubmit();
}

function checkStockTakeAndSubmit() {
  var btn = document.getElementById('btnConfirm');
  var btnText = document.getElementById('btnText');
  btnText.textContent = 'Checking…';
  btn.disabled = true;

  var barcodes = items.map(function(item) { return item.barcode; });

  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'check_stock_take.php', true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      btn.disabled = false;
      updateType();

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.blocked && resp.blocked_items && resp.blocked_items.length > 0) {
            var listHtml = '<div style="text-align:left;max-height:200px;overflow-y:auto;margin-top:10px;">';
            resp.blocked_items.forEach(function(item) {
              listHtml += '<div style="padding:6px 0;border-bottom:1px solid #eee;font-size:13px;">' +
                '<strong>' + item.name + '</strong><br>' +
                '<span style="color:#b45309;font-size:12px;">Session: ' + item.session_code + '</span></div>';
            });
            listHtml += '</div>';

            var alertDiv = document.createElement('div');
            alertDiv.id = 'stockTakeAlert';
            alertDiv.style.cssText = 'background:#fef3c7;border:1px solid #f59e0b;border-radius:10px;padding:14px 16px;margin-bottom:16px;';
            alertDiv.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">' +
              '<svg style="width:20px;height:20px;fill:none;stroke:#b45309;stroke-width:2;flex-shrink:0;"><circle cx="10" cy="10" r="9"/><line x1="10" y1="6" x2="10" y2="11"/><circle cx="10" cy="14" r="0.5" fill="#b45309"/></svg>' +
              '<strong style="color:#92400e;font-size:14px;">Stock Take In Progress</strong></div>' +
              '<p style="font-size:13px;color:#92400e;margin:0;">The following product(s) are currently under active stock take and cannot be stock in / purchased:</p>' +
              listHtml;

            var existing = document.getElementById('stockTakeAlert');
            if (existing) existing.remove();
            var mainEl = document.querySelector('.main');
            mainEl.insertBefore(alertDiv, mainEl.children[1]);

            return;
          }
        } catch(e) {}
      }

      // No blocked items, proceed with order
      finishOrder();
    }
  };
  xhr.send(JSON.stringify({ barcodes: barcodes }));
}

function finishOrder() {
  var btn = document.getElementById('btnConfirm');
  var btnText = document.getElementById('btnText');
  btnText.textContent = 'Submitting…';
  btn.disabled = true;

  // Send order to backend
  var txtTo = (document.getElementById('txtTo').value || '').trim();
  var payload = JSON.stringify({ orderType: orderType, items: items, txtTo: txtTo });

  var xhr = new XMLHttpRequest();
  xhr.open('POST', 'submit_order.php', true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      btn.disabled = false;
      document.querySelectorAll('input[name="orderType"]').forEach(function(r) { r.disabled = false; });

      var overlay = document.getElementById('successOverlay');
      var title = document.getElementById('successTitle');
      var msg = document.getElementById('successMsg');

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success) {
            sessionStorage.removeItem('confirmItems');
            sessionStorage.removeItem('cart');
            // Bust client-side product cache so qty updates immediately
            localStorage.removeItem('pw_all_products_data');
            localStorage.removeItem('pw_all_products_ts');
            // Redirect to preview/print page
            window.location.href = 'stockin_preview.php?salnum=' + encodeURIComponent(resp.salnum);
          } else {
            alert('Order failed: ' + (resp.error || 'Unknown error'));
            updateType();
          }
        } catch(e) {
          alert('Order failed: Invalid server response');
          updateType();
        }
      } else {
        alert('Order failed: Server error (' + xhr.status + ')');
        updateType();
      }
    }
  };
  xhr.send(payload);
}

render();
</script>
</body>
</html>
