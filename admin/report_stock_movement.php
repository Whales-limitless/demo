<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'report_stock_movement';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Movement Report</title>
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
.text-positive { color: #16a34a; }
.text-negative { color: #dc2626; }
@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-exchange-alt" style="color:var(--primary);margin-right:8px;"></i>Stock Movement Report</h1>
    </div>

    <div class="filter-bar">
        <label>Start Date:</label><input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
        <label>End Date:</label><input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
        <input type="text" id="searchInput" placeholder="Search product..." style="flex:1;max-width:250px;">
        <div style="position:relative;" id="branchFilterWrap">
            <button type="button" id="branchFilterBtn" onclick="toggleBranchDropdown();" style="padding:7px 14px;border:1px solid #d1d5db;border-radius:8px;background:#fff;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-building"></i> <span id="branchFilterLabel">All Branches</span> <i class="fas fa-chevron-down" style="font-size:10px;"></i>
            </button>
            <div id="branchDropdown" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.12);z-index:100;min-width:220px;max-height:300px;overflow-y:auto;padding:8px 0;">
                <div style="padding:6px 14px;border-bottom:1px solid #e5e7eb;">
                    <label style="font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" id="branchSelectAll" checked onchange="toggleAllBranches(this.checked);"> All Branches
                    </label>
                </div>
                <div id="branchCheckboxes" style="padding:4px 0;"></div>
            </div>
        </div>
        <button onclick="generateReport();"><i class="fas fa-search"></i> Apply Filter</button>
    </div>

    <div class="summary-cards" id="summaryCards" style="display:none;">
        <div class="summary-card">
            <div class="label">Total Products</div>
            <div class="value" id="sumProducts">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total In</div>
            <div class="value text-positive" id="sumIn">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Out</div>
            <div class="value text-negative" id="sumOut">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Select date range and click Apply Filter to view the report.</p>
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
var allBranches = []; // [{code, name}, ...]

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function fmtNum(n) { return parseFloat(n || 0).toFixed(2); }

// ==================== BRANCH FILTER ====================
function loadBranches() {
    $.post('branch_ajax.php', { action: 'list' }, function(data) {
        allBranches = data.branches || [];
        var container = document.getElementById('branchCheckboxes');
        if (allBranches.length === 0) {
            container.innerHTML = '<div style="padding:8px 14px;color:#6b7280;font-size:12px;">No branches found</div>';
            return;
        }
        var html = '';
        allBranches.forEach(function(br) {
            html += '<label style="display:flex;align-items:center;gap:8px;padding:5px 14px;font-size:13px;cursor:pointer;">' +
                '<input type="checkbox" class="branch-cb" value="' + escHtml(br.code) + '" checked> ' +
                escHtml(br.name) + '</label>';
        });
        container.innerHTML = html;
        // Listen for individual checkbox changes
        container.querySelectorAll('.branch-cb').forEach(function(cb) {
            cb.addEventListener('change', updateBranchLabel);
        });
    }, 'json');
}

function toggleBranchDropdown() {
    var dd = document.getElementById('branchDropdown');
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('branchFilterWrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('branchDropdown').style.display = 'none';
    }
});

function toggleAllBranches(checked) {
    document.querySelectorAll('.branch-cb').forEach(function(cb) { cb.checked = checked; });
    updateBranchLabel();
}

function updateBranchLabel() {
    var cbs = document.querySelectorAll('.branch-cb');
    var checked = document.querySelectorAll('.branch-cb:checked');
    var allCb = document.getElementById('branchSelectAll');

    if (checked.length === 0 || checked.length === cbs.length) {
        allCb.checked = true;
        cbs.forEach(function(cb) { cb.checked = true; });
        document.getElementById('branchFilterLabel').textContent = 'All Branches';
    } else {
        allCb.checked = false;
        var names = [];
        checked.forEach(function(cb) {
            var br = allBranches.find(function(b) { return b.code === cb.value; });
            if (br) names.push(br.name);
        });
        document.getElementById('branchFilterLabel').textContent = names.length <= 2 ? names.join(', ') : checked.length + ' branches';
    }
}

function getSelectedBranches() {
    var cbs = document.querySelectorAll('.branch-cb');
    var checked = document.querySelectorAll('.branch-cb:checked');
    // If all checked or none checked, return empty (= all branches combined)
    if (checked.length === 0 || checked.length === cbs.length) return [];
    var codes = [];
    checked.forEach(function(cb) { codes.push(cb.value); });
    return codes;
}

// Load branches on page ready
document.addEventListener('DOMContentLoaded', function() { loadBranches(); });

