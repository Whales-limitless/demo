<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$ordno = $_GET['ordno'] ?? '';
if ($ordno === '') { header("Location: del_dashboard.php"); exit; }

// Fetch order
$stmt = $connect->prepare("SELECT * FROM `del_orderlist` WHERE `ORDNO` = ? LIMIT 1");
$stmt->bind_param("s", $ordno);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { header("Location: del_dashboard.php"); exit; }

// Fetch customer details
$customer = null;
if (!empty($order['CUSTOMERCODE'])) {
    $stmt = $connect->prepare("SELECT `HP`, `ADDRESS` FROM `del_customer` WHERE `CODE` = ? LIMIT 1");
    $stmt->bind_param("s", $order['CUSTOMERCODE']);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Check if signed
$hasSig = false;
$stmt = $connect->prepare("SELECT `ORDNO` FROM `del_sign` WHERE `ORDNO` = ? LIMIT 1");
$stmt->bind_param("s", $ordno);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) { $hasSig = true; }
$stmt->close();

// Fetch order items
$items = [];
$stmt = $connect->prepare("SELECT * FROM `del_orderlistdesc` WHERE `ORDERNO` = ? ORDER BY `PDESC` ASC");
$stmt->bind_param("s", $ordno);
$stmt->execute();
$result = $stmt->get_result();
while ($r = $result->fetch_assoc()) { $items[] = $r; }
$stmt->close();

$safeOrdno = preg_replace('/[\/\\\\:*?"<>|]/', '_', $ordno);
$sigPath = 'uploads/signatures/' . $safeOrdno . '.png';
$hasSigFile = file_exists(__DIR__ . '/' . $sigPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Order - <?php echo htmlspecialchars($ordno); ?></title>
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
        .back-btn { display: flex; align-items: center; gap: 4px; background: none; border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; cursor: pointer; padding: 6px 8px; border-radius: 8px; transition: background 0.2s; text-decoration: none; }
        .back-btn:hover { background: rgba(255,255,255,0.15); }
        .back-btn svg { width: 20px; height: 20px; flex-shrink: 0; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; flex: 1; }
        .print-btn { background: rgba(255,255,255,0.2); border: none; color: #fff; padding: 8px 14px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .print-btn:hover { background: rgba(255,255,255,0.3); }
        .print-btn svg { width: 16px; height: 16px; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        .do-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 24px; margin-bottom: 16px; }
        .do-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid var(--text); padding-bottom: 12px; }
        .do-company { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; }
        .do-company-info { font-size: 11px; color: var(--text-muted); }
        .do-title { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; margin-top: 8px; }

        .do-info { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; font-size: 13px; }
        .do-info-item { display: flex; gap: 6px; }
        .do-info-label { font-weight: 700; white-space: nowrap; }

        .do-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 13px; }
        .do-table th { background: var(--text); color: #fff; padding: 8px 10px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .do-table td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        .do-table th:first-child, .do-table td:first-child { width: 40px; text-align: center; }
        .do-table th:last-child, .do-table td:last-child { width: 80px; text-align: center; }

        .do-signature { margin-top: 20px; border: 2px dashed #d1d5db; border-radius: 8px; padding: 16px; text-align: center; min-height: 100px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .do-signature img { max-width: 300px; max-height: 100px; }
        .do-signature p { font-size: 12px; color: var(--text-muted); margin-top: 8px; }

        .sign-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; margin-top: 12px; }
        .sign-btn:hover { background: var(--primary-dark); }
        .sign-btn svg { width: 18px; height: 18px; }

        .do-footer { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 12px; }
        .do-footer-item { }
        .do-footer-label { font-weight: 700; text-transform: uppercase; font-size: 11px; color: var(--text-muted); margin-bottom: 2px; }
        .do-footer-value { font-size: 13px; }

        @media print {
            .page-header, .print-btn, .sign-btn { display: none !important; }
            body { background: #fff; padding: 0; }
            .main-content { padding: 0; max-width: 100%; }
            .do-card { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <a href="javascript:history.back()" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
            Back
        </a>
        <span class="page-title">Delivery Order</span>
        <button class="print-btn" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
        </button>
    </header>

    <div class="main-content">
        <div class="do-card">
            <div class="do-header">
                <div class="do-company">PARKWAY FURNITURE SDN BHD</div>
                <div class="do-company-info">(CO. NO 771304-T)</div>
                <div class="do-company-info">TEL: 011-26114677/082-764677 HP:017-8129799</div>
                <div class="do-title">DELIVERY ORDER</div>
            </div>

            <div class="do-info">
                <div class="do-info-item"><span class="do-info-label">Order No:</span> <span><?php echo htmlspecialchars($ordno); ?></span></div>
                <div class="do-info-item"><span class="do-info-label">Del. Date:</span> <span><?php echo htmlspecialchars($order['DELDATE'] ?? ''); ?></span></div>
                <div class="do-info-item"><span class="do-info-label">Customer:</span> <span><?php echo htmlspecialchars($order['CUSTOMER'] ?? ''); ?></span></div>
                <div class="do-info-item"><span class="do-info-label">Driver:</span> <span><?php echo htmlspecialchars($order['DRIVER'] ?? ''); ?></span></div>
                <?php if ($customer): ?>
                <div class="do-info-item" style="grid-column:1/-1;"><span class="do-info-label">Address:</span> <span><?php echo htmlspecialchars($customer['ADDRESS'] ?? ''); ?></span></div>
                <div class="do-info-item"><span class="do-info-label">Tel:</span> <span><?php echo htmlspecialchars($customer['HP'] ?? ''); ?></span></div>
                <?php endif; ?>
            </div>

            <table class="do-table">
                <thead>
                    <tr><th>No.</th><th>Description</th><th>Qty</th><th>Install</th></tr>
                </thead>
                <tbody>
                    <?php if (count($items) === 0): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px;">No items</td></tr>
                    <?php else: ?>
                    <?php foreach ($items as $idx => $item): ?>
                    <tr>
                        <td><?php echo $idx + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['PDESC'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(($item['QTY'] ?? '') . ' ' . ($item['UOM'] ?? '')); ?></td>
                        <td><?php echo (isset($item['INSTALL']) && $item['INSTALL'] === 'Y') ? '<span style="color:#f59e0b;font-weight:600;">Yes</span>' : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($order['REMARK'])): ?>
            <div style="font-size:13px;margin-bottom:16px;"><strong>Remark:</strong> <?php echo htmlspecialchars($order['REMARK']); ?></div>
            <?php endif; ?>

            <div class="do-signature">
                <?php if ($hasSigFile): ?>
                <img src="<?php echo htmlspecialchars($sigPath); ?>" alt="Signature">
                <p>Customer Signature</p>
                <?php endif; ?>
                <a href="del_sign.php?ordno=<?php echo urlencode($ordno); ?>&id=<?php echo (int)$order['ID']; ?>" class="sign-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                    <?php echo $hasSigFile ? 'Re-capture Signature' : 'Capture Signature'; ?>
                </a>
            </div>

            <div class="do-footer">
                <div class="do-footer-item">
                    <div class="do-footer-label">Location</div>
                    <div class="do-footer-value"><?php echo htmlspecialchars($order['LOCATION'] ?? ''); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'mobile-bottombar.php'; ?>
</body>
</html>
