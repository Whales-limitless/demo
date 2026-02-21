<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch all users
$users = [];
$result = $connect->query("SELECT * FROM `sysfile` ORDER BY `ID` ASC");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $users[] = $r;
    }
}

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

.admin-topbar .nav-links {
    display: flex;
    align-items: center;
    gap: 4px;
}

.admin-topbar .nav-links a {
    color: rgba(255,255,255,0.75);
    text-decoration: none;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all var(--transition);
}

.admin-topbar .nav-links a:hover { background: rgba(255,255,255,0.15); color: #fff; }
.admin-topbar .nav-links a.active { background: rgba(255,255,255,0.2); color: #fff; }

.admin-topbar .right-section {
    display: flex;
    align-items: center;
    gap: 16px;
}

.admin-topbar .user-info { font-size: 13px; opacity: 0.9; }

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

.admin-topbar .btn-logout:hover { background: rgba(255,255,255,0.25); color: #fff; }

.page-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 24px 40px;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.page-header h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 22px;
    font-weight: 700;
    margin: 0;
}

.btn-add {
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
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-add:hover { background: var(--primary-dark); }

.table-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    padding: 20px;
    overflow: hidden;
}

.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 320px;
}

.search-box input {
    width: 100%;
    padding: 9px 14px 9px 36px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    outline: none;
    transition: border-color var(--transition);
}

.search-box input:focus { border-color: var(--primary); }

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 13px;
}

.user-count { font-size: 13px; color: var(--text-muted); }

.users-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.users-table thead th {
    background: var(--text);
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 10px 12px;
    white-space: nowrap;
    text-align: left;
}

