<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include('dbconnection.php');
$connect->set_charset("utf8mb4");

$orderId = intval($_GET['id'] ?? 0);
$fromPage = ($_GET['from'] ?? '') === 'history' ? 'del_history.php' : 'del_dashboard.php';
if ($orderId <= 0) { header("Location: del_dashboard.php"); exit; }

$stmt = $connect->prepare("SELECT o.*, c.NAME AS CUSTNAME FROM `del_orderlist` o LEFT JOIN `del_customer` c ON o.CUSTOMERCODE = c.CODE WHERE o.ID = ? LIMIT 1");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { header("Location: del_dashboard.php"); exit; }

// Ensure INSTALL_IMG column exists
$connect->query("ALTER TABLE `del_orderlistdesc` ADD COLUMN `INSTALL_IMG` VARCHAR(200) NOT NULL DEFAULT '' AFTER `INSTALL`");

// Fetch items that require installation
$ordno = $connect->real_escape_string($order['ORDNO'] ?? '');
$installItems = [];
$instQ = $connect->query("SELECT * FROM `del_orderlistdesc` WHERE ORDERNO = '$ordno' AND INSTALL = 'Y'");
if ($instQ) {
    while ($ir = $instQ->fetch_assoc()) { $installItems[] = $ir; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Photos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 160px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .back-btn { display: flex; align-items: center; gap: 4px; background: none; border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; cursor: pointer; padding: 6px 8px; border-radius: 8px; transition: background 0.2s; text-decoration: none; }
        .back-btn:hover { background: rgba(255,255,255,0.15); }
        .back-btn svg { width: 20px; height: 20px; flex-shrink: 0; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        .order-info { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; margin-bottom: 16px; }
        .order-info h3 { font-size: 16px; margin-bottom: 4px; }
        .order-info p { font-size: 13px; color: var(--text-muted); }

        .photo-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        @media (max-width: 480px) { .photo-grid { grid-template-columns: 1fr; } }

        .photo-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); overflow: hidden; position: relative; cursor: pointer; }
        .photo-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; padding: 10px 12px 8px; letter-spacing: 0.3px; }
        .photo-preview { width: 100%; aspect-ratio: 4/3; background: #f9fafb; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .photo-preview .placeholder { text-align: center; color: var(--text-muted); }
        .photo-preview .placeholder svg { width: 32px; height: 32px; margin-bottom: 4px; }
        .photo-preview .placeholder p { font-size: 12px; }
        .photo-card input[type="file"] { display: none; }
        .photo-existing { position: absolute; top: 8px; right: 8px; background: #16a34a; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px; }

        .action-bar { display: flex; gap: 12px; margin-bottom: 20px; }
        .action-bar button { flex: 1; padding: 14px 20px; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .action-bar button svg { width: 20px; height: 20px; }
        .btn-upload { background: #374151; color: #fff; }
        .btn-upload:hover { background: #1f2937; }
        .btn-upload:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Floating Job Done button */
        .floating-done { position: fixed; bottom: 70px; left: 0; right: 0; z-index: 99; padding: 12px 16px; background: linear-gradient(transparent, var(--bg) 20%); }
        .floating-done button { width: 100%; max-width: 700px; margin: 0 auto; display: flex; padding: 16px 20px; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s; align-items: center; justify-content: center; gap: 8px; background: #16a34a; color: #fff; box-shadow: 0 4px 16px rgba(22,163,74,0.3); }
        .floating-done button:hover { background: #15803d; }
        .floating-done button:disabled { opacity: 0.5; cursor: not-allowed; }
        .floating-done button svg { width: 22px; height: 22px; }

        /* Installation section */
        .install-section { margin-bottom: 20px; }
        .install-header { background: #f59e0b; color: #fff; padding: 10px 16px; border-radius: 12px 12px 0 0; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .install-header svg { width: 20px; height: 20px; }
        .install-list { background: var(--surface); border-radius: 0 0 12px 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); overflow: hidden; }
        .install-item { padding: 12px 16px; border-bottom: 1px solid #f3f4f6; }
        .install-item:last-child { border-bottom: none; }
        .install-item-name { font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .install-item-qty { font-size: 12px; color: var(--text-muted); margin-bottom: 8px; }
        .install-photo-area { cursor: pointer; display: flex; align-items: center; gap: 12px; background: #f9fafb; border-radius: 8px; padding: 8px; border: 1px dashed #d1d5db; }
        .install-preview { width: 80px; height: 60px; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .install-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .install-preview .placeholder svg { width: 24px; height: 24px; color: var(--text-muted); }
        .install-photo-label { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .install-photo-label svg { width: 16px; height: 16px; }
        .install-item input[type="file"] { display: none; }
        .install-existing-badge { display: inline-block; background: #16a34a; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 10px; margin-left: 8px; }
        .btn-install-upload { width: 100%; padding: 14px 20px; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 700; cursor: pointer; background: #f59e0b; color: #fff; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s; }
        .btn-install-upload:hover { background: #d97706; }
        .btn-install-upload:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-install-upload svg { width: 20px; height: 20px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <a href="<?php echo $fromPage; ?>" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
            Back
        </a>
        <span class="page-title">Upload Photos</span>
    </header>

    <div class="main-content">
        <div class="order-info">
            <h3><?php echo htmlspecialchars($order['ORDNO'] ?? ''); ?></h3>
            <p><?php echo htmlspecialchars($order['CUSTNAME'] ?? $order['CUSTOMER'] ?? ''); ?> &middot; <?php echo htmlspecialchars($order['DELDATE'] ?? ''); ?></p>
        </div>

        <div class="photo-grid">
            <?php for ($i = 1; $i <= 3; $i++):
                $imgField = 'IMG' . $i;
                $hasImg = !empty($order[$imgField]);
            ?>
            <div class="photo-card" onclick="document.getElementById('file<?php echo $i; ?>').click()">
                <div class="photo-label">Photo <?php echo $i; ?></div>
                <?php if ($hasImg): ?>
                <div class="photo-existing">Uploaded</div>
                <?php endif; ?>
                <div class="photo-preview" id="preview<?php echo $i; ?>">
                    <?php if ($hasImg): ?>
                    <img src="uploads/<?php echo htmlspecialchars($order[$imgField]); ?>" style="display:block;" alt="Photo <?php echo $i; ?>">
                    <?php else: ?>
                    <div class="placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <p>Tap to select</p>
                    </div>
                    <?php endif; ?>
                </div>
                <input type="file" accept="image/*" capture="environment" id="file<?php echo $i; ?>" onchange="previewImage(<?php echo $i; ?>)">
            </div>
            <?php endfor; ?>
        </div>

        <div class="action-bar">
            <button class="btn-upload" onclick="uploadPhotos()" id="btnUpload">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload
            </button>
        </div>

        <?php if (count($installItems) > 0): ?>
        <!-- Installation Section -->
        <div class="install-section" style="margin-top: 20px;">
            <div class="install-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                Installation (<?php echo count($installItems); ?> item<?php echo count($installItems) > 1 ? 's' : ''; ?>)
            </div>
            <div class="install-list">
                <?php foreach ($installItems as $idx => $instItem):
                    $hasInstImg = !empty($instItem['INSTALL_IMG']);
                ?>
                <div class="install-item">
                    <div class="install-item-name">
                        <?php echo htmlspecialchars($instItem['PDESC'] ?? ''); ?>
                        <?php if ($hasInstImg): ?>
                        <span class="install-existing-badge">Uploaded</span>
                        <?php endif; ?>
                    </div>
                    <div class="install-item-qty">Qty: <?php echo htmlspecialchars(($instItem['QTY'] ?? '') . ' ' . ($instItem['UOM'] ?? '')); ?></div>
                    <div class="install-photo-area" onclick="document.getElementById('installFile<?php echo $instItem['ID']; ?>').click()">
                        <div class="install-preview" id="installPreview<?php echo $instItem['ID']; ?>">
                            <?php if ($hasInstImg): ?>
                            <img src="uploads/<?php echo htmlspecialchars($instItem['INSTALL_IMG']); ?>" style="display:block;" alt="Install photo">
                            <?php else: ?>
                            <div class="placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="install-photo-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            Tap to take photo
                        </div>
                    </div>
                    <input type="file" accept="image/*" capture="environment" id="installFile<?php echo $instItem['ID']; ?>" onchange="previewInstallImage(<?php echo $instItem['ID']; ?>)">
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 12px;">
                <button class="btn-install-upload" onclick="uploadInstallPhotos()" id="btnInstallUpload">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Upload Installation Photos
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Floating Job Done Button -->
    <div class="floating-done no-print">
        <button onclick="markDone()" id="btnDone">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Job Done
        </button>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script>
    var orderId = <?php echo $orderId; ?>;
    var backPage = '<?php echo $fromPage; ?>';

    function previewImage(num) {
        var input = document.getElementById('file' + num);
        var preview = document.getElementById('preview' + num);
        if (input.files && input.files[0]) {
            var img = preview.querySelector('img');
            if (!img) {
                preview.innerHTML = '<img style="display:block;" alt="Photo ' + num + '">';
                img = preview.querySelector('img');
            }
            img.src = URL.createObjectURL(input.files[0]);
            img.style.display = 'block';
        }
    }

    var uploadBtnHtml = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload';
    var installBtnHtml = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Upload Installation Photos';
    var orderNo = <?php echo json_encode($order['ORDNO'] ?? ''); ?>;

    // Save each photo as a SEPARATE pending record (compressed individually)
    // This ensures: 1) each photo is small (~150-300KB vs 3-10MB raw)
    //               2) each photo syncs independently (one failure doesn't block others)
    //               3) POST size never exceeds PHP limits
    function savePhotosOffline() {
        var promises = [];
        for (var i = 1; i <= 3; i++) {
            var input = document.getElementById('file' + i);
            if (input.files && input.files[0]) {
                (function(idx, file) {
                    promises.push(
                        OfflineSync.compressImage(file, 1200, 0.75).then(function(compressedB64) {
                            return OfflineSync.addPending('photo_upload', 'Photo ' + idx + ' - ' + orderNo, {
                                url: 'del_work_ajax.php',
                                fields: { action: 'upload_single', id: orderId, image_num: idx },
                                files: [{ key: 'image', data: compressedB64, name: 'photo' + idx + '.jpg', type: 'image/jpeg' }]
                            });
                        })
                    );
                })(i, input.files[0]);
            }
        }
        return Promise.all(promises);
    }

    function uploadPhotos() {
        var hasFile = false;
        for (var i = 1; i <= 3; i++) {
            var input = document.getElementById('file' + i);
            if (input.files && input.files[0]) { hasFile = true; break; }
        }
        if (!hasFile) {
            Swal.fire({ icon: 'warning', text: 'Please select at least one photo to upload.', confirmButtonColor: '#C8102E' });
            return;
        }

        var btn = document.getElementById('btnUpload');
        btn.disabled = true;
        btn.innerHTML = '<span>Uploading...</span>';

        if (!navigator.onLine) {
            savePhotosOffline().then(function() {
                btn.disabled = false;
                btn.innerHTML = uploadBtnHtml;
                Swal.fire({ icon: 'info', title: 'Saved Offline', text: 'Photos will sync automatically when you are back online.', confirmButtonColor: '#C8102E' });
            });
            return;
        }

        var formData = new FormData();
        formData.append('action', 'upload');
        formData.append('id', orderId);
        for (var i = 1; i <= 3; i++) {
            var input = document.getElementById('file' + i);
            if (input.files && input.files[0]) formData.append('image' + i, input.files[0]);
        }

        fetch('del_work_ajax.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = uploadBtnHtml;
            if (data.success) {
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Upload failed.', confirmButtonColor: '#C8102E' });
            }
        })
        .catch(function() {
            // Network failed, save offline
            savePhotosOffline().then(function() {
                btn.disabled = false;
                btn.innerHTML = uploadBtnHtml;
                Swal.fire({ icon: 'info', title: 'Saved Offline', text: 'Photos will sync automatically when you are back online.', confirmButtonColor: '#C8102E' });
            });
        });
    }

    var installItemIds = <?php echo json_encode(array_map(function($item) { return $item['ID']; }, $installItems)); ?>;

    function previewInstallImage(itemId) {
        var input = document.getElementById('installFile' + itemId);
        var preview = document.getElementById('installPreview' + itemId);
        if (input.files && input.files[0]) {
            var img = preview.querySelector('img');
            if (!img) {
                preview.innerHTML = '<img style="display:block;" alt="Install photo">';
                img = preview.querySelector('img');
            }
            img.src = URL.createObjectURL(input.files[0]);
            img.style.display = 'block';
        }
    }

    // Save each install photo as a SEPARATE pending record (compressed individually)
    function saveInstallOffline() {
        var promises = [];
        for (var i = 0; i < installItemIds.length; i++) {
            var itemId = installItemIds[i];
            var input = document.getElementById('installFile' + itemId);
            if (input && input.files && input.files[0]) {
                (function(id, file) {
                    promises.push(
                        OfflineSync.compressImage(file, 1200, 0.75).then(function(compressedB64) {
                            return OfflineSync.addPending('install_upload', 'Install photo item ' + id + ' - ' + orderNo, {
                                url: 'del_work_ajax.php',
                                fields: { action: 'upload_install_single', id: orderId, item_id: id },
                                files: [{ key: 'image', data: compressedB64, name: 'install_' + id + '.jpg', type: 'image/jpeg' }]
                            });
                        })
                    );
                })(itemId, input.files[0]);
            }
        }
        return Promise.all(promises);
    }

    function uploadInstallPhotos() {
        var hasFile = false;
        for (var i = 0; i < installItemIds.length; i++) {
            var input = document.getElementById('installFile' + installItemIds[i]);
            if (input && input.files && input.files[0]) { hasFile = true; break; }
        }
        if (!hasFile) {
            Swal.fire({ icon: 'warning', text: 'Please select at least one installation photo to upload.', confirmButtonColor: '#f59e0b' });
            return;
        }

        var btn = document.getElementById('btnInstallUpload');
        btn.disabled = true;
        btn.innerHTML = '<span>Uploading...</span>';

        if (!navigator.onLine) {
            saveInstallOffline().then(function() {
                btn.disabled = false;
                btn.innerHTML = installBtnHtml;
                Swal.fire({ icon: 'info', title: 'Saved Offline', text: 'Install photos will sync automatically when you are back online.', confirmButtonColor: '#f59e0b' });
            });
            return;
        }

        var formData = new FormData();
        formData.append('action', 'upload_install');
        formData.append('id', orderId);
        for (var i = 0; i < installItemIds.length; i++) {
            var itemId = installItemIds[i];
            var input = document.getElementById('installFile' + itemId);
            if (input && input.files && input.files[0]) formData.append('install_img_' + itemId, input.files[0]);
        }

        fetch('del_work_ajax.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = installBtnHtml;
            if (data.success) {
                Swal.fire({ icon: 'success', text: data.success, timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Upload failed.', confirmButtonColor: '#C8102E' });
            }
        })
        .catch(function() {
            saveInstallOffline().then(function() {
                btn.disabled = false;
                btn.innerHTML = installBtnHtml;
                Swal.fire({ icon: 'info', title: 'Saved Offline', text: 'Install photos will sync automatically when you are back online.', confirmButtonColor: '#f59e0b' });
            });
        });
    }

    function markDone() {
        Swal.fire({
            title: 'Mark as Done?',
            text: 'This will mark the delivery as completed. Make sure you have uploaded at least one photo.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: 'Yes, Job Done'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            var btn = document.getElementById('btnDone');
            btn.disabled = true;

            if (!navigator.onLine) {
                OfflineSync.addPending('job_done', 'Job Done - ' + orderNo, {
                    url: 'del_work_ajax.php',
                    fields: { action: 'done', id: orderId },
                    files: []
                }).then(function() {
                    btn.disabled = false;
                    Swal.fire({ icon: 'info', title: 'Saved Offline', text: 'Job Done will sync automatically when you are back online.', confirmButtonColor: '#16a34a' }).then(function() {
                        window.location.href = backPage;
                    });
                });
                return;
            }

            var formData = new FormData();
            formData.append('action', 'done');
            formData.append('id', orderId);

            fetch('del_work_ajax.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Job Done!', text: data.success, confirmButtonColor: '#16a34a' }).then(function() {
                        window.location.href = backPage;
                    });
                } else {
                    Swal.fire({ icon: 'error', text: data.error || 'Failed.', confirmButtonColor: '#C8102E' });
                }
            })
            .catch(function() {
                // Network failed, save offline
                OfflineSync.addPending('job_done', 'Job Done - ' + orderNo, {
                    url: 'del_work_ajax.php',
                    fields: { action: 'done', id: orderId },
                    files: []
                }).then(function() {
                    btn.disabled = false;
                    Swal.fire({ icon: 'info', title: 'Saved Offline', text: 'Job Done will sync automatically when you are back online.', confirmButtonColor: '#16a34a' }).then(function() {
                        window.location.href = backPage;
                    });
                });
            });
        });
    }
    </script>
</body>
</html>
