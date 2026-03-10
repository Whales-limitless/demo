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
$rowpurchasedate = '';


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
    $rowpurchasedate = $row['PURCHASEDATE'] ?? '';

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

        // Get rack remark from PRODUCTS.rack field
        $rack_remark = '';
        $remark_q = $connect->query("SELECT `rack` FROM `PRODUCTS` WHERE `barcode` = '$barcode' LIMIT 1");
        if ($remark_q && $rr2 = $remark_q->fetch_assoc()) {
            $rack_remark = $rr2['rack'] ?? '';
        }

        $order_items[] = [
            'barcode' => $irow['BARCODE'] ?? '',
            'pdesc'   => $irow['PDESC'] ?? '',
            'qty'     => $qty,
            'rack'    => $rack_code,
            'rack_remark' => $rack_remark
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
    max-width: 302px;
    margin: 0 auto;
    padding: 8px 4px;
}

.detail-card {
    background: #fff;
    border-radius: var(--radius);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    padding: 8px;
    margin-bottom: 8px;
}

/* Print-only styles */
.office-copy { display: none; }

@media print {
    .no-print { display: none !important; }
    .office-copy { display: block; }
    body { background: #fff; margin: 0; padding: 0; }
    .detail-card { box-shadow: none; padding: 0; border-radius: 0; }
    .detail-container { padding: 0; max-width: 80mm; margin: 0; }
    @page { size: 80mm auto; margin: 2mm; }
}

/* Toolbar */
.detail-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 8px;
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
.btn-print { background: #3b82f6; color: #fff; }
.btn-print:hover { background: #2563eb; color: #fff; }
/* Header section */
.order-header {
    text-align: center;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px dashed var(--text);
}

.order-header img { width: 50px; height: 50px; margin-bottom: 4px; }
.order-header .merchant-name { font-weight: 700; font-size: 13px; }
.order-header .merchant-info { font-size: 10px; color: var(--text-muted); }

/* Info grid */
.info-table { width: 100%; margin-bottom: 8px; font-size: 11px; }
.info-table td { padding: 1px 2px; vertical-align: top; }
.info-table .label { color: var(--text-muted); white-space: nowrap; }
.info-table .sep { width: 8px; }
.info-table .highlight { font-size: 14px; font-weight: 700; }

/* Items table */
.items-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 4px; }
.items-table thead td {
    border-top: 1px dashed var(--text);
    border-bottom: 1px dashed var(--text);
    padding: 4px 2px;
    font-weight: 600;
    font-size: 10px;
}
.items-table tbody td { padding: 3px 2px; border-bottom: 1px dotted #ddd; font-size: 10px; }
.items-table .text-right { text-align: right; }
.items-table .rack-remark { font-size: 9px; color: var(--text-muted); font-style: italic; }
.items-table tfoot td { padding: 3px 2px; }
.items-table tfoot .total-row td { font-weight: 700; border-top: 1px dashed var(--text); }

/* Order footer info */
.order-footer {
    border-top: 1px dashed var(--text);
    padding-top: 6px;
    font-size: 10px;
    color: var(--text-muted);
}

/* Rack group styles */
.rack-group-header {
    background: #f0f4ff;
    border-left: 3px solid #3b82f6;
    padding: 4px 6px;
    margin-top: 6px;
    margin-bottom: 0;
    border-radius: 2px 2px 0 0;
    display: flex;
    align-items: center;
    gap: 4px;
}
.rack-group-header i { color: #3b82f6; font-size: 10px; }
.rack-group-header .rack-label { font-weight: 700; font-size: 11px; color: #1e40af; }
.rack-group-header .rack-count { font-size: 9px; color: var(--text-muted); }
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
            <button type="submit" name="submit_print" class="btn-toolbar btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
        </form>
    </div>

    <!-- Order Detail Card -->
    <div class="detail-card">

        <!-- Header -->
        <div class="order-header">
            <div style="text-align:center;font-weight:700;font-size:13px;margin-bottom:4px;">Purchase Order</div>
            <img src="../logo/logo.png" alt="Logo" onerror="this.style.display='none'">
            <div class="merchant-name"><?php echo htmlspecialchars($mer_name); ?></div>
            <div class="merchant-info"><?php echo htmlspecialchars($mer_addr); ?></div>
        </div>

        <!-- TO -->
        <?php if (!empty($rowto)): ?>
        <div style="font-size:14px;font-weight:700;margin-bottom:6px;">TO: <?php echo htmlspecialchars($rowto); ?></div>
        <?php endif; ?>

        <!-- Order Info -->
        <table class="info-table">
            <tr>
                <td class="label">Order ID</td>
                <td class="sep">:</td>
                <td><strong><?php echo htmlspecialchars($roworderid); ?></strong></td>
            </tr>
            <tr>
                <td class="label">Purchase Date</td>
                <td class="sep">:</td>
                <td><?php echo !empty($rowpurchasedate) ? date('d/m/Y', strtotime($rowpurchasedate)) : ''; ?></td>
            </tr>
            <tr>
                <td class="label">Delivery Date</td>
                <td class="sep">:</td>
                <td><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : ''; ?> <?php echo htmlspecialchars($rowttime); ?></td>
            </tr>
            <tr>
                <td class="label">Staff</td>
                <td class="sep">:</td>
                <td><?php echo htmlspecialchars($rowname); ?></td>
            </tr>
        </table>

        <!-- Items Grouped by Rack -->
        <?php foreach ($grouped_items as $rackName => $items): ?>
        <div class="rack-group-header<?php echo $rackName === 'Unassigned' ? ' unassigned' : ''; ?>">
            <i class="fas fa-<?php echo $rackName === 'Unassigned' ? 'question-circle' : 'warehouse'; ?>"></i>
            <span class="rack-label"><?php echo htmlspecialchars($rackName); ?></span>
            <span class="rack-count">(<?php echo count($items); ?> item<?php echo count($items) > 1 ? 's' : ''; ?>)</span>
        </div>
        <table class="items-table" style="margin-top:0;">
            <thead>
                <tr>
                    <td style="width:15%">Qty</td>
                    <td style="width:50%">Item</td>
                    <td style="width:35%" class="text-right">Rack Remark</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo $item['qty']; ?></td>
                    <td><?php echo htmlspecialchars($item['pdesc']); ?></td>
                    <td class="text-right rack-remark"><?php echo htmlspecialchars($item['rack_remark']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; ?>

        <!-- Footer -->
        <div class="order-footer">
            <p style="text-align:center;margin:4px 0;">--- End ---</p>
        </div>

        <!-- Office Copy (print only) -->
        <div class="office-copy" style="page-break-before:always;">
            <div class="order-header">
                <div style="text-align:center;font-weight:700;font-size:13px;margin-bottom:4px;">Purchase Order</div>
                <img src="../logo/logo.png" alt="Logo" style="width:50px;height:50px;" onerror="this.style.display='none'">
                <div class="merchant-name"><?php echo htmlspecialchars($mer_name); ?></div>
                <div class="merchant-info"><?php echo htmlspecialchars($mer_addr); ?></div>
            </div>

            <?php if (!empty($rowto)): ?>
            <div style="font-size:14px;font-weight:700;margin-bottom:6px;">TO: <?php echo htmlspecialchars($rowto); ?></div>
            <?php endif; ?>

            <table class="info-table">
                <tr>
                    <td class="label">Order ID</td>
                    <td class="sep">:</td>
                    <td><strong><?php echo htmlspecialchars($roworderid); ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Purchase Date</td>
                    <td class="sep">:</td>
                    <td><?php echo !empty($rowpurchasedate) ? date('d/m/Y', strtotime($rowpurchasedate)) : ''; ?></td>
                </tr>
                <tr>
                    <td class="label">Delivery Date</td>
                    <td class="sep">:</td>
                    <td><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : ''; ?> <?php echo htmlspecialchars($rowttime); ?></td>
                </tr>
                <tr>
                    <td class="label">Staff</td>
                    <td class="sep">:</td>
                    <td><?php echo htmlspecialchars($rowname); ?></td>
                </tr>
            </table>

            <?php foreach ($grouped_items as $rackName => $items): ?>
            <div class="rack-group-header<?php echo $rackName === 'Unassigned' ? ' unassigned' : ''; ?>">
                <i class="fas fa-<?php echo $rackName === 'Unassigned' ? 'question-circle' : 'warehouse'; ?>"></i>
                <span class="rack-label"><?php echo htmlspecialchars($rackName); ?></span>
                <span class="rack-count">(<?php echo count($items); ?> item<?php echo count($items) > 1 ? 's' : ''; ?>)</span>
            </div>
            <table class="items-table" style="margin-top:0;">
                <thead>
                    <tr>
                        <td style="width:15%">Qty</td>
                        <td style="width:50%">Item</td>
                        <td style="width:35%" class="text-right">Rack Remark</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo $item['qty']; ?></td>
                        <td><?php echo htmlspecialchars($item['pdesc']); ?></td>
                        <td class="text-right rack-remark"><?php echo htmlspecialchars($item['rack_remark']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>

            <div style="margin-top:10px;font-size:10px;">
                <p style="margin:0;">OFFICE COPY</p>
            </div>

            <div class="order-footer" style="margin-top:6px;">
                <p style="text-align:center;margin:4px 0;">--- End ---</p>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
