<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
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

/* Pagination */
.pagination-wrap { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; flex-wrap: wrap; gap: 8px; }
.pagination-info { font-size: 13px; color: var(--text-muted); }
.pagination-btns { display: flex; gap: 4px; }
.pagination-btns button { padding: 6px 12px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); color: var(--text); }
.pagination-btns button:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
.pagination-btns button.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.pagination-btns button:disabled { opacity: 0.4; cursor: default; }

/* Manage list inside modals */
.manage-list { max-height: 300px; overflow-y: auto; }
.manage-list .manage-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid #f3f4f6; gap: 8px; }
.manage-list .manage-item:last-child { border-bottom: none; }
.manage-list .manage-item .item-name { flex: 1; font-size: 13px; }
.manage-list .manage-item .item-name.inactive { text-decoration: line-through; color: var(--text-muted); }
.manage-list .manage-item .item-actions { display: flex; gap: 4px; }
.manage-list .manage-item .item-actions button { padding: 3px 8px; border: none; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; color: #fff; }
.manage-add-row { display: flex; gap: 8px; padding: 12px 0; }
.manage-add-row input, .manage-add-row select { flex: 1; padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
.manage-add-row button { padding: 7px 14px; }
.btn-manage { background: none; border: 1px solid #d1d5db; padding: 0 8px; height: 38px; border-radius: 0 8px 8px 0; cursor: pointer; color: var(--text-muted); font-size: 13px; transition: all var(--transition); display: flex; align-items: center; }
.btn-manage:hover { border-color: var(--primary); color: var(--primary); }
.select-with-manage { display: flex; }
.select-with-manage select { border-radius: 8px 0 0 8px; border-right: none; flex: 1; }

/* Loading spinner */
.table-loading { text-align: center; padding: 40px; color: var(--text-muted); }
.table-loading i { font-size: 24px; margin-bottom: 8px; display: block; }

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
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="btn-add" onclick="openManageCatModal();" style="background:#6366f1;">
                <i class="fas fa-tags"></i> Manage Categories
            </button>
            <button class="btn-add" onclick="openManageUomModal();" style="background:#0891b2;">
                <i class="fas fa-ruler"></i> Manage UOM
            </button>
            <button class="btn-add" onclick="openCreateModal();">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="toolbar-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search products..." oninput="debouncedFetch();">
                </div>
                <select id="filterCategory" class="filter-select" onchange="fetchProducts(1);">
                    <option value="">All Categories</option>
                </select>
                <select id="filterStatus" class="filter-select" onchange="fetchProducts(1);">
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
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>QOH</th>
                        <th>Min Qty</th>
                        <th>Rack</th>
                        <th>Status</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <tr class="no-results"><td colspan="10" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading products...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap" id="paginationWrap" style="display:none;">
            <div class="pagination-info" id="paginationInfo"></div>
            <div class="pagination-btns" id="paginationBtns"></div>
        </div>
    </div>
</div>

<!-- Create/Edit Product Modal -->
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
                        <select id="fCat" class="form-select" onchange="loadSubCatOptions();">
                            <option value="">-- Select --</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Sub Category</label>
                        <select id="fSubCat" class="form-select">
                            <option value="">-- Select --</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">UOM</label>
                        <select id="fUom" class="form-select">
                            <option value="">-- Select --</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-semibold">Price</label>
                        <input type="number" id="fDisPrice" class="form-control" step="0.01" min="0" placeholder="0.00" value="0">
                    </div>
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
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Rack Location</label>
                        <input type="text" id="fRack" class="form-control" list="rackList" placeholder="Rack">
                        <datalist id="rackList"></datalist>
                    </div>
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

<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tags"></i> Manage Categories & Sub Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2">Categories</h6>
                        <div class="manage-add-row">
                            <input type="text" id="newCatName" placeholder="New category name">
                            <button class="btn btn-sm btn-success" onclick="createCategory();"><i class="fas fa-plus"></i> Add</button>
                        </div>
                        <div class="manage-list" id="catListManage"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-2">Sub Categories</h6>
                        <div class="manage-add-row">
                            <select id="newSubCatParent" style="max-width:140px;">
                                <option value="">Category...</option>
                            </select>
                            <input type="text" id="newSubCatName" placeholder="New sub category">
                            <button class="btn btn-sm btn-success" onclick="createSubCategory();"><i class="fas fa-plus"></i> Add</button>
                        </div>
                        <div class="manage-list" id="subCatListManage"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage UOM Modal -->
<div class="modal fade" id="manageUomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ruler"></i> Manage UOM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="manage-add-row">
                    <input type="text" id="newUomName" placeholder="New UOM name (e.g. PCS, KG, BOX)">
                    <button class="btn btn-sm btn-success" onclick="createUom();"><i class="fas fa-plus"></i> Add</button>
                </div>
                <div class="manage-list" id="uomListManage"></div>
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
var productModal = null, manageCatModal = null, manageUomModal = null;
var currentPage = 1;
var debounceTimer = null;

// Cache for dropdowns
var categoriesCache = [];
var subCategoriesCache = {};
var uomCache = [];

document.addEventListener('DOMContentLoaded', function() {
    productModal = new bootstrap.Modal(document.getElementById('productModal'));
    manageCatModal = new bootstrap.Modal(document.getElementById('manageCatModal'));
    manageUomModal = new bootstrap.Modal(document.getElementById('manageUomModal'));

    // Load initial data
    loadCategoryFilter();
    loadDropdowns();
    fetchProducts(1);
});

// ===================== PAGINATION & TABLE =====================

function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() { fetchProducts(1); }, 300);
}

function fetchProducts(page) {
    currentPage = page;
    var search = document.getElementById('searchInput').value.trim();
    var cat = document.getElementById('filterCategory').value;
    var status = document.getElementById('filterStatus').value;

    document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="10" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>';

    $.ajax({
        type: 'POST', url: 'product_ajax.php',
        data: { action: 'list', page: page, per_page: 50, search: search, cat: cat, status: status },
        dataType: 'json',
        success: function(data) {
            renderTable(data);
            renderPagination(data);
        },
        error: function() {
            document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="10"><i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px;display:block;"></i>Failed to load products</td></tr>';
        }
    });
}

function renderTable(data) {
    var tbody = document.getElementById('dataBody');
    var products = data.products || [];
    var offset = (data.page - 1) * data.per_page;

    if (products.length === 0) {
        tbody.innerHTML = '<tr class="no-results"><td colspan="10"><i class="fas fa-boxes-stacked" style="font-size:24px;margin-bottom:8px;display:block;"></i>No products found</td></tr>';
        document.getElementById('itemCount').textContent = '0 product(s)';
        return;
    }

    var html = '';
    for (var i = 0; i < products.length; i++) {
        var p = products[i];
        var isActive = (p.checked || 'Y') === 'Y';
        var qoh = parseFloat(p.qoh || 0);
        var minQty = parseInt(p.min_qty || 0);
        var isLow = (qoh <= minQty && isActive);

        html += '<tr>';
        html += '<td>' + (offset + i + 1) + '</td>';
        html += '<td><strong>' + escHtml(p.barcode || '') + '</strong></td>';
        html += '<td>' + escHtml(p.name || '') + '</td>';
        html += '<td>' + escHtml(p.cat || '') + '</td>';
        html += '<td>' + parseFloat(p.disprice || 0).toFixed(2) + '</td>';
        html += '<td>' + Math.round(qoh);
        if (isLow) html += ' <span class="badge-low">Low</span>';
        html += '</td>';
        html += '<td>' + minQty + '</td>';
        html += '<td>' + escHtml(p.rack || '') + '</td>';
        html += '<td><span class="badge-status ' + (isActive ? 'badge-active' : 'badge-inactive') + '">' + (isActive ? 'Active' : 'Inactive') + '</span></td>';
        html += '<td style="white-space:nowrap">';
        html += '<button class="btn-action btn-edit" onclick="openEditModal(' + p.id + ');"><i class="fas fa-pen"></i> Edit</button>';
        if (isActive) {
            html += ' <button class="btn-action btn-delete" onclick="deactivateProduct(' + p.id + ',\'' + escHtml(p.name || '').replace(/'/g, "\\'") + '\');"><i class="fas fa-ban"></i></button>';
        }
        html += '</td>';
        html += '</tr>';
    }
    tbody.innerHTML = html;
    document.getElementById('itemCount').textContent = data.total + ' product(s)';
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
    html += '<button ' + (data.page <= 1 ? 'disabled' : '') + ' onclick="fetchProducts(' + (data.page - 1) + ');">&laquo; Prev</button>';

    // Show limited page numbers
    var startPage = Math.max(1, data.page - 2);
    var endPage = Math.min(data.pages, data.page + 2);
    if (startPage > 1) {
        html += '<button onclick="fetchProducts(1);">1</button>';
        if (startPage > 2) html += '<button disabled>...</button>';
    }
    for (var i = startPage; i <= endPage; i++) {
        html += '<button class="' + (i === data.page ? 'active' : '') + '" onclick="fetchProducts(' + i + ');">' + i + '</button>';
    }
    if (endPage < data.pages) {
        if (endPage < data.pages - 1) html += '<button disabled>...</button>';
        html += '<button onclick="fetchProducts(' + data.pages + ');">' + data.pages + '</button>';
    }

    html += '<button ' + (data.page >= data.pages ? 'disabled' : '') + ' onclick="fetchProducts(' + (data.page + 1) + ');">Next &raquo;</button>';
    btns.innerHTML = html;
}

function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}

// ===================== DROPDOWNS =====================

function loadCategoryFilter() {
    $.post('product_ajax.php', { action: 'categories' }, function(cats) {
        var sel = document.getElementById('filterCategory');
        var val = sel.value;
        sel.innerHTML = '<option value="">All Categories</option>';
        (cats || []).forEach(function(c) {
            sel.innerHTML += '<option value="' + escHtml(c) + '">' + escHtml(c) + '</option>';
        });
        sel.value = val;
    }, 'json');
}

function loadDropdowns() {
    // Load categories for product form
    $.post('product_ajax.php', { action: 'cat_list' }, function(cats) {
        categoriesCache = (cats || []).filter(function(c) { return c.status === 'ACTIVE'; });
        refreshCatSelect();
    }, 'json');

    // Load UOMs
    $.post('product_ajax.php', { action: 'uom_list' }, function(uoms) {
        uomCache = (uoms || []).filter(function(u) { return u.status === 'ACTIVE'; });
        refreshUomSelect();
    }, 'json');

    // Load racks for datalist
    $.post('product_ajax.php', { action: 'racks' }, function(racks) {
        var dl = document.getElementById('rackList');
        dl.innerHTML = '';
        (racks || []).forEach(function(r) {
            dl.innerHTML += '<option value="' + escHtml(r) + '">';
        });
    }, 'json');
}

function refreshCatSelect() {
    var sel = document.getElementById('fCat');
    var val = sel.value;
    sel.innerHTML = '<option value="">-- Select --</option>';
    categoriesCache.forEach(function(c) {
        sel.innerHTML += '<option value="' + escHtml(c.name) + '">' + escHtml(c.name) + '</option>';
    });
    sel.value = val;

    // Also refresh parent dropdown in manage modal
    var pSel = document.getElementById('newSubCatParent');
    var pVal = pSel.value;
    pSel.innerHTML = '<option value="">Category...</option>';
    categoriesCache.forEach(function(c) {
        pSel.innerHTML += '<option value="' + c.id + '">' + escHtml(c.name) + '</option>';
    });
    pSel.value = pVal;
}

function refreshUomSelect() {
    var sel = document.getElementById('fUom');
    var val = sel.value;
    sel.innerHTML = '<option value="">-- Select --</option>';
    uomCache.forEach(function(u) {
        sel.innerHTML += '<option value="' + escHtml(u.name) + '">' + escHtml(u.name) + '</option>';
    });
    sel.value = val;
}

function loadSubCatOptions() {
    var catName = document.getElementById('fCat').value;
    var sel = document.getElementById('fSubCat');
    sel.innerHTML = '<option value="">-- Select --</option>';

    if (!catName) return;

    // Find category id
    var catObj = categoriesCache.find(function(c) { return c.name === catName; });
    if (!catObj) return;

    $.post('product_ajax.php', { action: 'subcat_list', category_id: catObj.id }, function(subs) {
        var active = (subs || []).filter(function(s) { return s.status === 'ACTIVE'; });
        active.forEach(function(s) {
            sel.innerHTML += '<option value="' + escHtml(s.name) + '">' + escHtml(s.name) + '</option>';
        });
    }, 'json');
}

// ===================== PRODUCT CRUD =====================

function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('fBarcode').value = '';
    document.getElementById('fCode').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('fDescription').value = '';
    document.getElementById('fCat').value = '';
    document.getElementById('fSubCat').innerHTML = '<option value="">-- Select --</option>';
    document.getElementById('fUom').value = '';
    document.getElementById('fDisPrice').value = '0';
    document.getElementById('fQoh').value = '0';
    document.getElementById('fMinQty').value = '0';
    document.getElementById('fMaxQty').value = '0';
    document.getElementById('fRack').value = '';
    document.getElementById('fChecked').value = 'Y';
    document.getElementById('fBarcode').disabled = false;
}

