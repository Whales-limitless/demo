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
    <title>Stock Take</title>
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

        /* Page Header */
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
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .back-btn svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            max-width: 700px;
            margin: 0 auto;
            padding: 16px;
        }

        /* Session List View */
        .section-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 16px;
        }

        .session-card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 16px;
            margin-bottom: 12px;
            transition: box-shadow 0.2s;
        }

        .session-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12), 0 4px 16px rgba(0, 0, 0, 0.06);
        }

        .session-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .session-code {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }

        .session-description {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
            line-height: 1.4;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .status-badge.open {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-badge.in-progress {
            background: #fef3c7;
            color: #d97706;
        }

        .session-card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .session-progress {
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .progress-bar-wrap {
            width: 80px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .open-session-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .open-session-btn:hover {
            background: var(--primary-dark);
        }

        .open-session-btn svg {
            width: 16px;
            height: 16px;
        }

        /* Loading & Empty States */
        .loading-state,
        .empty-state {
            text-align: center;
            padding: 48px 16px;
            color: var(--text-muted);
        }

        .loading-state svg,
        .empty-state svg {
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .loading-spinner {
            display: inline-block;
            width: 36px;
            height: 36px;
            border: 3px solid #e5e7eb;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-bottom: 12px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Count View */
        .count-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .count-back-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--surface);
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        .count-back-btn:hover {
            background: #f9fafb;
        }

        .count-back-btn svg {
            width: 20px;
            height: 20px;
            color: var(--text);
        }

        .count-session-info {
            flex: 1;
            min-width: 0;
        }

        .count-session-code {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }

        .count-session-desc {
            font-size: 13px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Search Input */
        .search-wrap {
            position: relative;
            margin-bottom: 16px;
        }

        .search-wrap svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 10px 12px 10px 40px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text);
            background: var(--surface);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .search-input::placeholder {
            color: var(--text-muted);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.1);
        }

        /* Item Cards */
        .item-card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
            padding: 16px;
            margin-bottom: 12px;
        }

        .item-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 12px;
        }

        .item-barcode {
            font-family: 'DM Sans', monospace;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            background: #fef2f2;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .item-description {
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            line-height: 1.4;
            margin-bottom: 12px;
        }

        .item-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .item-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .item-field.full-width {
            grid-column: 1 / -1;
        }

        .item-field-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .item-field-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            padding: 8px 10px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .item-field input[type="number"],
        .item-field input[type="text"] {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 500;
            color: var(--text);
            background: var(--surface);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .item-field input[type="number"]:focus,
        .item-field input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.1);
        }

        /* Remove number input spinners on mobile for cleaner look */
        .item-field input[type="number"]::-webkit-inner-spin-button,
        .item-field input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .item-field input[type="number"] {
            -moz-appearance: textfield;
        }

        .variance-value {
            font-size: 15px;
            font-weight: 700;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            text-align: center;
        }

        .variance-value.positive {
            color: #16a34a;
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .variance-value.negative {
            color: #dc2626;
            background: #fef2f2;
            border-color: #fecaca;
        }

        .variance-value.zero {
            color: var(--text-muted);
        }

        /* Fixed Save Button */
        .save-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 99;
            background: var(--surface);
            padding: 12px 16px;
            box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
        }

        .save-bar .save-btn {
            width: 100%;
            max-width: 700px;
            padding: 14px 24px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .save-bar .save-btn:hover {
            background: var(--primary-dark);
        }

        .save-bar .save-btn:active {
            transform: scale(0.98);
        }

        .save-bar .save-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .save-bar .save-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Item count summary in count view */
        .items-summary {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .items-summary strong {
            color: var(--text);
        }

        /* No results from search */
        .no-results {
            text-align: center;
            padding: 32px 16px;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {
            .main-content {
                padding: 12px;
            }

            .item-fields {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .session-card-bottom {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .open-session-btn {
                justify-content: center;
            }
        }

        /* Adjust padding when save bar visible */
        body.count-active {
            padding-bottom: 140px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <button class="back-btn" onclick="history.back()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
            Back
        </button>
        <span class="page-title">Stock Take</span>
    </header>

    <div class="main-content">

        <!-- Session List View -->
        <div id="sessionListView">
            <h2 class="section-heading">Stock Take Sessions</h2>
            <div id="sessionListContainer">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading sessions...</p>
                </div>
            </div>
        </div>

        <!-- Count View (hidden initially) -->
        <div id="countView" style="display: none;">
            <div class="count-header">
                <button class="count-back-btn" onclick="backToList()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                    </svg>
                </button>
                <div class="count-session-info">
                    <div class="count-session-code" id="countSessionCode"></div>
                    <div class="count-session-desc" id="countSessionDesc"></div>
                </div>
            </div>

            <div class="search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="text" class="search-input" id="itemSearch" placeholder="Search by barcode or description..." oninput="filterItems()">
            </div>

            <div class="items-summary" id="itemsSummary"></div>

            <div id="itemsContainer"></div>
        </div>

    </div>

    <!-- Fixed Save Bar (only visible in count view) -->
    <div class="save-bar" id="saveBar" style="display: none;">
        <button class="save-btn" onclick="saveCounts()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            Save Counts
        </button>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script>
        let currentSessionId = null;
        let currentItems = [];

        // ---- Session List ----

        function loadSessions() {
            const container = document.getElementById('sessionListContainer');
            container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Loading sessions...</p></div>';

            fetch('staff_stock_take_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=list_sessions'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.sessions && data.sessions.length > 0) {
                    renderSessions(data.sessions);
                } else if (data.success && (!data.sessions || data.sessions.length === 0)) {
                    container.innerHTML = '<div class="empty-state">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>' +
                        '</svg>' +
                        '<p>No open stock take sessions found.</p>' +
                        '</div>';
                } else {
                    container.innerHTML = '<div class="empty-state"><p>Error loading sessions. Please try again.</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading sessions:', error);
                container.innerHTML = '<div class="empty-state"><p>Error loading sessions. Please try again.</p></div>';
            });
        }

        function renderSessions(sessions) {
            const container = document.getElementById('sessionListContainer');
            let html = '';

            sessions.forEach(session => {
                const statusClass = session.status === 'IN_PROGRESS' ? 'in-progress' : 'open';
                const statusLabel = session.status === 'IN_PROGRESS' ? 'In Progress' : 'Open';
                const counted = parseInt(session.counted) || 0;
                const total = parseInt(session.total) || 0;
                const progressPct = total > 0 ? Math.round((counted / total) * 100) : 0;

                html += '<div class="session-card">' +
                    '<div class="session-card-top">' +
                        '<div>' +
                            '<div class="session-code">' + escapeHtml(session.session_code) + '</div>' +
                            '<div class="session-description">' + escapeHtml(session.description || 'No description') + '</div>' +
                        '</div>' +
                        '<span class="status-badge ' + statusClass + '">' + statusLabel + '</span>' +
                    '</div>' +
                    '<div class="session-card-bottom">' +
                        '<div class="session-progress">' +
                            '<span>' + counted + ' / ' + total + ' counted</span>' +
                            '<div class="progress-bar-wrap">' +
                                '<div class="progress-bar-fill" style="width: ' + progressPct + '%"></div>' +
                            '</div>' +
                        '</div>' +
                        '<button class="open-session-btn" onclick="openSession(' + session.id + ')">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                                '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>' +
                            '</svg>' +
                            'Open' +
                        '</button>' +
                    '</div>' +
                '</div>';
            });

            container.innerHTML = html;
        }

        // ---- Count View ----

        function openSession(sessionId) {
            currentSessionId = sessionId;
            const container = document.getElementById('itemsContainer');
            container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Loading items...</p></div>';

            document.getElementById('sessionListView').style.display = 'none';
            document.getElementById('countView').style.display = 'block';
            document.getElementById('saveBar').style.display = 'flex';
            document.body.classList.add('count-active');
            document.getElementById('itemSearch').value = '';

            fetch('staff_stock_take_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_items&session_id=' + encodeURIComponent(sessionId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('countSessionCode').textContent = data.session_code || 'Session #' + sessionId;
                    document.getElementById('countSessionDesc').textContent = data.description || '';
                    currentItems = data.items || [];
                    renderItems(currentItems);
                } else {
                    container.innerHTML = '<div class="empty-state"><p>Error loading items. Please try again.</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading items:', error);
                container.innerHTML = '<div class="empty-state"><p>Error loading items. Please try again.</p></div>';
            });
        }

        function renderItems(items) {
            const container = document.getElementById('itemsContainer');
            const summary = document.getElementById('itemsSummary');

            if (items.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No items in this session.</p></div>';
                summary.innerHTML = '';
                return;
            }

            summary.innerHTML = 'Showing <strong>' + items.length + '</strong> item' + (items.length !== 1 ? 's' : '');

            let html = '';

            items.forEach((item, index) => {
                const systemQty = parseFloat(item.system_qty) || 0;
                const countedQty = item.counted_qty !== null && item.counted_qty !== '' ? parseFloat(item.counted_qty) : '';
                const remark = item.remark || '';
                const variance = countedQty !== '' ? (countedQty - systemQty) : '';
                let varianceClass = 'zero';
                let varianceDisplay = '-';

                if (variance !== '') {
                    if (variance > 0) {
                        varianceClass = 'positive';
                        varianceDisplay = '+' + variance;
                    } else if (variance < 0) {
                        varianceClass = 'negative';
                        varianceDisplay = '' + variance;
                    } else {
                        varianceClass = 'zero';
                        varianceDisplay = '0';
                    }
                }

                html += '<div class="item-card" data-barcode="' + escapeAttr(item.barcode || '') + '" data-description="' + escapeAttr(item.description || '') + '" data-item-id="' + escapeAttr(item.id) + '">' +
                    '<div class="item-card-header">' +
                        '<span class="item-barcode">' + escapeHtml(item.barcode || 'N/A') + '</span>' +
                    '</div>' +
                    '<div class="item-description">' + escapeHtml(item.description || 'No description') + '</div>' +
                    '<div class="item-fields">' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">System Qty</span>' +
                            '<div class="item-field-value">' + systemQty + '</div>' +
                        '</div>' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">Counted Qty</span>' +
                            '<input type="number" class="counted-input" data-index="' + index + '" data-system-qty="' + systemQty + '" value="' + (countedQty !== '' ? countedQty : '') + '" placeholder="Enter count" inputmode="numeric" onchange="updateVariance(this)" oninput="updateVariance(this)">' +
                        '</div>' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">Variance</span>' +
                            '<div class="variance-value ' + varianceClass + '" data-index="' + index + '">' + varianceDisplay + '</div>' +
                        '</div>' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">Remark</span>' +
                            '<input type="text" class="remark-input" data-index="' + index + '" value="' + escapeAttr(remark) + '" placeholder="Optional remark">' +
                        '</div>' +
                    '</div>' +
                '</div>';
            });

            container.innerHTML = html;
        }

        function updateVariance(input) {
            const index = input.getAttribute('data-index');
            const systemQty = parseFloat(input.getAttribute('data-system-qty')) || 0;
            const countedQty = input.value !== '' ? parseFloat(input.value) : '';
            const varianceEl = document.querySelector('.variance-value[data-index="' + index + '"]');

            if (!varianceEl) return;

            if (countedQty === '' || isNaN(countedQty)) {
                varianceEl.textContent = '-';
                varianceEl.className = 'variance-value zero';
                return;
            }

            const variance = countedQty - systemQty;

            if (variance > 0) {
                varianceEl.textContent = '+' + variance;
                varianceEl.className = 'variance-value positive';
            } else if (variance < 0) {
                varianceEl.textContent = '' + variance;
                varianceEl.className = 'variance-value negative';
            } else {
                varianceEl.textContent = '0';
                varianceEl.className = 'variance-value zero';
            }
        }

        function filterItems() {
            const query = document.getElementById('itemSearch').value.toLowerCase().trim();
            const cards = document.querySelectorAll('#itemsContainer .item-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const barcode = (card.getAttribute('data-barcode') || '').toLowerCase();
                const description = (card.getAttribute('data-description') || '').toLowerCase();

                if (query === '' || barcode.includes(query) || description.includes(query)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const summary = document.getElementById('itemsSummary');

            if (query !== '' && visibleCount === 0) {
                let noResults = document.getElementById('noResultsMsg');
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.id = 'noResultsMsg';
                    noResults.className = 'no-results';
                    noResults.textContent = 'No items match your search.';
                    document.getElementById('itemsContainer').appendChild(noResults);
                }
                noResults.style.display = 'block';
                summary.innerHTML = 'No items match "<strong>' + escapeHtml(query) + '</strong>"';
            } else {
                const noResults = document.getElementById('noResultsMsg');
                if (noResults) noResults.style.display = 'none';
                summary.innerHTML = 'Showing <strong>' + visibleCount + '</strong> of <strong>' + currentItems.length + '</strong> item' + (currentItems.length !== 1 ? 's' : '');
            }
        }

        function backToList() {
            currentSessionId = null;
            currentItems = [];
            document.getElementById('countView').style.display = 'none';
            document.getElementById('saveBar').style.display = 'none';
            document.getElementById('sessionListView').style.display = 'block';
            document.body.classList.remove('count-active');
            loadSessions();
        }

        function saveCounts() {
            const itemCards = document.querySelectorAll('#itemsContainer .item-card');
            const counts = [];

            itemCards.forEach(card => {
                const itemId = card.getAttribute('data-item-id');
                const countedInput = card.querySelector('.counted-input');
                const remarkInput = card.querySelector('.remark-input');

                const countedVal = countedInput ? countedInput.value : '';
                const remarkVal = remarkInput ? remarkInput.value : '';

                if (countedVal !== '') {
                    counts.push({
                        item_id: itemId,
                        counted_qty: countedVal,
                        remark: remarkVal
                    });
                }
            });

            if (counts.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Counts',
                    text: 'Please enter at least one counted quantity before saving.',
                    confirmButtonColor: '#C8102E'
                });
                return;
            }

            const saveBtn = document.querySelector('.save-btn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<div class="loading-spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></div> Saving...';

            fetch('staff_stock_take_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_counts',
                    session_id: currentSessionId,
                    counts: counts
                })
            })
            .then(response => response.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Save Counts';

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Counts Saved',
                        text: 'Stock take counts have been saved successfully.',
                        confirmButtonColor: '#C8102E'
                    }).then(() => {
                        openSession(currentSessionId);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: data.message || 'An error occurred while saving counts.',
                        confirmButtonColor: '#C8102E'
                    });
                }
            })
            .catch(error => {
                console.error('Error saving counts:', error);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Save Counts';

                Swal.fire({
                    icon: 'error',
                    title: 'Save Failed',
                    text: 'A network error occurred. Please try again.',
                    confirmButtonColor: '#C8102E'
                });
            });
        }

        // ---- Utility Functions ----

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function escapeAttr(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#39;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;');
        }

        // ---- Init ----
        document.addEventListener('DOMContentLoaded', function() {
            loadSessions();
        });
    </script>
</body>
</html>
