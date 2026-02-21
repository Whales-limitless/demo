<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch POs with supplier name
$pos = [];
$result = $connect->query("SELECT po.*, s.name AS supplier_name FROM `purchase_order` po LEFT JOIN `supplier` s ON po.supplier_id = s.id ORDER BY po.id DESC");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $pos[] = $r;
    }
}

$currentPage = 'po';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Orders</title>
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
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
.btn-add:hover { background: var(--primary-dark); color: #fff; }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { position: relative; flex: 1; max-width: 320px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.filter-group { display: flex; gap: 8px; align-items: center; }
.filter-group select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-po { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-DRAFT { background: #f3f4f6; color: #6b7280; }
.badge-APPROVED { background: #dbeafe; color: #2563eb; }
.badge-PARTIALLY_RECEIVED { background: #fef3c7; color: #d97706; }
.badge-RECEIVED { background: #dcfce7; color: #16a34a; }
.badge-CLOSED { background: #e5e7eb; color: #374151; }
.badge-CANCELLED { background: #fee2e2; color: #dc2626; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; text-decoration: none; }
.btn-view { background: #6b7280; } .btn-view:hover { background: #4b5563; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; color: #fff; }
@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>Purchase Orders</h1>
        <a href="po_detail.php" class="btn-add"><i class="fas fa-plus"></i> New PO</a>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search POs...">
            </div>
            <div class="filter-group">
                <select id="statusFilter" onchange="filterTable();">
                    <option value="">All Status</option>
                    <option value="DRAFT">Draft</option>
                    <option value="APPROVED">Approved</option>
                    <option value="PARTIALLY_RECEIVED">Partially Received</option>
                    <option value="RECEIVED">Received</option>
                    <option value="CLOSED">Closed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div class="item-count" id="itemCount"><?php echo count($pos); ?> PO(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected</th>
                        <th>Total (RM)</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($pos) === 0): ?>
                    <tr class="no-results"><td colspan="9"><i class="fas fa-file-invoice" style="font-size:24px;margin-bottom:8px;display:block;"></i>No purchase orders found</td></tr>
                    <?php else: ?>
                    <?php foreach ($pos as $i => $po): ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($po['po_number'] ?? '') . ' ' . ($po['supplier_name'] ?? '') . ' ' . ($po['status'] ?? '') . ' ' . ($po['created_by'] ?? '')
                    )); ?>" data-status="<?php echo htmlspecialchars($po['status'] ?? ''); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($po['po_number'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($po['supplier_name'] ?? ''); ?></td>
                        <td><?php echo !empty($po['order_date']) ? date('d/m/Y', strtotime($po['order_date'])) : ''; ?></td>
                        <td><?php echo !empty($po['expected_date']) ? date('d/m/Y', strtotime($po['expected_date'])) : '-'; ?></td>
                        <td><?php echo number_format($po['total_amount'] ?? 0, 2); ?></td>
                        <td><span class="badge-po badge-<?php echo htmlspecialchars($po['status'] ?? 'DRAFT'); ?>"><?php echo str_replace('_', ' ', htmlspecialchars($po['status'] ?? '')); ?></span></td>
                        <td><?php echo htmlspecialchars($po['created_by'] ?? ''); ?></td>
                        <td style="white-space:nowrap">
                            <a href="po_detail.php?id=<?php echo (int)$po['id']; ?>" class="btn-action btn-view"><i class="fas fa-eye"></i> View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterTable() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    var status = document.getElementById('statusFilter').value;
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;

    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        var s = row.getAttribute('data-status') || '';
        var matchSearch = d.indexOf(query) > -1;
        var matchStatus = status === '' || s === status;
        if (matchSearch && matchStatus) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });

    document.getElementById('itemCount').textContent = count + ' PO(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
}

document.getElementById('searchInput').addEventListener('input', filterTable);
</script>
</body>
</html>
