<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

$poId = intval($_GET['id'] ?? 0);
$po = null;
$items = [];
$isNew = ($poId === 0);

if (!$isNew) {
    $stmt = $connect->prepare("SELECT po.*, s.name AS supplier_name FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id WHERE po.id = ? LIMIT 1");
    $stmt->bind_param("i", $poId);
    $stmt->execute();
    $result = $stmt->get_result();
    $po = $result->fetch_assoc();
    $stmt->close();

    if (!$po) {
        header("Location: po.php");
        exit;
    }

    $itemResult = $connect->query("SELECT * FROM `purchase_order_item` WHERE `po_id` = $poId ORDER BY `id` ASC");
    if ($itemResult) {
        while ($r = $itemResult->fetch_assoc()) {
            $items[] = $r;
        }
    }
}

// Fetch active suppliers for dropdown
$suppliers = [];
$supResult = $connect->query("SELECT `id`, `code`, `name` FROM `supplier` WHERE `status` = 'ACTIVE' ORDER BY `name` ASC");
if ($supResult) {
    while ($r = $supResult->fetch_assoc()) {
        $suppliers[] = $r;
    }
}

$isDraft = $isNew || ($po['status'] ?? '') === 'DRAFT';
$canApprove = !$isNew && ($po['status'] ?? '') === 'DRAFT';
$canCancel = !$isNew && in_array($po['status'] ?? '', ['DRAFT', 'APPROVED']);
$canReceive = !$isNew && in_array($po['status'] ?? '', ['APPROVED', 'PARTIALLY_RECEIVED']);

