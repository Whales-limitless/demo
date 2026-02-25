<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch suppliers
$suppliers = [];
$result = $connect->query("SELECT * FROM `supplier` ORDER BY `status` ASC, `name` ASC");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $suppliers[] = $r;
    }
}

$currentPage = 'supplier';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supplier Management</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root {
    --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6;
    --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px;
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { position: relative; flex: 1; max-width: 320px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-active { background: #dcfce7; color: #16a34a; }
.badge-inactive { background: #fee2e2; color: #dc2626; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }
@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-truck" style="color:var(--primary);margin-right:8px;"></i>Supplier Management</h1>
        <button class="btn-add" onclick="openCreateModal();">
            <i class="fas fa-plus"></i> Add Supplier
        </button>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search suppliers...">
            </div>
            <div class="item-count" id="itemCount"><?php echo count($suppliers); ?> supplier(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Payment Terms</th>
                        <th>Lead Time</th>
                        <th>Status</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($suppliers) === 0): ?>
                    <tr class="no-results"><td colspan="10"><i class="fas fa-truck" style="font-size:24px;margin-bottom:8px;display:block;"></i>No suppliers found</td></tr>
                    <?php else: ?>
                    <?php foreach ($suppliers as $i => $s): ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($s['code'] ?? '') . ' ' . ($s['name'] ?? '') . ' ' . ($s['contact_person'] ?? '') . ' ' . ($s['phone'] ?? '') . ' ' . ($s['email'] ?? '')
                    )); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($s['code'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['contact_person'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['payment_terms'] ?? ''); ?></td>
                        <td><?php echo ($s['lead_time_days'] ?? 0) > 0 ? $s['lead_time_days'] . ' days' : '-'; ?></td>
                        <td><span class="badge-status <?php echo ($s['status'] ?? '') === 'ACTIVE' ? 'badge-active' : 'badge-inactive'; ?>"><?php echo htmlspecialchars($s['status'] ?? ''); ?></span></td>
                        <td style="white-space:nowrap">
                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo (int)$s['id']; ?>);"><i class="fas fa-pen"></i> Edit</button>
                            <button class="btn-action btn-delete" onclick="deleteSupplier(<?php echo (int)$s['id']; ?>, '<?php echo htmlspecialchars($s['name'] ?? '', ENT_QUOTES); ?>');"><i class="fas fa-trash"></i></button>
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
<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-truck"></i> Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Supplier Code <span class="text-danger">*</span></label>
                        <input type="text" id="fCode" class="form-control" placeholder="e.g. SUP001">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Supplier Name <span class="text-danger">*</span></label>
                        <input type="text" id="fName" class="form-control" placeholder="Company name">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Contact Person</label>
                        <input type="text" id="fContact" class="form-control" placeholder="Contact name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" id="fPhone" class="form-control" placeholder="Phone number">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" id="fEmail" class="form-control" placeholder="Email address">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="fStatus" class="form-select">
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea id="fAddress" class="form-control" rows="2" placeholder="Full address"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Payment Terms</label>
                        <input type="text" id="fPayment" class="form-control" placeholder="e.g. Net 30, COD">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Lead Time (days)</label>
                        <input type="number" id="fLeadTime" class="form-control" placeholder="0" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveSupplier();"><i class="fas fa-check"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var modal = null;
document.addEventListener('DOMContentLoaded', function() {
    modal = new bootstrap.Modal(document.getElementById('supplierModal'));
});

// Search
document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        if (d.indexOf(q) > -1) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('itemCount').textContent = count + ' supplier(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
});

function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('fCode').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('fContact').value = '';
    document.getElementById('fPhone').value = '';
    document.getElementById('fEmail').value = '';
    document.getElementById('fAddress').value = '';
    document.getElementById('fPayment').value = '';
    document.getElementById('fLeadTime').value = '0';
    document.getElementById('fStatus').value = 'ACTIVE';
    document.getElementById('fCode').disabled = false;
}

function openCreateModal() {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-truck"></i> Add Supplier';
    modal.show();
}

function openEditModal(id) {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-truck"></i> Edit Supplier';
    document.getElementById('editId').value = id;
    document.getElementById('fCode').disabled = true;

    $.ajax({
        type: 'POST', url: 'supplier_ajax.php', data: { action: 'get', id: id }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            document.getElementById('fCode').value = data.code || '';
            document.getElementById('fName').value = data.name || '';
            document.getElementById('fContact').value = data.contact_person || '';
            document.getElementById('fPhone').value = data.phone || '';
            document.getElementById('fEmail').value = data.email || '';
            document.getElementById('fAddress').value = data.address || '';
            document.getElementById('fPayment').value = data.payment_terms || '';
            document.getElementById('fLeadTime').value = data.lead_time_days || '0';
            document.getElementById('fStatus').value = data.status || 'ACTIVE';
            modal.show();
        }
    });
}

function saveSupplier() {
    var editId = document.getElementById('editId').value;
    var code = document.getElementById('fCode').value.trim();
    var name = document.getElementById('fName').value.trim();

    if (code === '' || name === '') {
        Swal.fire({ icon: 'warning', text: 'Supplier code and name are required.' });
        return;
    }

    var postData = {
        action: editId ? 'update' : 'create',
        code: code, name: name,
        contact_person: document.getElementById('fContact').value.trim(),
        phone: document.getElementById('fPhone').value.trim(),
        email: document.getElementById('fEmail').value.trim(),
        address: document.getElementById('fAddress').value.trim(),
        payment_terms: document.getElementById('fPayment').value.trim(),
        lead_time_days: document.getElementById('fLeadTime').value,
        status: document.getElementById('fStatus').value
    };
    if (editId) postData.id = editId;

    $.ajax({
        type: 'POST', url: 'supplier_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.success) {
                modal.hide();
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}

function deleteSupplier(id, name) {
    Swal.fire({
        title: 'Delete supplier?',
        text: 'Deactivate "' + name + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Deactivate'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'supplier_ajax.php', data: { action: 'delete', id: id }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                }
            });
        }
    });
}

document.getElementById('supplierModal').addEventListener('shown.bs.modal', function() {
    var el = document.getElementById('fCode');
    if (!el.disabled) el.focus(); else document.getElementById('fName').focus();
});
</script>
</body>
</html>
