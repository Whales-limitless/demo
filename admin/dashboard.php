<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

// Prepare orderlist2 summary table for DataTable
$connect->query("TRUNCATE TABLE `orderlist2`");
$connect->query("INSERT INTO `orderlist2` (SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUMQTY) SELECT SALNUM,ACCODE,NAME,ADMINRMK,TXTTO,SDATE,TTIME,SUM(QTY) AS SUMQTY FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND BARCODE <> 'PT' GROUP BY SALNUM,ACCODE ORDER BY SALNUM DESC");
$connect->query("UPDATE orderlist2 AS b INNER JOIN MEMBER AS g ON b.ACCODE = g.ACCODE SET b.HP = g.HP");

// Count new orders (sound not yet acknowledged)
$newOrderCount = 0;
$query56 = $connect->query("SELECT COUNT(DISTINCT SALNUM) as cnt FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0'");
if ($query56 && $row = $query56->fetch_assoc()) {
    $newOrderCount = (int)$row['cnt'];
}

// Handle notification acknowledge
if (isset($_POST['noted'])) {
    $connect->query("UPDATE `orderlist` SET SOUND = '1' WHERE SOUND = '0'");
    header("Location: dashboard.php");
    exit;
}

// Check if there are unacknowledged orders for sound
$hasNewSound = false;
$querySnd = $connect->query("SELECT 1 FROM `orderlist` WHERE STATUS != 'DONE' AND STATUS != 'DELETED' AND SOUND = '0' LIMIT 1");
if ($querySnd && $querySnd->num_rows > 0) {
    $hasNewSound = true;
}

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Admin Dashboard - Live View</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; }

:root {
    --primary: #C8102E;
    --primary-dark: #a00d24;
    --surface: #ffffff;
    --bg: #f3f4f6;
    --text: #1a1a1a;
    --text-muted: #6b7280;
    --radius: 12px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
    --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    -webkit-font-smoothing: antialiased;
    margin: 0;
}

/* Top Navbar */
.admin-topbar {
    background: var(--primary);
    color: #fff;
    padding: 0 24px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(200,16,46,0.3);
}

.admin-topbar .brand {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-topbar .brand i { font-size: 20px; }

.admin-topbar .right-section {
    display: flex;
    align-items: center;
    gap: 16px;
}

.admin-topbar .user-info {
    font-size: 13px;
    opacity: 0.9;
}

.admin-topbar .btn-logout {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: none;
    padding: 7px 16px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background var(--transition);
}

.admin-topbar .btn-logout:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

/* Dashboard Content */
.dashboard-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px 24px 40px;
}

/* Status Bar */
.status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
}

.live-dot {
    width: 10px;
    height: 10px;
    background: #22c55e;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.4); }
    50% { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
}

.countdown-label {
    color: var(--text-muted);
    font-size: 13px;
}

.notification-btn {
    position: relative;
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 9px 20px;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
}

.notification-btn:hover { background: var(--primary-dark); }

.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
}

/* Table Card */
.table-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    padding: 20px;
    overflow: hidden;
}

.table-card .table { font-size: 13px; margin-bottom: 0; }
.table-card .table thead th {
    background: var(--text);
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border: none;
    padding: 10px 12px;
    white-space: nowrap;
}

.table-card .table tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.table-card .table tbody tr:hover { background: #f9fafb; }

/* Action Buttons */
.btn-action {
    padding: 5px 12px;
    border: none;
    border-radius: 6px;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition);
    display: inline-block;
    margin: 1px;
    text-decoration: none;
    color: #fff;
}

