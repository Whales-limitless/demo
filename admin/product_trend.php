<?php
session_start();
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

$currentPage = 'product_trend';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Trends</title>
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
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th { background: var(--text); color: #fff; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; padding: 10px 12px; white-space: nowrap; text-align: left; }
.data-table tbody td { padding: 10px 12px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.data-table tbody tr:hover { background: #f9fafb; }
.data-table tbody tr.no-results td { text-align: center; padding: 40px; color: var(--text-muted); }
.badge-status { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-active { background: #dcfce7; color: #16a34a; }
.badge-inactive { background: #f3f4f6; color: #6b7280; }
.btn-action { padding: 5px 12px; border: none; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition); display: inline-block; margin: 1px; color: #fff; }
.btn-edit { background: #3b82f6; } .btn-edit:hover { background: #2563eb; }
.btn-delete { background: #ef4444; } .btn-delete:hover { background: #dc2626; }
.btn-activate { background: #16a34a; } .btn-activate:hover { background: #15803d; }
.btn-preview { background: #8b5cf6; } .btn-preview:hover { background: #7c3aed; }
.modal-content { border-radius: var(--radius); border: none; box-shadow: var(--shadow-md); }
.modal-header { border-bottom: 1px solid #e5e7eb; }
.modal-header .modal-title { font-family: 'Outfit', sans-serif; font-weight: 700; }
.modal-footer { border-top: 1px solid #e5e7eb; }

/* Threshold colors */
.dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
.dot-green { background: #16a34a; }
.dot-yellow { background: #eab308; }
.dot-red { background: #ef4444; }
.dot-black { background: #1a1a1a; }

/* Threshold legend */
.threshold-legend { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 12px; font-size: 13px; }
.threshold-legend .legend-item { display: flex; align-items: center; gap: 4px; }

/* Preview stats */
.preview-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
.stat-card { background: var(--bg); border-radius: 10px; padding: 16px; text-align: center; }
.stat-card .stat-value { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; line-height: 1.2; }
.stat-card .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.03em; }
.stat-green .stat-value { color: #16a34a; }
.stat-yellow .stat-value { color: #eab308; }
.stat-red .stat-value { color: #ef4444; }
.stat-black .stat-value { color: #1a1a1a; }

/* Top movers */
.top-movers { max-height: 240px; overflow-y: auto; }
.top-movers .mover-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
.top-movers .mover-item:last-child { border-bottom: none; }
.top-movers .mover-rank { font-weight: 700; color: var(--text-muted); width: 24px; }
.top-movers .mover-name { flex: 1; font-weight: 600; }
.top-movers .mover-qty { font-weight: 700; color: var(--primary); }

/* Table loading */
.table-loading { text-align: center; padding: 40px; color: var(--text-muted); }
.table-loading i { font-size: 24px; margin-bottom: 8px; display: block; }

@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .table-card { padding: 12px; }
    .preview-stats { grid-template-columns: repeat(2, 1fr); }
    .btn-action { padding: 4px 8px; font-size: 11px; }
}
</style>
</head>
<body>

<?php include('nav.php'); ?>

<div class="page-content">
    <div class="page-header">
        <h1><i class="fas fa-chart-line" style="color:var(--primary);margin-right:8px;"></i>Product Trends</h1>
        <button class="btn-add" onclick="openCreateModal();">
            <i class="fas fa-plus"></i> New Trend Config
        </button>
    </div>

    <div class="threshold-legend">
        <div class="legend-item"><span class="dot dot-green"></span> Hot Seller (Popular)</div>
        <div class="legend-item"><span class="dot dot-yellow"></span> Moderate Demand</div>
        <div class="legend-item"><span class="dot dot-red"></span> Slow Mover</div>
        <div class="legend-item"><span class="dot dot-black"></span> No Orders (Dead Stock)</div>
    </div>

    <div class="table-card">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th>Name</th>
                        <th>Date Range</th>
                        <th>Thresholds</th>
                        <th>Status</th>
                        <th style="width:1%">Action</th>
                    </tr>
                </thead>
                <tbody id="dataBody">
                    <tr class="no-results"><td colspan="6" class="table-loading"><i class="fas fa-spinner fa-spin"></i>Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="table-card" id="previewCard" style="display:none;margin-top:20px;">
        <h5 style="font-family:'Outfit',sans-serif;font-weight:700;margin-bottom:16px;">
            <i class="fas fa-chart-pie" style="color:var(--primary);margin-right:6px;"></i>
            Trend Preview: <span id="previewTitle"></span>
        </h5>
        <div class="preview-stats" id="previewStats"></div>
        <h6 style="font-weight:700;margin-bottom:8px;">Top 10 Movers</h6>
        <div class="top-movers" id="topMovers"></div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="trendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-chart-line"></i> New Trend Config</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Config Name <span class="text-danger">*</span></label>
                    <input type="text" id="fName" class="form-control" placeholder="e.g. Jan-Feb 2026 Trend">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Date From <span class="text-danger">*</span></label>
                        <input type="date" id="fDateFrom" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Date To <span class="text-danger">*</span></label>
                        <input type="date" id="fDateTo" class="form-control">
                    </div>
                </div>
                <hr>
                <p class="text-muted" style="font-size:12px;margin-bottom:12px;">Set order quantity thresholds for indicator colors. The system will sum all order quantities per product within the date range.</p>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold"><span class="dot dot-green"></span> Green (>=)</label>
                        <input type="number" id="fGreenMin" class="form-control" min="1" value="50" placeholder="50">
                        <small class="text-muted">Hot seller threshold</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold"><span class="dot dot-yellow"></span> Yellow (>=)</label>
                        <input type="number" id="fYellowMin" class="form-control" min="1" value="10" placeholder="10">
                        <small class="text-muted">Moderate demand</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold"><span class="dot dot-red"></span> Red (>=)</label>
                        <input type="number" id="fRedMin" class="form-control" min="1" value="1" placeholder="1">
                        <small class="text-muted">Slow mover</small>
                    </div>
                </div>
                <div class="alert alert-secondary" style="font-size:12px;margin-bottom:0;">
                    <strong><span class="dot dot-black"></span> Black:</strong> Products with 0 orders in the date range (dead stock).
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success w-50" onclick="saveConfig();"><i class="fas fa-check"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var trendModal = null;

document.addEventListener('DOMContentLoaded', function() {
    trendModal = new bootstrap.Modal(document.getElementById('trendModal'));
    loadConfigs();
});

function escHtml(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}

// ===================== LOAD CONFIGS =====================

function loadConfigs() {
    $.post('product_trend_ajax.php', { action: 'list' }, function(data) {
        var tbody = document.getElementById('dataBody');
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr class="no-results"><td colspan="6"><i class="fas fa-chart-line" style="font-size:24px;margin-bottom:8px;display:block;"></i>No trend configs yet. Create one to get started.</td></tr>';
            return;
        }

        var html = '';
        for (var i = 0; i < data.length; i++) {
            var c = data[i];
            var isActive = parseInt(c.is_active) === 1;

            html += '<tr style="' + (isActive ? 'background:#f0fdf4;' : '') + '">';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td><strong>' + escHtml(c.name) + '</strong></td>';
            html += '<td>' + escHtml(c.date_from) + ' <i class="fas fa-arrow-right" style="font-size:10px;color:var(--text-muted);margin:0 4px;"></i> ' + escHtml(c.date_to) + '</td>';
            html += '<td>';
            html += '<span class="dot dot-green"></span>' + c.green_min + '+ ';
            html += '<span class="dot dot-yellow"></span>' + c.yellow_min + '+ ';
            html += '<span class="dot dot-red"></span>' + c.red_min + '+ ';
            html += '<span class="dot dot-black"></span>0';
            html += '</td>';
            html += '<td>';
            if (isActive) {
                html += '<span class="badge-status badge-active">Active</span>';
            } else {
                html += '<span class="badge-status badge-inactive">Inactive</span>';
            }
            html += '</td>';
            html += '<td style="white-space:nowrap">';
            html += '<button class="btn-action btn-preview" onclick="previewConfig(' + c.id + ',\'' + escHtml(c.name).replace(/'/g, "\\'") + '\');"><i class="fas fa-eye"></i></button> ';
            html += '<button class="btn-action btn-edit" onclick="openEditModal(' + c.id + ');"><i class="fas fa-pen"></i></button> ';
            if (isActive) {
                html += '<button class="btn-action" style="background:#f59e0b;" onclick="deactivateConfig(' + c.id + ');"><i class="fas fa-pause"></i></button>';
            } else {
                html += '<button class="btn-action btn-activate" onclick="activateConfig(' + c.id + ');"><i class="fas fa-check"></i></button> ';
                html += '<button class="btn-action btn-delete" onclick="deleteConfig(' + c.id + ',\'' + escHtml(c.name).replace(/'/g, "\\'") + '\');"><i class="fas fa-trash"></i></button>';
            }
            html += '</td>';
            html += '</tr>';
        }
        tbody.innerHTML = html;
    }, 'json');
}

// ===================== CRUD =====================

function clearForm() {
    document.getElementById('editId').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('fDateFrom').value = '';
    document.getElementById('fDateTo').value = '';
    document.getElementById('fGreenMin').value = '50';
    document.getElementById('fYellowMin').value = '10';
    document.getElementById('fRedMin').value = '1';
}

function openCreateModal() {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-chart-line"></i> New Trend Config';
    trendModal.show();
}

function openEditModal(id) {
    clearForm();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-chart-line"></i> Edit Trend Config';
    document.getElementById('editId').value = id;

    $.post('product_trend_ajax.php', { action: 'get', id: id }, function(data) {
        if (data.error) { Swal.fire({ icon: 'error', text: data.error }); return; }
        document.getElementById('fName').value = data.name || '';
        document.getElementById('fDateFrom').value = data.date_from || '';
        document.getElementById('fDateTo').value = data.date_to || '';
        document.getElementById('fGreenMin').value = data.green_min || '50';
        document.getElementById('fYellowMin').value = data.yellow_min || '10';
        document.getElementById('fRedMin').value = data.red_min || '1';
        trendModal.show();
    }, 'json');
}

function saveConfig() {
    var editId = document.getElementById('editId').value;
    var name = document.getElementById('fName').value.trim();
    var dateFrom = document.getElementById('fDateFrom').value;
    var dateTo = document.getElementById('fDateTo').value;

    if (name === '' || dateFrom === '' || dateTo === '') {
        Swal.fire({ icon: 'warning', text: 'Name and date range are required.' });
        return;
    }

    var greenMin = parseInt(document.getElementById('fGreenMin').value) || 50;
    var yellowMin = parseInt(document.getElementById('fYellowMin').value) || 10;
    var redMin = parseInt(document.getElementById('fRedMin').value) || 1;

    if (greenMin <= yellowMin || yellowMin <= redMin) {
        Swal.fire({ icon: 'warning', text: 'Thresholds must be in order: Green > Yellow > Red.' });
        return;
    }

    var postData = {
        action: editId ? 'update' : 'create',
        name: name,
        date_from: dateFrom,
        date_to: dateTo,
        green_min: greenMin,
        yellow_min: yellowMin,
        red_min: redMin
    };
    if (editId) postData.id = editId;

    $.post('product_trend_ajax.php', postData, function(data) {
        if (data.success) {
            trendModal.hide();
            Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() {
                loadConfigs();
            });
        } else {
            Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
        }
    }, 'json');
}

function activateConfig(id) {
    $.post('product_trend_ajax.php', { action: 'activate', id: id }, function(data) {
        if (data.success) {
            loadConfigs();
            Swal.fire({ icon: 'success', text: 'Trend config activated. Staff will now see these indicators.', timer: 2000, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', text: data.error });
        }
    }, 'json');
}

function deactivateConfig(id) {
    $.post('product_trend_ajax.php', { action: 'deactivate', id: id }, function(data) {
        if (data.success) {
            loadConfigs();
        } else {
            Swal.fire({ icon: 'error', text: data.error });
        }
    }, 'json');
}

function deleteConfig(id, name) {
    Swal.fire({
        title: 'Delete trend config?',
        text: 'Delete "' + name + '"? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete'
    }).then(function(result) {
        if (result.isConfirmed) {
            $.post('product_trend_ajax.php', { action: 'delete', id: id }, function(data) {
                if (data.success) {
                    loadConfigs();
                    // Hide preview if it was showing this config
                    document.getElementById('previewCard').style.display = 'none';
                } else {
                    Swal.fire({ icon: 'error', text: data.error });
                }
            }, 'json');
        }
    });
}

// ===================== PREVIEW =====================

function previewConfig(id, name) {
    var card = document.getElementById('previewCard');
    card.style.display = 'block';
    document.getElementById('previewTitle').textContent = name;
    document.getElementById('previewStats').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin" style="font-size:20px;"></i><div>Analyzing order data...</div></div>';
    document.getElementById('topMovers').innerHTML = '';

    $.post('product_trend_ajax.php', { action: 'preview', id: id }, function(data) {
        if (data.error) {
            document.getElementById('previewStats').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:20px;color:var(--text-muted);">' + escHtml(data.error) + '</div>';
            return;
        }

        var total = data.total_products || 0;

        var statsHtml = '';
        statsHtml += '<div class="stat-card stat-green"><div class="stat-value">' + data.green + '</div><div class="stat-label">Hot Sellers</div></div>';
        statsHtml += '<div class="stat-card stat-yellow"><div class="stat-value">' + data.yellow + '</div><div class="stat-label">Moderate</div></div>';
        statsHtml += '<div class="stat-card stat-red"><div class="stat-value">' + data.red + '</div><div class="stat-label">Slow Movers</div></div>';
        statsHtml += '<div class="stat-card stat-black"><div class="stat-value">' + data.black + '</div><div class="stat-label">Dead Stock</div></div>';
        document.getElementById('previewStats').innerHTML = statsHtml;

        // Top movers
        var topMovers = data.top_movers || [];
        if (topMovers.length === 0) {
            document.getElementById('topMovers').innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);">No orders found in this date range.</div>';
        } else {
            var moversHtml = '';
            for (var i = 0; i < topMovers.length; i++) {
                var m = topMovers[i];
                moversHtml += '<div class="mover-item">';
                moversHtml += '<span class="mover-rank">#' + (i + 1) + '</span>';
                moversHtml += '<span class="mover-name">' + escHtml(m.name || m.BARCODE) + '</span>';
                moversHtml += '<span class="mover-qty">' + parseInt(m.total_ordered) + ' orders</span>';
                moversHtml += '</div>';
            }
            document.getElementById('topMovers').innerHTML = moversHtml;
        }

        // Scroll to preview
        setTimeout(function() {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }, 'json');
}

// Focus name input when modal opens
document.getElementById('trendModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('fName').focus();
});
</script>
</body>
</html>
