<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch stock loss records (stockadj with LOSS_REASON not ADJUSTMENT)
$losses = [];
$result = $connect->query("SELECT * FROM `stockadj` WHERE `LOSS_REASON` IS NOT NULL AND `LOSS_REASON` != 'ADJUSTMENT' ORDER BY `ID` DESC LIMIT 500");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $losses[] = $r;
    }
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

/* Product search modal grid (same as PO) */
.psm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.psm-card { display: flex; flex-direction: column; align-items: center; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.15s; text-align: center; }
.psm-card:hover { border-color: var(--primary); background: #fef2f2; box-shadow: 0 2px 8px rgba(200,16,46,0.1); }
.psm-card.already-added { opacity: 0.5; }
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

/* Record loss section - multi product */
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
                <button class="btn-add" onclick="openProductSearchModal();"><i class="fas fa-search"></i> Search Product</button>
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
                <input type="text" id="searchInput" placeholder="Search losses...">
            </div>
            <div class="filter-group">
                <select id="reasonFilter" onchange="filterTable();">
                    <option value="">All Reasons</option>
                    <option value="SPOILAGE">Spoilage</option>
                    <option value="DAMAGE">Damage</option>
                    <option value="THEFT">Theft</option>
                    <option value="EXPIRED">Expired</option>
                    <option value="OTHER">Other</option>
                </select>
            </div>
            <div class="item-count" id="itemCount"><?php echo count($losses); ?> record(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Date</th>
                        <th>Barcode</th>
                        <th>Description</th>
                        <th>Qty Lost</th>
                        <th>Reason</th>
                        <th>Remark</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($losses) === 0): ?>
                    <tr class="no-results"><td colspan="8"><i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px;display:block;"></i>No stock loss records</td></tr>
                    <?php else: ?>
                    <?php foreach ($losses as $i => $l): ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($l['BARCODE'] ?? '') . ' ' . ($l['PDESC'] ?? '') . ' ' . ($l['LOSS_REASON'] ?? '') . ' ' . ($l['REMARK'] ?? '') . ' ' . ($l['USER'] ?? '')
                    )); ?>" data-reason="<?php echo htmlspecialchars($l['LOSS_REASON'] ?? ''); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo !empty($l['SDATE']) ? date('d/m/Y', strtotime($l['SDATE'])) : ''; ?></td>
                        <td><?php echo htmlspecialchars($l['BARCODE'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($l['PDESC'] ?? ''); ?></td>
                        <td><strong><?php echo abs(floatval($l['QTYADJ'] ?? 0)); ?></strong></td>
                        <td><span class="badge-reason badge-<?php echo htmlspecialchars($l['LOSS_REASON'] ?? 'OTHER'); ?>"><?php echo htmlspecialchars($l['LOSS_REASON'] ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars($l['REMARK'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($l['USER'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Hidden file inputs for image upload -->
<input type="file" id="fileInput" class="hidden-input" accept="image/*">

<!-- Product Search Modal (same as PO) -->
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
                </div>
                <div id="psmResultsContainer">
                    <div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>Enter a search term and click Search</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var productSearchModal = null;
var lossItems = [];
var itemIdCounter = 0;
var activeImageItemId = null;
var fileInput = document.getElementById('fileInput');

document.addEventListener('DOMContentLoaded', function() {
    productSearchModal = new bootstrap.Modal(document.getElementById('productSearchModal'));
});

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

function escAttr(text) {
    return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ==================== TABLE FILTER ====================
function filterTable() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    var reason = document.getElementById('reasonFilter').value;
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        var r = row.getAttribute('data-reason') || '';
        var matchSearch = d.indexOf(query) > -1;
        var matchReason = reason === '' || r === reason;
        if (matchSearch && matchReason) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('itemCount').textContent = count + ' record(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
}

document.getElementById('searchInput').addEventListener('input', filterTable);

// ==================== PRODUCT SEARCH MODAL ====================
var psmSearchXhr = null;
var psmCurrentQuery = '';
var psmCurrentOffset = 0;
var psmTotal = 0;

function openProductSearchModal() {
    document.getElementById('psmSearchInput').value = '';
    document.getElementById('psmResultsContainer').innerHTML = '<div class="psm-empty"><i class="fas fa-box-open" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i>Enter a search term and click Search</div>';
    productSearchModal.show();
    setTimeout(function() { document.getElementById('psmSearchInput').focus(); }, 300);
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
        type: 'POST', url: 'stock_loss_ajax.php', data: { action: 'search_products', q: psmCurrentQuery, offset: psmCurrentOffset }, dataType: 'json',
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
                var alreadyAdded = lossItems.some(function(item) { return item.barcode === p.barcode; });
                var addedClass = alreadyAdded ? ' already-added' : '';
                var imgHtml = p.image ? '<img class="psm-card-img" src="../img/' + escHtml(p.image) + '" alt="" loading="lazy">' :
                    '<div class="psm-card-noimg"><i class="fas fa-box"></i></div>';
                html += '<div class="psm-card' + addedClass + '" data-barcode="' + escHtml(p.barcode) + '" data-name="' + escHtml(p.name) + '" data-qoh="' + (p.qoh || 0) + '" onclick="selectProductFromCard(this);">';
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
    var qoh = parseInt(el.getAttribute('data-qoh')) || 0;

    if (lossItems.some(function(item) { return item.barcode === barcode; })) {
        Swal.fire({ icon: 'info', title: 'Already Added', text: 'This product is already in the list.', confirmButtonColor: '#C8102E', timer: 1500, showConfirmButton: false });
        return;
    }

    addProduct(barcode, name, qoh);
    productSearchModal.hide();

    var Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 1500, timerProgressBar: true });
    Toast.fire({ icon: 'success', title: 'Added: ' + name });
}

// ==================== MULTI-PRODUCT LIST ====================
function addProduct(barcode, name, qoh) {
    itemIdCounter++;
    lossItems.push({
        id: itemIdCounter,
        barcode: barcode,
        name: name,
        qoh: qoh,
        qty: 1,
        reason: '',
        remark: '',
        imageData: null
    });
    renderList();
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
        container.innerHTML = '<div class="empty-list" id="emptyState"><i class="fas fa-exclamation-triangle" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.3;"></i><div>Search and add products above to record stock loss</div></div>';
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
        html += '  <div class="loss-item-header">';
        html += '    <div class="loss-item-info">';
        html += '      <div class="loss-item-name">' + escHtml(item.name) + '</div>';
        html += '      <div class="loss-item-meta">' + escHtml(item.barcode) + ' &middot; QOH: ' + item.qoh + '</div>';
        html += '    </div>';
        html += '    <button class="loss-item-remove" onclick="removeItem(' + item.id + ')" title="Remove"><i class="fas fa-times"></i></button>';
        html += '  </div>';
        html += '  <div class="loss-item-fields">';
        html += '    <div class="loss-field">';
        html += '      <label>Quantity</label>';
        html += '      <input type="number" min="1" value="' + item.qty + '" onchange="updateItem(' + item.id + ', \'qty\', this.value)" placeholder="1">';
        html += '    </div>';
        html += '    <div class="loss-field">';
        html += '      <label>Reason</label>';
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
        if (!item.qty || item.qty < 1) {
            Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid quantity for "' + item.name + '".', confirmButtonColor: '#C8102E' });
            return;
        }
        if (!item.reason) {
            Swal.fire({ icon: 'warning', title: 'Missing Reason', text: 'Please select a reason for "' + item.name + '".', confirmButtonColor: '#C8102E' });
            return;
        }
    }

    var totalItems = lossItems.length;
    var totalQty = lossItems.reduce(function(sum, i) { return sum + i.qty; }, 0);

    Swal.fire({
        title: 'Confirm Stock Loss',
        html: '<strong>' + totalItems + ' product(s)</strong> with a total of <strong>' + totalQty + ' unit(s)</strong> will be deducted from inventory.',
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
            barcode: item.barcode,
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
            Swal.fire({
                icon: 'success',
                title: 'Recorded',
                text: data.success,
                confirmButtonColor: '#C8102E'
            }).then(function() { location.reload(); });
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
</script>
</body>
</html>