.btn-view { background: #6b7280; }
.btn-view:hover { background: #4b5563; color: #fff; }
.btn-done { background: #3b82f6; }
.btn-done:hover { background: #2563eb; }
.btn-success-action { background: #22c55e; }
.btn-success-action:hover { background: #16a34a; }
.btn-delete { background: #ef4444; }
.btn-delete:hover { background: #dc2626; }

/* Modal */
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* DataTable overrides */
.dataTables_wrapper .dataTables_filter input {
    border-radius: 8px;
    border: 1px solid #d1d5db;
    padding: 6px 12px;
    font-family: 'DM Sans', sans-serif;
}

div.dt-buttons .btn { font-size: 12px; border-radius: 6px; }

/* Audio hidden */
audio { display: none; }

@media (max-width: 768px) {
    .admin-topbar { padding: 0 16px; }
    .dashboard-content { padding: 16px; }
    .status-bar { flex-direction: column; align-items: flex-start; }
    .table-card { padding: 12px; }
}
</style>
</head>
<body>

<!-- Top Navigation -->
<div class="admin-topbar">
    <div class="brand">
        <i class="fas fa-tachometer-alt"></i>
        Admin Dashboard
    </div>
    <div class="right-section">
        <span class="user-info d-none d-md-inline">
            <i class="fas fa-user-circle"></i> <?php echo $adminName; ?>
        </span>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- Dashboard Content -->
<div class="dashboard-content">

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="live-indicator">
            <span class="live-dot"></span>
            <span>Live View</span>
            <span class="countdown-label">Refresh in <strong id="seconds">30</strong>s</span>
        </div>

        <form method="POST" action="" style="margin:0;">
            <button type="submit" name="noted" class="notification-btn">
                <i class="fas fa-bell"></i> Notification
                <?php if ($newOrderCount > 0): ?>
                <span class="notification-badge"><?php echo $newOrderCount; ?></span>
                <?php endif; ?>
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table id="ordersTable" class="table table-hover nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Order No</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Qty</th>
                        <th>To / Remark</th>
                        <th>Admin Remark</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Edit/Success Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Update Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="remark">Remark</label>
                    <input type="text" id="remark" class="form-control" placeholder="Enter remark">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="rowtransno">Transaction No</label>
                    <input type="text" id="rowtransno" class="form-control" placeholder="Enter transaction number">
                </div>
                <input type="hidden" id="pid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="successbtn();">
                    <i class="fas fa-check"></i> Save & Mark Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Sound -->
<?php if ($hasNewSound): ?>
<audio id="notifSound" autoplay>
    <source src="sound/Melody-notification-sound.mp3" type="audio/mpeg">
</audio>
<?php endif; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Countdown timer
var totalSeconds = 30;
var secondsLabel = document.getElementById("seconds");

setInterval(function() {
    totalSeconds--;
    if (totalSeconds < 0) totalSeconds = 30;
    secondsLabel.textContent = totalSeconds < 10 ? '0' + totalSeconds : totalSeconds;
}, 1000);

// Auto-refresh page every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);

// DataTable initialization
$(document).ready(function() {
    var t = $('#ordersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "server_processing.php"
        },
        columnDefs: [
            {
                searchable: false,
                orderable: false,
                targets: 0
            },
            {
                searchable: false,
                orderable: false,
                targets: 9,
                render: function(data, type, row, meta) {
                    var id = row[3]; // SALNUM
                    if (type === 'display') {
                        return '<div style="white-space:nowrap">' +
                            '<a href="order_detail.php?salnum=' + id + '" class="btn-action btn-view"><i class="fas fa-eye"></i> View</a> ' +
                            '<button type="button" onclick="donebtn(\'' + id + '\');" class="btn-action btn-done"><i class="fas fa-check"></i> Done</button> ' +
                            '<button type="button" onclick="editbtn(\'' + id + '\');" class="btn-action btn-success-action"><i class="fas fa-dollar-sign"></i> Success</button> ' +
                            '<button type="button" onclick="deletebtn(\'' + id + '\');" class="btn-action btn-delete"><i class="fas fa-trash"></i> Delete</button>' +
                            '</div>';
                    }
                    return data;
                }
            }
        ],
        order: [[0, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                className: 'btn btn-sm btn-outline-secondary',
                exportOptions: { columns: [0,1,2,3,4,5,6,7,8] }
            },
            {
                extend: 'csv',
                className: 'btn btn-sm btn-outline-secondary',
                exportOptions: { columns: [0,1,2,3,4,5,6,7,8] }
            },
            {
                extend: 'excel',
                className: 'btn btn-sm btn-outline-secondary',
                exportOptions: { columns: [0,1,2,3,4,5,6,7,8] }
            },
            {
                extend: 'pdf',
                className: 'btn btn-sm btn-outline-secondary',
                exportOptions: { columns: [0,1,2,3,4,5,6,7,8] }
            },
            {
                extend: 'print',
                className: 'btn btn-sm btn-outline-secondary',
                title: 'Order List',
                exportOptions: { columns: [0,1,2,3,4,5,6,7,8] },
                customize: function(doc) {
                    $(doc.document.body).find('h1').css('font-size', '14pt').css('text-align', 'left');
                }
            },
            {
                extend: 'colvis',
                className: 'btn btn-sm btn-outline-secondary'
            }
        ],
        pageLength: 100,
        stateSave: true
    });

    // Row numbering
    t.on('order.dt search.dt', function() {
        var i = 1;
        t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function() {
            this.data(i++);
        });
    }).draw();
});

// Edit/Success modal
function editbtn(id) {
    $('#editModal').modal('show');
    $.ajax({
        type: 'POST',
        url: 'admin_ajax.php',
        data: { id: id, action: "detail" },
        success: function(data) {
            var parts = data.split("|");
            $('#remark').val(parts[0]);
            $('#rowtransno').val(parts[1]);
            $('#pid').val(id);
        }
    });
}

// Autofocus modal
$('#editModal').on('shown.bs.modal', function() {
    $('#remark').trigger('focus');
});

// Enter key navigation in modal
$(document).ready(function() {
    $('#remark').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#rowtransno').focus();
        }
    });
    $('#rowtransno').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            successbtn();
        }
    });
});

// Done button
function donebtn(id) {
    Swal.fire({
        title: 'Mark as Done?',
        text: 'This order will be marked as completed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Done'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: 'admin_ajax.php',
                data: { id: id, action: "done" },
                success: function(data) {
                    if (data.trim() === 'Saved.') {
                        Swal.fire({ icon: 'success', text: 'Marked as Done', timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data });
                    }
                }
            });
        }
    });
}

// Delete button
function deletebtn(id) {
    Swal.fire({
        title: 'Delete this order?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: 'admin_ajax.php',
                data: { id: id, action: "delete" },
                success: function(data) {
                    if (data.trim() === 'Deleted.') {
                        Swal.fire({ icon: 'success', text: 'Order Deleted', timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data });
                    }
                }
            });
        }
    });
}

// Success button (save remark + transno)
function successbtn() {
    var remark = document.getElementById("remark").value;
    var rowtransno = document.getElementById("rowtransno").value;
    var pid = document.getElementById("pid").value;

    $.ajax({
        type: 'POST',
        url: 'admin_ajax.php',
        data: { remark: remark, rowtransno: rowtransno, pid: pid, action: "success" },
        success: function(value) {
            if (value.trim() === "Saved.") {
                Swal.fire({ icon: 'success', text: 'Payment Saved', timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: value });
            }
        }
    });
}
</script>

</body>
</html>
