<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$currentPage = 'staff_grn';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Goods Receiving</title>
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
.grn-page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.grn-page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.grn-page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.grn-search-box { position: relative; flex: 1; max-width: 320px; }
.grn-search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.grn-search-box input:focus { border-color: var(--primary); }
.grn-search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-received { background: #dcfce7; color: #16a34a; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-view { background: #6366f1; } .btn-view:hover { background: #4f46e5; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

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

/* Receive mode selection */
.receive-mode { display: flex; gap: 12px; margin-bottom: 16px; }
.receive-mode-btn { flex: 1; padding: 14px; border: 2px solid #d1d5db; border-radius: 10px; background: #fff; cursor: pointer; text-align: center; transition: all var(--transition); }
.receive-mode-btn:hover { border-color: var(--primary); }
.receive-mode-btn.active { border-color: var(--primary); background: #fef2f2; }
.receive-mode-btn i { font-size: 24px; display: block; margin-bottom: 6px; color: var(--primary); }
.receive-mode-btn span { font-size: 13px; font-weight: 600; }

@media (max-width: 768px) {
    .grn-page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .grn-search-box { max-width: 100%; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="grn-page-content">
    <div class="grn-page-header">
        <h1><i class="fas fa-dolly" style="color:var(--primary);margin-right:8px;"></i>Goods Receiving</h1>
        <button class="btn-add" onclick="openReceiveModal();">
            <i class="fas fa-plus"></i> Receive Goods
        </button>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="grn-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="grnSearchInput" placeholder="Search GRN number, supplier, PO...">
            </div>
            <div class="item-count" id="grnItemCount">0 GRN(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>GRN Number</th>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Receive Date</th>
                        <th>Received By</th>
                        <th>Remark</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="grnDataBody">
                    <tr class="no-results"><td colspan="8"><i class="fas fa-spinner fa-spin" style="font-size:24px;margin-bottom:8px;display:block;"></i>Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Receive Goods Modal -->
<div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-dolly"></i> Receive Goods</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Choose mode -->
                <div id="stepMode">
                    <p class="fw-semibold mb-3">How would you like to receive goods?</p>
                    <div class="receive-mode">
                        <div class="receive-mode-btn" onclick="selectMode('po');">
                            <i class="fas fa-file-invoice"></i>
                            <span>From Purchase Order</span>
                            <div class="text-muted" style="font-size:12px;margin-top:4px;">Receive against an existing approved PO</div>
                        </div>
                        <div class="receive-mode-btn" onclick="selectMode('direct');">
                            <i class="fas fa-box-open"></i>
                            <span>Direct Receive</span>
                            <div class="text-muted" style="font-size:12px;margin-top:4px;">Receive goods without a PO</div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Form -->
                <div id="stepForm" style="display:none;">
                    <input type="hidden" id="receiveMode" value="">
                    <input type="hidden" id="receivePoId" value="0">
                    <input type="hidden" id="receiveSupplierId" value="0">

                    <!-- PO mode: select PO -->
                    <div id="poSelectWrap" style="display:none;" class="mb-3">
                        <label class="form-label fw-semibold">Select Purchase Order <span class="text-danger">*</span></label>
                        <select id="fSelectPO" class="form-select" onchange="loadPOItems();">
                            <option value="">-- Select PO --</option>
                        </select>
                    </div>

                    <!-- Direct mode: select supplier -->
                    <div id="directSupplierWrap" style="display:none;" class="mb-3">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <select id="fDirectSupplier" class="form-select">
                            <option value="">-- Select Supplier --</option>
                        </select>
                    </div>

                    <div id="poInfoWrap" style="display:none;" class="detail-section mb-3">
                        <div class="detail-row"><span class="detail-label">PO Number:</span><span id="poInfoNumber">-</span></div>
                        <div class="detail-row"><span class="detail-label">Supplier:</span><span id="poInfoSupplier">-</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remark</label>
                        <input type="text" id="fGrnRemark" class="form-control" placeholder="Optional remark">
                    </div>

                    <!-- Direct mode: product search -->
                    <div id="directProductSearch" style="display:none;">
                        <label class="form-label fw-semibold">Add Products</label>
                        <div class="product-search-wrap mb-2">
                            <input type="text" id="grnProductSearch" class="form-control" placeholder="Search product by name or barcode...">
                            <div class="product-search-results" id="grnProductResults"></div>
                        </div>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="line-items-table">
                            <thead>
                                <tr>
                                    <th style="width:30px">#</th>
                                    <th>Product</th>
                                    <th>Barcode</th>
                                    <th style="width:80px">Ordered</th>
                                    <th style="width:90px">Receive Qty</th>
                                    <th style="width:80px">Rejected</th>
                                    <th style="width:100px">Batch No</th>
                                    <th style="width:120px">Exp Date</th>
                                    <th style="width:100px">Location</th>
                                    <th style="width:30px"></th>
                                </tr>
                            </thead>
                            <tbody id="grnLineItems"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnBack" style="display:none;" onclick="backToMode();">Back</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" id="btnReceive" style="display:none;" onclick="submitReceive();"><i class="fas fa-check"></i> Receive &amp; Update Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- View GRN Detail Modal -->
<div class="modal fade" id="viewGrnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewGrnTitle"><i class="fas fa-dolly"></i> GRN Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewGrnBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include('mobile-bottombar.php'); ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var receiveModalObj = null, viewGrnModalObj = null;
var grnLineIndex = 0;
var grnSearchTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    receiveModalObj = new bootstrap.Modal(document.getElementById('receiveModal'));
    viewGrnModalObj = new bootstrap.Modal(document.getElementById('viewGrnModal'));
    loadGRNs();
});

// ==================== LOAD GRN LIST ====================
function loadGRNs() {
    $.ajax({
        type: 'POST', url: 'staff_grn_ajax.php', data: { action: 'list' }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            renderGRNTable(data.grns || []);
        }
    });
}

function renderGRNTable(grns) {
    var body = document.getElementById('grnDataBody');
    if (grns.length === 0) {
        body.innerHTML = '<tr class="no-results"><td colspan="8"><i class="fas fa-dolly" style="font-size:24px;margin-bottom:8px;display:block;"></i>No goods receiving records found</td></tr>';
        document.getElementById('grnItemCount').textContent = '0 GRN(s)';
        return;
    }

    var html = '';
    grns.forEach(function(g, i) {
        var search = ((g.grn_number || '') + ' ' + (g.po_number || '') + ' ' + (g.supplier_name || '') + ' ' + (g.received_by || '')).toLowerCase();
        html += '<tr data-search="' + escHtml(search) + '">';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td><strong>' + escHtml(g.grn_number) + '</strong></td>';
        html += '<td>' + escHtml(g.po_number || '-') + '</td>';
        html += '<td>' + escHtml(g.supplier_name || '-') + '</td>';
        html += '<td>' + escHtml(g.receive_date || '-') + '</td>';
        html += '<td>' + escHtml(g.received_by || '-') + '</td>';
        html += '<td>' + escHtml(g.remark || '-') + '</td>';
        html += '<td style="white-space:nowrap">';
        html += '<button class="btn-action btn-view" onclick="viewGRN(' + g.id + ');"><i class="fas fa-eye"></i> View</button>';
        html += '</td>';
        html += '</tr>';
    });
    body.innerHTML = html;
    document.getElementById('grnItemCount').textContent = grns.length + ' GRN(s)';
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ==================== SEARCH ====================
document.getElementById('grnSearchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var rows = document.querySelectorAll('#grnDataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        if (d.indexOf(q) > -1) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('grnItemCount').textContent = count + ' GRN(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
});

// ==================== RECEIVE MODAL ====================
function openReceiveModal() {
    document.getElementById('stepMode').style.display = '';
    document.getElementById('stepForm').style.display = 'none';
    document.getElementById('btnReceive').style.display = 'none';
    document.getElementById('btnBack').style.display = 'none';
    document.getElementById('grnLineItems').innerHTML = '';
    document.getElementById('fGrnRemark').value = '';
    grnLineIndex = 0;
    receiveModalObj.show();
}

function selectMode(mode) {
    document.getElementById('receiveMode').value = mode;
    document.getElementById('stepMode').style.display = 'none';
    document.getElementById('stepForm').style.display = '';
    document.getElementById('btnReceive').style.display = '';
    document.getElementById('btnBack').style.display = '';
    document.getElementById('grnLineItems').innerHTML = '';
    grnLineIndex = 0;

    if (mode === 'po') {
        document.getElementById('poSelectWrap').style.display = '';
        document.getElementById('directSupplierWrap').style.display = 'none';
        document.getElementById('directProductSearch').style.display = 'none';
        loadReceivablePOs();
    } else {
        document.getElementById('poSelectWrap').style.display = 'none';
        document.getElementById('directSupplierWrap').style.display = '';
        document.getElementById('directProductSearch').style.display = '';
        document.getElementById('poInfoWrap').style.display = 'none';
        document.getElementById('receivePoId').value = '0';
        loadGrnSuppliers();
    }
}

function backToMode() {
    document.getElementById('stepMode').style.display = '';
    document.getElementById('stepForm').style.display = 'none';
    document.getElementById('btnReceive').style.display = 'none';
    document.getElementById('btnBack').style.display = 'none';
}

function loadReceivablePOs() {
    $.ajax({
        type: 'POST', url: 'staff_grn_ajax.php', data: { action: 'list_receivable_pos' }, dataType: 'json',
        success: function(data) {
            var sel = document.getElementById('fSelectPO');
            sel.innerHTML = '<option value="">-- Select PO --</option>';
            (data.pos || []).forEach(function(p) {
                sel.innerHTML += '<option value="' + p.id + '" data-supplier-id="' + p.supplier_id + '">' + escHtml(p.po_number + ' - ' + p.supplier_name) + '</option>';
            });
        }
    });
}

function loadGrnSuppliers() {
    $.ajax({
        type: 'POST', url: 'staff_grn_ajax.php', data: { action: 'list_suppliers' }, dataType: 'json',
        success: function(data) {
            var sel = document.getElementById('fDirectSupplier');
            sel.innerHTML = '<option value="">-- Select Supplier --</option>';
            (data.suppliers || []).forEach(function(s) {
                sel.innerHTML += '<option value="' + s.id + '">' + escHtml(s.code + ' - ' + s.name) + '</option>';
            });
        }
    });
}

function loadPOItems() {
    var poId = document.getElementById('fSelectPO').value;
    if (!poId) {
        document.getElementById('poInfoWrap').style.display = 'none';
        document.getElementById('grnLineItems').innerHTML = '';
        return;
    }

    $.ajax({
        type: 'POST', url: 'staff_grn_ajax.php', data: { action: 'get_po_items', po_id: poId }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            var po = data.po;
            document.getElementById('receivePoId').value = po.id;
            document.getElementById('receiveSupplierId').value = po.supplier_id;
            document.getElementById('poInfoNumber').textContent = po.po_number;
            document.getElementById('poInfoSupplier').textContent = po.supplier_name;
            document.getElementById('poInfoWrap').style.display = '';

            document.getElementById('grnLineItems').innerHTML = '';
            grnLineIndex = 0;
            (data.items || []).forEach(function(item) {
                if (item.qty_pending > 0) {
                    addGRNLine(item.id, item.barcode, item.product_desc, item.qty_ordered, item.qty_pending, item.rack || '');
                }
            });
        }
    });
}

function addGRNLine(poItemId, barcode, desc, qtyOrdered, qtyPending, rack) {
    grnLineIndex++;
    var idx = grnLineIndex;
    var html = '<tr id="gline_' + idx + '">';
    html += '<td>' + idx + '</td>';
    html += '<td>' + escHtml(desc) + '<input type="hidden" class="gi-po-item-id" value="' + (poItemId || '') + '"><input type="hidden" class="gi-barcode" value="' + escHtml(barcode) + '"><input type="hidden" class="gi-desc" value="' + escHtml(desc) + '"></td>';
    html += '<td><small class="text-muted">' + escHtml(barcode) + '</small></td>';
    html += '<td>' + (qtyOrdered !== null ? parseFloat(qtyOrdered).toFixed(0) : '-') + '</td>';
    html += '<td><input type="number" class="gi-qty" value="' + (parseFloat(qtyPending) || 0) + '" min="0" step="any"></td>';
    html += '<td><input type="number" class="gi-rejected" value="0" min="0" step="any"></td>';
    html += '<td><input type="text" class="gi-batch" value="" placeholder="Batch"></td>';
    html += '<td><input type="date" class="gi-exp" value=""></td>';
    html += '<td><input type="text" class="gi-rack" value="' + escHtml(rack) + '" placeholder="Rack"></td>';
    html += '<td><button class="btn-remove-line" onclick="removeGRNLine(' + idx + ');"><i class="fas fa-times"></i></button></td>';
    html += '</tr>';
    document.getElementById('grnLineItems').insertAdjacentHTML('beforeend', html);
}

function removeGRNLine(idx) {
    var row = document.getElementById('gline_' + idx);
    if (row) row.remove();
    renumberGRNLines();
}

function renumberGRNLines() {
    var rows = document.querySelectorAll('#grnLineItems tr');
    rows.forEach(function(r, i) { r.cells[0].textContent = i + 1; });
}

// ==================== DIRECT MODE PRODUCT SEARCH ====================
document.getElementById('grnProductSearch').addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(grnSearchTimeout);
    if (q.length < 2) { document.getElementById('grnProductResults').style.display = 'none'; return; }

    grnSearchTimeout = setTimeout(function() {
        $.ajax({
            type: 'POST', url: 'staff_grn_ajax.php', data: { action: 'search_products', q: q }, dataType: 'json',
            success: function(data) {
                var container = document.getElementById('grnProductResults');
                var products = data.products || [];
                if (products.length === 0) {
                    container.innerHTML = '<div class="item text-muted">No products found</div>';
                    container.style.display = 'block';
                    return;
                }
                var html = '';
                products.forEach(function(p) {
                    html += '<div class="item" onclick="selectGRNProduct(\'' + escHtml(p.barcode) + '\', \'' + escHtml(p.name).replace(/'/g, "\\'") + '\', \'' + escHtml(p.rack || '') + '\');">';
                    html += '<strong>' + escHtml(p.name) + '</strong><br><span class="barcode">' + escHtml(p.barcode) + ' | UOM: ' + escHtml(p.uom || '-') + '</span>';
                    html += '</div>';
                });
                container.innerHTML = html;
                container.style.display = 'block';
            }
        });
    }, 300);
});

function selectGRNProduct(barcode, name, rack) {
    document.getElementById('grnProductResults').style.display = 'none';
    document.getElementById('grnProductSearch').value = '';
    addGRNLine(null, barcode, name, null, 1, rack);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.product-search-wrap')) {
        var el = document.getElementById('grnProductResults');
        if (el) el.style.display = 'none';
    }
});

