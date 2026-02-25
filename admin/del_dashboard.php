<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$currentPage = 'del_dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery Board</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root {
    --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6;
    --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px;
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-view { background: #3b82f6; } .btn-view:hover { background: #2563eb; }
.btn-complete { background: #16a34a; } .btn-complete:hover { background: #15803d; }
.btn-img { background: #8b5cf6; } .btn-img:hover { background: #7c3aed; }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-order { background: #fee2e2; color: #dc2626; }
.badge-assigned { background: #e0e7ff; color: #4338ca; }
.badge-done { background: #dcfce7; color: #16a34a; }
.badge-completed { background: #f0fdf4; color: #166534; }

/* Tabs */
.status-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.status-tab { padding: 8px 20px; border: 2px solid #e5e7eb; border-radius: 10px; background: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: all var(--transition); color: var(--text-muted); position: relative; }
.status-tab:hover { border-color: var(--primary); color: var(--primary); }
.status-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.status-tab .tab-count { font-size: 11px; margin-left: 6px; opacity: 0.8; }
.date-filter { display: none; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.date-filter.show { display: flex; }
.date-filter input[type="date"] { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
.date-filter button { padding: 7px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }

/* Image modal */
.img-modal-body img { max-width: 100%; border-radius: 8px; margin-bottom: 10px; }

@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-truck" style="color:var(--primary);margin-right:8px;"></i>Delivery Board</h1>
    </div>

    <div class="status-tabs">
        <button class="status-tab active" data-status="" onclick="switchTab(this, '')">Order <span class="tab-count" id="countOrder">0</span></button>
        <button class="status-tab" data-status="A" onclick="switchTab(this, 'A')">Assigned <span class="tab-count" id="countAssigned">0</span></button>
        <button class="status-tab" data-status="D" onclick="switchTab(this, 'D')">Done <span class="tab-count" id="countDone">0</span></button>
        <button class="status-tab" data-status="C" onclick="switchTab(this, 'C')">Completed <span class="tab-count" id="countCompleted">0</span></button>
    </div>

    <div class="date-filter" id="dateFilter">
        <label style="font-size:13px;font-weight:600;">From:</label>
        <input type="date" id="startDate" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
        <label style="font-size:13px;font-weight:600;">To:</label>
        <input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
        <button onclick="loadData()"><i class="fas fa-filter"></i> Filter</button>
    </div>

    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Del. Date</th>
                        <th>Order No</th>
                        <th>Driver</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Distance</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <tr><td colspan="10" class="empty-state"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-images"></i> Delivery Photos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body img-modal-body" id="imgModalBody"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var currentStatus = '';
var imgModal = null;

document.addEventListener('DOMContentLoaded', function() {
    imgModal = new bootstrap.Modal(document.getElementById('imgModal'));
    loadData();
    // Auto-refresh every 15 seconds for active tabs
    setInterval(function() { if (currentStatus !== 'C') loadData(); }, 15000);
});

function switchTab(el, status) {
    document.querySelectorAll('.status-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
    currentStatus = status;
    document.getElementById('dateFilter').classList.toggle('show', status === 'C');
    loadData();
}

function loadData() {
    var postData = { action: 'list', status: currentStatus };
    if (currentStatus === 'C') {
        postData.start_date = document.getElementById('startDate').value;
        postData.end_date = document.getElementById('endDate').value;
    }

    $.ajax({
        type: 'POST', url: 'del_dashboard_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { return; }
            renderTable(data.orders || []);
            if (data.counts) {
                document.getElementById('countOrder').textContent = data.counts.order || 0;
                document.getElementById('countAssigned').textContent = data.counts.assigned || 0;
                document.getElementById('countDone').textContent = data.counts.done || 0;
                document.getElementById('countCompleted').textContent = data.counts.completed || 0;
            }
        }
    });
}

function renderTable(orders) {
    var tbody = document.getElementById('dataBody');
    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty-state"><i class="fas fa-truck"></i>No orders found</td></tr>';
        return;
    }

    var statusMap = { '': 'Order', 'A': 'Assigned', 'D': 'Done', 'C': 'Completed' };
    var badgeMap = { '': 'badge-order', 'A': 'badge-assigned', 'D': 'badge-done', 'C': 'badge-completed' };

    var html = orders.map(function(o, i) {
        var hasImg = o.IMG1 || o.IMG2 || o.IMG3;
        var canComplete = (o.STATUS === 'D' || o.STATUS === 'A');
        var actions = '<button class="btn-action btn-view" onclick="viewOrder(' + o.ID + ')"><i class="fas fa-eye"></i></button>';
        if (hasImg) actions += ' <button class="btn-action btn-img" onclick="viewImages(' + o.ID + ')"><i class="fas fa-image"></i></button>';
        if (canComplete) actions += ' <button class="btn-action btn-complete" onclick="completeOrder(' + o.ID + ')"><i class="fas fa-check"></i></button>';

        return '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + escHtml(o.DELDATE || '') + '</td>' +
            '<td><strong>' + escHtml(o.ORDNO || '') + '</strong></td>' +
            '<td>' + escHtml(o.DRIVER || '-') + '</td>' +
            '<td>' + escHtml(o.CUSTOMER || '') + '</td>' +
            '<td>' + escHtml(o.LOCATION || '') + '</td>' +
            '<td>' + escHtml(o.DISTANT || '') + '</td>' +
            '<td>' + escHtml(o.RETAIL || '') + '</td>' +
            '<td><span class="badge-status ' + (badgeMap[o.STATUS] || '') + '">' + (statusMap[o.STATUS] || o.STATUS) + '</span></td>' +
            '<td style="white-space:nowrap">' + actions + '</td>' +
        '</tr>';
    }).join('');
    tbody.innerHTML = html;
}

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function viewOrder(id) {
    window.open('del_order.php?view=' + id, '_blank');
}

function viewImages(id) {
    $.ajax({
        type: 'POST', url: 'del_dashboard_ajax.php', data: { action: 'images', id: id }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            var html = '';
            if (data.IMG1) html += '<img src="../staff/uploads/' + data.IMG1 + '" alt="Photo 1">';
            if (data.IMG2) html += '<img src="../staff/uploads/' + data.IMG2 + '" alt="Photo 2">';
            if (data.IMG3) html += '<img src="../staff/uploads/' + data.IMG3 + '" alt="Photo 3">';
            if (!html) html = '<p class="text-muted text-center">No photos uploaded.</p>';
            document.getElementById('imgModalBody').innerHTML = html;
            imgModal.show();
        }
    });
}

function completeOrder(id) {
    Swal.fire({
        title: 'Complete Order?',
        text: 'Mark this delivery as completed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Yes, Complete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'del_dashboard_ajax.php', data: { action: 'complete', id: id }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1200, showConfirmButton: false }).then(function() { loadData(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Failed.' });
                    }
                }
            });
        }
    });
}
</script>
</body>
</html>
