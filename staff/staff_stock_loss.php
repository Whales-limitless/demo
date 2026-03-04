<?php
session_start();
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            padding-bottom: 80px;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }

        .page-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--primary);
            color: #fff;
            padding: 0 16px;
            height: 56px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 12px rgba(200, 16, 46, 0.3);
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 4px;
            background: none;
            border: none;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 600;
        }

        .main-content {
            max-width: 700px;
            margin: 0 auto;
            padding: 16px 16px 100px;
        }

        .card {
            background: var(--surface);
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.04);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--text);
            background: var(--bg);
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.1);
        }

        .form-control:disabled {
            background: #e5e7eb;
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        .btn-record {
            width: 100%;
            padding: 13px 20px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            background: var(--primary);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 6px;
        }

        .btn-record:hover {
            background: var(--primary-dark);
        }

        .btn-record:active {
            transform: scale(0.98);
        }

        .btn-record:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .section-header {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 12px;
            padding-left: 2px;
        }

        .recent-card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            padding: 14px 16px;
            margin-bottom: 10px;
        }

        .recent-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .recent-card-date {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .reason-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .reason-badge.spoilage {
            background: #fef3c7;
            color: #d97706;
        }

        .reason-badge.damage {
            background: #fee2e2;
            color: #dc2626;
        }

        .reason-badge.theft {
            background: #fce7f3;
            color: #be185d;
        }

        .reason-badge.expired {
            background: #e5e7eb;
            color: #374151;
        }

        .reason-badge.other {
            background: #dbeafe;
            color: #2563eb;
        }

        .recent-card-desc {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 2px;
        }

        .recent-card-barcode {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .recent-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .recent-card-qty {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
        }

        .recent-card-remark {
            font-size: 12px;
            color: var(--text-muted);
            font-style: italic;
            text-align: right;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .no-records {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
            padding: 30px 16px;
        }

        .loading-spinner {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Product search dropdown */
        .search-wrap { position: relative; }
        .search-wrap .search-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #9ca3af; pointer-events: none; width: 16px; height: 16px;
        }
        .search-wrap .form-control { padding-left: 38px; }
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
        .dd-item-name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dd-item-barcode { font-size: 11px; color: var(--text-muted); font-weight: 500; }
        .dd-item-qoh { font-size: 11px; font-weight: 700; color: var(--primary); white-space: nowrap; }
        .dd-msg { text-align: center; padding: 16px; color: var(--text-muted); font-size: 13px; }
        .selected-product {
            display: flex; align-items: center; gap: 10px; background: #f0fdf4;
            border: 1.5px solid #bbf7d0; border-radius: 10px; padding: 10px 14px;
        }
        .selected-product .sp-info { flex: 1; min-width: 0; }
        .selected-product .sp-name { font-size: 14px; font-weight: 600; }
        .selected-product .sp-meta { font-size: 11px; color: var(--text-muted); }
        .selected-product .sp-clear {
            background: none; border: none; color: var(--text-muted); cursor: pointer;
            padding: 4px; border-radius: 6px; transition: color 0.15s;
        }
        .selected-product .sp-clear:hover { color: var(--primary); }

        @media (min-width: 768px) {
            .main-content {
                padding: 24px 24px 100px;
            }

            .card {
                padding: 24px;
            }
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
</header>

<div class="main-content">
    <!-- Record Loss Form -->
    <div class="card">
        <div class="card-title">Record Loss</div>

        <div class="form-group">
            <label>Product</label>
            <div id="searchArea">
                <div class="search-wrap">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="productSearch" class="form-control" placeholder="Search by barcode or product name..." autocomplete="off">
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>
            </div>
            <div id="selectedArea" style="display:none;">
                <div class="selected-product">
                    <div class="sp-info">
                        <div class="sp-name" id="spName"></div>
                        <div class="sp-meta"><span id="spBarcode"></span> &middot; QOH: <span id="spQoh"></span></div>
                    </div>
                    <button type="button" class="sp-clear" onclick="clearSelection()" title="Change product">
                        <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
            <input type="hidden" id="barcode" value="">
        </div>

        <div class="form-group">
            <label for="quantity">Quantity Lost</label>
            <input type="number" id="quantity" class="form-control" min="1" value="1" placeholder="Enter quantity">
        </div>

        <div class="form-group">
            <label for="reason">Reason</label>
            <select id="reason" class="form-control">
                <option value="">-- Select Reason --</option>
                <option value="Spoilage">Spoilage</option>
                <option value="Damage">Damage</option>
                <option value="Theft">Theft</option>
                <option value="Expired">Expired</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="remark">Remark</label>
            <input type="text" id="remark" class="form-control" placeholder="Optional remark">
        </div>

        <button class="btn-record" id="btnRecord" onclick="recordLoss()">Record Loss</button>
    </div>

    <!-- Recent Losses -->
    <div class="section-header">Recent Records</div>
    <div id="recentContainer">
        <div class="loading-spinner">Loading recent records...</div>
    </div>
</div>

<?php include 'mobile-bottombar.php'; ?>

<script>
    let selectedBarcode = '';
    let searchTimer = null;

    // ===================== PRODUCT SEARCH =====================

    const searchInput = document.getElementById('productSearch');
    const dropdown = document.getElementById('searchDropdown');

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
                    html += '<div class="dd-item" onclick="selectProduct(\'' + escapeHtml(p.barcode).replace(/'/g, "&#39;") + '\', \'' + escapeHtml(p.name).replace(/'/g, "&#39;") + '\', ' + parseInt(p.qoh) + ')">';
                    html += '<div class="dd-item-info">';
                    html += '<div class="dd-item-name">' + escapeHtml(p.name) + '</div>';
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

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-wrap')) {
            dropdown.classList.remove('active');
        }
    });

    function selectProduct(barcode, name, qoh) {
        selectedBarcode = barcode;
        document.getElementById('barcode').value = barcode;
        document.getElementById('spName').textContent = name;
        document.getElementById('spBarcode').textContent = barcode;
        document.getElementById('spQoh').textContent = qoh;

        document.getElementById('searchArea').style.display = 'none';
        document.getElementById('selectedArea').style.display = 'block';
        dropdown.classList.remove('active');
        searchInput.value = '';
    }

    function clearSelection() {
        selectedBarcode = '';
        document.getElementById('barcode').value = '';
        document.getElementById('selectedArea').style.display = 'none';
        document.getElementById('searchArea').style.display = 'block';
        setTimeout(function() { searchInput.focus(); }, 50);
    }

    // ===================== RECORD LOSS =====================

    function recordLoss() {
        const barcode = document.getElementById('barcode').value.trim();
        const quantity = parseInt(document.getElementById('quantity').value, 10);
        const reason = document.getElementById('reason').value;
        const remark = document.getElementById('remark').value.trim();

        if (!barcode) {
            Swal.fire({ icon: 'warning', title: 'No Product', text: 'Please search and select a product first.', confirmButtonColor: '#C8102E' });
            return;
        }

        if (!quantity || quantity < 1) {
            Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid quantity (minimum 1).', confirmButtonColor: '#C8102E' });
            return;
        }

        if (!reason) {
            Swal.fire({ icon: 'warning', title: 'Missing Reason', text: 'Please select a reason for the stock loss.', confirmButtonColor: '#C8102E' });
            return;
        }

        Swal.fire({
            title: 'Confirm Stock Loss',
            html: '<strong>' + quantity + ' unit(s)</strong> will be deducted from inventory.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#C8102E',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Record Loss',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const btnRecord = document.getElementById('btnRecord');
                btnRecord.disabled = true;
                btnRecord.textContent = 'Recording...';

                const params = new URLSearchParams();
                params.append('action', 'record');
                params.append('barcode', barcode);
                params.append('qty', quantity);
                params.append('reason', reason.toUpperCase());
                params.append('remark', remark);

                fetch('staff_stock_loss_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                })
                .then(response => response.json())
                .then(data => {
                    btnRecord.disabled = false;
                    btnRecord.textContent = 'Record Loss';

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Recorded',
                            text: data.success,
                            confirmButtonColor: '#C8102E'
                        });

                        // Reset form
                        clearSelection();
                        document.getElementById('quantity').value = 1;
                        document.getElementById('reason').value = '';
                        document.getElementById('remark').value = '';

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
                    btnRecord.disabled = false;
                    btnRecord.textContent = 'Record Loss';
                    console.error('Record error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred. Please try again.',
                        confirmButtonColor: '#C8102E'
                    });
                });
            }
        });
    }

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

    document.addEventListener('DOMContentLoaded', function() {
        loadRecent();
    });
</script>

</body>
</html>
