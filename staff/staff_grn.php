<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
include('dbconnection.php');
$connect->set_charset("utf8mb4");

$poId = intval($_GET['po_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Receiving</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 80px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }

        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .back-btn { background: none; border: none; color: #fff; cursor: pointer; display: flex; align-items: center; padding: 4px; text-decoration: none; }
        .back-btn svg { width: 22px; height: 22px; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; flex: 1; }

        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        /* Tab bar for list vs receive */
        .tab-bar { display: flex; background: var(--surface); border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); margin-bottom: 16px; overflow: hidden; }
        .tab-btn { flex: 1; padding: 10px 8px; border: none; background: none; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; text-align: center; border-bottom: 3px solid transparent; transition: all 0.2s; }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); background: #fef2f2; }

        /* GRN Card */
        .grn-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; margin-bottom: 12px; }
        .grn-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .grn-number { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 600; }
        .grn-date { font-size: 12px; color: var(--text-muted); }
        .grn-meta { font-size: 12px; color: var(--text-muted); line-height: 1.6; }

        /* Receive form */
        .info-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; margin-bottom: 12px; }
        .info-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
        .info-row .lbl { color: var(--text-muted); font-weight: 500; }
        .info-row .val { font-weight: 600; }

        .form-group { margin-bottom: 14px; }
        .form-label { font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block; }
        .form-input { width: 100%; padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; }
        .form-input:focus { border-color: var(--primary); }
        .form-select { width: 100%; padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; outline: none; appearance: none; background: var(--surface); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
        .form-select:focus { border-color: var(--primary); }

        /* Receive item card */
        .recv-item { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 12px; margin-bottom: 10px; }
        .recv-item-top { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .ri-img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: #f3f4f6; flex-shrink: 0; }
        .ri-noimg { width: 44px; height: 44px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ri-noimg svg { width: 18px; height: 18px; stroke: #d1d5db; fill: none; }
        .ri-info { flex: 1; min-width: 0; }
        .ri-name { font-weight: 600; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ri-barcode { font-size: 11px; color: var(--text-muted); }
        .recv-fields { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .recv-field label { font-size: 10px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .recv-field input { width: 100%; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 13px; text-align: center; }
        .recv-field input:focus { border-color: var(--primary); outline: none; }
        .recv-qty-info { font-size: 11px; color: var(--text-muted); margin-top: 6px; display: flex; gap: 12px; }

        .btn-primary { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 24px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 600; cursor: pointer; margin-top: 16px; }
        .btn-primary:active { transform: scale(0.98); }

        .empty-state { text-align: center; padding: 48px 16px; color: var(--text-muted); }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state p { font-size: 14px; }
        .loading { text-align: center; padding: 40px; color: var(--text-muted); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <a href="<?php echo $poId > 0 ? 'staff_po.php?id=' . $poId : 'staff_po.php'; ?>" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        </a>
        <span class="page-title">Goods Receiving</span>
    </header>

    <div class="main-content">
        <?php if ($poId > 0): ?>
        <!-- RECEIVE MODE -->
        <div id="receiveView">
            <div class="loading" id="receiveLoading">Loading PO items...</div>
            <div id="receiveContent" style="display:none;">
                <div class="info-card" id="poInfoCard"></div>
                <div class="form-group">
                    <label class="form-label">GRN Remark</label>
                    <input type="text" id="grnRemark" class="form-input" placeholder="Optional note for this receiving">
                </div>
                <div id="receiveItems"></div>
                <button class="btn-primary" onclick="submitGRN();">Submit Receiving</button>
            </div>
        </div>
        <?php else: ?>
        <!-- LIST MODE -->
        <div class="tab-bar">
            <button class="tab-btn active" data-view="history" onclick="switchGrnTab(this)">GRN History</button>
            <button class="tab-btn" data-view="receive" onclick="switchGrnTab(this)">Receive from PO</button>
        </div>

        <div id="grnHistoryView">
            <div id="grnList"><div class="loading">Loading...</div></div>
        </div>

        <div id="grnReceiveView" style="display:none;">
            <div class="form-group">
                <label class="form-label">Select PO to Receive</label>
                <select id="poSelect" class="form-select" onchange="if(this.value) window.location='staff_grn.php?po_id='+this.value;">
                    <option value="">-- Select Approved PO --</option>
                </select>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    var poId = <?php echo $poId; ?>;

    <?php if ($poId > 0): ?>
    // ==================== RECEIVE MODE ====================
    var receiveData = { po: null, items: [] };

    function loadPOItems() {
        fetch('staff_po_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_po_items&po_id=' + poId
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                Swal.fire({ icon: 'error', text: data.error }).then(function() { window.location = 'staff_po.php'; });
                return;
            }
            receiveData = data;
            renderReceiveView();
        });
    }

    function renderReceiveView() {
        var po = receiveData.po;
        var items = receiveData.items;

        document.getElementById('receiveLoading').style.display = 'none';
        document.getElementById('receiveContent').style.display = '';

        // PO info card
        var date = po.order_date ? new Date(po.order_date).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) : '';
        document.getElementById('poInfoCard').innerHTML =
            '<div class="info-row"><span class="lbl">PO Number</span><span class="val">' + escHtml(po.po_number) + '</span></div>' +
            '<div class="info-row"><span class="lbl">Supplier</span><span class="val">' + escHtml(po.supplier_name) + '</span></div>' +
            '<div class="info-row"><span class="lbl">Order Date</span><span class="val">' + date + '</span></div>';

        // Receive items
        var noImgSvg = '<svg viewBox="0 0 24 24" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';

        var html = '';
        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            var pending = parseFloat(it.qty_pending) || 0;
            var imgHtml = it.product_image ?
                '<img class="ri-img" src="../img/' + escHtml(it.product_image) + '" alt="">' :
                '<div class="ri-noimg">' + noImgSvg + '</div>';

            html += '<div class="recv-item" data-poi-id="' + it.id + '" data-barcode="' + escHtml(it.barcode) + '">' +
                '<div class="recv-item-top">' + imgHtml +
                '<div class="ri-info"><div class="ri-name">' + escHtml(it.product_desc) + '</div>' +
                '<div class="ri-barcode">' + escHtml(it.barcode) + '</div></div></div>' +
                '<div class="recv-fields">' +
                    '<div class="recv-field"><label>Receive</label><input type="number" class="grn-qty" value="' + Math.max(0, pending) + '" min="0" step="0.01"></div>' +
                    '<div class="recv-field"><label>Rejected</label><input type="number" class="grn-rejected" value="0" min="0" step="0.01"></div>' +
                    '<div class="recv-field"><label>Rack</label><input type="text" class="grn-rack" value="" placeholder=""></div>' +
                '</div>' +
                '<div class="recv-qty-info">' +
                    '<span>Ordered: <strong>' + it.qty_ordered + '</strong></span>' +
                    '<span>Received: <strong>' + it.qty_received + '</strong></span>' +
                    '<span>Pending: <strong>' + pending + '</strong></span>' +
                '</div>' +
            '</div>';
        }
        document.getElementById('receiveItems').innerHTML = html;
    }

    function submitGRN() {
        var items = [];
        document.querySelectorAll('.recv-item').forEach(function(el) {
            var qty = parseFloat(el.querySelector('.grn-qty').value) || 0;
            var rejected = parseFloat(el.querySelector('.grn-rejected').value) || 0;
            if (qty > 0 || rejected > 0) {
                items.push({
                    po_item_id: el.getAttribute('data-poi-id'),
                    barcode: el.getAttribute('data-barcode'),
                    product_desc: el.querySelector('.ri-name').textContent,
                    qty_received: qty,
                    qty_rejected: rejected,
                    batch_no: '',
                    rack_location: el.querySelector('.grn-rack').value.trim()
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
                fetch('staff_po_ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=receive' +
                        '&po_id=' + poId +
                        '&supplier_id=' + receiveData.po.supplier_id +
                        '&remark=' + encodeURIComponent(document.getElementById('grnRemark').value) +
                        '&items=' + encodeURIComponent(JSON.stringify(items))
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        Swal.fire({ icon: 'success', text: data.success, showConfirmButton: true }).then(function() {
                            window.location = 'staff_po.php?id=' + poId;
                        });
                    } else {
                        Swal.fire({ icon: 'error', text: data.error || 'Something went wrong.' });
                    }
                });
            }
        });
    }

    loadPOItems();

    <?php else: ?>
    // ==================== LIST MODE ====================
    function switchGrnTab(btn) {
        document.querySelectorAll('.tab-btn').forEach(function(t) { t.classList.remove('active'); });
        btn.classList.add('active');
        var view = btn.getAttribute('data-view');
        document.getElementById('grnHistoryView').style.display = view === 'history' ? '' : 'none';
        document.getElementById('grnReceiveView').style.display = view === 'receive' ? '' : 'none';
        if (view === 'receive') loadReceivablePOs();
    }

    function loadGRNs() {
        fetch('staff_po_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list_grn'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var grns = data.grns || [];
            if (grns.length === 0) {
                document.getElementById('grnList').innerHTML = '<div class="empty-state"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg><p>No receiving records yet</p></div>';
                return;
            }
            var html = '';
            for (var i = 0; i < grns.length; i++) {
                var g = grns[i];
                var date = g.receive_date ? new Date(g.receive_date).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'}) : '';
                html += '<div class="grn-card">' +
                    '<div class="grn-card-top"><div class="grn-number">' + escHtml(g.grn_number) + '</div><div class="grn-date">' + date + '</div></div>' +
                    '<div class="grn-meta">' +
                        (g.po_number ? 'PO: ' + escHtml(g.po_number) + '<br>' : '') +
                        'Supplier: ' + escHtml(g.supplier_name || '-') +
                        (g.received_by ? '<br>By: ' + escHtml(g.received_by) : '') +
                        (g.remark ? '<br>Note: ' + escHtml(g.remark) : '') +
                    '</div></div>';
            }
            document.getElementById('grnList').innerHTML = html;
        });
    }

    function loadReceivablePOs() {
        fetch('staff_po_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=list_receivable_pos'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var pos = data.pos || [];
            var sel = document.getElementById('poSelect');
            sel.innerHTML = '<option value="">-- Select Approved PO --</option>';
            for (var i = 0; i < pos.length; i++) {
                var o = document.createElement('option');
                o.value = pos[i].id;
                o.textContent = pos[i].po_number + ' - ' + (pos[i].supplier_name || '');
                sel.appendChild(o);
            }
        });
    }

    loadGRNs();
    <?php endif; ?>
    </script>
</body>
</html>
