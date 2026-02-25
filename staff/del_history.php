<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Look up driver code
$driverCode = '';
$staffUser = $_SESSION['user_name'] ?? '';
$stmt = $connect->prepare("SELECT `CODE` FROM `del_driver` WHERE `USERNAME` = ? LIMIT 1");
$stmt->bind_param("s", $staffUser);
$stmt->execute();
$dResult = $stmt->get_result();
if ($dResult->num_rows > 0) {
    $driverCode = $dResult->fetch_assoc()['CODE'];
}
$stmt->close();

$orders = [];
if ($driverCode !== '') {
    $sql = "SELECT o.*, c.NAME AS CUSTNAME, c.HP AS CUSTPHONE, c.ADDRESS AS CUSTADDRESS
            FROM `del_orderlist` o
            LEFT JOIN `del_customer` c ON o.CUSTOMER = c.CODE
            WHERE o.DRIVERCODE = ? AND (o.STATUS = 'D' OR o.STATUS = 'C')
            ORDER BY o.DELDATE DESC, o.ORDNO ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $driverCode);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) { $orders[] = $r; }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery History</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 80px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        .order-count { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
        .order-count strong { color: var(--text); }

        .order-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04); padding: 16px; margin-bottom: 12px; }
        .order-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
        .order-date { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .order-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .order-badge.done { background: #fef3c7; color: #d97706; }
        .order-badge.completed { background: #dcfce7; color: #16a34a; }
        .order-customer { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 4px; }
        .order-address { font-size: 13px; color: var(--text-muted); line-height: 1.4; margin-bottom: 10px; }
        .order-meta { display: flex; gap: 12px; font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }
        .order-meta span { display: flex; align-items: center; gap: 4px; }
        .order-meta svg { width: 14px; height: 14px; }

        .order-actions { display: flex; gap: 8px; }
        .action-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 10px; border: 2px solid #e5e7eb; background: var(--surface); font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .action-btn:hover { border-color: var(--primary); color: var(--primary); }
        .action-btn svg { width: 16px; height: 16px; }

        .empty-state { text-align: center; padding: 48px 16px; color: var(--text-muted); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state p { font-size: 14px; }

        .not-driver { text-align: center; padding: 60px 20px; }
        .not-driver svg { width: 56px; height: 56px; color: var(--text-muted); opacity: 0.4; margin-bottom: 16px; }
        .not-driver h2 { font-size: 18px; margin-bottom: 8px; }
        .not-driver p { font-size: 14px; color: var(--text-muted); }

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
        .item-qty { font-weight: 700; white-space: nowrap; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <span class="page-title">Delivery History</span>
    </header>

    <div class="main-content">
    <?php if ($driverCode === ''): ?>
        <div class="not-driver">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <h2>Not a Driver</h2>
            <p>Your account is not linked to a driver profile.</p>
        </div>
    <?php else: ?>
        <div class="order-count">Showing <strong><?php echo count($orders); ?></strong> completed delivery(s)</div>

        <?php if (count($orders) === 0): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <p>No delivery history found.</p>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $o):
            $statusClass = $o['STATUS'] === 'C' ? 'completed' : 'done';
            $statusLabel = $o['STATUS'] === 'C' ? 'Completed' : 'Done';
        ?>
        <div class="order-card">
            <div class="order-card-top">
                <div>
                    <div class="order-date"><?php echo htmlspecialchars($o['DELDATE'] ?? ''); ?> &middot; <?php echo htmlspecialchars($o['ORDNO'] ?? ''); ?></div>
                    <div class="order-customer"><?php echo htmlspecialchars($o['CUSTNAME'] ?? $o['CUSTOMER'] ?? ''); ?></div>
                </div>
                <span class="order-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
            </div>
            <?php if (!empty($o['CUSTADDRESS'])): ?>
            <div class="order-address"><?php echo htmlspecialchars($o['CUSTADDRESS']); ?></div>
            <?php endif; ?>
            <?php if (!empty($o['DONEDATETIME'])): ?>
            <div class="order-meta">
                <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Done: <?php echo htmlspecialchars($o['DONEDATETIME']); ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="order-actions">
                <a href="del_work.php?id=<?php echo (int)$o['ID']; ?>" class="action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    Photos
                </a>
                <button class="action-btn" onclick="showItems(<?php echo (int)$o['ID']; ?>, '<?php echo htmlspecialchars($o['ORDNO'] ?? '', ENT_QUOTES); ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Items
                </button>
                <a href="del_vieworder.php?ordno=<?php echo urlencode($o['ORDNO'] ?? ''); ?>" class="action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    DO
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
    </div>

    <!-- Items Modal -->
    <div class="items-overlay" id="itemsOverlay" onclick="if(event.target===this)closeItems()">
        <div class="items-modal">
            <h3>
                <span id="itemsTitle">Order Items</span>
                <button class="close-btn" onclick="closeItems()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </h3>
            <div id="itemsBody"><p style="color:var(--text-muted);text-align:center;padding:20px;">Loading...</p></div>
        </div>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script>
    function showItems(orderId, ordno) {
        document.getElementById('itemsTitle').textContent = 'Items - ' + ordno;
        document.getElementById('itemsBody').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;">Loading...</p>';
        document.getElementById('itemsOverlay').classList.add('active');

        fetch('del_dashboard_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=items&id=' + encodeURIComponent(orderId)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { document.getElementById('itemsBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:20px;">' + data.error + '</p>'; return; }
            var items = data.items || [];
            if (items.length === 0) {
                document.getElementById('itemsBody').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;">No items found.</p>';
                return;
            }
            var html = '';
            for (var i = 0; i < items.length; i++) {
                html += '<div class="item-row"><span class="item-num">' + (i + 1) + '.</span><span class="item-desc">' + escHtml(items[i].PDESC || '') + '</span><span class="item-qty">' + escHtml(items[i].QTY || '') + ' ' + escHtml(items[i].UOM || '') + '</span></div>';
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
