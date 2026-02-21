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

// Ensure rack tables exist
$connect->query("CREATE TABLE IF NOT EXISTS `rack` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$connect->query("CREATE TABLE IF NOT EXISTS `rack_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rack_id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rack_barcode` (`rack_id`, `barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$get_id = $connect->real_escape_string($_GET['salnum'] ?? '0');

// Update view status
$connect->query("UPDATE `orderlist` SET view_status = '1' WHERE SALNUM = '$get_id'");

// Handle print counter
if (isset($_POST["submit_print"])) {
    $connect->query("UPDATE `orderlist` SET PRINT = PRINT + 1 WHERE SALNUM = '$get_id'");
}

// Fetch order header
$raccode = $roworderid = $rowdate = $rowtrack = $rowname = $rowoutlet = '';
$rowttime = $rowstatus = $rowto = '';
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
    $rowstatus  = $row['STATUS'] ?? '';
    $rowto      = $row['TXTTO'] ?? '';

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

// Get order items and group by rack
$order_items = [];
$total_qty = 0;
$item_query = $connect->query("SELECT * FROM `orderlist` WHERE SALNUM = '$roworderid' AND PDESC <> 'USE POINTS'");
if ($item_query) {
    while ($irow = $item_query->fetch_assoc()) {
        $qty     = (int)($irow['QTY'] ?? 0);
        $barcode = $connect->real_escape_string($irow['BARCODE'] ?? '');

        // Look up rack via rack_product table
        $rack_label = 'Unassigned';
        $rack_sort  = 'ZZZZ'; // sort unassigned to end
        $rack_q = $connect->query("SELECT r.code, r.name FROM rack_product rp INNER JOIN rack r ON rp.rack_id = r.id WHERE rp.barcode = '$barcode' LIMIT 1");
        if ($rack_q && $rr = $rack_q->fetch_assoc()) {
            $rack_label = $rr['code'] . (!empty($rr['name']) ? ' - ' . $rr['name'] : '');
            $rack_sort  = $rr['code'];
        }

        $order_items[] = [
            'barcode'    => $irow['BARCODE'] ?? '',
            'pdesc'      => $irow['PDESC'] ?? '',
            'qty'        => $qty,
            'rack_label' => $rack_label,
            'rack_sort'  => $rack_sort,
        ];

        $total_qty += $qty;
    }
}

// Sort by rack then by product name
usort($order_items, function($a, $b) {
    $c = strcmp($a['rack_sort'], $b['rack_sort']);
    if ($c !== 0) return $c;
    return strcmp($a['pdesc'], $b['pdesc']);
});

// Group by rack
$grouped = [];
foreach ($order_items as $item) {
    $grouped[$item['rack_label']][] = $item;
}

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

/* Rack group */
.rack-group-wrap {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 12px;
}
.rack-group-header {
    background: var(--text);
    color: #fff;
    padding: 8px 14px;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.rack-group-header i { opacity: 0.7; }

/* Items table */
.items-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 0; }
.items-table thead td {
    border-bottom: 2px solid #e5e7eb;
    padding: 8px 10px;
    font-weight: 600;
    background: #f9fafb;
}
.items-table tbody td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; }
.items-table tbody tr:last-child td { border-bottom: none; }
.items-table .text-right { text-align: right; }

/* Summary bar */
.summary-bar {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 14px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    margin-bottom: 20px;
}
.summary-bar .total-label { color: var(--text-muted); }
.summary-bar .total-value { font-weight: 800; font-size: 18px; }

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
</style>
</head>
<body>

<div class="detail-container">
    <!-- Toolbar -->
    <div class="detail-toolbar no-print">
        <a href="dashboard.php" class="btn-toolbar btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        <form method="POST" style="margin:0;display:inline">
            <button type="submit" name="submit_print" class="btn-toolbar btn-print-a4" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
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
                <td class="label">Name</td>
                <td class="sep">:</td>
                <td><?php echo htmlspecialchars($rowname); ?></td>
            </tr>
            <tr>
                <td class="label">Date</td>
                <td class="sep">:</td>
                <td><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : ''; ?></td>
                <td class="label">Time</td>
                <td class="sep">:</td>
                <td><?php echo htmlspecialchars($rowttime); ?></td>
            </tr>
        </table>

        <!-- Items grouped by rack -->
        <?php foreach ($grouped as $rack_label => $items): ?>
        <div class="rack-group-wrap">
            <div class="rack-group-header">
                <i class="fas fa-warehouse"></i>
                <?php echo htmlspecialchars($rack_label); ?>
                <span style="opacity:0.6;font-weight:400;font-size:12px;">(<?php echo count($items); ?> item<?php echo count($items) > 1 ? 's' : ''; ?>)</span>
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <td style="width:5%">S/N</td>
                        <td style="width:20%">Barcode</td>
                        <td>Item</td>
                        <td style="width:10%" class="text-right">Qty</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                        <td><?php echo htmlspecialchars($item['pdesc']); ?></td>
                        <td class="text-right"><?php echo $item['qty']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

        <!-- Total Summary -->
        <div class="summary-bar">
            <span class="total-label">Total Quantity</span>
            <span class="total-value"><?php echo $total_qty; ?></span>
        </div>

        <!-- Footer Info -->
        <div class="order-footer">
            <p>
                <strong>Order date:</strong>
                <?php echo (!empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : '') . ' ' . htmlspecialchars($rowttime); ?>
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
                    <td class="label">Name</td>
                    <td class="sep">:</td>
                    <td><?php echo htmlspecialchars($rowname); ?></td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="sep">:</td>
                    <td><?php echo !empty($rowdate) ? date('d/m/Y', strtotime($rowdate)) : ''; ?></td>
                    <td class="label">Time</td>
                    <td class="sep">:</td>
                    <td><?php echo htmlspecialchars($rowttime); ?></td>
                </tr>
            </table>

            <?php foreach ($grouped as $rack_label => $items): ?>
            <div class="rack-group-wrap">
                <div class="rack-group-header">
                    <i class="fas fa-warehouse"></i>
                    <?php echo htmlspecialchars($rack_label); ?>
                    <span style="opacity:0.6;font-weight:400;font-size:12px;">(<?php echo count($items); ?> item<?php echo count($items) > 1 ? 's' : ''; ?>)</span>
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <td style="width:5%">S/N</td>
                            <td style="width:20%">Barcode</td>
                            <td>Item</td>
                            <td style="width:10%" class="text-right">Qty</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                            <td><?php echo htmlspecialchars($item['pdesc']); ?></td>
                            <td class="text-right"><?php echo $item['qty']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

            <div class="summary-bar">
                <span class="total-label">Total Quantity</span>
                <span class="total-value"><?php echo $total_qty; ?></span>
            </div>

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
