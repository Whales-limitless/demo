<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
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
$rowdelfee = 0;

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
    $rowdelfee  = $row['DELIFEE'] ?? 0;
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

// Get order items
$order_items = [];
$sum = 0;
$discount = 0;
$item_query = $connect->query("SELECT * FROM `orderlist` WHERE SALNUM = '$roworderid' AND PDESC <> 'USE POINTS'");
if ($item_query) {
    while ($irow = $item_query->fetch_assoc()) {
        $qty   = (float)($irow['QTY'] ?? 0);
        $price = (float)($irow['RETAIL'] ?? 0);
        $amt   = (float)($irow['AMOUNT'] ?? 0);
        $disc  = $amt - ($qty * $price);

        // Get rack info
        $rack_info = '';
        $barcode = $connect->real_escape_string($irow['BARCODE'] ?? '');
        $rack_q = $connect->query("SELECT rack FROM PRODUCTS WHERE barcode = '$barcode' LIMIT 1");
        if ($rack_q && $rr = $rack_q->fetch_assoc()) {
            if (!empty($rr['rack'])) $rack_info = 'Rack: ' . $rr['rack'];
        }

        $order_items[] = [
            'barcode' => $irow['BARCODE'] ?? '',
            'pdesc'   => $irow['PDESC'] ?? '',
            'qty'     => $qty,
            'price'   => $price,
            'disc'    => $disc,
            'amt'     => $amt,
            'rack'    => $rack_info
        ];

        $sum += $amt;
        $discount += $disc;
    }
}

// Payment type label
$paymentLabel = 'N/A';
if ($rowptype === 'CS') $paymentLabel = 'Cash';
elseif ($rowptype === 'SnP') $paymentLabel = 'Senangpay';
elseif ($rowptype === 'SnPR') $paymentLabel = 'Senangpay Ins';

