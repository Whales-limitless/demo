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

        .status-badge.draft {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-badge.submitted {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-badge.approved {
            background: #f3e8ff;
            color: #7c3aed;
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

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 0;
            margin-bottom: 16px;
            background: var(--surface);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .tab-btn {
            flex: 1;
            padding: 12px 16px;
            border: none;
            background: var(--surface);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            position: relative;
        }

        .tab-btn.active {
            color: var(--primary);
            background: #fef2f2;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }

        .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 6px;
        }

        .tab-badge.pending-badge {
            background: #fef3c7;
            color: #d97706;
        }

        .tab-badge.done-badge {
            background: #dcfce7;
            color: #16a34a;
        }

        .submitted-date {
            font-size: 11px;
            color: #16a34a;
            margin-top: 4px;
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
            z-index: 101;
            background: var(--surface);
            padding: 12px 16px;
            box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
        }

        @media (max-width: 992px) {
            .save-bar {
                bottom: 60px;
            }
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

        /* Rack tags */
        .rack-tags { display: flex; flex-wrap: wrap; gap: 4px; }
        .rack-tag { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px; cursor: pointer; transition: opacity 0.2s; }
        .rack-tag:hover { opacity: 0.8; }
        .rack-tag-select { background: #fef3c7; color: #92400e; }
        .rack-tag-select.unset { background: #f3f4f6; color: var(--text-muted); }
        .rack-tag-remark { background: #e0f2fe; color: #0369a1; }

        /* Rack modal */
        .rack-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 600; justify-content: center; align-items: center; padding: 16px; }
        .rack-modal-overlay.active { display: flex; }
        .rack-modal { background: var(--surface); border-radius: 14px; width: 100%; max-width: 360px; padding: 20px; box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
        .rack-modal h3 { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; margin-bottom: 14px; }
        .rack-modal label { font-size: 13px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 6px; }
        .rack-modal select, .rack-modal input[type="text"] { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s; margin-bottom: 16px; }
        .rack-modal select:focus, .rack-modal input[type="text"]:focus { border-color: var(--primary); }
        .rack-modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .rack-modal-actions button { padding: 9px 20px; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .rack-modal-actions .btn-cancel { background: #f3f4f6; color: var(--text); }
        .rack-modal-actions .btn-cancel:hover { background: #e5e7eb; }
        .rack-modal-actions .btn-save { background: var(--primary); color: #fff; }
        .rack-modal-actions .btn-save:hover { background: var(--primary-dark); }

        /* Print button on session card */
        .print-session-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #6b7280;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .print-session-btn:hover { background: #4b5563; }
        .print-session-btn svg { width: 16px; height: 16px; }

        /* Adjust padding when save bar visible */
        body.count-active {
            padding-bottom: 140px;
        }

        @media (max-width: 992px) {
            body.count-active {
                padding-bottom: 200px;
            }
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

            <div class="tab-nav">
                <button class="tab-btn active" id="tabPending" onclick="switchTab('pending')">
                    Pending <span class="tab-badge pending-badge" id="pendingCount">0</span>
                </button>
                <button class="tab-btn" id="tabSubmitted" onclick="switchTab('submitted')">
                    Submitted <span class="tab-badge done-badge" id="submittedCount">0</span>
                </button>
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

    <!-- Fixed Bottom Bar (only visible in count view) -->
    <div class="save-bar" id="saveBar" style="display: none;">
        <button class="save-btn" style="background:#6b7280;" onclick="saveDraft()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"/>
            </svg>
            Save Draft
        </button>
        <button class="save-btn" style="margin-left:10px;" onclick="submitCount()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
            </svg>
            Submit
        </button>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <!-- Rack Select Modal -->
    <div class="rack-modal-overlay" id="rackModalOverlay">
        <div class="rack-modal">
            <h3>Rack Management</h3>
            <label>Select Rack</label>
            <select id="rackModalSelect"><option value="">-- No Rack --</option></select>
            <div class="rack-modal-actions">
                <button class="btn-cancel" onclick="closeRackModal()">Cancel</button>
                <button class="btn-save" onclick="saveRackSelection()">Save</button>
            </div>
        </div>
    </div>

    <!-- Rack Remark Modal -->
    <div class="rack-modal-overlay" id="rackRemarkModalOverlay">
        <div class="rack-modal">
            <h3>Edit Rack Remark</h3>
            <label>Rack Remark</label>
            <input type="text" id="rackRemarkInput" placeholder="Enter rack remark...">
            <div class="rack-modal-actions">
                <button class="btn-cancel" onclick="closeRackRemarkModal()">Cancel</button>
                <button class="btn-save" onclick="saveRackRemark()">Save</button>
            </div>
        </div>
    </div>

    <script>
        let currentSessionId = null;
        let currentItems = [];
        let currentTab = 'pending';

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
                        '<p>No stock take sessions available.</p>' +
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
                let statusClass = 'draft';
                let statusLabel = 'Draft';
                if (session.status === 'SUBMITTED') { statusClass = 'submitted'; statusLabel = 'Completed'; }
                else if (session.status === 'APPROVED') { statusClass = 'approved'; statusLabel = 'Approved'; }

                const counted = parseInt(session.counted) || 0;
                const total = parseInt(session.total) || 0;
                const progressPct = total > 0 ? Math.round((counted / total) * 100) : 0;
                const isDraft = session.status === 'DRAFT';

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
                        '<button class="print-session-btn" onclick="printStockTake(' + session.id + ')">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                                '<path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>' +
                            '</svg>' +
                            'Print' +
                        '</button>' +
                        (isDraft ?
                        '<button class="open-session-btn" onclick="openSession(' + session.id + ')">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">' +
                                '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>' +
                            '</svg>' +
                            'Start Count' +
                        '</button>'
                        : '') +
                    '</div>' +
                '</div>';
            });

            container.innerHTML = html;
        }

        // ---- Count View ----

        function openSession(sessionId) {
            currentSessionId = sessionId;
            currentTab = 'pending';
            const container = document.getElementById('itemsContainer');
            container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div><p>Loading items...</p></div>';

            document.getElementById('sessionListView').style.display = 'none';
            document.getElementById('countView').style.display = 'block';
            document.getElementById('saveBar').style.display = 'flex';
            document.body.classList.add('count-active');
            document.getElementById('itemSearch').value = '';

            // Reset tab state
            document.getElementById('tabPending').classList.add('active');
            document.getElementById('tabSubmitted').classList.remove('active');

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
                    updateTabCounts();
                    renderItems();
                } else {
                    container.innerHTML = '<div class="empty-state"><p>' + (data.error || 'Error loading items.') + '</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading items:', error);
                container.innerHTML = '<div class="empty-state"><p>Error loading items. Please try again.</p></div>';
            });
        }

        function switchTab(tab) {
            currentTab = tab;
            document.getElementById('tabPending').classList.toggle('active', tab === 'pending');
            document.getElementById('tabSubmitted').classList.toggle('active', tab === 'submitted');
            document.getElementById('itemSearch').value = '';

            // Show/hide save bar based on tab
            document.getElementById('saveBar').style.display = tab === 'pending' ? 'flex' : 'none';

            renderItems();
        }

        function updateTabCounts() {
            const pending = currentItems.filter(i => i.item_status !== 'COUNTED').length;
            const submitted = currentItems.filter(i => i.item_status === 'COUNTED').length;
            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('submittedCount').textContent = submitted;
        }

        function renderItems() {
            const container = document.getElementById('itemsContainer');
            const summary = document.getElementById('itemsSummary');

            const isPending = currentTab === 'pending';
            const filteredItems = currentItems.filter(i =>
                isPending ? (i.item_status !== 'COUNTED') : (i.item_status === 'COUNTED')
            );

            if (filteredItems.length === 0) {
                const msg = isPending ? 'All items have been submitted!' : 'No items submitted yet.';
                container.innerHTML = '<div class="empty-state"><p>' + msg + '</p></div>';
                summary.innerHTML = '';
                return;
            }

            summary.innerHTML = 'Showing <strong>' + filteredItems.length + '</strong> item' + (filteredItems.length !== 1 ? 's' : '');

            let html = '';

            filteredItems.forEach((item, index) => {
                const systemQty = parseFloat(item.system_qty) || 0;
                const countedQty = item.counted_qty !== null && item.counted_qty !== '' ? parseFloat(item.counted_qty) : '';
                const remark = item.remark || '';
                const variance = countedQty !== '' ? (countedQty - systemQty) : '';
                let varianceClass = 'zero';
                let varianceDisplay = '-';

                if (variance !== '') {
                    if (variance > 0) { varianceClass = 'positive'; varianceDisplay = '+' + variance; }
                    else if (variance < 0) { varianceClass = 'negative'; varianceDisplay = '' + variance; }
                    else { varianceClass = 'zero'; varianceDisplay = '0'; }
                }

                const isSubmittedItem = item.item_status === 'COUNTED';

                const rackLoc = item.rack_location || '';
                const prodId = item.product_id || 0;
                const rackSelectLabel = rackLoc ? 'Rack: ' + rackLoc : 'No Rack';
                const rackSelectClass = rackLoc ? 'rack-tag rack-tag-select' : 'rack-tag rack-tag-select unset';
                let rackTagsHtml = '<div class="rack-tags">' +
                    '<span class="' + rackSelectClass + '" onclick="openRackModal(' + prodId + ', \'' + escapeAttr(rackLoc).replace(/'/g, "\\'") + '\', ' + escapeAttr(item.id) + ')">&#9881; ' + escapeHtml(rackSelectLabel) + '</span>';
                if (!rackLoc) {
                    rackTagsHtml += '<span class="rack-tag rack-tag-remark" onclick="openRackRemarkModal(' + prodId + ', \'' + escapeAttr(rackLoc).replace(/'/g, "\\'") + '\', ' + escapeAttr(item.id) + ')">&#9998; Rack Remark</span>';
                }
                rackTagsHtml += '</div>';

                html += '<div class="item-card" data-barcode="' + escapeAttr(item.barcode || '') + '" data-description="' + escapeAttr(item.description || '') + '" data-item-id="' + escapeAttr(item.id) + '" data-product-id="' + prodId + '">' +
                    '<div class="item-card-header">' +
                        '<span class="item-barcode">' + escapeHtml(item.barcode || 'N/A') + '</span>' +
                        rackTagsHtml +
                    '</div>' +
                    '<div class="item-description">' + escapeHtml(item.description || 'No description') + '</div>' +
                    (isSubmittedItem && item.counted_at ? '<div class="submitted-date">Submitted: ' + escapeHtml(item.counted_at) + '</div>' : '') +
                    '<div class="item-fields">' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">System Qty</span>' +
                            '<div class="item-field-value">' + systemQty + '</div>' +
                        '</div>' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">Counted Qty</span>' +
                            (isSubmittedItem ?
                                '<div class="item-field-value">' + (countedQty !== '' ? countedQty : '-') + '</div>' :
                                '<input type="number" class="counted-input" data-index="' + index + '" data-system-qty="' + systemQty + '" value="' + (countedQty !== '' ? countedQty : '') + '" placeholder="Enter count" inputmode="numeric" onchange="updateVariance(this)" oninput="updateVariance(this)">') +
                        '</div>' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">Variance</span>' +
                            '<div class="variance-value ' + varianceClass + '" data-index="' + index + '">' + varianceDisplay + '</div>' +
                        '</div>' +
                        '<div class="item-field">' +
                            '<span class="item-field-label">Remark</span>' +
                            (isSubmittedItem ?
                                '<div class="item-field-value">' + escapeHtml(remark || '-') + '</div>' :
                                '<input type="text" class="remark-input" data-index="' + index + '" value="' + escapeAttr(remark) + '" placeholder="Optional remark">') +
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
            if (variance > 0) { varianceEl.textContent = '+' + variance; varianceEl.className = 'variance-value positive'; }
            else if (variance < 0) { varianceEl.textContent = '' + variance; varianceEl.className = 'variance-value negative'; }
            else { varianceEl.textContent = '0'; varianceEl.className = 'variance-value zero'; }
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
                const total = currentItems.filter(i => currentTab === 'pending' ? i.item_status !== 'COUNTED' : i.item_status === 'COUNTED').length;
                summary.innerHTML = 'Showing <strong>' + visibleCount + '</strong> of <strong>' + total + '</strong> item' + (total !== 1 ? 's' : '');
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

        function gatherCounts() {
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

            return counts;
        }

        function saveDraft() {
            const counts = gatherCounts();
            if (counts.length === 0) {
                Swal.fire({ icon: 'warning', title: 'No Counts', text: 'Please enter at least one counted quantity before saving.', confirmButtonColor: '#C8102E' });
                return;
            }

            const btns = document.querySelectorAll('.save-bar .save-btn');
            btns.forEach(b => b.disabled = true);

            fetch('staff_stock_take_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_counts', session_id: currentSessionId, counts: counts })
            })
            .then(response => response.json())
            .then(data => {
                btns.forEach(b => b.disabled = false);
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Draft Saved', text: 'Your counts have been saved. You can continue counting later.', confirmButtonColor: '#C8102E' }).then(() => {
                        openSession(currentSessionId);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Save Failed', text: data.message || data.error || 'An error occurred.', confirmButtonColor: '#C8102E' });
                }
            })
            .catch(error => {
                btns.forEach(b => b.disabled = false);
                Swal.fire({ icon: 'error', title: 'Save Failed', text: 'A network error occurred. Please try again.', confirmButtonColor: '#C8102E' });
            });
        }

        function submitCount() {
            const counts = gatherCounts();
            if (counts.length === 0) {
                Swal.fire({ icon: 'warning', title: 'No Counts', text: 'Please enter at least one counted quantity before submitting.', confirmButtonColor: '#C8102E' });
                return;
            }

            Swal.fire({
                title: 'Submit ' + counts.length + ' item(s)?',
                text: 'These items will be marked as counted. You can continue counting remaining items later.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#C8102E',
                confirmButtonText: 'Yes, Submit'
            }).then(result => {
                if (!result.isConfirmed) return;

                const btns = document.querySelectorAll('.save-bar .save-btn');
                btns.forEach(b => b.disabled = true);

                fetch('staff_stock_take_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'submit', session_id: currentSessionId, counts: counts })
                })
                .then(response => response.json())
                .then(data => {
                    btns.forEach(b => b.disabled = false);
                    if (data.success) {
                        let msg = data.success;
                        if (data.session_completed) {
                            msg = 'All items submitted! Session is now complete and sent for admin review.';
                        }
                        Swal.fire({ icon: 'success', title: 'Submitted!', text: msg, confirmButtonColor: '#C8102E' }).then(() => {
                            if (data.session_completed) {
                                backToList();
                            } else {
                                openSession(currentSessionId);
                            }
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Submit Failed', text: data.message || data.error || 'An error occurred.', confirmButtonColor: '#C8102E' });
                    }
                })
                .catch(error => {
                    btns.forEach(b => b.disabled = false);
                    Swal.fire({ icon: 'error', title: 'Submit Failed', text: 'A network error occurred. Please try again.', confirmButtonColor: '#C8102E' });
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

        // ---- Rack Management ----
        let rackEditProductId = null;
        let rackEditItemId = null;
        let rackListCache = null;

        function openRackModal(productId, currentRack, itemId) {
            if (!productId) { Swal.fire({ icon: 'warning', text: 'Product not found for this item.' }); return; }
            rackEditProductId = productId;
            rackEditItemId = itemId;
            document.getElementById('rackModalSelect').value = currentRack || '';
            document.getElementById('rackModalOverlay').classList.add('active');

            if (rackListCache === null) {
                fetch('product_rack_ajax.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=rack_list' })
                .then(r => r.json())
                .then(data => { rackListCache = data; populateRackSelect(currentRack); })
                .catch(() => { rackListCache = []; });
            } else {
                populateRackSelect(currentRack);
            }
        }

        function populateRackSelect(currentVal) {
            const sel = document.getElementById('rackModalSelect');
            sel.innerHTML = '<option value="">-- No Rack --</option>';
            (rackListCache || []).forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.code;
                opt.textContent = r.code + (r.description ? ' - ' + r.description : '');
                if (r.code === currentVal) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        function closeRackModal() {
            document.getElementById('rackModalOverlay').classList.remove('active');
            rackEditProductId = null;
            rackEditItemId = null;
        }

        function saveRackSelection() {
            if (!rackEditProductId) return;
            const val = document.getElementById('rackModalSelect').value.trim();
            fetch('product_rack_ajax.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=update_rack&id=' + rackEditProductId + '&rack=' + encodeURIComponent(val) })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    updateItemRack(rackEditProductId, rackEditItemId, resp.rack);
                    closeRackModal();
                } else {
                    Swal.fire({ icon: 'error', text: resp.error || 'Failed to update rack.' });
                }
            })
            .catch(() => { Swal.fire({ icon: 'error', text: 'Failed to update rack.' }); });
        }

        function openRackRemarkModal(productId, currentRack, itemId) {
            if (!productId) { Swal.fire({ icon: 'warning', text: 'Product not found for this item.' }); return; }
            rackEditProductId = productId;
            rackEditItemId = itemId;
            document.getElementById('rackRemarkInput').value = currentRack || '';
            document.getElementById('rackRemarkModalOverlay').classList.add('active');
            document.getElementById('rackRemarkInput').focus();
        }

        function closeRackRemarkModal() {
            document.getElementById('rackRemarkModalOverlay').classList.remove('active');
            rackEditProductId = null;
            rackEditItemId = null;
        }

        function saveRackRemark() {
            if (!rackEditProductId) return;
            const val = document.getElementById('rackRemarkInput').value.trim();
            fetch('product_rack_ajax.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'action=update_rack&id=' + rackEditProductId + '&rack=' + encodeURIComponent(val) })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    updateItemRack(rackEditProductId, rackEditItemId, resp.rack);
                    closeRackRemarkModal();
                } else {
                    Swal.fire({ icon: 'error', text: resp.error || 'Failed to update rack.' });
                }
            })
            .catch(() => { Swal.fire({ icon: 'error', text: 'Failed to update rack.' }); });
        }

        function updateItemRack(productId, itemId, newRack) {
            // Update data model
            currentItems.forEach(item => {
                if (item.product_id == productId) {
                    item.rack_location = newRack || '';
                }
            });
            // Re-render rack tags for the specific card
            const card = document.querySelector('.item-card[data-item-id="' + itemId + '"]');
            if (card) {
                const header = card.querySelector('.item-card-header');
                const oldTags = header.querySelector('.rack-tags');
                if (oldTags) oldTags.remove();

                const rackLoc = newRack || '';
                const rackSelectLabel = rackLoc ? 'Rack: ' + rackLoc : 'No Rack';
                const rackSelectClass = rackLoc ? 'rack-tag rack-tag-select' : 'rack-tag rack-tag-select unset';
                let tagsHtml = '<div class="rack-tags"><span class="' + rackSelectClass + '" onclick="openRackModal(' + productId + ', \'' + escapeAttr(rackLoc).replace(/'/g, "\\'") + '\', ' + itemId + ')">&#9881; ' + escapeHtml(rackSelectLabel) + '</span>';
                if (!rackLoc) {
                    tagsHtml += '<span class="rack-tag rack-tag-remark" onclick="openRackRemarkModal(' + productId + ', \'' + escapeAttr(rackLoc).replace(/'/g, "\\'") + '\', ' + itemId + ')">&#9998; Rack Remark</span>';
                }
                tagsHtml += '</div>';
                header.insertAdjacentHTML('beforeend', tagsHtml);
            }
        }

        // Close rack modals on overlay click
        document.getElementById('rackModalOverlay').addEventListener('click', function(e) { if (e.target === this) closeRackModal(); });
        document.getElementById('rackRemarkModalOverlay').addEventListener('click', function(e) { if (e.target === this) closeRackRemarkModal(); });

        // ---- Print Stock Take Checklist ----

        function printStockTake(sessionId) {
            fetch('staff_stock_take_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_items&session_id=' + encodeURIComponent(sessionId)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire({ icon: 'error', text: data.error || 'Failed to load items.' });
                    return;
                }

                const items = data.items || [];
                const sessionCode = data.session_code || '';
                const desc = data.description || '';
                const now = new Date();
                const printedDate = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

                let rows = '';
                items.forEach(function(item, i) {
                    rows += '<tr>' +
                        '<td style="width:14px;text-align:center;vertical-align:top;padding:3px 2px;">' +
                            '<span style="display:inline-block;width:12px;height:12px;border:1.5px solid #000;border-radius:2px;"></span>' +
                        '</td>' +
                        '<td style="padding:3px 4px;font-size:11px;line-height:1.3;word-break:break-word;">' +
                            escapeHtml(item.description || item.product_desc || 'N/A') +
                        '</td>' +
                    '</tr>';
                });

                const html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                    '<title>Stock Take - ' + escapeHtml(sessionCode) + '</title>' +
                    '<style>' +
                        '@page { margin: 2mm; size: 80mm auto; }' +
                        'body { font-family: Arial, sans-serif; width: 76mm; margin: 0 auto; padding: 2mm 0; color: #000; }' +
                        'h2 { font-size: 14px; text-align: center; margin: 0 0 2px; }' +
                        '.sub { font-size: 11px; text-align: center; color: #333; margin-bottom: 6px; }' +
                        '.meta { font-size: 10px; margin-bottom: 6px; }' +
                        '.meta div { margin-bottom: 1px; }' +
                        'table { width: 100%; border-collapse: collapse; }' +
                        'tr { border-bottom: 0.5px dashed #ccc; }' +
                        'tr:last-child { border-bottom: none; }' +
                        '.footer { font-size: 9px; text-align: center; color: #999; margin-top: 8px; border-top: 1px dashed #ccc; padding-top: 4px; }' +
                    '</style>' +
                    '</head><body>' +
                    '<h2>STOCK TAKE</h2>' +
                    (desc ? '<div class="sub">' + escapeHtml(desc) + '</div>' : '') +
                    '<div class="meta">' +
                        '<div><strong>Session:</strong> ' + escapeHtml(sessionCode) + '</div>' +
                        '<div><strong>Total Items:</strong> ' + items.length + '</div>' +
                    '</div>' +
                    '<table>' + rows + '</table>' +
                    '<div class="footer">' +
                        'Printed: ' + escapeHtml(printedDate) +
                    '</div>' +
                    '</body></html>';

                const printWin = window.open('', '_blank', 'width=320,height=600');
                printWin.document.write(html);
                printWin.document.close();
                printWin.focus();
                setTimeout(function() { printWin.print(); }, 400);
            })
            .catch(function() {
                Swal.fire({ icon: 'error', text: 'Failed to load items for printing.' });
            });
        }

        // ---- Init ----
        document.addEventListener('DOMContentLoaded', function() {
            loadSessions();
        });
    </script>
</body>
</html>