// ==================== SUBMIT RECEIVE ====================
function submitReceive() {
    var mode = document.getElementById('receiveMode').value;
    var poId = parseInt(document.getElementById('receivePoId').value) || 0;
    var supplierId = 0;

    if (mode === 'po') {
        supplierId = parseInt(document.getElementById('receiveSupplierId').value) || 0;
        if (!poId) { Swal.fire({ icon: 'warning', text: 'Please select a PO.' }); return; }
    } else {
        supplierId = parseInt(document.getElementById('fDirectSupplier').value) || 0;
        if (!supplierId) { Swal.fire({ icon: 'warning', text: 'Please select a supplier.' }); return; }
    }

    var items = [];
    document.querySelectorAll('#grnLineItems tr').forEach(function(row) {
        var qty = parseFloat(row.querySelector('.gi-qty').value) || 0;
        if (qty > 0) {
            items.push({
                po_item_id: row.querySelector('.gi-po-item-id').value || null,
                barcode: row.querySelector('.gi-barcode').value,
                product_desc: row.querySelector('.gi-desc').value,
                qty_received: qty,
                qty_rejected: parseFloat(row.querySelector('.gi-rejected').value) || 0,
                batch_no: row.querySelector('.gi-batch').value,
                exp_date: row.querySelector('.gi-exp').value,
                rack_location: row.querySelector('.gi-rack').value
            });
        }
    });

    if (items.length === 0) { Swal.fire({ icon: 'warning', text: 'No items with quantity to receive.' }); return; }

    Swal.fire({
        title: 'Confirm Receive?',
        text: 'This will update stock quantities. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        confirmButtonText: 'Yes, Receive'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'staff_grn_ajax.php',
                data: {
                    action: 'receive',
                    po_id: poId,
                    supplier_id: supplierId,
                    remark: document.getElementById('fGrnRemark').value,
                    items: JSON.stringify(items)
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        receiveModalObj.hide();
                        Swal.fire({ icon: 'success', text: data.success, timer: 2000, showConfirmButton: false }).then(function() { loadGRNs(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                }
            });
        }
    });
}

