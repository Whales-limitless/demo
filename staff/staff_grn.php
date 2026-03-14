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

/* Product search modal grid */
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
.psm-search-bar { display: flex; gap: 8px; }
.psm-search-bar input { flex: 1; }
.psm-search-btn { background: var(--primary); color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
.psm-search-btn:hover { background: var(--primary-dark); }
.psm-result-count { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }
.psm-load-more { display: block; width: 100%; padding: 10px; margin-top: 12px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; text-align: center; transition: all 0.15s; }
.psm-load-more:hover { background: #e5e7eb; border-color: var(--primary); color: var(--primary); }

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

                    <!-- Direct mode: product search button -->
                    <div id="directProductSearch" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Line Items</label>
                            <button type="button" class="btn-add" onclick="openProductSearchModal();"><i class="fas fa-search"></i> Search Product</button>
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
                                    <th style="width:70px">UOM</th>
                                    <th style="width:90px">Receive Qty</th>
                                    <th style="width:80px">Rejected</th>
                                    <th style="width:130px">Inv. Qty</th>
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

<!-- Product Search Modal -->
<div class="modal fade" id="productSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search" style="color:var(--primary);margin-right:6px;"></i>Search Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="psm-search-bar mb-3">
                    <input type="text" class="form-control" id="psmSearchInput" placeholder="Enter product name..." autocomplete="off">
                    <button type="button" class="psm-search-btn" onclick="doProductSearch();"><i class="fas fa-search"></i> Search</button>
                </div>
                <div id="psmResultsContainer">
                    <div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>Enter a search term and click Search</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('mobile-bottombar.php'); ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var receiveModalObj = null, viewGrnModalObj = null, productSearchModal = null;
var grnLineIndex = 0;

document.addEventListener('DOMContentLoaded', function() {
    receiveModalObj = new bootstrap.Modal(document.getElementById('receiveModal'));
    viewGrnModalObj = new bootstrap.Modal(document.getElementById('viewGrnModal'));
    productSearchModal = new bootstrap.Modal(document.getElementById('productSearchModal'));
    document.getElementById('productSearchModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('psmSearchInput').focus();
    });
    document.getElementById('productSearchModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('psmSearchInput').value = '';
        document.getElementById('psmResultsContainer').innerHTML = '<div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>Enter a search term and click Search</div>';
    });
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
                    addGRNLine(item.id, item.barcode, item.product_desc, item.qty_ordered, item.qty_pending, item.rack || '', item.uom || '');
                }
            });
        }
    });
}

// UOM conversion cache
var uomConversionCache = {};

function addGRNLine(poItemId, barcode, desc, qtyOrdered, qtyPending, rack, poUom) {
    grnLineIndex++;
    var idx = grnLineIndex;
    var uomDisplay = poUom || '';
    var html = '<tr id="gline_' + idx + '">';
    html += '<td>' + idx + '</td>';
    html += '<td>' + escHtml(desc) + '<input type="hidden" class="gi-po-item-id" value="' + (poItemId || '') + '"><input type="hidden" class="gi-barcode" value="' + escHtml(barcode) + '"><input type="hidden" class="gi-desc" value="' + escHtml(desc) + '"><input type="hidden" class="gi-receive-uom" value="' + escHtml(uomDisplay) + '"><input type="hidden" class="gi-inv-uom" value=""><input type="hidden" class="gi-conv-factor" value="1"><input type="hidden" class="gi-qty-converted" value="0"></td>';
    html += '<td><small class="text-muted">' + escHtml(barcode) + '</small></td>';
    html += '<td>' + (qtyOrdered !== null ? parseFloat(qtyOrdered).toFixed(0) : '-') + '</td>';
    html += '<td><small>' + escHtml(uomDisplay || '-') + '</small></td>';
    html += '<td><input type="number" class="gi-qty" value="' + (parseFloat(qtyPending) || 0) + '" min="0" step="any" onchange="recalcConversion(this);" oninput="recalcConversion(this);"></td>';
    html += '<td><input type="number" class="gi-rejected" value="0" min="0" step="any"></td>';
    html += '<td class="gi-converted-cell"><span class="gi-converted-display" style="font-size:12px;color:var(--text-muted);">-</span></td>';
    html += '<td><input type="text" class="gi-batch" value="" placeholder="Batch"></td>';
    html += '<td><input type="date" class="gi-exp" value=""></td>';
    html += '<td><input type="text" class="gi-rack" value="' + escHtml(rack) + '" placeholder="Rack"></td>';
    html += '<td><button class="btn-remove-line" onclick="removeGRNLine(' + idx + ');"><i class="fas fa-times"></i></button></td>';
    html += '</tr>';
    document.getElementById('grnLineItems').insertAdjacentHTML('beforeend', html);

    // Lookup conversion for this barcode + UOM
    if (barcode && uomDisplay) {
        lookupConversion(idx, barcode, uomDisplay);
    }
}

