<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$get_id = $connect->real_escape_string($_GET['salnum'] ?? '0');

// Update view status
$connect->query("UPDATE `orderlist` SET view_status = '1' WHERE SALNUM = '$get_id'");

// Handle print counter
if (isset($_POST["submit_print"])) {
    $connect->query("UPDATE `orderlist` SET PRINT = PRINT + 1 WHERE SALNUM = '$get_id'");
}

// Fetch order header
$raccode = $roworderid = $rowdate = $rowtrack = $rowname = $rowoutlet = '';
$rowttime = $rowptype = $rowstatus = $rowto = $rowphone = $rowemail = $rowaddress = '';
$mer_name = $mer_addr = $mer_cont = '';


$getdata = $connect->query("SELECT * FROM `orderlist` WHERE SALNUM = '$get_id' LIMIT 1");

if ($getdata && $getdata->num_rows > 0) {
    $row = $getdata->fetch_assoc();
    $raccode    = $row['ACCODE'] ?? '';
    $roworderid = $row['SALNUM'] ?? '';
    $rowdate    = $row['SDATE'] ?? '';
    $rowtrack   = $row['TRANSNO'] ?? '';
    $rowname    = $row['NAME'] ?? '';
    $rowoutlet  = $row['OUTLET'] ?? '';
    $rowttime   = $row['TTIME'] ?? '';
    $rowptype   = $row['PTYPE'] ?? '';
    $rowstatus  = $row['STATUS'] ?? '';
    $rowto      = $row['TXTTO'] ?? '';

    // Get member contact
    $query_contact = $connect->query("SELECT * FROM MEMBER WHERE ACCODE = '$raccode'");
    if ($query_contact && $contact_row = $query_contact->fetch_assoc()) {
        $rowphone   = $contact_row['HP'] ?? '';
        $rowemail   = $contact_row['EMAIL'] ?? '';
        $rowaddress = trim(($contact_row['ADD1'] ?? '') . ' ' . ($contact_row['ADD2'] ?? '') . ' ' . ($contact_row['ADD3'] ?? ''));
    }

    // Get outlet/merchant info
    $get_merchant = $connect->query("SELECT * FROM outlet WHERE CODE = '$rowoutlet'");
    if ($get_merchant && $m_row = $get_merchant->fetch_assoc()) {
        $mer_name = $m_row['PDESC'] ?? '';
        $mer_addr = $m_row['ADDRESS'] ?? '';
        $mer_cont = $m_row['CONTACT'] ?? '';
    }
} else {
    echo '<!DOCTYPE html><html><body><h1>Order not found.</h1><a href="dashboard.php">Back to Dashboard</a></body></html>';
    exit;
}

// Get order items with rack info from rack_product table (multi-rack support)
$order_items = [];
$item_query = $connect->query("SELECT * FROM `orderlist` WHERE SALNUM = '$roworderid' AND PDESC <> 'USE POINTS'");
if ($item_query) {
    while ($irow = $item_query->fetch_assoc()) {
        $qty = (float)($irow['QTY'] ?? 0);
        $barcode = $connect->real_escape_string($irow['BARCODE'] ?? '');

        // Get rack from rack_product table (rack management only)
        $rack_code = '';
        $rack_q = $connect->query("
            SELECT r.`code`
            FROM `rack_product` rp
            JOIN `rack` r ON rp.`rack_id` = r.`id`
            WHERE rp.`barcode` = '$barcode' AND r.`status` = 'ACTIVE'
            ORDER BY r.`code` ASC
            LIMIT 1
        ");
        if ($rack_q && $rr = $rack_q->fetch_assoc()) {
            $rack_code = $rr['code'];
        }

        $order_items[] = [
            'barcode' => $irow['BARCODE'] ?? '',
            'pdesc'   => $irow['PDESC'] ?? '',
            'qty'     => $qty,
            'rack'    => $rack_code
        ];
    }
}

// Group items by rack
$grouped_items = [];
foreach ($order_items as $item) {
    $rackKey = !empty($item['rack']) ? $item['rack'] : 'Unassigned';
    if (!isset($grouped_items[$rackKey])) {
        $grouped_items[$rackKey] = [];
    }
    $grouped_items[$rackKey][] = $item;
}
// Sort rack groups alphabetically, Unassigned last
uksort($grouped_items, function($a, $b) {
    if ($a === 'Unassigned') return 1;
    if ($b === 'Unassigned') return -1;
    return strcmp($a, $b);
});

// Status label
$statusLabel = ($rowstatus === 'DONE') ? 'Done' : (($rowstatus === 'DELETED') ? 'Deleted' : 'Pending');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?php echo htmlspecialchars($roworderid); ?> - Detail</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
    --primary: #C8102E;
    --primary-dark: #a00d24;
    --text: #1a1a1a;
    --text-muted: #6b7280;
    --radius: 12px;
}

