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
$result = $connect->query("SELECT `USERNAME` AS `CODE`, `USER_NAME` AS `NAME` FROM `sysfile` WHERE `TYPE` = 'D' ORDER BY `USER_NAME` ASC");
if ($result) { while ($r = $result->fetch_assoc()) { $drivers[] = $r; } }

$selectedDriver = $_GET['driver'] ?? '';

$currentPage = 'del_assign';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Driver</title>
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
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-assign { background: #16a34a; } .btn-assign:hover { background: #15803d; }
.btn-unassign { background: #ef4444; } .btn-unassign:hover { background: #dc2626; }

/* Dual panel */
.assign-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.panel { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; }
.panel h5 { font-family: 'Outfit', sans-serif; font-weight: 700; margin-bottom: 16px; font-size: 16px; }
.panel h5 .count { font-weight: 400; color: var(--text-muted); font-size: 13px; }
.order-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
.order-row:hover { background: #f9fafb; }
.order-info { flex: 1; }
.order-info strong { display: block; }
.order-info small { color: var(--text-muted); }
.btn-back { background: #6b7280; color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.driver-select { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
.driver-select select { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; min-width: 250px; }

@media (max-width: 768px) { .assign-panels { grid-template-columns: 1fr; } .page-content { padding: 16px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-user-check" style="color:var(--primary);margin-right:8px;"></i>Assign Driver</h1>
    </div>

    <div class="driver-select">
        <label style="font-weight:600;font-size:14px;">Select Driver:</label>
        <select id="driverSelect" onchange="onDriverChange();">
            <option value="">-- Choose a Driver --</option>
            <?php foreach ($drivers as $d): ?>
            <option value="<?php echo htmlspecialchars($d['CODE']); ?>" <?php echo $selectedDriver === $d['CODE'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['NAME']); ?> (<?php echo htmlspecialchars($d['CODE']); ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="assign-panels" id="panels" style="<?php echo $selectedDriver ? '' : 'display:none;'; ?>">
        <div class="panel">
            <h5><i class="fas fa-inbox" style="color:#6b7280;"></i> Available Orders <span class="count" id="availCount">(0)</span></h5>
            <div id="availOrders"></div>
        </div>
        <div class="panel">
            <h5><i class="fas fa-user-check" style="color:#16a34a;"></i> Assigned to Driver <span class="count" id="assignedCount">(0)</span></h5>
            <div id="assignedOrders"></div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function onDriverChange() {
    var code = document.getElementById('driverSelect').value;
    if (!code) { document.getElementById('panels').style.display = 'none'; return; }
    document.getElementById('panels').style.display = '';
    loadAssignment(code);
}

function loadAssignment(driverCode) {
    $.ajax({
        type: 'POST', url: 'del_assign_ajax.php', data: { action: 'load', driver_code: driverCode }, dataType: 'json',
        success: function(data) {
            if (data.error) return;
            renderPanel('availOrders', data.available || [], 'assign', driverCode);
            renderPanel('assignedOrders', data.assigned || [], 'unassign', driverCode);
            document.getElementById('availCount').textContent = '(' + (data.available || []).length + ')';
            document.getElementById('assignedCount').textContent = '(' + (data.assigned || []).length + ')';
        }
    });
}

function renderPanel(containerId, orders, actionType, driverCode) {
    var container = document.getElementById(containerId);
    if (orders.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-muted);">No orders</div>';
        return;
    }
    container.innerHTML = orders.map(function(o) {
        var btnClass = actionType === 'assign' ? 'btn-assign' : 'btn-unassign';
        var btnIcon = actionType === 'assign' ? 'fa-arrow-right' : 'fa-arrow-left';
        var btnText = actionType === 'assign' ? 'Assign' : 'Remove';
        return '<div class="order-row">' +
            '<div class="order-info"><strong>' + escHtml(o.ORDNO||'') + '</strong><small>' + escHtml(o.DELDATE||'') + ' | ' + escHtml(o.CUSTOMER||'') + ' | ' + escHtml(o.LOCATION||'') + '</small></div>' +
            '<button class="btn-action ' + btnClass + '" onclick="transferOrder(' + o.ID + ',\'' + actionType + '\',\'' + escHtml(driverCode) + '\')"><i class="fas ' + btnIcon + '"></i> ' + btnText + '</button>' +
        '</div>';
    }).join('');
}

function transferOrder(orderId, actionType, driverCode) {
    $.ajax({
        type: 'POST', url: 'del_assign_ajax.php', data: { action: actionType, id: orderId, driver_code: driverCode }, dataType: 'json',
        success: function(data) {
            if (data.success) { loadAssignment(driverCode); }
            else { Swal.fire({ icon: 'error', text: data.error || 'Failed.' }); }
        }
    });
}

<?php if ($selectedDriver): ?>
document.addEventListener('DOMContentLoaded', function() { loadAssignment('<?php echo htmlspecialchars($selectedDriver); ?>'); });
<?php endif; ?>
</script>
</body>
</html>
