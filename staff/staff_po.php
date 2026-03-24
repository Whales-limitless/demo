<?php
require_once __DIR__ . '/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$currentPage = 'staff_po';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Orders</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="components.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root {
    --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6;
    --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px;
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.po-page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.po-page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.po-page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.po-search-box { position: relative; flex: 1; max-width: 320px; }
.po-search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.po-search-box input:focus { border-color: var(--primary); }
.po-search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-draft { background: #e5e7eb; color: #374151; }
.badge-approved { background: #dbeafe; color: #1d4ed8; }
.badge-partially_received { background: #fef3c7; color: #92400e; }
.badge-received { background: #dcfce7; color: #16a34a; }
.badge-closed { background: #f3e8ff; color: #7c3aed; }
.badge-cancelled { background: #fee2e2; color: #dc2626; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
.btn-view { background: #6366f1; } .btn-view:hover { background: #4f46e5; }
.btn-approve { background: #16a34a; } .btn-approve:hover { background: #15803d; }
.btn-cancel-po { background: #ef4444; } .btn-cancel-po:hover { background: #dc2626; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }
.status-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
.status-tab { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: var(--text-muted); transition: all var(--transition); }
.status-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.status-tab:hover:not(.active) { border-color: var(--primary); color: var(--primary); }

/* Product search result items */
.psm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.psm-card { display: flex; flex-direction: column; align-items: center; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.15s; text-align: center; }
.psm-card:hover { border-color: var(--primary); background: #fef2f2; box-shadow: 0 2px 8px rgba(200,16,46,0.1); }
.psm-card-img { width: 64px; height: 64px; border-radius: 8px; object-fit: cover; background: #f3f4f6; margin-bottom: 8px; }
.psm-card-noimg { width: 64px; height: 64px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
.psm-card-noimg i { font-size: 20px; color: #d1d5db; }
.psm-card-name { font-weight: 600; font-size: 12px; line-height: 1.3; margin-bottom: 4px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word; }
.psm-card-meta { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; }
.psm-card-qoh { font-size: 11px; font-weight: 700; }
.psm-card-qoh.in { color: #16a34a; }
.psm-card-qoh.out { color: #dc2626; }
.psm-card-rack { font-size: 10px; color: var(--text-muted); }
.psm-empty { text-align:center; padding:30px 20px; color:var(--text-muted); font-size:13px; }
#psmResultsContainer { max-height: 450px; overflow-y: auto; }
.psm-search-bar { display: flex; gap: 8px; flex-wrap: wrap; }
.psm-search-bar input { flex: 1; min-width: 0; flex-basis: 100%; }
@media (min-width: 576px) { .psm-search-bar input { flex-basis: auto; } }
.psm-search-btn { background: var(--primary); color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.psm-search-btn:hover { background: var(--primary-dark); }
.psm-result-count { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }
.psm-load-more { display: block; width: 100%; padding: 10px; margin-top: 12px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; text-align: center; transition: all 0.15s; }
.psm-load-more:hover { background: #e5e7eb; border-color: var(--primary); color: var(--primary); }

/* Line items table */
.line-items-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 12px; table-layout: fixed; min-width: 700px; }
.line-items-table th { background: #f9fafb; padding: 8px 10px; font-weight: 600; font-size: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; }
.line-items-table td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.line-items-table input { width: 100%; padding: 5px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; font-family: 'DM Sans', sans-serif; }
.line-items-table input:focus { border-color: var(--primary); outline: none; }
.btn-remove-line { background: #fee2e2; color: #dc2626; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-size: 12px; }
.btn-remove-line:hover { background: #fca5a5; }
.li-uom-wrap { display: flex; gap: 4px; align-items: center; }
.li-uom-select { padding: 5px 6px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-family: 'DM Sans', sans-serif; outline: none; width: 100%; }
.li-uom-select:focus { border-color: var(--primary); }
.li-conv-indicator { font-size: 11px; line-height: 1.3; margin-top: 3px; padding: 2px 6px; border-radius: 4px; }
.li-conv-indicator.converted { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.li-conv-indicator.no-conv { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
.li-conv-indicator.same-uom { background: #f3f4f6; color: #6b7280; }

/* New product image upload */
.np-img-upload-area { border: 2px dashed #d1d5db; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all var(--transition); background: #fafbfc; position: relative; }
.np-img-upload-area:hover { border-color: var(--primary); background: #fff5f5; }
.np-img-upload-area.has-image { border-style: solid; border-color: #d1d5db; padding: 8px; }
.np-upload-placeholder { color: var(--text-muted); font-size: 13px; }
.np-btn-remove-img { position: absolute; top: 4px; right: 4px; background: #ef4444; color: #fff; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; }

/* Detail view */
.detail-section { background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
.detail-row { display: flex; gap: 16px; margin-bottom: 6px; font-size: 13px; }
.detail-label { font-weight: 600; min-width: 130px; color: var(--text-muted); }

@media (max-width: 768px) {
    .po-page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .po-search-box { max-width: 100%; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="po-page-content">
    <div class="po-page-header">
        <h1><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>Purchase Orders</h1>
        <button class="btn-add" onclick="openCreateModal();">
            <i class="fas fa-plus"></i> New PO
        </button>
    </div>

    <div class="table-card">
        <div class="status-tabs">
            <div class="status-tab active" data-status="" onclick="filterStatus(this);">All</div>
            <div class="status-tab" data-status="DRAFT" onclick="filterStatus(this);">Draft</div>
            <div class="status-tab" data-status="APPROVED" onclick="filterStatus(this);">Approved</div>
            <div class="status-tab" data-status="PARTIALLY_RECEIVED" onclick="filterStatus(this);">Partial</div>
            <div class="status-tab" data-status="RECEIVED" onclick="filterStatus(this);">Received</div>
            <div class="status-tab" data-status="CANCELLED" onclick="filterStatus(this);">Cancelled</div>
        </div>

        <div class="table-toolbar">
            <div class="po-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="poSearchInput" placeholder="Search PO number, supplier...">
            </div>
            <div class="item-count" id="poItemCount">0 PO(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Date</th>
                        <th>Total (RM)</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="poDataBody">
                    <tr class="no-results"><td colspan="9"><i class="fas fa-spinner fa-spin" style="font-size:24px;margin-bottom:8px;display:block;"></i>Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit PO Modal -->
<div class="modal fade" id="poModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-file-invoice"></i> New Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <select id="fSupplier" class="form-select"></select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Order Date <span class="text-danger">*</span></label>
                        <input type="date" id="fOrderDate" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Expected Date</label>
                        <input type="date" id="fExpectedDate" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Remark</label>
                    <input type="text" id="fRemark" class="form-control" placeholder="Optional remark">
                </div>

                <hr>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label fw-semibold mb-0">Line Items</label>
                    <button type="button" class="btn-add" onclick="openProductSearchModal();"><i class="fas fa-search"></i> Search Product</button>
                </div>

                <div style="overflow-x:auto;">
                    <table class="line-items-table">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Product</th>
                                <th>Barcode</th>
                                <th style="width:90px">UOM</th>
                                <th style="width:140px">Qty</th>
                                <th style="width:140px">QOH Impact</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="lineItems"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="savePO();"><i class="fas fa-check"></i> Save PO</button>
            </div>
        </div>
    </div>
</div>

<!-- View PO Detail Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTitle"><i class="fas fa-file-invoice"></i> PO Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Product Search Modal (Bootstrap modal) -->
<div class="modal fade" id="productSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search" style="color:var(--primary);margin-right:6px;"></i>Search Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="psm-search-bar mb-3">
                    <input type="text" class="form-control" id="psmSearchInput" placeholder="Enter product name or barcode..." autocomplete="off">
                    <button type="button" class="psm-search-btn" onclick="doProductSearch();"><i class="fas fa-search"></i> Search</button>
                    <button type="button" class="btn-add" onclick="openNewProductModal();" style="white-space:nowrap;"><i class="fas fa-plus"></i> New Product</button>
                </div>
                <div id="psmResultsContainer">
                    <div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>Enter a search term and click Search</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add New Product Modal -->
<div class="modal fade" id="newProductModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-box"></i> Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Barcode <span class="text-danger">*</span></label>
                        <input type="text" id="npBarcode" class="form-control" placeholder="Product barcode">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Product Code</label>
                        <input type="text" id="npCode" class="form-control" placeholder="Product code">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" id="npName" class="form-control" placeholder="Product name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea id="npDescription" class="form-control" rows="2" placeholder="Product description"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Image</label>
                    <input type="file" id="npImage" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="npPreviewImage(this);">
                    <div class="np-img-upload-area" id="npImgUploadArea" onclick="document.getElementById('npImage').click();">
                        <div class="np-upload-placeholder" id="npImgPlaceholder">
                            <i class="fas fa-cloud-upload-alt" style="font-size:28px;display:block;margin-bottom:8px;color:#9ca3af;"></i>
                            Click to upload image<br><small>JPG, PNG, GIF, WebP (max 10MB)</small>
                        </div>
                        <img id="npImgPreview" src="" alt="" style="display:none;max-width:100%;max-height:200px;border-radius:6px;object-fit:contain;">
                        <button type="button" class="np-btn-remove-img" id="npBtnRemoveImg" style="display:none;" onclick="npRemoveImage(event);"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select id="npCat" class="form-select" onchange="npLoadSubCatOptions();"></select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Sub Category</label>
                        <select id="npSubCat" class="form-select"><option value="">-- Select --</option></select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">UOM</label>
                        <select id="npUom" class="form-select"><option value="">-- Select --</option></select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">QOH</label>
                        <input type="number" id="npQoh" class="form-control" min="0" placeholder="0" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Rack</label>
                        <select id="npRackSelect" class="form-select"><option value="">-- Select --</option></select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Rack Remark</label>
                        <input type="text" id="npRackRemark" class="form-control" placeholder="Rack remark (optional)">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveNewProduct();"><i class="fas fa-check"></i> Save & Add to PO</button>
            </div>
        </div>
    </div>
</div>

<?php include('mobile-bottombar.php'); ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var poModal = null, viewModalObj = null, productSearchModal = null, newProductModal = null;
var currentStatus = '';
var lineItemIndex = 0;

document.addEventListener('DOMContentLoaded', function() {
    poModal = new bootstrap.Modal(document.getElementById('poModal'));
    viewModalObj = new bootstrap.Modal(document.getElementById('viewModal'));
    productSearchModal = new bootstrap.Modal(document.getElementById('productSearchModal'));
    newProductModal = new bootstrap.Modal(document.getElementById('newProductModal'));
    // Auto-focus search input when product search modal opens
    document.getElementById('productSearchModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('psmSearchInput').focus();
    });
    // Clear search when modal closes
    document.getElementById('productSearchModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('psmSearchInput').value = '';
        document.getElementById('psmResultsContainer').innerHTML = '<div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>Enter a search term and click Search</div>';
    });
    loadPOs();
});

// ==================== LOAD PO LIST ====================
function loadPOs() {
    $.ajax({
        type: 'POST', url: 'staff_po_ajax.php', data: { action: 'list_po', status: currentStatus }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            renderPOTable(data.pos || []);
        }
    });
}

function renderPOTable(pos) {
    var body = document.getElementById('poDataBody');
    if (pos.length === 0) {
        body.innerHTML = '<tr class="no-results"><td colspan="9"><i class="fas fa-file-invoice" style="font-size:24px;margin-bottom:8px;display:block;"></i>No purchase orders found</td></tr>';
        document.getElementById('poItemCount').textContent = '0 PO(s)';
        return;
    }

    var html = '';
    pos.forEach(function(po, i) {
        var statusClass = 'badge-' + (po.status || '').toLowerCase();
        var search = ((po.po_number || '') + ' ' + (po.supplier_name || '') + ' ' + (po.created_by || '')).toLowerCase();
        html += '<tr data-search="' + escHtml(search) + '">';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td><strong>' + escHtml(po.po_number) + '</strong></td>';
        html += '<td>' + escHtml(po.supplier_name || '-') + '</td>';
        html += '<td>' + escHtml(po.order_date || '-') + '</td>';
        html += '<td>' + escHtml(po.expected_date || '-') + '</td>';
        html += '<td>' + parseFloat(po.total_amount || 0).toFixed(2) + '</td>';
        html += '<td><span class="badge-status ' + statusClass + '">' + escHtml(po.status) + '</span></td>';
        html += '<td>' + escHtml(po.created_by || '-') + '</td>';
        html += '<td style="white-space:nowrap">';
        html += '<button class="btn-action btn-view" onclick="viewPO(' + po.id + ');"><i class="fas fa-eye"></i></button> ';
        if (po.status === 'DRAFT') {
            html += '<button class="btn-action btn-edit" onclick="editPO(' + po.id + ');"><i class="fas fa-pen"></i></button> ';
            html += '<button class="btn-action btn-approve" onclick="approvePO(' + po.id + ', \'' + escHtml(po.po_number) + '\');"><i class="fas fa-check"></i></button> ';
            html += '<button class="btn-action btn-cancel-po" onclick="cancelPO(' + po.id + ', \'' + escHtml(po.po_number) + '\');"><i class="fas fa-ban"></i></button>';
        } else if (po.status === 'APPROVED') {
            html += '<button class="btn-action btn-cancel-po" onclick="cancelPO(' + po.id + ', \'' + escHtml(po.po_number) + '\');"><i class="fas fa-ban"></i></button>';
        }
        html += '</td>';
        html += '</tr>';
    });
    body.innerHTML = html;
    document.getElementById('poItemCount').textContent = pos.length + ' PO(s)';
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ==================== STATUS FILTER ====================
function filterStatus(el) {
    document.querySelectorAll('.status-tab').forEach(function(t) { t.classList.remove('active'); });
    el.classList.add('active');
    currentStatus = el.getAttribute('data-status');
    loadPOs();
}

// ==================== SEARCH ====================
document.getElementById('poSearchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var rows = document.querySelectorAll('#poDataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        if (d.indexOf(q) > -1) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('poItemCount').textContent = count + ' PO(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
});

// ==================== CREATE / EDIT MODAL ====================
function loadSuppliers(callback) {
    $.ajax({
        type: 'POST', url: 'staff_po_ajax.php', data: { action: 'list_suppliers' }, dataType: 'json',
        success: function(data) {
            var sel = document.getElementById('fSupplier');
            sel.innerHTML = '<option value="">-- Select Supplier --</option>';
            (data.suppliers || []).forEach(function(s) {
                sel.innerHTML += '<option value="' + s.id + '">' + escHtml(s.code + ' - ' + s.name) + '</option>';
            });
            if (callback) callback();
        }
    });
}

function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('fSupplier').value = '';
    document.getElementById('fOrderDate').value = new Date().toISOString().slice(0, 10);
    document.getElementById('fExpectedDate').value = '';
    document.getElementById('fRemark').value = '';
    document.getElementById('lineItems').innerHTML = '';
    lineItemIndex = 0;
}

function openCreateModal() {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-invoice"></i> New Purchase Order';
    loadPoUomCache();
    loadSuppliers(function() {
        poModal.show();
    });
}

function editPO(id) {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-invoice"></i> Edit Purchase Order';
    document.getElementById('editId').value = id;

    loadPoUomCache();
    loadSuppliers(function() {
        $.ajax({
            type: 'POST', url: 'staff_po_ajax.php', data: { action: 'get_po', id: id }, dataType: 'json',
            success: function(data) {
                if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
                var po = data.po;
                document.getElementById('fSupplier').value = po.supplier_id;
                document.getElementById('fOrderDate').value = po.order_date || '';
                document.getElementById('fExpectedDate').value = po.expected_date || '';
                document.getElementById('fRemark').value = po.remark || '';

                (data.items || []).forEach(function(item) {
                    addLineItem(item.barcode, item.product_desc, item.uom, item.qty_ordered);
                });
                poModal.show();
            }
        });
    });
}

// ==================== LINE ITEMS ====================
var poUomCache = [];
var poUomLoaded = false;

function loadPoUomCache(callback) {
    if (poUomLoaded) { if (callback) callback(); return; }
    $.post('staff_po_ajax.php', { action: 'uom_list' }, function(uoms) {
        poUomCache = (uoms || []).filter(function(u) { return u.status === 'ACTIVE'; });
        poUomLoaded = true;
        if (callback) callback();
    }, 'json');
}

function buildUomSelect(idx, selectedUom) {
    var html = '<select class="li-uom-select li-uom" onchange="onLineUomChange(' + idx + ');">';
    var found = false;
    poUomCache.forEach(function(u) {
        var sel = (u.name === selectedUom) ? ' selected' : '';
        if (u.name === selectedUom) found = true;
        html += '<option value="' + escHtml(u.name) + '"' + sel + '>' + escHtml(u.name) + '</option>';
    });
    if (selectedUom && !found) {
        html += '<option value="' + escHtml(selectedUom) + '" selected>' + escHtml(selectedUom) + '</option>';
    }
    html += '<option value="__add_new__">+ Add New UOM</option>';
    html += '</select>';
    return html;
}

function addLineItem(barcode, desc, uom, qty) {
    lineItemIndex++;
    var idx = lineItemIndex;

    function render() {
        var html = '<tr id="line_' + idx + '" data-base-uom="' + escHtml(uom || '') + '">';
        html += '<td>' + idx + '</td>';
        html += '<td><div class="li-desc" style="word-break:break-word;font-size:0.95em;padding:4px 0;min-width:120px;">' + escHtml(desc) + '</div><input type="hidden" class="li-desc-val" value="' + escHtml(desc) + '"><input type="hidden" class="li-barcode" value="' + escHtml(barcode) + '"></td>';
        html += '<td><small class="text-muted">' + escHtml(barcode) + '</small></td>';
        html += '<td>' + buildUomSelect(idx, uom || '') + '</td>';
        html += '<td><input type="number" class="li-qty" value="' + (parseFloat(qty) || 1) + '" min="0" step="any" onchange="onLineQtyChange(' + idx + ');" oninput="onLineQtyChange(' + idx + ');"></td>';
        html += '<td class="li-impact-cell" id="impact_' + idx + '"><span class="li-conv-indicator same-uom">+' + (parseFloat(qty) || 1) + ' ' + escHtml(uom || '') + ' QOH</span></td>';
        html += '<td><button class="btn-remove-line" onclick="removeLine(' + idx + ');"><i class="fas fa-times"></i></button></td>';
        html += '</tr>';
        document.getElementById('lineItems').insertAdjacentHTML('beforeend', html);
    }

    if (!poUomLoaded) {
        loadPoUomCache(render);
    } else {
        render();
    }
}

function removeLine(idx) {
    var row = document.getElementById('line_' + idx);
    if (row) row.remove();
    renumberLines();
}

function renumberLines() {
    var rows = document.querySelectorAll('#lineItems tr');
    rows.forEach(function(r, i) { r.cells[0].textContent = i + 1; });
}

function onLineUomChange(idx) {
    var row = document.getElementById('line_' + idx);
    if (!row) return;
    var sel = row.querySelector('.li-uom');
    if (sel.value === '__add_new__') {
        Swal.fire({
            title: 'Add New UOM',
            input: 'text',
            inputPlaceholder: 'e.g. CTN, BOX, PACK',
            showCancelButton: true,
            confirmButtonText: 'Add',
            inputValidator: function(v) { if (!v || !v.trim()) return 'UOM name is required.'; }
        }).then(function(result) {
            if (result.isConfirmed) {
                var newName = result.value.trim().toUpperCase();
                $.post('staff_po_ajax.php', { action: 'uom_create', name: newName }, function(r) {
                    if (r.success) {
                        poUomCache.push({ id: r.id, name: newName, status: 'ACTIVE' });
                        var td = sel.parentElement;
                        td.innerHTML = buildUomSelect(idx, newName);
                        updateConversionIndicator(idx);
                    } else if (r.error && r.error.indexOf('already exists') > -1) {
                        var exists = poUomCache.find(function(u) { return u.name === newName; });
                        if (!exists) poUomCache.push({ id: 0, name: newName, status: 'ACTIVE' });
                        var td = sel.parentElement;
                        td.innerHTML = buildUomSelect(idx, newName);
                        updateConversionIndicator(idx);
                    } else {
                        Swal.fire({ icon: 'error', text: r.error || 'Failed to create UOM.' });
                        sel.value = row.getAttribute('data-base-uom') || '';
                    }
                }, 'json');
            } else {
                sel.value = row.getAttribute('data-base-uom') || '';
            }
        });
        return;
    }
    updateConversionIndicator(idx);
}

function onLineQtyChange(idx) {
    updateConversionIndicator(idx);
}

function updateConversionIndicator(idx) {
    var row = document.getElementById('line_' + idx);
    if (!row) return;
    var barcode = row.querySelector('.li-barcode').value;
    var selectedUom = row.querySelector('.li-uom').value;
    var baseUom = row.getAttribute('data-base-uom') || '';
    var qty = parseFloat(row.querySelector('.li-qty').value) || 0;
    var cell = document.getElementById('impact_' + idx);

    if (qty <= 0) {
        cell.innerHTML = '<span class="li-conv-indicator same-uom">No qty</span>';
        return;
    }

    if (!baseUom || selectedUom === baseUom) {
        cell.innerHTML = '<span class="li-conv-indicator same-uom">+' + qty + ' ' + escHtml(baseUom || selectedUom) + ' QOH</span>';
        return;
    }

    cell.innerHTML = '<span class="li-conv-indicator no-conv"><i class="fas fa-spinner fa-spin"></i></span>';
    $.post('staff_po_ajax.php', { action: 'uom_conversion_lookup', barcode: barcode, from_uom: selectedUom }, function(data) {
        if (data.found) {
            var converted = qty * data.conversion_factor;
            cell.innerHTML = '<span class="li-conv-indicator converted">' +
                '<strong>+' + converted + ' ' + escHtml(data.to_uom) + '</strong> QOH' +
                '<br><small>' + qty + ' ' + escHtml(selectedUom) + ' x ' + data.conversion_factor + '</small>' +
                '</span>';
        } else {
            cell.innerHTML = '<span class="li-conv-indicator no-conv">' +
                '+' + qty + ' ' + escHtml(selectedUom) + ' QOH' +
                '<br><small>No conversion set (1:1)</small>' +
                '</span>';
        }
    }, 'json');
}

// ==================== PRODUCT SEARCH MODAL ====================
var psmSearchXhr = null;
var psmCurrentQuery = '';
var psmCurrentOffset = 0;
var psmTotal = 0;

function openProductSearchModal() {
    productSearchModal.show();
}

document.getElementById('psmSearchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); doProductSearch(); }
});

function doProductSearch() {
    var q = document.getElementById('psmSearchInput').value;
    if (q.length === 0) {
        document.getElementById('psmResultsContainer').innerHTML = '<div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>Please enter a search term</div>';
        return;
    }
    psmCurrentQuery = q;
    psmCurrentOffset = 0;
    psmTotal = 0;
    loadProducts(false);
}

function loadMoreProducts() {
    loadProducts(true);
}

function loadProducts(append) {
    if (psmSearchXhr) { psmSearchXhr.abort(); }
    var container = document.getElementById('psmResultsContainer');
    if (!append) {
        container.innerHTML = '<div class="psm-empty"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    } else {
        var btn = document.getElementById('psmLoadMoreBtn');
        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    }

    psmSearchXhr = $.ajax({
        type: 'POST', url: 'staff_po_ajax.php', data: { action: 'search_products', q: psmCurrentQuery, offset: psmCurrentOffset }, dataType: 'json',
        success: function(data) {
            psmSearchXhr = null;
            var products = data.products || [];
            psmTotal = data.total || 0;

            if (!append && products.length === 0) {
                container.innerHTML = '<div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>No products found for "' + escHtml(psmCurrentQuery) + '"</div>';
                return;
            }

            var html = '';
            products.forEach(function(p) {
                var qohClass = (p.qoh || 0) > 0 ? 'in' : 'out';
                var imgHtml = p.image ? '<img class="psm-card-img" src="../img/' + escHtml(p.image) + '" alt="" loading="lazy">' :
                    '<div class="psm-card-noimg"><i class="fas fa-box"></i></div>';
                html += '<div class="psm-card" data-barcode="' + escHtml(p.barcode) + '" data-name="' + escHtml(p.name) + '" data-uom="' + escHtml(p.uom || '') + '" onclick="selectProductFromCard(this);">';
                html += imgHtml;
                html += '<div class="psm-card-name">' + escHtml(p.name) + '</div>';
                html += '<div class="psm-card-meta">' + escHtml(p.barcode) + '</div>';
                if (p.rack) { html += '<div class="psm-card-rack"><i class="fas fa-map-marker-alt"></i> ' + escHtml(p.rack) + '</div>'; }
                html += '<div class="psm-card-qoh ' + qohClass + '">QOH: ' + (p.qoh || 0) + '</div>';
                html += '</div>';
            });

            psmCurrentOffset += products.length;
            var loaded = psmCurrentOffset;
            var hasMore = loaded < psmTotal;

            if (append) {
                var grid = container.querySelector('.psm-grid');
                if (grid) grid.insertAdjacentHTML('beforeend', html);
                var oldBtn = document.getElementById('psmLoadMoreBtn');
                if (oldBtn) oldBtn.remove();
                var countEl = container.querySelector('.psm-result-count');
                if (countEl) countEl.textContent = 'Showing ' + loaded + ' of ' + psmTotal + ' products';
            } else {
                container.innerHTML = '<div class="psm-result-count">Showing ' + loaded + ' of ' + psmTotal + ' products</div><div class="psm-grid">' + html + '</div>';
            }

            if (hasMore) {
                container.insertAdjacentHTML('beforeend', '<button class="psm-load-more" id="psmLoadMoreBtn" onclick="loadMoreProducts();">Load More (' + (psmTotal - loaded) + ' remaining)</button>');
            }
        },
        error: function() { psmSearchXhr = null; }
    });
}

function selectProductFromCard(el) {
    var barcode = el.getAttribute('data-barcode');
    var name = el.getAttribute('data-name');
    var uom = el.getAttribute('data-uom');
    addLineItem(barcode, name, uom, 1);
}

// ==================== SAVE PO ====================
function savePO() {
    var editId = document.getElementById('editId').value;
    var supplierId = document.getElementById('fSupplier').value;
    var orderDate = document.getElementById('fOrderDate').value;

    if (!supplierId) { Swal.fire({ icon: 'warning', text: 'Please select a supplier.' }); return; }
    if (!orderDate) { Swal.fire({ icon: 'warning', text: 'Please enter order date.' }); return; }

    var items = [];
    document.querySelectorAll('#lineItems tr').forEach(function(row) {
        items.push({
            barcode: row.querySelector('.li-barcode').value,
            product_desc: row.querySelector('.li-desc-val').value,
            uom: row.querySelector('.li-uom').value,
            qty_ordered: parseFloat(row.querySelector('.li-qty').value) || 0,
            unit_cost: 0
        });
    });

    if (items.length === 0) { Swal.fire({ icon: 'warning', text: 'Please add at least one line item.' }); return; }

    var postData = {
        action: editId ? 'update' : 'create',
        supplier_id: supplierId,
        order_date: orderDate,
        expected_date: document.getElementById('fExpectedDate').value,
        remark: document.getElementById('fRemark').value,
        items: JSON.stringify(items)
    };
    if (editId) postData.id = editId;

    $.ajax({
        type: 'POST', url: 'staff_po_ajax.php', data: postData, dataType: 'json',
        success: function(data) {
            if (data.success) {
                poModal.hide();
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { loadPOs(); });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}

// ==================== VIEW PO ====================
function viewPO(id) {
    $.ajax({
        type: 'POST', url: 'staff_po_ajax.php', data: { action: 'get_po', id: id }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            var po = data.po;
            var items = data.items || [];
            var statusClass = 'badge-' + (po.status || '').toLowerCase();

            var html = '<div class="detail-section">';
            html += '<div class="detail-row"><span class="detail-label">PO Number:</span><strong>' + escHtml(po.po_number) + '</strong></div>';
            html += '<div class="detail-row"><span class="detail-label">Status:</span><span class="badge-status ' + statusClass + '">' + escHtml(po.status) + '</span></div>';
            html += '<div class="detail-row"><span class="detail-label">Supplier:</span>' + escHtml(po.supplier_name || '-') + '</div>';
            html += '<div class="detail-row"><span class="detail-label">Order Date:</span>' + escHtml(po.order_date || '-') + '</div>';
            html += '<div class="detail-row"><span class="detail-label">Expected Date:</span>' + escHtml(po.expected_date || '-') + '</div>';
            html += '<div class="detail-row"><span class="detail-label">Total Amount:</span>RM ' + parseFloat(po.total_amount || 0).toFixed(2) + '</div>';
            html += '<div class="detail-row"><span class="detail-label">Created By:</span>' + escHtml(po.created_by || '-') + '</div>';
            if (po.approved_by) {
                html += '<div class="detail-row"><span class="detail-label">Approved By:</span>' + escHtml(po.approved_by) + '</div>';
                html += '<div class="detail-row"><span class="detail-label">Approved Date:</span>' + escHtml(po.approved_date || '-') + '</div>';
            }
            if (po.remark) {
                html += '<div class="detail-row"><span class="detail-label">Remark:</span>' + escHtml(po.remark) + '</div>';
            }
            html += '</div>';

            html += '<div style="overflow-x:auto;">';
            html += '<table class="line-items-table">';
            html += '<thead><tr><th>#</th><th>Product</th><th>Barcode</th><th>UOM</th><th>Ordered</th><th>Received</th></tr></thead>';
            html += '<tbody>';
            items.forEach(function(item, i) {
                html += '<tr>';
                html += '<td>' + (i + 1) + '</td>';
                html += '<td>' + escHtml(item.product_desc) + '</td>';
                html += '<td><small class="text-muted">' + escHtml(item.barcode) + '</small></td>';
                html += '<td>' + escHtml(item.uom || '-') + '</td>';
                html += '<td>' + parseFloat(item.qty_ordered || 0).toFixed(2) + '</td>';
                html += '<td>' + parseFloat(item.qty_received || 0).toFixed(2) + '</td>';
                html += '</tr>';
            });
            html += '</tbody>';
            html += '</table>';
            html += '</div>';

            document.getElementById('viewTitle').innerHTML = '<i class="fas fa-file-invoice"></i> ' + escHtml(po.po_number);
            document.getElementById('viewBody').innerHTML = html;
            viewModalObj.show();
        }
    });
}

// ==================== APPROVE / CANCEL ====================
function approvePO(id, poNumber) {
    Swal.fire({
        title: 'Approve PO?',
        text: 'Approve "' + poNumber + '"?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Yes, Approve'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'staff_po_ajax.php', data: { action: 'approve', id: id }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { loadPOs(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error });
                    }
                }
            });
        }
    });
}

function cancelPO(id, poNumber) {
    Swal.fire({
        title: 'Cancel PO?',
        text: 'Cancel "' + poNumber + '"? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Cancel PO'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'staff_po_ajax.php', data: { action: 'cancel', id: id }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { loadPOs(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error });
                    }
                }
            });
        }
    });
}

// ==================== NEW PRODUCT FROM PO ====================
var npCategoriesCache = [];
var npUomCache = [];
var npRacksCache = [];
var npDropdownsLoaded = false;

function openNewProductModal() {
    document.getElementById('npBarcode').value = '';
    document.getElementById('npCode').value = '';
    document.getElementById('npName').value = '';
    document.getElementById('npDescription').value = '';
    document.getElementById('npQoh').value = '0';
    document.getElementById('npRackRemark').value = '';
    document.getElementById('npImage').value = '';
    document.getElementById('npImgPreview').style.display = 'none';
    document.getElementById('npImgPreview').src = '';
    document.getElementById('npImgPlaceholder').style.display = '';
    document.getElementById('npBtnRemoveImg').style.display = 'none';
    document.getElementById('npImgUploadArea').classList.remove('has-image');
    document.getElementById('npSubCat').innerHTML = '<option value="">-- Select --</option>';

    if (!npDropdownsLoaded) {
        npLoadDropdowns(function() {
            newProductModal.show();
        });
    } else {
        npRefreshDropdowns();
        newProductModal.show();
    }
}

function npLoadDropdowns(callback) {
    var pending = 3;
    function done() { pending--; if (pending === 0) { npDropdownsLoaded = true; if (callback) callback(); } }

    $.post('staff_po_ajax.php', { action: 'cat_list' }, function(cats) {
        npCategoriesCache = (cats || []).filter(function(c) { return c.status === 'ACTIVE'; });
        npRefreshCatSelect();
        done();
    }, 'json');

    $.post('staff_po_ajax.php', { action: 'uom_list' }, function(uoms) {
        npUomCache = (uoms || []).filter(function(u) { return u.status === 'ACTIVE'; });
        npRefreshUomSelect();
        done();
    }, 'json');

    $.post('staff_po_ajax.php', { action: 'rack_list' }, function(racks) {
        npRacksCache = racks || [];
        npRefreshRackSelect();
        done();
    }, 'json');
}

function npRefreshDropdowns() {
    npRefreshCatSelect();
    npRefreshUomSelect();
    npRefreshRackSelect();
}

function npRefreshCatSelect() {
    var sel = document.getElementById('npCat');
    sel.innerHTML = '<option value="">-- Select --</option>';
    npCategoriesCache.forEach(function(c) {
        sel.innerHTML += '<option value="' + escHtml(c.ccode) + '" data-name="' + escHtml(c.name) + '" data-id="' + c.id + '">' + escHtml(c.name) + '</option>';
    });
}

function npRefreshUomSelect() {
    var sel = document.getElementById('npUom');
    sel.innerHTML = '<option value="">-- Select --</option>';
    npUomCache.forEach(function(u) {
        sel.innerHTML += '<option value="' + escHtml(u.name) + '">' + escHtml(u.name) + '</option>';
    });
}

function npRefreshRackSelect() {
    var sel = document.getElementById('npRackSelect');
    sel.innerHTML = '<option value="">-- Select --</option>';
    npRacksCache.forEach(function(r) {
        sel.innerHTML += '<option value="' + escHtml(r.code) + '">' + escHtml(r.code) + (r.description ? ' - ' + escHtml(r.description) : '') + '</option>';
    });
}

function npLoadSubCatOptions() {
    var catCode = document.getElementById('npCat').value;
    var sel = document.getElementById('npSubCat');
    sel.innerHTML = '<option value="">-- Select --</option>';
    if (!catCode) return;

    var catObj = npCategoriesCache.find(function(c) { return c.ccode === catCode; });
    if (!catObj) return;

    $.post('staff_po_ajax.php', { action: 'subcat_list', category_id: catObj.id }, function(subs) {
        (subs || []).forEach(function(s) {
            sel.innerHTML += '<option value="' + escHtml(s.sub_code) + '" data-name="' + escHtml(s.name) + '">' + escHtml(s.name) + '</option>';
        });
    }, 'json');
}

function npPreviewImage(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire({ icon: 'warning', text: 'Image must be smaller than 10MB.' });
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('npImgPreview').src = e.target.result;
            document.getElementById('npImgPreview').style.display = '';
            document.getElementById('npImgPlaceholder').style.display = 'none';
            document.getElementById('npBtnRemoveImg').style.display = 'flex';
            document.getElementById('npImgUploadArea').classList.add('has-image');
        };
        reader.readAsDataURL(file);
    }
}

function npRemoveImage(e) {
    e.stopPropagation();
    document.getElementById('npImage').value = '';
    document.getElementById('npImgPreview').style.display = 'none';
    document.getElementById('npImgPreview').src = '';
    document.getElementById('npImgPlaceholder').style.display = '';
    document.getElementById('npBtnRemoveImg').style.display = 'none';
    document.getElementById('npImgUploadArea').classList.remove('has-image');
}

function saveNewProduct() {
    var barcode = document.getElementById('npBarcode').value.trim();
    var name = document.getElementById('npName').value.trim();

    if (barcode === '' || name === '') {
        Swal.fire({ icon: 'warning', text: 'Barcode and product name are required.' });
        return;
    }

    var catSel = document.getElementById('npCat');
    var subSel = document.getElementById('npSubCat');
    var catCode = catSel.value.trim();
    var subCode = subSel.value.trim();
    var catName = catSel.selectedOptions[0] ? (catSel.selectedOptions[0].getAttribute('data-name') || catSel.selectedOptions[0].textContent) : '';
    var subName = subSel.selectedOptions[0] ? (subSel.selectedOptions[0].getAttribute('data-name') || subSel.selectedOptions[0].textContent) : '';
    if (catSel.value === '') { catName = ''; catCode = ''; }
    if (subSel.value === '') { subName = ''; subCode = ''; }

    var formData = new FormData();
    formData.append('action', 'create_product');
    formData.append('barcode', barcode);
    formData.append('code', document.getElementById('npCode').value.trim());
    formData.append('name', name);
    formData.append('description', document.getElementById('npDescription').value.trim());
    formData.append('cat', catName);
    formData.append('sub_cat', subName);
    formData.append('cat_code', catCode);
    formData.append('sub_code', subCode);
    formData.append('uom', document.getElementById('npUom').value.trim());
    formData.append('qoh', document.getElementById('npQoh').value);
    formData.append('rack', document.getElementById('npRackSelect').value.trim());
    formData.append('rack_remark', document.getElementById('npRackRemark').value.trim());
    formData.append('checked', 'Y');

    var imageFile = document.getElementById('npImage').files[0];
    if (imageFile) {
        formData.append('product_image', imageFile);
    }

    $.ajax({
        type: 'POST', url: 'staff_po_ajax.php', data: formData, dataType: 'json',
        processData: false, contentType: false,
        success: function(data) {
            if (data.success) {
                var uom = document.getElementById('npUom').value.trim();
                newProductModal.hide();
                addLineItem(barcode, name, uom, 1);
                Swal.fire({ icon: 'success', text: 'Product created and added to PO.', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}
</script>
</body>
</html>
