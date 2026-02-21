<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch all products
$products = [];
$result = $connect->query("SELECT id, barcode, code, name, cat, sub_cat, oriprice, disprice, cost, COALESCE(qoh, 0) AS qoh, uom, rack, min_qty, max_qty, checked FROM PRODUCTS ORDER BY checked DESC, name ASC");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $products[] = $r;
    }
}

// Fetch distinct categories for filter
$categories = [];
$catResult = $connect->query("SELECT DISTINCT `cat` FROM `PRODUCTS` WHERE `cat` IS NOT NULL AND `cat` != '' ORDER BY `cat` ASC");
if ($catResult) {
    while ($r = $catResult->fetch_assoc()) {
        $categories[] = $r['cat'];
    }
}

// Fetch distinct racks for dropdown
$racks = [];
$rackResult = $connect->query("SELECT DISTINCT `rack` FROM `PRODUCTS` WHERE `rack` IS NOT NULL AND `rack` != '' ORDER BY `rack` ASC");
if ($rackResult) {
    while ($r = $rackResult->fetch_assoc()) {
        $racks[] = $r['rack'];
    }
}

$currentPage = 'product';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Management</title>
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
.admin-topbar { background: var(--primary); color: #fff; padding: 0 24px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
.admin-topbar .brand { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.admin-topbar .brand i { font-size: 20px; }
.admin-topbar .nav-links { display: flex; align-items: center; gap: 4px; }
.admin-topbar .nav-links a { color: rgba(255,255,255,0.75); text-decoration: none; padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; transition: all var(--transition); }
.admin-topbar .nav-links a:hover { background: rgba(255,255,255,0.15); color: #fff; }
.admin-topbar .nav-links a.active { background: rgba(255,255,255,0.2); color: #fff; }
.admin-topbar .right-section { display: flex; align-items: center; gap: 16px; }
.admin-topbar .user-info { font-size: 13px; opacity: 0.9; }
.admin-topbar .btn-logout { background: rgba(255,255,255,0.15); color: #fff; border: none; padding: 7px 16px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background var(--transition); }
.admin-topbar .btn-logout:hover { background: rgba(255,255,255,0.25); color: #fff; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.toolbar-filters { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1; }
.search-box { position: relative; flex: 1; max-width: 320px; min-width: 180px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.filter-select { padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; background: #fff; transition: border-color var(--transition); min-width: 140px; }
.filter-select:focus { border-color: var(--primary); }
.item-count { font-size: 13px; color: var(--text-muted); white-space: nowrap; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-active { background: #dcfce7; color: #16a34a; }
.badge-inactive { background: #fee2e2; color: #dc2626; }
.badge-low { background: #fef3c7; color: #d97706; display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; margin-left: 4px; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; }
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
    .toolbar-filters { flex-direction: column; align-items: stretch; }
    .filter-select { width: 100%; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-boxes-stacked" style="color:var(--primary);margin-right:8px;"></i>Product Management</h1>
        <button class="btn-add" onclick="openCreateModal();">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="toolbar-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search products..." oninput="filterTable();">
                </div>
                <select id="filterCategory" class="filter-select" onchange="filterTable();">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterStatus" class="filter-select" onchange="filterTable();">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="item-count" id="itemCount"><?php echo count($products); ?> product(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Cost</th>
                        <th>Price</th>
                        <th>QOH</th>
                        <th>Min Qty</th>
                        <th>Rack</th>
                        <th>Status</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($products) === 0): ?>
                    <tr class="no-results"><td colspan="11"><i class="fas fa-boxes-stacked" style="font-size:24px;margin-bottom:8px;display:block;"></i>No products found</td></tr>
                    <?php else: ?>
                    <?php foreach ($products as $i => $p): ?>
                    <?php
                        $isActive = ($p['checked'] ?? 'Y') === 'Y';
                        $qoh = floatval($p['qoh'] ?? 0);
                        $minQty = intval($p['min_qty'] ?? 0);
                        $isLowStock = ($qoh <= $minQty && $isActive);
                    ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($p['barcode'] ?? '') . ' ' . ($p['code'] ?? '') . ' ' . ($p['name'] ?? '') . ' ' . ($p['cat'] ?? '') . ' ' . ($p['sub_cat'] ?? '') . ' ' . ($p['rack'] ?? '')
                    )); ?>"
                    data-cat="<?php echo htmlspecialchars($p['cat'] ?? ''); ?>"
                    data-status="<?php echo $isActive ? 'active' : 'inactive'; ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($p['barcode'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($p['cat'] ?? ''); ?></td>
                        <td><?php echo number_format(floatval($p['cost'] ?? 0), 2); ?></td>
                        <td><?php echo number_format(floatval($p['disprice'] ?? $p['oriprice'] ?? 0), 2); ?></td>
                        <td>
                            <?php echo number_format($qoh, 0); ?>
                            <?php if ($isLowStock): ?>
                            <span class="badge-low">Low</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo intval($p['min_qty'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars($p['rack'] ?? ''); ?></td>
                        <td><span class="badge-status <?php echo $isActive ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $isActive ? 'Active' : 'Inactive'; ?></span></td>
                        <td style="white-space:nowrap">
                            <button class="btn-action btn-edit" onclick="openEditModal(<?php echo (int)$p['id']; ?>);"><i class="fas fa-pen"></i> Edit</button>
                            <?php if ($isActive): ?>
                            <button class="btn-action btn-delete" onclick="deactivateProduct(<?php echo (int)$p['id']; ?>, '<?php echo htmlspecialchars($p['name'] ?? '', ENT_QUOTES); ?>');"><i class="fas fa-ban"></i></button>
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
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-box"></i> Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Barcode <span class="text-danger">*</span></label>
                        <input type="text" id="fBarcode" class="form-control" placeholder="Product barcode">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Product Code</label>
                        <input type="text" id="fCode" class="form-control" placeholder="Product code">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" id="fName" class="form-control" placeholder="Product name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea id="fDescription" class="form-control" rows="2" placeholder="Product description"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <input type="text" id="fCat" class="form-control" list="catList" placeholder="Category">
                        <datalist id="catList">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Sub Category</label>
                        <input type="text" id="fSubCat" class="form-control" placeholder="Sub category">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">UOM</label>
                        <input type="text" id="fUom" class="form-control" placeholder="e.g. PCS, KG, BOX">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Cost Price</label>
                        <input type="number" id="fCost" class="form-control" step="0.01" min="0" placeholder="0.00" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Original Price</label>
                        <input type="number" id="fOriPrice" class="form-control" step="0.01" min="0" placeholder="0.00" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Discount Price</label>
                        <input type="number" id="fDisPrice" class="form-control" step="0.01" min="0" placeholder="0.00" value="0">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">QOH</label>
                        <input type="number" id="fQoh" class="form-control" min="0" placeholder="0" value="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Min Qty (Reorder)</label>
                        <input type="number" id="fMinQty" class="form-control" step="1" min="0" placeholder="0" value="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Max Qty</label>
                        <input type="number" id="fMaxQty" class="form-control" step="1" min="0" placeholder="0" value="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Rack Location</label>
                        <input type="text" id="fRack" class="form-control" list="rackList" placeholder="Rack">
                        <datalist id="rackList">
                            <?php foreach ($racks as $rack): ?>
                            <option value="<?php echo htmlspecialchars($rack); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="fChecked" class="form-select">
                            <option value="Y">Active</option>
                            <option value="N">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveProduct();"><i class="fas fa-check"></i> Save</button>
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
    modal = new bootstrap.Modal(document.getElementById('productModal'));
});

// Filter table by search text, category, and status
function filterTable() {
    var q = document.getElementById('searchInput').value.toLowerCase();
    var catFilter = document.getElementById('filterCategory').value;
    var statusFilter = document.getElementById('filterStatus').value;
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;

    rows.forEach(function(row) {
        var search = row.getAttribute('data-search') || '';
        var cat = row.getAttribute('data-cat') || '';
        var status = row.getAttribute('data-status') || '';

        var matchSearch = (q === '' || search.indexOf(q) > -1);
        var matchCat = (catFilter === '' || cat === catFilter);
        var matchStatus = (statusFilter === '' || status === statusFilter);

        if (matchSearch && matchCat && matchStatus) {
            row.style.display = '';
            count++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('itemCount').textContent = count + ' product(s)';

    // Renumber visible rows
    var num = 1;
    rows.forEach(function(row) {
        if (row.style.display !== 'none') {
            row.cells[0].textContent = num++;
        }
    });
}

// Clear all form fields
function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('fBarcode').value = '';
    document.getElementById('fCode').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('fDescription').value = '';
    document.getElementById('fCat').value = '';
    document.getElementById('fSubCat').value = '';
    document.getElementById('fUom').value = '';
    document.getElementById('fCost').value = '0';
    document.getElementById('fOriPrice').value = '0';
    document.getElementById('fDisPrice').value = '0';
    document.getElementById('fQoh').value = '0';
    document.getElementById('fMinQty').value = '0';
    document.getElementById('fMaxQty').value = '0';
    document.getElementById('fRack').value = '';
    document.getElementById('fChecked').value = 'Y';
    document.getElementById('fBarcode').disabled = false;
}

// Open create modal
function openCreateModal() {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box"></i> Add Product';
    modal.show();
}

// Open edit modal with AJAX GET
function openEditModal(id) {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box"></i> Edit Product';
    document.getElementById('editId').value = id;
    document.getElementById('fBarcode').disabled = true;

    $.ajax({
        type: 'POST', url: 'product_ajax.php', data: { action: 'get', id: id }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            document.getElementById('fBarcode').value = data.barcode || '';
            document.getElementById('fCode').value = data.code || '';
            document.getElementById('fName').value = data.name || '';
            document.getElementById('fDescription').value = data.description || '';
            document.getElementById('fCat').value = data.cat || '';
            document.getElementById('fSubCat').value = data.sub_cat || '';
            document.getElementById('fUom').value = data.uom || '';
            document.getElementById('fCost').value = data.cost || '0';
            document.getElementById('fOriPrice').value = data.oriprice || '0';
            document.getElementById('fDisPrice').value = data.disprice || '0';
            document.getElementById('fQoh').value = data.qoh || '0';
            document.getElementById('fMinQty').value = data.min_qty || '0';
            document.getElementById('fMaxQty').value = data.max_qty || '0';
            document.getElementById('fRack').value = data.rack || '';
            document.getElementById('fChecked').value = data.checked || 'Y';
            modal.show();
        }
    });
}

// Save product (create or update)
function saveProduct() {
    var editId = document.getElementById('editId').value;
    var barcode = document.getElementById('fBarcode').value.trim();
    var name = document.getElementById('fName').value.trim();

    if (barcode === '' || name === '') {
        Swal.fire({ icon: 'warning', text: 'Barcode and product name are required.' });
        return;
    }

    var postData = {
        action: editId ? 'update' : 'create',
        barcode: barcode,
        code: document.getElementById('fCode').value.trim(),
        name: name,
        description: document.getElementById('fDescription').value.trim(),
        cat: document.getElementById('fCat').value.trim(),
        sub_cat: document.getElementById('fSubCat').value.trim(),
        uom: document.getElementById('fUom').value.trim(),
        cost: document.getElementById('fCost').value,
        oriprice: document.getElementById('fOriPrice').value,
        disprice: document.getElementById('fDisPrice').value,
        qoh: document.getElementById('fQoh').value,
        min_qty: document.getElementById('fMinQty').value,
        max_qty: document.getElementById('fMaxQty').value,
        rack: document.getElementById('fRack').value.trim(),
        checked: document.getElementById('fChecked').value
    };
    if (editId) postData.id = editId;

    $.ajax({
        type: 'POST', url: 'product_ajax.php', data: postData, dataType: 'json',
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

// Deactivate product with SweetAlert confirmation
function deactivateProduct(id, name) {
    Swal.fire({
        title: 'Deactivate product?',
        text: 'Deactivate "' + name + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Deactivate'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'product_ajax.php', data: { action: 'delete', id: id }, dataType: 'json',
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

// Modal autofocus
document.getElementById('productModal').addEventListener('shown.bs.modal', function() {
    var el = document.getElementById('fBarcode');
    if (!el.disabled) el.focus(); else document.getElementById('fName').focus();
});
</script>
</body>
</html>
