<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$drivers = [];
$dr = $connect->query("SELECT `CODE`, `NAME` FROM `del_driver` ORDER BY `NAME` ASC");
if ($dr) { while ($r = $dr->fetch_assoc()) { $drivers[] = $r; } }

$locations = [];
$lr = $connect->query("SELECT `ID`, `NAME` FROM `del_location` ORDER BY `NAME` ASC");
if ($lr) { while ($r = $lr->fetch_assoc()) { $locations[] = $r; } }

$currentPage = 'del_report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery Reports</title>
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
.report-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
.report-tab { padding: 8px 20px; border: 2px solid #e5e7eb; border-radius: 10px; background: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: all var(--transition); color: var(--text-muted); }
.report-tab:hover { border-color: var(--primary); color: var(--primary); }
.report-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }
@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:8px;"></i>Delivery Reports</h1>
    </div>

    <div class="report-tabs">
        <button class="report-tab active" onclick="switchReport(this, 'summary')">Driver Summary</button>
        <button class="report-tab" onclick="switchReport(this, 'detailed')">Driver Detailed</button>
    </div>

    <div class="filter-bar">
        <label>From:</label><input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
        <label>To:</label><input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
        <select id="filterDriver"><option value="">All Drivers</option><?php foreach ($drivers as $d): ?><option value="<?php echo htmlspecialchars($d['CODE']); ?>"><?php echo htmlspecialchars($d['NAME']); ?></option><?php endforeach; ?></select>
        <select id="filterLocation"><option value="">All Locations</option><?php foreach ($locations as $l): ?><option value="<?php echo htmlspecialchars($l['NAME']); ?>"><?php echo htmlspecialchars($l['NAME']); ?></option><?php endforeach; ?></select>
        <button onclick="generateReport();"><i class="fas fa-search"></i> Generate</button>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Select filters and click Generate to view the report.</p>
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
var currentReport = 'summary';
var dtTable = null;

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function switchReport(el, type) {
    document.querySelectorAll('.report-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
    currentReport = type;
}

function generateReport() {
    var postData = {
        action: currentReport,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        driver: document.getElementById('filterDriver').value,
        location: document.getElementById('filterLocation').value
    };

    $.ajax({
        type: 'POST', url: 'del_report_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            if (currentReport === 'summary') renderSummary(data.rows || []);
            else renderDetailed(data.rows || []);
        }
    });
}

function renderSummary(rows) {
    var html = '<table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">' +
        '<thead><tr><th>No</th><th>Driver</th><th>Total Orders</th><th>Total Distance (km)</th><th>Total Commission (RM)</th></tr></thead><tbody>';
    var totalOrders = 0, totalDist = 0, totalComm = 0;
    rows.forEach(function(r, i) {
        totalOrders += parseInt(r.total_orders) || 0;
        totalDist += parseFloat(r.total_distance) || 0;
        totalComm += parseFloat(r.total_commission) || 0;
        html += '<tr><td>' + (i+1) + '</td><td>' + escHtml(r.DRIVER) + '</td><td>' + r.total_orders + '</td><td>' + (parseFloat(r.total_distance)||0).toFixed(2) + '</td><td>' + (parseFloat(r.total_commission)||0).toFixed(2) + '</td></tr>';
    });
    html += '</tbody><tfoot><tr style="font-weight:700;"><td colspan="2">TOTAL</td><td>' + totalOrders + '</td><td>' + totalDist.toFixed(2) + '</td><td>' + totalComm.toFixed(2) + '</td></tr></tfoot></table>';
    document.getElementById('reportContent').innerHTML = html;
    initDataTable();
}

function renderDetailed(rows) {
    var html = '<table id="reportTable" class="table table-striped table-sm" style="width:100%;font-size:13px;">' +
        '<thead><tr><th>No</th><th>Del. Date</th><th>Done At</th><th>Order No</th><th>Driver</th><th>Customer</th><th>Location</th><th>Distance</th><th>Commission</th></tr></thead><tbody>';
    var totalDist = 0, totalComm = 0;
    rows.forEach(function(r, i) {
        totalDist += parseFloat(r.DISTANT) || 0;
        totalComm += parseFloat(r.RETAIL) || 0;
        html += '<tr><td>' + (i+1) + '</td><td>' + escHtml(r.DELDATE||'') + '</td><td>' + escHtml(r.DONETIME||'') + '</td><td>' + escHtml(r.ORDNO||'') + '</td><td>' + escHtml(r.DRIVER||'') + '</td><td>' + escHtml(r.CUSTOMER||'') + '</td><td>' + escHtml(r.LOCATION||'') + '</td><td>' + (parseFloat(r.DISTANT)||0).toFixed(2) + '</td><td>' + (parseFloat(r.RETAIL)||0).toFixed(2) + '</td></tr>';
    });
    html += '</tbody><tfoot><tr style="font-weight:700;"><td colspan="7">TOTAL</td><td>' + totalDist.toFixed(2) + '</td><td>' + totalComm.toFixed(2) + '</td></tr></tfoot></table>';
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
        order: []
    });
}
</script>
</body>
</html>
