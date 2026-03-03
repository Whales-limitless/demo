<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Ensure orderlist2 summary table exists
$connect->query("CREATE TABLE IF NOT EXISTS `orderlist2` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `SALNUM` varchar(100) NOT NULL DEFAULT '',
  `ACCODE` varchar(20) NOT NULL DEFAULT '',
  `NAME` varchar(100) NOT NULL DEFAULT '',
  `ADMINRMK` mediumtext DEFAULT NULL,
  `TXTTO` varchar(200) NOT NULL DEFAULT '',
  `SDATE` date DEFAULT NULL,
  `TTIME` time DEFAULT NULL,
  `SUMQTY` int(11) NOT NULL DEFAULT 0,
  `HP` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Build initial orderlist2
$connect->query("TRUNCATE TABLE `orderlist2`");
$connect->query("INSERT INTO `orderlist2` (SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUMQTY) SELECT SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUM(QTY) AS SUMQTY FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND BARCODE <> 'PT' GROUP BY SALNUM,ACCODE ORDER BY SALNUM DESC");
$connect->query("UPDATE orderlist2 AS b INNER JOIN MEMBER AS g ON b.ACCODE = g.ACCODE SET b.HP = g.HP");

// Count new orders
$newOrderCount = 0;
$q = $connect->query("SELECT COUNT(DISTINCT SALNUM) as cnt FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0'");
if ($q && $row = $q->fetch_assoc()) $newOrderCount = (int)$row['cnt'];

// Fetch orders
$orders = [];
$orderResult = $connect->query("SELECT * FROM `orderlist2` ORDER BY SALNUM DESC");
if ($orderResult) { while ($r = $orderResult->fetch_assoc()) $orders[] = $r; }

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Live Orders</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }

:root {
    --primary: #C8102E;
    --primary-dark: #a00d24;
    --surface: #ffffff;
    --bg: #f3f4f6;
    --text: #1a1a1a;
    --text-muted: #6b7280;
    --radius: 14px;
    --radius-sm: 10px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
    --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }

/* ── Layout ── */
.dashboard-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }

/* ── Status Bar ── */
.status-bar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
}
.live-indicator { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
.live-dot { width: 10px; height: 10px; background: #22c55e; border-radius: 50%; animation: pulse 2s infinite; flex-shrink: 0; }
.live-dot.error { background: #ef4444; animation: none; }
.live-dot.polling { background: #f59e0b; }
@keyframes pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.4); }
    50% { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
}
.countdown-label { color: var(--text-muted); font-size: 13px; }

.status-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.sound-toggle {
    background: #6b7280; color: #fff; border: none; padding: 9px 14px; border-radius: var(--radius-sm);
    font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition);
}
.sound-toggle:hover { background: #4b5563; }
.sound-toggle.enabled { background: #22c55e; }
.sound-toggle.enabled:hover { background: #16a34a; }

.notification-btn {
    position: relative; background: var(--primary); color: #fff; border: none; padding: 9px 20px;
    border-radius: var(--radius-sm); font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background var(--transition);
}
.notification-btn:hover { background: var(--primary-dark); }
.notification-badge {
    position: absolute; top: -6px; right: -6px; background: #ef4444; color: #fff; font-size: 11px;
    font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; padding: 0 5px;
}

/* ── Toolbar ── */
.toolbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.search-box { position: relative; flex: 1; max-width: 360px; min-width: 180px; }
.search-box input {
    width: 100%; padding: 10px 14px 10px 38px; border: 1px solid #d1d5db; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition);
    background: var(--surface);
}
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.order-count { font-size: 13px; color: var(--text-muted); font-weight: 500; }

/* ── Order Cards Grid ── */
.order-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 14px;
}

/* ── Order Card ── */
.order-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    overflow: hidden;
    transition: transform var(--transition), box-shadow var(--transition);
    display: flex;
    flex-direction: column;
}
.order-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

.order-card.card-new { animation: cardHighlight 2.5s ease-out; }
@keyframes cardHighlight {
    0% { box-shadow: 0 0 0 3px rgba(200,16,46,0.5), var(--shadow-md); }
    100% { box-shadow: var(--shadow-md); }
}

.card-header-strip {
    background: var(--text);
    color: #fff;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.card-header-strip .order-num {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.02em;
}
.card-header-strip .order-date {
    font-size: 12px;
    opacity: 0.8;
    white-space: nowrap;
}

.card-body-content {
    padding: 14px 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.card-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    line-height: 1.4;
}
.card-row .row-icon {
    color: var(--text-muted);
    width: 16px;
    text-align: center;
    flex-shrink: 0;
    margin-top: 2px;
    font-size: 12px;
}
.card-row .row-label {
    color: var(--text-muted);
    min-width: 50px;
    flex-shrink: 0;
    font-weight: 500;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.card-row .row-value {
    flex: 1;
    word-break: break-word;
}

.qty-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 12px;
    padding: 2px 10px;
    border-radius: 6px;
}

.card-footer-actions {
    padding: 10px 16px;
    border-top: 1px solid #f0f1f3;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.btn-card {
    flex: 1;
    min-width: 0;
    padding: 8px 6px;
    border: none;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition);
    text-decoration: none;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
}
.btn-card:hover { opacity: 0.9; color: #fff; }
.btn-card.c-view { background: #6b7280; }
.btn-card.c-done { background: #3b82f6; }
.btn-card.c-pay  { background: #22c55e; }
.btn-card.c-del  { background: #ef4444; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
    grid-column: 1 / -1;
}
.empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }
.empty-state p { font-size: 15px; }

/* Modal */
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .dashboard-content { padding: 14px; }
    .status-bar { flex-direction: column; align-items: flex-start; }
    .order-grid { grid-template-columns: 1fr; gap: 10px; }
    .search-box { max-width: 100%; }
    .btn-card { font-size: 11px; padding: 7px 4px; }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .order-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="dashboard-content">

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="live-indicator">
            <span class="live-dot" id="liveDot"></span>
            <span>Live View</span>
            <span class="countdown-label">Refresh in <strong id="seconds">15</strong>s</span>
        </div>
        <div class="status-right">
            <button type="button" class="sound-toggle" id="soundToggle" onclick="toggleSound();">
                <i class="fas fa-volume-mute" id="soundIcon"></i>
            </button>
            <button type="button" class="notification-btn" id="notifBtn" onclick="acknowledgeOrders();">
                <i class="fas fa-bell"></i> Notification
                <span class="notification-badge" id="notifBadge" style="<?php echo $newOrderCount > 0 ? '' : 'display:none;'; ?>"><?php echo $newOrderCount; ?></span>
            </button>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search orders...">
        </div>
        <div class="order-count" id="orderCount"><?php echo count($orders); ?> order(s)</div>
    </div>

    <!-- Order Cards -->
    <div class="order-grid" id="orderGrid">
        <?php if (count($orders) === 0): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No pending orders</p>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $order):
            $salnum = htmlspecialchars($order['SALNUM'] ?? '');
            $name   = htmlspecialchars($order['NAME'] ?? '');
            $sdate  = !empty($order['SDATE']) ? date('d/m/Y', strtotime($order['SDATE'])) : '';
            $ttime  = htmlspecialchars($order['TTIME'] ?? '');
            $qty    = htmlspecialchars($order['SUMQTY'] ?? '0');
            $txtto  = htmlspecialchars($order['TXTTO'] ?? '');
            $rmk    = htmlspecialchars($order['ADMINRMK'] ?? '');
        ?>
        <div class="order-card" data-salnum="<?php echo $salnum; ?>">
            <div class="card-header-strip">
                <span class="order-num">#<?php echo $salnum; ?></span>
                <span class="order-date"><i class="far fa-calendar-alt"></i> <?php echo $sdate; ?> <?php echo $ttime; ?></span>
            </div>
            <div class="card-body-content">
                <div class="card-row">
                    <span class="row-icon"><i class="fas fa-user"></i></span>
                    <span class="row-label">Name</span>
                    <span class="row-value"><strong><?php echo $name; ?></strong></span>
                </div>
                <div class="card-row">
                    <span class="row-icon"><i class="fas fa-boxes-stacked"></i></span>
                    <span class="row-label">Qty</span>
                    <span class="row-value"><span class="qty-badge"><?php echo $qty; ?></span></span>
                </div>
                <?php if ($txtto !== ''): ?>
                <div class="card-row">
                    <span class="row-icon"><i class="fas fa-truck"></i></span>
                    <span class="row-label">To</span>
                    <span class="row-value"><?php echo $txtto; ?></span>
                </div>
                <?php endif; ?>
                <?php if ($rmk !== ''): ?>
                <div class="card-row">
                    <span class="row-icon"><i class="fas fa-comment"></i></span>
                    <span class="row-label">Remark</span>
                    <span class="row-value"><?php echo $rmk; ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer-actions">
                <a href="order_detail.php?salnum=<?php echo $salnum; ?>" class="btn-card c-view"><i class="fas fa-eye"></i> View</a>
                <button type="button" onclick="donebtn('<?php echo $salnum; ?>');" class="btn-card c-done"><i class="fas fa-check"></i> Done</button>
                <button type="button" onclick="editbtn('<?php echo $salnum; ?>');" class="btn-card c-pay"><i class="fas fa-dollar-sign"></i> Pay</button>
                <button type="button" onclick="deletebtn('<?php echo $salnum; ?>');" class="btn-card c-del"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit/Success Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Update Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="remark">Remark</label>
                    <input type="text" id="remark" class="form-control" placeholder="Enter remark">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="rowtransno">Transaction No</label>
                    <input type="text" id="rowtransno" class="form-control" placeholder="Enter transaction number">
                </div>
                <input type="hidden" id="pid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="successbtn();">
                    <i class="fas fa-check"></i> Save & Mark Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// =====================================================================
// PRODUCTION-READY LIVE VIEW (Card UI)
// - AJAX polling every 15s (no page reload)
// - Preserves search, scroll, modal state
// - Web Audio API notification sound (browser-compliant)
// - Exponential backoff on errors, auto-recovers
// - Visibility API: pauses when tab hidden
// =====================================================================

var POLL_INTERVAL   = 15;
var MAX_POLL        = 60;
var pollTimer       = null;
var countdown       = POLL_INTERVAL;
var currentInterval = POLL_INTERVAL;
var errors          = 0;
var knownSalnums    = {};
var soundEnabled    = false;
var audioCtx        = null;
var notifBuffer     = null;
var modalOpen       = false;

// Seed known orders
<?php foreach ($orders as $o): ?>
knownSalnums['<?php echo addslashes($o['SALNUM'] ?? ''); ?>'] = true;
<?php endforeach; ?>

// ── Audio ──────────────────────────────────────────────

function initAudio() {
    if (audioCtx) return;
    try {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        fetch('sound/Melody-notification-sound.mp3')
            .then(function(r) { return r.arrayBuffer(); })
            .then(function(b) { return audioCtx.decodeAudioData(b); })
            .then(function(d) { notifBuffer = d; })
            .catch(function() { notifBuffer = null; });
    } catch(e) { audioCtx = null; }
}

function playSound() {
    if (!soundEnabled || !audioCtx || !notifBuffer) return;
    if (audioCtx.state === 'suspended') audioCtx.resume();
    var src = audioCtx.createBufferSource();
    src.buffer = notifBuffer;
    src.connect(audioCtx.destination);
    src.start(0);
}

function toggleSound() {
    soundEnabled = !soundEnabled;
    var icon = document.getElementById('soundIcon');
    var btn  = document.getElementById('soundToggle');
    if (soundEnabled) {
        initAudio();
        icon.className = 'fas fa-volume-up';
        btn.classList.add('enabled');
        setTimeout(playSound, 300);
    } else {
        icon.className = 'fas fa-volume-mute';
        btn.classList.remove('enabled');
    }
}

// ── Polling ────────────────────────────────────────────

function startPolling() {
    countdown = currentInterval;
    updateCountdown();
    clearInterval(pollTimer);
    pollTimer = setInterval(function() {
        countdown--;
        if (countdown <= 0) { doPoll(); countdown = currentInterval; }
        updateCountdown();
    }, 1000);
}

function updateCountdown() {
    var el = document.getElementById('seconds');
    if (el) el.textContent = countdown < 10 ? '0' + countdown : countdown;
}

function doPoll() {
    if (modalOpen) return;
    var dot = document.getElementById('liveDot');
    dot.className = 'live-dot polling';

    $.ajax({
        type: 'POST', url: 'admin_ajax.php', data: { action: 'poll' },
        dataType: 'json', timeout: 10000,
        success: function(data) {
            errors = 0;
            currentInterval = POLL_INTERVAL;
            dot.className = 'live-dot';

            var orders = data.orders || [];
            renderCards(orders);
            updateBadge(data.new_count || 0);

            // Detect new arrivals
            var hasNew = false;
            orders.forEach(function(o) {
                if (!knownSalnums[o.SALNUM]) { hasNew = true; knownSalnums[o.SALNUM] = true; }
            });
            if (hasNew && data.new_count > 0) playSound();
        },
        error: function() {
            errors++;
            currentInterval = Math.min(MAX_POLL, POLL_INTERVAL * Math.pow(2, errors - 1));
            countdown = currentInterval;
            dot.className = 'live-dot error';
        }
    });
}

// ── Render Cards ───────────────────────────────────────

function renderCards(orders) {
    var grid = document.getElementById('orderGrid');
    var query = (document.getElementById('searchInput').value || '').toLowerCase();

    if (orders.length === 0) {
        grid.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No pending orders</p></div>';
        document.getElementById('orderCount').textContent = '0 order(s)';
        return;
    }

    var html = '';
    var visible = 0;

    for (var i = 0; i < orders.length; i++) {
        var o = orders[i];
        var salnum = esc(o.SALNUM || '');
        var name   = esc(o.NAME || '');
        var sdate  = formatDate(o.SDATE);
        var ttime  = esc(o.TTIME || '');
        var qty    = esc((o.SUMQTY || '0') + '');
        var txtto  = esc(o.TXTTO || '');
        var rmk    = esc(o.ADMINRMK || '');
        var isNew  = !knownSalnums[o.SALNUM];

        var search = ((o.SALNUM||'') + ' ' + (o.NAME||'') + ' ' + (o.TXTTO||'') + ' ' + (o.ADMINRMK||'') + ' ' + (o.SDATE||'')).toLowerCase();
        var show = !query || search.indexOf(query) > -1;
        if (show) visible++;

        html += '<div class="order-card' + (isNew ? ' card-new' : '') + '" data-salnum="' + salnum + '"' + (show ? '' : ' style="display:none;"') + '>';

        // Header
        html += '<div class="card-header-strip">';
        html += '<span class="order-num">#' + salnum + '</span>';
        html += '<span class="order-date"><i class="far fa-calendar-alt"></i> ' + sdate + ' ' + ttime + '</span>';
        html += '</div>';

        // Body
        html += '<div class="card-body-content">';
        html += '<div class="card-row"><span class="row-icon"><i class="fas fa-user"></i></span><span class="row-label">Name</span><span class="row-value"><strong>' + name + '</strong></span></div>';
        html += '<div class="card-row"><span class="row-icon"><i class="fas fa-boxes-stacked"></i></span><span class="row-label">Qty</span><span class="row-value"><span class="qty-badge">' + qty + '</span></span></div>';
        if (txtto) html += '<div class="card-row"><span class="row-icon"><i class="fas fa-truck"></i></span><span class="row-label">To</span><span class="row-value">' + txtto + '</span></div>';
        if (rmk) html += '<div class="card-row"><span class="row-icon"><i class="fas fa-comment"></i></span><span class="row-label">Remark</span><span class="row-value">' + rmk + '</span></div>';
        html += '</div>';

        // Footer actions
        html += '<div class="card-footer-actions">';
        html += '<a href="order_detail.php?salnum=' + salnum + '" class="btn-card c-view"><i class="fas fa-eye"></i> View</a>';
        html += '<button type="button" onclick="donebtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-card c-done"><i class="fas fa-check"></i> Done</button>';
        html += '<button type="button" onclick="editbtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-card c-pay"><i class="fas fa-dollar-sign"></i> Pay</button>';
        html += '<button type="button" onclick="deletebtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-card c-del"><i class="fas fa-trash"></i></button>';
        html += '</div></div>';
    }

    grid.innerHTML = html;
    document.getElementById('orderCount').textContent = visible + ' order(s)';
}

function updateBadge(count) {
    var b = document.getElementById('notifBadge');
    if (count > 0) { b.textContent = count; b.style.display = 'flex'; }
    else { b.style.display = 'none'; }
}

function formatDate(ds) {
    if (!ds) return '';
    var p = ds.split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : esc(ds);
}

function esc(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s || ''));
    return d.innerHTML;
}

// ── Visibility API ─────────────────────────────────────

document.addEventListener('visibilitychange', function() {
    if (document.hidden) { clearInterval(pollTimer); pollTimer = null; }
    else { doPoll(); startPolling(); }
});

// ── Modal tracking ─────────────────────────────────────

$('#editModal').on('show.bs.modal', function() { modalOpen = true; });
$('#editModal').on('hidden.bs.modal', function() { modalOpen = false; });

// ── Search filter ──────────────────────────────────────

document.getElementById('searchInput').addEventListener('input', function() {
    var query = this.value.toLowerCase();
    var cards = document.querySelectorAll('.order-card');
    var visible = 0;
    cards.forEach(function(card) {
        var text = card.textContent.toLowerCase();
        if (!query || text.indexOf(query) > -1) { card.style.display = ''; visible++; }
        else { card.style.display = 'none'; }
    });
    document.getElementById('orderCount').textContent = visible + ' order(s)';
});

// ── Acknowledge ────────────────────────────────────────

function acknowledgeOrders() {
    $.post('admin_ajax.php', { action: 'noted' }, function() {
        updateBadge(0);
        Swal.fire({ icon: 'success', text: 'All notifications acknowledged', timer: 1200, showConfirmButton: false });
    }, 'json');
}

// ── Order Actions ──────────────────────────────────────

function editbtn(id) {
    $('#editModal').modal('show');
    $.post('admin_ajax.php', { id: id, action: 'detail' }, function(data) {
        var parts = data.split('|');
        $('#remark').val(parts[0]);
        $('#rowtransno').val(parts[1]);
        $('#pid').val(id);
    });
}

$('#editModal').on('shown.bs.modal', function() { $('#remark').trigger('focus'); });
$('#remark').on('keydown', function(e) { if (e.which === 13) { e.preventDefault(); $('#rowtransno').focus(); } });
$('#rowtransno').on('keydown', function(e) { if (e.which === 13) { e.preventDefault(); successbtn(); } });

function donebtn(id) {
    Swal.fire({
        title: 'Mark as Done?', text: 'This order will be marked as completed.', icon: 'question',
        showCancelButton: true, confirmButtonColor: '#3b82f6', cancelButtonColor: '#6b7280', confirmButtonText: 'Yes, Done'
    }).then(function(r) {
        if (r.isConfirmed) {
            $.post('admin_ajax.php', { id: id, action: 'done' }, function(data) {
                if (data.trim() === 'Saved.') {
                    Swal.fire({ icon: 'success', text: 'Marked as Done', timer: 1500, showConfirmButton: false });
                    doPoll();
                } else Swal.fire({ icon: 'error', title: 'Error', text: data });
            });
        }
    });
}

function deletebtn(id) {
    Swal.fire({
        title: 'Delete this order?', text: 'This action cannot be undone.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280', confirmButtonText: 'Yes, Delete'
    }).then(function(r) {
        if (r.isConfirmed) {
            $.post('admin_ajax.php', { id: id, action: 'delete' }, function(data) {
                if (data.trim() === 'Deleted.') {
                    Swal.fire({ icon: 'success', text: 'Order Deleted', timer: 1500, showConfirmButton: false });
                    doPoll();
                } else Swal.fire({ icon: 'error', title: 'Error', text: data });
            });
        }
    });
}

function successbtn() {
    var remark = document.getElementById('remark').value;
    var rowtransno = document.getElementById('rowtransno').value;
    var pid = document.getElementById('pid').value;
    $.post('admin_ajax.php', { remark: remark, rowtransno: rowtransno, pid: pid, action: 'success' }, function(v) {
        if (v.trim() === 'Saved.') {
            $('#editModal').modal('hide');
            Swal.fire({ icon: 'success', text: 'Payment Saved', timer: 1500, showConfirmButton: false });
            doPoll();
        } else Swal.fire({ icon: 'error', title: 'Error', text: v });
    });
}

// ── Init ───────────────────────────────────────────────
startPolling();
</script>
</body>
</html>