.users-table tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.users-table tbody tr:hover { background: #f9fafb; }

.users-table tbody tr.no-results td {
    text-align: center;
    padding: 40px;
    color: var(--text-muted);
}

.badge-type {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-admin { background: #fef2f2; color: #dc2626; }
.badge-staff { background: #eff6ff; color: #2563eb; }
.badge-delivery { background: #fefce8; color: #ca8a04; }

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
    color: #fff;
}

.btn-edit { background: #3b82f6; }
.btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; }
.btn-delete:hover { background: #dc2626; }
.btn-delete:disabled { background: #d1d5db; cursor: not-allowed; }

.code-tag {
    font-family: monospace;
    background: #f3f4f6;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    color: var(--text-muted);
}

.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

@media (max-width: 768px) {
    .admin-topbar { padding: 0 16px; }
    .admin-topbar .nav-links { display: none; }
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<!-- Top Navigation -->
<div class="admin-topbar">
    <div class="brand">
        <i class="fas fa-tachometer-alt"></i>
        Admin Panel
    </div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-list-alt"></i> Orders</a>
        <a href="user.php" class="active"><i class="fas fa-users"></i> Users</a>
    </div>
    <div class="right-section">
        <span class="user-info d-none d-md-inline">
            <i class="fas fa-user-circle"></i> <?php echo $adminName; ?>
        </span>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- Page Content -->
<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-users" style="color:var(--primary);margin-right:8px;"></i>User Management</h1>
        <button class="btn-add" onclick="openCreateModal();">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search users...">
            </div>
            <div class="user-count" id="userCount"><?php echo count($users); ?> user(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="users-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Code</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="usersBody">
                    <?php if (count($users) === 0): ?>
                    <tr class="no-results">
                        <td colspan="6"><i class="fas fa-users-slash" style="font-size:24px;margin-bottom:8px;display:block;"></i>No users found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <?php
                        $typeVal = $u['TYPE'] ?? '';
                        if ($typeVal === 'A') { $typeName = 'Admin'; $badgeClass = 'badge-admin'; }
                        elseif ($typeVal === 'D') { $typeName = 'Delivery'; $badgeClass = 'badge-delivery'; }
                        else { $typeName = 'Staff'; $badgeClass = 'badge-staff'; }
                    ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($u['USERNAME'] ?? '') . ' ' .
                        ($u['USER1'] ?? '') . ' ' .
                        ($u['USER_NAME'] ?? '') . ' ' .
                        $typeName
                    )); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><span class="code-tag"><?php echo htmlspecialchars($u['USERNAME'] ?? ''); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($u['USER1'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['USER_NAME'] ?? ''); ?></td>
                        <td><span class="badge-type <?php echo $badgeClass; ?>"><?php echo $typeName; ?></span></td>
                        <td style="white-space:nowrap">
                            <button type="button" class="btn-action btn-edit" onclick="openEditModal(<?php echo (int)$u['ID']; ?>);">
                                <i class="fas fa-pen"></i> Edit
                            </button>
                            <?php if ($typeVal === 'A'): ?>
                            <button type="button" class="btn-action btn-delete" disabled title="Admin users cannot be deleted">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn-action btn-delete" onclick="deleteUser(<?php echo (int)$u['ID']; ?>, '<?php echo htmlspecialchars($u['USER1'] ?? '', ENT_QUOTES); ?>');">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-plus"></i> Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="mb-3" id="codeGroup" style="display:none;">
                    <label class="form-label fw-semibold">Code</label>
                    <input type="text" id="fCode" class="form-control" disabled style="background:#f3f4f6;font-family:monospace;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="fUsername">Username <span class="text-danger">*</span></label>
                    <input type="text" id="fUsername" class="form-control" placeholder="Login username" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="fPassword">Password <span class="text-danger">*</span></label>
                    <input type="text" id="fPassword" class="form-control" placeholder="Login password" autocomplete="off">
                    <div class="form-text" id="passwordHint" style="display:none;">Leave blank to keep current password.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="fName">Full Name</label>
                    <input type="text" id="fName" class="form-control" placeholder="Display name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="fType">Type <span class="text-danger">*</span></label>
                    <select id="fType" class="form-select">
                        <option value="S">Staff</option>
                        <option value="A">Admin</option>
                        <option value="D">Delivery</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveUser();">
                    <i class="fas fa-check"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var userModal = null;

document.addEventListener('DOMContentLoaded', function() {
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
});

// Search
document.getElementById('searchInput').addEventListener('input', function() {
    var query = this.value.toLowerCase();
    var rows = document.querySelectorAll('#usersBody tr:not(.no-results)');
    var count = 0;

    rows.forEach(function(row) {
        var data = row.getAttribute('data-search') || '';
        if (data.indexOf(query) > -1) {
            row.style.display = '';
            count++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('userCount').textContent = count + ' user(s)';

    var num = 1;
    rows.forEach(function(row) {
        if (row.style.display !== 'none') {
            row.cells[0].textContent = num++;
        }
    });
});

// Open create modal
function openCreateModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add User';
    document.getElementById('editId').value = '';
    document.getElementById('codeGroup').style.display = 'none';
    document.getElementById('fCode').value = '';
    document.getElementById('fUsername').value = '';
    document.getElementById('fUsername').disabled = false;
    document.getElementById('fPassword').value = '';
    document.getElementById('fPassword').placeholder = 'Login password';
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('fName').value = '';
    document.getElementById('fType').value = 'S';
    userModal.show();
}

// Open edit modal
function openEditModal(id) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit User';
    document.getElementById('editId').value = id;
    document.getElementById('fPassword').placeholder = 'Leave blank to keep current';
    document.getElementById('passwordHint').style.display = 'block';

    $.ajax({
        type: 'POST',
        url: 'user_ajax.php',
        data: { action: 'get', id: id },
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                Swal.fire({ icon: 'error', title: 'Error', text: data.error });
                return;
            }
            document.getElementById('codeGroup').style.display = 'block';
            document.getElementById('fCode').value = data.USERNAME || '';
            document.getElementById('fUsername').value = data.USER1 || '';
            document.getElementById('fUsername').disabled = true;
            document.getElementById('fPassword').value = '';
            document.getElementById('fName').value = data.USER_NAME || '';
            document.getElementById('fType').value = data.TYPE || 'S';
            userModal.show();
        }
    });
}

// Save (create or edit)
function saveUser() {
    var editId = document.getElementById('editId').value;
    var username = document.getElementById('fUsername').value.trim();
    var password = document.getElementById('fPassword').value.trim();
    var name = document.getElementById('fName').value.trim();
    var type = document.getElementById('fType').value;

    if (username === '') {
        Swal.fire({ icon: 'warning', text: 'Username is required.' });
        return;
    }

    if (!editId && password === '') {
        Swal.fire({ icon: 'warning', text: 'Password is required for new users.' });
        return;
    }

    var postData = {
        action: editId ? 'update' : 'create',
        username: username,
        password: password,
        name: name,
        type: type
    };

    if (editId) {
        postData.id = editId;
    }

    $.ajax({
        type: 'POST',
        url: 'user_ajax.php',
        data: postData,
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                userModal.hide();
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                    location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}

// Delete
function deleteUser(id, username) {
    Swal.fire({
        title: 'Delete user?',
        text: 'Delete "' + username + '"? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: 'user_ajax.php',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Something went wrong.' });
                    }
                }
            });
        }
    });
}

// Modal autofocus
document.getElementById('userModal').addEventListener('shown.bs.modal', function() {
    var el = document.getElementById('fUsername');
    if (!el.disabled) el.focus();
    else document.getElementById('fPassword').focus();
});
</script>

</body>
</html>
