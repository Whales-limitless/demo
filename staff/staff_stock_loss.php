<?php
require_once __DIR__ . '/session_security.php';
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Loss</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #C8102E;
            --primary-dark: #a00d24;
            --surface: #ffffff;
            --bg: #f3f4f6;
            --text: #1a1a1a;
            --text-muted: #6b7280;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding-bottom: 80px;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, h6 { font-family: 'Outfit', sans-serif; }

        .page-header {
            position: sticky; top: 0; z-index: 100;
            background: var(--primary); color: #fff;
            padding: 0 16px; height: 56px;
            display: flex; align-items: center; gap: 12px;
            box-shadow: 0 2px 12px rgba(200, 16, 46, 0.3);
        }

        .back-btn {
            display: flex; align-items: center; gap: 4px;
            background: none; border: none; color: #fff;
            font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
            cursor: pointer; padding: 6px 8px; border-radius: 8px; transition: background 0.2s;
        }
        .back-btn:hover { background: rgba(255, 255, 255, 0.15); }

        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }

        .header-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }

        .item-count-badge {
            background: rgba(255,255,255,0.2); color: #fff;
            padding: 4px 10px; border-radius: 20px;
            font-size: 12px; font-weight: 700;
        }

        .main-content {
            max-width: 700px; margin: 0 auto; padding: 16px 16px 100px;
        }

        /* Search Section */
        .search-card {
            background: var(--surface); border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
            padding: 16px; margin-bottom: 16px;
        }

        .search-wrap { position: relative; }
        .search-wrap .search-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; pointer-events: none; width: 18px; height: 18px;
        }
        .search-wrap input {
            width: 100%; padding: 12px 14px 12px 42px;
            font-family: 'DM Sans', sans-serif; font-size: 15px; color: var(--text);
            background: var(--bg); border: 1.5px solid #e5e7eb; border-radius: 12px;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-wrap input:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(200,16,46,0.1);
        }
        .search-wrap input::placeholder { color: #9ca3af; }

        .search-dropdown {
            display: none; position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px;
            background: var(--surface); border: 1.5px solid #e5e7eb; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12); z-index: 50; max-height: 280px;
            overflow-y: auto; -webkit-overflow-scrolling: touch;
        }
        .search-dropdown.active { display: block; }

        .dd-item {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 10px 14px; cursor: pointer; transition: background 0.15s;
            border-bottom: 1px solid #f3f4f6;
        }
        .dd-item:last-child { border-bottom: none; }
        .dd-item:hover, .dd-item:focus { background: #f9fafb; }
        .dd-item-info { flex: 1; min-width: 0; }
        .dd-item-name { font-size: 14px; font-weight: 600; color: var(--text); }
        .dd-item-name mark { background: #fef3c7; color: var(--text); border-radius: 2px; }
        .dd-item-barcode { font-size: 11px; color: var(--text-muted); font-weight: 500; }
        .dd-item-qoh { font-size: 11px; font-weight: 700; color: var(--primary); white-space: nowrap; }
        .dd-msg { text-align: center; padding: 16px; color: var(--text-muted); font-size: 13px; }

        /* Product List */
        .list-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 12px; padding: 0 2px;
        }
        .list-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 600; }
        .list-count { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        .empty-list {
            text-align: center; color: var(--text-muted); font-size: 14px;
            padding: 40px 16px; background: var(--surface); border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .empty-list svg { width: 48px; height: 48px; margin-bottom: 8px; opacity: 0.4; }

        .loss-item {
            background: var(--surface); border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
            padding: 16px; margin-bottom: 12px; position: relative;
        }

        .loss-item-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 8px; margin-bottom: 12px;
        }

        .loss-item-info { flex: 1; min-width: 0; }
        .loss-item-name { font-size: 14px; font-weight: 600; color: var(--text); line-height: 1.4; }
        .loss-item-meta { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .loss-item-remove {
            background: none; border: none; color: #d1d5db; cursor: pointer;
            padding: 4px; border-radius: 6px; transition: color 0.15s;
            flex-shrink: 0;
        }
        .loss-item-remove:hover { color: var(--primary); }

        .loss-item-fields {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }

        .loss-field { display: flex; flex-direction: column; gap: 4px; }
        .loss-field.full-width { grid-column: 1 / -1; }

        .loss-field label {
            font-size: 11px; font-weight: 600; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.3px;
        }

        .loss-field input, .loss-field select {
            width: 100%; padding: 8px 10px;
            border: 1.5px solid #e5e7eb; border-radius: 8px;
            font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text);
            background: var(--bg); outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .loss-field input:focus, .loss-field select:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(200,16,46,0.1);
        }
        .loss-field select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 8px center; padding-right: 30px;
        }

        /* Image upload */
        .image-upload-area {
            grid-column: 1 / -1;
            display: flex; align-items: center; gap: 10px; margin-top: 2px;
        }

        .img-preview {
            width: 56px; height: 56px; border-radius: 8px; border: 1.5px dashed #d1d5db;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; background: #f9fafb; flex-shrink: 0; position: relative;
        }
        .img-preview img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .img-preview svg { width: 22px; height: 22px; color: #d1d5db; }

        .img-actions { display: flex; gap: 6px; flex-wrap: wrap; }

        .img-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600;
            cursor: pointer; border: 1.5px solid #e5e7eb; background: var(--surface);
            color: var(--text-muted); transition: all 0.2s; font-family: 'DM Sans', sans-serif;
        }
        .img-btn:hover { border-color: var(--primary); color: var(--primary); }
        .img-btn svg { width: 14px; height: 14px; }
        .img-btn-remove { color: #ef4444; border-color: #fecaca; }
        .img-btn-remove:hover { background: #fef2f2; color: #dc2626; border-color: #ef4444; }

        /* Submit bar */
        .submit-bar {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 101;
            background: var(--surface); padding: 12px 16px;
            box-shadow: 0 -2px 12px rgba(0,0,0,0.1);
            display: none; justify-content: center;
        }
        .submit-bar.visible { display: flex; }

        @media (max-width: 992px) {
            .submit-bar { bottom: 60px; }
        }

        .submit-bar .btn-submit {
            width: 100%; max-width: 700px; padding: 14px 24px;
            background: var(--primary); color: #fff; border: none; border-radius: 12px;
            font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: background 0.2s, transform 0.1s;
        }
        .submit-bar .btn-submit:hover { background: var(--primary-dark); }
        .submit-bar .btn-submit:active { transform: scale(0.98); }
        .submit-bar .btn-submit:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }

        /* Recent Records Section */
        .section-header {
            font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 600;
            color: var(--text); margin-bottom: 12px; margin-top: 8px; padding-left: 2px;
        }

        .recent-card {
            background: var(--surface); border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05); padding: 14px 16px; margin-bottom: 10px;
        }
        .recent-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .recent-card-date { font-size: 12px; color: var(--text-muted); font-weight: 500; }

        .reason-badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;
        }
        .reason-badge.spoilage { background: #fef3c7; color: #d97706; }
        .reason-badge.damage { background: #fee2e2; color: #dc2626; }
        .reason-badge.theft { background: #fce7f3; color: #be185d; }
        .reason-badge.expired { background: #e5e7eb; color: #374151; }
        .reason-badge.other { background: #dbeafe; color: #2563eb; }

        .recent-card-desc { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
        .recent-card-barcode { font-size: 12px; color: var(--text-muted); margin-bottom: 6px; }
        .recent-card-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .recent-card-qty { font-size: 13px; font-weight: 700; color: var(--primary); }
        .recent-card-remark {
            font-size: 12px; color: var(--text-muted); font-style: italic;
            text-align: right; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        .no-records { text-align: center; color: var(--text-muted); font-size: 14px; padding: 30px 16px; }
        .loading-spinner { text-align: center; padding: 24px; color: var(--text-muted); font-size: 14px; }

        /* Hidden file input */
        .hidden-input { display: none; }

        @media (min-width: 768px) {
            .main-content { padding: 24px 24px 100px; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<header class="page-header">
    <button class="back-btn" onclick="history.back()">
        <svg style="width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polyline points="15 18 9 12 15 6"/></svg>
        Back
    </button>
    <span class="page-title">Stock Loss</span>
    <div class="header-right">
        <span class="item-count-badge" id="itemCountBadge" style="display:none;">0 items</span>
    </div>
</header>

<div class="main-content">
    <!-- Search & Add Product -->
    <div class="search-card">
        <div class="search-wrap">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="productSearch" placeholder="Search product name to add..." autocomplete="off">
            <div class="search-dropdown" id="searchDropdown"></div>
        </div>
    </div>

    <!-- Product Loss List -->
    <div id="lossListSection">
        <div class="list-header" id="listHeader" style="display:none;">
            <span class="list-title">Products to Record</span>
            <span class="list-count" id="listCount">0 items</span>
        </div>
        <div id="lossListContainer">
            <div class="empty-list" id="emptyState">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <div>Search and add products above to record stock loss</div>
            </div>
        </div>
    </div>

    <!-- Recent Losses -->
    <div class="section-header" style="margin-top: 24px;">Recent Records</div>
    <div id="recentContainer">
        <div class="loading-spinner">Loading recent records...</div>
    </div>
</div>

<!-- Fixed Submit Bar -->
<div class="submit-bar" id="submitBar">
    <button class="btn-submit" id="btnSubmit" onclick="submitAllLosses()">Submit All Losses</button>
</div>

<?php include 'mobile-bottombar.php'; ?>

<!-- Hidden camera/file inputs -->
<input type="file" id="cameraInput" class="hidden-input" accept="image/*" capture="environment">
<input type="file" id="fileInput" class="hidden-input" accept="image/*">

<script>
    let lossItems = []; // Array of { id, barcode, name, qoh, qty, reason, remark, imageData }
    let itemIdCounter = 0;
    let searchTimer = null;
    let activeImageItemId = null;

    const searchInput = document.getElementById('productSearch');
    const dropdown = document.getElementById('searchDropdown');
    const cameraInput = document.getElementById('cameraInput');
    const fileInput = document.getElementById('fileInput');

    // ===================== PRODUCT SEARCH (name only) =====================

    function highlightMatch(text, query) {
        if (!query) return escapeHtml(text);
        var escaped = escapeHtml(text);
        var re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escaped.replace(re, '<mark>$1</mark>');
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length === 0) {
            dropdown.classList.remove('active');
            return;
        }
        dropdown.innerHTML = '<div class="dd-msg">Searching...</div>';
        dropdown.classList.add('active');

        searchTimer = setTimeout(function() {
            fetch('staff_stock_loss_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=search&q=' + encodeURIComponent(q)
            })
            .then(r => r.json())
            .then(data => {
                if (!data || data.length === 0) {
                    dropdown.innerHTML = '<div class="dd-msg">No products found</div>';
                    return;
                }
                let html = '';
                data.forEach(function(p) {
                    const alreadyAdded = lossItems.some(item => item.barcode === p.barcode);
                    const dimClass = alreadyAdded ? ' style="opacity:0.5;"' : '';
                    html += '<div class="dd-item"' + dimClass + ' onclick="addProduct(\'' + escapeHtml(p.barcode).replace(/'/g, "&#39;") + '\', \'' + escapeHtml(p.name).replace(/'/g, "&#39;") + '\', ' + parseInt(p.qoh) + ')">';
                    html += '<div class="dd-item-info">';
                    html += '<div class="dd-item-name">' + highlightMatch(p.name, searchInput.value.trim()) + '</div>';
                    html += '<div class="dd-item-barcode">' + escapeHtml(p.barcode) + '</div>';
                    html += '</div>';
                    html += '<div class="dd-item-qoh">QOH: ' + parseInt(p.qoh) + '</div>';
                    html += '</div>';
                });
                dropdown.innerHTML = html;
            })
            .catch(function() {
                dropdown.innerHTML = '<div class="dd-msg">Search failed</div>';
            });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-wrap')) {
            dropdown.classList.remove('active');
        }
    });

    // ===================== ADD / REMOVE PRODUCTS =====================

    function addProduct(barcode, name, qoh) {
        // Check if already added
        if (lossItems.some(item => item.barcode === barcode)) {
            Swal.fire({ icon: 'info', title: 'Already Added', text: 'This product is already in the list.', confirmButtonColor: '#C8102E', timer: 1500, showConfirmButton: false });
            return;
        }

        itemIdCounter++;
        const item = {
            id: itemIdCounter,
            barcode: barcode,
            name: name,
            qoh: qoh,
            qty: 1,
            reason: '',
            remark: '',
            imageData: null
        };
        lossItems.push(item);

        dropdown.classList.remove('active');
        searchInput.value = '';
        searchInput.focus();

        renderList();
    }

    function removeItem(itemId) {
        lossItems = lossItems.filter(i => i.id !== itemId);
        renderList();
    }

    function renderList() {
        const container = document.getElementById('lossListContainer');
        const header = document.getElementById('listHeader');
        const badge = document.getElementById('itemCountBadge');
        const submitBar = document.getElementById('submitBar');
        const emptyState = document.getElementById('emptyState');

        if (lossItems.length === 0) {
            container.innerHTML = '';
            container.appendChild(createEmptyState());
            header.style.display = 'none';
            badge.style.display = 'none';
            submitBar.classList.remove('visible');
            return;
        }

        header.style.display = 'flex';
        document.getElementById('listCount').textContent = lossItems.length + ' item' + (lossItems.length > 1 ? 's' : '');
        badge.style.display = 'inline';
        badge.textContent = lossItems.length + ' item' + (lossItems.length > 1 ? 's' : '');
        submitBar.classList.add('visible');

        let html = '';
        lossItems.forEach(function(item) {
            const imgPreviewContent = item.imageData
                ? '<img src="' + item.imageData + '" alt="Photo">'
                : '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';

            const imgButtons = item.imageData
                ? '<button class="img-btn img-btn-remove" onclick="removeImage(' + item.id + ')"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Remove</button>'
                : '<button class="img-btn" onclick="captureImage(' + item.id + ')"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg> Camera</button>' +
                  '<button class="img-btn" onclick="uploadImage(' + item.id + ')"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload</button>';

            html += '<div class="loss-item" data-item-id="' + item.id + '">';
            html += '  <div class="loss-item-header">';
            html += '    <div class="loss-item-info">';
            html += '      <div class="loss-item-name">' + escapeHtml(item.name) + '</div>';
            html += '      <div class="loss-item-meta">' + escapeHtml(item.barcode) + ' &middot; QOH: ' + item.qoh + '</div>';
            html += '    </div>';
            html += '    <button class="loss-item-remove" onclick="removeItem(' + item.id + ')" title="Remove">';
            html += '      <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            html += '    </button>';
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
            html += '        <option value="Spoilage"' + (item.reason === 'Spoilage' ? ' selected' : '') + '>Spoilage</option>';
            html += '        <option value="Damage"' + (item.reason === 'Damage' ? ' selected' : '') + '>Damage</option>';
            html += '        <option value="Theft"' + (item.reason === 'Theft' ? ' selected' : '') + '>Theft</option>';
            html += '        <option value="Expired"' + (item.reason === 'Expired' ? ' selected' : '') + '>Expired</option>';
            html += '        <option value="Other"' + (item.reason === 'Other' ? ' selected' : '') + '>Other</option>';
            html += '      </select>';
            html += '    </div>';
            html += '    <div class="loss-field full-width">';
            html += '      <label>Remark (optional)</label>';
            html += '      <input type="text" value="' + escapeAttr(item.remark) + '" onchange="updateItem(' + item.id + ', \'remark\', this.value)" placeholder="Optional remark">';
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

    function createEmptyState() {
        const div = document.createElement('div');
        div.className = 'empty-list';
        div.id = 'emptyState';
        div.innerHTML = '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><div>Search and add products above to record stock loss</div>';
        return div;
    }

    function updateItem(itemId, field, value) {
        const item = lossItems.find(i => i.id === itemId);
        if (!item) return;
        if (field === 'qty') {
            item.qty = parseInt(value, 10) || 1;
        } else {
            item[field] = value;
        }
    }

    // ===================== IMAGE CAPTURE / UPLOAD =====================

    function captureImage(itemId) {
        activeImageItemId = itemId;
        cameraInput.value = '';
        cameraInput.click();
    }

    function uploadImage(itemId) {
        activeImageItemId = itemId;
        fileInput.value = '';
        fileInput.click();
    }

    function removeImage(itemId) {
        const item = lossItems.find(i => i.id === itemId);
        if (item) {
            item.imageData = null;
            renderList();
        }
    }

    function handleImageSelect(e) {
        const file = e.target.files[0];
        if (!file || !activeImageItemId) return;

        const item = lossItems.find(i => i.id === activeImageItemId);
        if (!item) return;

        // Resize and compress image
        const reader = new FileReader();
        reader.onload = function(ev) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const maxSize = 800;
                let w = img.width, h = img.height;
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
    }

    cameraInput.addEventListener('change', handleImageSelect);
    fileInput.addEventListener('change', handleImageSelect);

    // ===================== SUBMIT ALL LOSSES =====================

    function submitAllLosses() {
        if (lossItems.length === 0) return;

        // Validate all items
        for (let i = 0; i < lossItems.length; i++) {
            const item = lossItems[i];
            if (!item.qty || item.qty < 1) {
                Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid quantity for "' + item.name + '".', confirmButtonColor: '#C8102E' });
                return;
            }
            if (!item.reason) {
                Swal.fire({ icon: 'warning', title: 'Missing Reason', text: 'Please select a reason for "' + item.name + '".', confirmButtonColor: '#C8102E' });
                return;
            }
        }

        const totalItems = lossItems.length;
        const totalQty = lossItems.reduce((sum, i) => sum + i.qty, 0);

        Swal.fire({
            title: 'Confirm Stock Loss',
            html: '<strong>' + totalItems + ' product(s)</strong> with a total of <strong>' + totalQty + ' unit(s)</strong> will be deducted from inventory.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#C8102E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Record All',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                doSubmit();
            }
        });
    }

    function doSubmit() {
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.textContent = 'Submitting...';

        // Build FormData with items and images
        const formData = new FormData();
        formData.append('action', 'record_multiple');

        const itemsData = lossItems.map(function(item) {
            return {
                barcode: item.barcode,
                qty: item.qty,
                reason: item.reason.toUpperCase(),
                remark: item.remark
            };
        });
        formData.append('items', JSON.stringify(itemsData));

        // Append images as separate fields
        lossItems.forEach(function(item, index) {
            if (item.imageData) {
                formData.append('image_' + index, item.imageData);
            }
        });

        fetch('staff_stock_loss_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Submit All Losses';

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Recorded',
                    text: data.success,
                    confirmButtonColor: '#C8102E'
                });
                lossItems = [];
                renderList();
                loadRecent();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error || 'Failed to record stock loss.',
                    confirmButtonColor: '#C8102E'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = 'Submit All Losses';
            console.error('Submit error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#C8102E'
            });
        });
    }

    // ===================== RECENT RECORDS =====================

    function getReasonBadgeClass(reason) {
        switch (reason.toLowerCase()) {
            case 'spoilage': return 'spoilage';
            case 'damage': return 'damage';
            case 'theft': return 'theft';
            case 'expired': return 'expired';
            default: return 'other';
        }
    }

    function loadRecent() {
        const container = document.getElementById('recentContainer');
        container.innerHTML = '<div class="loading-spinner">Loading recent records...</div>';

        fetch('staff_stock_loss_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=recent'
        })
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                let html = '';
                data.forEach(function(rec) {
                    const reason = rec.LOSS_REASON || 'Other';
                    const badgeClass = getReasonBadgeClass(reason);
                    const remarkText = rec.REMARK || '';
                    const remarkHtml = remarkText ? '<span class="recent-card-remark">' + escapeHtml(remarkText) + '</span>' : '';
                    const qty = Math.abs(parseFloat(rec.QTYADJ || 0));

                    html += '<div class="recent-card">';
                    html += '  <div class="recent-card-top">';
                    html += '    <span class="recent-card-date">' + escapeHtml(rec.SDATE || '') + '</span>';
                    html += '    <span class="reason-badge ' + badgeClass + '">' + escapeHtml(reason) + '</span>';
                    html += '  </div>';
                    html += '  <div class="recent-card-desc">' + escapeHtml(rec.PDESC || '') + '</div>';
                    html += '  <div class="recent-card-barcode">Barcode: ' + escapeHtml(rec.BARCODE || '') + '</div>';
                    html += '  <div class="recent-card-bottom">';
                    html += '    <span class="recent-card-qty">-' + qty + ' unit(s)</span>';
                    html += '    ' + remarkHtml;
                    html += '  </div>';
                    html += '</div>';
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="no-records">No recent stock loss records found.</div>';
            }
        })
        .catch(err => {
            console.error('Load recent error:', err);
            container.innerHTML = '<div class="no-records">Failed to load recent records.</div>';
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function escapeAttr(text) {
        return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderList();
        loadRecent();
    });
</script>

</body>
</html>
