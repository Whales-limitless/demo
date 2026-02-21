<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Order</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,500;0,9..40,700;1,9..40,400&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
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
.type-toggle .type-icon { display: block; font-size: 20px; margin-bottom: 2px; }

.section-label { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.section-label .badge { background: var(--primary); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 10px; }

.summary-list { margin-bottom: 20px; }
.summary-item { display: flex; align-items: center; gap: 12px; background: var(--surface); border-radius: 12px; padding: 12px 14px; margin-bottom: 8px; box-shadow: var(--shadow-sm); animation: fadeUp 0.3s ease both; }
.summary-item .s-num { width: 28px; height: 28px; border-radius: 50%; background: var(--bg); display: grid; place-items: center; font-size: 12px; font-weight: 700; color: var(--text-muted); flex-shrink: 0; }
.summary-item .s-img { width: 52px; height: 52px; border-radius: 8px; object-fit: cover; flex-shrink: 0; background: var(--bg); }
.summary-item .s-details { flex: 1; min-width: 0; }
.summary-item .s-name { font-size: 13px; font-weight: 600; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.summary-item .s-meta { display: flex; align-items: center; gap: 8px; margin-top: 3px; font-size: 11px; color: var(--text-muted); }
.summary-item .s-rack { background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 4px; font-weight: 600; }
.summary-item .s-rack.unset { background: var(--bg); color: var(--text-muted); }
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
.btn-confirm .btn-icon { font-size: 18px; }

/* ── COUNTDOWN PROGRESS ── */
.countdown-bar { display: none; margin-bottom: 10px; }
.countdown-bar.active { display: block; }
.countdown-label { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; }
.countdown-label .tap-cancel { font-size: 11px; color: var(--text-muted); font-weight: 500; }
.progress-track { height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 3px; background: var(--primary); width: 0%; transition: none; }
.progress-fill.stock-in { background: var(--blue); }
.progress-fill.running { transition: width 2s linear; }

.btn-confirm.cancelling { background: #6b7280; }
.btn-confirm.cancelling:hover { background: #4b5563; }

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
@media (max-width: 480px) { .summary-item .s-img { width: 44px; height: 44px; } .summary-item .s-name { font-size: 12px; } .type-toggle label { padding: 10px 12px; font-size: 13px; } }
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
      <span class="type-icon">🛒</span>
      Purchase
    </label>
    <label>
      <input type="radio" name="orderType" value="stockin" onchange="updateType()">
      <span class="type-icon">📦</span>
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
    <div class="countdown-bar" id="countdownBar">
      <div class="countdown-label">
        <span id="countdownLabel">Purchase in 2…</span>
        <span class="tap-cancel">tap to cancel</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill" id="progressFill"></div>
      </div>
    </div>
    <button class="btn-confirm" id="btnConfirm" onclick="handleConfirmClick()">
      <span class="btn-icon">🛒</span>
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
// Default dummy data
var items = [
  { id: 1, name: 'VT PW 20-9438 ICE CREAM MOULD SET (3PCS)', img: 'https://picsum.photos/seed/p3/400/400', rack: null, qty: 2 },
  { id: 4, name: 'TAKE AWAY LUNCH BOX 750ML (50PCS/PKT)', img: 'https://picsum.photos/seed/p4/400/400', rack: 'A 12', qty: 5 },
  { id: 6, name: 'PP CUP 16OZ (50PCS/PKT)', img: 'https://picsum.photos/seed/p6/400/400', rack: 'B 01', qty: 10 },
  { id: 10, name: 'ELIANWARE WATER JUG 2.5L (1PC)', img: 'https://picsum.photos/seed/p10/400/400', rack: 'C 05', qty: 1 },
  { id: 12, name: 'ONESALL SQUARE CONTAINER 500ML', img: 'https://picsum.photos/seed/p12/400/400', rack: 'D 01', qty: 3 },
];

// Override with sessionStorage data if available
try {
  var stored = sessionStorage.getItem('confirmItems');
  if (stored) {
    var parsed = JSON.parse(stored);
    if (parsed && parsed.length > 0) items = parsed;
  }
} catch(e) { /* use defaults */ }

var orderType = 'purchase';

function render() {
  var list = document.getElementById('summaryList');
  list.innerHTML = items.map(function(item, i) {
    var rackHtml = item.rack ? '<span class="s-rack">📍 '+item.rack+'</span>' : '<span class="s-rack unset">No Rack</span>';
    return '<div class="summary-item" style="animation-delay:'+i*0.04+'s"><span class="s-num">'+(i+1)+'</span><img class="s-img" src="'+item.img+'" alt="'+item.name+'"><div class="s-details"><div class="s-name">'+item.name+'</div><div class="s-meta">'+rackHtml+'</div></div><div class="s-qty">×'+item.qty+'</div></div>';
  }).join('');
  document.getElementById('totalBadge').textContent = items.length;
  document.getElementById('totalItems').textContent = items.length;
  document.getElementById('totalQty').textContent = items.reduce(function(s,i){ return s+i.qty; }, 0);
}

function updateType() {
  orderType = document.querySelector('input[name="orderType"]:checked').value;
  var btn = document.getElementById('btnConfirm');
  var btnText = document.getElementById('btnText');
  var typeLabel = document.getElementById('orderTypeLabel');
  if (orderType === 'purchase') {
    btnText.textContent = 'Purchase'; btn.className = 'btn-confirm';
    btn.querySelector('.btn-icon').textContent = '🛒'; typeLabel.textContent = 'Purchase';
  } else {
    btnText.textContent = 'Stock In'; btn.className = 'btn-confirm stock-in';
    btn.querySelector('.btn-icon').textContent = '📦'; typeLabel.textContent = 'Stock In';
  }
}

var countdownTimer = null;
var isCounting = false;

function handleConfirmClick() {
  if (isCounting) {
    cancelCountdown();
  } else {
    startCountdown();
  }
}

function startCountdown() {
  var btn = document.getElementById('btnConfirm');
  var btnText = document.getElementById('btnText');
  var btnIcon = btn.querySelector('.btn-icon');
  var bar = document.getElementById('countdownBar');
  var fill = document.getElementById('progressFill');
  var countLabel = document.getElementById('countdownLabel');
  var label = orderType === 'purchase' ? 'Purchase' : 'Stock In';
  var count = 2;

  isCounting = true;

  // Reset and start smooth fill
  fill.classList.remove('running');
  fill.className = 'progress-fill' + (orderType === 'stockin' ? ' stock-in' : '');
  fill.style.width = '0%';
  bar.classList.add('active');
  void fill.offsetWidth;
  fill.classList.add('running');
  fill.style.width = '100%';

  // Update label and button
  countLabel.textContent = label + ' in ' + count + '…';
  btnIcon.textContent = '✕';
  btnText.textContent = 'Cancel';
  btn.className = 'btn-confirm cancelling';

  // Disable type toggle
  document.querySelectorAll('input[name="orderType"]').forEach(function(r){ r.disabled = true; });

  countdownTimer = setInterval(function() {
    count--;
    if (count > 0) {
      countLabel.textContent = label + ' in ' + count + '…';
    } else {
      clearInterval(countdownTimer);
      countdownTimer = null;
      countLabel.textContent = 'Submitting…';
      btnText.textContent = 'Submitting…';
      btnIcon.textContent = '';
      isCounting = false;
      setTimeout(function(){ finishOrder(); }, 300);
    }
  }, 1000);
}

function cancelCountdown() {
  clearInterval(countdownTimer);
  countdownTimer = null;
  isCounting = false;

  var btn = document.getElementById('btnConfirm');
  var btnText = document.getElementById('btnText');
  var btnIcon = btn.querySelector('.btn-icon');
  var bar = document.getElementById('countdownBar');
  var fill = document.getElementById('progressFill');

  // Hide bar
  bar.classList.remove('active');
  fill.classList.remove('running');
  fill.style.width = '0%';

  // Restore button
  document.querySelectorAll('input[name="orderType"]').forEach(function(r){ r.disabled = false; });
  if (orderType === 'purchase') {
    btnIcon.textContent = '🛒'; btnText.textContent = 'Purchase';
    btn.className = 'btn-confirm';
  } else {
    btnIcon.textContent = '📦'; btnText.textContent = 'Stock In';
    btn.className = 'btn-confirm stock-in';
  }
}

function finishOrder() {
  var bar = document.getElementById('countdownBar');
  var fill = document.getElementById('progressFill');
  bar.classList.remove('active');
  fill.classList.remove('running');
  fill.style.width = '0%';

  var btn = document.getElementById('btnConfirm');
  var overlay = document.getElementById('successOverlay');
  var title = document.getElementById('successTitle');
  var msg = document.getElementById('successMsg');
  if (orderType === 'purchase') { title.textContent = 'Purchase Submitted!'; msg.textContent = 'Your purchase order has been placed successfully.'; }
  else { title.textContent = 'Stock In Submitted!'; msg.textContent = 'Your stock in order has been recorded successfully.'; }
  document.querySelectorAll('input[name="orderType"]').forEach(function(r){ r.disabled = false; });
  overlay.classList.add('active');
  sessionStorage.removeItem('confirmItems');
}

render();
</script>
</body>
</html>