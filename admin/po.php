<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$currentPage = 'po';
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

/* Product search */
.product-search-wrap { position: relative; }
.product-search-results { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; max-height: 250px; overflow-y: auto; z-index: 1050; display: none; box-shadow: var(--shadow-md); }
.product-search-results .item { padding: 8px 12px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
.product-search-results .item:hover { background: #f0f9ff; }
.product-search-results .item .barcode { color: var(--text-muted); font-size: 11px; }

/* Line items table */
.line-items-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 12px; }
.line-items-table th { background: #f9fafb; padding: 8px 10px; font-weight: 600; font-size: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; }
.line-items-table td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.line-items-table input { width: 100%; padding: 5px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; font-family: 'DM Sans', sans-serif; }
.line-items-table input:focus { border-color: var(--primary); outline: none; }
.btn-remove-line { background: #fee2e2; color: #dc2626; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-size: 12px; }
.btn-remove-line:hover { background: #fca5a5; }

/* Detail view */
.detail-section { background: #f9fafb; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
.detail-row { display: flex; gap: 16px; margin-bottom: 6px; font-size: 13px; }
.detail-label { font-weight: 600; min-width: 130px; color: var(--text-muted); }

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
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search PO number, supplier...">
            </div>
            <div class="item-count" id="itemCount">0 PO(s)</div>
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
                <tbody id="dataBody">
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
                <label class="form-label fw-semibold">Line Items</label>
                <div class="product-search-wrap mb-2">
                    <input type="text" id="productSearch" class="form-control" placeholder="Search product by name or barcode...">
                    <div class="product-search-results" id="productResults"></div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="line-items-table">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Product</th>
                                <th>Barcode</th>
                                <th style="width:80px">UOM</th>
                                <th style="width:100px">Qty</th>
                                <th style="width:120px">Unit Cost</th>
                                <th style="width:100px">Subtotal</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="lineItems"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:right;font-weight:700;">Total:</td>
                                <td id="lineTotal" style="font-weight:700;">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
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

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var poModal = null, viewModal = null;
var currentStatus = '';
var lineItemIndex = 0;
var searchTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    poModal = new bootstrap.Modal(document.getElementById('poModal'));
    viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    loadPOs();
});

// ==================== LOAD PO LIST ====================
function loadPOs() {
    $.ajax({
        type: 'POST', url: 'po_ajax.php', data: { action: 'list', status: currentStatus }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            renderPOTable(data.pos || []);
        }
    });
}

function renderPOTable(pos) {
    var body = document.getElementById('dataBody');
    if (pos.length === 0) {
        body.innerHTML = '<tr class="no-results"><td colspan="9"><i class="fas fa-file-invoice" style="font-size:24px;margin-bottom:8px;display:block;"></i>No purchase orders found</td></tr>';
        document.getElementById('itemCount').textContent = '0 PO(s)';
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
    document.getElementById('itemCount').textContent = pos.length + ' PO(s)';
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
document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        if (d.indexOf(q) > -1) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('itemCount').textContent = count + ' PO(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
});

// ==================== CREATE / EDIT MODAL ====================
function loadSuppliers(callback) {
    $.ajax({
        type: 'POST', url: 'po_ajax.php', data: { action: 'list_suppliers' }, dataType: 'json',
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
    document.getElementById('lineTotal').textContent = '0.00';
    lineItemIndex = 0;
}

function openCreateModal() {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-invoice"></i> New Purchase Order';
    loadSuppliers(function() {
        poModal.show();
    });
}

function editPO(id) {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-invoice"></i> Edit Purchase Order';
    document.getElementById('editId').value = id;

    loadSuppliers(function() {
        $.ajax({
            type: 'POST', url: 'po_ajax.php', data: { action: 'get', id: id }, dataType: 'json',
            success: function(data) {
                if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
                var po = data.po;
                document.getElementById('fSupplier').value = po.supplier_id;
                document.getElementById('fOrderDate').value = po.order_date || '';
                document.getElementById('fExpectedDate').value = po.expected_date || '';
                document.getElementById('fRemark').value = po.remark || '';

                (data.items || []).forEach(function(item) {
                    addLineItem(item.barcode, item.product_desc, item.uom, item.qty_ordered, item.unit_cost);
                });
                recalcTotal();
                poModal.show();
            }
        });
    });
}

// ==================== LINE ITEMS ====================
function addLineItem(barcode, desc, uom, qty, cost) {
    lineItemIndex++;
    var idx = lineItemIndex;
    var html = '<tr id="line_' + idx + '">';
    html += '<td>' + idx + '</td>';
    html += '<td>' + escHtml(desc) + '<input type="hidden" class="li-barcode" value="' + escHtml(barcode) + '"><input type="hidden" class="li-desc" value="' + escHtml(desc) + '"></td>';
    html += '<td><small class="text-muted">' + escHtml(barcode) + '</small></td>';
    html += '<td><input type="text" class="li-uom" value="' + escHtml(uom || '') + '"></td>';
    html += '<td><input type="number" class="li-qty" value="' + (parseFloat(qty) || 0) + '" min="0" step="any" onchange="recalcTotal();" oninput="recalcTotal();"></td>';
    html += '<td><input type="number" class="li-cost" value="' + (parseFloat(cost) || 0).toFixed(2) + '" min="0" step="0.01" onchange="recalcTotal();" oninput="recalcTotal();"></td>';
    html += '<td class="li-subtotal">' + ((parseFloat(qty) || 0) * (parseFloat(cost) || 0)).toFixed(2) + '</td>';
    html += '<td><button class="btn-remove-line" onclick="removeLine(' + idx + ');"><i class="fas fa-times"></i></button></td>';
    html += '</tr>';
    document.getElementById('lineItems').insertAdjacentHTML('beforeend', html);
}

function removeLine(idx) {
    var row = document.getElementById('line_' + idx);
    if (row) row.remove();
    recalcTotal();
    renumberLines();
}

function renumberLines() {
    var rows = document.querySelectorAll('#lineItems tr');
    rows.forEach(function(r, i) { r.cells[0].textContent = i + 1; });
}

function recalcTotal() {
    var total = 0;
    document.querySelectorAll('#lineItems tr').forEach(function(row) {
        var qty = parseFloat(row.querySelector('.li-qty').value) || 0;
        var cost = parseFloat(row.querySelector('.li-cost').value) || 0;
        var sub = qty * cost;
        row.querySelector('.li-subtotal').textContent = sub.toFixed(2);
        total += sub;
    });
    document.getElementById('lineTotal').textContent = total.toFixed(2);
}

// ==================== PRODUCT SEARCH ====================
document.getElementById('productSearch').addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(searchTimeout);
    if (q.length < 2) { document.getElementById('productResults').style.display = 'none'; return; }

    searchTimeout = setTimeout(function() {
        $.ajax({
            type: 'POST', url: 'po_ajax.php', data: { action: 'search_products', q: q }, dataType: 'json',
            success: function(data) {
                var container = document.getElementById('productResults');
                var products = data.products || [];
                if (products.length === 0) {
                    container.innerHTML = '<div class="item text-muted">No products found</div>';
                    container.style.display = 'block';
                    return;
                }
                var html = '';
                products.forEach(function(p) {
                    html += '<div class="item" onclick="selectProduct(\'' + escHtml(p.barcode) + '\', \'' + escHtml(p.name).replace(/'/g, "\\'") + '\', \'' + escHtml(p.uom || '') + '\');">';
                    html += '<strong>' + escHtml(p.name) + '</strong><br><span class="barcode">' + escHtml(p.barcode) + ' | UOM: ' + escHtml(p.uom || '-') + ' | QOH: ' + p.qoh + '</span>';
                    html += '</div>';
                });
                container.innerHTML = html;
                container.style.display = 'block';
            }
        });
    }, 300);
});

function selectProduct(barcode, name, uom) {
    document.getElementById('productResults').style.display = 'none';
    document.getElementById('productSearch').value = '';
    addLineItem(barcode, name, uom, 1, 0);
    recalcTotal();
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.product-search-wrap')) {
        document.getElementById('productResults').style.display = 'none';
    }
});

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
            product_desc: row.querySelector('.li-desc').value,
            uom: row.querySelector('.li-uom').value,
            qty_ordered: parseFloat(row.querySelector('.li-qty').value) || 0,
            unit_cost: parseFloat(row.querySelector('.li-cost').value) || 0
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
        type: 'POST', url: 'po_ajax.php', data: postData, dataType: 'json',
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
        type: 'POST', url: 'po_ajax.php', data: { action: 'get', id: id }, dataType: 'json',
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

            html += '<table class="line-items-table">';
            html += '<thead><tr><th>#</th><th>Product</th><th>Barcode</th><th>UOM</th><th>Ordered</th><th>Received</th><th>Unit Cost</th><th>Subtotal</th></tr></thead>';
            html += '<tbody>';
            var grandTotal = 0;
            items.forEach(function(item, i) {
                var sub = (parseFloat(item.qty_ordered) || 0) * (parseFloat(item.unit_cost) || 0);
                grandTotal += sub;
                html += '<tr>';
                html += '<td>' + (i + 1) + '</td>';
                html += '<td>' + escHtml(item.product_desc) + '</td>';
                html += '<td><small class="text-muted">' + escHtml(item.barcode) + '</small></td>';
                html += '<td>' + escHtml(item.uom || '-') + '</td>';
                html += '<td>' + parseFloat(item.qty_ordered || 0).toFixed(2) + '</td>';
                html += '<td>' + parseFloat(item.qty_received || 0).toFixed(2) + '</td>';
                html += '<td>' + parseFloat(item.unit_cost || 0).toFixed(2) + '</td>';
                html += '<td>' + sub.toFixed(2) + '</td>';
                html += '</tr>';
            });
            html += '</tbody>';
            html += '<tfoot><tr><td colspan="7" style="text-align:right;font-weight:700;">Total:</td><td style="font-weight:700;">' + grandTotal.toFixed(2) + '</td></tr></tfoot>';
            html += '</table>';

            document.getElementById('viewTitle').innerHTML = '<i class="fas fa-file-invoice"></i> ' + escHtml(po.po_number);
            document.getElementById('viewBody').innerHTML = html;
            viewModal.show();
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
                type: 'POST', url: 'po_ajax.php', data: { action: 'approve', id: id }, dataType: 'json',
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
                type: 'POST', url: 'po_ajax.php', data: { action: 'cancel', id: id }, dataType: 'json',
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
</script>
</body>
</html>
