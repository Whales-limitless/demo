<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

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
  `PURCHASEDATE` date DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Ensure PURCHASEDATE column exists on orderlist (run once, safe to repeat)
$connect->query("ALTER TABLE `orderlist` ADD COLUMN `PURCHASEDATE` DATE DEFAULT NULL");
// Ensure PURCHASEDATE column exists on orderlist2
$connect->query("ALTER TABLE `orderlist2` ADD COLUMN `PURCHASEDATE` DATE DEFAULT NULL");

// Ensure branch_code column exists on orderlist2
$connect->query("ALTER TABLE `orderlist2` ADD COLUMN `branch_code` VARCHAR(20) DEFAULT ''");
$connect->query("ALTER TABLE `orderlist2` ADD COLUMN `branch_name` VARCHAR(100) DEFAULT ''");

$connect->query("TRUNCATE TABLE `orderlist2`");
$connect->query("INSERT INTO `orderlist2` (SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUMQTY,PURCHASEDATE,branch_code) SELECT SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUM(QTY) AS SUMQTY,PURCHASEDATE,branch_code FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND BARCODE <> 'PT' GROUP BY SALNUM,ACCODE ORDER BY SALNUM DESC");
$connect->query("UPDATE orderlist2 AS b INNER JOIN MEMBER AS g ON b.ACCODE = g.ACCODE SET b.HP = g.HP");
$connect->query("UPDATE orderlist2 AS o LEFT JOIN `branch` AS br ON o.branch_code = br.code SET o.branch_name = COALESCE(br.name, o.branch_code)");

$newOrderCount = 0;
$q = $connect->query("SELECT COUNT(DISTINCT SALNUM) as cnt FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0'");
if ($q && $row = $q->fetch_assoc()) $newOrderCount = (int)$row['cnt'];

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
    --radius: 12px;
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }

.dashboard-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }

/* Status Bar */
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

