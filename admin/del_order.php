<?php
require_once __DIR__ . '/../staff/session_security.php';
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
$connect->set_charset("utf8mb4");

// Check if viewing a specific order
$viewId = intval($_GET['view'] ?? 0);
$viewOrder = null;
$viewItems = [];
$viewSign = false;
if ($viewId > 0) {
    $stmt = $connect->prepare("SELECT o.*, c.HP AS CUST_PHONE, c.ADDRESS AS CUST_ADDRESS FROM `del_orderlist` o LEFT JOIN `del_customer` c ON o.CUSTOMERCODE = c.CODE WHERE o.ID = ? LIMIT 1");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) {
        $viewOrder = $r->fetch_assoc();
        // Get items
        $istmt = $connect->prepare("SELECT * FROM `del_orderlistdesc` WHERE `ORDERNO` = ?");
        $istmt->bind_param("s", $viewOrder['ORDNO']);
        $istmt->execute();
        $ir = $istmt->get_result();
        while ($row = $ir->fetch_assoc()) { $viewItems[] = $row; }
        $istmt->close();
        // Check signature
        $sstmt = $connect->prepare("SELECT ID FROM `del_sign` WHERE `ORDNO` = ? LIMIT 1");
        $sstmt->bind_param("s", $viewOrder['ORDNO']);
        $sstmt->execute();
        $viewSign = $sstmt->get_result()->num_rows > 0;
        $sstmt->close();
    }
    $stmt->close();
}

// Fetch customers and drivers for dropdowns
$customers = [];
$cr = $connect->query("SELECT * FROM `del_customer` ORDER BY `NAME` ASC");
if ($cr) { while ($row = $cr->fetch_assoc()) { $customers[] = $row; } }

$drivers = [];
$dr = $connect->query("SELECT `USERNAME` AS `CODE`, `USER_NAME` AS `NAME` FROM `sysfile` WHERE `TYPE` = 'D' ORDER BY `USER_NAME` ASC");
if ($dr) { while ($row = $dr->fetch_assoc()) { $drivers[] = $row; } }

$uoms = [];
$ur = $connect->query("SELECT * FROM `del_uom` ORDER BY `PDESC` ASC");
if ($ur) { while ($row = $ur->fetch_assoc()) { $uoms[] = $row; } }