// ==================== VIEW GRN ====================
function viewGRN(id) {
    $.ajax({
        type: 'POST', url: 'staff_grn_ajax.php', data: { action: 'get', id: id }, dataType: 'json',
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            var grn = data.grn;
            var items = data.items || [];

            var html = '<div class="detail-section">';
            html += '<div class="detail-row"><span class="detail-label">GRN Number:</span><strong>' + escHtml(grn.grn_number) + '</strong></div>';
            html += '<div class="detail-row"><span class="detail-label">PO Number:</span>' + escHtml(grn.po_number || '-') + '</div>';
            html += '<div class="detail-row"><span class="detail-label">Supplier:</span>' + escHtml(grn.supplier_name || '-') + '</div>';
            html += '<div class="detail-row"><span class="detail-label">Receive Date:</span>' + escHtml(grn.receive_date || '-') + '</div>';
            html += '<div class="detail-row"><span class="detail-label">Received By:</span>' + escHtml(grn.received_by || '-') + '</div>';
            if (grn.remark) {
                html += '<div class="detail-row"><span class="detail-label">Remark:</span>' + escHtml(grn.remark) + '</div>';
            }
            html += '</div>';

            html += '<table class="line-items-table">';
            html += '<thead><tr><th>#</th><th>Product</th><th>Barcode</th><th>Received</th><th>Rejected</th><th>Batch No</th><th>Exp Date</th><th>Location</th></tr></thead>';
            html += '<tbody>';
            items.forEach(function(item, i) {
                html += '<tr>';
                html += '<td>' + (i + 1) + '</td>';
                html += '<td>' + escHtml(item.product_desc) + '</td>';
                html += '<td><small class="text-muted">' + escHtml(item.barcode) + '</small></td>';
                html += '<td>' + parseFloat(item.qty_received || 0).toFixed(2) + '</td>';
                html += '<td>' + parseFloat(item.qty_rejected || 0).toFixed(2) + '</td>';
                html += '<td>' + escHtml(item.batch_no || '-') + '</td>';
                html += '<td>' + escHtml(item.exp_date || '-') + '</td>';
                html += '<td>' + escHtml(item.rack_location || '-') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';

            document.getElementById('viewGrnTitle').innerHTML = '<i class="fas fa-dolly"></i> ' + escHtml(grn.grn_number);
            document.getElementById('viewGrnBody').innerHTML = html;
            viewGrnModalObj.show();
        }
    });
}
</script>
</body>
</html>
