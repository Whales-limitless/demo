<?php
require_once __DIR__ . '/session_security.php';
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$userType = $_SESSION['user_type'] ?? 'S';
if ($userType !== 'D' && $userType !== 'A') {
    header("Location: index.php");
    exit;
}

$userCode = $_SESSION['user_code'] ?? '';
$userName = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Installation Job</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 100px; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; }

        .info-card { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; margin-bottom: 16px; }
        .info-card h3 { font-size: 16px; margin-bottom: 4px; }
        .info-card p { font-size: 13px; color: var(--text-muted); }

        .form-section { background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 16px; margin-bottom: 16px; }
        .form-label { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 8px; display: block; }

        .photo-card { border-radius: 12px; overflow: hidden; cursor: pointer; border: 2px dashed #d1d5db; background: #f9fafb; }
        .photo-preview { width: 100%; aspect-ratio: 4/3; background: #f9fafb; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .photo-preview .placeholder { text-align: center; color: var(--text-muted); padding: 20px; }
        .photo-preview .placeholder svg { width: 40px; height: 40px; margin-bottom: 8px; }
        .photo-preview .placeholder p { font-size: 13px; }
        input[type="file"] { display: none; }

        textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 12px; font-family: 'DM Sans', sans-serif; font-size: 14px; resize: vertical; min-height: 90px; }
        textarea:focus { outline: none; border-color: var(--primary); }

        .floating-submit { position: fixed; bottom: 70px; left: 0; right: 0; z-index: 99; padding: 12px 16px; background: linear-gradient(transparent, var(--bg) 20%); }
        .floating-submit button { width: 100%; max-width: 700px; margin: 0 auto; display: flex; padding: 16px 20px; border: none; border-radius: 12px; font-family: 'DM Sans', sans-serif; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.2s; align-items: center; justify-content: center; gap: 8px; background: var(--primary); color: #fff; box-shadow: 0 4px 16px rgba(200,16,46,0.3); }
        .floating-submit button:hover { background: var(--primary-dark); }
        .floating-submit button:disabled { opacity: 0.5; cursor: not-allowed; }
        .floating-submit button svg { width: 22px; height: 22px; }

        .quick-link { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: var(--surface); border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); text-decoration: none; color: var(--text); margin-bottom: 16px; }
        .quick-link:hover { background: #f9fafb; }
        .quick-link-left { display: flex; align-items: center; gap: 12px; }
        .quick-link-icon { width: 40px; height: 40px; border-radius: 10px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center; }
        .quick-link-icon svg { width: 20px; height: 20px; }
        .quick-link-text { font-size: 14px; font-weight: 600; }
        .quick-link-text small { display: block; font-size: 12px; color: var(--text-muted); font-weight: 400; }
        .quick-link-arrow svg { width: 20px; height: 20px; color: var(--text-muted); }

        .source-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: flex-end; justify-content: center; }
        .source-overlay.active { display: flex; }
        .source-modal { background: var(--surface); border-radius: 16px 16px 0 0; width: 100%; max-width: 500px; padding: 16px; }
        .source-handle { width: 40px; height: 4px; background: #d1d5db; border-radius: 2px; margin: 0 auto 16px; }
        .source-btn { width: 100%; padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px; background: var(--surface); font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .source-btn svg { width: 20px; height: 20px; color: var(--primary); }
        .source-btn:hover { background: #f9fafb; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <span class="page-title">Submit Installation Job</span>
    </header>

    <div class="main-content">
        <div class="info-card">
            <h3><?php echo htmlspecialchars($userName); ?></h3>
            <p>Submit one installation job at a time. Attach a photo of the completed installation and add a remark if needed.</p>
        </div>

        <a href="inst_history.php" class="quick-link">
            <div class="quick-link-left">
                <div class="quick-link-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="quick-link-text">
                    Installation History
                    <small>View your previous submissions</small>
                </div>
            </div>
            <div class="quick-link-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </div>
        </a>

        <div class="form-section">
            <label class="form-label">Installation Photo</label>
            <div class="photo-card" onclick="choosePhotoSource()">
                <div class="photo-preview" id="photoPreview">
                    <img id="photoImg" alt="Installation photo">
                    <div class="placeholder" id="photoPlaceholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <p>Tap to take photo or upload</p>
                    </div>
                </div>
                <input type="file" accept="image/*" capture="environment" id="fileCamera" onchange="onFileSelected(this)">
                <input type="file" accept="image/*" id="fileGallery" onchange="onFileSelected(this)">
            </div>
        </div>

        <div class="form-section">
            <label class="form-label" for="remarkInput">Remark <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
            <textarea id="remarkInput" placeholder="Notes about the installation..."></textarea>
        </div>
    </div>

    <div class="floating-submit">
        <button id="btnSubmit" onclick="submitJob()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Submit Installation Job
        </button>
    </div>

    <div class="source-overlay" id="sourceOverlay" onclick="if(event.target===this)closeSource()">
        <div class="source-modal">
            <div class="source-handle"></div>
            <button class="source-btn" onclick="pickSource('camera')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                Take Photo
            </button>
            <button class="source-btn" onclick="pickSource('gallery')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Choose from Gallery
            </button>
            <button class="source-btn" onclick="closeSource()" style="color:var(--text-muted);justify-content:center;">Cancel</button>
        </div>
    </div>

    <?php include 'mobile-bottombar.php'; ?>

    <script>
    var selectedFile = null;

    function choosePhotoSource() {
        document.getElementById('sourceOverlay').classList.add('active');
    }
    function closeSource() {
        document.getElementById('sourceOverlay').classList.remove('active');
    }
    function pickSource(type) {
        closeSource();
        if (type === 'camera') document.getElementById('fileCamera').click();
        else document.getElementById('fileGallery').click();
    }

    function onFileSelected(input) {
        if (!input.files || !input.files[0]) return;
        selectedFile = input.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('photoImg');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('photoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(selectedFile);
    }

    function submitJob() {
        if (!selectedFile) {
            Swal.fire({ icon: 'warning', text: 'Please attach an installation photo first.', confirmButtonColor: '#C8102E' });
            return;
        }

        var btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/></svg> Submitting...';

        var formData = new FormData();
        formData.append('action', 'submit');
        formData.append('image', selectedFile);
        formData.append('remark', document.getElementById('remarkInput').value);

        fetch('inst_job_ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Submit Installation Job';
            if (data.error) {
                Swal.fire({ icon: 'error', text: data.error, confirmButtonColor: '#C8102E' });
                return;
            }
            Swal.fire({
                icon: 'success',
                title: 'Submitted!',
                text: 'Pending admin approval.',
                confirmButtonColor: '#C8102E',
                confirmButtonText: 'View History'
            }).then(function() {
                window.location.href = 'inst_history.php';
            });
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Submit Installation Job';
            Swal.fire({ icon: 'error', text: 'Network error. Please try again.', confirmButtonColor: '#C8102E' });
        });
    }
    </script>
</body>
</html>
