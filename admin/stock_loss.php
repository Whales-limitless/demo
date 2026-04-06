<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'stock_loss';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Loss</title>
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
.filter-group { display: flex; gap: 8px; align-items: center; }
.filter-group select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-reason { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-SPOILAGE { background: #fef3c7; color: #d97706; }
.badge-DAMAGE { background: #fee2e2; color: #dc2626; }
.badge-THEFT { background: #fce7f3; color: #be185d; }
.badge-EXPIRED { background: #e5e7eb; color: #374151; }
.badge-OTHER { background: #dbeafe; color: #2563eb; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* Record loss section - multi item */
.record-section { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; margin-bottom: 20px; }
.record-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px; flex-wrap: wrap; }
.record-section-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; }
.loss-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 12px; }
.loss-item-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 12px; }
.loss-item-info { flex: 1; min-width: 0; }
.loss-item-name { font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.4; }
.loss-item-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.loss-item-remove { background: #fee2e2; color: #dc2626; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-size: 12px; flex-shrink: 0; }
.loss-item-remove:hover { background: #fca5a5; }
.loss-item-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.loss-field { display: flex; flex-direction: column; gap: 4px; }
.loss-field.full-width { grid-column: 1 / -1; }
.loss-field label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; }
.loss-field input, .loss-field select { width: 100%; padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--text); background: #fff; outline: none; }
.loss-field input:focus, .loss-field select:focus { border-color: var(--primary); }
.image-upload-area { grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; margin-top: 2px; }
.img-preview { width: 56px; height: 56px; border-radius: 8px; border: 1.5px dashed #d1d5db; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f9fafb; flex-shrink: 0; }
.img-preview img { width: 100%; height: 100%; object-fit: cover; }
.img-actions { display: flex; gap: 6px; flex-wrap: wrap; }
.img-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: var(--text-muted); transition: all 0.2s; font-family: 'DM Sans', sans-serif; }
.img-btn:hover { border-color: var(--primary); color: var(--primary); }
.img-btn-remove { color: #ef4444; border-color: #fecaca; }
.img-btn-remove:hover { background: #fef2f2; color: #dc2626; border-color: #ef4444; }
.empty-list { text-align: center; color: var(--text-muted); font-size: 14px; padding: 30px 16px; }
.btn-submit-all { background: var(--primary); color: #fff; border: none; padding: 12px 32px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 700; cursor: pointer; }
.btn-submit-all:hover { background: var(--primary-dark); }
.btn-submit-all:disabled { background: #9ca3af; cursor: not-allowed; }
.hidden-input { display: none; }
.btn-view { background: #dbeafe; color: #2563eb; border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-view:hover { background: #bfdbfe; }
.btn-delete { background: #fee2e2; color: #dc2626; border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-delete:hover { background: #fca5a5; }
.detail-item { display: flex; gap: 14px; padding: 14px; border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 10px; }
.detail-item-img { width: 72px; height: 72px; border-radius: 8px; object-fit: cover; background: #f3f4f6; flex-shrink: 0; }
.detail-item-noimg { width: 72px; height: 72px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.detail-item-noimg i { font-size: 24px; color: #d1d5db; }
.detail-item-info { flex: 1; min-width: 0; }
.detail-item-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
.detail-item-meta { font-size: 12px; color: var(--text-muted); margin-bottom: 4px; }
.detail-item-fields { display: flex; gap: 12px; flex-wrap: wrap; font-size: 12px; }
.detail-item-fields span { background: #f3f4f6; padding: 3px 8px; border-radius: 6px; }
.session-loading { text-align: center; padding: 30px; color: var(--text-muted); }

.btn-export { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; }
.btn-export-pdf { background: #fee2e2; color: #dc2626; }
.btn-export-pdf:hover { background: #fca5a5; }
.btn-export-excel { background: #d1fae5; color: #059669; }
.btn-export-excel:hover { background: #a7f3d0; }

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
        <h1><i class="fas fa-exclamation-triangle" style="color:var(--primary);margin-right:8px;"></i>Stock Loss</h1>
    </div>

    <!-- Record Loss Section (multi-product) -->
    <div class="record-section">
        <div class="record-section-header">
            <span class="record-section-title">Record Stock Loss</span>
            <div style="display:flex;gap:8px;align-items:center;">
                <span id="lossItemCount" style="font-size:13px;color:var(--text-muted);display:none;">0 items</span>
                <button class="btn-add" onclick="addItem();"><i class="fas fa-plus"></i> Add Item</button>
            </div>
        </div>
        <div id="lossListContainer">
            <div class="empty-list" id="emptyState">
                <i class="fas fa-exclamation-triangle" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                <div>Search and add products above to record stock loss</div>
            </div>
        </div>
        <div id="submitArea" style="display:none;text-align:right;margin-top:16px;">
            <button class="btn-submit-all" id="btnSubmitAll" onclick="submitAllLosses();"><i class="fas fa-check"></i> Submit All Losses</button>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search sessions..." oninput="filterSessionTable();">
            </div>
            <div class="filter-group">
                <select id="reasonFilter" onchange="filterSessionTable();">
                    <option value="">All Reasons</option>
                    <option value="SPOILAGE">Spoilage</option>
                    <option value="DAMAGE">Damage</option>
                    <option value="THEFT">Theft</option>
                    <option value="EXPIRED">Expired</option>
                    <option value="OTHER">Other</option>
                </select>
            </div>
            <div class="item-count" id="itemCount">Loading...</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total Qty Lost</th>
                        <th>Reason(s)</th>
                        <th>Recorded By</th>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <tr class="no-results"><td colspan="7"><i class="fas fa-spinner fa-spin"></i> Loading sessions...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hidden file input for image upload -->
<input type="file" id="fileInput" class="hidden-input" accept="image/*">

<!-- Session Detail Modal -->
<div class="modal fade" id="sessionDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list" style="color:var(--primary);margin-right:6px;"></i>Session Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sessionDetailBody">
                <div class="session-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div class="modal-footer" style="justify-content:space-between;">
                <div style="display:flex;gap:6px;">
                    <button type="button" class="btn-export btn-export-pdf" onclick="exportSessionPDF();"><i class="fas fa-file-pdf"></i> PDF</button>
                    <button type="button" class="btn-export btn-export-excel" onclick="exportSessionExcel();"><i class="fas fa-file-excel"></i> Excel</button>
                </div>
                <div style="display:flex;gap:6px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="btnDeleteSession" onclick="deleteCurrentSession();"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
var lossItems = [];
var itemIdCounter = 0;
var activeImageItemId = null;
var fileInput = document.getElementById('fileInput');

var sessionDetailModal = null;
var currentSessionId = null;
var currentSessionItems = [];

document.addEventListener('DOMContentLoaded', function() {
    sessionDetailModal = new bootstrap.Modal(document.getElementById('sessionDetailModal'));
    loadSessions();
});

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

function escAttr(text) {
    return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ==================== SESSION TABLE ====================
function loadSessions() {
    $.ajax({
        type: 'POST', url: 'stock_loss_ajax.php', data: { action: 'list_sessions' }, dataType: 'json',
        success: function(data) {
            allSessions = data.sessions || [];
            renderSessionTable(allSessions);
        },
        error: function() {
            document.getElementById('dataBody').innerHTML = '<tr class="no-results"><td colspan="7">Failed to load sessions</td></tr>';
        }
    });
}

function renderSessionTable(sessions) {
    var body = document.getElementById('dataBody');
    if (sessions.length === 0) {
        body.innerHTML = '<tr class="no-results"><td colspan="7"><i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px;display:block;"></i>No stock loss records</td></tr>';
        document.getElementById('itemCount').textContent = '0 session(s)';
        return;
    }

    var html = '';
    sessions.forEach(function(s, i) {
        var reasons = (s.reasons || '').split(',');
        var reasonBadges = reasons.map(function(r) {
            return '<span class="badge-reason badge-' + escHtml(r) + '">' + escHtml(r) + '</span>';
        }).join(' ');

        var dateStr = s.session_date || '';
        if (dateStr) {
            var parts = dateStr.split('-');
            if (parts.length === 3) dateStr = parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        html += '<tr data-search="' + escHtml((s.session_id + ' ' + s.recorded_by + ' ' + s.reasons).toLowerCase()) + '" data-reasons="' + escHtml(s.reasons) + '">';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td>' + escHtml(dateStr) + (s.session_time ? ' <small style="color:var(--text-muted)">' + escHtml(s.session_time) + '</small>' : '') + '</td>';
        html += '<td><strong>' + s.item_count + '</strong> product' + (s.item_count > 1 ? 's' : '') + '</td>';
        html += '<td><strong>' + s.total_qty + '</strong></td>';
        html += '<td>' + reasonBadges + '</td>';
        html += '<td>' + escHtml(s.recorded_by || '') + '</td>';
        html += '<td>';
        html += '<button class="btn-view" onclick="viewSession(\'' + escHtml(s.session_id) + '\');"><i class="fas fa-eye"></i> View</button> ';
        html += '<button class="btn-delete" onclick="confirmDeleteSession(\'' + escHtml(s.session_id) + '\', ' + s.item_count + ', ' + s.total_qty + ');"><i class="fas fa-trash"></i></button>';
        html += '</td>';
        html += '</tr>';
    });
    body.innerHTML = html;
    document.getElementById('itemCount').textContent = sessions.length + ' session(s)';
}

function filterSessionTable() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    var reason = document.getElementById('reasonFilter').value;
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        var r = row.getAttribute('data-reasons') || '';
        var matchSearch = !query || d.indexOf(query) > -1;
        var matchReason = !reason || r.indexOf(reason) > -1;
        if (matchSearch && matchReason) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('itemCount').textContent = count + ' session(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
}

// ==================== SESSION DETAIL ====================
function viewSession(sessionId) {
    currentSessionId = sessionId;
    document.getElementById('sessionDetailBody').innerHTML = '<div class="session-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    sessionDetailModal.show();

    $.ajax({
        type: 'POST', url: 'stock_loss_ajax.php', data: { action: 'session_detail', session_id: sessionId }, dataType: 'json',
        success: function(data) {
            var items = data.items || [];
            currentSessionItems = items;
            if (items.length === 0) {
                document.getElementById('sessionDetailBody').innerHTML = '<div class="session-loading">No items found.</div>';
                return;
            }

            var totalQty = 0;
            var html = '<div style="margin-bottom:12px;font-size:13px;color:var(--text-muted);">Session: <strong>' + escHtml(sessionId) + '</strong> &middot; ' + escHtml(items[0].SDATE || '') + ' ' + escHtml(items[0].STIME || '') + ' &middot; Recorded by: <strong>' + escHtml(items[0].USER || '') + '</strong></div>';

            items.forEach(function(item) {
                var qty = Math.abs(item.QTYADJ || 0);
                totalQty += qty;

                var imgHtml;
                if (item.image_path) {
                    imgHtml = '<img class="detail-item-img" src="../staff/' + escHtml(item.image_path) + '" alt="" loading="lazy">';
                } else {
                    imgHtml = '<div class="detail-item-noimg"><i class="fas fa-box"></i></div>';
                }

                html += '<div class="detail-item">';
                html += imgHtml;
                html += '<div class="detail-item-info">';
                html += '<div class="detail-item-name">' + escHtml(item.PDESC || '') + '</div>';
                html += '<div class="detail-item-fields">';
                html += '<span><strong>Qty:</strong> ' + qty + '</span>';
                html += '<span class="badge-reason badge-' + escHtml(item.LOSS_REASON || 'OTHER') + '">' + escHtml(item.LOSS_REASON || '') + '</span>';
                if (item.REMARK) html += '<span>' + escHtml(item.REMARK) + '</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });

            html += '<div style="text-align:right;font-size:13px;font-weight:700;margin-top:8px;color:var(--primary);">' + items.length + ' item(s) &middot; Total Qty: ' + totalQty + '</div>';

            document.getElementById('sessionDetailBody').innerHTML = html;
        },
        error: function() {
            document.getElementById('sessionDetailBody').innerHTML = '<div class="session-loading">Failed to load details.</div>';
        }
    });
}

function deleteCurrentSession() {
    if (!currentSessionId) return;
    sessionDetailModal.hide();
    confirmDeleteSession(currentSessionId);
}

function confirmDeleteSession(sessionId, itemCount, totalQty) {
    currentSessionId = sessionId;
    var msg = 'This will delete ';
    if (itemCount && totalQty) {
        msg += '<strong>' + itemCount + ' item(s)</strong> with <strong>' + totalQty + ' unit(s)</strong>';
    } else {
        msg += 'all items in this session';
    }
    msg += '.';

    Swal.fire({
        title: 'Delete Stock Loss Session?',
        html: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'stock_loss_ajax.php', data: { action: 'delete_session', session_id: sessionId }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Deleted', text: data.success, confirmButtonColor: '#C8102E' });
                        loadSessions();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.error, confirmButtonColor: '#C8102E' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete session.', confirmButtonColor: '#C8102E' });
                }
            });
        }
    });
}

// ==================== MULTI-ITEM LIST ====================
function addItem() {
    itemIdCounter++;
    lossItems.push({
        id: itemIdCounter,
        description: '',
        qty: 1,
        reason: '',
        remark: '',
        imageData: null
    });
    renderList();
    // Focus the new description input
    setTimeout(function() {
        var input = document.getElementById('desc_' + itemIdCounter);
        if (input) input.focus();
    }, 50);
}

function removeItem(itemId) {
    lossItems = lossItems.filter(function(i) { return i.id !== itemId; });
    renderList();
}

function updateItem(itemId, field, value) {
    var item = lossItems.find(function(i) { return i.id === itemId; });
    if (!item) return;
    if (field === 'qty') {
        item.qty = parseInt(value, 10) || 1;
    } else {
        item[field] = value;
    }
}

function renderList() {
    var container = document.getElementById('lossListContainer');
    var countEl = document.getElementById('lossItemCount');
    var submitArea = document.getElementById('submitArea');

    if (lossItems.length === 0) {
        container.innerHTML = '<div class="empty-list" id="emptyState"><i class="fas fa-exclamation-triangle" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i><div>Click "Add Item" above to record stock loss</div></div>';
        countEl.style.display = 'none';
        submitArea.style.display = 'none';
        return;
    }

    countEl.style.display = 'inline';
    countEl.textContent = lossItems.length + ' item' + (lossItems.length > 1 ? 's' : '');
    submitArea.style.display = 'block';

    var html = '';
    lossItems.forEach(function(item) {
        var imgPreviewContent = item.imageData
            ? '<img src="' + item.imageData + '" alt="Photo">'
            : '<i class="fas fa-image" style="font-size:18px;color:#d1d5db;"></i>';

        var imgButtons = item.imageData
            ? '<button class="img-btn img-btn-remove" onclick="removeImage(' + item.id + ')"><i class="fas fa-times"></i> Remove</button>'
            : '<button class="img-btn" onclick="uploadImage(' + item.id + ')"><i class="fas fa-upload"></i> Upload</button>';

        html += '<div class="loss-item" data-item-id="' + item.id + '">';
        html += '  <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">';
        html += '    <div class="loss-field" style="flex:1;margin:0;">';
        html += '      <label>Product Description <span style="color:var(--primary)">*</span></label>';
        html += '      <input type="text" id="desc_' + item.id + '" value="' + escAttr(item.description) + '" onchange="updateItem(' + item.id + ', \'description\', this.value)" placeholder="Enter product description...">';
        html += '    </div>';
        html += '    <button class="loss-item-remove" onclick="removeItem(' + item.id + ')" title="Remove" style="margin-top:18px;"><i class="fas fa-times"></i></button>';
        html += '  </div>';
        html += '  <div class="loss-item-fields">';
        html += '    <div class="loss-field">';
        html += '      <label>Quantity <span style="color:var(--primary)">*</span></label>';
        html += '      <input type="number" min="1" value="' + item.qty + '" onchange="updateItem(' + item.id + ', \'qty\', this.value)" placeholder="1">';
        html += '    </div>';
        html += '    <div class="loss-field">';
        html += '      <label>Reason <span style="color:var(--primary)">*</span></label>';
        html += '      <select onchange="updateItem(' + item.id + ', \'reason\', this.value)">';
        html += '        <option value=""' + (item.reason === '' ? ' selected' : '') + '>-- Select --</option>';
        html += '        <option value="SPOILAGE"' + (item.reason === 'SPOILAGE' ? ' selected' : '') + '>Spoilage</option>';
        html += '        <option value="DAMAGE"' + (item.reason === 'DAMAGE' ? ' selected' : '') + '>Damage</option>';
        html += '        <option value="THEFT"' + (item.reason === 'THEFT' ? ' selected' : '') + '>Theft</option>';
        html += '        <option value="EXPIRED"' + (item.reason === 'EXPIRED' ? ' selected' : '') + '>Expired</option>';
        html += '        <option value="OTHER"' + (item.reason === 'OTHER' ? ' selected' : '') + '>Other</option>';
        html += '      </select>';
        html += '    </div>';
        html += '    <div class="loss-field full-width">';
        html += '      <label>Remark (optional)</label>';
        html += '      <input type="text" value="' + escAttr(item.remark) + '" onchange="updateItem(' + item.id + ', \'remark\', this.value)" placeholder="Additional details">';
        html += '    </div>';
        html += '    <div class="image-upload-area">';
        html += '      <div class="img-preview" id="imgPreview_' + item.id + '">' + imgPreviewContent + '</div>';
        html += '      <div class="img-actions">' + imgButtons + '</div>';
        html += '    </div>';
        html += '  </div>';
        html += '</div>';
    });

    container.innerHTML = html;
}

// ==================== IMAGE UPLOAD ====================
function uploadImage(itemId) {
    activeImageItemId = itemId;
    fileInput.value = '';
    fileInput.click();
}

function removeImage(itemId) {
    var item = lossItems.find(function(i) { return i.id === itemId; });
    if (item) {
        item.imageData = null;
        renderList();
    }
}

fileInput.addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file || !activeImageItemId) return;

    var item = lossItems.find(function(i) { return i.id === activeImageItemId; });
    if (!item) return;

    var reader = new FileReader();
    reader.onload = function(ev) {
        var img = new Image();
        img.onload = function() {
            var canvas = document.createElement('canvas');
            var maxSize = 800;
            var w = img.width, h = img.height;
            if (w > maxSize || h > maxSize) {
                if (w > h) { h = Math.round(h * maxSize / w); w = maxSize; }
                else { w = Math.round(w * maxSize / h); h = maxSize; }
            }
            canvas.width = w;
            canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            item.imageData = canvas.toDataURL('image/jpeg', 0.7);
            renderList();
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});

// ==================== SUBMIT ALL LOSSES ====================
function submitAllLosses() {
    if (lossItems.length === 0) return;

    for (var i = 0; i < lossItems.length; i++) {
        var item = lossItems[i];
        if (!item.description || !item.description.trim()) {
            Swal.fire({ icon: 'warning', title: 'Missing Description', text: 'Please enter a product description for item #' + (i + 1) + '.', confirmButtonColor: '#C8102E' });
            return;
        }
        if (!item.qty || item.qty < 1) {
            Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid quantity for "' + item.description + '".', confirmButtonColor: '#C8102E' });
            return;
        }
        if (!item.reason) {
            Swal.fire({ icon: 'warning', title: 'Missing Reason', text: 'Please select a reason for "' + item.description + '".', confirmButtonColor: '#C8102E' });
            return;
        }
    }

    var totalItems = lossItems.length;
    var totalQty = lossItems.reduce(function(sum, i) { return sum + i.qty; }, 0);

    Swal.fire({
        title: 'Confirm Stock Loss',
        html: '<strong>' + totalItems + ' item(s)</strong> with a total of <strong>' + totalQty + ' unit(s)</strong> will be recorded.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#C8102E',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Record All',
        cancelButtonText: 'Cancel'
    }).then(function(result) {
        if (result.isConfirmed) {
            doSubmit();
        }
    });
}

function doSubmit() {
    var btn = document.getElementById('btnSubmitAll');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    var formData = new FormData();
    formData.append('action', 'record_multiple');

    var itemsData = lossItems.map(function(item) {
        return {
            description: item.description,
            qty: item.qty,
            reason: item.reason,
            remark: item.remark
        };
    });
    formData.append('items', JSON.stringify(itemsData));

    lossItems.forEach(function(item, index) {
        if (item.imageData) {
            formData.append('image_' + index, item.imageData);
        }
    });

    fetch('stock_loss_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Submit All Losses';

        if (data.success) {
            lossItems = [];
            renderList();
            loadSessions();
            Swal.fire({
                icon: 'success',
                title: 'Recorded',
                text: data.success,
                confirmButtonColor: '#C8102E'
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to record stock loss.', confirmButtonColor: '#C8102E' });
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Submit All Losses';
        console.error('Submit error:', err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'An unexpected error occurred. Please try again.', confirmButtonColor: '#C8102E' });
    });
}

// ==================== EXPORT PDF ====================
function buildExportHtml() {
    if (!currentSessionItems || currentSessionItems.length === 0) return null;
    var items = currentSessionItems;
    var totalQty = 0;

    var html = '<div style="font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto;">';
    html += '<h2 style="color:#C8102E;margin:0 0 4px;">Stock Loss Report</h2>';
    html += '<p style="color:#6b7280;font-size:13px;margin:0 0 16px;">Session: ' + escHtml(currentSessionId) + ' &middot; ' + escHtml(items[0].SDATE || '') + ' ' + escHtml(items[0].STIME || '') + ' &middot; Recorded by: ' + escHtml(items[0].USER || '') + '</p>';
    html += '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
    html += '<thead><tr style="background:#1a1a1a;color:#fff;">';
    html += '<th style="padding:8px 10px;text-align:left;">No</th>';
    html += '<th style="padding:8px 10px;text-align:left;">Image</th>';
    html += '<th style="padding:8px 10px;text-align:left;">Description</th>';
    html += '<th style="padding:8px 10px;text-align:center;">Qty</th>';
    html += '<th style="padding:8px 10px;text-align:left;">Reason</th>';
    html += '<th style="padding:8px 10px;text-align:left;">Remark</th>';
    html += '</tr></thead><tbody>';

    items.forEach(function(item, i) {
        var qty = Math.abs(item.QTYADJ || 0);
        totalQty += qty;
        var imgTag = item.image_path
            ? '<img src="../staff/' + escHtml(item.image_path) + '" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">'
            : '<div style="width:60px;height:60px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#d1d5db;font-size:10px;">No image</div>';

        html += '<tr style="border-bottom:1px solid #e5e7eb;">';
        html += '<td style="padding:8px 10px;">' + (i + 1) + '</td>';
        html += '<td style="padding:8px 10px;">' + imgTag + '</td>';
        html += '<td style="padding:8px 10px;font-weight:600;">' + escHtml(item.PDESC || '') + '</td>';
        html += '<td style="padding:8px 10px;text-align:center;font-weight:700;">' + qty + '</td>';
        html += '<td style="padding:8px 10px;">' + escHtml(item.LOSS_REASON || '') + '</td>';
        html += '<td style="padding:8px 10px;">' + escHtml(item.REMARK || '') + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    html += '<div style="text-align:right;font-size:13px;font-weight:700;margin-top:12px;color:#C8102E;">' + items.length + ' item(s) &middot; Total Qty: ' + totalQty + '</div>';
    html += '</div>';
    return html;
}

function exportSessionPDF() {
    var html = buildExportHtml();
    if (!html) return;

    var container = document.createElement('div');
    container.innerHTML = html;
    document.body.appendChild(container);

    html2pdf().set({
        margin: 10,
        filename: 'StockLoss_' + (currentSessionId || 'export') + '.pdf',
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { scale: 2, useCORS: true, allowTaint: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(container).save().then(function() {
        document.body.removeChild(container);
    });
}

// ==================== EXPORT EXCEL ====================
function exportSessionExcel() {
    var html = buildExportHtml();
    if (!html) return;

    var excelHtml = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:spreadsheet" xmlns="http://www.w3.org/TR/REC-html40">';
    excelHtml += '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Stock Loss</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
    excelHtml += '<body>' + html + '</body></html>';

    var blob = new Blob([excelHtml], { type: 'application/vnd.ms-excel' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'StockLoss_' + (currentSessionId || 'export') + '.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>
