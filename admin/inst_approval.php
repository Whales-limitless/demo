<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Auto-create table if it doesn't exist
$connect->query("CREATE TABLE IF NOT EXISTS `inst_job` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `USERCODE` varchar(50) NOT NULL,
  `USERNAME` varchar(80) NOT NULL,
  `IMAGE` varchar(200) NOT NULL DEFAULT '',
  `REMARK` text NOT NULL,
  `STATUS` varchar(1) NOT NULL DEFAULT 'P',
  `REJECT_REASON` text NOT NULL,
  `APPROVE_REASON` text NOT NULL,
  `COMMISSION` double(10,2) NOT NULL DEFAULT 0.00,
  `SUBMIT_DATETIME` datetime NOT NULL,
  `REVIEWED_BY` varchar(50) NOT NULL DEFAULT '',
  `REVIEWED_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idx_user` (`USERCODE`),
  KEY `idx_status` (`STATUS`),
  KEY `idx_submit` (`SUBMIT_DATETIME`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$users = [];
$ur = $connect->query("SELECT `USERNAME` AS `CODE`, `USER_NAME` AS `NAME` FROM `sysfile` WHERE `TYPE` IN ('D','A') ORDER BY `USER_NAME` ASC");
if ($ur) { while ($r = $ur->fetch_assoc()) { $users[] = $r; } }

$currentPage = 'inst_approval';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Installation Job Approval</title>
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
.btn-view { background: #3b82f6; } .btn-view:hover { background: #2563eb; }
.btn-approve { background: #16a34a; } .btn-approve:hover { background: #15803d; }
.btn-reject { background: #dc2626; } .btn-reject:hover { background: #b91c1c; }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #dcfce7; color: #16a34a; }
.badge-rejected { background: #fee2e2; color: #dc2626; }

.status-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.status-tab { padding: 8px 20px; border: 2px solid #e5e7eb; border-radius: 10px; background: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: all var(--transition); color: var(--text-muted); }
.status-tab:hover { border-color: var(--primary); color: var(--primary); }
.status-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.status-tab .tab-count { font-size: 11px; margin-left: 6px; opacity: 0.8; }

.filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.filter-bar label { font-size: 13px; font-weight: 600; }
.filter-bar input, .filter-bar select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
.filter-bar button { padding: 7px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
.empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }

.thumb-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; }
.remark-cell { max-width: 220px; white-space: normal; word-break: break-word; }
.commission-cell { font-weight: 700; color: #16a34a; }

.modal-img { max-width: 100%; border-radius: 8px; }
.review-info { background: #f9fafb; padding: 10px 12px; border-radius: 8px; font-size: 13px; line-height: 1.6; margin-bottom: 12px; }
.review-info strong { color: var(--text); }

@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } .btn-action { padding: 4px 8px; font-size: 11px; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:8px;"></i>Installation Job Approval</h1>
    </div>

    <div class="status-tabs">
        <button class="status-tab" data-status="" onclick="switchTab(this, '')">All</button>
        <button class="status-tab active" data-status="P" onclick="switchTab(this, 'P')">Pending <span class="tab-count" id="countPending">0</span></button>
        <button class="status-tab" data-status="A" onclick="switchTab(this, 'A')">Approved <span class="tab-count" id="countApproved">0</span></button>
        <button class="status-tab" data-status="R" onclick="switchTab(this, 'R')">Rejected <span class="tab-count" id="countRejected">0</span></button>
    </div>

    <div class="filter-bar">
        <label>From:</label><input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
        <label>To:</label><input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
        <select id="filterUser"><option value="">All Users</option><?php foreach ($users as $u): ?><option value="<?php echo htmlspecialchars($u['CODE']); ?>"><?php echo htmlspecialchars($u['NAME']); ?></option><?php endforeach; ?></select>
        <button onclick="loadData()"><i class="fas fa-filter"></i> Filter</button>
    </div>

    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Photo</th>
                        <th>Submitted By</th>
                        <th>Date Time</th>
                        <th>Remark</th>
                        <th>Status</th>
                        <th>Commission (RM)</th>
                        <th>Reviewed</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <tr><td colspan="9" class="empty-state"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View / Image Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-image"></i> Installation Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody"></div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#dcfce7;">
                <h5 class="modal-title"><i class="fas fa-check-circle" style="color:#16a34a;"></i> Approve Installation Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approveId">
                <div class="review-info" id="approveInfo"></div>
                <div class="mb-3">
                    <label class="form-label">Commission (RM)</label>
                    <input type="number" step="0.01" min="0" id="approveCommission" class="form-control" placeholder="0.00" value="0.00">
                    <small class="text-muted">Optional - leave 0 if no commission.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Approval Note <span class="text-muted">(optional)</span></label>
                    <textarea id="approveReason" class="form-control" rows="3" placeholder="Optional note for the user..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmApprove()"><i class="fas fa-check"></i> Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#fee2e2;">
                <h5 class="modal-title"><i class="fas fa-times-circle" style="color:#dc2626;"></i> Reject Installation Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectId">
                <div class="review-info" id="rejectInfo"></div>
                <div class="mb-3">
                    <label class="form-label">Reject Reason <span class="text-muted">(optional)</span></label>
                    <textarea id="rejectReason" class="form-control" rows="3" placeholder="Explain why this is being rejected..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()"><i class="fas fa-times"></i> Reject</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var currentStatus = 'P';
var viewModal = null, approveModal = null, rejectModal = null;
var allRows = [];

document.addEventListener('DOMContentLoaded', function() {
    viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
    rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    loadData();
});

function escHtml(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

function switchTab(el, status) {
    document.querySelectorAll('.status-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
    currentStatus = status;
    loadData();
}

function loadData() {
    var postData = {
        action: 'list',
        status: currentStatus,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value,
        user: document.getElementById('filterUser').value
    };
    $.ajax({
        type: 'POST', url: 'inst_approval_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            allRows = data.rows || [];
            renderTable(allRows);
            if (data.counts) {
                document.getElementById('countPending').textContent = data.counts.pending || 0;
                document.getElementById('countApproved').textContent = data.counts.approved || 0;
                document.getElementById('countRejected').textContent = data.counts.rejected || 0;
            }
        }
    });
}

function renderTable(rows) {
    var tbody = document.getElementById('dataBody');
    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-state"><i class="fas fa-inbox"></i>No installation jobs found</td></tr>';
        return;
    }
    var statusMap = { 'P': 'Pending', 'A': 'Approved', 'R': 'Rejected' };
    var badgeMap = { 'P': 'badge-pending', 'A': 'badge-approved', 'R': 'badge-rejected' };

    var html = rows.map(function(r, i) {
        var actions = '<button class="btn-action btn-view" onclick="viewJob(' + r.ID + ')"><i class="fas fa-eye"></i></button>';
        if (r.STATUS === 'P') {
            actions += ' <button class="btn-action btn-approve" onclick="openApprove(' + r.ID + ')"><i class="fas fa-check"></i></button>';
            actions += ' <button class="btn-action btn-reject" onclick="openReject(' + r.ID + ')"><i class="fas fa-times"></i></button>';
        }
        var commission = parseFloat(r.COMMISSION) || 0;
        var commissionText = (r.STATUS === 'A' && commission > 0) ? '<span class="commission-cell">' + commission.toFixed(2) + '</span>' : '-';
        var reviewedText = r.REVIEWED_DATETIME ? (escHtml(r.REVIEWED_DATETIME) + (r.REVIEWED_BY ? '<br><small>by ' + escHtml(r.REVIEWED_BY) + '</small>' : '')) : '-';
        var thumb = r.IMAGE ? '<img src="../staff/uploads/' + escHtml(r.IMAGE) + '" class="thumb-img" onclick="viewJob(' + r.ID + ')">' : '-';

        return '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + thumb + '</td>' +
            '<td><strong>' + escHtml(r.USERNAME || r.USERCODE) + '</strong><br><small>' + escHtml(r.USERCODE) + '</small></td>' +
            '<td>' + escHtml(r.SUBMIT_DATETIME || '') + '</td>' +
            '<td class="remark-cell">' + escHtml(r.REMARK || '') + '</td>' +
            '<td><span class="badge-status ' + (badgeMap[r.STATUS] || '') + '">' + (statusMap[r.STATUS] || r.STATUS) + '</span></td>' +
            '<td>' + commissionText + '</td>' +
            '<td>' + reviewedText + '</td>' +
            '<td style="white-space:nowrap">' + actions + '</td>' +
        '</tr>';
    }).join('');
    document.getElementById('dataBody').innerHTML = html;
}

function findRow(id) {
    for (var i = 0; i < allRows.length; i++) if (parseInt(allRows[i].ID) === parseInt(id)) return allRows[i];
    return null;
}

function viewJob(id) {
    var r = findRow(id);
    if (!r) return;
    var statusMap = { 'P': 'Pending', 'A': 'Approved', 'R': 'Rejected' };
    var html = '';
    if (r.IMAGE) html += '<img src="../staff/uploads/' + escHtml(r.IMAGE) + '" class="modal-img" alt="Installation photo">';
    html += '<div style="margin-top:14px;">';
    html += '<div><strong>Submitted by:</strong> ' + escHtml(r.USERNAME || r.USERCODE) + ' (' + escHtml(r.USERCODE) + ')</div>';
    html += '<div><strong>Date Time:</strong> ' + escHtml(r.SUBMIT_DATETIME || '') + '</div>';
    html += '<div><strong>Status:</strong> ' + (statusMap[r.STATUS] || r.STATUS) + '</div>';
    if (r.REMARK) html += '<div><strong>Remark:</strong><br>' + escHtml(r.REMARK).replace(/\n/g, '<br>') + '</div>';
    if (r.STATUS === 'A') {
        var c = parseFloat(r.COMMISSION) || 0;
        html += '<div style="margin-top:10px;color:#16a34a;font-weight:700;"><strong>Commission:</strong> RM ' + c.toFixed(2) + '</div>';
        if (r.APPROVE_REASON) html += '<div><strong>Approval note:</strong><br>' + escHtml(r.APPROVE_REASON).replace(/\n/g, '<br>') + '</div>';
    }
    if (r.STATUS === 'R' && r.REJECT_REASON) {
        html += '<div style="margin-top:10px;color:#dc2626;"><strong>Reject reason:</strong><br>' + escHtml(r.REJECT_REASON).replace(/\n/g, '<br>') + '</div>';
    }
    if (r.REVIEWED_DATETIME) {
        html += '<div style="margin-top:10px;"><strong>Reviewed:</strong> ' + escHtml(r.REVIEWED_DATETIME);
        if (r.REVIEWED_BY) html += ' by ' + escHtml(r.REVIEWED_BY);
        html += '</div>';
    }
    html += '</div>';
    document.getElementById('viewModalBody').innerHTML = html;
    viewModal.show();
}

function openApprove(id) {
    var r = findRow(id); if (!r) return;
    document.getElementById('approveId').value = id;
    document.getElementById('approveCommission').value = '0.00';
    document.getElementById('approveReason').value = '';
    var info = '<strong>User:</strong> ' + escHtml(r.USERNAME || r.USERCODE) + ' (' + escHtml(r.USERCODE) + ')<br>';
    info += '<strong>Submitted:</strong> ' + escHtml(r.SUBMIT_DATETIME || '');
    if (r.REMARK) info += '<br><strong>Remark:</strong> ' + escHtml(r.REMARK);
    document.getElementById('approveInfo').innerHTML = info;
    approveModal.show();
}

function confirmApprove() {
    var id = document.getElementById('approveId').value;
    var commission = parseFloat(document.getElementById('approveCommission').value) || 0;
    var reason = document.getElementById('approveReason').value;
    if (commission < 0) { Swal.fire({ icon: 'warning', text: 'Commission cannot be negative.' }); return; }

    $.ajax({
        type: 'POST', url: 'inst_approval_ajax.php',
        data: { action: 'approve', id: id, commission: commission, reason: reason },
        dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            approveModal.hide();
            Swal.fire({ icon: 'success', text: data.success, timer: 1300, showConfirmButton: false }).then(loadData);
        }
    });
}

function openReject(id) {
    var r = findRow(id); if (!r) return;
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectReason').value = '';
    var info = '<strong>User:</strong> ' + escHtml(r.USERNAME || r.USERCODE) + ' (' + escHtml(r.USERCODE) + ')<br>';
    info += '<strong>Submitted:</strong> ' + escHtml(r.SUBMIT_DATETIME || '');
    if (r.REMARK) info += '<br><strong>Remark:</strong> ' + escHtml(r.REMARK);
    document.getElementById('rejectInfo').innerHTML = info;
    rejectModal.show();
}

function confirmReject() {
    var id = document.getElementById('rejectId').value;
    var reason = document.getElementById('rejectReason').value;
    $.ajax({
        type: 'POST', url: 'inst_approval_ajax.php',
        data: { action: 'reject', id: id, reason: reason },
        dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            rejectModal.hide();
            Swal.fire({ icon: 'success', text: data.success, timer: 1300, showConfirmButton: false }).then(loadData);
        }
    });
}
</script>
</body>
</html>
