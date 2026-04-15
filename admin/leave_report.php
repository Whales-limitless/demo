<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'leave_report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Report</title>
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
@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-calendar-check" style="color:var(--primary);margin-right:8px;"></i>Leave Report</h1>
    </div>

    <div class="filter-bar">
        <label>Start Date:</label><input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
        <label>End Date:</label><input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
        <label>Staff:</label>
        <select id="staffFilter">
            <option value="">All Staff</option>
        </select>
        <button onclick="generateReport();"><i class="fas fa-search"></i> Generate</button>
    </div>

    <div class="summary-cards" id="summaryCards">
        <div class="summary-card">
            <div class="label">Total Leave Days</div>
            <div class="value" id="sumLeaveDays">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Approved</div>
            <div class="value" id="sumApproved">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Pending</div>
            <div class="value" id="sumPending">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Rejected</div>
            <div class="value" id="sumRejected">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="reportContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Select date range and click Generate to view the leave report.</p>
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

function generateReport() {
    document.getElementById('reportContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    // TODO: Wire up AJAX call to leave report backend
    document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No leave data available yet.</p>';
}
</script>
</body>
</html>
