<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch all stock take sessions
$sessions = [];
$result = $connect->query("
    SELECT st.*,
        COUNT(sti.id) AS total_items,
        SUM(CASE WHEN sti.status = 'COUNTED' THEN 1 ELSE 0 END) AS counted_items,
        SUM(CASE WHEN sti.variance != 0 AND sti.status = 'COUNTED' THEN 1 ELSE 0 END) AS variance_items
    FROM `stock_take` st
    LEFT JOIN `stock_take_item` sti ON sti.stock_take_id = st.id
    GROUP BY st.id
    ORDER BY st.id DESC
");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $r['total_items'] = intval($r['total_items']);
        $r['counted_items'] = intval($r['counted_items']);
        $r['variance_items'] = intval($r['variance_items']);
        $sessions[] = $r;
    }
}

// Fetch categories for create modal
$categories = [];
$catResult = $connect->query("SELECT DISTINCT `cat` FROM `PRODUCTS` WHERE `cat` IS NOT NULL AND `cat` != '' ORDER BY `cat` ASC");
if ($catResult) {
    while ($r = $catResult->fetch_assoc()) {
        $categories[] = $r['cat'];
    }
}

// Fetch sub categories grouped by cat
$subCategories = [];
$subCatResult = $connect->query("SELECT DISTINCT `cat`, `sub_cat` FROM `PRODUCTS` WHERE `sub_cat` IS NOT NULL AND `sub_cat` != '' ORDER BY `cat` ASC, `sub_cat` ASC");
if ($subCatResult) {
    while ($r = $subCatResult->fetch_assoc()) {
        $subCategories[] = $r;
    }
}

// Check if viewing a specific session detail
$viewId = intval($_GET['view'] ?? 0);
$viewSession = null;
$viewItems = [];

if ($viewId > 0) {
    $stmt = $connect->prepare("SELECT * FROM `stock_take` WHERE `id` = ? LIMIT 1");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $res = $stmt->get_result();
    $viewSession = $res->fetch_assoc();
    $stmt->close();

    if ($viewSession) {
        $itemResult = $connect->query("SELECT * FROM `stock_take_item` WHERE `stock_take_id` = " . intval($viewId) . " ORDER BY `id` ASC");
        if ($itemResult) {
            while ($r = $itemResult->fetch_assoc()) {
                $viewItems[] = $r;
            }
        }
    }
}

