<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../staff/dbconnection.php');
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

    // Fetch items with product image
    $itemResult = $connect->query("SELECT poi.*, p.img1 AS product_image FROM `purchase_order_item` poi LEFT JOIN `PRODUCTS` p ON poi.barcode = p.barcode WHERE poi.po_id = $poId ORDER BY poi.id ASC");
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

/* Line item product cell with image */
.line-product { display: flex; align-items: center; gap: 10px; }
.line-product-img { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; background: #f3f4f6; flex-shrink: 0; }
.line-product-noimg { width: 36px; height: 36px; border-radius: 6px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #d1d5db; font-size: 14px; }
.line-product-name { font-weight: 500; font-size: 13px; }

/* New Product Modal */
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }
.img-preview-box { width: 100%; max-width: 200px; height: 150px; border: 2px dashed #d1d5db; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; transition: border-color 0.2s; position: relative; }
.img-preview-box:hover { border-color: var(--primary); }
.img-preview-box img { width: 100%; height: 100%; object-fit: cover; }
.img-preview-box .placeholder { text-align: center; color: var(--text-muted); font-size: 12px; }
.img-preview-box .placeholder i { font-size: 24px; display: block; margin-bottom: 6px; }

/* Product Picker Modal */
.product-picker-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1060; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); }
.product-picker-modal.active { display: flex; }
.product-picker-box { background: var(--surface); border-radius: var(--radius); box-shadow: 0 12px 48px rgba(0,0,0,0.2); width: 95%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; }
.product-picker-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.product-picker-header h5 { font-family: 'Outfit', sans-serif; font-weight: 700; margin: 0; font-size: 16px; }
.product-picker-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted); padding: 4px 8px; border-radius: 6px; transition: all 0.15s; }
.product-picker-close:hover { background: #f3f4f6; color: var(--text); }
.product-picker-toolbar { padding: 12px 20px; border-bottom: 1px solid #f3f4f6; display: flex; gap: 10px; align-items: center; }
.product-picker-search { flex: 1; position: relative; }
.product-picker-search input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.product-picker-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(200,16,46,0.1); }
.product-picker-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.product-picker-body { flex: 1; overflow-y: auto; padding: 0; }
.pp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.pp-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 11px; text-transform: uppercase; padding: 8px 12px; text-align: left; position: sticky; top: 0; z-index: 2; }
.pp-table tbody td { padding: 8px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.pp-table tbody tr { cursor: pointer; transition: background 0.15s; }
.pp-table tbody tr:hover { background: #fef3c7; }
.pp-table .pp-thumb { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; background: #f3f4f6; }
.pp-table .pp-thumb-empty { width: 36px; height: 36px; border-radius: 6px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 14px; }
.pp-table .pp-name { font-weight: 600; }
.pp-table .pp-barcode { font-size: 11px; color: var(--text-muted); }
.pp-table .pp-tag { background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 11px; color: var(--text-muted); }
.pp-qoh-in { color: #16a34a; font-weight: 600; }
.pp-qoh-out { color: #dc2626; font-weight: 600; }
.pp-empty { padding: 40px; text-align: center; color: var(--text-muted); font-size: 13px; }
.pp-empty i { font-size: 24px; display: block; margin-bottom: 8px; }
.pp-loading { padding: 30px; text-align: center; color: var(--text-muted); font-size: 13px; }
.product-picker-footer { padding: 12px 20px; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.pp-pagination-info { font-size: 12px; color: var(--text-muted); }
.pp-pagination-btns { display: flex; gap: 4px; }
.pp-pagination-btns button { padding: 5px 10px; border: 1px solid #d1d5db; background: #fff; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); color: var(--text); }
.pp-pagination-btns button:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); }
.pp-pagination-btns button.active { background: var(--primary); color: #fff; border-color: var(--primary); }
.pp-pagination-btns button:disabled { opacity: 0.4; cursor: default; }
.pp-create-btn { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; padding: 7px 14px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px; }
.pp-create-btn:hover { background: #dcfce7; border-color: #bbf7d0; }

@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .product-picker-box { width: 98%; max-height: 95vh; }
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
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
            <div class="card-title" style="margin-bottom:0;">Line Items</div>
            <?php if ($isDraft): ?>
            <button type="button" class="btn-primary-action" onclick="openProductPicker();" style="padding:8px 18px;font-size:13px;">
                <i class="fas fa-plus"></i> Add Product
            </button>
            <?php endif; ?>
        </div>
        <div style="overflow-x:auto;">
            <table class="items-table" id="itemsTable">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Product</th>
                        <th style="width:100px">Qty Ordered</th>
                        <th style="width:100px">Qty Received</th>
                        <th style="width:80px">UOM</th>
                        <?php if ($isDraft): ?><th style="width:50px"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="itemsBody">
                    <?php if (!$isNew): ?>
                    <?php foreach ($items as $idx => $item): ?>
                    <tr data-item-id="<?php echo $item['id']; ?>" data-barcode="<?php echo htmlspecialchars($item['barcode']); ?>">
                        <td><?php echo $idx + 1; ?></td>
                        <td>
                            <div class="line-product">
                                <?php if (!empty($item['product_image'])): ?>
                                <img class="line-product-img" src="../img/<?php echo htmlspecialchars($item['product_image']); ?>" alt="">
                                <?php else: ?>
                                <div class="line-product-noimg"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="line-product-name"><?php echo htmlspecialchars($item['product_desc']); ?></div>
                                    <?php if (!empty($item['barcode'])): ?>
                                    <div style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($item['barcode']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <input type="hidden" class="item-barcode" value="<?php echo htmlspecialchars($item['barcode']); ?>">
                            <input type="hidden" class="item-desc" value="<?php echo htmlspecialchars($item['product_desc']); ?>">
                        </td>
                        <td><?php if ($isDraft): ?><input type="number" class="item-qty" value="<?php echo $item['qty_ordered']; ?>" min="0.01" step="0.01"><?php else: echo $item['qty_ordered']; endif; ?></td>
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
        <div id="emptyHint" style="text-align:center;padding:24px;color:var(--text-muted);font-size:13px;<?php echo count($items) > 0 ? 'display:none;' : ''; ?>">
            <i class="fas fa-box-open" style="font-size:20px;display:block;margin-bottom:8px;"></i>
            Click "Add Product" to add products to this order.
        </div>
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

<!-- Product Picker Modal -->
<div class="product-picker-modal" id="productPickerModal">
    <div class="product-picker-box">
        <div class="product-picker-header">
            <h5><i class="fas fa-boxes-stacked" style="color:var(--primary);margin-right:8px;"></i>Select Product</h5>
            <button type="button" class="product-picker-close" onclick="closeProductPicker();">&times;</button>
        </div>
        <div class="product-picker-toolbar">
            <div class="product-picker-search">
                <i class="fas fa-search"></i>
                <input type="text" id="ppSearchInput" placeholder="Search by product name or barcode..." autocomplete="off">
            </div>
            <button type="button" class="pp-create-btn" onclick="openNewProductModal('');"><i class="fas fa-plus-circle"></i> New Product</button>
        </div>
        <div class="product-picker-body">
            <table class="pp-table">
                <thead>
                    <tr>
                        <th style="width:50px">Img</th>
                        <th>Product</th>
                        <th>Barcode</th>
                        <th>Category</th>
                        <th style="width:70px">QOH</th>
                        <th style="width:70px">UOM</th>
                    </tr>
                </thead>
                <tbody id="ppTableBody">
                    <tr><td colspan="6" class="pp-loading"><i class="fas fa-spinner fa-spin"></i> Loading products...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="product-picker-footer">
            <div class="pp-pagination-info" id="ppPaginationInfo"></div>
            <div class="pp-pagination-btns" id="ppPaginationBtns"></div>
        </div>
    </div>
</div>

<!-- New Product Modal -->
<div class="modal fade" id="newProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" id="npName" class="form-control" placeholder="Enter product name">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">UOM</label>
                        <input type="text" id="npUom" class="form-control" placeholder="e.g. PCS, KG, CTN">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Barcode <span style="font-weight:normal;color:var(--text-muted);">(optional)</span></label>
                        <input type="text" id="npBarcode" class="form-control" placeholder="Auto-generated if empty">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Image <span style="font-weight:normal;color:var(--text-muted);">(optional)</span></label>
                    <div class="img-preview-box" id="npImgBox" onclick="document.getElementById('npImageFile').click();">
                        <div class="placeholder" id="npImgPlaceholder">
                            <i class="fas fa-camera"></i>
                            Click to upload
                        </div>
                        <img id="npImgPreview" src="" alt="" style="display:none;">
                    </div>
                    <input type="file" id="npImageFile" accept="image/*" style="display:none;" onchange="previewNewProductImage(this);">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveNewProduct();"><i class="fas fa-check"></i> Create & Add to PO</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var poId = <?php echo $poId; ?>;
var isDraft = <?php echo $isDraft ? 'true' : 'false'; ?>;
var newProductModal = null;

document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('newProductModal');
    if (modalEl) newProductModal = new bootstrap.Modal(modalEl);
});

// ===================== PRODUCT PICKER MODAL =====================

var ppSearchTimer = null;
var ppSearchXhr = null;
var ppCurrentPage = 1;

function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function openProductPicker() {
    document.getElementById('productPickerModal').classList.add('active');
    document.getElementById('ppSearchInput').value = '';
    ppCurrentPage = 1;
    fetchPickerProducts(1);
    setTimeout(function() {
        document.getElementById('ppSearchInput').focus();
    }, 100);
}

function closeProductPicker() {
    document.getElementById('productPickerModal').classList.remove('active');
    if (ppSearchXhr) { ppSearchXhr.abort(); ppSearchXhr = null; }
}

function fetchPickerProducts(page) {
    ppCurrentPage = page;
    var q = document.getElementById('ppSearchInput').value.trim();
    var tbody = document.getElementById('ppTableBody');

    tbody.innerHTML = '<tr><td colspan="6" class="pp-loading"><i class="fas fa-spinner fa-spin"></i> Loading products...</td></tr>';

    if (ppSearchXhr) { ppSearchXhr.abort(); ppSearchXhr = null; }

    ppSearchXhr = $.ajax({
        type: 'POST', url: 'po_ajax.php',
        data: { action: 'search_products_paginated', q: q, page: page },
        dataType: 'json',
        success: function(data) {
            ppSearchXhr = null;
            renderPickerTable(data);
            renderPickerPagination(data);
        },
        error: function(xhr, status) {
            ppSearchXhr = null;
            if (status !== 'abort') {
                tbody.innerHTML = '<tr><td colspan="6" class="pp-empty"><i class="fas fa-exclamation-triangle"></i>Failed to load products</td></tr>';
            }
        }
    });
}

function renderPickerTable(data) {
    var tbody = document.getElementById('ppTableBody');
    var products = data.products || [];

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="pp-empty"><i class="fas fa-search"></i>No products found</td></tr>';
        return;
    }

    var html = '';
    for (var i = 0; i < products.length; i++) {
        var p = products[i];
        var pJson = escHtml(JSON.stringify(p));

        html += '<tr onclick=\'selectPickerProduct(' + JSON.stringify(p).replace(/'/g, "&#39;") + ')\'>';

        // Image
        if (p.image) {
            html += '<td><img class="pp-thumb" src="../img/' + escHtml(p.image) + '" alt="" loading="lazy"></td>';
        } else {
            html += '<td><div class="pp-thumb-empty"><i class="fas fa-image"></i></div></td>';
        }

        // Product name
        html += '<td><div class="pp-name">' + escHtml(p.name) + '</div></td>';

        // Barcode
        html += '<td><span class="pp-barcode">' + escHtml(p.barcode || '-') + '</span></td>';

        // Category
        html += '<td>' + (p.category_name ? '<span class="pp-tag">' + escHtml(p.category_name) + '</span>' : '<span style="color:#d1d5db;">-</span>') + '</td>';

        // QOH
        var qohVal = p.qoh || 0;
        html += '<td><span class="' + (qohVal > 0 ? 'pp-qoh-in' : 'pp-qoh-out') + '">' + qohVal + '</span></td>';

        // UOM
        html += '<td>' + escHtml(p.uom || '-') + '</td>';

        html += '</tr>';
    }
    tbody.innerHTML = html;
}

function renderPickerPagination(data) {
    var info = document.getElementById('ppPaginationInfo');
    var btns = document.getElementById('ppPaginationBtns');

    if (data.total === 0) {
        info.textContent = '';
        btns.innerHTML = '';
        return;
    }

    var start = (data.page - 1) * data.per_page + 1;
    var end = Math.min(data.page * data.per_page, data.total);
    info.textContent = 'Showing ' + start + '-' + end + ' of ' + data.total + ' products';

    if (data.pages <= 1) {
        btns.innerHTML = '';
        return;
    }

    var html = '';
    html += '<button ' + (data.page <= 1 ? 'disabled' : '') + ' onclick="fetchPickerProducts(' + (data.page - 1) + ');">&laquo;</button>';

    var startPage = Math.max(1, data.page - 2);
    var endPage = Math.min(data.pages, data.page + 2);
    if (startPage > 1) {
        html += '<button onclick="fetchPickerProducts(1);">1</button>';
        if (startPage > 2) html += '<button disabled>...</button>';
    }
    for (var i = startPage; i <= endPage; i++) {
        html += '<button class="' + (i === data.page ? 'active' : '') + '" onclick="fetchPickerProducts(' + i + ');">' + i + '</button>';
    }
    if (endPage < data.pages) {
        if (endPage < data.pages - 1) html += '<button disabled>...</button>';
        html += '<button onclick="fetchPickerProducts(' + data.pages + ');">' + data.pages + '</button>';
    }
    html += '<button ' + (data.page >= data.pages ? 'disabled' : '') + ' onclick="fetchPickerProducts(' + (data.page + 1) + ');">&raquo;</button>';
    btns.innerHTML = html;
}

function selectPickerProduct(product) {
    addProductLine(product);
    closeProductPicker();
}

// Search input event
var ppInput = document.getElementById('ppSearchInput');
if (ppInput) {
    ppInput.addEventListener('input', function() {
        clearTimeout(ppSearchTimer);
        ppSearchTimer = setTimeout(function() { fetchPickerProducts(1); }, 300);
    });
    ppInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeProductPicker();
    });
}

// Close modal on backdrop click
var ppModal = document.getElementById('productPickerModal');
if (ppModal) {
    ppModal.addEventListener('click', function(e) {
        if (e.target === ppModal) closeProductPicker();
    });
}

// ===================== ADD PRODUCT TO LINE ITEMS =====================

function addProductLine(product) {
    // Check if product already in list
    var existing = document.querySelector('#itemsBody tr[data-barcode="' + product.barcode + '"]');
    if (existing) {
        var qtyInput = existing.querySelector('.item-qty');
        if (qtyInput) {
            qtyInput.value = (parseFloat(qtyInput.value) || 0) + 1;
            qtyInput.focus();
            qtyInput.select();
        }
        return;
    }

    var tbody = document.getElementById('itemsBody');
    var rowCount = tbody.rows.length + 1;
    var tr = document.createElement('tr');
    tr.setAttribute('data-barcode', product.barcode || '');

    var imgHtml;
    if (product.image) {
        imgHtml = '<img class="line-product-img" src="../img/' + escHtml(product.image) + '" alt="">';
    } else {
        imgHtml = '<div class="line-product-noimg"><i class="fas fa-image"></i></div>';
    }

    tr.innerHTML = '<td>' + rowCount + '</td>' +
        '<td><div class="line-product">' + imgHtml + '<div><div class="line-product-name">' + escHtml(product.name) + '</div>' +
            (product.barcode ? '<div style="font-size:11px;color:var(--text-muted);">' + escHtml(product.barcode) + '</div>' : '') +
        '</div></div><input type="hidden" class="item-barcode" value="' + escHtml(product.barcode || '') + '"><input type="hidden" class="item-desc" value="' + escHtml(product.name) + '"></td>' +
        '<td><input type="number" class="item-qty" value="1" min="0.01" step="0.01"></td>' +
        '<td>0</td>' +
        '<td><input type="text" class="item-uom" value="' + escHtml(product.uom || '') + '"></td>' +
        '<td><button type="button" class="btn-sm-action btn-remove" onclick="removeRow(this);"><i class="fas fa-times"></i></button></td>';
    tbody.appendChild(tr);

    // Focus qty field
    tr.querySelector('.item-qty').focus();
    tr.querySelector('.item-qty').select();

    // Hide empty hint
    var hint = document.getElementById('emptyHint');
    if (hint) hint.style.display = 'none';
}

// ===================== NEW PRODUCT MODAL =====================

function openNewProductModal(prefillName) {
    closeProductPicker();
    document.getElementById('npName').value = prefillName || '';
    document.getElementById('npUom').value = '';
    document.getElementById('npBarcode').value = '';
    document.getElementById('npImageFile').value = '';
    document.getElementById('npImgPreview').style.display = 'none';
    document.getElementById('npImgPlaceholder').style.display = '';
    newProductModal.show();
    setTimeout(function() {
        document.getElementById('npName').focus();
    }, 300);
}

function previewNewProductImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('npImgPreview');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('npImgPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function saveNewProduct() {
    var name = document.getElementById('npName').value.trim();
    if (name === '') {
        Swal.fire({ icon: 'warning', text: 'Product name is required.' });
        return;
    }

    var formData = new FormData();
    formData.append('action', 'quick_create_product');
    formData.append('name', name);
    formData.append('uom', document.getElementById('npUom').value.trim());
    formData.append('barcode', document.getElementById('npBarcode').value.trim());

    var fileInput = document.getElementById('npImageFile');
    if (fileInput.files && fileInput.files[0]) {
        formData.append('product_image', fileInput.files[0]);
    }

    $.ajax({
        type: 'POST', url: 'po_ajax.php', data: formData,
        processData: false, contentType: false, dataType: 'json',
        success: function(data) {
            if (data.success && data.product) {
                newProductModal.hide();
                addProductLine(data.product);
                Swal.fire({ icon: 'success', text: 'Product "' + data.product.name + '" created and added.', timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Failed to create product.' });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', text: 'Network error. Please try again.' });
        }
    });
}

// ===================== LINE ITEM MANAGEMENT =====================

function removeRow(btn) {
    btn.closest('tr').remove();
    renumber();
    // Show empty hint if no rows
    var hint = document.getElementById('emptyHint');
    if (hint && document.querySelectorAll('#itemsBody tr').length === 0) {
        hint.style.display = '';
    }
}

function renumber() {
    var rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach(function(row, i) { row.cells[0].textContent = i + 1; });
}

function collectItems() {
    var items = [];
    document.querySelectorAll('#itemsBody tr').forEach(function(tr) {
        var barcodeInput = tr.querySelector('.item-barcode');
        var descInput = tr.querySelector('.item-desc');
        var barcode = barcodeInput ? barcodeInput.value.trim() : (tr.getAttribute('data-barcode') || '');
        var desc = descInput ? descInput.value.trim() : '';
        var qty = parseFloat(tr.querySelector('.item-qty')?.value || tr.cells[2].textContent) || 0;
        var uom = (tr.querySelector('.item-uom')?.value || '').trim();
        var itemId = tr.getAttribute('data-item-id') || '';
        if (desc !== '' && qty > 0) {
            items.push({ id: itemId, barcode: barcode, product_desc: desc, qty_ordered: qty, uom: uom });
        }
    });
    return items;
}

// ===================== SAVE / APPROVE / CANCEL =====================

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
</script>
</body>
</html>
