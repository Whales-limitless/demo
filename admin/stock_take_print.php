<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$sessionId = intval($_GET['id'] ?? 0);

if ($sessionId <= 0) {
    echo '<!DOCTYPE html><html><body><h1>Session not found.</h1><a href="stock_take.php">Back</a></body></html>';
    exit;
}

// Fetch session header
$stmt = $connect->prepare("SELECT * FROM `stock_take` WHERE `id` = ? LIMIT 1");
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    echo '<!DOCTYPE html><html><body><h1>Session not found.</h1><a href="stock_take.php">Back</a></body></html>';
    exit;
}

// Fetch session items
$items = [];
$itemResult = $connect->query("SELECT * FROM `stock_take_item` WHERE `stock_take_id` = " . intval($sessionId) . " ORDER BY `id` ASC");
if ($itemResult) {
    while ($r = $itemResult->fetch_assoc()) {
        $items[] = $r;
    }
}

// Fetch company/outlet info
$outletName = '';
$outletAddress = '';
$outletResult = $connect->query("SELECT `PDESC`, `ADDRESS` FROM `outlet` ORDER BY `ID` ASC LIMIT 1");
if ($outletResult && $oRow = $outletResult->fetch_assoc()) {
    $outletName = $oRow['PDESC'] ?? '';
    $outletAddress = $oRow['ADDRESS'] ?? '';
}

// Format dates
$createdAt = !empty($session['created_at']) ? date('d/m/Y h:i A', strtotime($session['created_at'])) : '-';

// Auto print flag
$autoPrint = isset($_GET['auto_print']) && $_GET['auto_print'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Take - <?php echo htmlspecialchars($session['session_code']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DM Sans', Arial, sans-serif;
    background: #e5e7eb;
    color: #1a1a1a;
    -webkit-font-smoothing: antialiased;
}

/* Toolbar - hidden on print */
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
    padding: 8px 18px;
    border: none;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
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
.btn-print { background: #3b82f6; color: #fff; }
.btn-print:hover { background: #2563eb; }

/* A4 page container */
.a4-page {
    width: 210mm;
    min-height: 297mm;
    margin: 16px auto;
    padding: 15mm 18mm;
    background: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.1);
}

/* Header */
.doc-header {
    text-align: center;
    margin-bottom: 14px;
}
.doc-header .company-name {
    font-size: 20px;
    font-weight: 700;
}
.doc-header .company-address {
    font-size: 13px;
    color: #444;
    margin-top: 2px;
}
.doc-header .doc-title {
    font-size: 18px;
    font-weight: 600;
    margin-top: 10px;
}

/* Info grid */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 24px;
    font-size: 13px;
    margin-bottom: 14px;
}
.info-item {
    display: flex;
    gap: 4px;
}
.info-label {
    font-weight: 700;
    white-space: nowrap;
    min-width: 120px;
}

/* Items table */
.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    margin-top: 10px;
}
.items-table thead th {
    background: #1a1a1a;
    color: #fff;
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    padding: 6px 8px;
    text-align: left;
    border: 1px solid #1a1a1a;
}
.items-table tbody td {
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    vertical-align: middle;
}
.items-table tbody tr:nth-child(even) {
    background: #f9fafb;
}
.items-table .text-center { text-align: center; }
.items-table .text-right { text-align: right; }
.items-table .col-counted {
    background: #fefce8;
}
.items-table .col-remark {
    min-width: 80px;
}

/* Footer */
.doc-footer {
    margin-top: 30px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
}
.signature-block {
    text-align: center;
    width: 180px;
}
.signature-line {
    border-top: 1px solid #1a1a1a;
    margin-top: 50px;
    padding-top: 4px;
}

.print-note {
    font-size: 11px;
    color: #6b7280;
    text-align: center;
    margin-top: 12px;
    font-style: italic;
}

/* Print styles */
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
</style>

<?php if ($autoPrint): ?>
<script>
window.onload = function() { window.print(); }
</script>
<?php endif; ?>
</head>
<body>

<!-- Toolbar -->
<div class="toolbar">
    <a href="stock_take.php?view=<?php echo $sessionId; ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
</div>

<!-- A4 Page -->
<div class="a4-page">
    <!-- Header -->
    <div class="doc-header">
        <div class="company-name"><?php echo htmlspecialchars($outletName); ?></div>
        <div class="company-address"><?php echo htmlspecialchars($outletAddress); ?></div>
        <div class="doc-title">Stock Take Sheet</div>
    </div>

    <!-- Info Section -->
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Session Code</span>
            <span>: <?php echo htmlspecialchars($session['session_code']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date</span>
            <span>: <?php echo htmlspecialchars($createdAt); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Description</span>
            <span>: <?php echo htmlspecialchars($session['description'] ?: '-'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Created By</span>
            <span>: <?php echo htmlspecialchars($session['created_by'] ?: '-'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Type</span>
            <span>: <?php echo htmlspecialchars($session['type']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Total Items</span>
            <span>: <?php echo count($items); ?></span>
        </div>
        <?php if ($session['type'] === 'PARTIAL'): ?>
        <div class="info-item">
            <span class="info-label">Category</span>
            <span>: <?php echo htmlspecialchars($session['filter_cat'] ?: '-'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Sub Category</span>
            <span>: <?php echo htmlspecialchars($session['filter_sub_cat'] ?: '-'); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%;">S/N</th>
                <th style="width:14%;">Barcode</th>
                <th style="width:36%;">Description</th>
                <th style="width:10%;" class="text-right">System Qty</th>
                <th style="width:12%;" class="text-center">Counted Qty</th>
                <th style="width:10%;" class="text-center">Variance</th>
                <th style="width:14%;">Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($items) === 0): ?>
            <tr><td colspan="7" class="text-center" style="padding:20px;color:#6b7280;">No items</td></tr>
            <?php else: ?>
            <?php foreach ($items as $idx => $item): ?>
            <tr>
                <td class="text-center"><?php echo $idx + 1; ?></td>
                <td><?php echo htmlspecialchars($item['barcode'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($item['product_desc'] ?? ''); ?></td>
                <td class="text-right"><?php echo rtrim(rtrim(number_format(floatval($item['system_qty']), 2), '0'), '.'); ?></td>
                <td class="text-center col-counted"><?php
                    if ($item['status'] === 'COUNTED') {
                        echo rtrim(rtrim(number_format(floatval($item['counted_qty']), 2), '0'), '.');
                    }
                ?></td>
                <td class="text-center"><?php
                    if ($item['status'] === 'COUNTED') {
                        $v = floatval($item['variance'] ?? 0);
                        if ($v > 0) echo '<span style="color:#16a34a;font-weight:600;">+' . $v . '</span>';
                        elseif ($v < 0) echo '<span style="color:#dc2626;font-weight:600;">' . $v . '</span>';
                        else echo '<span style="color:#6b7280;">0</span>';
                    }
                ?></td>
                <td class="col-remark"><?php echo htmlspecialchars($item['remark'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="print-note">Staff: Please write the counted quantity in the "Counted Qty" column for each item.</div>

    <!-- Footer with signature blocks -->
    <div class="doc-footer">
        <div class="signature-block">
            <div class="signature-line">Counted By</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Verified By</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Approved By</div>
        </div>
    </div>
</div>

</body>
</html>