function openCreateModal() {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box"></i> Add Product';
    productModal.show();
}

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
            document.getElementById('fUom').value = data.uom || '';
            document.getElementById('fDisPrice').value = data.disprice || '0';
            document.getElementById('fQoh').value = data.qoh || '0';
            document.getElementById('fMinQty').value = data.min_qty || '0';
            document.getElementById('fMaxQty').value = data.max_qty || '0';
            document.getElementById('fRack').value = data.rack || '';
            document.getElementById('fChecked').value = data.checked || 'Y';

            // Load sub categories then set value
            var catName = data.cat || '';
            if (catName) {
                var catObj = categoriesCache.find(function(c) { return c.name === catName; });
                if (catObj) {
                    $.post('product_ajax.php', { action: 'subcat_list', category_id: catObj.id }, function(subs) {
                        var sel = document.getElementById('fSubCat');
                        sel.innerHTML = '<option value="">-- Select --</option>';
                        var active = (subs || []).filter(function(s) { return s.status === 'ACTIVE'; });
                        active.forEach(function(s) {
                            sel.innerHTML += '<option value="' + escHtml(s.name) + '">' + escHtml(s.name) + '</option>';
                        });
                        sel.value = data.sub_cat || '';
                    }, 'json');
                }
            }

            productModal.show();
        }
    });
}

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
                productModal.hide();
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                    fetchProducts(currentPage);
                    loadCategoryFilter();
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}

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
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                            fetchProducts(currentPage);
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                }
            });
        }
    });
}

