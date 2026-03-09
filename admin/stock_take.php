<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch stock take sessions
$sessions = [];
$result = $connect->query("SELECT * FROM `stock_take` ORDER BY `id` DESC");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $sessions[] = $r;
    }
}

// Fetch category groups for filter
$catGroups = [];
$catResult = $connect->query("SELECT `id`, `ccode`, `cat_name` FROM `cat_group` WHERE COALESCE(`status`,'ACTIVE')='ACTIVE' ORDER BY `sort_no` ASC, `cat_name` ASC");
if ($catResult) {
    while ($r = $catResult->fetch_assoc()) {
        $catGroups[] = $r;
    }
}

$currentPage = 'stock_take';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Take</title>
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
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { position: relative; flex: 1; max-width: 320px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-st { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-OPEN { background: #dbeafe; color: #2563eb; }
.badge-IN_PROGRESS { background: #fef3c7; color: #d97706; }
.badge-COMPLETED { background: #dcfce7; color: #16a34a; }
.badge-DRAFT { background: #dbeafe; color: #2563eb; }
.badge-SUBMITTED { background: #fef3c7; color: #d97706; }
.badge-APPROVED { background: #dcfce7; color: #16a34a; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; text-decoration: none; }
.btn-view { background: #6b7280; } .btn-view:hover { background: #4b5563; color: #fff; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }
@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:8px;"></i>Stock Take</h1>
        <button class="btn-add" onclick="openCreateModal();"><i class="fas fa-plus"></i> New Stock Take</button>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search sessions...">
            </div>
            <div class="item-count" id="itemCount"><?php echo count($sessions); ?> session(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Session Code</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Sub-Category Filter</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($sessions) === 0): ?>
                    <tr class="no-results"><td colspan="9"><i class="fas fa-clipboard-check" style="font-size:24px;margin-bottom:8px;display:block;"></i>No stock take sessions</td></tr>
                    <?php else: ?>
                    <?php foreach ($sessions as $i => $s): ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($s['session_code'] ?? '') . ' ' . ($s['description'] ?? '') . ' ' . ($s['status'] ?? '') . ' ' . ($s['created_by'] ?? '')
                    )); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($s['session_code'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['description'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['type'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['filter_sub_cat'] ?? ($s['filter_cat'] ?? 'All')); ?></td>
                        <td><span class="badge-st badge-<?php echo htmlspecialchars($s['status'] ?? 'OPEN'); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($s['status'] ?? '')); ?></span></td>
                        <td><?php echo htmlspecialchars($s['created_by'] ?? ''); ?></td>
                        <td><?php echo !empty($s['created_at']) ? date('d/m/Y H:i', strtotime($s['created_at'])) : ''; ?></td>
                        <td style="white-space:nowrap">
                            <a href="stock_take_detail.php?id=<?php echo (int)$s['id']; ?>" class="btn-action btn-view"><i class="fas fa-eye"></i> Open</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> New Stock Take Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" id="fDesc" class="form-control" placeholder="e.g. Monthly Full Count - Feb 2026">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Type</label>
                    <select id="fType" class="form-select">
                        <option value="FULL">Full Inventory</option>
                        <option value="PARTIAL">Partial (by Sub-Category)</option>
                    </select>
                </div>
                <div class="mb-3" id="catGroupRow" style="display:none;">
                    <label class="form-label fw-semibold">Category Group</label>
                    <select id="fCatGroup" class="form-select" onchange="loadSubCatFilter();">
                        <option value="">-- Select Category Group --</option>
                        <?php foreach ($catGroups as $cg): ?>
                        <option value="<?php echo (int)$cg['id']; ?>" data-ccode="<?php echo htmlspecialchars($cg['ccode']); ?>"><?php echo htmlspecialchars($cg['cat_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3" id="subCatRow" style="display:none;">
                    <label class="form-label fw-semibold">Sub-Category Filter</label>
                    <select id="fSubCat" class="form-select" onchange="loadProducts();">
                        <option value="">-- Select Sub-Category --</option>
                    </select>
                </div>
                <div class="mb-3" id="productSelectRow" style="display:none;">
                    <label class="form-label fw-semibold">Select Products</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="selectAllProducts" checked onchange="toggleAllProducts();">
                        <label class="form-check-label fw-semibold" for="selectAllProducts">Select All</label>
                    </div>
                    <div id="productListContainer" style="max-height:300px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;padding:8px;">
                        <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">Select a sub-category to load products</div>
                    </div>
                    <div class="mt-1" style="font-size:12px;color:var(--text-muted);">
                        <span id="selectedCount">0</span> product(s) selected
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="createSession();"><i class="fas fa-check"></i> Create Session</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var createModalObj = null;
document.addEventListener('DOMContentLoaded', function() {
    createModalObj = new bootstrap.Modal(document.getElementById('createModal'));
});

var allProducts = [];

document.getElementById('fType').addEventListener('change', function() {
    var isPartial = this.value === 'PARTIAL';
    document.getElementById('catGroupRow').style.display = isPartial ? 'block' : 'none';
    document.getElementById('subCatRow').style.display = isPartial ? 'block' : 'none';
    document.getElementById('productSelectRow').style.display = isPartial ? 'block' : 'none';
    if (!isPartial) {
        document.getElementById('fCatGroup').value = '';
        document.getElementById('fSubCat').innerHTML = '<option value="">-- Select Sub-Category --</option>';
        allProducts = [];
        document.getElementById('productListContainer').innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">Select a sub-category to load products</div>';
    }
});

function loadSubCatFilter() {
    var catGroupId = document.getElementById('fCatGroup').value;
    var sel = document.getElementById('fSubCat');
    sel.innerHTML = '<option value="">-- Select Sub-Category --</option>';
    allProducts = [];
    document.getElementById('productListContainer').innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">Select a sub-category to load products</div>';
    updateSelectedCount();
    if (!catGroupId) return;

    $.post('product_ajax.php', { action: 'subcat_list', category_id: catGroupId }, function(subs) {
        (subs || []).forEach(function(s) {
            sel.innerHTML += '<option value="' + escHtml(s.name) + '">' + escHtml(s.name) + '</option>';
        });
    }, 'json');
}

function loadProducts() {
    var subCat = document.getElementById('fSubCat').value;
    var container = document.getElementById('productListContainer');
    allProducts = [];
    if (!subCat) {
        container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">Select a sub-category to load products</div>';
        updateSelectedCount();
        return;
    }
    container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';

    $.post('stock_take_ajax.php', { action: 'get_products', sub_cat: subCat }, function(data) {
        if (data.error) { container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);">' + escHtml(data.error) + '</div>'; return; }
        allProducts = data.products || [];
        if (allProducts.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">No products found in this sub-category</div>';
            updateSelectedCount();
            return;
        }
        var html = '';
        allProducts.forEach(function(p, i) {
            var lastDate = p.last_stock_take ? p.last_stock_take : 'Never';
            html += '<div class="form-check py-1 px-2" style="border-bottom:1px solid #f3f4f6;">' +
                '<input class="form-check-input product-check" type="checkbox" value="' + escHtml(p.barcode) + '" id="prod_' + i + '" checked onchange="updateSelectedCount();">' +
                '<label class="form-check-label w-100" for="prod_' + i + '" style="font-size:12px;cursor:pointer;">' +
                '<div class="d-flex justify-content-between align-items-center">' +
                '<div><strong>' + escHtml(p.barcode) + '</strong> - ' + escHtml(p.name) + '</div>' +
                '<div style="font-size:11px;color:' + (p.last_stock_take ? '#16a34a' : '#9ca3af') + ';white-space:nowrap;margin-left:8px;">' + escHtml(lastDate) + '</div>' +
                '</div>' +
                '</label></div>';
        });
        container.innerHTML = html;
        document.getElementById('selectAllProducts').checked = true;
        updateSelectedCount();
    }, 'json');
}

function toggleAllProducts() {
    var checked = document.getElementById('selectAllProducts').checked;
    document.querySelectorAll('.product-check').forEach(function(cb) { cb.checked = checked; });
    updateSelectedCount();
}

function updateSelectedCount() {
    var count = document.querySelectorAll('.product-check:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s || ''));
    return d.innerHTML;
}

document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        if (d.indexOf(q) > -1) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('itemCount').textContent = count + ' session(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
});

function openCreateModal() {
    document.getElementById('fDesc').value = '';
    document.getElementById('fType').value = 'FULL';
    document.getElementById('fCatGroup').value = '';
    document.getElementById('fSubCat').innerHTML = '<option value="">-- Select Sub-Category --</option>';
    document.getElementById('catGroupRow').style.display = 'none';
    document.getElementById('subCatRow').style.display = 'none';
    createModalObj.show();
}

function createSession() {
    var desc = document.getElementById('fDesc').value.trim();
    var type = document.getElementById('fType').value;
    var subCat = document.getElementById('fSubCat').value;
    var catGroupSel = document.getElementById('fCatGroup');
    var catGroupName = catGroupSel.selectedOptions[0] ? catGroupSel.selectedOptions[0].textContent.trim() : '';
    if (catGroupSel.value === '') catGroupName = '';

    // Collect selected product barcodes (for PARTIAL type)
    var selectedBarcodes = [];
    if (type === 'PARTIAL') {
        document.querySelectorAll('.product-check:checked').forEach(function(cb) {
            selectedBarcodes.push(cb.value);
        });
        if (selectedBarcodes.length === 0 && subCat !== '') {
            Swal.fire({ icon: 'warning', text: 'Please select at least one product.' });
            return;
        }
    }

    $.ajax({
        type: 'POST', url: 'stock_take_ajax.php',
        contentType: 'application/json',
        data: JSON.stringify({ action: 'create', description: desc, type: type, filter_cat: catGroupName, filter_sub_cat: subCat, products: selectedBarcodes }),
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                createModalObj.hide();
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                    if (data.session_id) window.location.href = 'stock_take_detail.php?id=' + data.session_id;
                    else location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}
</script>
</body>
</html>
