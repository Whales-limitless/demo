<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rack Management</title>
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

.page-content { max-width: 1200px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }

.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }

.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }

.rack-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; border-radius: var(--radius); overflow: hidden; }
.rack-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 12px 14px; white-space: nowrap; text-align: left; }
.rack-table thead th:first-child { border-top-left-radius: var(--radius); }
.rack-table thead th:last-child { border-top-right-radius: var(--radius); }
.rack-table tbody td { padding: 11px 14px; vertical-align: middle; border-bottom: 1px solid #f0f1f3; }
.rack-table tbody tr:hover { background: #f9fafb; }
.rack-table tbody tr:last-child td { border-bottom: none; }
.rack-table tbody tr:last-child td:first-child { border-bottom-left-radius: var(--radius); }
.rack-table tbody tr:last-child td:last-child { border-bottom-right-radius: var(--radius); }
.rack-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }

.code-tag { font-family: monospace; background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--text-muted); }
.badge-count { display: inline-block; background: #eff6ff; color: #2563eb; font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 6px; }

.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-products { background: #8b5cf6; }
.btn-products:hover { background: #7c3aed; }
.btn-edit { background: #3b82f6; }
.btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; }
.btn-delete:hover { background: #dc2626; }
.btn-unlink { background: #ef4444; border: none; color: #fff; padding: 3px 10px; border-radius: 5px; font-size: 11px; font-weight: 600; cursor: pointer; }
.btn-unlink:hover { background: #dc2626; }

/* Product linking area */
.product-panel { margin-top: 16px; }
.linked-list { max-height: 300px; overflow-y: auto; }
.linked-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border-bottom: 1px solid #f0f1f3; font-size: 13px; }
.linked-item:last-child { border-bottom: none; }
.linked-item .item-info { display: flex; flex-direction: column; gap: 1px; }
.linked-item .item-name { font-weight: 600; }
.linked-item .item-sub { font-size: 11px; color: var(--text-muted); }

.search-result-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border-bottom: 1px solid #f0f1f3; font-size: 13px; cursor: pointer; transition: background 0.15s; }
.search-result-item:hover { background: #f0fdf4; }
.search-result-item:last-child { border-bottom: none; }
.search-results-box { max-height: 250px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; }

.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

@media (max-width: 768px) {
    .admin-topbar { padding: 0 16px; }
    .admin-topbar .nav-links { display: none; }
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<div class="admin-topbar">
    <div class="brand"><i class="fas fa-tachometer-alt"></i> Admin Panel</div>
    <div class="nav-links">
        <a href="dashboard.php"><i class="fas fa-list-alt"></i> Orders</a>
        <a href="rack.php" class="active"><i class="fas fa-warehouse"></i> Rack</a>
        <a href="user.php"><i class="fas fa-users"></i> Users</a>
    </div>
    <div class="right-section">
        <span class="user-info d-none d-md-inline"><i class="fas fa-user-circle"></i> <?php echo $adminName; ?></span>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-warehouse" style="color:var(--primary);margin-right:8px;"></i>Rack Management</h1>
        <button class="btn-add" onclick="openCreateRack();"><i class="fas fa-plus"></i> Add Rack</button>
    </div>

    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="rack-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Products</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="rackBody">
                    <tr class="no-results"><td colspan="5">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Rack Modal -->
<div class="modal fade" id="rackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rackModalTitle"><i class="fas fa-plus"></i> Add Rack</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rackEditId" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rack Code <span class="text-danger">*</span></label>
                    <input type="text" id="rackCode" class="form-control" placeholder="e.g. R01, A-1">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rack Name</label>
                    <input type="text" id="rackName" class="form-control" placeholder="e.g. Rack 01, Aisle A Row 1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveRack();"><i class="fas fa-check"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Products Modal -->
<div class="modal fade" id="productsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-boxes-stacked"></i> <span id="prodModalRackName">Rack Products</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="prodRackId" value="">

                <!-- Search to add -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Add Product</label>
                    <input type="text" id="productSearch" class="form-control" placeholder="Search by barcode, name, or SKU...">
                    <div class="search-results-box mt-2" id="searchResults" style="display:none;"></div>
                </div>

                <hr>

                <!-- Linked products list -->
                <label class="form-label fw-semibold">Linked Products <span class="badge-count" id="linkedCount">0</span></label>
                <div class="linked-list" id="linkedList">
                    <div style="text-align:center;padding:20px;color:var(--text-muted);">No products linked</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var rackModal, productsModal;
document.addEventListener('DOMContentLoaded', function() {
    rackModal = new bootstrap.Modal(document.getElementById('rackModal'));
    productsModal = new bootstrap.Modal(document.getElementById('productsModal'));
    loadRacks();
});

function loadRacks() {
    $.post('rack_ajax.php', { action: 'list_racks' }, function(data) {
        var body = document.getElementById('rackBody');
        if (!data.racks || data.racks.length === 0) {
            body.innerHTML = '<tr class="no-results"><td colspan="5"><i class="fas fa-warehouse" style="font-size:24px;margin-bottom:8px;display:block;"></i>No racks found</td></tr>';
            return;
        }
        var html = '';
        data.racks.forEach(function(r, i) {
            html += '<tr>' +
                '<td>' + (i+1) + '</td>' +
                '<td><span class="code-tag">' + esc(r.code) + '</span></td>' +
                '<td>' + esc(r.name) + '</td>' +
                '<td><span class="badge-count">' + r.product_count + '</span></td>' +
                '<td style="white-space:nowrap">' +
                    '<button class="btn-action btn-products" onclick="openProducts(' + r.id + ',\'' + esc(r.code) + ' - ' + esc(r.name) + '\');"><i class="fas fa-boxes-stacked"></i> Products</button> ' +
                    '<button class="btn-action btn-edit" onclick="openEditRack(' + r.id + ',\'' + esc(r.code) + '\',\'' + esc(r.name) + '\');"><i class="fas fa-pen"></i> Edit</button> ' +
                    '<button class="btn-action btn-delete" onclick="deleteRack(' + r.id + ',\'' + esc(r.code) + '\');"><i class="fas fa-trash"></i> Delete</button>' +
                '</td></tr>';
        });
        body.innerHTML = html;
    }, 'json');
}

function esc(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
}

function openCreateRack() {
    document.getElementById('rackModalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Rack';
    document.getElementById('rackEditId').value = '';
    document.getElementById('rackCode').value = '';
    document.getElementById('rackCode').disabled = false;
    document.getElementById('rackName').value = '';
    rackModal.show();
}

function openEditRack(id, code, name) {
    document.getElementById('rackModalTitle').innerHTML = '<i class="fas fa-pen"></i> Edit Rack';
    document.getElementById('rackEditId').value = id;
    document.getElementById('rackCode').value = code;
    document.getElementById('rackCode').disabled = false;
    document.getElementById('rackName').value = name;
    rackModal.show();
}

function saveRack() {
    var editId = document.getElementById('rackEditId').value;
    var code = document.getElementById('rackCode').value.trim();
    var name = document.getElementById('rackName').value.trim();

    if (code === '') {
        Swal.fire({ icon: 'warning', text: 'Rack code is required.' });
        return;
    }

    var postData = { action: editId ? 'update_rack' : 'create_rack', code: code, name: name };
    if (editId) postData.id = editId;

    $.post('rack_ajax.php', postData, function(data) {
        if (data.success) {
            rackModal.hide();
            Swal.fire({ icon: 'success', text: data.success, timer: 1200, showConfirmButton: false }).then(function() { loadRacks(); });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.error });
        }
    }, 'json');
}

function deleteRack(id, code) {
    Swal.fire({
        title: 'Delete rack?',
        text: 'Delete "' + code + '" and unlink all its products?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('rack_ajax.php', { action: 'delete_rack', id: id }, function(data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', text: data.success, timer: 1200, showConfirmButton: false }).then(function() { loadRacks(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.error });
                }
            }, 'json');
        }
    });
}

// Products modal
function openProducts(rackId, rackLabel) {
    document.getElementById('prodRackId').value = rackId;
    document.getElementById('prodModalRackName').textContent = rackLabel;
    document.getElementById('productSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchResults').innerHTML = '';
    loadLinkedProducts(rackId);
    productsModal.show();
}

function loadLinkedProducts(rackId) {
    $.post('rack_ajax.php', { action: 'get_rack_products', rack_id: rackId }, function(data) {
        var list = document.getElementById('linkedList');
        var count = document.getElementById('linkedCount');
        if (!data.products || data.products.length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);">No products linked</div>';
            count.textContent = '0';
            return;
        }
        count.textContent = data.products.length;
        var html = '';
        data.products.forEach(function(p) {
            html += '<div class="linked-item">' +
                '<div class="item-info">' +
                    '<span class="item-name">' + esc(p.name || p.barcode) + '</span>' +
                    '<span class="item-sub">' + esc(p.barcode) + (p.stkcode ? ' | SKU: ' + esc(p.stkcode) : '') + '</span>' +
                '</div>' +
                '<button class="btn-unlink" onclick="unlinkProduct(' + p.link_id + ');"><i class="fas fa-times"></i> Unlink</button>' +
            '</div>';
        });
        list.innerHTML = html;
    }, 'json');
}

var searchTimeout;
document.getElementById('productSearch').addEventListener('input', function() {
    var q = this.value.trim();
    var rackId = document.getElementById('prodRackId').value;
    var box = document.getElementById('searchResults');

    clearTimeout(searchTimeout);
    if (q.length < 1) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }

    searchTimeout = setTimeout(function() {
        $.post('rack_ajax.php', { action: 'search_products', q: q, rack_id: rackId }, function(data) {
            if (!data.products || data.products.length === 0) {
                box.innerHTML = '<div style="padding:12px;text-align:center;color:var(--text-muted);font-size:13px;">No products found</div>';
                box.style.display = 'block';
                return;
            }
            var html = '';
            data.products.forEach(function(p) {
                html += '<div class="search-result-item" onclick="linkProduct(\'' + esc(p.barcode) + '\');">' +
                    '<div class="item-info">' +
                        '<span class="item-name">' + esc(p.name) + '</span>' +
                        '<span class="item-sub">' + esc(p.barcode) + (p.stkcode ? ' | SKU: ' + esc(p.stkcode) : '') + '</span>' +
                    '</div>' +
                    '<i class="fas fa-plus" style="color:#22c55e;"></i>' +
                '</div>';
            });
            box.innerHTML = html;
            box.style.display = 'block';
        }, 'json');
    }, 300);
});

function linkProduct(barcode) {
    var rackId = document.getElementById('prodRackId').value;
    $.post('rack_ajax.php', { action: 'link_product', rack_id: rackId, barcode: barcode }, function(data) {
        if (data.success) {
            loadLinkedProducts(rackId);
            loadRacks();
            // Re-trigger search to remove linked item
            var q = document.getElementById('productSearch').value.trim();
            if (q.length > 0) {
                document.getElementById('productSearch').dispatchEvent(new Event('input'));
            }
        } else {
            Swal.fire({ icon: 'error', text: data.error });
        }
    }, 'json');
}

function unlinkProduct(linkId) {
    var rackId = document.getElementById('prodRackId').value;
    $.post('rack_ajax.php', { action: 'unlink_product', link_id: linkId }, function(data) {
        if (data.success) {
            loadLinkedProducts(rackId);
            loadRacks();
        } else {
            Swal.fire({ icon: 'error', text: data.error });
        }
    }, 'json');
}

// Autofocus
document.getElementById('rackModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('rackCode').focus();
});
document.getElementById('productsModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('productSearch').focus();
});
</script>

</body>
</html>
