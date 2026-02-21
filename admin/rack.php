<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include('../dbconnection.php');
$connect->set_charset("utf8mb4");

// Fetch distinct racks with product counts
$racks = [];
$result = $connect->query("
    SELECT COALESCE(NULLIF(rack, ''), 'Unassigned') AS rack_name,
           COUNT(*) AS product_count,
           SUM(COALESCE(qoh, 0)) AS total_qoh
    FROM PRODUCTS WHERE checked = 'Y'
    GROUP BY COALESCE(NULLIF(rack, ''), 'Unassigned')
    ORDER BY rack_name ASC
");
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $racks[] = $r;
    }
}

$currentPage = 'rack';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rack Management</title>
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
.btn-add { background: var(--primary); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; transition: background var(--transition); display: inline-flex; align-items: center; gap: 6px; }
.btn-add:hover { background: var(--primary-dark); }
.table-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow-md); padding: 20px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.search-box { position: relative; flex: 1; max-width: 320px; }
.search-box input { width: 100%; padding: 9px 14px 9px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; outline: none; transition: border-color var(--transition); }
.search-box input:focus { border-color: var(--primary); }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
.item-count { font-size: 13px; color: var(--text-muted); }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-active { background: #dcfce7; color: #16a34a; }
.badge-inactive { background: #fee2e2; color: #dc2626; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* Rack Grid */
.rack-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.rack-card {
    background: var(--surface);
    border: 2px solid #e5e7eb;
    border-radius: var(--radius);
    padding: 18px;
    cursor: pointer;
    transition: all var(--transition);
    position: relative;
}
.rack-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.rack-card.selected {
    border-color: var(--primary);
    background: #fef2f2;
    box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.15);
}
.rack-card .rack-name {
    font-family: 'Outfit', sans-serif;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text);
}
.rack-card .rack-name i {
    color: var(--primary);
    font-size: 18px;
}
.rack-card .rack-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
}
.rack-card .rack-stat {
    display: flex;
    flex-direction: column;
}
.rack-card .rack-stat .stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.2;
}
.rack-card .rack-stat .stat-label {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 600;
}
.rack-card .btn-rename {
    background: none;
    border: 1px solid #d1d5db;
    color: var(--text-muted);
    padding: 4px 10px;
    border-radius: 6px;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.rack-card .btn-rename:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: #fef2f2;
}

/* Detail card */
#detailCard {
    display: none;
}
#detailCard.visible {
    display: block;
}
.detail-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}
.detail-header h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.detail-header h2 i {
    color: var(--primary);
}
.btn-assign {
    background: #16a34a;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-assign:hover {
    background: #15803d;
}
.btn-move {
    background: #f59e0b;
    color: #fff;
}
.btn-move:hover {
    background: #d97706;
}

/* Assign modal search results */
.assign-search-box {
    position: relative;
    margin-bottom: 16px;
}
.assign-search-box input {
    width: 100%;
    padding: 10px 14px 10px 38px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    outline: none;
    transition: border-color var(--transition);
}
.assign-search-box input:focus {
    border-color: var(--primary);
}
.assign-search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 14px;
}
.assign-results {
    max-height: 320px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}
