<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'report_purchase';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase History Report</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px; --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.filter-bar label { font-size: 13px; font-weight: 600; }
.filter-bar input, .filter-bar select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
.filter-bar button { padding: 7px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.filter-bar button:hover { background: var(--primary-dark); }
.summary-cards { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.summary-card { flex: 1; min-width: 140px; background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 16px; text-align: center; }
.summary-card .label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
.summary-card .value { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }
.type-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; white-space: nowrap; display: inline-block; }
.badge-stockin { background: #dbeafe; color: #2563eb; }
.badge-purchase { background: #fef3c7; color: #92400e; }
.action-btn { padding: 4px 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; font-size: 12px; font-weight: 600; color: var(--text); cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-right: 4px; }
.action-btn:hover { border-color: var(--primary); color: var(--primary); }
.item-row { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
.item-row:last-child { border-bottom: none; }
.item-num { color: var(--text-muted); font-weight: 600; min-width: 24px; }
.item-desc { flex: 1; }
.item-desc .item-barcode { font-size: 11px; color: var(--text-muted); }
.item-qty { font-weight: 700; white-space: nowrap; }
@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-dolly" style="color:var(--primary);margin-right:8px;"></i>Purchase History Report</h1>
    </div>

    <div class="filter-bar">
        <label>Start Date:</label><input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
        <label>End Date:</label><input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
        <label>Type:</label>
        <select id="typeFilter">
            <option value="ALL">All</option>
            <option value="STOCKIN">Stock In</option>
            <option value="PURCHASE">Purchase</option>
        </select>
        <label>Branch:</label>
        <select id="branchFilter">
            <option value="">All Branches</option>
        </select>
        <input type="text" id="searchInput" placeholder="Search SALNUM / staff / item..." style="flex:1;max-width:260px;">
        <button onclick="generateReport();"><i class="fas fa-search"></i> Apply Filter</button>
    </div>

    <div class="summary-cards" id="summaryCards" style="display:none;">
        <div class="summary-card">
            <div class="label">Total Records</div>
            <div class="value" id="sumRecords">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Stock In</div>
            <div class="value" id="sumStockIn" style="color:#2563eb;">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Purchase</div>
            <div class="value" id="sumPurchase" style="color:#92400e;">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Qty</div>
            <div class="value" id="sumQty">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Select filters and click Apply Filter to view the report.</p>
        </div>
    </div>
</div>

<!-- Items Modal -->
<div class="modal fade" id="itemsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--bg);border-bottom:1px solid #e5e7eb;">
        <h5 class="modal-title" id="itemsModalTitle" style="font-family:'Outfit',sans-serif;font-size:16px;font-weight:700;">Purchase Items</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="itemsModalBody" style="padding:20px;">
        <p class="text-center text-muted">Loading...</p>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
var dtTable = null;

function escHtml(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

function loadBranches() {
    $.post('report_purchase_ajax.php', { action: 'branches' }, function(data) {
        var sel = document.getElementById('branchFilter');
        (data.branches || []).forEach(function(br) {
            var opt = document.createElement('option');
            opt.value = br.code;
            opt.textContent = br.name;
            sel.appendChild(opt);
        });
    }, 'json');
}

document.addEventListener('DOMContentLoaded', function() {
    loadBranches();
    // Enter key triggers filter
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') generateReport();
    });
});

function generateReport() {
    var postData = {
        action: 'list',
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        type: document.getElementById('typeFilter').value,
        branch: document.getElementById('branchFilter').value,
        search: document.getElementById('searchInput').value.trim()
    };

    document.getElementById('reportContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    $.ajax({
        type: 'POST', url: 'report_purchase_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            renderTable(data.rows || []);
        },
        error: function() { Swal.fire({ icon: 'error', text: 'Failed to load report.' }); }
    });
}

function renderTable(rows) {
    if (rows.length === 0) {
        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No records found for the selected filters.</p>';
        document.getElementById('summaryCards').style.display = 'none';
        if (dtTable) { dtTable.destroy(); dtTable = null; }
        return;
    }

    var totalQty = 0, stockInCount = 0, purchaseCount = 0;

    var html = '<table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">' +
        '<thead><tr>' +
        '<th>No</th>' +
        '<th>SALNUM</th>' +
        '<th>Date</th>' +
        '<th>Time</th>' +
        '<th>Type</th>' +
        '<th>Staff</th>' +
        '<th>Branch</th>' +
        '<th class="text-end">Items</th>' +
        '<th class="text-end">Total Qty</th>' +
        '<th>Remark</th>' +
        '<th>Actions</th>' +
        '</tr></thead><tbody>';

    rows.forEach(function(r, i) {
        var qty = parseFloat(r.TOTAL_QTY) || 0;
        totalQty += qty;
        if (r.PTYPE === 'STOCKIN') stockInCount++; else if (r.PTYPE === 'PURCHASE') purchaseCount++;

        var dateStr = r.SDATE ? new Date(r.SDATE + 'T00:00:00').toLocaleDateString('en-GB') : '';
        var timeStr = '';
        if (r.TTIME) {
            var parts = r.TTIME.split(':');
            if (parts.length >= 2) {
                var h = parseInt(parts[0]); var m = parts[1];
                var ampm = h >= 12 ? 'PM' : 'AM';
                var h12 = h % 12; if (h12 === 0) h12 = 12;
                timeStr = (h12 < 10 ? '0' + h12 : h12) + ':' + m + ' ' + ampm;
            }
        }
        var typeBadge = r.PTYPE === 'STOCKIN'
            ? '<span class="type-badge badge-stockin">Stock In</span>'
            : '<span class="type-badge badge-purchase">Purchase</span>';

        html += '<tr>';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td><strong>' + escHtml(r.SALNUM) + '</strong></td>';
        html += '<td>' + escHtml(dateStr) + '</td>';
        html += '<td>' + escHtml(timeStr) + '</td>';
        html += '<td>' + typeBadge + '</td>';
        html += '<td>' + escHtml(r.NAME || '-') + '</td>';
        html += '<td>' + escHtml(r.branch_name || '-') + '</td>';
        html += '<td class="text-end">' + (parseInt(r.ITEM_COUNT) || 0) + '</td>';
        html += '<td class="text-end fw-bold">' + qty + '</td>';
        html += '<td>' + escHtml(r.TXTTO || '') + '</td>';
        html += '<td style="white-space:nowrap;">' +
            '<button class="action-btn" onclick="showItems(\'' + escHtml(r.SALNUM) + '\')"><i class="fas fa-list"></i> Items</button>' +
            '<a class="action-btn" href="../staff/stockin_preview.php?salnum=' + encodeURIComponent(r.SALNUM) + '" target="_blank"><i class="fas fa-print"></i> Print</a>' +
            '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    document.getElementById('reportContent').innerHTML = html;

    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('sumRecords').textContent = rows.length;
    document.getElementById('sumStockIn').textContent = stockInCount;
    document.getElementById('sumPurchase').textContent = purchaseCount;
    document.getElementById('sumQty').textContent = totalQty;

    initDataTable();
}

function initDataTable() {
    if (dtTable) { dtTable.destroy(); }
    dtTable = $('#reportTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        pageLength: 50,
        ordering: true,
        order: [],
        columnDefs: [{ orderable: false, targets: -1 }]
    });
}

function showItems(salnum) {
    document.getElementById('itemsModalTitle').textContent = 'Items — ' + salnum;
    document.getElementById('itemsModalBody').innerHTML = '<p class="text-center text-muted" style="padding:30px;"><i class="fas fa-spinner fa-spin"></i> Loading items...</p>';
    var modal = new bootstrap.Modal(document.getElementById('itemsModal'));
    modal.show();

    $.post('report_purchase_ajax.php', { action: 'items', salnum: salnum }, function(data) {
        if (data.error) {
            document.getElementById('itemsModalBody').innerHTML = '<p class="text-danger text-center">' + escHtml(data.error) + '</p>';
            return;
        }
        var items = data.items || [];
        if (items.length === 0) {
            document.getElementById('itemsModalBody').innerHTML = '<p class="text-center text-muted">No items found.</p>';
            return;
        }
        var totalQty = 0;
        var html = '<table class="table table-sm table-striped" style="font-size:13px;">' +
            '<thead><tr><th>#</th><th>Barcode</th><th>Description</th><th class="text-end">Qty</th></tr></thead><tbody>';
        items.forEach(function(it, idx) {
            var q = parseFloat(it.QTY) || 0;
            totalQty += q;
            html += '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td>' + escHtml(it.BARCODE || '') + '</td>' +
                '<td>' + escHtml(it.PDESC || '') + '</td>' +
                '<td class="text-end fw-bold">+' + q + '</td>' +
                '</tr>';
        });
        html += '</tbody><tfoot><tr style="font-weight:700;"><td colspan="3">Total</td><td class="text-end">' + totalQty + '</td></tr></tfoot></table>';
        document.getElementById('itemsModalBody').innerHTML = html;
    }, 'json').fail(function() {
        document.getElementById('itemsModalBody').innerHTML = '<p class="text-danger text-center">Failed to load items.</p>';
    });
}
</script>
</body>
</html>