// ==================== REPORT ====================
function generateReport() {
    var postData = {
        action: 'stock_movement',
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        search: document.getElementById('searchInput').value.trim()
    };

    var selBranches = getSelectedBranches();
    for (var bi = 0; bi < selBranches.length; bi++) {
        postData['branches[' + bi + ']'] = selBranches[bi];
    }

    document.getElementById('reportContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    $.ajax({
        type: 'POST', url: 'report_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            renderTable(data.rows || [], data.branches || []);
        },
        error: function() { Swal.fire({ icon: 'error', text: 'Failed to load report.' }); }
    });
}

function renderTable(rows, branches) {
    if (rows.length === 0) {
        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No data found for the selected period.</p>';
        document.getElementById('summaryCards').style.display = 'none';
        return;
    }

    var hasBranches = branches && branches.length > 0;
    var totalIn = 0, totalOut = 0;

    // Build header
    var html = '<div style="overflow-x:auto;"><table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">';
    html += '<thead>';

    if (hasBranches) {
        // Two-row header: top row for branch group names, bottom row for In/Out/Adj per branch + totals
        html += '<tr><th rowspan="2" style="vertical-align:middle;">No</th><th rowspan="2" style="vertical-align:middle;">Description</th><th rowspan="2" class="text-end" style="vertical-align:middle;" title="Stock balance before the start date, based on all historical transactions"><i class="fas fa-info-circle" style="font-size:10px;color:var(--text-muted);margin-right:3px;"></i>Opening</th>';
        branches.forEach(function(br) {
            html += '<th colspan="3" class="text-center" style="background:#f0f4ff;border-bottom:2px solid #3b82f6;">' + escHtml(br.name) + '</th>';
        });
        html += '<th colspan="3" class="text-center" style="background:#f0fdf4;border-bottom:2px solid #16a34a;">Total</th>';
        html += '<th rowspan="2" class="text-end" style="vertical-align:middle;">Closing</th></tr>';

        html += '<tr>';
        branches.forEach(function() {
            html += '<th class="text-end" style="font-size:11px;background:#f0f4ff;">In</th><th class="text-end" style="font-size:11px;background:#f0f4ff;">Out</th><th class="text-end" style="font-size:11px;background:#f0f4ff;">Adj</th>';
        });
        html += '<th class="text-end" style="font-size:11px;background:#f0fdf4;">In</th><th class="text-end" style="font-size:11px;background:#f0fdf4;">Out</th><th class="text-end" style="font-size:11px;background:#f0fdf4;">Adj</th>';
        html += '</tr>';
    } else {
        html += '<tr><th>No</th><th>Description</th><th class="text-end" title="Stock balance before the start date, based on all historical transactions"><i class="fas fa-info-circle" style="font-size:10px;color:var(--text-muted);margin-right:3px;"></i>Opening</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Adj</th><th class="text-end">Closing</th></tr>';
    }
    html += '</thead><tbody>';

    rows.forEach(function(r, i) {
        var inQty = parseFloat(r.in) || 0;
        var outQty = parseFloat(r.out) || 0;
        totalIn += inQty;
        totalOut += outQty;

        html += '<tr>';
        html += '<td>' + (i+1) + '</td>';
        html += '<td>' + escHtml(r.description) + '</td>';
        html += '<td class="text-end">' + fmtNum(r.opening) + '</td>';

        if (hasBranches && r.branches) {
            branches.forEach(function(br) {
                var bd = r.branches[br.code] || { in: 0, out: 0, adj: 0 };
                var brIn = parseFloat(bd.in) || 0;
                var brOut = parseFloat(bd.out) || 0;
                var brAdj = parseFloat(bd.adj) || 0;
                html += '<td class="text-end text-success" style="background:#fafbff;">' + (brIn > 0 ? fmtNum(brIn) : '') + '</td>';
                html += '<td class="text-end text-danger" style="background:#fafbff;">' + (brOut > 0 ? fmtNum(brOut) : '') + '</td>';
                html += '<td class="text-end" style="background:#fafbff;">' + (brAdj !== 0 ? fmtNum(brAdj) : '') + '</td>';
            });
        }

        html += '<td class="text-end text-success fw-bold">' + (inQty > 0 ? fmtNum(inQty) : '') + '</td>';
        html += '<td class="text-end text-danger fw-bold">' + (outQty > 0 ? fmtNum(outQty) : '') + '</td>';
        html += '<td class="text-end">' + (parseFloat(r.adj) !== 0 ? fmtNum(r.adj) : '') + '</td>';
        html += '<td class="text-end fw-bold">' + fmtNum(r.closing) + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    document.getElementById('reportContent').innerHTML = html;

    document.getElementById('summaryCards').style.display = 'flex';
    document.getElementById('sumProducts').textContent = rows.length;
    document.getElementById('sumIn').textContent = fmtNum(totalIn);
    document.getElementById('sumOut').textContent = fmtNum(totalOut);

    initDataTable();
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
