<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'report_purchase_history';

// Generate month options (current + last 12 months)
$monthOptions = [];
for ($i = 0; $i <= 12; $i++) {
    $monthOptions[] = date('Y-m', strtotime("-$i month"));
}
$selectedMonth = date('Y-m');
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
.filter-hint { font-size: 12px; color: var(--text-muted); margin-left: 4px; }
.summary-cards { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.summary-card { flex: 1; min-width: 140px; background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 16px; text-align: center; }
.summary-card .label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
.summary-card .value { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }
.type-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
.badge-stockin { background: #dbeafe; color: #2563eb; }
.badge-purchase { background: #fef3c7; color: #92400e; }
.btn-view { padding: 4px 10px; font-size: 12px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; font-weight: 600; }
.btn-view:hover { border-color: var(--primary); color: var(--primary); }

.items-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; padding: 16px; }
.items-overlay.active { display: flex; }
.items-modal { background: var(--surface); border-radius: var(--radius); width: 100%; max-width: 560px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
.items-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; }
.items-modal-header h3 { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; margin: 0; }
.items-modal-body { overflow-y: auto; padding: 8px 20px 16px; flex: 1; }
.items-modal table { width: 100%; font-size: 13px; border-collapse: collapse; }
.items-modal th { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; text-align: left; padding: 8px 6px; border-bottom: 2px solid #e5e7eb; }
.items-modal td { padding: 8px 6px; border-bottom: 1px solid #f3f4f6; }
.items-modal td.qty { text-align: right; font-weight: 700; }
.items-modal-close { background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 4px; }
.items-modal-close i { font-size: 18px; }

@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-history" style="color:var(--primary);margin-right:8px;"></i>Purchase History Report</h1>
    </div>

    <div class="filter-bar">
        <label>Month:</label>
        <select id="monthFilter">
            <?php foreach ($monthOptions as $mo): ?>
            <option value="<?php echo $mo; ?>" <?php echo ($mo === $selectedMonth) ? 'selected' : ''; ?>><?php echo date('F Y', strtotime($mo . '-01')); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" id="searchInput" placeholder="Search by purchase no. or item name..." style="flex:1;max-width:320px;">
        <button onclick="generateReport();"><i class="fas fa-search"></i> Generate</button>
        <span class="filter-hint">Search spans all months when set.</span>
    </div>

    <div class="summary-cards" id="summaryCards" style="display:none;">
        <div class="summary-card">
            <div class="label">Total Records</div>
            <div class="value" id="sumRecords">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Items</div>
            <div class="value" id="sumItems">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Qty</div>
            <div class="value" id="sumQty">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Click <strong>Generate</strong> to load purchase records for the selected month, or enter a search to query across all months.</p>
        </div>
    </div>
</div>

<div class="items-overlay" id="itemsOverlay" onclick="if(event.target===this)closeItemsModal()">
    <div class="items-modal">
        <div class="items-modal-header">
            <h3 id="itemsModalTitle">Items</h3>
            <button class="items-modal-close" onclick="closeItemsModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="items-modal-body" id="itemsModalBody">
            <p style="text-align:center;color:var(--text-muted);padding:20px;">Loading...</p>
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
function escHtml(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
function escAttr(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function formatDate(d) {
    if (!d) return '';
    var p = String(d).split('-');
    if (p.length !== 3) return String(d);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return p[2] + ' ' + (months[parseInt(p[1],10)-1] || p[1]) + ' ' + p[0];
}
function formatTime(t) {
    if (!t) return '';
    var p = String(t).split(':');
    if (p.length < 2) return String(t);
    var h = parseInt(p[0], 10), ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12; if (h === 0) h = 12;
    return h + ':' + p[1] + ' ' + ampm;
}

function generateReport() {
    var postData = {
        action: 'purchase_history',
        ym: document.getElementById('monthFilter').value,
        search: document.getElementById('searchInput').value.trim()
    };
    document.getElementById('reportContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    $.ajax({
        type: 'POST', url: 'report_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            renderTable(data.rows || []);
        },
        error: function() { Swal.fire({ icon: 'error', text: 'Failed to load report.' }); }
    });
}

function renderTable(rows) {
    if (rows.length === 0) {
        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No purchase records found.</p>';
        document.getElementById('summaryCards').style.display = 'none';
        return;
    }

    var totalItems = 0, totalQty = 0;
    var html = '<table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">' +
        '<thead><tr>' +
            '<th>No</th>' +
            '<th>Purchase No.</th>' +
            '<th>Type</th>' +
            '<th>Date</th>' +
            '<th>Time</th>' +
            '<th>By (User)</th>' +
            '<th>Remark</th>' +
            '<th class="text-end">Items</th>' +
            '<th class="text-end">Total Qty</th>' +
            '<th>Action</th>' +
        '</tr></thead><tbody>';

    rows.forEach(function(r, i) {
        var items = parseInt(r.ITEM_COUNT) || 0;
        var qty = parseFloat(r.TOTAL_QTY) || 0;
        totalItems += items;
        totalQty += qty;

        var isStockin = (r.PTYPE === 'STOCKIN');
        var badgeCls = isStockin ? 'badge-stockin' : 'badge-purchase';
        var badgeTxt = isStockin ? 'Stock In' : 'Purchase';

        html += '<tr>' +
            '<td>' + (i+1) + '</td>' +
            '<td><strong>' + escHtml(r.SALNUM || '') + '</strong></td>' +
            '<td><span class="type-badge ' + badgeCls + '">' + badgeTxt + '</span></td>' +
            '<td>' + escHtml(formatDate(r.SDATE)) + '</td>' +
            '<td>' + escHtml(formatTime(r.TTIME)) + '</td>' +
            '<td>' + escHtml(r.NAME || '') + '</td>' +
            '<td>' + escHtml(r.TXTTO || '') + '</td>' +
            '<td class="text-end">' + items + '</td>' +
            '<td class="text-end fw-bold">' + qty + '</td>' +
            '<td><button class="btn-view" onclick="viewItems(\'' + escAttr(r.SALNUM || '') + '\')"><i class="fas fa-list"></i> View</button></td>' +
            '</tr>';
    });

    html += '</tbody><tfoot><tr style="font-weight:700;"><td colspan="7">TOTAL</td><td class="text-end">' + totalItems + '</td><td class="text-end">' + totalQty + '</td><td></td></tr></tfoot></table>';
    document.getElementById('reportContent').innerHTML = html;

    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('sumRecords').textContent = rows.length;
    document.getElementById('sumItems').textContent = totalItems;
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
        order: [[3, 'desc'], [4, 'desc']]
    });
}

function viewItems(salnum) {
    if (!salnum) return;
    document.getElementById('itemsModalTitle').textContent = 'Items - ' + salnum;
    document.getElementById('itemsModalBody').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:20px;">Loading...</p>';
    document.getElementById('itemsOverlay').classList.add('active');

    $.ajax({
        type: 'POST', url: 'report_ajax.php',
        data: { action: 'purchase_history_items', salnum: salnum },
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                document.getElementById('itemsModalBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:20px;">' + escHtml(data.error) + '</p>';
                return;
            }
            var items = data.items || [];
            if (items.length === 0) {
                document.getElementById('itemsModalBody').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:20px;">No items found.</p>';
                return;
            }
            var html = '<table><thead><tr><th>#</th><th>Barcode</th><th>Description</th><th class="qty">Qty</th></tr></thead><tbody>';
            items.forEach(function(it, idx) {
                html += '<tr>' +
                    '<td>' + (idx+1) + '</td>' +
                    '<td>' + escHtml(it.BARCODE || '') + '</td>' +
                    '<td>' + escHtml(it.PDESC || '') + '</td>' +
                    '<td class="qty">+' + escHtml(it.QTY || '0') + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('itemsModalBody').innerHTML = html;
        },
        error: function() {
            document.getElementById('itemsModalBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:20px;">Failed to load items.</p>';
        }
    });
}

function closeItemsModal() {
    document.getElementById('itemsOverlay').classList.remove('active');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeItemsModal();
});

document.getElementById('searchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); generateReport(); }
});

// Auto-load on first visit
generateReport();
</script>
</body>
</html>