.assign-results .assign-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid #f3f4f6;
    transition: background var(--transition);
}
.assign-results .assign-item:last-child {
    border-bottom: none;
}
.assign-results .assign-item:hover {
    background: #f9fafb;
}
.assign-results .assign-item .item-info {
    flex: 1;
    min-width: 0;
}
.assign-results .assign-item .item-barcode {
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 600;
}
.assign-results .assign-item .item-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.assign-results .assign-item .item-rack {
    font-size: 11px;
    color: var(--text-muted);
}
.assign-results .assign-item .btn-assign-item {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 5px 12px;
    border-radius: 6px;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: background var(--transition);
    white-space: nowrap;
    margin-left: 12px;
}
.assign-results .assign-item .btn-assign-item:hover {
    background: var(--primary-dark);
}
.assign-results .no-results-msg {
    text-align: center;
    padding: 24px;
    color: var(--text-muted);
    font-size: 13px;
}
.loading-spinner {
    text-align: center;
    padding: 30px;
    color: var(--text-muted);
}
.loading-spinner i {
    font-size: 24px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .search-box { max-width: 100%; }
    .btn-action { padding: 4px 8px; font-size: 11px; }
    .rack-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
    .rack-card { padding: 14px; }
    .detail-header { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-warehouse" style="color:var(--primary);margin-right:8px;"></i>Rack Management</h1>
        <div class="item-count"><?php echo count($racks); ?> rack(s)</div>
    </div>

    <!-- Rack Grid -->
    <div class="rack-grid" id="rackGrid">
        <?php if (count($racks) === 0): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:40px; color:var(--text-muted);">
                <i class="fas fa-warehouse" style="font-size:32px; margin-bottom:12px; display:block;"></i>
                No racks found
            </div>
        <?php else: ?>
            <?php foreach ($racks as $rack): ?>
            <div class="rack-card" id="rack-<?php echo htmlspecialchars(md5($rack['rack_name'])); ?>"
                 onclick="loadRackProducts('<?php echo htmlspecialchars($rack['rack_name'], ENT_QUOTES); ?>', this);">
                <div class="rack-name">
                    <i class="fas fa-warehouse"></i>
                    <?php echo htmlspecialchars($rack['rack_name']); ?>
                </div>
                <div class="rack-stats">
                    <div class="rack-stat">
                        <span class="stat-value"><?php echo (int)$rack['product_count']; ?></span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="rack-stat">
                        <span class="stat-value"><?php echo (int)$rack['total_qoh']; ?></span>
                        <span class="stat-label">Total QOH</span>
                    </div>
                </div>
                <?php if ($rack['rack_name'] !== 'Unassigned'): ?>
                <button class="btn-rename" onclick="event.stopPropagation(); renameRack('<?php echo htmlspecialchars($rack['rack_name'], ENT_QUOTES); ?>');">
                    <i class="fas fa-pen"></i> Rename
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Detail Card -->
    <div class="table-card" id="detailCard">
        <div class="detail-header">
            <h2><i class="fas fa-boxes-stacked"></i> <span id="detailTitle">Rack Products</span></h2>
            <button class="btn-assign" onclick="openAssignModal();">
                <i class="fas fa-plus"></i> Assign Product
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Barcode</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>QOH</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                    <tr class="no-results"><td colspan="6">Select a rack to view products</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen"></i> Rename Rack</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Old Name</label>
                    <input type="text" id="renameOldName" class="form-control" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Name <span class="text-danger">*</span></label>
                    <input type="text" id="renameNewName" class="form-control" placeholder="Enter new rack name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="doRename();"><i class="fas fa-check"></i> Rename</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Product Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Assign Product to <span id="assignRackLabel"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="assign-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="assignSearchInput" placeholder="Search by barcode or product name..." oninput="searchProducts();">
                </div>
                <div class="assign-results" id="assignResults">
                    <div class="no-results-msg">Type to search for products</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var renameModal = null;
var assignModal = null;
var currentRack = '';
var currentCardEl = null;
var searchTimer = null;

document.addEventListener('DOMContentLoaded', function() {
    renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
    assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
});

// Load products for a specific rack
function loadRackProducts(rackName, cardEl) {
    // Update selected state
    document.querySelectorAll('.rack-card').forEach(function(c) {
        c.classList.remove('selected');
    });
    if (cardEl) {
        cardEl.classList.add('selected');
    }

    currentRack = rackName;
    currentCardEl = cardEl;

    // Show detail card
    var detailCard = document.getElementById('detailCard');
    detailCard.classList.add('visible');

    // Update title
    document.getElementById('detailTitle').textContent = rackName + ' Products';

    // Show loading
    document.getElementById('detailBody').innerHTML = '<tr><td colspan="6"><div class="loading-spinner"><i class="fas fa-spinner"></i><div>Loading products...</div></div></td></tr>';

    $.ajax({
        type: 'POST',
        url: 'rack_ajax.php',
        data: { action: 'products', rack: rackName },
        dataType: 'json',
        success: function(data) {
            var tbody = document.getElementById('detailBody');
            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr class="no-results"><td colspan="6"><i class="fas fa-box-open" style="font-size:24px;margin-bottom:8px;display:block;"></i>No products in this rack</td></tr>';
                return;
            }

            var html = '';
            for (var i = 0; i < data.length; i++) {
                var p = data[i];
                html += '<tr>';
                html += '<td>' + (i + 1) + '</td>';
                html += '<td><strong>' + escapeHtml(p.barcode || p.code || '') + '</strong></td>';
                html += '<td>' + escapeHtml(p.name || '') + '</td>';
                html += '<td>' + escapeHtml(p.cat || '') + '</td>';
                html += '<td>' + parseInt(p.qoh || 0) + '</td>';
                html += '<td style="white-space:nowrap">';
                html += '<button class="btn-action btn-move" onclick="changeRack(' + parseInt(p.id) + ', \'' + escapeHtml(p.name || '').replace(/'/g, "\\'") + '\');"><i class="fas fa-arrows-alt"></i> Move</button>';
                html += '</td>';
                html += '</tr>';
            }
            tbody.innerHTML = html;
        },
        error: function() {
            document.getElementById('detailBody').innerHTML = '<tr class="no-results"><td colspan="6">Failed to load products</td></tr>';
        }
    });

    // Scroll to detail card
    setTimeout(function() {
        detailCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

// Rename rack
function renameRack(oldName) {
    document.getElementById('renameOldName').value = oldName;
    document.getElementById('renameNewName').value = '';
    renameModal.show();
}

function doRename() {
    var oldName = document.getElementById('renameOldName').value.trim();
    var newName = document.getElementById('renameNewName').value.trim();

    if (newName === '') {
        Swal.fire({ icon: 'warning', text: 'Please enter a new rack name.' });
        return;
    }

    if (newName === oldName) {
        Swal.fire({ icon: 'warning', text: 'New name is the same as the old name.' });
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'rack_ajax.php',
        data: { action: 'rename', old_rack: oldName, new_rack: newName },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                renameModal.hide();
                Swal.fire({
                    icon: 'success',
                    text: data.success,
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', text: 'Failed to rename rack.' });
        }
    });
}

// Change rack for a single product (move)
function changeRack(productId, productName) {
    Swal.fire({
        title: 'Move Product',
        text: 'Enter new rack for "' + productName + '":',
        input: 'text',
        inputPlaceholder: 'New rack name (leave empty to unassign)',
        showCancelButton: true,
        confirmButtonColor: '#C8102E',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Move',
        inputValidator: function(value) {
            // Allow empty to unassign
            return null;
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            var newRack = (result.value || '').trim();

            $.ajax({
                type: 'POST',
                url: 'rack_ajax.php',
                data: { action: 'assign', product_id: productId, rack: newRack },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            text: data.success,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', text: 'Failed to move product.' });
                }
            });
        }
    });
}

// Assign product modal
function openAssignModal() {
    if (!currentRack) {
        Swal.fire({ icon: 'warning', text: 'Please select a rack first.' });
        return;
    }
    document.getElementById('assignRackLabel').textContent = '"' + currentRack + '"';
    document.getElementById('assignSearchInput').value = '';
    document.getElementById('assignResults').innerHTML = '<div class="no-results-msg">Type to search for products</div>';
    assignModal.show();

    setTimeout(function() {
        document.getElementById('assignSearchInput').focus();
    }, 300);
}

// Search products for assignment (debounced)
function searchProducts() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
        var q = document.getElementById('assignSearchInput').value.trim();
        var resultsDiv = document.getElementById('assignResults');

        if (q === '') {
            resultsDiv.innerHTML = '<div class="no-results-msg">Type to search for products</div>';
            return;
        }

        if (q.length < 1) {
            return;
        }

        resultsDiv.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner"></i></div>';

        $.ajax({
            type: 'POST',
            url: 'rack_ajax.php',
            data: { action: 'search_products', q: q },
            dataType: 'json',
            success: function(data) {
                if (!data || data.length === 0) {
                    resultsDiv.innerHTML = '<div class="no-results-msg"><i class="fas fa-search" style="margin-right:6px;"></i>No products found</div>';
                    return;
                }

                var html = '';
                for (var i = 0; i < data.length; i++) {
                    var p = data[i];
                    var rackLabel = p.rack ? escapeHtml(p.rack) : 'Unassigned';
                    html += '<div class="assign-item">';
                    html += '<div class="item-info">';
                    html += '<div class="item-barcode">' + escapeHtml(p.barcode || '') + '</div>';
                    html += '<div class="item-name">' + escapeHtml(p.name || '') + '</div>';
                    html += '<div class="item-rack"><i class="fas fa-warehouse" style="margin-right:4px;font-size:10px;"></i>Current: ' + rackLabel + '</div>';
                    html += '</div>';
                    html += '<button class="btn-assign-item" onclick="assignToRack(' + parseInt(p.id) + ');"><i class="fas fa-plus"></i> Assign</button>';
                    html += '</div>';
                }
                resultsDiv.innerHTML = html;
            },
            error: function() {
                resultsDiv.innerHTML = '<div class="no-results-msg">Search failed. Please try again.</div>';
            }
        });
    }, 300);
}

// Assign a product to the current rack
function assignToRack(productId) {
    if (!currentRack || currentRack === 'Unassigned') {
        // If assigning to "Unassigned", set rack to empty
        var rackValue = (currentRack === 'Unassigned') ? '' : currentRack;
    } else {
        var rackValue = currentRack;
    }

    $.ajax({
        type: 'POST',
        url: 'rack_ajax.php',
        data: { action: 'assign', product_id: productId, rack: rackValue },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    text: data.success,
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    assignModal.hide();
                    location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', text: 'Failed to assign product.' });
        }
    });
}

// Utility: escape HTML
function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Focus new name input when rename modal opens
document.getElementById('renameModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('renameNewName').focus();
});
</script>
</body>
</html>
