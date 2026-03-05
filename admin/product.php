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

/* Product image upload */
.img-upload-area { border: 2px dashed #d1d5db; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all var(--transition); background: #fafbfc; position: relative; }
.img-upload-area:hover { border-color: var(--primary); background: #fff5f5; }
.img-upload-area.has-image { border-style: solid; border-color: #d1d5db; padding: 8px; }
.img-upload-area .upload-placeholder { color: var(--text-muted); font-size: 13px; }
.img-upload-area .upload-placeholder i { font-size: 28px; display: block; margin-bottom: 8px; color: #9ca3af; }
.img-upload-area img { max-width: 100%; max-height: 200px; border-radius: 6px; object-fit: contain; }
.img-upload-area .btn-remove-img { position: absolute; top: 4px; right: 4px; background: #ef4444; color: #fff; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; }
.product-thumb { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; }
.product-thumb-placeholder { width: 40px; height: 40px; border-radius: 6px; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 16px; }

@media (max-width: 768px) {
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
            <a href="cat_group.php" class="btn-add" style="background:#6366f1;text-decoration:none;">
                <i class="fas fa-layer-group"></i> Category Groups
            </a>
            <button class="btn-add" onclick="openManageCatModal();" style="background:#8b5cf6;">
                <i class="fas fa-tags"></i> Manage Sub-Categories
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
                        <th style="width:50px">Img</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>QOH</th>
                        <th>Rack</th>
                        <th>Status</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <tr class="no-results"><td colspan="9" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading products...</td></tr>
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
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Image</label>
                    <input type="file" id="fImage" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="previewImage(this);">
                    <div class="img-upload-area" id="imgUploadArea" onclick="document.getElementById('fImage').click();">
                        <div class="upload-placeholder" id="imgPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Click to upload image<br><small>JPG, PNG, GIF, WebP (max 10MB). Will be auto-compressed.</small>
                        </div>
                        <img id="imgPreview" src="" alt="" style="display:none;">
                        <button type="button" class="btn-remove-img" id="btnRemoveImg" style="display:none;" onclick="removeImage(event);"><i class="fas fa-times"></i></button>
                    </div>
                    <input type="hidden" id="fExistingImage" value="">
                    <input type="hidden" id="fRemoveImage" value="0">
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
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">QOH</label>
                        <input type="number" id="fQoh" class="form-control" min="0" placeholder="0" value="0">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Rack</label>
                        <select id="fRackSelect" class="form-select">
                            <option value="">-- Select --</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Rack Remark</label>
                        <input type="text" id="fRack" class="form-control" placeholder="Rack remark (optional)">
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

<!-- Manage Sub Categories Modal -->
<div class="modal fade" id="manageCatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tags"></i> Manage Sub Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:13px;">Category groups are managed from the <a href="cat_group.php">Category Groups</a> page. Here you can manage sub categories under each group.</p>
                <div class="manage-add-row">
                    <select id="newSubCatParent" style="max-width:200px;">
                        <option value="">Category group...</option>
                    </select>
                    <input type="text" id="newSubCatName" placeholder="New sub category name">
                    <button class="btn btn-sm btn-success" onclick="createSubCategory();"><i class="fas fa-plus"></i> Add</button>
                </div>
                <div class="manage-list" id="subCatListManage"></div>
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
var racksCache = [];

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

    document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="9" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>';

    $.ajax({
        type: 'POST', url: 'product_ajax.php',
        data: { action: 'list', page: page, per_page: 50, search: search, cat: cat, status: status },
        dataType: 'json',
        success: function(data) {
            renderTable(data);
            renderPagination(data);
        },
        error: function() {
            document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="9"><i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px;display:block;"></i>Failed to load products</td></tr>';
        }
    });
}

function renderTable(data) {
    var tbody = document.getElementById('dataBody');
    var products = data.products || [];
    var offset = (data.page - 1) * data.per_page;

    if (products.length === 0) {
        tbody.innerHTML = '<tr class="no-results"><td colspan="9"><i class="fas fa-boxes-stacked" style="font-size:24px;margin-bottom:8px;display:block;"></i>No products found</td></tr>';
        document.getElementById('itemCount').textContent = '0 product(s)';
        return;
    }

    var html = '';
    for (var i = 0; i < products.length; i++) {
        var p = products[i];
        var isActive = (p.checked || 'Y') === 'Y';
        var qoh = parseFloat(p.qoh || 0);

        html += '<tr>';
        html += '<td>' + (offset + i + 1) + '</td>';
        if (p.image) {
            html += '<td><img src="../product_img/' + escHtml(p.image) + '" class="product-thumb" loading="lazy"></td>';
        } else {
            html += '<td><div class="product-thumb-placeholder"><i class="fas fa-image"></i></div></td>';
        }
        html += '<td><strong>' + escHtml(p.barcode || '') + '</strong></td>';
        html += '<td>' + escHtml(p.name || '') + '</td>';
        html += '<td>' + escHtml(p.cat || '') + '</td>';
        html += '<td>' + Math.round(qoh) + '</td>';
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

    // Load racks from rack table for select dropdown
    $.post('product_ajax.php', { action: 'rack_list' }, function(racks) {
        racksCache = racks || [];
        refreshRackSelect();
    }, 'json');
}

function refreshCatSelect() {
    var sel = document.getElementById('fCat');
    var val = sel.value;
    sel.innerHTML = '<option value="">-- Select --</option>';
    categoriesCache.forEach(function(c) {
        // value = ccode (cat_code), display = cat_name, store id as data attr
        sel.innerHTML += '<option value="' + escHtml(c.ccode) + '" data-name="' + escHtml(c.name) + '" data-id="' + c.id + '">' + escHtml(c.name) + '</option>';
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

function refreshRackSelect() {
    var sel = document.getElementById('fRackSelect');
    var val = sel.value;
    sel.innerHTML = '<option value="">-- Select --</option>';
    racksCache.forEach(function(r) {
        sel.innerHTML += '<option value="' + escHtml(r.code) + '">' + escHtml(r.code) + (r.description ? ' - ' + escHtml(r.description) : '') + '</option>';
    });
    sel.value = val;
}

function loadSubCatOptions(preselectSubCode) {
    var catCode = document.getElementById('fCat').value;
    var sel = document.getElementById('fSubCat');
    sel.innerHTML = '<option value="">-- Select --</option>';

    if (!catCode) return;

    // Find category group id from ccode
    var catObj = categoriesCache.find(function(c) { return c.ccode === catCode; });
    if (!catObj) return;

    $.post('product_ajax.php', { action: 'subcat_list', category_id: catObj.id }, function(subs) {
        (subs || []).forEach(function(s) {
            // value = sub_code, display = sub_cat name
            sel.innerHTML += '<option value="' + escHtml(s.sub_code) + '" data-name="' + escHtml(s.name) + '">' + escHtml(s.name) + '</option>';
        });
        if (preselectSubCode) sel.value = preselectSubCode;
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
    document.getElementById('fQoh').value = '0';
    document.getElementById('fRackSelect').value = '';
    document.getElementById('fRack').value = '';
    document.getElementById('fChecked').value = 'Y';
    document.getElementById('fBarcode').disabled = false;
    // Reset image
    document.getElementById('fImage').value = '';
    document.getElementById('fExistingImage').value = '';
    document.getElementById('fRemoveImage').value = '0';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgPreview').src = '';
    document.getElementById('imgPlaceholder').style.display = '';
    document.getElementById('btnRemoveImg').style.display = 'none';
    document.getElementById('imgUploadArea').classList.remove('has-image');
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
            document.getElementById('fUom').value = data.uom || '';
            document.getElementById('fQoh').value = data.qoh || '0';
            // Try to match rack text to a rack code in the select
            var rackVal = data.rack || '';
            document.getElementById('fRackSelect').value = rackVal;
            document.getElementById('fRack').value = rackVal;
            document.getElementById('fChecked').value = data.checked || 'Y';

            // Set category by cat_code, then load sub-cats and set by sub_code
            var catCode = data.cat_code || '';
            var subCode = data.sub_code || '';
            if (catCode) {
                document.getElementById('fCat').value = catCode;
                loadSubCatOptions(subCode);
            } else if (data.cat) {
                // Fallback: try to match by name for legacy products
                var catObj = categoriesCache.find(function(c) { return c.name === data.cat; });
                if (catObj) {
                    document.getElementById('fCat').value = catObj.ccode;
                    loadSubCatOptions(subCode);
                }
            }

            // Show existing image
            showExistingImage(data.image || '');

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

    var rackSelect = document.getElementById('fRackSelect').value.trim();
    var rackRemark = document.getElementById('fRack').value.trim();
    // Use rack select value if chosen, otherwise fall back to rack remark
    var rackValue = rackSelect || rackRemark;

    // Resolve cat_code / sub_code and text names
    var catSel = document.getElementById('fCat');
    var subSel = document.getElementById('fSubCat');
    var catCode = catSel.value.trim();
    var subCode = subSel.value.trim();
    var catName = catSel.selectedOptions[0] ? (catSel.selectedOptions[0].getAttribute('data-name') || catSel.selectedOptions[0].textContent) : '';
    var subName = subSel.selectedOptions[0] ? (subSel.selectedOptions[0].getAttribute('data-name') || subSel.selectedOptions[0].textContent) : '';
    if (catSel.value === '') { catName = ''; catCode = ''; }
    if (subSel.value === '') { subName = ''; subCode = ''; }

    var formData = new FormData();
    formData.append('action', editId ? 'update' : 'create');
    formData.append('barcode', barcode);
    formData.append('code', document.getElementById('fCode').value.trim());
    formData.append('name', name);
    formData.append('description', document.getElementById('fDescription').value.trim());
    formData.append('cat', catName);
    formData.append('sub_cat', subName);
    formData.append('cat_code', catCode);
    formData.append('sub_code', subCode);
    formData.append('uom', document.getElementById('fUom').value.trim());
    formData.append('qoh', document.getElementById('fQoh').value);
    formData.append('rack', rackValue);
    formData.append('checked', document.getElementById('fChecked').value);
    if (editId) formData.append('id', editId);

    // Image upload
    var imageFile = document.getElementById('fImage').files[0];
    if (imageFile) {
        formData.append('product_image', imageFile);
    }
    formData.append('existing_image', document.getElementById('fExistingImage').value);
    formData.append('remove_image', document.getElementById('fRemoveImage').value);

    $.ajax({
        type: 'POST', url: 'product_ajax.php', data: formData, dataType: 'json',
        processData: false, contentType: false,
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
    loadManageSubCategories();
    manageCatModal.show();
}

// ===================== SUB CATEGORY MANAGEMENT =====================

function loadManageSubCategories() {
    $.post('product_ajax.php', { action: 'subcat_list' }, function(subs) {
        var all = subs || [];
        var html = '';
        all.forEach(function(s) {
            html += '<div class="manage-item">';
            html += '<span class="item-name">';
            html += '<small style="color:var(--text-muted);">[' + escHtml(s.cat_name || '') + ']</small> ' + escHtml(s.name);
            html += '</span>';
            html += '<div class="item-actions">';
            html += '<button style="background:#3b82f6;" onclick="editSubCategory(' + s.id + ',\'' + escHtml(s.name).replace(/'/g, "\\'") + '\');"><i class="fas fa-pen"></i></button>';
            html += '<button style="background:#ef4444;" onclick="deleteSubCategory(' + s.id + ');"><i class="fas fa-ban"></i></button>';
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

function editSubCategory(id, oldName) {
    Swal.fire({
        title: 'Edit Sub Category',
        input: 'text',
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonText: 'Save',
        inputValidator: function(v) { if (!v || !v.trim()) return 'Name is required.'; }
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('product_ajax.php', { action: 'subcat_update', id: id, name: result.value.trim() }, function(r) {
                if (r.success) { loadManageSubCategories(); }
                else Swal.fire({ icon: 'error', text: r.error });
            }, 'json');
        }
    });
}

function deleteSubCategory(id) {
    Swal.fire({
        text: 'Delete this sub category?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', confirmButtonText: 'Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('product_ajax.php', { action: 'subcat_delete', id: id }, function(r) {
                if (r.success) { loadManageSubCategories(); }
                else Swal.fire({ icon: 'error', text: r.error });
            }, 'json');
        }
    });
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

// ===================== IMAGE UPLOAD =====================

function previewImage(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        // Validate size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire({ icon: 'warning', text: 'Image must be smaller than 10MB.' });
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('imgPreview').style.display = '';
            document.getElementById('imgPlaceholder').style.display = 'none';
            document.getElementById('btnRemoveImg').style.display = 'flex';
            document.getElementById('imgUploadArea').classList.add('has-image');
            document.getElementById('fRemoveImage').value = '0';
        };
        reader.readAsDataURL(file);
    }
}

function removeImage(e) {
    e.stopPropagation();
    document.getElementById('fImage').value = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('imgPreview').src = '';
    document.getElementById('imgPlaceholder').style.display = '';
    document.getElementById('btnRemoveImg').style.display = 'none';
    document.getElementById('imgUploadArea').classList.remove('has-image');
    document.getElementById('fRemoveImage').value = '1';
}

function showExistingImage(imageName) {
    if (imageName) {
        document.getElementById('fExistingImage').value = imageName;
        document.getElementById('imgPreview').src = '../product_img/' + imageName;
        document.getElementById('imgPreview').style.display = '';
        document.getElementById('imgPlaceholder').style.display = 'none';
        document.getElementById('btnRemoveImg').style.display = 'flex';
        document.getElementById('imgUploadArea').classList.add('has-image');
    }
}

// Modal autofocus
document.getElementById('productModal').addEventListener('shown.bs.modal', function() {
    var el = document.getElementById('fBarcode');
    if (!el.disabled) el.focus(); else document.getElementById('fName').focus();
});
</script>
</body>
</html>
