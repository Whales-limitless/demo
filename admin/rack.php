<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'rack';
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
.badge-count { background: #dbeafe; color: #2563eb; display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; }
.btn-view { background: #16a34a; } .btn-view:hover { background: #15803d; }
.btn-activate { background: #16a34a; } .btn-activate:hover { background: #15803d; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* Pagination */
.pagination-wrap { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; flex-wrap: wrap; gap: 8px; }
.pagination-info { font-size: 13px; color: var(--text-muted); }
.pagination-btns { display: flex; gap: 4px; }
.pagination-btns button { padding: 6px 12px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); color: var(--text); }
.pagination-btns button:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
.pagination-btns button.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.pagination-btns button:disabled { opacity: 0.4; cursor: default; }

/* Product list in rack detail */
.product-list-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; margin-top: 20px; }
.product-list-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.product-list-header h2 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
.product-list-header h2 i { color: var(--primary); }

/* Assign search */
.assign-search-box { position: relative; margin-bottom: 16px; }
.assign-search-box input { width: 100%; padding: 10px 14px 10px 38px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.assign-search-box input:focus { border-color: var(--primary); }
.assign-search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; }
.assign-results { max-height: 320px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; }
.assign-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #f3f4f6; transition: background var(--transition); }
.assign-item:last-child { border-bottom: none; }
.assign-item:hover { background: #f9fafb; }
.assign-item .item-info { flex: 1; min-width: 0; }
.assign-item .item-barcode { font-size: 11px; color: var(--text-muted); font-weight: 600; }
.assign-item .item-name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.assign-item .item-qoh { font-size: 11px; color: var(--text-muted); }
.assign-item .btn-assign-item { background: var(--primary); color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 11px; font-weight: 600; cursor: pointer; transition: background var(--transition); white-space: nowrap; margin-left: 12px; }
.assign-item .btn-assign-item:hover { background: var(--primary-dark); }
.no-results-msg { text-align: center; padding: 24px; color: var(--text-muted); font-size: 13px; }

/* Matched products */
.matched-products { margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px; }
.matched-tag { display: inline-flex; align-items: center; gap: 4px; background: #fef3c7; color: #92400e; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; }
.matched-tag i { font-size: 9px; }

/* Loading spinner */
.table-loading { text-align: center; padding: 40px; color: var(--text-muted); }
.table-loading i { font-size: 24px; margin-bottom: 8px; display: block; }

@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
    .toolbar-filters { flex-direction: column; align-items: stretch; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-warehouse" style="color:var(--primary);margin-right:8px;"></i>Rack Management</h1>
        <button class="btn-add" onclick="openCreateModal();">
            <i class="fas fa-plus"></i> Add Rack
        </button>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="toolbar-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search racks or products..." oninput="debouncedFetch();">
                </div>
                <select id="filterStatus" class="filter-select" onchange="fetchRacks(1);">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="item-count" id="itemCount">Loading...</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Rack Code</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <tr class="no-results"><td colspan="6" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading racks...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap" id="paginationWrap" style="display:none;">
            <div class="pagination-info" id="paginationInfo"></div>
            <div class="pagination-btns" id="paginationBtns"></div>
        </div>
    </div>

    <!-- Rack Products Section -->
    <div class="product-list-card" id="productListCard" style="display:none;">
        <div class="product-list-header">
            <h2><i class="fas fa-boxes-stacked"></i> <span id="productListTitle">Rack Products</span></h2>
            <button class="btn-add" onclick="openAssignModal();" style="background:#16a34a;">
                <i class="fas fa-plus"></i> Add Products
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>QOH</th>
                        <th>UOM</th>
                        <th>Assigned</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="productBody">
                    <tr class="no-results"><td colspan="8">Select a rack to view products</td></tr>
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
                <h5 class="modal-title" id="rackModalTitle"><i class="fas fa-warehouse"></i> Add Rack</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rack Code <span class="text-danger">*</span></label>
                    <input type="text" id="fCode" class="form-control" placeholder="e.g. RACK-A1, SHELF-01">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" id="fDescription" class="form-control" placeholder="Optional description">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveRack();"><i class="fas fa-check"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Products Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add Products to <span id="assignRackLabel"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="assign-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="assignSearchInput" placeholder="Search by barcode or product name..." oninput="searchProducts();">
                </div>
                <div class="assign-results" id="assignResults">
                    <div class="no-results-msg">Type to search for products</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var rackModal = null, assignModal_bs = null;
var currentPage = 1;
var debounceTimer = null;
var searchTimer = null;
var selectedRackId = 0;
var selectedRackCode = '';

document.addEventListener('DOMContentLoaded', function() {
    rackModal = new bootstrap.Modal(document.getElementById('rackModal'));
    assignModal_bs = new bootstrap.Modal(document.getElementById('assignModal'));
    fetchRacks(1);
});

// ===================== RACK TABLE =====================

function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() { fetchRacks(1); }, 300);
}

function fetchRacks(page) {
    currentPage = page;
    var search = document.getElementById('searchInput').value.trim();
    var status = document.getElementById('filterStatus').value;

    document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="6" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>';

    $.ajax({
        type: 'POST', url: 'rack_ajax.php',
        data: { action: 'list', page: page, per_page: 50, search: search, status: status },
        dataType: 'json',
        success: function(data) {
            renderTable(data);
            renderPagination(data);
        },
        error: function() {
            document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="6"><i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px;display:block;"></i>Failed to load racks</td></tr>';
        }
    });
}

function renderTable(data) {
    var tbody = document.getElementById('dataBody');
    var racks = data.racks || [];
    var offset = (data.page - 1) * data.per_page;

    if (racks.length === 0) {
        tbody.innerHTML = '<tr class="no-results"><td colspan="6"><i class="fas fa-warehouse" style="font-size:24px;margin-bottom:8px;display:block;"></i>No racks found</td></tr>';
        document.getElementById('itemCount').textContent = '0 rack(s)';
        return;
    }

    var html = '';
    for (var i = 0; i < racks.length; i++) {
        var r = racks[i];
        var isActive = r.status === 'ACTIVE';
        var pCount = parseInt(r.product_count || 0);
        var isSelected = (r.id == selectedRackId);

        html += '<tr style="' + (isSelected ? 'background:#fef2f2;' : '') + '">';
        html += '<td>' + (offset + i + 1) + '</td>';
        html += '<td><strong>' + escHtml(r.code) + '</strong>';
        if (r.matched_products && r.matched_products.length > 0) {
            html += '<div class="matched-products">';
            for (var j = 0; j < r.matched_products.length; j++) {
                var mp = r.matched_products[j];
                html += '<span class="matched-tag"><i class="fas fa-box"></i>' + escHtml(mp.name || mp.barcode) + '</span>';
            }
            html += '</div>';
        }
        html += '</td>';
        html += '<td>' + escHtml(r.description || '-') + '</td>';
        html += '<td><span class="badge-count">' + pCount + '</span></td>';
        html += '<td><span class="badge-status ' + (isActive ? 'badge-active' : 'badge-inactive') + '">' + (isActive ? 'Active' : 'Inactive') + '</span></td>';
        html += '<td style="white-space:nowrap">';
        html += '<button class="btn-action btn-view" onclick="viewRackProducts(' + r.id + ',\'' + escHtml(r.code).replace(/'/g, "\\'") + '\');"><i class="fas fa-eye"></i></button> ';
        html += '<button class="btn-action btn-edit" onclick="openEditModal(' + r.id + ');"><i class="fas fa-pen"></i></button>';
        if (isActive) {
            html += ' <button class="btn-action btn-delete" onclick="deactivateRack(' + r.id + ',\'' + escHtml(r.code).replace(/'/g, "\\'") + '\');"><i class="fas fa-ban"></i></button>';
        } else {
            html += ' <button class="btn-action btn-activate" onclick="activateRack(' + r.id + ');"><i class="fas fa-check"></i></button>';
        }
        html += ' <button class="btn-action" style="background:#7f1d1d;" onclick="deleteRack(' + r.id + ',\'' + escHtml(r.code).replace(/'/g, "\\'") + '\');"><i class="fas fa-trash"></i></button>';
        html += '</td>';
        html += '</tr>';
    }
    tbody.innerHTML = html;
    document.getElementById('itemCount').textContent = data.total + ' rack(s)';
}

function renderPagination(data) {
    var wrap = document.getElementById('paginationWrap');
    var info = document.getElementById('paginationInfo');
    var btns = document.getElementById('paginationBtns');

    if (data.pages <= 1) {
        wrap.style.display = 'none';
        return;
    }

    wrap.style.display = 'flex';
    var start = (data.page - 1) * data.per_page + 1;
    var end = Math.min(data.page * data.per_page, data.total);
    info.textContent = 'Showing ' + start + '-' + end + ' of ' + data.total;

    var html = '';
    html += '<button ' + (data.page <= 1 ? 'disabled' : '') + ' onclick="fetchRacks(' + (data.page - 1) + ');">&laquo; Prev</button>';

    var startPage = Math.max(1, data.page - 2);
    var endPage = Math.min(data.pages, data.page + 2);
    if (startPage > 1) {
        html += '<button onclick="fetchRacks(1);">1</button>';
        if (startPage > 2) html += '<button disabled>...</button>';
    }
    for (var i = startPage; i <= endPage; i++) {
        html += '<button class="' + (i === data.page ? 'active' : '') + '" onclick="fetchRacks(' + i + ');">' + i + '</button>';
    }
    if (endPage < data.pages) {
        if (endPage < data.pages - 1) html += '<button disabled>...</button>';
        html += '<button onclick="fetchRacks(' + data.pages + ');">' + data.pages + '</button>';
    }

    html += '<button ' + (data.page >= data.pages ? 'disabled' : '') + ' onclick="fetchRacks(' + (data.page + 1) + ');">Next &raquo;</button>';
    btns.innerHTML = html;
}

function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}

// ===================== RACK CRUD =====================

function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('fCode').value = '';
    document.getElementById('fDescription').value = '';
}

function openCreateModal() {
    clearForm();
    document.getElementById('rackModalTitle').innerHTML = '<i class="fas fa-warehouse"></i> Add Rack';
    rackModal.show();
}

function openEditModal(id) {
    clearForm();
    document.getElementById('rackModalTitle').innerHTML = '<i class="fas fa-warehouse"></i> Edit Rack';
    document.getElementById('editId').value = id;

    $.ajax({
        type: 'POST', url: 'rack_ajax.php', data: { action: 'get', id: id }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            document.getElementById('fCode').value = data.code || '';
            document.getElementById('fDescription').value = data.description || '';
            rackModal.show();
        }
    });
}

function saveRack() {
    var editId = document.getElementById('editId').value;
    var code = document.getElementById('fCode').value.trim();

    if (code === '') {
        Swal.fire({ icon: 'warning', text: 'Rack code is required.' });
        return;
    }

    var postData = {
        action: editId ? 'update' : 'create',
        code: code,
        description: document.getElementById('fDescription').value.trim()
    };
    if (editId) postData.id = editId;

    $.ajax({
        type: 'POST', url: 'rack_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.success) {
                rackModal.hide();
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                    fetchRacks(currentPage);
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}

function deactivateRack(id, code) {
    Swal.fire({
        title: 'Deactivate rack?',
        text: 'Deactivate "' + code + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Deactivate'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'rack_ajax.php', data: { action: 'delete', id: id }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                            fetchRacks(currentPage);
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                }
            });
        }
    });
}

