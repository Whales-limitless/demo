<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

// Auth check
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

// Prepare orderlist2 summary table
$connect->query("TRUNCATE TABLE `orderlist2`");
$connect->query("INSERT INTO `orderlist2` (SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUMQTY) SELECT SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUM(QTY) AS SUMQTY FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND BARCODE <> 'PT' GROUP BY SALNUM,ACCODE ORDER BY SALNUM DESC");
$connect->query("UPDATE orderlist2 AS b INNER JOIN MEMBER AS g ON b.ACCODE = g.ACCODE SET b.HP = g.HP");

// Count new orders
$newOrderCount = 0;
$query56 = $connect->query("SELECT COUNT(DISTINCT SALNUM) as cnt FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0'");
if ($query56 && $row = $query56->fetch_assoc()) {
    $newOrderCount = (int)$row['cnt'];
}

// Fetch all orders from orderlist2
$orders = [];
$orderResult = $connect->query("SELECT * FROM `orderlist2` ORDER BY SALNUM DESC");
if ($orderResult) {
    while ($r = $orderResult->fetch_assoc()) {
        $orders[] = $r;
    }
}

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Admin Dashboard - Live View</title>
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
    --radius: 12px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    -webkit-font-smoothing: antialiased;
    margin: 0;
}

/* Dashboard Content */
.dashboard-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px 24px 40px;
}

/* Status Bar */
.status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
}

.live-dot {
    width: 10px;
    height: 10px;
    background: #22c55e;
    border-radius: 50%;
    animation: pulse 2s infinite;
    flex-shrink: 0;
}

.live-dot.error {
    background: #ef4444;
    animation: none;
}

