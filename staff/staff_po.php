<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch suppliers for dropdown
$suppliers = [];
$supResult = $connect->query("SELECT `id`, `code`, `name` FROM `supplier` WHERE `status` = 'ACTIVE' ORDER BY `name` ASC");
if ($supResult) { while ($r = $supResult->fetch_assoc()) $suppliers[] = $r; }

// Check if opening specific PO
$openPoId = intval($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 80px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }

        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .back-btn { background: none; border: none; color: #fff; cursor: pointer; display: flex; align-items: center; padding: 4px; text-decoration: none; }
        .back-btn svg { width: 22px; height: 22px; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; flex: 1; }
        .header-action { background: rgba(255,255,255,0.2); border: none; color: #fff; padding: 8px 14px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .header-action:active { transform: scale(0.96); }

        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        /* Tabs */
        .tab-bar { display: flex; background: var(--surface); border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); margin-bottom: 16px; overflow: hidden; }
        .tab-btn { flex: 1; padding: 10px 8px; border: none; background: none; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; color: var(--text-muted); cursor: pointer; text-align: center; border-bottom: 3px solid transparent; transition: all 0.2s; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); background: #fef2f2; }
        .tab-btn .tab-count { display: inline-block; background: #e5e7eb; color: var(--text-muted); padding: 1px 6px; border-radius: 10px; font-size: 10px; margin-left: 2px; }
        .tab-btn.active .tab-count { background: #fee2e2; color: var(--primary); }

        /* PO Card */
        .po-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04); padding: 16px; margin-bottom: 12px; cursor: pointer; transition: box-shadow 0.2s; }
        .po-card:active { box-shadow: 0 1px 2px rgba(0,0,0,0.06); }
        .po-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
        .po-number { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 600; }
        .po-date { font-size: 12px; color: var(--text-muted); }
        .po-supplier { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
        .badge { font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; white-space: nowrap; text-transform: uppercase; }
        .badge-DRAFT { background: #f3f4f6; color: #6b7280; }
        .badge-APPROVED { background: #dbeafe; color: #2563eb; }
        .badge-PARTIALLY_RECEIVED { background: #fef3c7; color: #d97706; }
        .badge-RECEIVED { background: #dcfce7; color: #16a34a; }
        .badge-CLOSED { background: #e5e7eb; color: #374151; }
        .badge-CANCELLED { background: #fee2e2; color: #dc2626; }

        /* Detail View */
        .detail-view { display: none; }
        .detail-view.active { display: block; }
        .list-view { display: block; }
        .list-view.hidden { display: none; }

        .detail-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; margin-bottom: 12px; }
        .detail-card .label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px; }
        .detail-card .value { font-size: 14px; font-weight: 500; margin-bottom: 12px; }
        .detail-card .value:last-child { margin-bottom: 0; }

        /* Search bar for add product */
        .search-wrap { position: relative; margin-bottom: 12px; }
        .search-input { width: 100%; padding: 12px 14px 12px 40px; border: 2px solid #e5e7eb; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; background: var(--surface); }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(200,16,46,0.1); }
        .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .search-icon svg { width: 18px; height: 18px; }
        .search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: var(--surface); border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 12px 40px rgba(0,0,0,0.15); z-index: 200; max-height: 300px; overflow-y: auto; display: none; margin-top: 4px; }
        .search-dropdown.active { display: block; }

        .sr-item { display: flex; align-items: center; gap: 10px; padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
        .sr-item:last-child { border-bottom: none; }
        .sr-item:active { background: #f3f4f6; }
        .sr-img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #f3f4f6; flex-shrink: 0; }
        .sr-noimg { width: 40px; height: 40px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sr-noimg svg { width: 18px; height: 18px; stroke: #d1d5db; fill: none; }
        .sr-info { flex: 1; min-width: 0; }
        .sr-name { font-weight: 600; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sr-name mark { background: #fef3c7; border-radius: 2px; padding: 0 1px; }
        .sr-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .sr-qoh { font-size: 11px; font-weight: 600; white-space: nowrap; }
        .sr-qoh.in { color: #16a34a; }
        .sr-qoh.out { color: #dc2626; }
        .sr-empty { padding: 20px; text-align: center; color: var(--text-muted); font-size: 13px; }
        .sr-create { display: flex; align-items: center; gap: 8px; padding: 12px 14px; cursor: pointer; background: #f0fdf4; color: #16a34a; font-weight: 600; font-size: 13px; border-top: 1px solid #dcfce7; }
        .sr-create:active { background: #dcfce7; }
        .sr-create svg { width: 18px; height: 18px; }

        /* Line items */
        .line-item { background: var(--surface); border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .li-img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: #f3f4f6; flex-shrink: 0; }
        .li-noimg { width: 44px; height: 44px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .li-noimg svg { width: 18px; height: 18px; stroke: #d1d5db; fill: none; }
        .li-info { flex: 1; min-width: 0; }
        .li-name { font-weight: 600; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .li-barcode { font-size: 11px; color: var(--text-muted); }
        .li-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
        .li-qty-wrap { display: flex; align-items: center; gap: 4px; }
        .li-qty-input { width: 60px; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; text-align: center; }
        .li-qty-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 2px rgba(200,16,46,0.1); }
        .li-uom { font-size: 11px; color: var(--text-muted); }
        .li-remove { background: none; border: none; color: #ef4444; cursor: pointer; padding: 4px; }
        .li-remove svg { width: 18px; height: 18px; }
        .li-received { font-size: 11px; color: var(--text-muted); }

        /* Buttons */
        .btn-primary { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 24px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-primary:active { transform: scale(0.98); }
        .btn-secondary { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px 24px; background: var(--surface); color: var(--text); border: 2px solid #e5e7eb; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }
        .btn-success { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 24px; background: #22c55e; color: #fff; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-success:active { transform: scale(0.98); }
        .btn-danger { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px 24px; background: #ef4444; color: #fff; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-warning { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 24px; background: #f59e0b; color: #fff; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-group { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; }

        .empty-state { text-align: center; padding: 48px 16px; color: var(--text-muted); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state p { font-size: 14px; }
        .loading { text-align: center; padding: 40px; color: var(--text-muted); }

        /* New Product Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 300; align-items: center; justify-content: center; padding: 16px; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: var(--surface); border-radius: 16px; max-width: 440px; width: 100%; padding: 24px; max-height: 90vh; overflow-y: auto; }
        .modal-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
        .modal-close { background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 4px; }
        .modal-close svg { width: 20px; height: 20px; }
        .form-group { margin-bottom: 14px; }
        .form-label { font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block; }
        .form-input { width: 100%; padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; }
        .form-input:focus { border-color: var(--primary); }
        .form-select { width: 100%; padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; appearance: none; background: var(--surface); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
        .form-select:focus { border-color: var(--primary); }
        .img-upload { width: 100%; height: 120px; border: 2px dashed #d1d5db; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; transition: border-color 0.2s; }
        .img-upload:active { border-color: var(--primary); }
        .img-upload img { width: 100%; height: 100%; object-fit: cover; }
        .img-upload .placeholder { text-align: center; color: var(--text-muted); font-size: 12px; }
        .img-upload .placeholder svg { width: 24px; height: 24px; display: block; margin: 0 auto 4px; }

        .hint-text { font-size: 11px; color: var(--text-muted); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- LIST VIEW -->
    <div class="list-view" id="listView">
        <header class="page-header">
            <a href="./" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <span class="page-title">Purchase Orders</span>
            <button class="header-action" onclick="showCreatePO();">
                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New PO
            </button>
        </header>

        <div class="main-content">
            <div class="tab-bar" id="tabBar">
                <button class="tab-btn active" data-status="" onclick="switchTab(this)">All</button>
                <button class="tab-btn" data-status="DRAFT" onclick="switchTab(this)">Draft</button>
                <button class="tab-btn" data-status="APPROVED" onclick="switchTab(this)">Approved</button>
                <button class="tab-btn" data-status="RECEIVED" onclick="switchTab(this)">Received</button>
            </div>
            <div id="poList"><div class="loading">Loading...</div></div>
        </div>
    </div>

    <!-- DETAIL VIEW (Create / Edit / View) -->
    <div class="detail-view" id="detailView">
        <header class="page-header">
            <button class="back-btn" onclick="backToList();">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </button>
            <span class="page-title" id="detailTitle">New Purchase Order</span>
        </header>

        <div class="main-content">
            <!-- PO Header Fields -->
            <div class="detail-card" id="poHeaderCard">
                <div class="form-group">
                    <label class="form-label">Supplier *</label>
                    <select id="fSupplier" class="form-select">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $sup): ?>
                        <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['code'] . ' - ' . $sup['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Order Date *</label>
                        <input type="date" id="fOrderDate" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Expected Date</label>
                        <input type="date" id="fExpectedDate" class="form-input">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Remark</label>
                    <input type="text" id="fRemark" class="form-input" placeholder="Optional note">
                </div>
            </div>

            <!-- Product Search (Draft only) -->
            <div id="searchSection">
                <div class="search-wrap" id="searchWrap">
                    <span class="search-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </span>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search product to add..." autocomplete="off">
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>
            </div>

            <!-- Line Items -->
            <div id="lineItems"></div>
            <div id="emptyHint" class="empty-state" style="padding:24px;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <p>Search above to add products</p>
            </div>

            <!-- Action Buttons -->
            <div class="btn-group" id="actionButtons"></div>
        </div>
    </div>

    <!-- New Product Modal -->
    <div class="modal-overlay" id="newProductModal" onclick="if(event.target===this)closeNewProductModal()">
        <div class="modal-box" onclick="event.stopPropagation()">
            <div class="modal-title">
                <span>Add New Product</span>
                <button class="modal-close" onclick="closeNewProductModal()">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="form-group">
                <label class="form-label">Product Name *</label>
                <input type="text" id="npName" class="form-input" placeholder="Enter product name">
            </div>
            <div style="display:flex;gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label class="form-label">UOM</label>
                    <input type="text" id="npUom" class="form-input" placeholder="e.g. PCS, KG">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label">Barcode <span class="hint-text">(optional)</span></label>
                    <input type="text" id="npBarcode" class="form-input" placeholder="Auto if empty">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Image <span class="hint-text">(optional)</span></label>
                <div class="img-upload" id="npImgBox" onclick="document.getElementById('npImageFile').click();">
                    <div class="placeholder" id="npImgPlaceholder">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        Tap to upload photo
                    </div>
                    <img id="npImgPreview" src="" alt="" style="display:none;">
                </div>
                <input type="file" id="npImageFile" accept="image/*" style="display:none;" onchange="previewNPImage(this);">
            </div>
            <button class="btn-success" onclick="saveNewProduct();" style="margin-top:8px;">Create & Add to PO</button>
        </div>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    var currentPoId = 0;
    var currentPoStatus = '';
    var lineItemsData = []; // {barcode, name, image, qty, uom, itemId}
    var currentTab = '';

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function highlightMatch(text, query) {
        if (!query) return escHtml(text);
        var escaped = escHtml(text);
        var re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escaped.replace(re, '<mark>$1</mark>');
    }

    // ==================== LIST VIEW ====================
    function loadPOs() {
        document.getElementById('poList').innerHTML = '<div class="loading">Loading...</div>';
        fetch('staff_po_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list_po&status=' + encodeURIComponent(currentTab)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var pos = data.pos || [];
            if (pos.length === 0) {
                document.getElementById('poList').innerHTML = '<div class="empty-state"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p>No purchase orders found</p></div>';
                return;
            }
            var html = '';
            for (var i = 0; i < pos.length; i++) {
                var p = pos[i];
                var date = p.order_date ? new Date(p.order_date).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) : '';
                var statusLabel = (p.status || '').replace(/_/g, ' ');
                html += '<div class="po-card" onclick="openPO(' + p.id + ')">' +
                    '<div class="po-card-top"><div><div class="po-number">' + escHtml(p.po_number) + '</div><div class="po-date">' + date + '</div></div>' +
                    '<span class="badge badge-' + escHtml(p.status) + '">' + escHtml(statusLabel) + '</span></div>' +
                    '<div class="po-supplier">' + escHtml(p.supplier_name || '') + '</div>' +
                    (p.created_by ? '<div style="font-size:11px;color:var(--text-muted);">By: ' + escHtml(p.created_by) + '</div>' : '') +
                '</div>';
            }
            document.getElementById('poList').innerHTML = html;
        })
        .catch(function() {
            document.getElementById('poList').innerHTML = '<div class="empty-state"><p>Failed to load. Please try again.</p></div>';
        });
    }

    function switchTab(btn) {
        document.querySelectorAll('.tab-btn').forEach(function(t) { t.classList.remove('active'); });
        btn.classList.add('active');
        currentTab = btn.getAttribute('data-status');
        loadPOs();
    }

    // ==================== DETAIL VIEW ====================
    function showCreatePO() {
        currentPoId = 0;
        currentPoStatus = 'DRAFT';
        lineItemsData = [];
        document.getElementById('detailTitle').textContent = 'New Purchase Order';
        document.getElementById('fSupplier').value = '';
        document.getElementById('fSupplier').disabled = false;
        document.getElementById('fOrderDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('fOrderDate').disabled = false;
        document.getElementById('fExpectedDate').value = '';
        document.getElementById('fExpectedDate').disabled = false;
        document.getElementById('fRemark').value = '';
        document.getElementById('fRemark').disabled = false;
        document.getElementById('searchSection').style.display = '';
        document.getElementById('emptyHint').style.display = '';
        renderLineItems();
        renderActionButtons();
        document.getElementById('listView').classList.add('hidden');
        document.getElementById('detailView').classList.add('active');
    }

    function openPO(id) {
        document.getElementById('listView').classList.add('hidden');
        document.getElementById('detailView').classList.add('active');
        document.getElementById('detailTitle').textContent = 'Loading...';
        document.getElementById('lineItems').innerHTML = '<div class="loading">Loading...</div>';
        document.getElementById('actionButtons').innerHTML = '';

        fetch('staff_po_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_po&id=' + id
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                Swal.fire({ icon: 'error', text: data.error });
                backToList();
                return;
            }
            var po = data.po;
            var items = data.items || [];
            currentPoId = po.id;
            currentPoStatus = po.status;

            var isDraft = po.status === 'DRAFT';
            document.getElementById('detailTitle').textContent = po.po_number + ' - ' + po.status.replace(/_/g, ' ');
            document.getElementById('fSupplier').value = po.supplier_id || '';
            document.getElementById('fSupplier').disabled = !isDraft;
            document.getElementById('fOrderDate').value = po.order_date || '';
            document.getElementById('fOrderDate').disabled = !isDraft;
            document.getElementById('fExpectedDate').value = po.expected_date || '';
            document.getElementById('fExpectedDate').disabled = !isDraft;
            document.getElementById('fRemark').value = po.remark || '';
            document.getElementById('fRemark').disabled = !isDraft;
            document.getElementById('searchSection').style.display = isDraft ? '' : 'none';

            lineItemsData = items.map(function(it) {
                return {
                    barcode: it.barcode || '',
                    name: it.product_desc || '',
                    image: it.product_image || '',
                    qty: parseFloat(it.qty_ordered) || 0,
                    qtyReceived: parseFloat(it.qty_received) || 0,
                    uom: it.uom || '',
                    itemId: it.id || ''
                };
            });

            renderLineItems();
            renderActionButtons();
            document.getElementById('emptyHint').style.display = lineItemsData.length > 0 ? 'none' : '';
        });
    }

    function backToList() {
        document.getElementById('detailView').classList.remove('active');
        document.getElementById('listView').classList.remove('hidden');
        loadPOs();
    }

    function renderLineItems() {
        var isDraft = currentPoStatus === 'DRAFT';
        if (lineItemsData.length === 0) {
            document.getElementById('lineItems').innerHTML = '';
            document.getElementById('emptyHint').style.display = isDraft ? '' : 'none';
            return;
        }
        document.getElementById('emptyHint').style.display = 'none';

        var noImgSvg = '<svg viewBox="0 0 24 24" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';

        var html = '';
        for (var i = 0; i < lineItemsData.length; i++) {
            var it = lineItemsData[i];
            var imgHtml = it.image ? '<img class="li-img" src="../img/' + escHtml(it.image) + '" alt="">' :
                '<div class="li-noimg">' + noImgSvg + '</div>';

            html += '<div class="line-item" data-idx="' + i + '">' +
                imgHtml +
                '<div class="li-info"><div class="li-name">' + escHtml(it.name) + '</div>' +
                    (it.barcode ? '<div class="li-barcode">' + escHtml(it.barcode) + '</div>' : '') +
                '</div>' +
                '<div class="li-right">';

            if (isDraft) {
                html += '<div class="li-qty-wrap"><input type="number" class="li-qty-input" value="' + it.qty + '" min="0.01" step="0.01" onchange="updateQty(' + i + ',this.value)"><span class="li-uom">' + escHtml(it.uom) + '</span></div>' +
                    '<button class="li-remove" onclick="removeLine(' + i + ')"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></button>';
            } else {
                html += '<div style="font-size:14px;font-weight:700;">' + it.qty + ' <span class="li-uom">' + escHtml(it.uom) + '</span></div>';
                if (it.qtyReceived > 0) {
                    html += '<div class="li-received">Received: ' + it.qtyReceived + '</div>';
                }
            }
            html += '</div></div>';
        }
        document.getElementById('lineItems').innerHTML = html;
    }

    function renderActionButtons() {
        var isDraft = currentPoStatus === 'DRAFT';
        var canApprove = currentPoId > 0 && currentPoStatus === 'DRAFT';
        var canReceive = currentPoId > 0 && (currentPoStatus === 'APPROVED' || currentPoStatus === 'PARTIALLY_RECEIVED');
        var canCancel = currentPoId > 0 && (currentPoStatus === 'DRAFT' || currentPoStatus === 'APPROVED');

        var html = '';
        if (isDraft) {
            html += '<button class="btn-primary" onclick="savePO()"><svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Draft</button>';
        }
        if (canApprove) {
            html += '<button class="btn-success" onclick="approvePO()">Approve PO</button>';
        }
        if (canReceive) {
            html += '<a class="btn-warning" href="staff_grn.php?po_id=' + currentPoId + '">Receive Goods (GRN)</a>';
        }
        if (canCancel) {
            html += '<button class="btn-danger" onclick="cancelPO()">Cancel PO</button>';
        }
        document.getElementById('actionButtons').innerHTML = html;
    }

    function updateQty(idx, val) { lineItemsData[idx].qty = parseFloat(val) || 0; }

    function removeLine(idx) {
        lineItemsData.splice(idx, 1);
        renderLineItems();
    }

    // ==================== PRODUCT SEARCH ====================
    var searchInput = document.getElementById('searchInput');
    var searchDropdown = document.getElementById('searchDropdown');
    var searchTimer = null;
    var searchXhr = null;

    function doSearch(q) {
        if (!q || q.length < 1) { searchDropdown.classList.remove('active'); return; }
        if (searchXhr) { searchXhr.abort(); }

        searchDropdown.innerHTML = '<div class="sr-empty">Searching...</div>';
        searchDropdown.classList.add('active');

        searchXhr = new XMLHttpRequest();
        searchXhr.open('POST', 'staff_po_ajax.php');
        searchXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        searchXhr.onload = function() {
            searchXhr = null;
            try {
                var data = JSON.parse(this.responseText);
                renderSearchResults(data.products || [], q);
            } catch(e) { searchDropdown.classList.remove('active'); }
        };
        searchXhr.onerror = function() { searchXhr = null; searchDropdown.classList.remove('active'); };
        searchXhr.send('action=search_products&q=' + encodeURIComponent(q));
    }

    function renderSearchResults(products, query) {
        var noImgSvg = '<svg viewBox="0 0 24 24" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';

        if (products.length === 0) {
            searchDropdown.innerHTML = '<div class="sr-empty">No products found</div>' +
                '<div class="sr-create" onclick="openNewProductModal(\'' + escHtml(query).replace(/'/g, "\\'") + '\')"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create "' + escHtml(query) + '"</div>';
            return;
        }

        var html = products.map(function(p) {
            var imgHtml = p.image ? '<img class="sr-img" src="../img/' + escHtml(p.image) + '" alt="" loading="lazy">' :
                '<div class="sr-noimg">' + noImgSvg + '</div>';
            var qohClass = p.qoh > 0 ? 'in' : 'out';
            return '<div class="sr-item" onclick=\'addProduct(' + JSON.stringify(p).replace(/'/g, "&#39;") + ')\'>' +
                imgHtml +
                '<div class="sr-info"><div class="sr-name">' + highlightMatch(p.name, query) + '</div>' +
                '<div class="sr-meta">' + (p.barcode ? escHtml(p.barcode) : '') + (p.uom ? ' &middot; ' + escHtml(p.uom) : '') + '</div></div>' +
                '<span class="sr-qoh ' + qohClass + '">QOH: ' + p.qoh + '</span>' +
            '</div>';
        }).join('');

        html += '<div class="sr-create" onclick="openNewProductModal(\'' + escHtml(query).replace(/'/g, "\\'") + '\')"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create new product</div>';
        searchDropdown.innerHTML = html;
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(searchTimer);
            if (q.length < 1) { searchDropdown.classList.remove('active'); return; }
            searchTimer = setTimeout(function() { doSearch(q); }, 250);
        });
        searchInput.addEventListener('keydown', function(e) { if (e.key === 'Escape') searchDropdown.classList.remove('active'); });
        document.addEventListener('click', function(e) {
            var wrap = document.getElementById('searchWrap');
            if (wrap && !wrap.contains(e.target)) searchDropdown.classList.remove('active');
        });
    }

    function addProduct(p) {
        // Check duplicate
        for (var i = 0; i < lineItemsData.length; i++) {
            if (lineItemsData[i].barcode === p.barcode) {
                lineItemsData[i].qty += 1;
                renderLineItems();
                searchDropdown.classList.remove('active');
                searchInput.value = '';
                return;
            }
        }
        lineItemsData.push({
            barcode: p.barcode || '', name: p.name || '', image: p.image || '',
            qty: 1, qtyReceived: 0, uom: p.uom || '', itemId: ''
        });
        renderLineItems();
        searchDropdown.classList.remove('active');
        searchInput.value = '';
    }

    // ==================== NEW PRODUCT MODAL ====================
    function openNewProductModal(prefill) {
        searchDropdown.classList.remove('active');
        document.getElementById('npName').value = prefill || '';
        document.getElementById('npUom').value = '';
        document.getElementById('npBarcode').value = '';
        document.getElementById('npImageFile').value = '';
        document.getElementById('npImgPreview').style.display = 'none';
        document.getElementById('npImgPlaceholder').style.display = '';
        document.getElementById('newProductModal').classList.add('active');
    }

    function closeNewProductModal() { document.getElementById('newProductModal').classList.remove('active'); }

    function previewNPImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('npImgPreview').src = e.target.result;
                document.getElementById('npImgPreview').style.display = 'block';
                document.getElementById('npImgPlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function saveNewProduct() {
        var name = document.getElementById('npName').value.trim();
        if (!name) { Swal.fire({ icon: 'warning', text: 'Product name is required.' }); return; }

        var fd = new FormData();
        fd.append('action', 'quick_create_product');
        fd.append('name', name);
        fd.append('uom', document.getElementById('npUom').value.trim());
        fd.append('barcode', document.getElementById('npBarcode').value.trim());
        var fi = document.getElementById('npImageFile');
        if (fi.files && fi.files[0]) fd.append('product_image', fi.files[0]);

        fetch('staff_po_ajax.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.product) {
                closeNewProductModal();
                addProduct(data.product);
                Swal.fire({ icon: 'success', text: 'Product created!', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Failed.' });
            }
        });
    }

    // ==================== SAVE / APPROVE / CANCEL ====================
    function collectItems() {
        return lineItemsData.filter(function(it) { return it.qty > 0 && it.name; }).map(function(it) {
            return { id: it.itemId, barcode: it.barcode, product_desc: it.name, qty_ordered: it.qty, uom: it.uom };
        });
    }

    function savePO() {
        var supplierId = document.getElementById('fSupplier').value;
        var orderDate = document.getElementById('fOrderDate').value;
        if (!supplierId || !orderDate) { Swal.fire({ icon: 'warning', text: 'Supplier and order date are required.' }); return; }
        var items = collectItems();
        if (items.length === 0) { Swal.fire({ icon: 'warning', text: 'Add at least one product.' }); return; }

        fetch('staff_po_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=' + (currentPoId > 0 ? 'update' : 'create') +
                '&id=' + currentPoId +
                '&supplier_id=' + encodeURIComponent(supplierId) +
                '&order_date=' + encodeURIComponent(orderDate) +
                '&expected_date=' + encodeURIComponent(document.getElementById('fExpectedDate').value) +
                '&remark=' + encodeURIComponent(document.getElementById('fRemark').value) +
                '&items=' + encodeURIComponent(JSON.stringify(items))
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                    if (data.po_id) openPO(data.po_id);
                    else backToList();
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Failed.' });
            }
        });
    }

    function approvePO() {
        Swal.fire({ title: 'Approve this PO?', icon: 'question', showCancelButton: true, confirmButtonColor: '#22c55e', confirmButtonText: 'Approve' }).then(function(r) {
            if (r.isConfirmed) {
                fetch('staff_po_ajax.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=approve&id=' + currentPoId })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) { Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { openPO(currentPoId); }); }
                    else { Swal.fire({ icon: 'error', text: data.error }); }
                });
            }
        });
    }

    function cancelPO() {
        Swal.fire({ title: 'Cancel this PO?', text: 'This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, Cancel' }).then(function(r) {
            if (r.isConfirmed) {
                fetch('staff_po_ajax.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=cancel&id=' + currentPoId })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) { Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { backToList(); }); }
                    else { Swal.fire({ icon: 'error', text: data.error }); }
                });
            }
        });
    }

    // ==================== INIT ====================
    var openId = <?php echo $openPoId; ?>;
    if (openId > 0) { openPO(openId); }
    else { loadPOs(); }
    </script>
</body>
</html>
