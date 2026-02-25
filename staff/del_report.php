<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Look up driver code
$driverCode = '';
$staffUser = $_SESSION['user_name'] ?? '';
$stmt = $connect->prepare("SELECT `CODE` FROM `del_driver` WHERE `USERNAME` = ? LIMIT 1");
$stmt->bind_param("s", $staffUser);
$stmt->execute();
$dResult = $stmt->get_result();
if ($dResult->num_rows > 0) {
    $driverCode = $dResult->fetch_assoc()['CODE'];
}
$stmt->close();

// Fetch locations for filter
$locations = [];
$lr = $connect->query("SELECT `ID`, `NAME` FROM `del_location` ORDER BY `NAME` ASC");
if ($lr) { while ($r = $lr->fetch_assoc()) { $locations[] = $r; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 80px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
        .main-content { max-width: 900px; margin: 0 auto; padding: 16px; }

        .report-tabs { display: flex; gap: 8px; margin-bottom: 16px; }
        .report-tab { padding: 8px 18px; border-radius: 20px; border: 2px solid #e5e7eb; background: var(--surface); font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: all 0.2s; }
        .report-tab:hover { border-color: var(--primary); color: var(--primary); }
        .report-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .filter-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; margin-bottom: 16px; }
        .filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: var(--text-muted); }
        .filter-group input, .filter-group select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: var(--primary); }
        .btn-generate { padding: 8px 20px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-generate:hover { background: var(--primary-dark); }

        .table-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; overflow-x: auto; }
        .table-card table { width: 100%; font-size: 13px; }
        .table-card th { font-weight: 600; }

        .not-driver { text-align: center; padding: 60px 20px; }
        .not-driver svg { width: 56px; height: 56px; color: var(--text-muted); opacity: 0.4; margin-bottom: 16px; }
        .not-driver h2 { font-size: 18px; margin-bottom: 8px; }
        .not-driver p { font-size: 14px; color: var(--text-muted); }

        /* DataTables overrides */
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 8px; font-family: 'DM Sans', sans-serif; }
        .dataTables_wrapper .dt-buttons .dt-button { background: var(--primary) !important; color: #fff !important; border: none !important; border-radius: 6px !important; font-family: 'DM Sans', sans-serif; font-size: 12px; padding: 5px 12px !important; margin-right: 4px; }

        @media (max-width: 480px) { .filter-row { flex-direction: column; } .filter-group { width: 100%; } .filter-group input, .filter-group select { width: 100%; } }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <span class="page-title">Delivery Reports</span>
    </header>

    <div class="main-content">
    <?php if ($driverCode === ''): ?>
        <div class="not-driver">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <h2>Not a Driver</h2>
            <p>Your account is not linked to a driver profile.</p>
        </div>
    <?php else: ?>
        <div class="report-tabs">
            <button class="report-tab active" onclick="switchReport(this, 'summary')">Summary</button>
            <button class="report-tab" onclick="switchReport(this, 'detailed')">Detailed</button>
        </div>

        <div class="filter-card">
            <div class="filter-row">
                <div class="filter-group">
                    <label>From</label>
                    <input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="filter-group">
                    <label>To</label>
                    <input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="filter-group">
                    <label>Location</label>
                    <select id="filterLocation">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $l): ?>
                        <option value="<?php echo htmlspecialchars($l['NAME']); ?>"><?php echo htmlspecialchars($l['NAME']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn-generate" onclick="generateReport()">Generate</button>
            </div>
        </div>

        <div class="table-card">
            <div id="reportContent">
                <p style="text-align:center;color:var(--text-muted);padding:40px;">Select filters and click Generate to view your report.</p>
            </div>
        </div>
    <?php endif; ?>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script>
    var currentReport = 'summary';
    var dtTable = null;
    var driverCode = '<?php echo addslashes($driverCode); ?>';

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
            driver: driverCode,
            location: document.getElementById('filterLocation').value
        };

        document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:40px;">Loading...</p>';

        $.ajax({
            type: 'POST', url: 'del_report_ajax.php', data: postData, dataType: 'json',
            success: function(data) {
                if (data.error) { Swal.fire({ icon: 'error', text: data.error, confirmButtonColor: '#C8102E' }); return; }
                if (currentReport === 'summary') renderSummary(data.rows || []);
                else renderDetailed(data.rows || []);
            },
            error: function() {
                document.getElementById('reportContent').innerHTML = '<p style="text-align:center;color:#dc2626;padding:40px;">Failed to load report.</p>';
            }
        });
    }

    function renderSummary(rows) {
        var html = '<table id="reportTable" class="display" style="width:100%;font-size:13px;">' +
            '<thead><tr><th>No</th><th>Total Orders</th><th>Total Distance (km)</th><th>Total Commission (RM)</th></tr></thead><tbody>';
        var totalOrders = 0, totalDist = 0, totalComm = 0;
        rows.forEach(function(r, i) {
            totalOrders += parseInt(r.total_orders) || 0;
            totalDist += parseFloat(r.total_distance) || 0;
            totalComm += parseFloat(r.total_commission) || 0;
            html += '<tr><td>' + (i + 1) + '</td><td>' + r.total_orders + '</td><td>' + (parseFloat(r.total_distance) || 0).toFixed(2) + '</td><td>' + (parseFloat(r.total_commission) || 0).toFixed(2) + '</td></tr>';
        });
        html += '</tbody><tfoot><tr style="font-weight:700;"><td>TOTAL</td><td>' + totalOrders + '</td><td>' + totalDist.toFixed(2) + '</td><td>' + totalComm.toFixed(2) + '</td></tr></tfoot></table>';
        document.getElementById('reportContent').innerHTML = html;
        initDataTable();
    }

    function renderDetailed(rows) {
        var html = '<table id="reportTable" class="display" style="width:100%;font-size:13px;">' +
            '<thead><tr><th>No</th><th>Del. Date</th><th>Done At</th><th>Order No</th><th>Customer</th><th>Location</th><th>Distance</th><th>Commission</th></tr></thead><tbody>';
        var totalDist = 0, totalComm = 0;
        rows.forEach(function(r, i) {
            totalDist += parseFloat(r.DISTANT) || 0;
            totalComm += parseFloat(r.RETAIL) || 0;
            html += '<tr><td>' + (i + 1) + '</td><td>' + escHtml(r.DELDATE || '') + '</td><td>' + escHtml(r.DONETIME || '') + '</td><td>' + escHtml(r.ORDNO || '') + '</td><td>' + escHtml(r.CUSTOMER || '') + '</td><td>' + escHtml(r.LOCATION || '') + '</td><td>' + (parseFloat(r.DISTANT) || 0).toFixed(2) + '</td><td>' + (parseFloat(r.RETAIL) || 0).toFixed(2) + '</td></tr>';
        });
        html += '</tbody><tfoot><tr style="font-weight:700;"><td colspan="6">TOTAL</td><td>' + totalDist.toFixed(2) + '</td><td>' + totalComm.toFixed(2) + '</td></tr></tfoot></table>';
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