.live-dot.polling {
    background: #f59e0b;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.4); }
    50% { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
}

.countdown-label {
    color: var(--text-muted);
    font-size: 13px;
}

.status-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.notification-btn {
    position: relative;
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 9px 20px;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.notification-btn:hover { background: var(--primary-dark); }

.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
}

.sound-toggle {
    background: #6b7280;
    color: #fff;
    border: none;
    padding: 9px 14px;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.sound-toggle:hover { background: #4b5563; }
.sound-toggle.enabled { background: #22c55e; }
.sound-toggle.enabled:hover { background: #16a34a; }

/* Table Card */
.table-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    padding: 20px;
    overflow: hidden;
}

/* Search & Toolbar */
.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 360px;
}

.search-box input {
    width: 100%;
    padding: 9px 14px 9px 36px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    outline: none;
    transition: border-color var(--transition);
}

.search-box input:focus { border-color: var(--primary); }

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 13px;
}

.table-toolbar .order-count {
    font-size: 13px;
    color: var(--text-muted);
}

/* Orders Table */
.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
    border-radius: var(--radius);
    overflow: hidden;
}

.orders-table thead th {
    background: var(--text);
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 12px 14px;
    white-space: nowrap;
    text-align: left;
}

.orders-table thead th:first-child { border-top-left-radius: var(--radius); }
.orders-table thead th:last-child { border-top-right-radius: var(--radius); }

.orders-table tbody td {
    padding: 11px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f1f3;
}

.orders-table tbody tr:hover { background: #f9fafb; }

.orders-table tbody tr:last-child td { border-bottom: none; }
.orders-table tbody tr:last-child td:first-child { border-bottom-left-radius: var(--radius); }
.orders-table tbody tr:last-child td:last-child { border-bottom-right-radius: var(--radius); }

.orders-table tbody tr.no-results td {
    text-align: center;
    padding: 40px;
    color: var(--text-muted);
}

/* Highlight new row */
.orders-table tbody tr.row-new {
    animation: highlightNew 2s ease-out;
}

@keyframes highlightNew {
    0% { background: #fef3c7; }
    100% { background: transparent; }
}

/* Action Buttons */
.btn-action {
    padding: 5px 12px;
    border: none;
    border-radius: 6px;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition);
    display: inline-block;
    margin: 1px;
    text-decoration: none;
    color: #fff;
}

.btn-view { background: #6b7280; }
.btn-view:hover { background: #4b5563; color: #fff; }
.btn-done { background: #3b82f6; }
.btn-done:hover { background: #2563eb; }
.btn-success-action { background: #22c55e; }
.btn-success-action:hover { background: #16a34a; }
.btn-delete { background: #ef4444; }
.btn-delete:hover { background: #dc2626; }

/* Modal */
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

@media (max-width: 768px) {
    .dashboard-content { padding: 16px; }
    .status-bar { flex-direction: column; align-items: flex-start; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<!-- Top Navigation -->
<?php $currentPage = 'dashboard'; include('nav.php'); ?>

<!-- Dashboard Content -->
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
                <?php if ($newOrderCount > 0): ?>
                <span class="notification-badge" id="notifBadge"><?php echo $newOrderCount; ?></span>
                <?php else: ?>
                <span class="notification-badge" id="notifBadge" style="display:none;">0</span>
                <?php endif; ?>
            </button>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search orders...">
            </div>
            <div class="order-count" id="orderCount">
                <?php echo count($orders); ?> order(s)
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="orders-table" id="ordersTable">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Order No</th>
                        <th>Name</th>
                        <th>Qty</th>
                        <th>To / Remark</th>
                        <th>Admin Remark</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <?php if (count($orders) === 0): ?>
                    <tr class="no-results">
                        <td colspan="9"><i class="fas fa-inbox" style="font-size:24px;margin-bottom:8px;display:block;"></i>No orders found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $i => $order): ?>
                    <tr data-salnum="<?php echo htmlspecialchars($order['SALNUM'] ?? ''); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo !empty($order['SDATE']) ? date('d/m/Y', strtotime($order['SDATE'])) : ''; ?></td>
                        <td><?php echo htmlspecialchars($order['TTIME'] ?? ''); ?></td>
                        <td><strong><?php echo htmlspecialchars($order['SALNUM'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['NAME'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($order['SUMQTY'] ?? '0'); ?></td>
                        <td><?php echo htmlspecialchars($order['TXTTO'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($order['ADMINRMK'] ?? ''); ?></td>
                        <td style="white-space:nowrap">
                            <a href="order_detail.php?salnum=<?php echo htmlspecialchars($order['SALNUM'] ?? ''); ?>" class="btn-action btn-view"><i class="fas fa-eye"></i> View</a>
                            <button type="button" onclick="donebtn('<?php echo htmlspecialchars($order['SALNUM'] ?? ''); ?>');" class="btn-action btn-done"><i class="fas fa-check"></i> Done</button>
                            <button type="button" onclick="editbtn('<?php echo htmlspecialchars($order['SALNUM'] ?? ''); ?>');" class="btn-action btn-success-action"><i class="fas fa-dollar-sign"></i> Success</button>
                            <button type="button" onclick="deletebtn('<?php echo htmlspecialchars($order['SALNUM'] ?? ''); ?>');" class="btn-action btn-delete"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit/Success Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Update Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// =====================================================================
// PRODUCTION-READY LIVE VIEW
// - AJAX polling (no full page reload)
// - Preserves search, scroll, modal state
// - Notification sound with user-gesture unlock (browser requirement)
// - Exponential backoff on errors, recovers automatically
// - Visibility API: pauses polling when tab is hidden
// =====================================================================

var POLL_INTERVAL = 15;       // seconds between polls
var MAX_POLL_INTERVAL = 60;   // max backoff seconds
var pollTimer = null;
var countdown = POLL_INTERVAL;
var currentInterval = POLL_INTERVAL;
var consecutiveErrors = 0;
var knownSalnums = {};        // track known orders to detect new ones
var soundEnabled = false;     // must be enabled by user click (browser policy)
var audioCtx = null;
var notifBuffer = null;
var modalOpen = false;

// Initialize known orders from server-rendered data
<?php foreach ($orders as $order): ?>
knownSalnums['<?php echo addslashes($order['SALNUM'] ?? ''); ?>'] = true;
<?php endforeach; ?>

// ===================== AUDIO SETUP =====================

function initAudio() {
    if (audioCtx) return;
    try {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        // Load notification sound
        fetch('sound/notification.wav')
            .then(function(r) { return r.arrayBuffer(); })
            .then(function(buf) { return audioCtx.decodeAudioData(buf); })
            .then(function(decoded) { notifBuffer = decoded; })
            .catch(function() { notifBuffer = null; });
    } catch(e) {
        audioCtx = null;
    }
}

function playNotifSound() {
    if (!soundEnabled || !audioCtx || !notifBuffer) return;
    // Resume context if suspended (browser policy)
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    var source = audioCtx.createBufferSource();
    source.buffer = notifBuffer;
    source.connect(audioCtx.destination);
    source.start(0);
}

function toggleSound() {
    soundEnabled = !soundEnabled;
    var icon = document.getElementById('soundIcon');
    var btn = document.getElementById('soundToggle');
    if (soundEnabled) {
        initAudio();
        icon.className = 'fas fa-volume-up';
        btn.classList.add('enabled');
        // Play once to confirm
        setTimeout(playNotifSound, 300);
    } else {
        icon.className = 'fas fa-volume-mute';
        btn.classList.remove('enabled');
    }
}

// ===================== POLLING =====================

function startPolling() {
    countdown = currentInterval;
    updateCountdown();
    clearInterval(pollTimer);
    pollTimer = setInterval(function() {
        countdown--;
        if (countdown <= 0) {
            doPoll();
            countdown = currentInterval;
        }
        updateCountdown();
    }, 1000);
}

function updateCountdown() {
    var el = document.getElementById('seconds');
    if (el) el.textContent = countdown < 10 ? '0' + countdown : countdown;
}

function doPoll() {
    // Don't poll if modal is open (user is working)
    if (modalOpen) return;

    var dot = document.getElementById('liveDot');
    dot.className = 'live-dot polling';

    $.ajax({
        type: 'POST',
        url: 'admin_ajax.php',
        data: { action: 'poll' },
        dataType: 'json',
        timeout: 10000,
        success: function(data) {
            consecutiveErrors = 0;
            currentInterval = POLL_INTERVAL;
            dot.className = 'live-dot';

            // Update table
            renderOrders(data.orders || []);

            // Update notification badge
            updateBadge(data.new_count || 0);

            // Detect new orders and play sound
            var newOrders = [];
            (data.orders || []).forEach(function(o) {
                if (!knownSalnums[o.SALNUM]) {
                    newOrders.push(o.SALNUM);
                    knownSalnums[o.SALNUM] = true;
                }
            });

            if (newOrders.length > 0 && data.new_count > 0) {
                playNotifSound();
            }
        },
        error: function() {
            consecutiveErrors++;
            // Exponential backoff: 15 -> 30 -> 60 (capped)
            currentInterval = Math.min(MAX_POLL_INTERVAL, POLL_INTERVAL * Math.pow(2, consecutiveErrors - 1));
            countdown = currentInterval;
            dot.className = 'live-dot error';
        }
    });
}

function renderOrders(orders) {
    var tbody = document.getElementById('ordersBody');
    var searchQuery = (document.getElementById('searchInput').value || '').toLowerCase();

    if (orders.length === 0) {
        tbody.innerHTML = '<tr class="no-results"><td colspan="9"><i class="fas fa-inbox" style="font-size:24px;margin-bottom:8px;display:block;"></i>No orders found</td></tr>';
        document.getElementById('orderCount').textContent = '0 order(s)';
        return;
    }

    var html = '';
    var visibleCount = 0;
    for (var i = 0; i < orders.length; i++) {
        var o = orders[i];
        var salnum = escHtml(o.SALNUM || '');
        var name = escHtml(o.NAME || '');
        var txtto = escHtml(o.TXTTO || '');
        var adminrmk = escHtml(o.ADMINRMK || '');
        var sdate = o.SDATE ? formatDate(o.SDATE) : '';
        var ttime = escHtml(o.TTIME || '');
        var sumqty = escHtml((o.SUMQTY || '0') + '');

        var searchData = ((o.SDATE || '') + ' ' + (o.SALNUM || '') + ' ' + (o.NAME || '') + ' ' + (o.TXTTO || '') + ' ' + (o.ADMINRMK || '')).toLowerCase();
        var isVisible = !searchQuery || searchData.indexOf(searchQuery) > -1;
        var isNew = !knownSalnums[o.SALNUM] ? ' row-new' : '';

        if (isVisible) visibleCount++;

        html += '<tr data-salnum="' + salnum + '" class="' + isNew + '"' + (isVisible ? '' : ' style="display:none;"') + '>';
        html += '<td>' + (isVisible ? (visibleCount) : '') + '</td>';
        html += '<td>' + sdate + '</td>';
        html += '<td>' + ttime + '</td>';
        html += '<td><strong>' + salnum + '</strong></td>';
        html += '<td>' + name + '</td>';
        html += '<td>' + sumqty + '</td>';
        html += '<td>' + txtto + '</td>';
        html += '<td>' + adminrmk + '</td>';
        html += '<td style="white-space:nowrap">';
        html += '<a href="order_detail.php?salnum=' + salnum + '" class="btn-action btn-view"><i class="fas fa-eye"></i> View</a>';
        html += '<button type="button" onclick="donebtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-action btn-done"><i class="fas fa-check"></i> Done</button>';
        html += '<button type="button" onclick="editbtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-action btn-success-action"><i class="fas fa-dollar-sign"></i> Success</button>';
        html += '<button type="button" onclick="deletebtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-action btn-delete"><i class="fas fa-trash"></i> Delete</button>';
        html += '</td></tr>';
    }

    tbody.innerHTML = html;
    document.getElementById('orderCount').textContent = visibleCount + ' order(s)';
}

function updateBadge(count) {
    var badge = document.getElementById('notifBadge');
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('-');
    if (parts.length === 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
    return escHtml(dateStr);
}

// ===================== VISIBILITY API =====================

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(pollTimer);
        pollTimer = null;
    } else {
        // Tab visible again: poll immediately then restart
        doPoll();
        startPolling();
    }
});

// ===================== MODAL TRACKING =====================

$('#editModal').on('show.bs.modal', function() { modalOpen = true; });
$('#editModal').on('hidden.bs.modal', function() { modalOpen = false; });

// ===================== SEARCH FILTER =====================

document.getElementById('searchInput').addEventListener('input', function() {
    var query = this.value.toLowerCase();
    var rows = document.querySelectorAll('#ordersBody tr:not(.no-results)');
    var visibleCount = 0;

    rows.forEach(function(row) {
        var salnum = row.getAttribute('data-salnum') || '';
        var text = row.textContent.toLowerCase();
        if (!query || text.indexOf(query) > -1) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('orderCount').textContent = visibleCount + ' order(s)';

    // Re-number visible rows
    var num = 1;
    rows.forEach(function(row) {
        if (row.style.display !== 'none') {
            row.cells[0].textContent = num++;
        }
    });
});

// ===================== NOTIFICATION ACKNOWLEDGE =====================

function acknowledgeOrders() {
    $.ajax({
        type: 'POST',
        url: 'admin_ajax.php',
        data: { action: 'noted' },
        dataType: 'json',
        success: function() {
            updateBadge(0);
            Swal.fire({ icon: 'success', text: 'All notifications acknowledged', timer: 1200, showConfirmButton: false });
        }
    });
}

// ===================== ORDER ACTIONS =====================

// Edit/Success modal
function editbtn(id) {
    $('#editModal').modal('show');
    $.ajax({
        type: 'POST',
        url: 'admin_ajax.php',
        data: { id: id, action: "detail" },
        success: function(data) {
            var parts = data.split("|");
            $('#remark').val(parts[0]);
            $('#rowtransno').val(parts[1]);
            $('#pid').val(id);
        }
    });
}

// Autofocus modal
$('#editModal').on('shown.bs.modal', function() {
    $('#remark').trigger('focus');
});

// Enter key navigation in modal
$('#remark').on('keydown', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        $('#rowtransno').focus();
    }
});
$('#rowtransno').on('keydown', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        successbtn();
    }
});

// Done button
function donebtn(id) {
    Swal.fire({
        title: 'Mark as Done?',
        text: 'This order will be marked as completed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Done'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: 'admin_ajax.php',
                data: { id: id, action: "done" },
                success: function(data) {
                    if (data.trim() === 'Saved.') {
                        Swal.fire({ icon: 'success', text: 'Marked as Done', timer: 1500, showConfirmButton: false });
                        doPoll(); // Refresh table without full reload
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data });
                    }
                }
            });
        }
    });
}

// Delete button
function deletebtn(id) {
    Swal.fire({
        title: 'Delete this order?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: 'admin_ajax.php',
                data: { id: id, action: "delete" },
                success: function(data) {
                    if (data.trim() === 'Deleted.') {
                        Swal.fire({ icon: 'success', text: 'Order Deleted', timer: 1500, showConfirmButton: false });
                        doPoll(); // Refresh table without full reload
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data });
                    }
                }
            });
        }
    });
}

// Success button (save remark + transno)
function successbtn() {
    var remark = document.getElementById("remark").value;
    var rowtransno = document.getElementById("rowtransno").value;
    var pid = document.getElementById("pid").value;

    $.ajax({
        type: 'POST',
        url: 'admin_ajax.php',
        data: { remark: remark, rowtransno: rowtransno, pid: pid, action: "success" },
        success: function(value) {
            if (value.trim() === "Saved.") {
                $('#editModal').modal('hide');
                Swal.fire({ icon: 'success', text: 'Payment Saved', timer: 1500, showConfirmButton: false });
                doPoll(); // Refresh table without full reload
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: value });
            }
        }
    });
}

function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s || ''));
    return d.innerHTML;
}

// ===================== INIT =====================
startPolling();
</script>

</body>
</html>