// ===================== CATEGORY MANAGEMENT =====================

function openManageCatModal() {
    loadManageCategories();
    loadManageSubCategories();
    manageCatModal.show();
}

function loadManageCategories() {
    $.post('product_ajax.php', { action: 'cat_list' }, function(cats) {
        var all = cats || [];
        categoriesCache = all.filter(function(c) { return c.status === 'ACTIVE'; });
        refreshCatSelect();

        var html = '';
        all.forEach(function(c) {
            var inactive = c.status !== 'ACTIVE';
            html += '<div class="manage-item">';
            html += '<span class="item-name ' + (inactive ? 'inactive' : '') + '">' + escHtml(c.name) + '</span>';
            html += '<div class="item-actions">';
            html += '<button style="background:#3b82f6;" onclick="editCategory(' + c.id + ',\'' + escHtml(c.name).replace(/'/g, "\\'") + '\');"><i class="fas fa-pen"></i></button>';
            if (inactive) {
                html += '<button style="background:#16a34a;" onclick="activateCategory(' + c.id + ');"><i class="fas fa-check"></i></button>';
            } else {
                html += '<button style="background:#ef4444;" onclick="deleteCategory(' + c.id + ');"><i class="fas fa-ban"></i></button>';
            }
            html += '</div></div>';
        });
        if (all.length === 0) html = '<div style="padding:20px;text-align:center;color:var(--text-muted);">No categories yet</div>';
        document.getElementById('catListManage').innerHTML = html;
    }, 'json');
}

function createCategory() {
    var name = document.getElementById('newCatName').value.trim();
    if (!name) return;
    $.post('product_ajax.php', { action: 'cat_create', name: name }, function(r) {
        if (r.success) {
            document.getElementById('newCatName').value = '';
            loadManageCategories();
            loadCategoryFilter();
        } else {
            Swal.fire({ icon: 'error', text: r.error });
        }
    }, 'json');
}

function editCategory(id, oldName) {
    Swal.fire({
        title: 'Edit Category',
        input: 'text',
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonText: 'Save',
        inputValidator: function(v) { if (!v || !v.trim()) return 'Name is required.'; }
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('product_ajax.php', { action: 'cat_update', id: id, name: result.value.trim() }, function(r) {
                if (r.success) {
                    loadManageCategories();
                    loadCategoryFilter();
                } else {
                    Swal.fire({ icon: 'error', text: r.error });
                }
            }, 'json');
        }
    });
}

