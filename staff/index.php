<?php
require_once __DIR__ . '/session_security.php';
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Staff');
$indexType = $_SESSION['user_type'] ?? 'S';
$portalLabel = 'staff';
if ($indexType === 'A') $portalLabel = 'admin';
elseif ($indexType === 'D') $portalLabel = 'delivery';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Home</title>
<!-- PWA Meta Tags (must be in <head> for install prompt) -->
<link rel="manifest" href="/staff/manifest.json">
<meta name="theme-color" content="#C8102E">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PWSTAFF">
<link rel="apple-touch-icon" href="/staff/icons/icon-152.png">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="components.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --primary: #C8102E;
  --primary-dark: #a00d24;
  --surface: #ffffff;
  --bg: #f3f4f6;
  --text: #1a1a1a;
  --text-muted: #6b7280;
  --radius: 14px;
  --shadow-lg: 0 8px 30px rgba(0,0,0,0.12);
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

.main { max-width: 960px; margin: 0 auto; padding: 24px 16px 90px; }

.welcome-card {
  background: var(--surface);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  padding: 40px 32px;
  text-align: center;
  animation: fadeUp 0.4s ease both;
}

.welcome-card h1 {
  font-family: 'Outfit', sans-serif;
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 8px;
}

.welcome-card .user-name { color: var(--primary); }
.welcome-card p { color: var(--text-muted); font-size: 15px; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

/* Offline Status Card */
.offline-status-card {
  background: var(--surface);
  border-radius: var(--radius);
  box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
  padding: 20px;
  margin-top: 16px;
  animation: fadeUp 0.5s ease both;
}

.offline-status-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.offline-status-header h3 {
  font-family: 'Outfit', sans-serif;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.offline-status-header h3 svg { width: 20px; height: 20px; }

.online-indicator {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 16px;
}
.online-indicator.online { background: #dcfce7; color: #16a34a; }
.online-indicator.offline { background: #fee2e2; color: #dc2626; }
.online-indicator .dot { width: 8px; height: 8px; border-radius: 50%; }
.online-indicator.online .dot { background: #16a34a; }
.online-indicator.offline .dot { background: #dc2626; }

/* Download for Offline */
.download-section {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.download-btn {
  width: 100%;
  padding: 14px 20px;
  border: none;
  border-radius: 12px;
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: var(--primary);
  color: #fff;
  transition: all 0.2s;
}
.download-btn:hover { background: var(--primary-dark); }
.download-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.download-btn svg { width: 20px; height: 20px; }

.download-progress {
  margin-top: 12px;
  display: none;
}

.progress-bar-wrap {
  background: #e5e7eb;
  border-radius: 8px;
  height: 8px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-bar-fill {
  background: var(--primary);
  height: 100%;
  border-radius: 8px;
  width: 0%;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  color: var(--text-muted);
  text-align: center;
}

.download-result {
  margin-top: 10px;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  text-align: center;
  display: none;
}
.download-result.success { background: #dcfce7; color: #16a34a; display: block; }
.download-result.error { background: #fee2e2; color: #dc2626; display: block; }

.last-download {
  font-size: 12px;
  color: var(--text-muted);
  text-align: center;
  margin-top: 8px;
}

/* Sync History Section */
.sync-section {
  background: var(--surface);
  border-radius: var(--radius);
  box-shadow: 0 1px 4px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
  padding: 20px;
  margin-top: 16px;
  animation: fadeUp 0.6s ease both;
}

.sync-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.sync-header h3 {
  font-family: 'Outfit', sans-serif;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.sync-header h3 svg { width: 18px; height: 18px; }

.sync-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 10px;
  border-radius: 12px;
}
.sync-badge.pending { background: #fef3c7; color: #92400e; }

.sync-btn {
  padding: 6px 14px;
  border: none;
  border-radius: 8px;
  background: #3b82f6;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.sync-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.sync-list {
  background: #f9fafb;
  border-radius: 10px;
  overflow: hidden;
}

.sync-item {
  padding: 10px 14px;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
}
.sync-item:last-child { border-bottom: none; }

.sync-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.sync-icon svg { width: 16px; height: 16px; }
.sync-icon.photo { background: #dbeafe; color: #2563eb; }
.sync-icon.install { background: #fef3c7; color: #d97706; }
.sync-icon.signature { background: #f3e8ff; color: #7c3aed; }
.sync-icon.done { background: #dcfce7; color: #16a34a; }

.sync-info { flex: 1; min-width: 0; }
.sync-desc { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sync-time { font-size: 11px; color: var(--text-muted); }

.sync-status {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 8px;
  white-space: nowrap;
}
.sync-status.pending { background: #fef3c7; color: #92400e; }
.sync-status.synced { background: #dcfce7; color: #16a34a; }
.sync-status.error { background: #fee2e2; color: #dc2626; }

.sync-error {
  font-size: 11px;
  color: #dc2626;
  margin-top: 2px;
  word-break: break-word;
}

.sync-empty {
  text-align: center;
  padding: 16px;
  color: var(--text-muted);
  font-size: 13px;
}

.sync-error-banner {
  background: #fee2e2;
  border: 1px solid #fca5a5;
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 12px;
  display: none;
}
.sync-error-banner.visible { display: block; }
.sync-error-banner .error-title {
  font-size: 13px;
  font-weight: 700;
  color: #dc2626;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.sync-error-banner .error-title svg { width: 16px; height: 16px; flex-shrink: 0; }
.sync-error-banner .error-msg {
  font-size: 12px;
  color: #991b1b;
  word-break: break-word;
  line-height: 1.4;
}

.sync-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.retry-failed-btn {
  padding: 6px 14px;
  border: none;
  border-radius: 8px;
  background: #f59e0b;
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: none;
}
.retry-failed-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.retry-failed-btn.visible { display: inline-block; }

.sync-badge.failed { background: #fee2e2; color: #dc2626; }

@media (max-width: 480px) {
  .welcome-card { padding: 32px 20px; }
  .welcome-card h1 { font-size: 22px; }
}
</style>
</head>
<body>

<?php include('navbar.php'); ?>

<main class="main">
  <div class="welcome-card">
    <h1>Welcome, <span class="user-name"><?php echo $userName; ?></span></h1>
    <p>You are logged in as <?php echo $portalLabel; ?>.</p>
  </div>

  <?php if ($indexType === 'D' || $indexType === 'A'): ?>
  <!-- Offline Ready Status (delivery users only) -->
  <div class="offline-status-card" id="offlineStatusCard">
    <div class="offline-status-header">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Offline Ready
      </h3>
      <span class="online-indicator" id="onlineIndicator">
        <span class="dot"></span>
        <span id="onlineText">Checking...</span>
      </span>
    </div>

    <div class="download-section">
      <button class="download-btn" id="downloadBtn" onclick="startDownload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download All for Offline
      </button>
      <div class="download-progress" id="downloadProgress">
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" id="progressFill"></div>
        </div>
        <div class="progress-text" id="progressText">Preparing...</div>
      </div>
      <div class="download-result" id="downloadResult"></div>
      <div class="last-download" id="lastDownload"></div>
    </div>
  </div>

  <!-- Sync Activity (delivery users only) -->
  <div class="sync-section" id="syncSection">
    <div class="sync-header">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        Sync Activity
        <span class="sync-badge pending" id="syncPendingBadge" style="display:none;">0 pending</span>
        <span class="sync-badge failed" id="syncFailedBadge" style="display:none;">0 failed</span>
      </h3>
      <div class="sync-actions">
        <button class="retry-failed-btn" id="retryFailedBtn" onclick="retryFailed()">Retry Failed</button>
        <button class="sync-btn" id="syncNowBtn" onclick="manualSync()">Sync Now</button>
      </div>
    </div>
    <div class="sync-error-banner" id="syncErrorBanner">
      <div class="error-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span id="syncErrorTitle">Sync Error</span>
      </div>
      <div class="error-msg" id="syncErrorMsg"></div>
    </div>
    <div class="sync-list" id="syncList">
      <div class="sync-empty">No sync activity yet. Actions performed offline (photo uploads, signatures, job completions) will appear here.</div>
    </div>
  </div>
  <?php endif; ?>
</main>

<?php include('mobile-bottombar.php'); ?>

<script>
(function() {
  <?php if ($indexType === 'D' || $indexType === 'A'): ?>
  // ==================== ONLINE/OFFLINE INDICATOR ====================
  function updateOnlineIndicator() {
    var ind = document.getElementById('onlineIndicator');
    var txt = document.getElementById('onlineText');
    if (navigator.onLine) {
      ind.className = 'online-indicator online';
      txt.textContent = 'Online';
    } else {
      ind.className = 'online-indicator offline';
      txt.textContent = 'Offline';
    }
  }
  updateOnlineIndicator();
  window.addEventListener('online', updateOnlineIndicator);
  window.addEventListener('offline', updateOnlineIndicator);

  // ==================== SYNC ACTIVITY TABLE ====================
  var typeIcons = {
    photo_upload: { cls: 'photo', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>' },
    install_upload: { cls: 'install', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>' },
    signature: { cls: 'signature', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.83 2.83 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>' },
    job_done: { cls: 'done', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' }
  };

  function formatSyncDate(iso) {
    if (!iso) return '';
    var d = new Date(iso);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
  }

  function renderSyncList(records) {
    var list = document.getElementById('syncList');
    var badge = document.getElementById('syncPendingBadge');

    if (!records || records.length === 0) {
      list.innerHTML = '<div class="sync-empty">No sync activity yet. Actions performed offline (photo uploads, signatures, job completions) will appear here.</div>';
      badge.style.display = 'none';
      return;
    }

    var pending = records.filter(function(r) { return r.status === 'pending'; });
    if (pending.length > 0) {
      badge.style.display = 'inline';
      badge.textContent = pending.length + ' pending';
    } else {
      badge.style.display = 'none';
    }

    records.sort(function(a, b) { return b.id - a.id; });

    var html = '';
    var shown = Math.min(records.length, 20);
    for (var i = 0; i < shown; i++) {
      var r = records[i];
      var ti = typeIcons[r.type] || typeIcons.photo_upload;
      var statusCls = r.status === 'synced' ? 'synced' : (r.status === 'failed' ? 'error' : (r.error ? 'error' : 'pending'));
      var statusText = r.status === 'synced' ? 'Synced' : (r.status === 'failed' ? 'Failed' : (r.error ? 'Retrying' : 'Pending'));
      var timeStr = r.status === 'synced' ? formatSyncDate(r.synced_at) : formatSyncDate(r.created_at);

      html += '<div class="sync-item">';
      html += '<div class="sync-icon ' + ti.cls + '">' + ti.icon + '</div>';
      html += '<div class="sync-info"><div class="sync-desc">' + escHtml(r.description) + '</div><div class="sync-time">' + timeStr + '</div>';
      if (r.error) {
        html += '<div class="sync-error">' + escHtml(r.error) + (r.retries ? ' (attempt ' + r.retries + '/3)' : '') + '</div>';
      }
      html += '</div>';
      html += '<span class="sync-status ' + statusCls + '">' + statusText + '</span>';
      html += '</div>';
    }

    list.innerHTML = html;
  }

  // Show/hide sync error banner
  function showSyncError(errorMsg) {
    var banner = document.getElementById('syncErrorBanner');
    var msgEl = document.getElementById('syncErrorMsg');
    if (errorMsg) {
      msgEl.textContent = errorMsg;
      banner.classList.add('visible');
    } else {
      banner.classList.remove('visible');
      msgEl.textContent = '';
    }
  }

  // Register sync UI callback
  if (typeof OfflineSync !== 'undefined') {
    OfflineSync.onSyncUpdate(function(state, pendingCount, allRecords, syncError, failedCount) {
      renderSyncList(allRecords);

      // Show/hide failed badge and retry button
      var failedBadge = document.getElementById('syncFailedBadge');
      var retryBtn = document.getElementById('retryFailedBtn');
      if (failedCount > 0) {
        failedBadge.style.display = 'inline';
        failedBadge.textContent = failedCount + ' failed';
        retryBtn.classList.add('visible');
      } else {
        failedBadge.style.display = 'none';
        retryBtn.classList.remove('visible');
      }

      // Show sync error banner
      if (state === 'error' && syncError) {
        showSyncError(syncError);
      } else if (state === 'done') {
        showSyncError(null);
      }
    });

    // Initial load
    OfflineSync.getAll().then(function(records) {
      renderSyncList(records);
      // Check for failed records on initial load
      var failedCount = records.filter(function(r) { return r.status === 'failed'; }).length;
      var failedBadge = document.getElementById('syncFailedBadge');
      var retryBtn = document.getElementById('retryFailedBtn');
      if (failedCount > 0) {
        failedBadge.style.display = 'inline';
        failedBadge.textContent = failedCount + ' failed';
        retryBtn.classList.add('visible');
      }
      // Show last error if any
      var lastErr = OfflineSync.getLastError();
      if (lastErr) showSyncError(lastErr);
    });
  }

  // Manual sync
  window.manualSync = function() {
    if (typeof OfflineSync === 'undefined') return;
    var btn = document.getElementById('syncNowBtn');
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    showSyncError(null); // Clear previous errors
    OfflineSync.syncAll().then(function() {
      btn.disabled = false;
      btn.textContent = 'Sync Now';
    }).catch(function() {
      btn.disabled = false;
      btn.textContent = 'Sync Now';
    });
  };

  // Retry failed records
  window.retryFailed = function() {
    if (typeof OfflineSync === 'undefined') return;
    var btn = document.getElementById('retryFailedBtn');
    btn.disabled = true;
    btn.textContent = 'Retrying...';
    showSyncError(null);
    OfflineSync.retryFailed().then(function(count) {
      btn.disabled = false;
      btn.textContent = 'Retry Failed';
      if (count > 0) {
        // Immediately trigger sync after resetting failed records
        OfflineSync.syncAll();
      }
    }).catch(function() {
      btn.disabled = false;
      btn.textContent = 'Retry Failed';
    });
  };

  function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  // ==================== DOWNLOAD FOR OFFLINE ====================
  var userType = '<?php echo $indexType; ?>';
  var downloading = false;

  // Show last download time
  if (typeof OfflineSync !== 'undefined') {
    OfflineSync.getData('delivery_data').then(function(record) {
      if (record && record.saved_at) {
        var d = new Date(record.saved_at);
        var timeStr = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('lastDownload').textContent = 'Last downloaded: ' + timeStr;
        if (record.data && record.data.order_count !== undefined) {
          document.getElementById('lastDownload').textContent += ' (' + record.data.order_count + ' orders)';
        }
      }
    });
  }

  window.startDownload = function() {
    if (downloading) return;
    if (!navigator.onLine) {
      document.getElementById('downloadResult').className = 'download-result error';
      document.getElementById('downloadResult').textContent = 'You must be online to download data for offline use.';
      return;
    }
    if (typeof OfflineSync === 'undefined' || !OfflineSync.downloadAll) return;

    downloading = true;
    var btn = document.getElementById('downloadBtn');
    var progress = document.getElementById('downloadProgress');
    var fill = document.getElementById('progressFill');
    var text = document.getElementById('progressText');
    var result = document.getElementById('downloadResult');

    btn.disabled = true;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;animation:spin 1s linear infinite"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Downloading...';
    progress.style.display = 'block';
    result.className = 'download-result';
    result.style.display = 'none';
    fill.style.width = '0%';
    text.textContent = 'Preparing...';

    OfflineSync.downloadAll(userType, function(state, completed, total, message) {
      if (state === 'progress' || state === 'start') {
        var pct = total > 0 ? Math.round((completed / total) * 100) : 0;
        fill.style.width = pct + '%';
        text.textContent = message;
      } else if (state === 'complete') {
        fill.style.width = '100%';
        text.textContent = 'Complete!';
      } else if (state === 'error') {
        text.textContent = message;
      }
    }).then(function(res) {
      downloading = false;
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download All for Offline';

      var totalPhp = res.totalPages || 0;
      var failedPages = res.failedPages || 0;
      if (failedPages > 0 && totalPhp === 0) {
        result.className = 'download-result error';
      } else if (failedPages > 0) {
        result.className = 'download-result error';
      } else {
        result.className = 'download-result success';
      }
      var msg = 'Downloaded ' + totalPhp + ' pages';
      if (res.data && res.data.order_count !== undefined) {
        msg += ', ' + res.data.order_count + ' delivery orders with items';
      }
      msg += ' for offline use.';
      if (failedPages > 0) {
        msg += ' WARNING: ' + failedPages + ' pages failed to cache (session may have expired or pages returned errors). Try downloading again.';
      }
      result.textContent = msg;

      // Update last download time
      var now = new Date();
      var timeStr = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
      document.getElementById('lastDownload').textContent = 'Last downloaded: ' + timeStr;
      if (res.data && res.data.order_count !== undefined) {
        document.getElementById('lastDownload').textContent += ' (' + res.data.order_count + ' orders)';
      }

      setTimeout(function() { progress.style.display = 'none'; }, 2000);
    }).catch(function(err) {
      downloading = false;
      btn.disabled = false;
      btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download All for Offline';

      result.className = 'download-result error';
      result.textContent = 'Download failed: ' + (err.message || 'Unknown error');
    });
  };

  // Auto-download on first visit (if no data cached yet)
  // Wait for SW to be ready so cache writes work properly
  function waitForSWReady() {
    if (!('serviceWorker' in navigator)) return Promise.resolve();
    return navigator.serviceWorker.ready.then(function() {
      // Give SW a moment to claim the client
      return new Promise(function(resolve) { setTimeout(resolve, 500); });
    });
  }

  if (typeof OfflineSync !== 'undefined' && navigator.onLine) {
    waitForSWReady().then(function() {
      return OfflineSync.getData('delivery_data');
    }).then(function(record) {
      if (!record) {
        // First visit - auto-download
        window.startDownload();
      }
    });
  }
  <?php endif; ?>
})();
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

</body>
</html>