// Status label
$statusLabel = 'Unpaid';
if (in_array($rowstatus, ['PAYMENT', 'DONE'])) $statusLabel = 'Paid';
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
.btn-print-receipt { background: #8b5cf6; color: #fff; }
.btn-print-receipt:hover { background: #7c3aed; color: #fff; }

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

/* Receipt-specific styles */
.receipt-view {
    display: none;
    max-width: 300px;
    margin: 0 auto;
    font-size: 12px;
}

.receipt-view .items-table { font-size: 11px; }
.receipt-view .order-header { margin-bottom: 10px; padding-bottom: 10px; }
.receipt-view .info-table { font-size: 12px; }

@media print {
    body.print-receipt .detail-card { max-width: 300px; margin: 0 auto; font-size: 11px; }
    body.print-receipt .items-table { font-size: 10px; }
    body.print-receipt .info-table { font-size: 11px; }
    body.print-receipt .info-table .highlight { font-size: 14px; }
}
</style>
</head>
<body>

<div class="detail-container">
    <!-- Toolbar -->
    <div class="detail-toolbar no-print">
        <a href="dashboard.php" class="btn-toolbar btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="order_detail.php?salnum=<?php echo htmlspecialchars($get_id); ?>&print=receipt" class="btn-toolbar btn-print-receipt"><i class="fas fa-receipt"></i> Print Receipt</a>
        <form method="POST" style="margin:0;display:inline">
            <button type="submit" name="submit_print" class="btn-toolbar btn-print-a4" onclick="window.print();"><i class="fas fa-print"></i> Print A4</button>
        </form>
    </div>

    <!-- Order Detail Card -->
    <div class="detail-card">

        <!-- Header -->
        <div class="order-header">
            <img src="../logo/logo.png" alt="Logo" onerror="this.style.display='none'">
            <div class="merchant-name"><?php echo htmlspecialchars($mer_name); ?></div>
            <div class="merchant-info"><?php echo htmlspecialchars($mer_addr); ?></div>
            <div class="merchant-info">Contact: <?php echo htmlspecialchars($mer_cont); ?></div>
            <div style="text-align:right;margin-top:-40px;font-weight:600;font-size:14px;">Sales Order</div>
        </div>

        <!-- To -->
        <table class="info-table">
            <tr>
                <td class="label highlight">To</td>
                <td class="sep">:</td>
                <td class="highlight" colspan="4"><?php echo htmlspecialchars($rowto); ?></td>
            </tr>
        </table>

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
                <td><?php echo htmlspecialchars($rowname); ?></td>
                <td class="label">Email</td>
                <td class="sep">:</td>
                <td><?php echo htmlspecialchars($rowemail); ?></td>
            </tr>
            <tr>
                <td class="label">Phone</td>
                <td class="sep">:</td>
                <td><?php echo htmlspecialchars($rowphone); ?></td>
                <td></td><td></td><td></td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <td style="width:5%">S/N</td>
                    <td style="width:15%">Barcode</td>
                    <td style="width:35%">Item</td>
                    <td style="width:10%" class="text-right">Qty</td>
                    <td style="width:12%" class="text-right">Price</td>
                    <td style="width:11%" class="text-right">Disc</td>
                    <td style="width:12%" class="text-right">Amt</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $i => $item): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($item['pdesc']); ?>
                        <?php if (!empty($item['rack'])): ?>
                        <div class="rack-info"><?php echo htmlspecialchars($item['rack']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?php echo $item['qty']; ?></td>
                    <td class="text-right"><?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($item['disc'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($item['amt'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="7">&nbsp;</td></tr>
                <tr>
                    <td colspan="6" class="text-right">Discount</td>
                    <td class="text-right"><?php echo number_format($discount, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right">Delivery Fee</td>
                    <td class="text-right"><?php echo number_format($rowdelfee, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="6" class="text-right">Total (RM)</td>
                    <td class="text-right"><?php echo number_format($sum + $rowdelfee, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Info -->
        <div class="order-footer">
            <p>
                <strong>Order date:</strong>
                <?php echo (!empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '') . ' ' . htmlspecialchars($rowttime); ?>
            </p>
            <p>
                <strong>Payment type:</strong> <?php echo $paymentLabel; ?>
            </p>
            <p>
                <strong>Status:</strong>
                <span class="status-badge <?php echo $statusLabel === 'Paid' ? 'status-paid' : 'status-unpaid'; ?>">
                    <?php echo $statusLabel; ?>
                </span>
            </p>
        </div>

        <!-- Office Copy (print only) -->
        <div class="office-copy" style="page-break-before:always;">
            <div class="order-header">
                <img src="../logo/logo.png" alt="Logo" style="width:70px;height:70px;" onerror="this.style.display='none'">
                <div class="merchant-name"><?php echo htmlspecialchars($mer_name); ?></div>
                <div class="merchant-info"><?php echo htmlspecialchars($mer_addr); ?></div>
                <div class="merchant-info">Contact: <?php echo htmlspecialchars($mer_cont); ?></div>
                <div style="text-align:right;margin-top:-40px;font-weight:600;font-size:14px;">Sales Order</div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="label highlight">To</td>
                    <td class="sep">:</td>
                    <td class="highlight" colspan="4"><?php echo htmlspecialchars($rowto); ?></td>
                </tr>
            </table>

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
                    <td><?php echo htmlspecialchars($rowname); ?></td>
                    <td class="label">Email</td>
                    <td class="sep">:</td>
                    <td><?php echo htmlspecialchars($rowemail); ?></td>
                </tr>
                <tr>
                    <td class="label">Phone</td>
                    <td class="sep">:</td>
                    <td><?php echo htmlspecialchars($rowphone); ?></td>
                    <td></td><td></td><td></td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <td style="width:5%">S/N</td>
                        <td style="width:15%">Barcode</td>
                        <td style="width:35%">Item</td>
                        <td style="width:10%" class="text-right">Qty</td>
                        <td style="width:12%" class="text-right">Price</td>
                        <td style="width:11%" class="text-right">Disc</td>
                        <td style="width:12%" class="text-right">Amt</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $i => $item): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['pdesc']); ?>
                            <?php if (!empty($item['rack'])): ?>
                            <div class="rack-info"><?php echo htmlspecialchars($item['rack']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo $item['qty']; ?></td>
                        <td class="text-right"><?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['disc'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($item['amt'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="7">&nbsp;</td></tr>
                    <tr>
                        <td colspan="6" class="text-right">Discount</td>
                        <td class="text-right"><?php echo number_format($discount, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="6" class="text-right">Delivery Fee</td>
                        <td class="text-right"><?php echo number_format($rowdelfee, 2); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="6" class="text-right">Total (RM)</td>
                        <td class="text-right"><?php echo number_format($sum + $rowdelfee, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

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