$currentPage = 'po';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isNew ? 'New Purchase Order' : 'PO: ' . htmlspecialchars($po['po_number']); ?></title>
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
.page-content { max-width: 1200px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; margin-bottom: 20px; border: none; }
.card-title { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; margin-bottom: 16px; }
.badge-po { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
.badge-DRAFT { background: #f3f4f6; color: #6b7280; }
.badge-APPROVED { background: #dbeafe; color: #2563eb; }
.badge-PARTIALLY_RECEIVED { background: #fef3c7; color: #d97706; }
.badge-RECEIVED { background: #dcfce7; color: #16a34a; }
.badge-CLOSED { background: #e5e7eb; color: #374151; }
.badge-CANCELLED { background: #fee2e2; color: #dc2626; }
.items-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.items-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 10px 12px; text-align: left; }
.items-table tbody td { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.items-table tbody tr:hover { background: #f9fafb; }
.items-table input, .items-table select { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 13px; width: 100%; }
.items-table input:focus, .items-table select:focus { border-color: var(--primary); outline: none; }
.btn-sm-action { padding: 4px 10px; border: none; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer; color: #fff; }
.btn-remove { background: #ef4444; } .btn-remove:hover { background: #dc2626; }
.btn-primary-action { background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background var(--transition); }
.btn-primary-action:hover { background: var(--primary-dark); }
.btn-success-action { background: #22c55e; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-success-action:hover { background: #16a34a; }
.btn-warning-action { background: #f59e0b; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-warning-action:hover { background: #d97706; }
.btn-danger-action { background: #ef4444; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-danger-action:hover { background: #dc2626; }
.btn-outline { background: transparent; color: var(--text); border: 1px solid #d1d5db; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-outline:hover { background: #f3f4f6; }
.action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
.discrepancy { color: #d97706; font-weight: 600; }
@media (max-width: 768px) {
    .page-content { padding: 16px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1>
            <i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>
            <?php if ($isNew): ?>New Purchase Order<?php else: ?>PO: <?php echo htmlspecialchars($po['po_number']); ?> <span class="badge-po badge-<?php echo htmlspecialchars($po['status']); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($po['status'])); ?></span><?php endif; ?>
        </h1>
        <a href="po.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    <!-- PO Header -->
    <div class="card">
        <div class="card-title">Order Details</div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                <select id="supplierId" class="form-select" <?php echo !$isDraft ? 'disabled' : ''; ?>>
                    <option value="">-- Select Supplier --</option>
                    <?php foreach ($suppliers as $sup): ?>
                    <option value="<?php echo $sup['id']; ?>" <?php echo (!$isNew && $po['supplier_id'] == $sup['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sup['code'] . ' - ' . $sup['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Order Date <span class="text-danger">*</span></label>
                <input type="date" id="orderDate" class="form-control" value="<?php echo $isNew ? date('Y-m-d') : htmlspecialchars($po['order_date'] ?? ''); ?>" <?php echo !$isDraft ? 'disabled' : ''; ?>>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Expected Date</label>
                <input type="date" id="expectedDate" class="form-control" value="<?php echo htmlspecialchars($po['expected_date'] ?? ''); ?>" <?php echo !$isDraft ? 'disabled' : ''; ?>>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Remark</label>
            <textarea id="remark" class="form-control" rows="2" <?php echo !$isDraft ? 'disabled' : ''; ?>><?php echo htmlspecialchars($po['remark'] ?? ''); ?></textarea>
        </div>
        <?php if (!$isNew && $po['approved_by']): ?>
        <div class="text-muted" style="font-size:12px;">
            Approved by <strong><?php echo htmlspecialchars($po['approved_by']); ?></strong> on <?php echo htmlspecialchars($po['approved_date'] ?? ''); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Line Items -->
    <div class="card">
        <div class="card-title">Line Items</div>
        <div style="overflow-x:auto;">
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="width:160px">Barcode</th>
                        <th>Description</th>
                        <th style="width:90px">Qty Ordered</th>
                        <th style="width:90px">Qty Received</th>
                        <th style="width:80px">UOM</th>
                        <?php if ($isDraft): ?><th style="width:50px"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if (!$isNew): ?>
                    <?php foreach ($items as $idx => $item): ?>
                    <tr data-item-id="<?php echo $item['id']; ?>">
                        <td><?php echo $idx + 1; ?></td>
                        <td><?php if ($isDraft): ?><input type="text" class="item-barcode" value="<?php echo htmlspecialchars($item['barcode']); ?>" onchange="lookupProduct(this);"><?php else: echo htmlspecialchars($item['barcode']); endif; ?></td>
                        <td><?php if ($isDraft): ?><input type="text" class="item-desc" value="<?php echo htmlspecialchars($item['product_desc']); ?>"><?php else: echo htmlspecialchars($item['product_desc']); endif; ?></td>
                        <td><?php if ($isDraft): ?><input type="number" class="item-qty" value="<?php echo $item['qty_ordered']; ?>" min="0" step="0.01"><?php else: echo $item['qty_ordered']; endif; ?></td>
                        <td><?php echo $item['qty_received']; ?><?php if ($item['qty_received'] < $item['qty_ordered'] && !$isDraft): ?> <span class="discrepancy" title="Pending"><i class="fas fa-exclamation-circle"></i></span><?php endif; ?></td>
                        <td><?php if ($isDraft): ?><input type="text" class="item-uom" value="<?php echo htmlspecialchars($item['uom']); ?>"><?php else: echo htmlspecialchars($item['uom']); endif; ?></td>
                        <?php if ($isDraft): ?><td><button type="button" class="btn-sm-action btn-remove" onclick="removeRow(this);"><i class="fas fa-times"></i></button></td><?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($isDraft): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-3" onclick="addRow();"><i class="fas fa-plus"></i> Add Line</button>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="action-bar">
        <?php if ($isDraft): ?>
        <button type="button" class="btn-primary-action" onclick="savePO();"><i class="fas fa-save"></i> Save Draft</button>
        <?php endif; ?>
        <?php if ($canApprove): ?>
        <button type="button" class="btn-success-action" onclick="approvePO();"><i class="fas fa-check-circle"></i> Approve</button>
        <?php endif; ?>
        <?php if ($canReceive): ?>
        <a href="grn.php?po_id=<?php echo $poId; ?>" class="btn-warning-action" style="text-decoration:none;"><i class="fas fa-dolly"></i> Receive Goods (GRN)</a>
        <?php endif; ?>
        <?php if ($canCancel): ?>
        <button type="button" class="btn-danger-action" onclick="cancelPO();"><i class="fas fa-ban"></i> Cancel PO</button>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var poId = <?php echo $poId; ?>;
var isDraft = <?php echo $isDraft ? 'true' : 'false'; ?>;

function addRow() {
    var tbody = document.getElementById('itemsBody');
    var rowCount = tbody.rows.length + 1;
    var tr = document.createElement('tr');
    tr.innerHTML = '<td>' + rowCount + '</td>' +
        '<td><input type="text" class="item-barcode" placeholder="Scan/enter barcode" onchange="lookupProduct(this);"></td>' +
        '<td><input type="text" class="item-desc" placeholder="Product description"></td>' +
        '<td><input type="number" class="item-qty" value="1" min="0" step="0.01"></td>' +
        '<td>0</td>' +
        '<td><input type="text" class="item-uom" value=""></td>' +
        '<td><button type="button" class="btn-sm-action btn-remove" onclick="removeRow(this);"><i class="fas fa-times"></i></button></td>';
    tbody.appendChild(tr);
}

function removeRow(btn) {
    btn.closest('tr').remove();
    renumber();
}

function renumber() {
    var rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach(function(row, i) { row.cells[0].textContent = i + 1; });
}

function lookupProduct(input) {
    var barcode = input.value.trim();
    if (barcode === '') return;
    var tr = input.closest('tr');

    $.ajax({
        type: 'POST', url: 'po_ajax.php', data: { action: 'lookup_product', barcode: barcode }, dataType: 'json',
        success: function(data) {
            if (data.name) {
                tr.querySelector('.item-desc').value = data.name;
                if (data.uom) tr.querySelector('.item-uom').value = data.uom;
            }
        }
    });
}

function collectItems() {
    var items = [];
    document.querySelectorAll('#itemsBody tr').forEach(function(tr) {
        var barcode = (tr.querySelector('.item-barcode')?.value || tr.cells[1].textContent).trim();
        var desc = (tr.querySelector('.item-desc')?.value || tr.cells[2].textContent).trim();
        var qty = parseFloat(tr.querySelector('.item-qty')?.value || tr.cells[3].textContent) || 0;
        var uom = (tr.querySelector('.item-uom')?.value || tr.cells[5].textContent).trim();
        var itemId = tr.getAttribute('data-item-id') || '';
        if (barcode !== '' && qty > 0) {
            items.push({ id: itemId, barcode: barcode, product_desc: desc, qty_ordered: qty, uom: uom });
        }
    });
    return items;
}

function savePO() {
    var supplierId = document.getElementById('supplierId').value;
    var orderDate = document.getElementById('orderDate').value;
    if (!supplierId || !orderDate) {
        Swal.fire({ icon: 'warning', text: 'Supplier and order date are required.' });
        return;
    }

    var items = collectItems();
    if (items.length === 0) {
        Swal.fire({ icon: 'warning', text: 'Add at least one line item.' });
        return;
    }

    $.ajax({
        type: 'POST', url: 'po_ajax.php',
        data: {
            action: poId > 0 ? 'update' : 'create',
            id: poId,
            supplier_id: supplierId,
            order_date: orderDate,
            expected_date: document.getElementById('expectedDate').value,
            remark: document.getElementById('remark').value,
            items: JSON.stringify(items)
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                    if (data.po_id) window.location.href = 'po_detail.php?id=' + data.po_id;
                    else location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        }
    });
}

function approvePO() {
    Swal.fire({
        title: 'Approve this PO?',
        text: 'Once approved, the PO can be received against.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'Approve'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'po_ajax.php', data: { action: 'approve', id: poId }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error });
                    }
                }
            });
        }
    });
}

function cancelPO() {
    Swal.fire({
        title: 'Cancel this PO?',
        text: 'This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Cancel PO'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'po_ajax.php', data: { action: 'cancel', id: poId }, dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error });
                    }
                }
            });
        }
    });
}

// Auto-add first row for new POs
if (isDraft && document.querySelectorAll('#itemsBody tr').length === 0) {
    addRow();
}
</script>
</body>
</html>