function deleteCategory(id) {
    $.post('product_ajax.php', { action: 'cat_delete', id: id }, function(r) {
        if (r.success) { loadManageCategories(); loadCategoryFilter(); }
        else Swal.fire({ icon: 'error', text: r.error });
    }, 'json');
}

function activateCategory(id) {
    $.post('product_ajax.php', { action: 'cat_activate', id: id }, function(r) {
        if (r.success) { loadManageCategories(); loadCategoryFilter(); }
        else Swal.fire({ icon: 'error', text: r.error });
    }, 'json');
}

// ===================== SUB CATEGORY MANAGEMENT =====================

function loadManageSubCategories() {
    $.post('product_ajax.php', { action: 'subcat_list' }, function(subs) {
        var all = subs || [];
        var html = '';
        all.forEach(function(s) {
            var inactive = s.status !== 'ACTIVE';
            html += '<div class="manage-item">';
            html += '<span class="item-name ' + (inactive ? 'inactive' : '') + '">';
            html += '<small style="color:var(--text-muted);">[' + escHtml(s.cat_name || '') + ']</small> ' + escHtml(s.name);
            html += '</span>';
            html += '<div class="item-actions">';
            html += '<button style="background:#3b82f6;" onclick="editSubCategory(' + s.id + ',' + s.category_id + ',\'' + escHtml(s.name).replace(/'/g, "\\'") + '\');"><i class="fas fa-pen"></i></button>';
            if (inactive) {
                html += '<button style="background:#16a34a;" onclick="activateSubCategory(' + s.id + ');"><i class="fas fa-check"></i></button>';
            } else {
                html += '<button style="background:#ef4444;" onclick="deleteSubCategory(' + s.id + ');"><i class="fas fa-ban"></i></button>';
            }
            html += '</div></div>';
        });
        if (all.length === 0) html = '<div style="padding:20px;text-align:center;color:var(--text-muted);">No sub categories yet</div>';
        document.getElementById('subCatListManage').innerHTML = html;
    }, 'json');
}

function createSubCategory() {
    var catId = document.getElementById('newSubCatParent').value;
    var name = document.getElementById('newSubCatName').value.trim();
    if (!catId || !name) { Swal.fire({ icon: 'warning', text: 'Select a category and enter a name.' }); return; }
    $.post('product_ajax.php', { action: 'subcat_create', category_id: catId, name: name }, function(r) {
        if (r.success) {
            document.getElementById('newSubCatName').value = '';
            loadManageSubCategories();
        } else {
            Swal.fire({ icon: 'error', text: r.error });
        }
    }, 'json');
}

function editSubCategory(id, catId, oldName) {
    Swal.fire({
        title: 'Edit Sub Category',
        input: 'text',
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonText: 'Save',
        inputValidator: function(v) { if (!v || !v.trim()) return 'Name is required.'; }
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('product_ajax.php', { action: 'subcat_update', id: id, category_id: catId, name: result.value.trim() }, function(r) {
                if (r.success) { loadManageSubCategories(); }
                else Swal.fire({ icon: 'error', text: r.error });
            }, 'json');
        }
    });
}

