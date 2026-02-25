<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Look up driver code from del_driver table by matching staff username
$driverCode = '';
$driverName = '';
$staffUser = $_SESSION['user_name'] ?? '';
$stmt = $connect->prepare("SELECT `CODE`, `NAME` FROM `del_driver` WHERE `USERNAME` = ? LIMIT 1");
$stmt->bind_param("s", $staffUser);
$stmt->execute();
$dResult = $stmt->get_result();
if ($dResult->num_rows > 0) {
    $dRow = $dResult->fetch_assoc();
    $driverCode = $dRow['CODE'];
    $driverName = $dRow['NAME'];
}
$stmt->close();

// Get filter
$filter = $_GET['filter'] ?? 'today';
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$where = "WHERE o.DRIVERCODE = ? AND o.STATUS = 'A'";
$params = [$driverCode];
$types = "s";

if ($filter === 'today') {
    $where .= " AND o.DELDATE = ?";
    $params[] = $today;
    $types .= "s";
} elseif ($filter === 'yesterday') {
    $where .= " AND o.DELDATE = ?";
    $params[] = $yesterday;
    $types .= "s";
}

$orders = [];
if ($driverCode !== '') {
    $sql = "SELECT o.*, c.NAME AS CUSTNAME, c.HP AS CUSTPHONE, c.ADDRESS AS CUSTADDRESS
            FROM `del_orderlist` o
            LEFT JOIN `del_customer` c ON o.CUSTOMERCODE = c.CODE
            $where
            ORDER BY o.DELDATE DESC, o.ORDNO ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param($types, ...$params);
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
    <title>My Deliveries</title>
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
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 80px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        .filter-tabs { display: flex; gap: 8px; margin-bottom: 16px; overflow-x: auto; }
        .filter-tab { padding: 8px 18px; border-radius: 20px; border: 2px solid #e5e7eb; background: var(--surface); font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; white-space: nowrap; text-decoration: none; transition: all 0.2s; }
        .filter-tab:hover { border-color: var(--primary); color: var(--primary); }
        .filter-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        .order-count { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }
        .order-count strong { color: var(--text); }

        .order-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04); padding: 16px; margin-bottom: 12px; }
        .order-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
        .order-date { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .order-badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #dbeafe; color: #2563eb; }
        .order-customer { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 600; margin-bottom: 4px; }
        .order-address { font-size: 13px; color: var(--text-muted); line-height: 1.4; margin-bottom: 4px; }
        .order-remark { font-size: 13px; color: #d97706; font-style: italic; margin-bottom: 10px; }

        .order-contact { display: flex; gap: 8px; margin-bottom: 12px; }
        .contact-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; color: #fff; transition: opacity 0.2s; }
        .contact-btn:hover { opacity: 0.85; }
        .contact-btn.whatsapp { background: #25d366; }
        .contact-btn.call { background: #3b82f6; }
        .contact-btn svg { width: 14px; height: 14px; }

        .order-actions { display: flex; gap: 8px; }
        .action-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 10px; border: 2px solid #e5e7eb; background: var(--surface); font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text); cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .action-btn:hover { border-color: var(--primary); color: var(--primary); }
        .action-btn.go { background: var(--primary); color: #fff; border-color: var(--primary); }
        .action-btn.go:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .action-btn svg { width: 16px; height: 16px; }

        .empty-state { text-align: center; padding: 48px 16px; color: var(--text-muted); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state p { font-size: 14px; }

        .not-driver { text-align: center; padding: 60px 20px; }
        .not-driver svg { width: 56px; height: 56px; color: var(--text-muted); opacity: 0.4; margin-bottom: 16px; }
        .not-driver h2 { font-size: 18px; margin-bottom: 8px; }
        .not-driver p { font-size: 14px; color: var(--text-muted); }

        /* Items modal */
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
        <span class="page-title">My Deliveries</span>
    </header>

    <div class="main-content">
    <?php if ($driverCode === ''): ?>
        <div class="not-driver">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <h2>Not a Driver</h2>
            <p>Your account is not linked to a driver profile. Please contact the administrator.</p>
        </div>
    <?php else: ?>
        <div class="filter-tabs">
            <a href="?filter=today" class="filter-tab <?php echo $filter === 'today' ? 'active' : ''; ?>">Today</a>
            <a href="?filter=yesterday" class="filter-tab <?php echo $filter === 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
            <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
        </div>

        <div class="order-count">Showing <strong><?php echo count($orders); ?></strong> assigned delivery(s)</div>

        <?php if (count($orders) === 0): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <p>No assigned deliveries found.</p>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $o):
            $custPhone = $o['CUSTPHONE'] ?? '';
            $waPhone = preg_replace('/[^0-9]/', '', $custPhone);
            if ($waPhone && substr($waPhone, 0, 1) === '0') { $waPhone = '60' . substr($waPhone, 1); }
        ?>
        <div class="order-card">
            <div class="order-card-top">
                <div>
                    <div class="order-date"><?php echo htmlspecialchars($o['DELDATE'] ?? ''); ?> &middot; <?php echo htmlspecialchars($o['ORDNO'] ?? ''); ?></div>
                    <div class="order-customer"><?php echo htmlspecialchars($o['CUSTNAME'] ?? $o['CUSTOMER'] ?? ''); ?></div>
                </div>
                <span class="order-badge">Assigned</span>
            </div>
            <?php if (!empty($o['CUSTADDRESS'])): ?>
            <div class="order-address"><?php echo htmlspecialchars($o['CUSTADDRESS']); ?></div>
            <?php endif; ?>
            <?php if (!empty($o['REMARK'])): ?>
            <div class="order-remark"><?php echo htmlspecialchars($o['REMARK']); ?></div>
            <?php endif; ?>
            <?php if ($custPhone !== ''): ?>
            <div class="order-contact">
                <a href="https://wa.me/<?php echo $waPhone; ?>" target="_blank" rel="noopener" class="contact-btn whatsapp">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.612.638l4.716-1.244A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.4 0-4.637-.85-6.378-2.267l-.446-.37-3.12.822.859-3.022-.397-.467A9.953 9.953 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
                    WhatsApp
                </a>
                <a href="tel:<?php echo htmlspecialchars($custPhone); ?>" class="contact-btn call">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                    Call
                </a>
            </div>
            <?php endif; ?>
            <div class="order-actions">
                <a href="del_work.php?id=<?php echo (int)$o['ID']; ?>" class="action-btn go">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    Go
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