function lookupConversion(lineIdx, barcode, fromUom) {
    $.post('staff_grn_ajax.php', { action: 'uom_conversion_lookup', barcode: barcode, from_uom: fromUom }, function(data) {
        var row = document.getElementById('gline_' + lineIdx);
        if (!row) return;
        if (data.found) {
            row.querySelector('.gi-conv-factor').value = data.conversion_factor;
            row.querySelector('.gi-inv-uom').value = data.to_uom;
            recalcConversion(row.querySelector('.gi-qty'));
        } else {
            row.querySelector('.gi-conv-factor').value = 1;
            row.querySelector('.gi-inv-uom').value = data.base_uom || fromUom;
            recalcConversion(row.querySelector('.gi-qty'));
        }
    }, 'json');
}

function recalcConversion(qtyInput) {
    var row = qtyInput.closest('tr');
    var qty = parseFloat(qtyInput.value) || 0;
    var factor = parseFloat(row.querySelector('.gi-conv-factor').value) || 1;
    var invUom = row.querySelector('.gi-inv-uom').value || '';
    var receiveUom = row.querySelector('.gi-receive-uom').value || '';
    var converted = qty * factor;
    row.querySelector('.gi-qty-converted').value = converted;

    var displayEl = row.querySelector('.gi-converted-display');
    if (factor !== 1 && invUom && receiveUom !== invUom) {
        displayEl.innerHTML = '<strong style="color:#16a34a;">' + converted.toFixed(2) + ' ' + escHtml(invUom) + '</strong>';
    } else if (invUom) {
        displayEl.innerHTML = converted.toFixed(2) + ' ' + escHtml(invUom);
    } else {
        displayEl.textContent = '-';
    }
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
        type: 'POST', url: 'staff_grn_ajax.php', data: { action: 'search_products', q: psmCurrentQuery, offset: psmCurrentOffset }, dataType: 'json',
        success: function(data) {
            psmSearchXhr = null;
            var products = data.products || [];
            psmTotal = data.total || products.length;

            if (!append && products.length === 0) {
                container.innerHTML = '<div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>No products found for "' + escHtml(psmCurrentQuery) + '"</div>';
                return;
            }

            var html = '';
            products.forEach(function(p) {
                var qohClass = (p.qoh || 0) > 0 ? 'in' : 'out';
                var imgHtml = p.image ? '<img class="psm-card-img" src="../img/' + escHtml(p.image) + '" alt="" loading="lazy">' :
                    '<div class="psm-card-noimg"><i class="fas fa-box"></i></div>';
                html += '<div class="psm-card" onclick="selectProductFromModal(\'' + escHtml(p.barcode) + '\', \'' + escHtml(p.name).replace(/'/g, "\\'") + '\', \'' + escHtml(p.uom || '') + '\', \'' + escHtml(p.rack || '') + '\');">';
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

function selectProductFromModal(barcode, name, uom, rack) {
    addGRNLine(null, barcode, name, null, 1, rack, uom);
}

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
                receive_uom: row.querySelector('.gi-receive-uom').value || '',
                conversion_factor: parseFloat(row.querySelector('.gi-conv-factor').value) || 1,
                qty_converted: parseFloat(row.querySelector('.gi-qty-converted').value) || qty,
                inventory_uom: row.querySelector('.gi-inv-uom').value || '',
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
            html += '<thead><tr><th>#</th><th>Product</th><th>Barcode</th><th>Received</th><th>Inv. Qty</th><th>Rejected</th><th>Batch No</th><th>Exp Date</th><th>Location</th></tr></thead>';
            html += '<tbody>';
            items.forEach(function(item, i) {
                var recvUom = item.receive_uom || '';
                var invUom = item.inventory_uom || '';
                var qtyConv = item.qty_converted ? parseFloat(item.qty_converted) : null;
                html += '<tr>';
                html += '<td>' + (i + 1) + '</td>';
                html += '<td>' + escHtml(item.product_desc) + '</td>';
                html += '<td><small class="text-muted">' + escHtml(item.barcode) + '</small></td>';
                html += '<td>' + parseFloat(item.qty_received || 0).toFixed(2) + (recvUom ? ' <small class="text-muted">' + escHtml(recvUom) + '</small>' : '') + '</td>';
                if (qtyConv !== null && invUom) {
                    html += '<td><strong style="color:#16a34a;">' + qtyConv.toFixed(2) + ' ' + escHtml(invUom) + '</strong></td>';
                } else {
                    html += '<td>-</td>';
                }
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
