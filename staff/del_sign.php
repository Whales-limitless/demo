<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$ordno = $_GET['ordno'] ?? '';
$orderId = intval($_GET['id'] ?? 0);
if ($ordno === '') { header("Location: del_dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capture Signature</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="components.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #C8102E; --primary-dark: #a00d24; --surface: #ffffff; --bg: #f3f4f6; --text: #1a1a1a; --text-muted: #6b7280; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
        h1, h2, h3 { font-family: 'Outfit', sans-serif; }
        .page-header { position: sticky; top: 0; z-index: 100; background: var(--primary); color: #fff; padding: 0 16px; height: 56px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 12px rgba(200,16,46,0.3); }
        .back-btn { display: flex; align-items: center; gap: 4px; background: none; border: none; color: #fff; font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 500; cursor: pointer; padding: 6px 8px; border-radius: 8px; transition: background 0.2s; text-decoration: none; }
        .back-btn:hover { background: rgba(255,255,255,0.15); }
        .back-btn svg { width: 20px; height: 20px; flex-shrink: 0; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 600; }
        .main-content { max-width: 700px; margin: 0 auto; padding: 16px; display: flex; flex-direction: column; align-items: center; }

        .sign-card { background: var(--surface); border-radius: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04); padding: 24px; width: 100%; text-align: center; }
        .sign-card h3 { font-size: 16px; margin-bottom: 4px; }
        .sign-card p { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }

        .canvas-wrap { border: 2px solid #d1d5db; border-radius: 12px; overflow: hidden; margin-bottom: 16px; touch-action: none; background: #fff; }
        .canvas-wrap canvas { display: block; width: 100%; cursor: crosshair; }

        .sign-actions { display: flex; gap: 10px; justify-content: center; }
        .sign-actions button { padding: 12px 24px; border: none; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
        .sign-actions button svg { width: 18px; height: 18px; }
        .btn-clear { background: #e5e7eb; color: var(--text); }
        .btn-clear:hover { background: #d1d5db; }
        .btn-save { background: #16a34a; color: #fff; }
        .btn-save:hover { background: #15803d; }
        .btn-cancel { background: #ef4444; color: #fff; }
        .btn-cancel:hover { background: #dc2626; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <a href="del_vieworder.php?ordno=<?php echo urlencode($ordno); ?>" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
            Back
        </a>
        <span class="page-title">Capture Signature</span>
    </header>

    <div class="main-content">
        <div class="sign-card">
            <h3>Customer Signature</h3>
            <p>Order: <?php echo htmlspecialchars($ordno); ?></p>

            <div class="canvas-wrap" id="canvasWrap">
                <canvas id="signCanvas" width="600" height="200"></canvas>
            </div>

            <div class="sign-actions">
                <button class="btn-clear" onclick="clearCanvas()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    Clear
                </button>
                <button class="btn-save" onclick="saveSignature()" id="btnSave">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Save
                </button>
                <a href="del_vieworder.php?ordno=<?php echo urlencode($ordno); ?>" style="text-decoration:none;">
                    <button type="button" class="btn-cancel">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Cancel
                    </button>
                </a>
            </div>
        </div>
    </div>

    <script>
    var canvas = document.getElementById('signCanvas');
    var ctx = canvas.getContext('2d');
    var isDrawing = false;
    var lastX = 0;
    var lastY = 0;
    var hasDrawn = false;

    // Scale canvas for retina
    function resizeCanvas() {
        var wrap = document.getElementById('canvasWrap');
        var w = wrap.clientWidth;
        var ratio = window.devicePixelRatio || 1;
        canvas.width = w * ratio;
        canvas.height = Math.round((w * 200 / 600) * ratio);
        canvas.style.height = Math.round(w * 200 / 600) + 'px';
        ctx.scale(ratio, ratio);
        ctx.strokeStyle = '#1a1a1a';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }
    resizeCanvas();

    function getPos(e) {
        var rect = canvas.getBoundingClientRect();
        var x, y;
        if (e.touches) {
            x = e.touches[0].clientX - rect.left;
            y = e.touches[0].clientY - rect.top;
        } else {
            x = e.clientX - rect.left;
            y = e.clientY - rect.top;
        }
        return { x: x, y: y };
    }

    function startDraw(e) {
        e.preventDefault();
        isDrawing = true;
        var pos = getPos(e);
        lastX = pos.x;
        lastY = pos.y;
    }

    function draw(e) {
        e.preventDefault();
        if (!isDrawing) return;
        hasDrawn = true;
        var pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastX = pos.x;
        lastY = pos.y;
    }

    function endDraw(e) {
        e.preventDefault();
        isDrawing = false;
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', endDraw);
    canvas.addEventListener('mouseleave', endDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', endDraw);

    function clearCanvas() {
        var ratio = window.devicePixelRatio || 1;
        ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
        hasDrawn = false;
    }

    function saveSignature() {
        if (!hasDrawn) {
            Swal.fire({ icon: 'warning', text: 'Please draw a signature first.', confirmButtonColor: '#C8102E' });
            return;
        }

        var btn = document.getElementById('btnSave');
        btn.disabled = true;

        var imgData = canvas.toDataURL('image/png');

        fetch('del_sign_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=save&ordno=' + encodeURIComponent('<?php echo addslashes($ordno); ?>') + '&img_data=' + encodeURIComponent(imgData)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            if (data.success) {
                Swal.fire({ icon: 'success', text: 'Signature saved successfully.', timer: 1500, showConfirmButton: false }).then(function() {
                    window.location.href = 'del_vieworder.php?ordno=' + encodeURIComponent('<?php echo addslashes($ordno); ?>');
                });
            } else {
                Swal.fire({ icon: 'error', text: data.error || 'Failed to save signature.', confirmButtonColor: '#C8102E' });
            }
        })
        .catch(function() {
            btn.disabled = false;
            Swal.fire({ icon: 'error', text: 'Network error.', confirmButtonColor: '#C8102E' });
        });
    }
    </script>
</body>
</html>