$editId = intval($_GET['edit'] ?? 0);
$currentPage = 'del_order';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $viewOrder ? 'Delivery Order - ' . htmlspecialchars($viewOrder['ORDNO']) : 'Delivery Orders'; ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; }
:root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; --radius: 12px; --shadow-md: 0 4px 16px rgba(0,0,0,0.08); --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; margin: 0; }
.page-content { max-width: 1400px; margin: 0 auto; padding: 20px 24px 40px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-header h1 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700; margin: 0; }
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { position: relative; flex: 1; max-width: 320px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; }
.btn-view { background: #8b5cf6; } .btn-view:hover { background: #7c3aed; }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-order { background: #fee2e2; color: #dc2626; }
.badge-assigned { background: #e0e7ff; color: #4338ca; }
.badge-done { background: #dcfce7; color: #16a34a; }
.badge-completed { background: #f0fdf4; color: #166534; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; } .modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* Filter bar */
.filter-bar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; align-items: center; }
.filter-bar input, .filter-bar select { padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; }
.filter-bar button { padding: 7px 16px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }

/* DO view */
.do-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 30px; margin-bottom: 20px; }
.do-header { text-align: center; margin-bottom: 24px; }
.do-header h2 { font-family: 'Outfit', sans-serif; font-size: 20px; margin: 0; }
.do-info { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; font-size: 13px; }
.do-info dt { font-weight: 700; color: var(--text-muted); }
.do-items { margin-bottom: 20px; }
.do-items table { width: 100%; border-collapse: collapse; font-size: 13px; }
.do-items th, .do-items td { border: 1px solid #e5e7eb; padding: 8px 12px; text-align: left; }
.do-items th { background: #f9fafb; font-weight: 600; }
.do-signature img { max-width: 300px; border: 1px solid #e5e7eb; border-radius: 8px; }
.btn-back { background: #6b7280; color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.btn-print { background: #3b82f6; color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }

/* Item table in modal */
.item-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
.item-row input, .item-row select { flex: 1; }
.item-list { max-height: 200px; overflow-y: auto; margin-top: 12px; }
.item-list table { width: 100%; font-size: 12px; }
.item-list td { padding: 4px 8px; border-bottom: 1px solid #f3f4f6; }
.btn-remove-item { background: #ef4444; color: #fff; border: none; border-radius: 4px; padding: 2px 8px; cursor: pointer; font-size: 11px; }

@media (max-width: 768px) { .page-content { padding: 16px; } .table-card { padding: 12px; } .do-info { grid-template-columns: 1fr; } }
@media print { .page-header, .btn-back, .btn-print, .admin-topbar, .admin-sidebar, .sidebar-overlay { display: none !important; } .page-content { margin: 0 !important; padding: 0 !important; } .do-card { box-shadow: none; } }
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">

<?php if ($viewOrder): ?>
<!-- VIEW DELIVERY ORDER -->
<div class="page-header">
    <h1><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>Delivery Order</h1>
    <div>
        <a href="del_order.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="del_order.php?edit=<?php echo $viewOrder['ID']; ?>" class="btn-print" style="background:#3b82f6;text-decoration:none;"><i class="fas fa-pen"></i> Edit</a>
        <button class="btn-print" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
    </div>
</div>
<div class="do-card">
    <div class="do-header">
        <h2>DELIVERY ORDER</h2>
        <p style="color:var(--text-muted);font-size:13px;">Order No: <strong><?php echo htmlspecialchars($viewOrder['ORDNO']); ?></strong></p>
    </div>
    <div class="do-info">
        <div><dt>Delivery Date</dt><dd><?php echo htmlspecialchars($viewOrder['DELDATE'] ?? ''); ?></dd></div>
        <div><dt>Status</dt><dd><?php
            $sm = ['' => 'Order', 'A' => 'Assigned', 'D' => 'Done', 'C' => 'Completed'];
            echo $sm[$viewOrder['STATUS']] ?? $viewOrder['STATUS'];
        ?></dd></div>
        <div><dt>Customer</dt><dd><?php echo htmlspecialchars($viewOrder['CUSTOMER'] ?? ''); ?></dd></div>
        <div><dt>Driver</dt><dd><?php echo htmlspecialchars($viewOrder['DRIVER'] ?? '-'); ?></dd></div>
        <div><dt>Address</dt><dd><?php echo htmlspecialchars($viewOrder['CUST_ADDRESS'] ?? ''); ?></dd></div>
        <div><dt>Phone</dt><dd><?php echo htmlspecialchars($viewOrder['CUST_PHONE'] ?? ''); ?></dd></div>
        <div><dt>Location</dt><dd><?php echo htmlspecialchars($viewOrder['LOCATION'] ?? ''); ?></dd></div>
        <div><dt>Purchase Date</dt><dd><?php echo !empty($viewOrder['CREATED_AT']) ? htmlspecialchars(date('Y-m-d', strtotime($viewOrder['CREATED_AT']))) : '-'; ?></dd></div>
        <?php if ($viewOrder['REMARK']): ?><div style="grid-column:1/-1"><dt>Remark</dt><dd><?php echo htmlspecialchars($viewOrder['REMARK']); ?></dd></div><?php endif; ?>
    </div>
    <div class="do-items">
        <h6 style="font-weight:700;margin-bottom:8px;">Order Items</h6>
        <table>
            <thead><tr><th>No</th><th>Description</th><th>Qty</th><th>UOM</th><th>Installation</th></tr></thead>
            <tbody>
                <?php if (count($viewItems) === 0): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No items</td></tr>
                <?php else: ?>
                <?php foreach ($viewItems as $idx => $item): ?>
                <tr>
                    <td><?php echo $idx + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['PDESC'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($item['QTY'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($item['UOM'] ?? ''); ?></td>
                    <td><?php echo (isset($item['INSTALL']) && $item['INSTALL'] === 'Y') ? '<span style="color:#f59e0b;font-weight:600;"><i class="fas fa-tools"></i> Yes</span>' : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($viewSign): ?>
    <div class="do-signature">
        <h6 style="font-weight:700;margin-bottom:8px;">Customer Signature</h6>
        <?php
            $safeOrdno = preg_replace('/[\/\\\\:*?"<>|]/', '_', $viewOrder['ORDNO']);
            $sigPath = '../staff/uploads/signatures/' . $safeOrdno . '.png';
        ?>
        <img src="<?php echo $sigPath; ?>" alt="Signature">
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ORDER LIST -->
<div class="page-header">
    <h1><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>Delivery Orders</h1>
    <button class="btn-add" onclick="openCreateModal();"><i class="fas fa-plus"></i> Add Order</button>
</div>

<div class="filter-bar">
    <input type="date" id="filterStart" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
    <span>to</span>
    <input type="date" id="filterEnd" value="<?php echo date('Y-m-d'); ?>">
    <select id="filterStatus">
        <option value="">All Status</option>
        <option value="O">Order</option>
        <option value="A">Assigned</option>
        <option value="D">Done</option>
        <option value="C">Completed</option>
    </select>
    <button onclick="loadOrders();"><i class="fas fa-filter"></i> Filter</button>
</div>

<div class="table-card">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead><tr><th style="width:40px">No</th><th>Del. Date</th><th>Order No</th><th>Driver</th><th>Customer</th><th>Address</th><th>Status</th><th style="width:1%">Action</th></tr></thead>
            <tbody id="dataBody">
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-file-invoice"></i> Add Delivery Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Order No <span class="text-danger">*</span></label><input type="text" id="fOrdno" class="form-control" placeholder="e.g. DO-001"></div>
                    <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Delivery Date <span class="text-danger">*</span></label><input type="date" id="fDeldate" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="col-md-4 mb-3"><label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                        <select id="fCustomer" class="form-select">
                            <option value="">-- Select Customer --</option>
                            <?php foreach ($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['CODE']); ?>" data-name="<?php echo htmlspecialchars($c['NAME']); ?>" data-location="<?php echo htmlspecialchars($c['LOCATION']); ?>" data-address="<?php echo htmlspecialchars($c['ADDRESS'] ?? ''); ?>"><?php echo htmlspecialchars($c['NAME']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Location</label><input type="text" id="fLocation" class="form-control" readonly></div>
                    <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Address</label><input type="text" id="fAddress" class="form-control" readonly></div>
                </div>
                <div class="mb-3"><label class="form-label fw-semibold">Remark</label><textarea id="fRemark" class="form-control" rows="2" placeholder="Delivery notes..."></textarea></div>

                <hr>
                <h6 class="fw-bold mb-3">Order Items</h6>
                <div class="item-row">
                    <input type="text" id="itemDesc" class="form-control" placeholder="Description">
                    <input type="text" id="itemQty" class="form-control" placeholder="Qty" style="max-width:80px;">
                    <select id="itemUom" class="form-select" style="max-width:120px;">
                        <option value="">UOM</option>
                        <?php foreach ($uoms as $u): ?>
                        <option value="<?php echo htmlspecialchars($u['PDESC']); ?>"><?php echo htmlspecialchars($u['PDESC']); ?></option>
                        <?php endforeach; ?>
                        <option value="PCS">PCS</option>
                        <option value="CTN">CTN</option>
                        <option value="SET">SET</option>
                        <option value="PKT">PKT</option>
                    </select>
                    <select id="itemInstall" class="form-select" style="max-width:140px;">
                        <option value="N">No Install</option>
                        <option value="Y">Install</option>
                    </select>
                    <button class="btn btn-sm btn-success" onclick="addItem();"><i class="fas fa-plus"></i></button>
                </div>
                <div class="item-list">
                    <table>
                        <tbody id="itemBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveOrder();"><i class="fas fa-check"></i> Save Order</button>
            </div>
        </div>
    </div>
</div>
<!-- View Order Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye"></i> <span id="viewTitle">Delivery Order</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody" style="padding:0;">
                <div style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewEditBtn"><i class="fas fa-pen"></i> Edit</button>
                <button type="button" class="btn btn-info text-white" onclick="printViewModal();"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
<?php if (!$viewOrder): ?>
var modal = null;
var viewModalEl = null;
var orderItems = [];
var locationData = <?php
    $locMap = [];
    $lr = $connect->query("SELECT * FROM `del_location` ORDER BY `NAME` ASC");
    if ($lr) { while ($row = $lr->fetch_assoc()) { $locMap[$row['NAME']] = $row; } }
    echo json_encode($locMap);
?>;

document.addEventListener('DOMContentLoaded', function() {
    modal = new bootstrap.Modal(document.getElementById('orderModal'));
    viewModalEl = new bootstrap.Modal(document.getElementById('viewModal'));
    $('#fCustomer').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Select Customer --',
        allowClear: true,
        dropdownParent: $('#orderModal'),
        width: '100%'
    }).on('change', function() { onCustomerChange(); });
    loadOrders();
    <?php if ($editId > 0): ?>
    openEditModal(<?php echo $editId; ?>);
    <?php endif; ?>
});

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function onCustomerChange() {
    var sel = document.getElementById('fCustomer');
    var opt = sel.options[sel.selectedIndex];
    var loc = opt.getAttribute('data-location') || '';
    var addr = opt.getAttribute('data-address') || '';
    document.getElementById('fLocation').value = loc;
    document.getElementById('fAddress').value = addr;
}

function addItem() {
    var desc = document.getElementById('itemDesc').value.trim();
    var qty = document.getElementById('itemQty').value.trim();
    var uom = document.getElementById('itemUom').value;
    var install = document.getElementById('itemInstall').value;
    if (desc === '') return;
    orderItems.push({ desc: desc, qty: qty, uom: uom, install: install });
    document.getElementById('itemDesc').value = '';
    document.getElementById('itemQty').value = '';
    document.getElementById('itemInstall').value = 'N';
    renderItems();
}

function removeItem(idx) { orderItems.splice(idx, 1); renderItems(); }

function renderItems() {
    var html = orderItems.map(function(item, i) {
        var installLabel = (item.install === 'Y') ? '<span style="color:#f59e0b;font-weight:600;">Yes</span>' : 'No';
        return '<tr><td>' + (i+1) + '</td><td>' + escHtml(item.desc) + '</td><td>' + escHtml(item.qty) + '</td><td>' + escHtml(item.uom) + '</td><td>' + installLabel + '</td><td><button class="btn-remove-item" onclick="removeItem(' + i + ')">X</button></td></tr>';
    }).join('');
    document.getElementById('itemBody').innerHTML = html || '<tr><td colspan="6" style="text-align:center;color:#999;">No items added</td></tr>';
}

function openCreateModal() {
    document.getElementById('editId').value = '';
    document.getElementById('fOrdno').value = '';
    document.getElementById('fOrdno').disabled = false;
    document.getElementById('fDeldate').value = '<?php echo date("Y-m-d"); ?>';
    $('#fCustomer').val('').trigger('change');
    document.getElementById('fLocation').value = '';
    document.getElementById('fAddress').value = '';
    document.getElementById('fRemark').value = '';
    orderItems = [];
    renderItems();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-file-invoice"></i> Add Delivery Order';
    modal.show();
}

function openEditModal(id) {
    $.ajax({
        type: 'POST', url: 'del_order_ajax.php', dataType: 'json',
        data: { action: 'get', id: id },
        success: function(data) {
            if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
            var o = data.order;
            document.getElementById('editId').value = o.ID;
            document.getElementById('fOrdno').value = o.ORDNO || '';
            document.getElementById('fOrdno').disabled = false;
            document.getElementById('fDeldate').value = o.DELDATE || '';
            $('#fCustomer').val(o.CUSTOMERCODE || '').trigger('change');
            document.getElementById('fLocation').value = o.LOCATION || '';
            document.getElementById('fRemark').value = o.REMARK || '';
            orderItems = (data.items || []).map(function(item) {
                return { desc: item.PDESC || '', qty: item.QTY || '', uom: item.UOM || '', install: item.INSTALL || 'N' };
            });
            renderItems();
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen"></i> Edit Delivery Order';
            modal.show();
        }
    });
}

function loadOrders() {
    $.ajax({
        type: 'POST', url: 'del_order_ajax.php', dataType: 'json',
        data: { action: 'list', start_date: document.getElementById('filterStart').value, end_date: document.getElementById('filterEnd').value, status: document.getElementById('filterStatus').value },
        success: function(data) {
            if (data.error) return;
            var orders = data.orders || [];
            var tbody = document.getElementById('dataBody');
            if (orders.length === 0) { tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-file-invoice" style="font-size:24px;display:block;margin-bottom:8px;"></i>No orders found</td></tr>'; return; }
            var statusMap = { '': 'Order', 'A': 'Assigned', 'D': 'Done', 'C': 'Completed' };
            var badgeMap = { '': 'badge-order', 'A': 'badge-assigned', 'D': 'badge-done', 'C': 'badge-completed' };
            tbody.innerHTML = orders.map(function(o, i) {
                return '<tr><td>' + (i+1) + '</td><td>' + escHtml(o.DELDATE||'') + '</td><td><strong>' + escHtml(o.ORDNO||'') + '</strong></td><td>' + escHtml(o.DRIVER||'-') + '</td><td>' + escHtml(o.CUSTOMER||'') + '</td><td>' + escHtml(o.CUST_ADDRESS||'') + '</td><td><span class="badge-status ' + (badgeMap[o.STATUS]||'') + '">' + (statusMap[o.STATUS]||o.STATUS) + '</span></td><td style="white-space:nowrap"><button class="btn-action btn-edit" onclick="openEditModal(' + o.ID + ')"><i class="fas fa-pen"></i></button> <button class="btn-action btn-view" onclick="openViewModal(' + o.ID + ')"><i class="fas fa-eye"></i></button> <button class="btn-action btn-delete" onclick="deleteOrder(' + o.ID + ',\'' + escHtml(o.ORDNO||'') + '\')"><i class="fas fa-trash"></i></button></td></tr>';
            }).join('');
        }
    });
}

function saveOrder() {
    var editId = document.getElementById('editId').value;
    var ordno = document.getElementById('fOrdno').value.trim();
    var deldate = document.getElementById('fDeldate').value;
    var customerCode = document.getElementById('fCustomer').value;
    if (ordno === '' || deldate === '' || customerCode === '') { Swal.fire({ icon: 'warning', text: 'Order No, Date and Customer are required.' }); return; }
    var sel = document.getElementById('fCustomer');
    var customerName = sel.options[sel.selectedIndex].getAttribute('data-name') || '';

    var payload = {
        action: editId ? 'update' : 'create',
        ordno: ordno, deldate: deldate, customercode: customerCode, customer: customerName,
        location: document.getElementById('fLocation').value, remark: document.getElementById('fRemark').value, items: orderItems
    };
    if (editId) { payload.id = editId; }

    $.ajax({
        type: 'POST', url: 'del_order_ajax.php', dataType: 'json', contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function(data) {
            if (data.success) { modal.hide(); Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { loadOrders(); }); }
            else { Swal.fire({ icon: 'error', text: data.error || 'Failed.' }); }
        }
    });
}

function deleteOrder(id, ordno) {
    Swal.fire({ title: 'Delete order?', text: 'Remove order "' + ordno + '"?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, Delete' }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({ type: 'POST', url: 'del_order_ajax.php', data: { action: 'delete', id: id }, dataType: 'json', success: function(data) {
                if (data.success) { Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { loadOrders(); }); }
                else { Swal.fire({ icon: 'error', text: data.error || 'Failed.' }); }
            }});
        }
    });
}

function openViewModal(id) {
    var body = document.getElementById('viewModalBody');
    body.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    viewModalEl.show();
    $.ajax({
        type: 'POST', url: 'del_order_ajax.php', dataType: 'json',
        data: { action: 'get', id: id },
        success: function(data) {
            if (data.error) { body.innerHTML = '<div style="padding:20px;color:#dc2626;">' + escHtml(data.error) + '</div>'; return; }
            var o = data.order;
            var items = data.items || [];
            var statusMap = { '': 'Order', 'A': 'Assigned', 'D': 'Done', 'C': 'Completed' };
            var purchaseDate = o.CREATED_AT ? o.CREATED_AT.substring(0, 10) : '-';
            var html = '<div style="padding:24px;">';
            html += '<div style="text-align:center;margin-bottom:20px;"><h4 style="font-family:Outfit,sans-serif;font-weight:700;margin:0;">DELIVERY ORDER</h4><p style="color:var(--text-muted);font-size:13px;margin:4px 0 0;">Order No: <strong>' + escHtml(o.ORDNO||'') + '</strong></p></div>';
            html += '<div class="do-info">';
            html += '<div><dt>Delivery Date</dt><dd>' + escHtml(o.DELDATE||'') + '</dd></div>';
            html += '<div><dt>Status</dt><dd>' + (statusMap[o.STATUS]||o.STATUS) + '</dd></div>';
            html += '<div><dt>Customer</dt><dd>' + escHtml(o.CUSTOMER||'') + '</dd></div>';
            html += '<div><dt>Driver</dt><dd>' + escHtml(o.DRIVER||'-') + '</dd></div>';
            html += '<div><dt>Address</dt><dd>' + escHtml(o.CUST_ADDRESS||'') + '</dd></div>';
            html += '<div><dt>Phone</dt><dd>' + escHtml(o.CUST_PHONE||'') + '</dd></div>';
            html += '<div><dt>Location</dt><dd>' + escHtml(o.LOCATION||'') + '</dd></div>';
            html += '<div><dt>Purchase Date</dt><dd>' + escHtml(purchaseDate) + '</dd></div>';
            if (o.REMARK) { html += '<div style="grid-column:1/-1"><dt>Remark</dt><dd>' + escHtml(o.REMARK) + '</dd></div>'; }
            html += '</div>';
            html += '<h6 style="font-weight:700;margin-bottom:8px;">Order Items</h6>';
            html += '<div class="do-items"><table><thead><tr><th>No</th><th>Description</th><th>Qty</th><th>UOM</th><th>Installation</th></tr></thead><tbody>';
            if (items.length === 0) {
                html += '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);">No items</td></tr>';
            } else {
                items.forEach(function(item, idx) {
                    var installLabel = (item.INSTALL === 'Y') ? '<span style="color:#f59e0b;font-weight:600;"><i class="fas fa-tools"></i> Yes</span>' : '-';
                    html += '<tr><td>' + (idx+1) + '</td><td>' + escHtml(item.PDESC||'') + '</td><td>' + escHtml(item.QTY||'') + '</td><td>' + escHtml(item.UOM||'') + '</td><td>' + installLabel + '</td></tr>';
                });
            }
            html += '</tbody></table></div>';
            if (data.hasSignature) {
                var safeOrdno = (o.ORDNO||'').replace(/[\/\\:*?"<>|]/g, '_');
                html += '<div style="margin-top:16px;"><h6 style="font-weight:700;margin-bottom:8px;">Customer Signature</h6>';
                html += '<img src="../staff/uploads/signatures/' + encodeURIComponent(safeOrdno) + '.png" alt="Signature" style="max-width:300px;border:1px solid #e5e7eb;border-radius:8px;"></div>';
            }
            html += '</div>';
            body.innerHTML = html;
            // Wire up edit button
            document.getElementById('viewEditBtn').onclick = function() { viewModalEl.hide(); openEditModal(id); };
        }
    });
}

function printViewModal() {
    var content = document.getElementById('viewModalBody').innerHTML;
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write('<html><head><title>Delivery Order</title>');
    w.document.write('<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">');
    w.document.write('<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">');
    w.document.write('<style>body{font-family:"DM Sans",sans-serif;padding:20px;color:#1a1a1a;} .do-info{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;font-size:13px;} .do-info dt{font-weight:700;color:#6b7280;} .do-items table{width:100%;border-collapse:collapse;font-size:13px;} .do-items th,.do-items td{border:1px solid #e5e7eb;padding:8px 12px;text-align:left;} .do-items th{background:#f9fafb;font-weight:600;}</style>');
    w.document.write('</head><body>');
    w.document.write(content);
    w.document.write('</body></html>');
    w.document.close();
    w.onload = function() { w.print(); };
}

renderItems();
<?php endif; ?>
</script>
</body>
</html>
