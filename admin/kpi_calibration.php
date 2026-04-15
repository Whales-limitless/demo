<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'kpi_calibration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KPI Calibration</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px; --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.page-header .subtitle { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.calibration-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 24px; margin-bottom: 20px; }
.calibration-card h2 { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px; }
.calibration-card h2 i { color: var(--primary); }
.filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.filter-bar label { font-size: 13px; font-weight: 600; }
.filter-bar input, .filter-bar select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
.filter-bar button { padding: 7px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.filter-bar button:hover { background: var(--primary-dark); }
.btn-finalize { padding: 10px 24px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; }
.btn-finalize:hover { background: var(--primary-dark); }
.btn-finalize:disabled { background: #9ca3af; cursor: not-allowed; }
.status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.status-badge.finalized { background: #d1fae5; color: #065f46; }
.status-badge.draft { background: #fef3c7; color: #92400e; }
.status-badge.pending { background: #e0e7ff; color: #3730a3; }
.summary-cards { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.summary-card { flex: 1; min-width: 140px; background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 16px; text-align: center; }
.summary-card .label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
.summary-card .value { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
@media (max-width: 768px) { .page-content { padding: 16px; } .calibration-card { padding: 16px; } .table-card { padding: 12px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-sliders-h" style="color:var(--primary);margin-right:8px;"></i>KPI Calibration</h1>
            <div class="subtitle">Review and finalize monthly KPI scores for all employees</div>
        </div>
    </div>

    <div class="filter-bar">
        <label>Month:</label>
        <input type="month" id="calibrationMonth" value="<?php echo date('Y-m'); ?>">
        <label>Department:</label>
        <select id="deptFilter">
            <option value="">All Departments</option>
        </select>
        <button onclick="loadCalibration();"><i class="fas fa-search"></i> Load</button>
    </div>

    <div class="summary-cards" id="summaryCards">
        <div class="summary-card">
            <div class="label">Total Employees</div>
            <div class="value" id="sumTotal">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Reviewed</div>
            <div class="value" id="sumReviewed">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Pending Review</div>
            <div class="value" id="sumPendingReview">0</div>
        </div>
        <div class="summary-card">
            <div class="label">Finalized</div>
            <div class="value" id="sumFinalized">0</div>
        </div>
    </div>

    <div class="table-card">
        <div id="calibrationContent">
            <p style="text-align:center;color:var(--text-muted);padding:40px;">Select a month and click Load to view KPI calibration data.</p>
        </div>
    </div>

    <div style="margin-top:20px;text-align:right;">
        <button class="btn-finalize" id="btnFinalize" disabled onclick="finalizeMonth();"><i class="fas fa-check-circle"></i> Finalize Month</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function loadCalibration() {
    document.getElementById('calibrationContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    // TODO: Wire up AJAX call to KPI calibration backend
    document.getElementById('calibrationContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">No KPI calibration data available yet.</p>';
}

function finalizeMonth() {
    Swal.fire({
        title: 'Finalize KPI?',
        text: 'Are you sure you want to finalize KPI scores for this month? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--primary)',
        confirmButtonText: 'Yes, finalize',
        cancelButtonText: 'Cancel'
    }).then(function(result) {
        if (result.isConfirmed) {
            // TODO: Wire up AJAX call to finalize KPI scores
            Swal.fire('Finalized!', 'Monthly KPI scores have been finalized.', 'success');
        }
    });
}
</script>
</body>
</html>