$currentPage = 'stock_take';
$isDetailView = ($viewId > 0 && $viewSession);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isDetailView ? 'Stock Take - ' . htmlspecialchars($viewSession['session_code']) : 'Stock Take'; ?></title>
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
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.btn-outline { background: transparent; color: var(--text); border: 1px solid #d1d5db; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-outline:hover { background: #f3f4f6; color: var(--text); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { position: relative; flex: 1; max-width: 320px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.filter-group { display: flex; gap: 8px; align-items: center; }
.filter-group select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-DRAFT { background: #dbeafe; color: #2563eb; }
.badge-SUBMITTED { background: #dcfce7; color: #16a34a; }
.badge-APPROVED { background: #f3e8ff; color: #7c3aed; }
.badge-OPEN { background: #fef3c7; color: #d97706; }
.badge-IN_PROGRESS { background: #fef3c7; color: #d97706; }
.badge-COMPLETED { background: #e5e7eb; color: #374151; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; text-decoration: none; }
.btn-view { background: #6b7280; } .btn-view:hover { background: #4b5563; color: #fff; }
.btn-approve { background: #7c3aed; } .btn-approve:hover { background: #6d28d9; color: #fff; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; color: #fff; }
.progress-mini { width: 60px; height: 6px; background: #e5e7eb; border-radius: 3px; display: inline-block; vertical-align: middle; margin-right: 6px; overflow: hidden; }
.progress-mini-fill { height: 100%; background: var(--primary); border-radius: 3px; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* Detail View */
.detail-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; margin-bottom: 20px; }
.detail-card .card-title { font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 16px; margin-bottom: 16px; }
.detail-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
.detail-info-item label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 2px; }
.detail-info-item span { font-size: 14px; font-weight: 500; color: var(--text); }
.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.summary-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 16px; text-align: center; }
.summary-card .sc-value { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; }
.summary-card .sc-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.sc-total .sc-value { color: var(--text); }
.sc-counted .sc-value { color: #2563eb; }
.sc-match .sc-value { color: #16a34a; }
.sc-variance .sc-value { color: #dc2626; }
.variance-pos { color: #16a34a; font-weight: 600; }
.variance-neg { color: #dc2626; font-weight: 600; }
.variance-zero { color: var(--text-muted); }
.action-bar { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-primary-action { background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-primary-action:hover { background: var(--primary-dark); }
.btn-success-action { background: #7c3aed; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-success-action:hover { background: #6d28d9; }
.btn-warning-action { background: #d97706; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.btn-warning-action:hover { background: #b45309; }
@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
    .detail-info { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">

<?php if ($isDetailView): ?>
    <!-- DETAIL VIEW -->
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:8px;"></i><?php echo htmlspecialchars($viewSession['session_code']); ?></h1>
        <div style="display:flex;gap:8px;">
            <a href="stock_take_print.php?id=<?php echo $viewId; ?>" target="_blank" class="btn-outline"><i class="fas fa-print"></i> Print</a>
            <a href="stock_take.php" class="btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>

    <?php
    $totalItems = count($viewItems);
    $countedItems = 0;
    $matchItems = 0;
    $varianceItems = 0;
    $totalVariance = 0;
    foreach ($viewItems as $item) {
        if ($item['status'] === 'COUNTED') {
            $countedItems++;
            $v = floatval($item['variance'] ?? 0);
            if ($v == 0) $matchItems++;
            else { $varianceItems++; $totalVariance += $v; }
        }
    }
    ?>

    <div class="summary-cards">
        <div class="summary-card sc-total">
            <div class="sc-value"><?php echo $totalItems; ?></div>
            <div class="sc-label">Total Items</div>
        </div>
        <div class="summary-card sc-counted">
            <div class="sc-value"><?php echo $countedItems; ?></div>
            <div class="sc-label">Counted</div>
        </div>
        <div class="summary-card sc-match">
            <div class="sc-value"><?php echo $matchItems; ?></div>
            <div class="sc-label">Match</div>
        </div>
        <div class="summary-card sc-variance">
            <div class="sc-value"><?php echo $varianceItems; ?></div>
            <div class="sc-label">With Variance</div>
        </div>
    </div>

    <div class="detail-card">
        <div class="card-title">Session Details</div>
        <div class="detail-info">
            <div class="detail-info-item">
                <label>Session Code</label>
                <span><?php echo htmlspecialchars($viewSession['session_code']); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Description</label>
                <span><?php echo htmlspecialchars($viewSession['description'] ?: '-'); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Type</label>
                <span><?php echo htmlspecialchars($viewSession['type']); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Status</label>
                <span class="badge-status badge-<?php echo htmlspecialchars($viewSession['status']); ?>"><?php echo htmlspecialchars($viewSession['status']); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Created By</label>
                <span><?php echo htmlspecialchars($viewSession['created_by'] ?: '-'); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Created At</label>
                <span><?php echo !empty($viewSession['created_at']) ? date('d/m/Y H:i', strtotime($viewSession['created_at'])) : '-'; ?></span>
            </div>
            <?php if (!empty($viewSession['submitted_by'])): ?>
            <div class="detail-info-item">
                <label>Submitted By</label>
                <span><?php echo htmlspecialchars($viewSession['submitted_by']); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Submitted At</label>
                <span><?php echo !empty($viewSession['submitted_at']) ? date('d/m/Y H:i', strtotime($viewSession['submitted_at'])) : '-'; ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($viewSession['approved_by'])): ?>
            <div class="detail-info-item">
                <label>Approved By</label>
                <span><?php echo htmlspecialchars($viewSession['approved_by']); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Approved At</label>
                <span><?php echo !empty($viewSession['approved_at']) ? date('d/m/Y H:i', strtotime($viewSession['approved_at'])) : '-'; ?></span>
            </div>
            <?php endif; ?>
            <?php if ($viewSession['type'] === 'PARTIAL'): ?>
            <div class="detail-info-item">
                <label>Filter Category</label>
                <span><?php echo htmlspecialchars($viewSession['filter_cat'] ?: '-'); ?></span>
            </div>
            <div class="detail-info-item">
                <label>Filter Sub Category</label>
                <span><?php echo htmlspecialchars($viewSession['filter_sub_cat'] ?: '-'); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($viewSession['status'] === 'SUBMITTED'): ?>
    <div class="detail-card">
        <div class="action-bar">
            <button class="btn-success-action" onclick="approveSession(<?php echo $viewId; ?>);">
                <i class="fas fa-check-double"></i> Approve &amp; Apply Adjustments
            </button>
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin-top:10px;">Approving will adjust PRODUCTS QOH based on counted quantities for items with variance.</p>
    </div>
    <?php endif; ?>


    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search items...">
            </div>
            <div class="filter-group">
                <select id="varianceFilter" onchange="filterDetailTable();">
                    <option value="">All Items</option>
                    <option value="variance">With Variance Only</option>
                    <option value="match">Match Only</option>
                    <option value="pending">Pending Count</option>
                </select>
            </div>
            <div class="item-count" id="itemCount"><?php echo $totalItems; ?> item(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Barcode</th>
                        <th>Description</th>
                        <th>System Qty</th>
                        <th>Counted Qty</th>
                        <th>Variance</th>
                        <th>Status</th>
                        <th>Counted By</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($viewItems) === 0): ?>
                    <tr class="no-results"><td colspan="9">No items in this session</td></tr>
                    <?php else: ?>
                    <?php foreach ($viewItems as $i => $item):
                        $v = floatval($item['variance'] ?? 0);
                        $vClass = 'variance-zero';
                        $vDisplay = '-';
                        if ($item['status'] === 'COUNTED') {
                            if ($v > 0) { $vClass = 'variance-pos'; $vDisplay = '+' . $v; }
                            elseif ($v < 0) { $vClass = 'variance-neg'; $vDisplay = $v; }
                            else { $vClass = 'variance-zero'; $vDisplay = '0'; }
                        }
                        $hasVariance = ($item['status'] === 'COUNTED' && $v != 0) ? 'yes' : 'no';
                        $isMatch = ($item['status'] === 'COUNTED' && $v == 0) ? 'yes' : 'no';
                        $isPending = ($item['status'] !== 'COUNTED') ? 'yes' : 'no';
                    ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($item['barcode'] ?? '') . ' ' . ($item['product_desc'] ?? '') . ' ' . ($item['remark'] ?? '') . ' ' . ($item['counted_by'] ?? '')
                    )); ?>" data-variance="<?php echo $hasVariance; ?>" data-match="<?php echo $isMatch; ?>" data-pending="<?php echo $isPending; ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($item['barcode'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['product_desc'] ?? ''); ?></td>
                        <td><?php echo floatval($item['system_qty']); ?></td>
                        <td><?php echo $item['status'] === 'COUNTED' ? floatval($item['counted_qty']) : '-'; ?></td>
                        <td><span class="<?php echo $vClass; ?>"><?php echo $vDisplay; ?></span></td>
                        <td><span class="badge-status <?php echo $item['status'] === 'COUNTED' ? 'badge-SUBMITTED' : 'badge-DRAFT'; ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($item['counted_by'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($item['remark'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- SESSION LIST VIEW -->
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check" style="color:var(--primary);margin-right:8px;"></i>Stock Take</h1>
        <button class="btn-add" onclick="openCreateModal();"><i class="fas fa-plus"></i> New Session</button>
    </div>

    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search sessions...">
            </div>
            <div class="filter-group">
                <select id="statusFilter" onchange="filterTable();">
                    <option value="">All Status</option>
                    <option value="DRAFT">Draft</option>
                    <option value="SUBMITTED">Submitted</option>
                    <option value="APPROVED">Approved</option>
                </select>
            </div>
            <div class="item-count" id="itemCount"><?php echo count($sessions); ?> session(s)</div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Session Code</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <?php if (count($sessions) === 0): ?>
                    <tr class="no-results"><td colspan="9"><i class="fas fa-clipboard-check" style="font-size:24px;margin-bottom:8px;display:block;"></i>No stock take sessions</td></tr>
                    <?php else: ?>
                    <?php foreach ($sessions as $i => $s):
                        $total = $s['total_items'];
                        $counted = $s['counted_items'];
                        $pct = $total > 0 ? round(($counted / $total) * 100) : 0;
                    ?>
                    <tr data-search="<?php echo htmlspecialchars(strtolower(
                        ($s['session_code'] ?? '') . ' ' . ($s['description'] ?? '') . ' ' . ($s['created_by'] ?? '') . ' ' . ($s['status'] ?? '')
                    )); ?>" data-status="<?php echo htmlspecialchars($s['status']); ?>">
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($s['session_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['description'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($s['type']); ?></td>
                        <td>
                            <div class="progress-mini"><div class="progress-mini-fill" style="width:<?php echo $pct; ?>%"></div></div>
                            <span style="font-size:12px;"><?php echo $counted; ?>/<?php echo $total; ?></span>
                        </td>
                        <td><span class="badge-status badge-<?php echo htmlspecialchars($s['status']); ?>"><?php echo htmlspecialchars($s['status']); ?></span></td>
                        <td><?php echo htmlspecialchars($s['created_by'] ?: '-'); ?></td>
                        <td><?php echo !empty($s['created_at']) ? date('d/m/Y', strtotime($s['created_at'])) : ''; ?></td>
                        <td>
                            <a href="stock_take.php?view=<?php echo $s['id']; ?>" class="btn-action btn-view"><i class="fas fa-eye"></i> View</a>
                            <?php if ($s['status'] === 'DRAFT'): ?>
                            <button class="btn-action btn-delete" onclick="deleteSession(<?php echo $s['id']; ?>);"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

</div>

<!-- Create Session Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> New Stock Take Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" id="fDescription" class="form-control" placeholder="e.g. Monthly stock take March 2026">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select id="fType" class="form-select" onchange="toggleFilters();">
                        <option value="FULL">Full Stock Take (All Products)</option>
                        <option value="PARTIAL">Partial (Filter by Category/Sub Category)</option>
                    </select>
                </div>
                <div id="filterFields" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select id="fCategory" class="form-select" onchange="onCategoryChange();">
                                <option value="">-- All Categories --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Sub Category</label>
                            <select id="fSubCategory" class="form-select" onchange="loadProducts();">
                                <option value="">-- All Sub Categories --</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold mb-0">Select Products</label>
                            <span class="text-muted" style="font-size:12px;" id="productSelCount">0 selected</span>
                        </div>
                        <!-- Smart Selection Bar -->
                        <div id="smartSelectBar" style="display:none;margin-bottom:8px;padding:10px 12px;background:#f0f4ff;border:1px solid #c7d2fe;border-radius:8px;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span style="font-size:12px;font-weight:600;color:#4338ca;">Quick Select:</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="smartSelect('all')" style="font-size:12px;padding:3px 12px;">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="smartSelect('never')" style="font-size:12px;padding:3px 12px;">Never Stocked</button>
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="smartSelect('topN')" style="font-size:12px;padding:3px 12px;">Top</button>
                                    <input type="number" id="smartSelectN" value="50" min="1" style="width:60px;padding:3px 6px;border:1px solid #c7d2fe;border-radius:6px;font-size:12px;text-align:center;" onkeydown="if(event.key==='Enter'){event.preventDefault();smartSelect('topN');}">
                                    <span style="font-size:11px;color:#6366f1;">oldest first</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="smartSelect('none')" style="font-size:12px;padding:3px 12px;">Deselect All</button>
                            </div>
                            <div id="smartSelectInfo" style="font-size:11px;color:#6b7280;margin-top:6px;display:none;"></div>
                        </div>
                        <div style="position:relative;margin-bottom:8px;">
                            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                            <input type="text" id="productSearch" class="form-control" style="padding-left:36px;font-size:13px;" placeholder="Search by product name..." oninput="filterProductTable();">
                        </div>
                        <div style="max-height:300px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;">
                            <table class="data-table" id="productTable" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th style="width:36px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this);"></th>
                                        <th>Barcode</th>
                                        <th>Name</th>
                                        <th>QOH</th>
                                        <th style="width:100px;">Last Count</th>
                                    </tr>
                                </thead>
                                <tbody id="productBody">
                                    <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">Select a category or sub category to load products</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger w-50" onclick="createSession();"><i class="fas fa-plus"></i> Create Session</button>
            </div>
        </div>
    </div>
</div>

<script>
// Sub categories data from PHP
var subCategoriesData = <?php echo json_encode($subCategories); ?>;
</script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var createModalEl = document.getElementById('createModal');
var createModal = null;
document.addEventListener('DOMContentLoaded', function() {
    if (createModalEl) createModal = new bootstrap.Modal(createModalEl);
});

<?php if ($isDetailView): ?>
// Detail view filtering
function filterDetailTable() {
    var query = document.getElementById('searchInput').value.toLowerCase();
    var filter = document.getElementById('varianceFilter').value;
    var rows = document.querySelectorAll('#dataBody tr:not(.no-results)');
    var count = 0;
    rows.forEach(function(row) {
        var d = row.getAttribute('data-search') || '';
        var matchSearch = d.indexOf(query) > -1;
        var matchFilter = true;
        if (filter === 'variance') matchFilter = row.getAttribute('data-variance') === 'yes';
        else if (filter === 'match') matchFilter = row.getAttribute('data-match') === 'yes';
        else if (filter === 'pending') matchFilter = row.getAttribute('data-pending') === 'yes';
        if (matchSearch && matchFilter) { row.style.display = ''; count++; }
        else { row.style.display = 'none'; }
    });
    document.getElementById('itemCount').textContent = count + ' item(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
}
document.getElementById('searchInput').addEventListener('input', filterDetailTable);

function approveSession(id) {
    Swal.fire({
        title: 'Approve Stock Take?',
        html: 'This will:<br>1. Approve the session<br>2. Adjust product QOH for items with variance<br>3. Record adjustments in stock adjustment log',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        confirmButtonText: 'Yes, Approve & Adjust'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'stock_take_ajax.php',
                data: { action: 'approve', session_id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, showConfirmButton: true }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error });
                    }
                }
            });
        }
    });
}

<?php else: ?>
// List view filtering
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
    document.getElementById('itemCount').textContent = count + ' session(s)';
    var num = 1;
    rows.forEach(function(row) { if (row.style.display !== 'none') row.cells[0].textContent = num++; });
}
document.getElementById('searchInput').addEventListener('input', filterTable);
<?php endif; ?>

function openCreateModal() {
    document.getElementById('fDescription').value = '';
    document.getElementById('fType').value = 'FULL';
    document.getElementById('fCategory').value = '';
    document.getElementById('fSubCategory').innerHTML = '<option value="">-- All Sub Categories --</option>';
    document.getElementById('filterFields').style.display = 'none';
    document.getElementById('productBody').innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">Select a category or sub category to load products</td></tr>';
    document.getElementById('productSearch').value = '';
    document.getElementById('productSelCount').textContent = '0 selected';
    document.getElementById('selectAll').checked = false;
    document.getElementById('smartSelectBar').style.display = 'none';
    document.getElementById('smartSelectN').value = '50';
    loadedProducts = [];
    createModal.show();
}

function toggleFilters() {
    var type = document.getElementById('fType').value;
    document.getElementById('filterFields').style.display = type === 'PARTIAL' ? 'block' : 'none';
}

function onCategoryChange() {
    var cat = document.getElementById('fCategory').value;
    var subSelect = document.getElementById('fSubCategory');
    subSelect.innerHTML = '<option value="">-- All Sub Categories --</option>';

    if (cat !== '') {
        var filtered = subCategoriesData.filter(function(s) { return s.cat === cat; });
        filtered.forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.sub_cat;
            opt.textContent = s.sub_cat;
            subSelect.appendChild(opt);
        });
    }
    loadProducts();
}

function loadProducts() {
    var cat = document.getElementById('fCategory').value;
    var subCat = document.getElementById('fSubCategory').value;

    if (cat === '' && subCat === '') {
        document.getElementById('productBody').innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">Select a category or sub category to load products</td></tr>';
        document.getElementById('smartSelectBar').style.display = 'none';
        updateSelectionCount();
        return;
    }

    document.getElementById('productBody').innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';

    $.ajax({
        type: 'POST', url: 'stock_take_ajax.php',
        data: { action: 'load_products', filter_cat: cat, filter_sub_cat: subCat },
        dataType: 'json',
        success: function(data) {
            if (data.success && data.products) {
                renderProductTable(data.products);
            } else {
                document.getElementById('productBody').innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">No products found</td></tr>';
            }
            updateSelectionCount();
        }
    });
}

// Store loaded products globally for smart selection
var loadedProducts = [];

function renderProductTable(products) {
    loadedProducts = products;
    var body = document.getElementById('productBody');
    var smartBar = document.getElementById('smartSelectBar');

    if (products.length === 0) {
        body.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-muted);">No products found</td></tr>';
        smartBar.style.display = 'none';
        return;
    }

    // Products are already sorted by last_stock_take ASC (NULL first) from server
    var html = '';
    products.forEach(function(p) {
        var lastCount = p.last_stock_take ? formatDate(p.last_stock_take) : '';
        var lastCountLabel = p.last_stock_take ? lastCount : '<span style="color:#dc2626;font-size:11px;">Never</span>';
        html += '<tr data-name="' + (p.name || '').toLowerCase() + '" data-barcode="' + escapeAttr(p.barcode) + '" data-last="' + (p.last_stock_take || '') + '">' +
            '<td><input type="checkbox" class="product-cb" value="' + escapeAttr(p.barcode) + '" checked onchange="updateSelectionCount();"></td>' +
            '<td>' + escapeHtml(p.barcode) + '</td>' +
            '<td>' + escapeHtml(p.name) + '</td>' +
            '<td>' + parseFloat(p.qoh || 0) + '</td>' +
            '<td>' + lastCountLabel + '</td>' +
        '</tr>';
    });
    body.innerHTML = html;
    document.getElementById('selectAll').checked = true;
    smartBar.style.display = 'block';

    // Auto-apply smart select with default N
    var neverCount = products.filter(function(p) { return !p.last_stock_take; }).length;
    var infoHtml = '<i class="fas fa-info-circle"></i> ' + products.length + ' total items';
    if (neverCount > 0) infoHtml += ', <b>' + neverCount + ' never counted</b>';
    document.getElementById('smartSelectInfo').innerHTML = infoHtml;
    document.getElementById('smartSelectInfo').style.display = 'block';

    updateSelectionCount();
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    var dd = String(d.getDate()).padStart(2, '0');
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    var yy = d.getFullYear();
    return dd + '/' + mm + '/' + yy;
}

function smartSelect(mode) {
    var checkboxes = document.querySelectorAll('#productBody .product-cb');
    if (checkboxes.length === 0) return;

    if (mode === 'all') {
        checkboxes.forEach(function(cb) { cb.checked = true; });
        document.getElementById('selectAll').checked = true;
    } else if (mode === 'none') {
        checkboxes.forEach(function(cb) { cb.checked = false; });
        document.getElementById('selectAll').checked = false;
    } else if (mode === 'never') {
        // Select only items that have never been stock taken
        checkboxes.forEach(function(cb) {
            var row = cb.closest('tr');
            var lastDate = row.getAttribute('data-last') || '';
            cb.checked = (lastDate === '');
        });
        document.getElementById('selectAll').checked = false;
    } else if (mode === 'topN') {
        // Select top N items sorted by: never first, then oldest date
        var n = parseInt(document.getElementById('smartSelectN').value) || 50;
        // Products are already sorted from server (NULL/never first, then oldest)
        // Just select first N
        checkboxes.forEach(function(cb, idx) { cb.checked = (idx < n); });
        document.getElementById('selectAll').checked = false;
    }

    updateSelectionCount();
}

function filterProductTable() {
    var query = document.getElementById('productSearch').value.toLowerCase().trim();
    var rows = document.querySelectorAll('#productBody tr[data-name]');
    rows.forEach(function(row) {
        var name = row.getAttribute('data-name') || '';
        row.style.display = (query === '' || name.indexOf(query) > -1) ? '' : 'none';
    });
}

function toggleSelectAll(el) {
    var checkboxes = document.querySelectorAll('#productBody .product-cb');
    checkboxes.forEach(function(cb) {
        var row = cb.closest('tr');
        if (row.style.display !== 'none') {
            cb.checked = el.checked;
        }
    });
    updateSelectionCount();
}

function updateSelectionCount() {
    var checked = document.querySelectorAll('#productBody .product-cb:checked').length;
    document.getElementById('productSelCount').textContent = checked + ' selected';
}

function getSelectedBarcodes() {
    var barcodes = [];
    document.querySelectorAll('#productBody .product-cb:checked').forEach(function(cb) {
        barcodes.push(cb.value);
    });
    return barcodes;
}

function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function escapeAttr(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function createSession() {
    var description = document.getElementById('fDescription').value.trim();
    var type = document.getElementById('fType').value;
    var category = document.getElementById('fCategory').value;
    var subCategory = document.getElementById('fSubCategory').value;

    var selectedBarcodes = [];
    if (type === 'PARTIAL') {
        selectedBarcodes = getSelectedBarcodes();
        if (selectedBarcodes.length === 0) {
            Swal.fire({ icon: 'warning', text: 'Please select at least one product.' });
            return;
        }
    }

    var itemText = type === 'FULL' ? 'All active products will be added to this session.' : selectedBarcodes.length + ' selected product(s) will be added to this session.';

    Swal.fire({
        title: 'Create Stock Take Session?',
        text: itemText,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#C8102E',
        confirmButtonText: 'Yes, Create'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Creating session...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
            $.ajax({
                type: 'POST', url: 'stock_take_ajax.php',
                data: {
                    action: 'create',
                    description: description,
                    type: type,
                    filter_cat: category,
                    filter_sub_cat: subCategory,
                    barcodes: JSON.stringify(selectedBarcodes)
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        createModal.hide();
                        // Open print page in new window
                        if (data.session_id) {
                            window.open('stock_take_print.php?id=' + data.session_id + '&auto_print=1', '_blank');
                        }
                        Swal.fire({ icon: 'success', text: data.success, timer: 2000, showConfirmButton: false }).then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error });
                    }
                }
            });
        }
    });
}

function deleteSession(id) {
    Swal.fire({
        title: 'Delete Session?',
        text: 'This will permanently delete the session and all its items.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST', url: 'stock_take_ajax.php',
                data: { action: 'delete', session_id: id },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { window.location.href = 'stock_take.php'; });
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
