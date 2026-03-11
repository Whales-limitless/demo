<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

$grnId = intval($_GET['id'] ?? 0);

if ($grnId <= 0) {
    echo '<!DOCTYPE html><html><body><h1>GRN not found.</h1><a href="grn.php">Back</a></body></html>';
    exit;
}

// Fetch GRN header
$stmt = $connect->prepare("SELECT g.*, s.name AS supplier_name, s.code AS supplier_code, po.po_number FROM `grn` g LEFT JOIN `supplier` s ON g.supplier_id = s.id LEFT JOIN `purchase_order` po ON g.po_id = po.id WHERE g.id = ? LIMIT 1");
$stmt->bind_param("i", $grnId);
$stmt->execute();
$grn = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$grn) {
    echo '<!DOCTYPE html><html><body><h1>GRN not found.</h1><a href="grn.php">Back</a></body></html>';
    exit;
}

// Fetch GRN items
$grnItems = [];
$stmtItems = $connect->prepare("SELECT * FROM `grn_item` WHERE `grn_id` = ? ORDER BY `id` ASC");
$stmtItems->bind_param("i", $grnId);
$stmtItems->execute();
$itemResult = $stmtItems->get_result();
while ($row = $itemResult->fetch_assoc()) {
    $grnItems[] = $row;
}
$stmtItems->close();

// Fetch company/outlet info (first outlet)
$outletName = '';
$outletAddress = '';
$outletResult = $connect->query("SELECT `PDESC`, `ADDRESS` FROM `outlet` ORDER BY `ID` ASC LIMIT 1");
if ($outletResult && $oRow = $outletResult->fetch_assoc()) {
    $outletName = $oRow['PDESC'] ?? '';
    $outletAddress = $oRow['ADDRESS'] ?? '';
}

// Format dates
$receiveDate = !empty($grn['receive_date']) ? date('d/m/Y', strtotime($grn['receive_date'])) : '';
$createdAt = !empty($grn['created_at']) ? date('d/m/Y h:i A', strtotime($grn['created_at'])) : '';

// Auto print flag
$autoPrint = isset($_GET['auto_print']) && $_GET['auto_print'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Received Note - <?php echo htmlspecialchars($grn['grn_number']); ?></title>
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
    padding: 20mm 18mm;
    background: #fff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.1);
}

/* Header */
.doc-header {
    text-align: center;
    margin-bottom: 16px;
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

/* Info rows */
.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
    font-size: 13px;
}
.info-row .info-left,
.info-row .info-right {
    display: flex;
    gap: 4px;
}
.info-row .info-left { flex: 0 0 65%; }
.info-row .info-right { flex: 0 0 35%; }
.info-label {
    font-weight: 700;
    white-space: nowrap;
    min-width: 120px;
}
.info-value {
    flex: 1;
}
.info-row .info-right .info-label {
    min-width: 80px;
}

/* Items table */
.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    margin-top: 16px;
}
.items-table thead th {
    background: #1a1a1a;
    color: #fff;
    font-weight: 600;
    font-size: 12px;
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
.items-table .text-center { text-align: center; }
.items-table .text-right { text-align: right; }

/* Footer */
.doc-footer {
    margin-top: 40px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
}
.signature-block {
    text-align: center;
    width: 200px;
}
.signature-line {
    border-top: 1px solid #1a1a1a;
    margin-top: 50px;
    padding-top: 4px;
}

/* Print styles */
@media print {
    .toolbar { display: none !important; }
    body { background: #fff; }
    .a4-page {
        margin: 0;
        padding: 15mm 18mm;
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
<div class="toolbar no-print">
    <a href="grn.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
</div>

<!-- A4 Page -->
<div class="a4-page">
    <!-- Header -->
    <div class="doc-header">
        <div class="company-name"><?php echo htmlspecialchars($outletName); ?></div>
        <div class="company-address"><?php echo htmlspecialchars($outletAddress); ?></div>
        <div class="doc-title">Stock Received Note</div>
    </div>

    <!-- Info Section -->
    <div class="info-row">
        <div class="info-left">
            <span class="info-label">Reference No</span>
            <span class="info-value">: <?php echo htmlspecialchars($grn['grn_number']); ?></span>
        </div>
        <div class="info-right">
            <span class="info-label">Date</span>
            <span class="info-value">: <?php echo htmlspecialchars($createdAt); ?></span>
        </div>
    </div>

    <div class="info-row">
        <div class="info-left">
            <span class="info-label">Received by</span>
            <span class="info-value">: <?php echo htmlspecialchars($grn['received_by'] ?? ''); ?></span>
        </div>
        <div class="info-right">
            <span class="info-label">Remark</span>
            <span class="info-value">: <?php echo htmlspecialchars($grn['remark'] ?? ''); ?></span>
        </div>
    </div>

    <?php if (!empty($grn['supplier_name'])): ?>
    <div class="info-row">
        <div class="info-left">
            <span class="info-label">Supplier</span>
            <span class="info-value">: <?php echo htmlspecialchars($grn['supplier_name']); ?></span>
        </div>
        <?php if (!empty($grn['po_number'])): ?>
        <div class="info-right">
            <span class="info-label">PO Number</span>
            <span class="info-value">: <?php echo htmlspecialchars($grn['po_number']); ?></span>
        </div>
        <?php else: ?>
        <div class="info-right"></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;">S/N</th>
                <th style="width:20%;">Barcode</th>
                <th style="width:35%;">Description</th>
                <th style="width:10%;" class="text-right">Qty</th>
                <th style="width:10%;" class="text-right">Rejected</th>
                <th style="width:10%;">Batch No</th>
                <th style="width:10%;">Rack</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($grnItems) === 0): ?>
            <tr><td colspan="7" class="text-center" style="padding:20px;color:#6b7280;">No items found</td></tr>
            <?php else: ?>
            <?php foreach ($grnItems as $idx => $item): ?>
            <tr>
                <td class="text-center"><?php echo $idx + 1; ?></td>
                <td><?php echo htmlspecialchars($item['barcode'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($item['product_desc'] ?? ''); ?></td>
                <td class="text-right"><?php echo rtrim(rtrim(number_format($item['qty_received'], 2), '0'), '.'); ?></td>
                <td class="text-right"><?php echo ($item['qty_rejected'] > 0) ? rtrim(rtrim(number_format($item['qty_rejected'], 2), '0'), '.') : '-'; ?></td>
                <td><?php echo htmlspecialchars($item['batch_no'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($item['rack_location'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer with signature blocks -->
    <div class="doc-footer">
        <div class="signature-block">
            <div class="signature-line">Received By</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Checked By</div>
        </div>
    </div>
</div>

</body>
</html>
