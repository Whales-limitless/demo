<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$currentPage = 'report_stock_take';

// Load categories for filter
$categories = [];
$catResult = $connect->query("SELECT DISTINCT `cat` FROM `PRODUCTS` WHERE `checked` = 'Y' AND `cat` != '' ORDER BY `cat` ASC");
if ($catResult) {
    while ($r = $catResult->fetch_assoc()) { $categories[] = $r['cat']; }
}

$subCategories = [];
$subCatResult = $connect->query("SELECT DISTINCT `sub_cat` FROM `PRODUCTS` WHERE `checked` = 'Y' AND `sub_cat` != '' ORDER BY `sub_cat` ASC");
if ($subCatResult) {
    while ($r = $subCatResult->fetch_assoc()) { $subCategories[] = $r['sub_cat']; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Take Report</title>
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
.badge-never { background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-overdue { background: #fef3c7; color: #92400e; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-ok { background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.text-positive { color: #16a34a; }
.text-negative { color: #dc2626; }
.text-warning { color: #d97706; }
@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-list" style="color:var(--primary);margin-right:8px;"></i>Stock Take Report</h1>
    </div>

    <div class="filter-bar">
        <label>Category:</label>
        <select id="filterCat" onchange="loadSubCategories();">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Sub Category:</label>
        <select id="filterSubCat">
            <option value="">All Sub Categories</option>
            <?php foreach ($subCategories as $sub): ?>
            <option value="<?php echo htmlspecialchars($sub); ?>"><?php echo htmlspecialchars($sub); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Status:</label>
        <select id="filterStatus">
            <option value="">All Products</option>
            <option value="never">Never Stock Taken</option>
            <option value="taken">Stock Taken Before</option>
            <option value="overdue">Overdue (> 30 days)</option>
        </select>

        <input type="text" id="searchInput" placeholder="Search product..." style="flex:1;max-width:250px;">
        <button onclick="generateReport();"><i class="fas fa-search"></i> Generate Report</button>
    </div>

    <div class="summary-cards" id="summaryCards" style="display:none;">
        <div class="summary-card">
            <div class="label">Total Products</div>
            <div class="value" id="sumTotal">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Never Stock Taken</div>
            <div class="value text-negative" id="sumNever">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Stock Taken</div>
            <div class="value text-positive" id="sumTaken">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Overdue (> 30 days)</div>
            <div class="value text-warning" id="sumOverdue">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Click Generate Report to view the stock take status of all products.</p>
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

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function loadSubCategories() {
    var cat = document.getElementById('filterCat').value;
    if (cat === '') return;
    $.post('report_ajax.php', { action: 'stock_take_subcategories', category: cat }, function(data) {
        var sel = document.getElementById('filterSubCat');
        sel.innerHTML = '<option value="">All Sub Categories</option>';
        (data.sub_categories || []).forEach(function(sc) {
            sel.innerHTML += '<option value="' + escHtml(sc) + '">' + escHtml(sc) + '</option>';
        });
    }, 'json');
}

function generateReport() {
    var postData = {
        action: 'stock_take_report',
        category: document.getElementById('filterCat').value,
        sub_category: document.getElementById('filterSubCat').value,
        status_filter: document.getElementById('filterStatus').value,
        search: document.getElementById('searchInput').value.trim()
    };

    document.getElementById('reportContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    $.ajax({
        type: 'POST', url: 'report_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            renderTable(data.rows || [], data.summary || {});
        },
        error: function() { Swal.fire({ icon: 'error', text: 'Failed to load report.' }); }
    });
}

function renderTable(rows, summary) {
    if (rows.length === 0) {
        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No products found matching the selected filters.</p>';
        document.getElementById('summaryCards').style.display = 'none';
        return;
    }

    // Update summary cards
    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('sumTotal').textContent = summary.total || 0;
    document.getElementById('sumNever').textContent = summary.never || 0;
    document.getElementById('sumTaken').textContent = summary.taken || 0;
    document.getElementById('sumOverdue').textContent = summary.overdue || 0;

    var html = '<div style="overflow-x:auto;"><table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">';
    html += '<thead><tr>';
    html += '<th>No</th>';
    html += '<th>Barcode</th>';
    html += '<th>Product Name</th>';
    html += '<th>Category</th>';
    html += '<th class="text-end">Current QOH</th>';
    html += '<th>Last Stock Take</th>';
    html += '<th class="text-end">Counted Qty</th>';
    html += '<th class="text-end">Variance</th>';
    html += '<th>Session</th>';
    html += '<th>Counted By</th>';
    html += '<th class="text-center">Days Ago</th>';
    html += '<th class="text-center">Status</th>';
    html += '</tr></thead><tbody>';

    rows.forEach(function(r, i) {
        var variance = parseFloat(r.variance) || 0;
        var varianceClass = variance > 0 ? 'text-positive' : (variance < 0 ? 'text-negative' : '');

        var statusBadge = '';
        var daysAgo = r.days_ago;
        if (r.last_stock_take === null) {
            statusBadge = '<span class="badge-never">Never</span>';
            daysAgo = '-';
        } else if (parseInt(r.days_ago) > 30) {
            statusBadge = '<span class="badge-overdue">Overdue</span>';
        } else {
            statusBadge = '<span class="badge-ok">OK</span>';
        }

        html += '<tr>';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td><code style="font-size:12px;">' + escHtml(r.barcode) + '</code></td>';
        html += '<td>' + escHtml(r.product_name) + '</td>';
        html += '<td>' + escHtml(r.category || '') + '</td>';
        html += '<td class="text-end fw-bold">' + parseFloat(r.current_qoh).toFixed(2) + '</td>';
        html += '<td>' + (r.last_stock_take || '<span style="color:#dc2626;">-</span>') + '</td>';
        html += '<td class="text-end">' + (r.counted_qty !== null ? parseFloat(r.counted_qty).toFixed(2) : '-') + '</td>';
        html += '<td class="text-end ' + varianceClass + '">' + (r.variance !== null ? (variance > 0 ? '+' : '') + variance.toFixed(2) : '-') + '</td>';
        html += '<td><span style="font-size:11px;">' + escHtml(r.session_code || '-') + '</span></td>';
        html += '<td>' + escHtml(r.counted_by || '-') + '</td>';
        html += '<td class="text-center">' + daysAgo + '</td>';
        html += '<td class="text-center">' + statusBadge + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    document.getElementById('reportContent').innerHTML = html;

    initDataTable();
}

function initDataTable() {
    if (dtTable) { dtTable.destroy(); }
    dtTable = $('#reportTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        pageLength: 50,
        ordering: true,
        order: [[10, 'desc'], [5, 'asc']] // Sort by days ago desc (never taken first), then last stock take asc
    });
}
</script>
</body>
</html>