function activateRack(id) {
    $.post('rack_ajax.php', { action: 'activate', id: id }, function(data) {
        if (data.success) {
            fetchRacks(currentPage);
        } else {
            Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
        }
    }, 'json');
}

function deleteRack(id, code) {
    // First fetch product count to show in confirmation
    $.ajax({
        type: 'POST', url: 'rack_ajax.php', data: { action: 'rack_product_count', id: id }, dataType: 'json',
        success: function(data) {
            var count = data.count || 0;
            var msg = 'This will permanently delete rack "' + code + '".';
            if (count > 0) {
                msg += '\n\nThis will unlink ' + count + ' product(s) currently assigned to this rack.';
            }

            Swal.fire({
                title: 'Delete rack permanently?',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#7f1d1d',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Delete',
                focusCancel: true
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST', url: 'rack_ajax.php', data: { action: 'destroy', id: id }, dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                // If deleted rack was selected, hide product panel
                                if (selectedRackId == id) {
                                    selectedRackId = 0;
                                    selectedRackCode = '';
                                    document.getElementById('productListCard').style.display = 'none';
                                }
                                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                                    fetchRacks(currentPage);
                                });
                            } else {
                                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                            }
                        }
                    });
                }
            });
        },
        error: function() {
            Swal.fire({ icon: 'error', text: 'Failed to fetch rack details.' });
        }
    });
}

