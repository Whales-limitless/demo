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
    header("Location: stock_take.php");
    exit;
}

$session = null;
$stmt = $connect->prepare("SELECT * FROM `stock_take` WHERE `id` = ? LIMIT 1");
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();
$session = $result->fetch_assoc();
$stmt->close();

if (!$session) {
    header("Location: stock_take.php");
    exit;
}

// Fetch stock take items
$items = [];
$itemResult = $connect->query("SELECT * FROM `stock_take_item` WHERE `stock_take_id` = $sessionId ORDER BY `id` ASC");
if ($itemResult) {
    while ($r = $itemResult->fetch_assoc()) {
        $items[] = $r;
    }
}

$isDraft = ($session['status'] === 'DRAFT');
$isSubmitted = ($session['status'] === 'SUBMITTED');
$isApproved = ($session['status'] === 'APPROVED');
// Legacy support
$isOpen = ($session['status'] === 'OPEN');
$isInProgress = ($session['status'] === 'IN_PROGRESS');
$isCompleted = ($session['status'] === 'COMPLETED');
$canEdit = ($isDraft || $isSubmitted || $isOpen || $isInProgress);

$currentPage = 'stock_take';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Take - <?php echo htmlspecialchars($session['session_code']); ?></title>
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
.badge-st { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
.badge-OPEN { background: #dbeafe; color: #2563eb; }
.badge-IN_PROGRESS { background: #fef3c7; color: #d97706; }
.badge-COMPLETED { background: #dcfce7; color: #16a34a; }
.badge-DRAFT { background: #dbeafe; color: #2563eb; }
.badge-SUBMITTED { background: #fef3c7; color: #d97706; }
.badge-APPROVED { background: #dcfce7; color: #16a34a; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 8px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table input { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 13px; width: 100%; }
.data-table input:focus { border-color: var(--primary); outline: none; }
.variance-pos { color: #16a34a; font-weight: 600; }
.variance-neg { color: #dc2626; font-weight: 600; }
.variance-zero { color: var(--text-muted); }
.btn-outline { background: transparent; color: var(--text); border: 1px solid #d1d5db; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-outline:hover { background: #f3f4f6; }
.btn-primary-action { background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-primary-action:hover { background: var(--primary-dark); }
.btn-success-action { background: #22c55e; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-success-action:hover { background: #16a34a; }
.btn-warning-action { background: #f59e0b; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-warning-action:hover { background: #d97706; }
.action-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
.search-box { position: relative; max-width: 320px; margin-bottom: 16px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.summary-stats { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 16px; }
.stat-box { background: #f9fafb; padding: 12px 20px; border-radius: 10px; text-align: center; }
.stat-box .num { font-size: 20px; font-weight: 700; font-family: 'Outfit', sans-serif; }
.stat-box .lbl { font-size: 12px; color: var(--text-muted); }
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
            <i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:8px;"></i>
            <?php echo htmlspecialchars($session['session_code']); ?>
            <span class="badge-st badge-<?php echo htmlspecialchars($session['status']); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($session['status'])); ?></span>
        </h1>
        <a href="stock_take.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    <div class="card">
        <div class="row">
            <div class="col-md-4 mb-2"><strong>Description:</strong> <?php echo htmlspecialchars($session['description'] ?? '-'); ?></div>
            <div class="col-md-2 mb-2"><strong>Type:</strong> <?php echo htmlspecialchars($session['type']); ?></div>
            <div class="col-md-3 mb-2"><strong>Category:</strong> <?php echo htmlspecialchars($session['filter_cat'] ?: 'All'); ?></div>
            <?php if (!empty($session['filter_sub_cat'])): ?>
            <div class="col-md-3 mb-2"><strong>Sub-Category:</strong> <?php echo htmlspecialchars($session['filter_sub_cat']); ?></div>
            <?php endif; ?>
            <div class="col-md-3 mb-2"><strong>Created:</strong> <?php echo date('d/m/Y H:i', strtotime($session['created_at'])); ?> by <?php echo htmlspecialchars($session['created_by']); ?></div>
            <?php if (!empty($session['submitted_by'])): ?>
            <div class="col-md-3 mb-2"><strong>Submitted:</strong> <?php echo !empty($session['submitted_at']) ? date('d/m/Y H:i', strtotime($session['submitted_at'])) : ''; ?> by <?php echo htmlspecialchars($session['submitted_by']); ?></div>
            <?php endif; ?>
            <?php if (!empty($session['approved_by'])): ?>
            <div class="col-md-3 mb-2"><strong>Approved:</strong> <?php echo !empty($session['approved_at']) ? date('d/m/Y H:i', strtotime($session['approved_at'])) : ''; ?> by <?php echo htmlspecialchars($session['approved_by']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Summary stats
    $totalItems = count($items);
    $counted = 0; $withVariance = 0; $adjApplied = 0;
    foreach ($items as $it) {
        if ($it['counted_qty'] !== null) $counted++;
        if ($it['variance'] !== null && floatval($it['variance']) != 0) $withVariance++;
        if ($it['adj_applied']) $adjApplied++;
    }
    ?>
    <div class="summary-stats">
        <div class="stat-box"><div class="num"><?php echo $totalItems; ?></div><div class="lbl">Total Items</div></div>
        <div class="stat-box"><div class="num"><?php echo $counted; ?></div><div class="lbl">Counted</div></div>
        <div class="stat-box"><div class="num" style="color:#d97706;"><?php echo $withVariance; ?></div><div class="lbl">With Variance</div></div>
        <div class="stat-box"><div class="num" style="color:#16a34a;"><?php echo $adjApplied; ?></div><div class="lbl">Adjustments Applied</div></div>
    </div>

    <div class="card">
        <div class="card-title">Count Sheet</div>

        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by barcode or description...">
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table" id="countTable">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Barcode</th>
                        <th>Description</th>
                        <th style="width:90px">System Qty</th>
                        <th style="width:100px">Counted Qty</th>
                        <th style="width:90px">Variance</th>
                        <th>Remark</th>
                        <th style="width:100px">Counted By</th>
                        <th style="width:110px">Counted Date</th>
                        <th style="width:80px">Status</th>
                        <th style="width:80px">Adj Applied</th>
                    </tr>
                </thead>
                <tbody id="countBody">
                    <?php foreach ($items as $idx => $item): ?>
                    <tr data-item-id="<?php echo $item['id']; ?>" data-search="<?php echo htmlspecialchars(strtolower(($item['barcode'] ?? '') . ' ' . ($item['product_desc'] ?? ''))); ?>">
                        <td><?php echo $idx + 1; ?></td>
                        <td><?php echo !empty($item['barcode']) ? htmlspecialchars($item['barcode']) : '<span style="color:#d97706;font-size:11px;">N/A</span>'; ?></td>
                        <td><?php echo !empty($item['product_desc']) ? htmlspecialchars($item['product_desc']) : '<span style="color:#d97706;font-size:11px;">N/A</span>'; ?></td>
                        <td><?php echo $item['system_qty']; ?></td>
                        <td>
                            <?php if ($canEdit && !$item['adj_applied']): ?>
                            <input type="number" class="count-qty" value="<?php echo $item['counted_qty'] ?? ''; ?>" step="0.01" onchange="calcVariance(this);">
                            <?php else: ?>
                            <?php echo $item['counted_qty'] ?? '-'; ?>
                            <?php endif; ?>
                        </td>
                        <td class="variance-cell">
                            <?php
                            if ($item['variance'] !== null) {
                                $v = floatval($item['variance']);
                                if ($v > 0) echo '<span class="variance-pos">+' . $v . '</span>';
                                elseif ($v < 0) echo '<span class="variance-neg">' . $v . '</span>';
                                else echo '<span class="variance-zero">0</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($canEdit && !$item['adj_applied']): ?>
                            <input type="text" class="count-remark" value="<?php echo htmlspecialchars($item['remark'] ?? ''); ?>" placeholder="Remark">
                            <?php else: ?>
                            <?php echo htmlspecialchars($item['remark'] ?? ''); ?>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;"><?php echo !empty($item['counted_by']) ? htmlspecialchars($item['counted_by']) : '-'; ?></td>
                        <td style="font-size:12px;"><?php echo !empty($item['counted_at']) ? date('d/m/Y H:i', strtotime($item['counted_at'])) : '-'; ?></td>
                        <td><?php
                            $itemStatus = $item['status'] ?? 'PENDING';
                            if ($itemStatus === 'COUNTED') echo '<span class="badge-st badge-APPROVED" style="font-size:10px;">COUNTED</span>';
                            else echo '<span class="badge-st badge-SUBMITTED" style="font-size:10px;">PENDING</span>';
                        ?></td>
                        <td><?php echo $item['adj_applied'] ? '<span style="color:#16a34a;font-weight:700;">Yes</span>' : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="action-bar">
        <?php if ($canEdit): ?>
        <button type="button" class="btn-primary-action" onclick="saveCounts();"><i class="fas fa-save"></i> Save Counts</button>
        <?php endif; ?>
        <?php if ($isSubmitted): ?>
        <button type="button" class="btn-success-action" onclick="approveSession();"><i class="fas fa-check-circle"></i> Approve &amp; Apply Adjustments</button>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var sessionId = <?php echo $sessionId; ?>;

document.getElementById('searchInput').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var rows = document.querySelectorAll('#countBody tr');
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        row.style.display = d.indexOf(q) > -1 ? '' : 'none';
    });
});

function calcVariance(input) {
    var tr = input.closest('tr');
    var systemQty = parseFloat(tr.cells[3].textContent) || 0;
    var countedQty = parseFloat(input.value);
    var varianceCell = tr.querySelector('.variance-cell');

    if (isNaN(countedQty) || input.value === '') {
        varianceCell.innerHTML = '-';
        return;
    }

    var variance = countedQty - systemQty;
    if (variance > 0) {
        varianceCell.innerHTML = '<span class="variance-pos">+' + variance.toFixed(2) + '</span>';
    } else if (variance < 0) {
        varianceCell.innerHTML = '<span class="variance-neg">' + variance.toFixed(2) + '</span>';
    } else {
        varianceCell.innerHTML = '<span class="variance-zero">0</span>';
    }
}

function saveCounts() {
    var counts = [];
    document.querySelectorAll('#countBody tr').forEach(function(tr) {
        var itemId = tr.getAttribute('data-item-id');
        var qtyInput = tr.querySelector('.count-qty');
        var remarkInput = tr.querySelector('.count-remark');
        if (qtyInput) {
            counts.push({
                id: itemId,
                counted_qty: qtyInput.value,
                remark: remarkInput ? remarkInput.value : ''
            });
        }
    });

    if (counts.length === 0) {
        Swal.fire({ icon: 'info', text: 'No editable items.' });
        return;
    }

    $.ajax({
        type: 'POST', url: 'stock_take_ajax.php',
        data: { action: 'save_counts', session_id: sessionId, counts: JSON.stringify(counts) },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                Swal.fire({ icon: 'error', text: data.error });
            }
        }
    });
}

function approveSession() {
    Swal.fire({
        title: 'Approve Stock Take?',
        text: 'This will approve the stock take, apply all adjustments to QOH, and lock the session. No further changes will be allowed.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        confirmButtonText: 'Yes, Approve'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'stock_take_ajax.php',
                data: { action: 'approve', session_id: sessionId },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error });
                    }
                }
            });
        }
    });
}
</script>
</body>
</html>
