<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Check if coming from a specific PO
$poId = intval($_GET['po_id'] ?? 0);
$po = null;
$poItems = [];

if ($poId > 0) {
    $stmt = $connect->prepare("SELECT po.*, s.name AS supplier_name, s.id AS sid FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.id = ? AND po.status IN ('APPROVED','PARTIALLY_RECEIVED') LIMIT 1");
    $stmt->bind_param("i", $poId);
    $stmt->execute();
    $result = $stmt->get_result();
    $po = $result->fetch_assoc();
    $stmt->close();

    if ($po) {
        $itemResult = $connect->query("SELECT * FROM `purchase_order_item` WHERE `po_id` = $poId ORDER BY `id` ASC");
        if ($itemResult) {
            while ($r = $itemResult->fetch_assoc()) {
                $r['qty_pending'] = $r['qty_ordered'] - $r['qty_received'];
                $poItems[] = $r;
            }
        }
    }
}

// Fetch all GRNs for listing (when no po_id)
$grns = [];
if ($poId === 0) {
    $result = $connect->query("SELECT g.*, s.name AS supplier_name, po.po_number FROM `grn` g LEFT JOIN `supplier` s ON g.supplier_id = s.id LEFT JOIN `purchase_order` po ON g.po_id = po.id ORDER BY g.id DESC");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $grns[] = $r;
        }
    }
}

// Fetch active suppliers
$suppliers = [];
$supResult = $connect->query("SELECT `id`, `code`, `name` FROM `supplier` WHERE `status` = 'ACTIVE' ORDER BY `name` ASC");
if ($supResult) {
    while ($r = $supResult->fetch_assoc()) {
        $suppliers[] = $r;
    }
}

// Fetch approved POs for dropdown
$approvedPOs = [];
$poResult = $connect->query("SELECT po.id, po.po_number, s.name AS supplier_name FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.status IN ('APPROVED','PARTIALLY_RECEIVED') ORDER BY po.id DESC");
if ($poResult) {
    while ($r = $poResult->fetch_assoc()) {
        $approvedPOs[] = $r;
    }
}

