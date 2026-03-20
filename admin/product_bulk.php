<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'product_bulk';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk Product Management</title>
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
.data-table tbody tr.selected { background: #eff6ff; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-active { background: #dcfce7; color: #16a34a; }
.badge-inactive { background: #fee2e2; color: #dc2626; }
.product-thumb { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; }
.product-thumb-placeholder { width: 36px; height: 36px; border-radius: 6px; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 14px; }

/* Checkbox styling */
.bulk-check { width: 16px; height: 16px; cursor: pointer; accent-color: var(--primary); }

/* Pagination */
.pagination-wrap { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; flex-wrap: wrap; gap: 8px; }
.pagination-info { font-size: 13px; color: var(--text-muted); }
.pagination-btns { display: flex; gap: 4px; }
.pagination-btns button { padding: 6px 12px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); color: var(--text); }
.pagination-btns button:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
.pagination-btns button.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.pagination-btns button:disabled { opacity: 0.4; cursor: default; }

/* Bulk action bar */
.bulk-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #1a1a2e; color: #fff; padding: 14px 24px; display: flex; align-items: center; justify-content: center; gap: 12px; z-index: 999; transform: translateY(100%); transition: transform 0.3s ease; box-shadow: 0 -4px 20px rgba(0,0,0,0.2); flex-wrap: wrap; }
.bulk-bar.visible { transform: translateY(0); }
.bulk-bar .bar-info { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500; }
.bulk-bar .bar-count { background: var(--primary); font-weight: 700; padding: 2px 12px; border-radius: 10px; font-size: 13px; }
.bulk-bar .bar-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.bulk-bar .bar-actions button { border: none; padding: 8px 18px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-bulk-edit { background: #3b82f6; color: #fff; }
.btn-bulk-edit:hover { background: #2563eb; }
.btn-bulk-activate { background: #16a34a; color: #fff; }
.btn-bulk-activate:hover { background: #15803d; }
.btn-bulk-deactivate { background: #ef4444; color: #fff; }
.btn-bulk-deactivate:hover { background: #dc2626; }
.btn-bulk-cancel { background: transparent; color: #9ca3af; border: 1px solid #4b5563 !important; }
.btn-bulk-cancel:hover { color: #fff; border-color: #fff !important; }

/* Modal */
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* Inline editable cells */
.inline-edit-select { padding: 4px 6px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; outline: none; background: #fff; max-width: 140px; width: 100%; }
.inline-edit-select:focus { border-color: var(--primary); }

/* Loading */
.table-loading { text-align: center; padding: 40px; color: var(--text-muted); }
.table-loading i { font-size: 24px; margin-bottom: 8px; display: block; }

/* Selection info */
.select-all-banner { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 16px; margin-bottom: 12px; font-size: 13px; display: none; align-items: center; justify-content: center; gap: 8px; }
.select-all-banner a { color: var(--primary); font-weight: 600; cursor: pointer; text-decoration: underline; }

@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
    .toolbar-filters { flex-direction: column; align-items: stretch; }
    .filter-select { width: 100%; }
    .bulk-bar { padding: 10px 16px; }
    .bulk-bar .bar-actions button { padding: 6px 12px; font-size: 12px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-layer-group" style="color:var(--primary);margin-right:8px;"></i>Bulk Product Management</h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="product.php" class="btn-back" style="background:#6b7280;color:#fff;border:none;padding:9px 20px;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="toolbar-filters">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search products..." oninput="debouncedFetch();">
                </div>
                <select id="filterCategory" class="filter-select" onchange="loadSubCategoryFilter(); fetchProducts(1);">
                    <option value="">All Categories</option>
                </select>
                <select id="filterSubCategory" class="filter-select" onchange="fetchProducts(1);">
                    <option value="">All Sub Categories</option>
                </select>
                <select id="filterStatus" class="filter-select" onchange="fetchProducts(1);">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="item-count" id="itemCount">Loading...</div>
        </div>

        <div class="select-all-banner" id="selectAllBanner">
            <i class="fas fa-info-circle" style="color:#3b82f6;"></i>
            <span id="selectAllText"></span>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" class="bulk-check" id="checkAll" onchange="toggleCheckAll(this);"></th>
                        <th style="width:30px">No</th>
                        <th style="width:44px">Img</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>UOM</th>
                        <th>QOH</th>
                        <th>Rack</th>
                        <th>Status</th>
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

<!-- Bulk Action Bar -->
<div class="bulk-bar" id="bulkBar">
    <div class="bar-info">
        <i class="fas fa-check-double"></i>
        <span><span class="bar-count" id="selectedCount">0</span> product(s) selected</span>
    </div>
    <div class="bar-actions">
        <button class="btn-bulk-edit" onclick="openBulkEditModal();"><i class="fas fa-pen"></i> Bulk Edit</button>
        <button class="btn-bulk-deactivate" onclick="bulkDelete();"><i class="fas fa-trash"></i> Delete</button>
        <button class="btn-bulk-cancel" onclick="clearSelection();"><i class="fas fa-times"></i> Clear</button>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen-to-square" style="color:var(--primary);margin-right:6px;"></i> Bulk Edit Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;">
                    <i class="fas fa-info-circle" style="color:#0284c7;margin-right:4px;"></i>
                    Only fields you change below will be updated. Leave a field as "-- No Change --" to keep existing values.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category</label>
                    <select id="bulkCat" class="form-select">
                        <option value="">-- No Change --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub Category</label>
                    <select id="bulkSubCat" class="form-select">
                        <option value="">-- No Change --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">UOM</label>
                    <select id="bulkUom" class="form-select">
                        <option value="">-- No Change --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Rack</label>
                    <select id="bulkRack" class="form-select">
                        <option value="">-- No Change --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select id="bulkStatus" class="form-select">
                        <option value="">-- No Change --</option>
                        <option value="Y">Active</option>
                        <option value="N">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="applyBulkEdit();"><i class="fas fa-check"></i> Apply to <span id="bulkEditCount">0</span> Product(s)</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var bulkEditModal = null;
var currentPage = 1;
var debounceTimer = null;
var selectedIds = {};
var totalProducts = 0;
var allSelected = false;

// Caches
var categoriesCache = [];
var uomCache = [];
var racksCache = [];

document.addEventListener('DOMContentLoaded', function() {
    bulkEditModal = new bootstrap.Modal(document.getElementById('bulkEditModal'));
    loadCategoryFilter();
    loadDropdowns();
    fetchProducts(1);
});

// ===================== FETCH & RENDER =====================

function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function() { fetchProducts(1); }, 300);
}

function fetchProducts(page) {
    currentPage = page;
    var search = document.getElementById('searchInput').value.trim();
    var cat = document.getElementById('filterCategory').value;
    var subCat = document.getElementById('filterSubCategory').value;
    var status = document.getElementById('filterStatus').value;

    document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="10" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>';

    $.ajax({
        type: 'POST', url: 'product_ajax.php',
        data: { action: 'list', page: page, per_page: 50, search: search, cat: cat, sub_cat: subCat, status: status },
        dataType: 'json',
        success: function(data) {
            totalProducts = data.total || 0;
            renderTable(data);
            renderPagination(data);
            updateBulkBar();
            updateCheckAllState();
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
        var isChecked = !!selectedIds[p.id];

        html += '<tr class="' + (isChecked ? 'selected' : '') + '">';
        html += '<td><input type="checkbox" class="bulk-check row-check" data-id="' + p.id + '" ' + (isChecked ? 'checked' : '') + ' onchange="toggleRow(this);"></td>';
        html += '<td>' + (offset + i + 1) + '</td>';
        if (p.image) {
            html += '<td><img src="../img/' + escHtml(p.image) + '" class="product-thumb" loading="lazy"></td>';
        } else {
            html += '<td><div class="product-thumb-placeholder"><i class="fas fa-image"></i></div></td>';
        }
        html += '<td><strong>' + escHtml(p.barcode || '') + '</strong></td>';
        html += '<td>' + escHtml(p.name || '') + '</td>';
        html += '<td>' + escHtml(p.cat || '') + '</td>';
        html += '<td>' + escHtml(p.uom || '') + '</td>';
        html += '<td>' + Math.round(parseFloat(p.qoh || 0)) + '</td>';
        html += '<td>' + escHtml(p.rack || '') + '</td>';
        html += '<td><span class="badge-status ' + (isActive ? 'badge-active' : 'badge-inactive') + '">' + (isActive ? 'Active' : 'Inactive') + '</span></td>';
        html += '</tr>';
    }
    tbody.innerHTML = html;
    document.getElementById('itemCount').textContent = data.total + ' product(s)';
}

function renderPagination(data) {
    var wrap = document.getElementById('paginationWrap');
    var info = document.getElementById('paginationInfo');
    var btns = document.getElementById('paginationBtns');

    if (data.pages <= 1) { wrap.style.display = 'none'; return; }

    wrap.style.display = 'flex';
    var start = (data.page - 1) * data.per_page + 1;
    var end = Math.min(data.page * data.per_page, data.total);
    info.textContent = 'Showing ' + start + '-' + end + ' of ' + data.total;

    var html = '';
    html += '<button ' + (data.page <= 1 ? 'disabled' : '') + ' onclick="fetchProducts(' + (data.page - 1) + ');">&laquo; Prev</button>';
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

// ===================== SELECTION LOGIC =====================

function toggleRow(cb) {
    var id = parseInt(cb.getAttribute('data-id'));
    if (cb.checked) {
        selectedIds[id] = true;
    } else {
        delete selectedIds[id];
        allSelected = false;
    }
    cb.closest('tr').classList.toggle('selected', cb.checked);
    updateBulkBar();
    updateCheckAllState();
}

function toggleCheckAll(cb) {
    var checkboxes = document.querySelectorAll('.row-check');
    checkboxes.forEach(function(c) {
        c.checked = cb.checked;
        var id = parseInt(c.getAttribute('data-id'));
        if (cb.checked) {
            selectedIds[id] = true;
            c.closest('tr').classList.add('selected');
        } else {
            delete selectedIds[id];
            c.closest('tr').classList.remove('selected');
        }
    });

    if (!cb.checked) {
        allSelected = false;
    }

    updateBulkBar();
    updateSelectAllBanner();
}

function updateCheckAllState() {
    var checkboxes = document.querySelectorAll('.row-check');
    var allChecked = checkboxes.length > 0;
    checkboxes.forEach(function(c) {
        if (!c.checked) allChecked = false;
    });
    document.getElementById('checkAll').checked = allChecked;
    updateSelectAllBanner();
}

function updateSelectAllBanner() {
    var banner = document.getElementById('selectAllBanner');
    var count = getSelectedCount();
    var checkboxes = document.querySelectorAll('.row-check');
    var allPageChecked = checkboxes.length > 0;
    checkboxes.forEach(function(c) { if (!c.checked) allPageChecked = false; });

    if (allPageChecked && totalProducts > checkboxes.length && !allSelected) {
        banner.style.display = 'flex';
        document.getElementById('selectAllText').innerHTML =
            'All ' + checkboxes.length + ' products on this page are selected. <a onclick="selectAllProducts();">Select all ' + totalProducts + ' products matching this filter</a>';
    } else if (allSelected) {
        banner.style.display = 'flex';
        document.getElementById('selectAllText').innerHTML =
            'All ' + totalProducts + ' products matching this filter are selected. <a onclick="clearSelection();">Clear selection</a>';
    } else {
        banner.style.display = 'none';
    }
}

function selectAllProducts() {
    allSelected = true;
    // Fetch all matching IDs
    var search = document.getElementById('searchInput').value.trim();
    var cat = document.getElementById('filterCategory').value;
    var status = document.getElementById('filterStatus').value;

    $.ajax({
        type: 'POST', url: 'product_ajax.php',
        data: { action: 'bulk_get_ids', search: search, cat: cat, status: status },
        dataType: 'json',
        success: function(data) {
            selectedIds = {};
            (data.ids || []).forEach(function(id) {
                selectedIds[id] = true;
            });
            // Check all visible checkboxes
            document.querySelectorAll('.row-check').forEach(function(c) {
                c.checked = true;
                c.closest('tr').classList.add('selected');
            });
            document.getElementById('checkAll').checked = true;
            updateBulkBar();
            updateSelectAllBanner();
        }
    });
}

function clearSelection() {
    selectedIds = {};
    allSelected = false;
    document.querySelectorAll('.row-check').forEach(function(c) {
        c.checked = false;
        c.closest('tr').classList.remove('selected');
    });
    document.getElementById('checkAll').checked = false;
    updateBulkBar();
    updateSelectAllBanner();
}

function getSelectedCount() {
    return Object.keys(selectedIds).length;
}

function getSelectedIdsArray() {
    return Object.keys(selectedIds).map(function(k) { return parseInt(k); });
}

function updateBulkBar() {
    var count = getSelectedCount();
    document.getElementById('selectedCount').textContent = count;
    var bar = document.getElementById('bulkBar');
    if (count > 0) {
        bar.classList.add('visible');
    } else {
        bar.classList.remove('visible');
    }
}

// ===================== BULK ACTIONS =====================

function bulkDelete() {
    var ids = getSelectedIdsArray();
    if (ids.length === 0) return;

    Swal.fire({
        title: 'Permanently Delete ' + ids.length + ' Product(s)?',
        html: '<div style="color:#ef4444;font-weight:600;margin-bottom:8px;"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone!</div>' +
              'All selected products and their images will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Delete Permanently',
        input: 'text',
        inputLabel: 'Type DELETE to confirm',
        inputValidator: function(v) { if (v !== 'DELETE') return 'Please type DELETE to confirm.'; }
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'product_ajax.php',
                data: { action: 'bulk_delete', ids: JSON.stringify(ids) },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                            clearSelection();
                            fetchProducts(currentPage);
                            loadCategoryFilter();
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', text: 'Network error. Please try again.' });
                }
            });
        }
    });
}

function openBulkEditModal() {
    var count = getSelectedCount();
    if (count === 0) return;
    document.getElementById('bulkEditCount').textContent = count;

    // Reset form
    document.getElementById('bulkCat').value = '';
    document.getElementById('bulkSubCat').innerHTML = '<option value="">-- No Change --</option>';
    document.getElementById('bulkUom').value = '';
    document.getElementById('bulkRack').value = '';
    document.getElementById('bulkStatus').value = '';

    // Populate dropdowns
    var catSel = document.getElementById('bulkCat');
    catSel.innerHTML = '<option value="">-- No Change --</option>';
    categoriesCache.forEach(function(c) {
        catSel.innerHTML += '<option value="' + escHtml(c.ccode) + '" data-name="' + escHtml(c.name) + '" data-id="' + c.id + '">' + escHtml(c.name) + '</option>';
    });

    var uomSel = document.getElementById('bulkUom');
    uomSel.innerHTML = '<option value="">-- No Change --</option>';
    uomCache.forEach(function(u) {
        uomSel.innerHTML += '<option value="' + escHtml(u.name) + '">' + escHtml(u.name) + '</option>';
    });

    var rackSel = document.getElementById('bulkRack');
    rackSel.innerHTML = '<option value="">-- No Change --</option>';
    rackSel.innerHTML += '<option value="__CLEAR__">-- Clear Rack --</option>';
    racksCache.forEach(function(r) {
        rackSel.innerHTML += '<option value="' + escHtml(r.code) + '">' + escHtml(r.code) + (r.description ? ' - ' + escHtml(r.description) : '') + '</option>';
    });

    bulkEditModal.show();
}

// Load sub-categories when bulk category changes
document.getElementById('bulkCat').addEventListener('change', function() {
    var catCode = this.value;
    var sel = document.getElementById('bulkSubCat');
    sel.innerHTML = '<option value="">-- No Change --</option>';
    if (!catCode) return;

    var catObj = categoriesCache.find(function(c) { return c.ccode === catCode; });
    if (!catObj) return;

    $.post('product_ajax.php', { action: 'subcat_list', category_id: catObj.id }, function(subs) {
        (subs || []).forEach(function(s) {
            sel.innerHTML += '<option value="' + escHtml(s.sub_code) + '" data-name="' + escHtml(s.name) + '">' + escHtml(s.name) + '</option>';
        });
    }, 'json');
});

function applyBulkEdit() {
    var ids = getSelectedIdsArray();
    if (ids.length === 0) return;

    var catSel = document.getElementById('bulkCat');
    var subSel = document.getElementById('bulkSubCat');
    var updates = {};
    var hasChange = false;

    if (catSel.value !== '') {
        updates.cat_code = catSel.value;
        updates.cat = catSel.selectedOptions[0] ? (catSel.selectedOptions[0].getAttribute('data-name') || catSel.selectedOptions[0].textContent) : '';
        hasChange = true;
    }
    if (subSel.value !== '') {
        updates.sub_code = subSel.value;
        updates.sub_cat = subSel.selectedOptions[0] ? (subSel.selectedOptions[0].getAttribute('data-name') || subSel.selectedOptions[0].textContent) : '';
        hasChange = true;
    }
    if (document.getElementById('bulkUom').value !== '') {
        updates.uom = document.getElementById('bulkUom').value;
        hasChange = true;
    }
    var rackVal = document.getElementById('bulkRack').value;
    if (rackVal !== '') {
        updates.rack = rackVal === '__CLEAR__' ? '' : rackVal;
        hasChange = true;
    }
    if (document.getElementById('bulkStatus').value !== '') {
        updates.checked = document.getElementById('bulkStatus').value;
        hasChange = true;
    }

    if (!hasChange) {
        Swal.fire({ icon: 'info', text: 'No changes selected. Please modify at least one field.' });
        return;
    }

    Swal.fire({
        title: 'Apply Bulk Edit?',
        text: 'Update ' + ids.length + ' product(s) with the selected changes?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        confirmButtonText: 'Yes, Apply'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'product_ajax.php',
                data: { action: 'bulk_edit', ids: JSON.stringify(ids), updates: JSON.stringify(updates) },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        bulkEditModal.hide();
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                            clearSelection();
                            fetchProducts(currentPage);
                            loadCategoryFilter();
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', text: 'Network error. Please try again.' });
                }
            });
        }
    });
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

function loadSubCategoryFilter() {
    var cat = document.getElementById('filterCategory').value;
    var sel = document.getElementById('filterSubCategory');
    sel.innerHTML = '<option value="">All Sub Categories</option>';
    if (!cat) return;

    $.post('product_ajax.php', { action: 'subcategories', cat: cat }, function(subs) {
        (subs || []).forEach(function(s) {
            sel.innerHTML += '<option value="' + escHtml(s) + '">' + escHtml(s) + '</option>';
        });
    }, 'json');
}

function loadDropdowns() {
    $.post('product_ajax.php', { action: 'cat_list' }, function(cats) {
        categoriesCache = (cats || []).filter(function(c) { return c.status === 'ACTIVE'; });
    }, 'json');

    $.post('product_ajax.php', { action: 'uom_list' }, function(uoms) {
        uomCache = (uoms || []).filter(function(u) { return u.status === 'ACTIVE'; });
    }, 'json');

    $.post('product_ajax.php', { action: 'rack_list' }, function(racks) {
        racksCache = racks || [];
    }, 'json');
}

// ===================== HELPERS =====================

function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}
</script>
</body>
</html>
