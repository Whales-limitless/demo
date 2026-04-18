<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'report_negative_qoh';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Negative QOH Report</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px; --shadow-md: 0 4px 16px rgba(0,0,0,0.08); }
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
.text-negative { color: #dc2626; }
@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-exclamation-triangle" style="color:var(--primary);margin-right:8px;"></i>Negative QOH Report</h1>
    </div>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search barcode / description / category..." style="flex:1;max-width:320px;">
        <button onclick="generateReport();"><i class="fas fa-search"></i> Refresh</button>
    </div>

    <div class="summary-cards" id="summaryCards" style="display:none;">
        <div class="summary-card">
            <div class="label">Products with Negative QOH</div>
            <div class="value text-negative" id="sumProducts">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Negative QOH</div>
            <div class="value text-negative" id="sumQoh">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Click Refresh to view products with negative quantity on hand.</p>
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
function fmtNum(n) { return parseFloat(n || 0).toFixed(2); }

document.addEventListener('DOMContentLoaded', function() {
    generateReport();
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') generateReport();
    });
});

function generateReport() {
    var postData = {
        action: 'negative_qoh',
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
        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No products with negative QOH found.</p>';
        document.getElementById('summaryCards').style.display = 'none';
        if (dtTable) { dtTable.destroy(); dtTable = null; }
        return;
    }

    var totalQoh = 0;

    var html = '<table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">' +
        '<thead><tr>' +
        '<th>No</th>' +
        '<th>Barcode</th>' +
        '<th>Description</th>' +
        '<th>Category</th>' +
        '<th>Sub Category</th>' +
        '<th>Rack</th>' +
        '<th>UOM</th>' +
        '<th class="text-end">QOH</th>' +
        '</tr></thead><tbody>';

    rows.forEach(function(r, i) {
        var qoh = parseFloat(r.qoh) || 0;
        totalQoh += qoh;

        html += '<tr>';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td><strong>' + escHtml(r.barcode) + '</strong></td>';
        html += '<td>' + escHtml(r.name) + '</td>';
        html += '<td>' + escHtml(r.cat || '-') + '</td>';
        html += '<td>' + escHtml(r.sub_cat || '-') + '</td>';
        html += '<td>' + escHtml(r.rack || '-') + '</td>';
        html += '<td>' + escHtml(r.uom || '-') + '</td>';
        html += '<td class="text-end text-negative fw-bold">' + fmtNum(qoh) + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    document.getElementById('reportContent').innerHTML = html;

    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('sumProducts').textContent = rows.length;
    document.getElementById('sumQoh').textContent = fmtNum(totalQoh);

    initDataTable();
}

function initDataTable() {
    if (dtTable) { dtTable.destroy(); }
    dtTable = $('#reportTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        pageLength: 50,
        ordering: true,
        order: [[7, 'asc']]
    });
}
</script>
</body>
</html>