// ===================== RACK PRODUCTS =====================

function viewRackProducts(rackId, rackCode) {
    selectedRackId = rackId;
    selectedRackCode = rackCode;

    document.getElementById('productListCard').style.display = 'block';
    document.getElementById('productListTitle').textContent = rackCode + ' - Products';
    document.getElementById('productBody').innerHTML = '<tr class="no-results"><td colspan="8" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>';

    // Refresh the table to highlight selected row
    fetchRacks(currentPage);

    $.ajax({
        type: 'POST', url: 'rack_ajax.php',
        data: { action: 'rack_products', rack_id: rackId },
        dataType: 'json',
        success: function(data) {
            var tbody = document.getElementById('productBody');
            if (!data || data.length === 0 || data.error) {
                tbody.innerHTML = '<tr class="no-results"><td colspan="8"><i class="fas fa-box-open" style="font-size:24px;margin-bottom:8px;display:block;"></i>No products assigned to this rack</td></tr>';
                return;
            }

            var html = '';
            for (var i = 0; i < data.length; i++) {
                var p = data[i];
                html += '<tr>';
                html += '<td>' + (i + 1) + '</td>';
                html += '<td><strong>' + escHtml(p.barcode || '') + '</strong></td>';
                html += '<td>' + escHtml(p.name || '-') + '</td>';
                html += '<td>' + escHtml(p.cat || '-') + '</td>';
                html += '<td>' + parseInt(p.qoh || 0) + '</td>';
                html += '<td>' + escHtml(p.uom || '-') + '</td>';
                html += '<td><small>' + escHtml((p.assigned_at || '').substring(0, 10)) + '</small></td>';
                html += '<td style="white-space:nowrap">';
                html += '<button class="btn-action btn-delete" onclick="removeProduct(' + parseInt(p.mapping_id) + ',\'' + escHtml(p.barcode || '').replace(/'/g, "\\'") + '\');"><i class="fas fa-times"></i> Remove</button>';
                html += '</td>';
                html += '</tr>';
            }
            tbody.innerHTML = html;
        },
        error: function() {
            document.getElementById('productBody').innerHTML = '<tr class="no-results"><td colspan="8">Failed to load products</td></tr>';
        }
    });

    // Scroll to product list
    setTimeout(function() {
        document.getElementById('productListCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

function removeProduct(mappingId, barcode) {
    Swal.fire({
        title: 'Remove product?',
        text: 'Remove "' + barcode + '" from this rack?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Remove'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'rack_ajax.php', data: { action: 'remove_product', mapping_id: mappingId }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1200, showConfirmButton: false }).then(function() {
                            viewRackProducts(selectedRackId, selectedRackCode);
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                }
            });
        }
    });
}

// ===================== ASSIGN PRODUCTS =====================

function openAssignModal() {
    if (!selectedRackId) {
        Swal.fire({ icon: 'warning', text: 'Please select a rack first.' });
        return;
    }

    document.getElementById('assignRackLabel').textContent = '"' + selectedRackCode + '"';
    document.getElementById('assignSearchInput').value = '';
    document.getElementById('assignResults').innerHTML = '<div class="no-results-msg">Type to search for products</div>';
    assignModal_bs.show();

    setTimeout(function() {
        document.getElementById('assignSearchInput').focus();
    }, 300);
}

function searchProducts() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
        var q = document.getElementById('assignSearchInput').value.trim();
        var resultsDiv = document.getElementById('assignResults');

        if (q === '') {
            resultsDiv.innerHTML = '<div class="no-results-msg">Type to search for products</div>';
            return;
        }

        resultsDiv.innerHTML = '<div class="no-results-msg"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

        $.ajax({
            type: 'POST', url: 'rack_ajax.php',
            data: { action: 'search_products', q: q, rack_id: selectedRackId },
            dataType: 'json',
            success: function(data) {
                if (!data || data.length === 0) {
                    resultsDiv.innerHTML = '<div class="no-results-msg"><i class="fas fa-search" style="margin-right:6px;"></i>No products found</div>';
                    return;
                }

                var html = '';
                for (var i = 0; i < data.length; i++) {
                    var p = data[i];
                    html += '<div class="assign-item">';
                    html += '<div class="item-info">';
                    html += '<div class="item-barcode">' + escHtml(p.barcode || '') + '</div>';
                    html += '<div class="item-name">' + escHtml(p.name || '') + '</div>';
                    html += '<div class="item-qoh">QOH: ' + parseInt(p.qoh || 0) + ' | ' + escHtml(p.cat || '-') + '</div>';
                    html += '</div>';
                    html += '<button class="btn-assign-item" onclick="assignProduct(\'' + escHtml(p.barcode || '').replace(/'/g, "\\'") + '\', this);"><i class="fas fa-plus"></i> Add</button>';
                    html += '</div>';
                }
                resultsDiv.innerHTML = html;
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="no-results-msg">Search failed. Try again.</div>';
            }
        });
    }, 300);
}

function assignProduct(barcode, btnEl) {
    if (!selectedRackId) return;

    // Disable button
    if (btnEl) { btnEl.disabled = true; btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

    $.ajax({
        type: 'POST', url: 'rack_ajax.php',
        data: { action: 'add_product', rack_id: selectedRackId, barcode: barcode },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                // Update button to show added
                if (btnEl) {
                    btnEl.innerHTML = '<i class="fas fa-check"></i> Added';
                    btnEl.style.background = '#16a34a';
                    btnEl.disabled = true;
                }
                // Refresh rack products
                viewRackProducts(selectedRackId, selectedRackCode);
            } else {
                if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = '<i class="fas fa-plus"></i> Add'; }
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        },
        error: function() {
            if (btnEl) { btnEl.disabled = false; btnEl.innerHTML = '<i class="fas fa-plus"></i> Add'; }
            Swal.fire({ icon: 'error', text: 'Failed to assign product.' });
        }
    });
}

// Focus code input when modal opens
document.getElementById('rackModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('fCode').focus();
});
</script>
</body>
</html>