function deleteSubCategory(id) {
    $.post('product_ajax.php', { action: 'subcat_delete', id: id }, function(r) {
        if (r.success) loadManageSubCategories();
        else Swal.fire({ icon: 'error', text: r.error });
    }, 'json');
}

function activateSubCategory(id) {
    $.post('product_ajax.php', { action: 'subcat_activate', id: id }, function(r) {
        if (r.success) loadManageSubCategories();
        else Swal.fire({ icon: 'error', text: r.error });
    }, 'json');
}

// ===================== UOM MANAGEMENT =====================

function openManageUomModal() {
    loadManageUoms();
    manageUomModal.show();
}

function loadManageUoms() {
    $.post('product_ajax.php', { action: 'uom_list' }, function(uoms) {
        var all = uoms || [];
        uomCache = all.filter(function(u) { return u.status === 'ACTIVE'; });
        refreshUomSelect();

        var html = '';
        all.forEach(function(u) {
            var inactive = u.status !== 'ACTIVE';
            html += '<div class="manage-item">';
            html += '<span class="item-name ' + (inactive ? 'inactive' : '') + '">' + escHtml(u.name) + '</span>';
            html += '<div class="item-actions">';
            html += '<button style="background:#3b82f6;" onclick="editUom(' + u.id + ',\'' + escHtml(u.name).replace(/'/g, "\\'") + '\');"><i class="fas fa-pen"></i></button>';
            if (inactive) {
                html += '<button style="background:#16a34a;" onclick="activateUom(' + u.id + ');"><i class="fas fa-check"></i></button>';
            } else {
                html += '<button style="background:#ef4444;" onclick="deleteUom(' + u.id + ');"><i class="fas fa-ban"></i></button>';
            }
            html += '</div></div>';
        });
        if (all.length === 0) html = '<div style="padding:20px;text-align:center;color:var(--text-muted);">No UOMs yet</div>';
        document.getElementById('uomListManage').innerHTML = html;
    }, 'json');
}

function createUom() {
    var name = document.getElementById('newUomName').value.trim();
    if (!name) return;
    $.post('product_ajax.php', { action: 'uom_create', name: name }, function(r) {
        if (r.success) {
            document.getElementById('newUomName').value = '';
            loadManageUoms();
        } else {
            Swal.fire({ icon: 'error', text: r.error });
        }
    }, 'json');
}

function editUom(id, oldName) {
    Swal.fire({
        title: 'Edit UOM',
        input: 'text',
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonText: 'Save',
        inputValidator: function(v) { if (!v || !v.trim()) return 'Name is required.'; }
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('product_ajax.php', { action: 'uom_update', id: id, name: result.value.trim() }, function(r) {
                if (r.success) loadManageUoms();
                else Swal.fire({ icon: 'error', text: r.error });
            }, 'json');
        }
    });
}

function deleteUom(id) {
    $.post('product_ajax.php', { action: 'uom_delete', id: id }, function(r) {
        if (r.success) loadManageUoms();
        else Swal.fire({ icon: 'error', text: r.error });
    }, 'json');
}

function activateUom(id) {
    $.post('product_ajax.php', { action: 'uom_activate', id: id }, function(r) {
        if (r.success) loadManageUoms();
        else Swal.fire({ icon: 'error', text: r.error });
    }, 'json');
}

// Modal autofocus
document.getElementById('productModal').addEventListener('shown.bs.modal', function() {
    var el = document.getElementById('fBarcode');
    if (!el.disabled) el.focus(); else document.getElementById('fName').focus();
});
</script>
</body>
</html>
