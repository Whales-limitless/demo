<?php
require_once __DIR__ . '/session_security.php';
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

// Use sysfile user_code as driver code for delivery users
$driverCode = '';
$driverName = '';
$userType = $_SESSION['user_type'] ?? 'S';
if ($userType === 'D' || $userType === 'A') {
    $driverCode = $_SESSION['user_code'] ?? '';
    $driverName = $_SESSION['user_name'] ?? '';
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
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
        .item-install-badge { display: inline-block; background: #fef3c7; color: #92400e; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px; margin-left: 6px; white-space: nowrap; }

        /* DO modal */
        .do-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: flex-start; justify-content: center; padding: 16px; overflow-y: auto; }
        .do-overlay.active { display: flex; }
        .do-modal { background: var(--surface); border-radius: 16px; max-width: 700px; width: 100%; margin: 24px auto; padding: 0; overflow: hidden; }
        .do-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; background: var(--primary); color: #fff; }
        .do-modal-header h3 { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 600; margin: 0; }
        .do-modal-header .do-close-btn { background: rgba(255,255,255,0.2); border: none; cursor: pointer; padding: 6px; border-radius: 8px; color: #fff; display: flex; align-items: center; }
        .do-modal-header .do-close-btn:hover { background: rgba(255,255,255,0.3); }
        .do-modal-header .do-close-btn svg { width: 20px; height: 20px; }
        .do-modal-header .do-print-btn { background: rgba(255,255,255,0.2); border: none; color: #fff; padding: 6px 12px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; }
        .do-modal-header .do-print-btn:hover { background: rgba(255,255,255,0.3); }
        .do-modal-header .do-print-btn svg { width: 14px; height: 14px; }
        .do-modal-body { padding: 20px; max-height: 75vh; overflow-y: auto; }
        .do-m-company { text-align: center; margin-bottom: 16px; border-bottom: 2px solid var(--text); padding-bottom: 10px; }
        .do-m-company h4 { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; margin: 0; }
        .do-m-company small { font-size: 11px; color: var(--text-muted); }
        .do-m-company .do-m-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; margin-top: 6px; }
        .do-m-info { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 14px; font-size: 13px; }
        .do-m-info-item { display: flex; gap: 6px; }
        .do-m-info-item.full { grid-column: 1 / -1; }
        .do-m-info-label { font-weight: 700; white-space: nowrap; }
        .do-m-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 13px; }
        .do-m-table th { background: var(--text); color: #fff; padding: 7px 10px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .do-m-table td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; }
        .do-m-table th:first-child, .do-m-table td:first-child { width: 40px; text-align: center; }
        .do-m-table th:last-child, .do-m-table td:last-child { width: 80px; text-align: center; }
        .do-m-sig { margin-top: 16px; border: 2px dashed #d1d5db; border-radius: 8px; padding: 14px; text-align: center; min-height: 80px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .do-m-sig img { max-width: 280px; max-height: 90px; }
        .do-m-sig p { font-size: 12px; color: var(--text-muted); margin-top: 6px; }
        .do-m-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .do-m-footer-label { font-weight: 700; text-transform: uppercase; font-size: 11px; color: var(--text-muted); margin-bottom: 2px; }
        .do-m-footer-value { font-size: 13px; }
        @media print {
            .do-overlay.active { position: static; background: none; padding: 0; display: block; }
            .do-modal { box-shadow: none; border-radius: 0; margin: 0; }
            .do-modal-header .do-close-btn, .do-modal-header .do-print-btn { display: none !important; }
            .do-modal-body { max-height: none; overflow: visible; }
            body > *:not(.do-overlay) { display: none !important; }
        }

        /* Sync history */
        .sync-section { margin-top: 20px; margin-bottom: 16px; }
        .sync-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .sync-header h3 { font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .sync-header h3 svg { width: 18px; height: 18px; }
        .sync-badge { font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 12px; }
        .sync-badge.pending { background: #fef3c7; color: #92400e; }
        .sync-badge.syncing { background: #dbeafe; color: #2563eb; }
        .sync-badge.done { background: #dcfce7; color: #16a34a; }
        .sync-btn { padding: 6px 14px; border: none; border-radius: 8px; background: #3b82f6; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; }
        .sync-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .sync-list { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); overflow: hidden; }
        .sync-item { padding: 10px 14px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .sync-item:last-child { border-bottom: none; }
        .sync-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sync-icon svg { width: 16px; height: 16px; }
        .sync-icon.photo { background: #dbeafe; color: #2563eb; }
        .sync-icon.install { background: #fef3c7; color: #d97706; }
        .sync-icon.signature { background: #f3e8ff; color: #7c3aed; }
        .sync-icon.done { background: #dcfce7; color: #16a34a; }
        .sync-info { flex: 1; min-width: 0; }
        .sync-desc { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sync-time { font-size: 11px; color: var(--text-muted); }
        .sync-status { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 8px; white-space: nowrap; }
        .sync-status.pending { background: #fef3c7; color: #92400e; }
        .sync-status.synced { background: #dcfce7; color: #16a34a; }
        .sync-status.error { background: #fee2e2; color: #dc2626; }
        .sync-empty { text-align: center; padding: 16px; color: var(--text-muted); font-size: 13px; }
        .online-indicator { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 16px; }
        .online-indicator.online { background: #dcfce7; color: #16a34a; }
        .online-indicator.offline { background: #fee2e2; color: #dc2626; }
        .online-indicator .dot { width: 8px; height: 8px; border-radius: 50%; }
        .online-indicator.online .dot { background: #16a34a; }
        .online-indicator.offline .dot { background: #dc2626; }
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
                <button class="action-btn" onclick="showDO('<?php echo htmlspecialchars($o['ORDNO'] ?? '', ENT_QUOTES); ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    DO
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Sync History Section -->
        <div class="sync-section" id="syncSection" style="display:none;">
            <div class="sync-header">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                    Sync History
                    <span class="sync-badge pending" id="syncPendingBadge" style="display:none;">0 pending</span>
                </h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <span class="online-indicator" id="onlineIndicator"><span class="dot"></span><span id="onlineText">Online</span></span>
                    <button class="sync-btn" id="syncNowBtn" onclick="manualSync()">Sync Now</button>
                </div>
            </div>
            <div class="sync-list" id="syncList">
                <div class="sync-empty">No sync history.</div>
            </div>
        </div>

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

    <!-- DO Modal -->
    <div class="do-overlay" id="doOverlay" onclick="if(event.target===this)closeDO()">
        <div class="do-modal">
            <div class="do-modal-header">
                <h3 id="doModalTitle">Delivery Order</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <button class="do-print-btn" onclick="window.print()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Print
                    </button>
                    <button class="do-close-btn" onclick="closeDO()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
            <div class="do-modal-body" id="doModalBody">
                <p style="color:var(--text-muted);text-align:center;padding:40px;">Loading...</p>
            </div>
        </div>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script>
    function renderItems(items) {
        if (items.length === 0) {
            document.getElementById('itemsBody').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;">No items found.</p>';
            return;
        }
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var installBadge = (items[i].INSTALL === 'Y') ? '<span class="item-install-badge">Installation</span>' : '';
            html += '<div class="item-row"><span class="item-num">' + (i + 1) + '.</span><span class="item-desc">' + escHtml(items[i].PDESC || '') + installBadge + '</span><span class="item-qty">' + escHtml(items[i].QTY || '') + ' ' + escHtml(items[i].UOM || '') + '</span></div>';
        }
        document.getElementById('itemsBody').innerHTML = html;
    }

    function getItemsFromOfflineData(ordno) {
        if (typeof OfflineSync === 'undefined' || !OfflineSync.getData) return Promise.resolve(null);
        return OfflineSync.getData('delivery_data').then(function(record) {
            if (!record || !record.data || !record.data.orders) return null;
            for (var i = 0; i < record.data.orders.length; i++) {
                if (record.data.orders[i].ORDNO === ordno) {
                    return record.data.orders[i].items || [];
                }
            }
            return null;
        }).catch(function() { return null; });
    }

    function showItems(orderId, ordno) {
        document.getElementById('itemsTitle').textContent = 'Items - ' + ordno;
        document.getElementById('itemsBody').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px;">Loading...</p>';
        document.getElementById('itemsOverlay').classList.add('active');

        // Try network first, fall back to offline data
        fetch('del_dashboard_ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=items&id=' + encodeURIComponent(orderId)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { document.getElementById('itemsBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:20px;">' + data.error + '</p>'; return; }
            renderItems(data.items || []);
        })
        .catch(function() {
            // Network failed - try offline data
            getItemsFromOfflineData(ordno).then(function(items) {
                if (items !== null) {
                    renderItems(items);
                } else {
                    document.getElementById('itemsBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:20px;">Offline - item data not downloaded. Use "Download All for Offline" from the Home page.</p>';
                }
            });
        });
    }

    function closeItems() {
        document.getElementById('itemsOverlay').classList.remove('active');
    }

    function showDO(ordno) {
        document.getElementById('doModalTitle').textContent = 'Delivery Order - ' + ordno;
        document.getElementById('doModalBody').innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:40px;">Loading...</p>';
        document.getElementById('doOverlay').classList.add('active');

        fetch('del_dashboard_ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=vieworder&ordno=' + encodeURIComponent(ordno)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                document.getElementById('doModalBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:40px;">' + escHtml(data.error) + '</p>';
                return;
            }
            renderDO(data);
        })
        .catch(function() {
            document.getElementById('doModalBody').innerHTML = '<p style="color:#dc2626;text-align:center;padding:40px;">Failed to load order. Please try again.</p>';
        });
    }

    function renderDO(data) {
        var o = data.order;
        var c = data.customer;
        var items = data.items || [];

        var html = '<div class="do-m-company">';
        html += '<h4>PARKWAY FURNITURE SDN BHD</h4>';
        html += '<small>(CO. NO 771304-T)</small><br>';
        html += '<small>TEL: 011-26114677/082-764677 HP:017-8129799</small>';
        html += '<div class="do-m-title">DELIVERY ORDER</div>';
        html += '</div>';

        html += '<div class="do-m-info">';
        html += '<div class="do-m-info-item"><span class="do-m-info-label">Order No:</span> <span>' + escHtml(o.ORDNO) + '</span></div>';
        html += '<div class="do-m-info-item"><span class="do-m-info-label">Del. Date:</span> <span>' + escHtml(o.DELDATE) + '</span></div>';
        html += '<div class="do-m-info-item"><span class="do-m-info-label">Customer:</span> <span>' + escHtml(o.CUSTOMER) + '</span></div>';
        html += '<div class="do-m-info-item"><span class="do-m-info-label">Driver:</span> <span>' + escHtml(o.DRIVER) + '</span></div>';
        if (c) {
            html += '<div class="do-m-info-item full"><span class="do-m-info-label">Address:</span> <span>' + escHtml(c.ADDRESS || '') + '</span></div>';
            html += '<div class="do-m-info-item"><span class="do-m-info-label">Tel:</span> <span>' + escHtml(c.HP || '') + '</span></div>';
        }
        html += '</div>';

        html += '<table class="do-m-table"><thead><tr><th>No.</th><th>Description</th><th>Qty</th><th>Install</th></tr></thead><tbody>';
        if (items.length === 0) {
            html += '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px;">No items</td></tr>';
        } else {
            for (var i = 0; i < items.length; i++) {
                var inst = (items[i].INSTALL === 'Y') ? '<span style="color:#f59e0b;font-weight:600;">Yes</span>' : '-';
                html += '<tr><td>' + (i + 1) + '</td><td>' + escHtml(items[i].PDESC || '') + '</td><td>' + escHtml((items[i].QTY || '') + ' ' + (items[i].UOM || '')) + '</td><td>' + inst + '</td></tr>';
            }
        }
        html += '</tbody></table>';

        if (o.REMARK) {
            html += '<div style="font-size:13px;margin-bottom:14px;"><strong>Remark:</strong> ' + escHtml(o.REMARK) + '</div>';
        }

        html += '<div class="do-m-sig">';
        if (data.hasSigFile) {
            html += '<img src="' + escHtml(data.sigPath) + '" alt="Signature">';
            html += '<p>Customer Signature</p>';
        } else {
            html += '<p style="color:var(--text-muted);">No signature captured yet</p>';
        }
        var signBtnLabel = data.hasSigFile ? 'Re-capture Signature' : 'Capture Signature';
        html += '<a href="del_sign.php?ordno=' + encodeURIComponent(o.ORDNO) + '&id=' + o.ID + '" style="display:inline-flex;align-items:center;gap:6px;padding:10px 24px;background:#C8102E;color:#fff;border:none;border-radius:10px;font-family:DM Sans,sans-serif;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;margin-top:12px;">';
        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>';
        html += signBtnLabel + '</a>';
        html += '</div>';

        if (o.LOCATION) {
            html += '<div class="do-m-footer"><div><div class="do-m-footer-label">Location</div><div class="do-m-footer-value">' + escHtml(o.LOCATION) + '</div></div></div>';
        }

        document.getElementById('doModalBody').innerHTML = html;
    }

    function closeDO() {
        document.getElementById('doOverlay').classList.remove('active');
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // Sync History UI
    var typeIcons = {
        photo_upload: { cls: 'photo', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>' },
        install_upload: { cls: 'install', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>' },
        signature: { cls: 'signature', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.83 2.83 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>' },
        job_done: { cls: 'done', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' }
    };

    function formatSyncDate(iso) {
        if (!iso) return '';
        var d = new Date(iso);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    }

    function renderSyncList(records) {
        var section = document.getElementById('syncSection');
        var list = document.getElementById('syncList');
        var badge = document.getElementById('syncPendingBadge');

        if (!records || records.length === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        var pending = records.filter(function(r) { return r.status === 'pending'; });

        if (pending.length > 0) {
            badge.style.display = 'inline';
            badge.textContent = pending.length + ' pending';
        } else {
            badge.style.display = 'none';
        }

        // Sort newest first
        records.sort(function(a, b) { return b.id - a.id; });

        var html = '';
        var shown = Math.min(records.length, 20);
        for (var i = 0; i < shown; i++) {
            var r = records[i];
            var ti = typeIcons[r.type] || typeIcons.photo_upload;
            var statusCls = r.status === 'synced' ? 'synced' : (r.error ? 'error' : 'pending');
            var statusText = r.status === 'synced' ? 'Synced' : (r.error ? 'Error' : 'Pending');
            var timeStr = r.status === 'synced' ? formatSyncDate(r.synced_at) : formatSyncDate(r.created_at);

            html += '<div class="sync-item">';
            html += '<div class="sync-icon ' + ti.cls + '">' + ti.icon + '</div>';
            html += '<div class="sync-info"><div class="sync-desc">' + escHtml(r.description) + '</div><div class="sync-time">' + timeStr + '</div></div>';
            html += '<span class="sync-status ' + statusCls + '">' + statusText + '</span>';
            html += '</div>';
        }

        list.innerHTML = html || '<div class="sync-empty">No sync history.</div>';
    }

    function updateOnlineIndicator() {
        var ind = document.getElementById('onlineIndicator');
        var txt = document.getElementById('onlineText');
        if (navigator.onLine) {
            ind.className = 'online-indicator online';
            txt.textContent = 'Online';
        } else {
            ind.className = 'online-indicator offline';
            txt.textContent = 'Offline';
        }
    }

    function manualSync() {
        var btn = document.getElementById('syncNowBtn');
        btn.disabled = true;
        btn.textContent = 'Syncing...';
        OfflineSync.syncAll().then(function() {
            btn.disabled = false;
            btn.textContent = 'Sync Now';
        }).catch(function() {
            btn.disabled = false;
            btn.textContent = 'Sync Now';
        });
    }

    // Register sync UI callback
    if (typeof OfflineSync !== 'undefined') {
        OfflineSync.onSyncUpdate(function(state, pendingCount, allRecords) {
            renderSyncList(allRecords);
        });

        // Initial load
        OfflineSync.getAll().then(function(records) {
            renderSyncList(records);
        });
    }

    // Online/offline indicator
    updateOnlineIndicator();
    window.addEventListener('online', updateOnlineIndicator);
    window.addEventListener('offline', updateOnlineIndicator);

    // Auto-open DO modal if redirected from signature page
    (function() {
        var params = new URLSearchParams(window.location.search);
        var autoShowOrdno = params.get('showdo');
        if (autoShowOrdno) {
            showDO(autoShowOrdno);
        }
    })();
    </script>
</body>
</html>
