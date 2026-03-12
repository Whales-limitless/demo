<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$salnum = $_GET['salnum'] ?? '';
if (empty($salnum)) {
    echo '<!DOCTYPE html><html><body><h1>Order not found.</h1><a href="index.php">Back to Home</a></body></html>';
    exit;
}

// Fetch order items
$items = [];
$orderInfo = null;
$stmt = $connect->prepare("SELECT SALNUM, NAME, SDATE, TTIME, BARCODE, PDESC, QTY, PTYPE, TXTTO FROM `orderlist` WHERE `SALNUM` = ? AND `BARCODE` <> 'PT' ORDER BY `ID` ASC");
$stmt->bind_param("s", $salnum);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (!$orderInfo) {
        $orderInfo = [
            'SALNUM' => $row['SALNUM'],
            'NAME' => $row['NAME'],
            'SDATE' => $row['SDATE'],
            'TTIME' => $row['TTIME'],
            'PTYPE' => $row['PTYPE'],
            'TXTTO' => $row['TXTTO']
        ];
    }
    $items[] = $row;
}
$stmt->close();

if (!$orderInfo) {
    echo '<!DOCTYPE html><html><body><h1>Order not found.</h1><a href="index.php">Back to Home</a></body></html>';
    exit;
}

// Fetch company/outlet info
$outletName = '';
$outletAddress = '';
$outletResult = $connect->query("SELECT `PDESC`, `ADDRESS` FROM `outlet` ORDER BY `ID` ASC LIMIT 1");
if ($outletResult && $oRow = $outletResult->fetch_assoc()) {
    $outletName = $oRow['PDESC'] ?? '';
    $outletAddress = $oRow['ADDRESS'] ?? '';
}

$totalQty = 0;
foreach ($items as $item) {
    $totalQty += intval($item['QTY']);
}

$orderDate = !empty($orderInfo['SDATE']) ? date('d/m/Y', strtotime($orderInfo['SDATE'])) : '-';
$orderTime = !empty($orderInfo['TTIME']) ? date('h:i A', strtotime($orderInfo['TTIME'])) : '-';
$orderTypeLabel = ($orderInfo['PTYPE'] === 'STOCKIN') ? 'Stock In' : 'Purchase';

$autoPrint = isset($_GET['auto_print']) && $_GET['auto_print'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($orderTypeLabel); ?> - <?php echo htmlspecialchars($salnum); ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DM Sans', Arial, sans-serif;
    background: #e5e7eb;
    color: #1a1a1a;
    -webkit-font-smoothing: antialiased;
}

.toolbar {
    max-width: 210mm;
    margin: 16px auto 0;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}
.toolbar a, .toolbar button {
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-back { background: #e5e7eb; color: #1a1a1a; }
.btn-back:hover { background: #d1d5db; }
.btn-home { background: #C8102E; color: #fff; }
.btn-home:hover { background: #a00d24; }
.btn-print { background: #2563eb; color: #fff; }
.btn-print:hover { background: #1d4ed8; }

.a4-page {
    width: 210mm;
    min-height: 297mm;
    margin: 16px auto;
    padding: 15mm 18mm;
    background: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.1);
}

.doc-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #1a1a1a;
}
.doc-header .company-name {
    font-family: 'Outfit', sans-serif;
    font-size: 22px;
    font-weight: 700;
}
.doc-header .company-address {
    font-size: 13px;
    color: #444;
    margin-top: 2px;
}
.doc-header .doc-title {
    font-family: 'Outfit', sans-serif;
    font-size: 20px;
    font-weight: 700;
    margin-top: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 24px;
    font-size: 13px;
    margin-bottom: 20px;
}
.info-item {
    display: flex;
    gap: 4px;
}
.info-label {
    font-weight: 700;
    white-space: nowrap;
    min-width: 100px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin-top: 10px;
}
.items-table thead th {
    background: #1a1a1a;
    color: #fff;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    padding: 8px 10px;
    text-align: left;
    border: 1px solid #1a1a1a;
}
.items-table tbody td {
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    vertical-align: middle;
}
.items-table tbody tr:nth-child(even) {
    background: #f9fafb;
}
.text-center { text-align: center; }
.text-right { text-align: right; }

.items-table tfoot td {
    padding: 8px 10px;
    font-weight: 700;
    border: 1px solid #d1d5db;
    background: #f3f4f6;
}

.doc-footer {
    margin-top: 40px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
}
.signature-block {
    text-align: center;
    width: 160px;
}
.signature-line {
    border-top: 1px solid #1a1a1a;
    margin-top: 50px;
    padding-top: 4px;
}

@media print {
    .toolbar { display: none !important; }
    body { background: #fff; }
    .a4-page {
        margin: 0;
        padding: 10mm 15mm;
        box-shadow: none;
        width: 100%;
        min-height: auto;
    }
    @page {
        size: A4 portrait;
        margin: 5mm;
    }
}

@media (max-width: 800px) {
    .a4-page {
        width: 100%;
        min-height: auto;
        margin: 12px;
        padding: 20px 16px;
        border-radius: 12px;
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
    .toolbar {
        padding: 0 12px;
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php if ($autoPrint): ?>
<script>
window.onload = function() { window.print(); }
</script>
<?php endif; ?>
</head>
<body>

<div class="toolbar">
    <a href="index.php" class="btn-home">
        <svg style="width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Back to Home
    </a>
    <button class="btn-print" onclick="window.print()">
        <svg style="width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print
    </button>
</div>

<div class="a4-page">
    <div class="doc-header">
        <?php if (!empty($outletName)): ?>
        <div class="company-name"><?php echo htmlspecialchars($outletName); ?></div>
        <?php endif; ?>
        <?php if (!empty($outletAddress)): ?>
        <div class="company-address"><?php echo htmlspecialchars($outletAddress); ?></div>
        <?php endif; ?>
        <div class="doc-title"><?php echo htmlspecialchars($orderTypeLabel); ?> Receipt</div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Order No</span>
            <span>: <?php echo htmlspecialchars($salnum); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date</span>
            <span>: <?php echo htmlspecialchars($orderDate); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Type</span>
            <span>: <?php echo htmlspecialchars($orderTypeLabel); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Time</span>
            <span>: <?php echo htmlspecialchars($orderTime); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Staff</span>
            <span>: <?php echo htmlspecialchars($orderInfo['NAME'] ?? '-'); ?></span>
        </div>
        <?php if (!empty($orderInfo['TXTTO'])): ?>
        <div class="info-item">
            <span class="info-label">To</span>
            <span>: <?php echo htmlspecialchars($orderInfo['TXTTO']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-item">
            <span class="info-label">Total Items</span>
            <span>: <?php echo count($items); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Total Qty</span>
            <span>: <?php echo $totalQty; ?></span>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:6%;" class="text-center">No</th>
                <th style="width:18%;">Barcode</th>
                <th style="width:52%;">Description</th>
                <th style="width:12%;" class="text-center">Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $item): ?>
            <tr>
                <td class="text-center"><?php echo $idx + 1; ?></td>
                <td><?php echo htmlspecialchars($item['BARCODE'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($item['PDESC'] ?? ''); ?></td>
                <td class="text-center"><?php echo intval($item['QTY']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total Quantity</td>
                <td class="text-center"><?php echo $totalQty; ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="doc-footer">
        <div class="signature-block">
            <div class="signature-line">Prepared By</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Received By</div>
        </div>
    </div>
</div>

</body>
</html>