body {
    font-family: 'DM Sans', sans-serif;
    background: #f3f4f6;
    color: var(--text);
    -webkit-font-smoothing: antialiased;
}

.detail-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 24px 16px;
}

.detail-card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    padding: 32px;
    margin-bottom: 24px;
}

/* Print-only styles */
.office-copy { display: none; }

@media print {
    .no-print { display: none !important; }
    .office-copy { display: block; }
    body { background: #fff; }
    .detail-card { box-shadow: none; padding: 0; border-radius: 0; }
    .detail-container { padding: 0; max-width: 100%; }
}

/* Toolbar */
.detail-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.btn-toolbar {
    padding: 8px 18px;
    border: none;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-back { background: #e5e7eb; color: var(--text); }
.btn-back:hover { background: #d1d5db; color: var(--text); }
.btn-print-a4 { background: #3b82f6; color: #fff; }
.btn-print-a4:hover { background: #2563eb; color: #fff; }
/* Header section */
.order-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--text);
}

.order-header img { width: 70px; height: 70px; margin-bottom: 8px; }
.order-header .merchant-name { font-weight: 700; font-size: 16px; }
.order-header .merchant-info { font-size: 13px; color: var(--text-muted); }

/* Info grid */
.info-table { width: 100%; margin-bottom: 24px; font-size: 14px; }
.info-table td { padding: 4px 8px; vertical-align: top; }
.info-table .label { color: var(--text-muted); width: 13%; }
.info-table .sep { width: 2%; }
.info-table .highlight { font-size: 20px; font-weight: 700; }

/* Items table */
.items-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 16px; }
.items-table thead td {
    border-top: 2px solid var(--text);
    border-bottom: 2px solid var(--text);
    padding: 8px 6px;
    font-weight: 600;
    background: #f9fafb;
}
.items-table tbody td { padding: 6px; border-bottom: 1px solid #f3f4f6; }
.items-table .text-right { text-align: right; }
.items-table .rack-info { font-size: 11px; color: var(--text-muted); font-style: italic; }
.items-table tfoot td { padding: 6px; }
.items-table tfoot .total-row td { font-weight: 700; border-top: 2px solid var(--text); }

/* Order footer info */
.order-footer {
    border-top: 2px solid var(--text);
    padding-top: 12px;
    font-size: 13px;
    color: var(--text-muted);
}

.order-footer .status-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.status-paid { background: #dcfce7; color: #16a34a; }
.status-unpaid { background: #fef2f2; color: #dc2626; }

/* Rack group styles */
.rack-group-header {
    background: #f0f4ff;
    border-left: 4px solid #3b82f6;
    padding: 8px 12px;
    margin-top: 16px;
    margin-bottom: 0;
    border-radius: 4px 4px 0 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.rack-group-header i { color: #3b82f6; font-size: 14px; }
.rack-group-header .rack-label { font-weight: 700; font-size: 14px; color: #1e40af; }
.rack-group-header .rack-count { font-size: 12px; color: var(--text-muted); }
.rack-group-header.unassigned { background: #fef3c7; border-left-color: #f59e0b; }
.rack-group-header.unassigned i { color: #f59e0b; }
.rack-group-header.unassigned .rack-label { color: #92400e; }
</style>
</head>
<body>

<div class="detail-container">
    <!-- Toolbar -->
    <div class="detail-toolbar no-print">
        <a href="dashboard.php" class="btn-toolbar btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        <form method="POST" style="margin:0;display:inline">
            <button type="submit" name="submit_print" class="btn-toolbar btn-print-a4" onclick="window.print();"><i class="fas fa-print"></i> Print A4</button>
        </form>
    </div>

    <!-- Order Detail Card -->
    <div class="detail-card">

        <!-- Header -->
        <div class="order-header">
            <div style="text-align:center;font-weight:700;font-size:16px;margin-bottom:8px;">Purchase Order</div>
            <img src="../logo/logo.png" alt="Logo" onerror="this.style.display='none'">
            <div class="merchant-name"><?php echo htmlspecialchars($mer_name); ?></div>
            <div class="merchant-info"><?php echo htmlspecialchars($mer_addr); ?></div>
        </div>

        <!-- Order Info -->
        <table class="info-table">
            <tr>
                <td class="label">Order ID</td>
                <td class="sep">:</td>
                <td><strong><?php echo htmlspecialchars($roworderid); ?></strong></td>
                <td class="label">Address</td>
                <td class="sep">:</td>
                <td><?php echo htmlspecialchars($rowaddress ?: 'N/A'); ?></td>
            </tr>
            <tr>
                <td class="label">Date</td>
                <td class="sep">:</td>
                <td><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : ''; ?></td>
                <td class="label">Time</td>
                <td class="sep">:</td>
                <td><?php echo htmlspecialchars($rowttime); ?></td>
            </tr>
            <tr>
                <td class="label">Customer</td>
                <td class="sep">:</td>
                <td colspan="4"><?php echo htmlspecialchars($rowname); ?></td>
            </tr>
        </table>

        <!-- Items Grouped by Rack -->
        <?php $sn = 1; foreach ($grouped_items as $rackName => $items): ?>
        <div class="rack-group-header<?php echo $rackName === 'Unassigned' ? ' unassigned' : ''; ?>">
            <i class="fas fa-<?php echo $rackName === 'Unassigned' ? 'question-circle' : 'warehouse'; ?>"></i>
            <span class="rack-label"><?php echo htmlspecialchars($rackName); ?></span>
            <span class="rack-count">(<?php echo count($items); ?> item<?php echo count($items) > 1 ? 's' : ''; ?>)</span>
        </div>
        <table class="items-table" style="margin-top:0;">
            <?php if ($sn === 1): ?>
            <thead>
                <tr>
                    <td style="width:5%">S/N</td>
                    <td style="width:20%">Barcode</td>
                    <td style="width:55%">Item</td>
                    <td style="width:20%" class="text-right">Qty</td>
                </tr>
            </thead>
            <?php endif; ?>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $sn++; ?></td>
                    <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                    <td><?php echo htmlspecialchars($item['pdesc']); ?></td>
                    <td class="text-right"><?php echo $item['qty']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>

        <!-- Footer Info -->
        <div class="order-footer">
            <p>
                <strong>Order date:</strong>
                <?php echo (!empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '') . ' ' . htmlspecialchars($rowttime); ?>
            </p>
            <p>
                <strong>Status:</strong>
                <span class="status-badge <?php echo $statusLabel === 'Done' ? 'status-paid' : 'status-unpaid'; ?>">
                    <?php echo $statusLabel; ?>
                </span>
            </p>
        </div>

        <!-- Office Copy (print only) -->
        <div class="office-copy" style="page-break-before:always;">
            <div class="order-header">
                <div style="text-align:center;font-weight:700;font-size:16px;margin-bottom:8px;">Purchase Order</div>
                <img src="../logo/logo.png" alt="Logo" style="width:70px;height:70px;" onerror="this.style.display='none'">
                <div class="merchant-name"><?php echo htmlspecialchars($mer_name); ?></div>
                <div class="merchant-info"><?php echo htmlspecialchars($mer_addr); ?></div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="label">Order ID</td>
                    <td class="sep">:</td>
                    <td><strong><?php echo htmlspecialchars($roworderid); ?></strong></td>
                    <td class="label">Address</td>
                    <td class="sep">:</td>
                    <td><?php echo htmlspecialchars($rowaddress ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="sep">:</td>
                    <td><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : ''; ?></td>
                    <td class="label">Time</td>
                    <td class="sep">:</td>
                    <td><?php echo htmlspecialchars($rowttime); ?></td>
                </tr>
                <tr>
                    <td class="label">Customer</td>
                    <td class="sep">:</td>
                    <td colspan="4"><?php echo htmlspecialchars($rowname); ?></td>
                </tr>
            </table>

            <?php $sn2 = 1; foreach ($grouped_items as $rackName => $items): ?>
            <div class="rack-group-header<?php echo $rackName === 'Unassigned' ? ' unassigned' : ''; ?>">
                <i class="fas fa-<?php echo $rackName === 'Unassigned' ? 'question-circle' : 'warehouse'; ?>"></i>
                <span class="rack-label"><?php echo htmlspecialchars($rackName); ?></span>
                <span class="rack-count">(<?php echo count($items); ?> item<?php echo count($items) > 1 ? 's' : ''; ?>)</span>
            </div>
            <table class="items-table" style="margin-top:0;">
                <?php if ($sn2 === 1): ?>
                <thead>
                    <tr>
                        <td style="width:5%">S/N</td>
                        <td style="width:20%">Barcode</td>
                        <td style="width:55%">Item</td>
                        <td style="width:20%" class="text-right">Qty</td>
                    </tr>
                </thead>
                <?php endif; ?>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $sn2++; ?></td>
                        <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                        <td><?php echo htmlspecialchars($item['pdesc']); ?></td>
                        <td class="text-right"><?php echo $item['qty']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>

            <table style="width:100%;margin-top:20px;">
                <tr>
                    <td style="width:70%">OFFICE COPY</td>
                    <td style="width:30%;text-align:center;">
                        <br><br>
                        <hr style="border-top:1px solid #000;">
                        Customer Signature
                    </td>
                </tr>
            </table>

            <div class="order-footer" style="margin-top:12px;">
                <p>Order date: <?php echo (!empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '') . ' ' . htmlspecialchars($rowttime); ?></p>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