.refresh-btn {
    background: #3b82f6; color: #fff; border: none; padding: 9px 16px; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition);
    display: inline-flex; align-items: center; gap: 6px;
}
.refresh-btn:hover { background: #2563eb; }
.refresh-btn.spinning i { animation: spin 0.8s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.sound-toggle {
    background: #6b7280; color: #fff; border: none; padding: 9px 14px; border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition);
}
.sound-toggle:hover { background: #4b5563; }
.sound-toggle.enabled { background: #22c55e; }
.sound-toggle.enabled:hover { background: #16a34a; }

.notification-btn {
    position: relative; background: var(--primary); color: #fff; border: none; padding: 9px 20px;
    border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background var(--transition);
}
.notification-btn:hover { background: var(--primary-dark); }
.notification-badge {
    position: absolute; top: -6px; right: -6px; background: #ef4444; color: #fff; font-size: 11px;
    font-weight: 700; min-width: 20px; height: 20px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; padding: 0 5px;
}

/* Table Card */
.table-card {
    background: var(--surface); border-radius: var(--radius);
    box-shadow: var(--shadow-md); padding: 20px; overflow: hidden;
}
.table-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.search-box { position: relative; flex: 1; max-width: 360px; }
.search-box input {
    width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px;
    font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition);
}
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.order-count { font-size: 13px; color: var(--text-muted); }

/* Table */
.orders-table {
    width: 100%; border-collapse: separate; border-spacing: 0;
    font-size: 13px; border-radius: var(--radius); overflow: hidden;
}
.orders-table thead th {
    background: var(--text); color: #fff; font-weight: 600; font-size: 12px;
    text-transform: uppercase; letter-spacing: 0.03em; padding: 12px 14px;
    white-space: nowrap; text-align: left;
}
.orders-table thead th:first-child { border-top-left-radius: var(--radius); }
.orders-table thead th:last-child { border-top-right-radius: var(--radius); }
.orders-table tbody td {
    padding: 11px 14px; vertical-align: middle; border-bottom: 1px solid #f0f1f3;
}
.orders-table tbody tr:hover { background: #f9fafb; }
.orders-table tbody tr:last-child td { border-bottom: none; }
.orders-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }

/* New row highlight */
.orders-table tbody tr.row-new { animation: highlightNew 2.5s ease-out; }
@keyframes highlightNew {
    0% { background: #fef3c7; }
    100% { background: transparent; }
}

/* Action Buttons */
.btn-action {
    padding: 5px 12px; border: none; border-radius: 6px;
    font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all var(--transition); display: inline-block;
    margin: 1px; text-decoration: none; color: #fff;
}
.btn-action:hover { opacity: 0.85; color: #fff; }
.btn-view { background: #6b7280; }
.btn-edit { background: #f59e0b; }
.btn-done { background: #3b82f6; }
.btn-delete { background: #ef4444; }

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
            <button type="button" class="refresh-btn" id="refreshBtn" onclick="manualRefresh();" title="Refresh now">
                <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
            </button>
            <button type="button" class="sound-toggle" id="soundToggle" onclick="toggleSound();">
                <i class="fas fa-volume-mute" id="soundIcon"></i>
            </button>
            <button type="button" class="notification-btn" id="notifBtn" onclick="acknowledgeOrders();">
                <i class="fas fa-bell"></i> Notification
                <span class="notification-badge" id="notifBadge" style="<?php echo $newOrderCount > 0 ? '' : 'display:none;'; ?>"><?php echo $newOrderCount; ?></span>
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
            <div class="order-count" id="orderCount"><?php echo count($orders); ?> order(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="orders-table">
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
                        <th>Branch</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="ordersBody">
                    <?php if (count($orders) === 0): ?>
                    <tr class="no-results"><td colspan="10"><i class="fas fa-inbox" style="font-size:24px;margin-bottom:8px;display:block;"></i>No orders found</td></tr>
                    <?php else: ?>
                    <?php foreach ($orders as $i => $order):
                        $salnum = htmlspecialchars($order['SALNUM'] ?? '');
                    ?>
                    <tr data-salnum="<?php echo $salnum; ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo !empty($order['SDATE']) ? date('d/m/Y', strtotime($order['SDATE'])) : ''; ?></td>
                        <td><?php echo htmlspecialchars($order['TTIME'] ?? ''); ?></td>
                        <td><strong><?php echo $salnum; ?></strong></td>
                        <td><?php echo htmlspecialchars($order['NAME'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($order['SUMQTY'] ?? '0'); ?></td>
                        <td><?php echo htmlspecialchars($order['TXTTO'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($order['ADMINRMK'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($order['branch_name'] ?? $order['branch_code'] ?? ''); ?></td>
                        <td style="white-space:nowrap">
                            <a href="order_detail.php?salnum=<?php echo $salnum; ?>" class="btn-action btn-view"><i class="fas fa-eye"></i></a>
                            <button type="button" onclick="openEditModal('<?php echo $salnum; ?>', '<?php echo htmlspecialchars($order['ADMINRMK'] ?? '', ENT_QUOTES); ?>', '<?php echo $order['PURCHASEDATE'] ?? ''; ?>');" class="btn-action btn-edit"><i class="fas fa-pen"></i></button>
                            <?php if (($_SESSION['admin_type'] ?? '') === 'A'): ?>
                            <button type="button" onclick="donebtn('<?php echo $salnum; ?>');" class="btn-action btn-done"><i class="fas fa-check"></i></button>
                            <?php endif; ?>
                            <button type="button" onclick="deletebtn('<?php echo $salnum; ?>');" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-pen"></i> Edit Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editSalnum">
        <div class="mb-3">
          <label class="form-label fw-semibold">Order No</label>
          <input type="text" class="form-control" id="editOrderNo" readonly style="background:#f3f4f6;">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Purchase Date</label>
          <input type="date" class="form-control" id="editPurchaseDate">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Admin Remark</label>
          <textarea class="form-control" id="editAdminRmk" rows="3" placeholder="Enter admin remark..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="saveEdit();"><i class="fas fa-check"></i> Save</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var isAdminType = <?php echo json_encode(($_SESSION['admin_type'] ?? '') === 'A'); ?>;
// =====================================================================
// PRODUCTION-READY LIVE VIEW (Table)
// - AJAX polling every 15s (no page reload)
// - Preserves search, scroll, modal state
// - Web Audio API notification sound
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
    var dot = document.getElementById('liveDot');
    dot.className = 'live-dot polling';

    $.ajax({
        type: 'POST', url: 'admin_ajax.php', data: { action: 'poll' },
        dataType: 'json', timeout: 10000,
        success: function(data) {
            errors = 0;
            currentInterval = POLL_INTERVAL;
            dot.className = 'live-dot';

            renderTable(data.orders || []);
            updateBadge(data.new_count || 0);

            var hasNew = false;
            (data.orders || []).forEach(function(o) {
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

// ── Render Table ───────────────────────────────────────

function renderTable(orders) {
    var tbody = document.getElementById('ordersBody');
    var query = (document.getElementById('searchInput').value || '').toLowerCase();

    if (orders.length === 0) {
        tbody.innerHTML = '<tr class="no-results"><td colspan="10"><i class="fas fa-inbox" style="font-size:24px;margin-bottom:8px;display:block;"></i>No orders found</td></tr>';
        document.getElementById('orderCount').textContent = '0 order(s)';
        return;
    }

    var html = '';
    var num = 0;

    for (var i = 0; i < orders.length; i++) {
        var o = orders[i];
        var salnum  = esc(o.SALNUM || '');
        var name    = esc(o.NAME || '');
        var sdate   = formatDate(o.SDATE);
        var ttime   = esc(o.TTIME || '');
        var qty     = esc((o.SUMQTY || '0') + '');
        var txtto   = esc(o.TXTTO || '');
        var rmk     = esc(o.ADMINRMK || '');
        var branch  = esc(o.branch_name || o.branch_code || '');
        var isNew   = !knownSalnums[o.SALNUM];

        var searchStr = ((o.SALNUM||'') + ' ' + (o.NAME||'') + ' ' + (o.TXTTO||'') + ' ' + (o.ADMINRMK||'') + ' ' + (o.SDATE||'')).toLowerCase();
        var show = !query || searchStr.indexOf(query) > -1;

        if (show) num++;

        html += '<tr data-salnum="' + salnum + '"' + (isNew ? ' class="row-new"' : '') + (show ? '' : ' style="display:none;"') + '>';
        html += '<td>' + (show ? num : '') + '</td>';
        html += '<td>' + sdate + '</td>';
        html += '<td>' + ttime + '</td>';
        html += '<td><strong>' + salnum + '</strong></td>';
        html += '<td>' + name + '</td>';
        html += '<td>' + qty + '</td>';
        html += '<td>' + txtto + '</td>';
        html += '<td>' + rmk + '</td>';
        html += '<td>' + branch + '</td>';
        html += '<td style="white-space:nowrap">';
        html += '<a href="order_detail.php?salnum=' + salnum + '" class="btn-action btn-view"><i class="fas fa-eye"></i></a>';
        html += '<button type="button" onclick="openEditModal(\'' + salnum.replace(/'/g, "\\'") + '\', \'' + (o.ADMINRMK||'').replace(/'/g, "\\'").replace(/\n/g, '\\n') + '\', \'' + (o.PURCHASEDATE||'') + '\');" class="btn-action btn-edit"><i class="fas fa-pen"></i></button>';
        if (isAdminType) {
            html += '<button type="button" onclick="donebtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-action btn-done"><i class="fas fa-check"></i></button>';
        }
        html += '<button type="button" onclick="deletebtn(\'' + salnum.replace(/'/g, "\\'") + '\');" class="btn-action btn-delete"><i class="fas fa-trash"></i></button>';
        html += '</td></tr>';
    }

    tbody.innerHTML = html;
    document.getElementById('orderCount').textContent = num + ' order(s)';
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

// ── Search filter ──────────────────────────────────────

document.getElementById('searchInput').addEventListener('input', function() {
    var query = this.value.toLowerCase();
    var rows = document.querySelectorAll('#ordersBody tr:not(.no-results)');
    var num = 0;
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        if (!query || text.indexOf(query) > -1) {
            row.style.display = '';
            num++;
            row.cells[0].textContent = num;
        } else {
            row.style.display = 'none';
        }
    });
    document.getElementById('orderCount').textContent = num + ' order(s)';
});

// ── Acknowledge ────────────────────────────────────────

function acknowledgeOrders() {
    $.post('admin_ajax.php', { action: 'noted' }, function() {
        updateBadge(0);
        Swal.fire({ icon: 'success', text: 'All notifications acknowledged', timer: 1200, showConfirmButton: false });
    }, 'json');
}

// ── Order Actions ──────────────────────────────────────

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

// ── Edit Modal ─────────────────────────────────────────

var editModalInstance = null;

function openEditModal(salnum, adminrmk, purchasedate) {
    document.getElementById('editSalnum').value = salnum;
    document.getElementById('editOrderNo').value = salnum;
    document.getElementById('editAdminRmk').value = adminrmk || '';
    document.getElementById('editPurchaseDate').value = purchasedate || '';

    if (!editModalInstance) {
        editModalInstance = new bootstrap.Modal(document.getElementById('editModal'));
    }
    editModalInstance.show();
}

function saveEdit() {
    var salnum = document.getElementById('editSalnum').value;
    var adminrmk = document.getElementById('editAdminRmk').value;
    var purchasedate = document.getElementById('editPurchaseDate').value;

    $.post('admin_ajax.php', {
        action: 'edit_order',
        salnum: salnum,
        adminrmk: adminrmk,
        purchasedate: purchasedate
    }, function(data) {
        if (data.trim() === 'Saved.') {
            if (editModalInstance) editModalInstance.hide();
            Swal.fire({ icon: 'success', text: 'Order updated', timer: 1500, showConfirmButton: false });
            doPoll();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data });
        }
    });
}

// ── Manual Refresh ─────────────────────────────────────

function manualRefresh() {
    var btn = document.getElementById('refreshBtn');
    btn.classList.add('spinning');
    btn.disabled = true;
    doPoll();
    countdown = currentInterval;
    updateCountdown();
    setTimeout(function() {
        btn.classList.remove('spinning');
        btn.disabled = false;
    }, 800);
}

// ── Init ───────────────────────────────────────────────
startPolling();
</script>
</body>
</html>
