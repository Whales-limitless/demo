<?php
require_once __DIR__ . '/session_security.php';
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$userName = $_SESSION['user_name'] ?? '';

// Month filter - default to current month
$selectedMonth = $_GET['ym'] ?? date('Y-m');

// Fetch STOCKIN orders grouped by SALNUM for selected month
$stockInRecords = [];
if (!empty($selectedMonth)) {
    $ymStart = $selectedMonth . '-01';
    $ymEnd = date('Y-m-t', strtotime($ymStart));

    $stmt = $connect->prepare("
        SELECT SALNUM, NAME, SDATE, TTIME, SUM(QTY) AS TOTAL_QTY, COUNT(*) AS ITEM_COUNT, TXTTO, PTYPE
        FROM `orderlist`
        WHERE PTYPE IN ('STOCKIN','PURCHASE') AND SDATE BETWEEN ? AND ?
        GROUP BY SALNUM
        ORDER BY SDATE DESC, TTIME DESC
    ");
    $stmt->bind_param("ss", $ymStart, $ymEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stockInRecords[] = $row;
    }
    $stmt->close();
}

// Generate month options (current + last 6 months)
$monthOptions = [];
for ($i = 0; $i <= 6; $i++) {
    $monthOptions[] = date('Y-m', strtotime("-$i month"));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History</title>
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
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        /* Month filter */
        .filter-bar { margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .filter-bar label { font-size: 14px; font-weight: 600; white-space: nowrap; }
        .filter-bar select {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            background: var(--surface);
            color: var(--text);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer;
        }
        .filter-bar select:focus { outline: none; border-color: var(--primary); }

        .record-count { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
        .record-count strong { color: var(--text); }

        /* Stock In Card */
        .stockin-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04); padding: 16px; margin-bottom: 12px; }
        .stockin-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
        .stockin-salnum { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 600; }
        .stockin-date { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .stockin-info { font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 10px; }
        .stockin-info span { display: flex; align-items: center; gap: 6px; }
        .stockin-info svg { width: 14px; height: 14px; flex-shrink: 0; }
        .stockin-summary { display: flex; gap: 16px; margin-bottom: 10px; }
        .stockin-stat { font-size: 13px; color: var(--text-muted); }
        .stockin-stat strong { color: var(--text); font-size: 15px; }

        .stockin-actions { display: flex; gap: 8px; }
        .action-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 10px; border: 2px solid #e5e7eb; background: var(--surface); font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .action-btn:hover { border-color: var(--primary); color: var(--primary); }
        .action-btn svg { width: 16px; height: 16px; }

        .type-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
        .badge-stockin { background: #dbeafe; color: #2563eb; }
        .badge-purchase { background: #fef3c7; color: #92400e; }

        .empty-state { text-align: center; padding: 48px 16px; color: var(--text-muted); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state p { font-size: 14px; }

        /* Items Modal */
        .items-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center; padding: 16px; }
        .items-overlay.active { display: flex; }
        .items-modal { background: var(--surface); border-radius: 16px; max-width: 500px; width: 100%; max-height: 80vh; overflow-y: auto; padding: 20px; }
        .items-modal h3 { font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
        .items-modal .close-btn { background: none; border: none; cursor: pointer; padding: 4px; color: var(--text-muted); }
        .items-modal .close-btn svg { width: 20px; height: 20px; }
        .item-row { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
        .item-row:last-child { border-bottom: none; }
        .item-num { color: var(--text-muted); font-weight: 600; min-width: 24px; }
        .item-desc { flex: 1; }
        .item-desc .item-barcode { font-size: 11px; color: var(--text-muted); }
        .item-qty { font-weight: 700; white-space: nowrap; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <a href="account.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>
        <span class="page-title">Purchase History</span>
    </header>

    <div class="main-content">
        <!-- Month Filter -->
        <div class="filter-bar">
            <label>Month:</label>
            <select id="monthFilter" onchange="window.location.href='staff_history.php?ym='+this.value">
                <?php foreach ($monthOptions as $mo): ?>
                <option value="<?php echo $mo; ?>" <?php echo ($mo === $selectedMonth) ? 'selected' : ''; ?>>
                    <?php echo date('F Y', strtotime($mo . '-01')); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="record-count">Showing <strong><?php echo count($stockInRecords); ?></strong> record(s) for <strong><?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></strong></div>

        <?php if (count($stockInRecords) === 0): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <p>No stock-in records for this month.</p>
        </div>
        <?php else: ?>
        <?php foreach ($stockInRecords as $rec): ?>
        <div class="stockin-card">
            <div class="stockin-card-top">
                <div>
                    <div class="stockin-salnum"><?php echo htmlspecialchars($rec['SALNUM']); ?></div>
                    <div class="stockin-date"><?php echo !empty($rec['SDATE']) ? date('d M Y', strtotime($rec['SDATE'])) : ''; ?><?php echo !empty($rec['TTIME']) ? ', ' . date('h:i A', strtotime($rec['TTIME'])) : ''; ?></div>
                </div>
                <span class="type-badge <?php echo ($rec['PTYPE'] ?? '') === 'STOCKIN' ? 'badge-stockin' : 'badge-purchase'; ?>"><?php echo ($rec['PTYPE'] ?? '') === 'STOCKIN' ? 'Stock In' : 'Purchase'; ?></span>
            </div>
            <div class="stockin-summary">
                <div class="stockin-stat"><strong><?php echo intval($rec['ITEM_COUNT']); ?></strong> item(s)</div>
                <div class="stockin-stat">Total qty: <strong><?php echo intval($rec['TOTAL_QTY']); ?></strong></div>
            </div>
            <div class="stockin-info">
                <?php if (!empty($rec['NAME'])): ?>
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    By: <?php echo htmlspecialchars($rec['NAME']); ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($rec['TXTTO'])): ?>
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
                    Remark: <?php echo htmlspecialchars($rec['TXTTO']); ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="stockin-actions">
                <button class="action-btn" onclick="showItems('<?php echo htmlspecialchars($rec['SALNUM'], ENT_QUOTES); ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    View Items
                </button>
                <a class="action-btn" href="stockin_preview.php?salnum=<?php echo urlencode($rec['SALNUM']); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Items Modal -->
    <div class="items-overlay" id="itemsOverlay" onclick="if(event.target===this)closeItems()">
        <div class="items-modal">
            <h3>
                <span id="itemsTitle">Purchase Items</span>
                <button class="close-btn" onclick="closeItems()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </h3>
            <div id="itemsBody"><p style="color:var(--text-muted);text-align:center;padding:20px;">Loading...</p></div>
        </div>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script>
    function showItems(salnum) {
        document.getElementById('itemsTitle').textContent = 'Items - ' + salnum;
        document.getElementById('itemsBody').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;">Loading...</p>';
        document.getElementById('itemsOverlay').classList.add('active');

        fetch('staff_history_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=items&salnum=' + encodeURIComponent(salnum)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                document.getElementById('itemsBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:20px;">' + escHtml(data.error) + '</p>';
                return;
            }
            var items = data.items || [];
            if (items.length === 0) {
                document.getElementById('itemsBody').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;">No items found.</p>';
                return;
            }
            var html = '';
            for (var i = 0; i < items.length; i++) {
                var it = items[i];
                html += '<div class="item-row">';
                html += '<span class="item-num">' + (i + 1) + '.</span>';
                html += '<span class="item-desc">' + escHtml(it.PDESC || '') + '<br><span class="item-barcode">' + escHtml(it.BARCODE || '') + '</span></span>';
                html += '<span class="item-qty">+' + escHtml(it.QTY || '0') + '</span>';
                html += '</div>';
            }
            document.getElementById('itemsBody').innerHTML = html;
        })
        .catch(function() {
            document.getElementById('itemsBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:20px;">Failed to load items.</p>';
        });
    }

    function closeItems() {
        document.getElementById('itemsOverlay').classList.remove('active');
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    </script>
</body>
</html>
