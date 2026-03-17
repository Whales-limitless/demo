<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'report_sales_branch';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales by Branch Report</title>
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
.toggle-bar { display: flex; gap: 4px; background: #e5e7eb; border-radius: 8px; padding: 3px; width: fit-content; }
.toggle-bar button { padding: 6px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: var(--transition); }
.toggle-bar button.active { background: var(--primary); color: #fff; }
.branch-group { margin-bottom: 24px; }
.branch-header { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; padding: 12px 16px; border-radius: var(--radius) var(--radius) 0 0; font-weight: 700; font-size: 14px; display: flex; justify-content: space-between; align-items: center; }
.branch-header .branch-stats { font-size: 12px; font-weight: 400; opacity: 0.9; }
.btn-print { padding: 7px 16px; background: #374151; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; margin-bottom: 12px; }
.btn-print:hover { background: #1f2937; }
@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
@media print {
    body { background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .sidebar, .topbar, .filter-bar, .btn-print, .summary-cards { display: none !important; }
    .page-content { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    .table-card { box-shadow: none !important; padding: 0 !important; border-radius: 0 !important; }
    .page-header { margin-bottom: 10px !important; }
    .print-header { display: block !important; text-align: center; margin-bottom: 10px; font-size: 11px; color: #666; }
    table { font-size: 10px !important; }
    tr { page-break-inside: avoid; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-store" style="color:var(--primary);margin-right:8px;"></i>Sales by Branch Report</h1>
    </div>

    <div class="filter-bar">
        <label>Start Date:</label><input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
        <label>End Date:</label><input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
        <div class="toggle-bar">
            <button id="btnSummary" class="active" onclick="setMode('summary')">Summary</button>
            <button id="btnDetailed" onclick="setMode('detailed')">Detailed</button>
        </div>
        <button onclick="generateReport();"><i class="fas fa-search"></i> Generate</button>
    </div>

    <div class="summary-cards" id="summaryCards" style="display:none;">
        <div class="summary-card">
            <div class="label">Total Branches</div>
            <div class="value" id="sumBranches">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Orders</div>
            <div class="value" id="sumOrders">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Qty Sold</div>
            <div class="value" id="sumQty">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Staff</div>
            <div class="value" id="sumStaff">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Select date range and click Generate to view the report.</p>
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
var reportMode = 'summary';
function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function setMode(mode) {
    reportMode = mode;
    document.getElementById('btnSummary').classList.toggle('active', mode === 'summary');
    document.getElementById('btnDetailed').classList.toggle('active', mode === 'detailed');
}

function generateReport() {
    var action = reportMode === 'detailed' ? 'sales_by_branch_detailed' : 'sales_by_branch';
    var postData = {
        action: action,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value
    };

    document.getElementById('reportContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    $.ajax({
        type: 'POST', url: 'report_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            if (reportMode === 'detailed') {
                renderDetailedTable(data.rows || []);
            } else {
                renderTable(data.rows || []);
            }
        },
        error: function() { Swal.fire({ icon: 'error', text: 'Failed to load report.' }); }
    });
}

function renderTable(rows) {
    if (rows.length === 0) {
        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No sales data found for the selected period.</p>';
        document.getElementById('summaryCards').style.display = 'none';
        return;
    }

    var totalOrders = 0, totalQty = 0, totalStaff = 0;
    var html = '<table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">' +
        '<thead><tr><th>No</th><th>Branch</th><th class="text-end">Orders</th><th class="text-end">Qty Sold</th><th class="text-end">Unique Products</th><th class="text-end">Staff</th></tr></thead><tbody>';

    rows.forEach(function(r, i) {
        var orders = parseInt(r.total_orders) || 0;
        var qty = parseFloat(r.total_qty) || 0;
        var staff = parseInt(r.staff_count) || 0;
        totalOrders += orders;
        totalQty += qty;
        totalStaff += staff;
        html += '<tr>' +
            '<td>' + (i+1) + '</td>' +
            '<td>' + escHtml(r.branch_name || r.branch_code || 'Unknown') + '</td>' +
            '<td class="text-end">' + orders + '</td>' +
            '<td class="text-end fw-bold">' + qty.toFixed(2) + '</td>' +
            '<td class="text-end">' + (r.unique_products || 0) + '</td>' +
            '<td class="text-end">' + staff + '</td>' +
            '</tr>';
    });

    html += '</tbody><tfoot><tr style="font-weight:700;"><td colspan="2">TOTAL</td><td class="text-end">' + totalOrders + '</td><td class="text-end">' + totalQty.toFixed(2) + '</td><td></td><td class="text-end">' + totalStaff + '</td></tr></tfoot></table>';
    document.getElementById('reportContent').innerHTML = html;

    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('sumBranches').textContent = rows.length;
    document.getElementById('sumOrders').textContent = totalOrders;
    document.getElementById('sumQty').textContent = totalQty.toFixed(2);
    document.getElementById('sumStaff').textContent = totalStaff;

    initDataTable();
}

function renderDetailedTable(rows) {
    if (rows.length === 0) {
        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No sales data found for the selected period.</p>';
        document.getElementById('summaryCards').style.display = 'none';
        return;
    }

    // Group by branch -> itemized orders
    var branches = {};
    var totalQty = 0;
    var allOrderNos = {};
    var allStaff = {};

    rows.forEach(function(r) {
        var bName = r.branch_name || 'No Branch';
        var bCode = r.branch_code || 'NO_BRANCH';
        if (!branches[bCode]) branches[bCode] = { name: bName, items: [], qty: 0, orders: {}, staff: {} };
        var qty = parseFloat(r.qty) || 0;
        branches[bCode].items.push(r);
        branches[bCode].qty += qty;
        branches[bCode].orders[r.order_no] = true;
        branches[bCode].staff[r.staff_name] = true;
        allOrderNos[r.order_no] = true;
        allStaff[r.staff_name] = true;
        totalQty += qty;
    });
    var totalOrders = Object.keys(allOrderNos).length;

    var branchKeys = Object.keys(branches).sort(function(a, b) {
        return branches[a].name.localeCompare(branches[b].name);
    });

    // Print button + date range header (hidden on screen, shown on print)
    var dateRange = document.getElementById('startDate').value + ' to ' + document.getElementById('endDate').value;
    var html = '<div class="print-header" style="display:none;">Sales by Branch Report (Detailed) | ' + escHtml(dateRange) + '</div>' +
        '<button class="btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print</button>';

    // Single table for consistent column alignment
    html += '<table class="table table-sm mb-0" style="width:100%;font-size:12px;table-layout:fixed;">' +
        '<colgroup><col style="width:4%"><col style="width:18%"><col style="width:9%"><col style="width:10%"><col style="width:13%"><col style="width:36%"><col style="width:10%"></colgroup>' +
        '<thead><tr style="background:#f9fafb;"><th>No</th><th>Order No</th><th>Date</th><th>Staff</th><th>Barcode</th><th>Product</th><th class="text-end">Qty</th></tr></thead><tbody>';

    var rowNum = 0;
    branchKeys.forEach(function(bCode) {
        var branch = branches[bCode];
        var branchOrderCount = Object.keys(branch.orders).length;
        var branchStaffCount = Object.keys(branch.staff).length;

        // Branch header row
        html += '<tr><td colspan="7" style="padding:0;border:none;">' +
            '<div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;padding:10px 16px;font-weight:700;font-size:13px;display:flex;justify-content:space-between;align-items:center;' +
            (rowNum > 0 ? 'margin-top:16px;' : '') + 'border-radius:var(--radius) var(--radius) 0 0;">' +
            '<span><i class="fas fa-store"></i> ' + escHtml(branch.name) + '</span>' +
            '<span style="font-size:12px;font-weight:400;opacity:0.9;">' + branchStaffCount + ' Staff | ' + branchOrderCount + ' Orders | Qty: ' + branch.qty.toFixed(2) + '</span>' +
            '</div></td></tr>';

        branch.items.forEach(function(item, idx) {
            var saleDate = item.sale_date ? new Date(item.sale_date + 'T00:00:00').toLocaleDateString('en-GB') : '';
            html += '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td style="word-break:break-all;">' + escHtml(item.order_no || '') + '</td>' +
                '<td>' + escHtml(saleDate) + '</td>' +
                '<td style="word-break:break-word;">' + escHtml(item.staff_name || '') + '</td>' +
                '<td style="word-break:break-all;">' + escHtml(item.barcode || '') + '</td>' +
                '<td style="word-break:break-word;">' + escHtml(item.product_desc || '') + '</td>' +
                '<td class="text-end fw-bold">' + (parseFloat(item.qty) || 0).toFixed(2) + '</td>' +
                '</tr>';
        });

        // Branch subtotal row
        html += '<tr style="font-weight:700;background:#f0f0f0;"><td colspan="6">Subtotal (' + escHtml(branch.name) + ')</td><td class="text-end">' + branch.qty.toFixed(2) + '</td></tr>';

        rowNum++;
    });

    // Grand total row
    html += '<tr><td colspan="7" style="padding:0;border:none;">' +
        '<div style="background:var(--primary);color:#fff;padding:12px 16px;border-radius:var(--radius);font-weight:700;display:flex;justify-content:space-between;margin-top:8px;">' +
        '<span>GRAND TOTAL</span><span>' + branchKeys.length + ' Branches | ' + Object.keys(allStaff).length + ' Staff | ' + totalOrders + ' Orders | Qty: ' + totalQty.toFixed(2) + '</span>' +
        '</div></td></tr>';

    html += '</tbody></table>';

    document.getElementById('reportContent').innerHTML = html;

    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('sumBranches').textContent = branchKeys.length;
    document.getElementById('sumOrders').textContent = totalOrders;
    document.getElementById('sumQty').textContent = totalQty.toFixed(2);
    document.getElementById('sumStaff').textContent = Object.keys(allStaff).length;

    if (dtTable) { dtTable.destroy(); dtTable = null; }
}

function initDataTable() {
    if (dtTable) { dtTable.destroy(); }
    dtTable = $('#reportTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        pageLength: 50,
        ordering: true,
        order: []
    });
}
</script>
</body>
</html>