$currentPage = 'grn';
$isReceiveMode = ($poId > 0 && $po);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isReceiveMode ? 'Receive Goods - ' . htmlspecialchars($po['po_number']) : 'Goods Receiving'; ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root {
    --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6;
    --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px;
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; margin-bottom: 20px; border: none; }
.card-title { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; margin-bottom: 16px; }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { position: relative; flex: 1; max-width: 320px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.data-table input { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 13px; width: 100%; }
.data-table input:focus { border-color: var(--primary); outline: none; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; text-decoration: none; }
.btn-view { background: #6b7280; } .btn-view:hover { background: #4b5563; color: #fff; }
.btn-primary-action { background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-primary-action:hover { background: var(--primary-dark); }
.btn-outline { background: transparent; color: var(--text); border: 1px solid #d1d5db; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-outline:hover { background: #f3f4f6; }
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
.btn-add:hover { background: var(--primary-dark); color: #fff; }
.discrepancy { color: #d97706; font-weight: 600; }
.action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
@media (max-width: 768px) {
    .page-content { padding: 16px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">

<?php if ($isReceiveMode): ?>
    <!-- RECEIVE GOODS MODE -->
    <div class="page-header">
        <h1><i class="fas fa-dolly" style="color:var(--primary);margin-right:8px;"></i>Receive Goods - <?php echo htmlspecialchars($po['po_number']); ?></h1>
        <a href="po_detail.php?id=<?php echo $poId; ?>" class="btn-outline"><i class="fas fa-arrow-left"></i> Back to PO</a>
    </div>

    <div class="card">
        <div class="card-title">PO Information</div>
        <div class="row">
            <div class="col-md-4 mb-2"><strong>Supplier:</strong> <?php echo htmlspecialchars($po['supplier_name']); ?></div>
            <div class="col-md-4 mb-2"><strong>PO Number:</strong> <?php echo htmlspecialchars($po['po_number']); ?></div>
            <div class="col-md-4 mb-2"><strong>Order Date:</strong> <?php echo date('d/m/Y', strtotime($po['order_date'])); ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Receive Items</div>
        <div class="mb-3">
            <label class="form-label fw-semibold">GRN Remark</label>
            <input type="text" id="grnRemark" class="form-control" placeholder="Optional remark for this receiving">
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Barcode</th>
                        <th>Description</th>
                        <th>Ordered</th>
                        <th>Previously Received</th>
                        <th>Pending</th>
                        <th style="width:100px">Receive Now</th>
                        <th style="width:90px">Rejected</th>
                        <th style="width:100px">Batch No</th>
                        <th style="width:100px">Rack</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($poItems as $idx => $item): ?>
                    <tr data-poi-id="<?php echo $item['id']; ?>">
                        <td><?php echo $idx + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_desc']); ?></td>
                        <td><?php echo $item['qty_ordered']; ?></td>
                        <td><?php echo $item['qty_received']; ?></td>
                        <td><?php echo $item['qty_pending']; ?></td>
                        <td><input type="number" class="grn-qty" value="<?php echo max(0, $item['qty_pending']); ?>" min="0" step="0.01" max="<?php echo $item['qty_pending']; ?>"></td>
                        <td><input type="number" class="grn-rejected" value="0" min="0" step="0.01"></td>
                        <td><input type="text" class="grn-batch" value="" placeholder="Batch"></td>
                        <td><input type="text" class="grn-rack" value="" placeholder="Rack"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="action-bar">
            <button type="button" class="btn-primary-action" onclick="submitGRN();"><i class="fas fa-check"></i> Submit Receiving</button>
        </div>
    </div>

<?php else: ?>
    <!-- GRN LISTING MODE -->
    <div class="page-header">
        <h1><i class="fas fa-dolly" style="color:var(--primary);margin-right:8px;"></i>Goods Receiving</h1>
        <div>
            <select id="poSelect" class="form-select d-inline-block" style="width:auto;margin-right:8px;" onchange="if(this.value) window.location='grn.php?po_id='+this.value;">
                <option value="">-- Quick Receive from PO --</option>
                <?php foreach ($approvedPOs as $apo): ?>
                <option value="<?php echo $apo['id']; ?>"><?php echo htmlspecialchars($apo['po_number'] . ' - ' . $apo['supplier_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search GRNs...">
            </div>
            <div class="item-count" id="itemCount"><?php echo count($grns); ?> GRN(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>GRN Number</th>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Receive Date</th>
                        <th>Received By</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($grns) === 0): ?>
                    <tr class="no-results"><td colspan="7"><i class="fas fa-dolly" style="font-size:24px;margin-bottom:8px;display:block;"></i>No receiving records found</td></tr>
                    <?php else: ?>
                    <?php foreach ($grns as $i => $g): ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($g['grn_number'] ?? '') . ' ' . ($g['po_number'] ?? '') . ' ' . ($g['supplier_name'] ?? '') . ' ' . ($g['received_by'] ?? '')
                    )); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($g['grn_number'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($g['po_number'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($g['supplier_name'] ?? ''); ?></td>
                        <td><?php echo !empty($g['receive_date']) ? date('d/m/Y', strtotime($g['receive_date'])) : ''; ?></td>
                        <td><?php echo htmlspecialchars($g['received_by'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($g['remark'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if ($isReceiveMode): ?>
function submitGRN() {
    var items = [];
    document.querySelectorAll('tr[data-poi-id]').forEach(function(tr) {
        var qty = parseFloat(tr.querySelector('.grn-qty').value) || 0;
        var rejected = parseFloat(tr.querySelector('.grn-rejected').value) || 0;
        if (qty > 0 || rejected > 0) {
            items.push({
                po_item_id: tr.getAttribute('data-poi-id'),
                barcode: tr.cells[1].textContent.trim(),
                product_desc: tr.cells[2].textContent.trim(),
                qty_received: qty,
                qty_rejected: rejected,
                batch_no: tr.querySelector('.grn-batch').value.trim(),
                rack_location: tr.querySelector('.grn-rack').value.trim()
            });
        }
    });

    if (items.length === 0) {
        Swal.fire({ icon: 'warning', text: 'Enter quantities to receive.' });
        return;
    }

    Swal.fire({
        title: 'Confirm Receiving?',
        text: 'This will update stock quantities (QOH).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'Yes, Receive'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'grn_ajax.php',
                data: {
                    action: 'receive',
                    po_id: <?php echo $poId; ?>,
                    supplier_id: <?php echo $po['supplier_id']; ?>,
                    remark: document.getElementById('grnRemark').value,
                    items: JSON.stringify(items)
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, showConfirmButton: true }).then(function() {
                            window.location.href = 'po_detail.php?id=<?php echo $poId; ?>';
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                }
            });
        }
    });
}
<?php else: ?>
// Search for listing mode
document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        if (d.indexOf(q) > -1) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('itemCount').textContent = count + ' GRN(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
});
<?php endif; ?>
</script>
</body>
</html>
