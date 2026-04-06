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
.selected-product-display { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; }
.selected-product-display img { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; background: #f3f4f6; }
.selected-product-display .noimg { width: 48px; height: 48px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; }
.selected-product-display .noimg i { font-size: 16px; color: #d1d5db; }
.selected-product-info { flex: 1; min-width: 0; }
.selected-product-info .sp-name { font-weight: 600; font-size: 13px; }
.selected-product-info .sp-meta { font-size: 11px; color: var(--text-muted); }
.selected-product-info .sp-qoh { font-size: 11px; font-weight: 700; }
.btn-change-product { background: none; border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 600; cursor: pointer; color: var(--text-muted); }
.btn-change-product:hover { border-color: var(--primary); color: var(--primary); }

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
        <button class="btn-add" onclick="openRecordModal();"><i class="fas fa-plus"></i> Record Loss</button>
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

<!-- Record Loss Modal -->
<div class="modal fade" id="lossModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Record Stock Loss</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fBarcode" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                    <div id="selectedProductArea">
                        <button type="button" class="btn-add" onclick="openProductSearchModal();" style="width:100%;"><i class="fas fa-search"></i> Search Product</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Qty Lost <span class="text-danger">*</span></label>
                        <input type="number" id="fQty" class="form-control" min="0.01" step="0.01" value="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <select id="fReason" class="form-select">
                            <option value="SPOILAGE">Spoilage</option>
                            <option value="DAMAGE">Damage</option>
                            <option value="THEFT">Theft</option>
                            <option value="EXPIRED">Expired</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Remark</label>
                    <input type="text" id="fRemark" class="form-control" placeholder="Additional details">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger w-50" onclick="saveLoss();"><i class="fas fa-check"></i> Record Loss</button>
            </div>
        </div>
    </div>
</div>

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
var modal = null;
var productSearchModal = null;

document.addEventListener('DOMContentLoaded', function() {
    modal = new bootstrap.Modal(document.getElementById('lossModal'));
    productSearchModal = new bootstrap.Modal(document.getElementById('productSearchModal'));
});

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

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

function openRecordModal() {
    document.getElementById('fBarcode').value = '';
    document.getElementById('selectedProductArea').innerHTML = '<button type="button" class="btn-add" onclick="openProductSearchModal();" style="width:100%;"><i class="fas fa-search"></i> Search Product</button>';
    document.getElementById('fQty').value = '1';
    document.getElementById('fReason').value = 'SPOILAGE';
    document.getElementById('fRemark').value = '';
    modal.show();
}

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
                var imgHtml = p.image ? '<img class="psm-card-img" src="../img/' + escHtml(p.image) + '" alt="" loading="lazy">' :
                    '<div class="psm-card-noimg"><i class="fas fa-box"></i></div>';
                html += '<div class="psm-card" data-barcode="' + escHtml(p.barcode) + '" data-name="' + escHtml(p.name) + '" data-qoh="' + (p.qoh || 0) + '" data-image="' + escHtml(p.image || '') + '" onclick="selectProductFromCard(this);">';
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
    var qoh = el.getAttribute('data-qoh');
    var image = el.getAttribute('data-image');

    document.getElementById('fBarcode').value = barcode;

    var imgHtml = image ? '<img src="../img/' + escHtml(image) + '" alt="">' : '<div class="noimg"><i class="fas fa-box"></i></div>';
    var qohClass = parseInt(qoh) > 0 ? 'in' : 'out';
    document.getElementById('selectedProductArea').innerHTML =
        '<div class="selected-product-display">' +
        imgHtml +
        '<div class="selected-product-info">' +
        '<div class="sp-name">' + escHtml(name) + '</div>' +
        '<div class="sp-meta">' + escHtml(barcode) + '</div>' +
        '<div class="sp-qoh psm-card-qoh ' + qohClass + '">QOH: ' + qoh + '</div>' +
        '</div>' +
        '<button type="button" class="btn-change-product" onclick="openProductSearchModal();"><i class="fas fa-exchange-alt"></i> Change</button>' +
        '</div>';

    productSearchModal.hide();

    var Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 1500, timerProgressBar: true });
    Toast.fire({ icon: 'success', title: 'Selected: ' + name });
}

function saveLoss() {
    var barcode = document.getElementById('fBarcode').value.trim();
    var qty = parseFloat(document.getElementById('fQty').value) || 0;
    var reason = document.getElementById('fReason').value;
    var remark = document.getElementById('fRemark').value.trim();

    if (barcode === '' || qty <= 0) {
        Swal.fire({ icon: 'warning', text: 'Please select a product and enter quantity.' });
        return;
    }

    Swal.fire({
        title: 'Record Stock Loss?',
        text: qty + ' unit(s) will be deducted from stock.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Record Loss'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'stock_loss_ajax.php',
                data: { action: 'record', barcode: barcode, qty: qty, reason: reason, remark: remark },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        modal.hide();
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
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
