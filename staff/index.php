<?php
session_start();
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

.cache-stats {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}

.stat-box {
  background: #f9fafb;
  border-radius: 10px;
  padding: 14px 12px;
  text-align: center;
}

.stat-num {
  font-family: 'Outfit', sans-serif;
  font-size: 24px;
  font-weight: 700;
  color: var(--primary);
  line-height: 1;
}

.stat-label {
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 600;
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.cache-page-list {
  background: #f9fafb;
  border-radius: 10px;
  padding: 12px 14px;
  margin-bottom: 16px;
}

.cache-page-list h4 {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-bottom: 8px;
}

.page-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.page-tag {
  display: inline-block;
  background: #dbeafe;
  color: #2563eb;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 12px;
}

.cache-message {
  font-size: 13px;
  color: var(--text-muted);
  text-align: center;
  padding: 8px 0;
  line-height: 1.5;
}

.cache-message strong { color: var(--text); }

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

.sync-empty {
  text-align: center;
  padding: 16px;
  color: var(--text-muted);
  font-size: 13px;
}

@media (max-width: 480px) {
  .welcome-card { padding: 32px 20px; }
  .welcome-card h1 { font-size: 22px; }
  .cache-stats { grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
  .stat-num { font-size: 20px; }
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

  <!-- Offline Ready Status -->
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

    <div class="cache-stats">
      <div class="stat-box">
        <div class="stat-num" id="statPages">-</div>
        <div class="stat-label">Pages</div>
      </div>
      <div class="stat-box">
        <div class="stat-num" id="statAssets">-</div>
        <div class="stat-label">Assets</div>
      </div>
      <div class="stat-box">
        <div class="stat-num" id="statSize">-</div>
        <div class="stat-label">Downloaded</div>
      </div>
    </div>

    <div class="cache-page-list" id="cachePageList" style="display:none;">
      <h4>Pages available offline</h4>
      <div class="page-tags" id="pageTags"></div>
    </div>

    <div class="cache-message" id="cacheMessage">
      Loading offline status...
    </div>
  </div>

  <!-- Sync Activity -->
  <div class="sync-section" id="syncSection">
    <div class="sync-header">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        Sync Activity
        <span class="sync-badge pending" id="syncPendingBadge" style="display:none;">0 pending</span>
      </h3>
      <div style="display:flex;gap:8px;align-items:center;">
        <button class="sync-btn" id="syncNowBtn" onclick="manualSync()">Sync Now</button>
      </div>
    </div>
    <div class="sync-list" id="syncList">
      <div class="sync-empty">No sync activity yet. Actions performed offline (photo uploads, signatures, job completions) will appear here.</div>
    </div>
  </div>
</main>

<?php include('mobile-bottombar.php'); ?>

<script>
(function() {
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

  // ==================== CACHE STATS ====================
  function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function friendlyPageName(filename) {
    var names = {
      'index.php': 'Home',
      'login.php': 'Login',
      'category.php': 'Categories',
      'products.php': 'Products',
      'all_products.php': 'All Products',
      'cart.php': 'Cart',
      'confirm.php': 'Confirm Order',
      'account.php': 'Account',
      'del_dashboard.php': 'My Deliveries',
      'del_work.php': 'Upload Photos',
      'del_vieworder.php': 'View Order',
      'del_sign.php': 'Signature',
      'del_history.php': 'Delivery History',
      'del_report.php': 'Delivery Reports',
      'staff_stock_take.php': 'Stock Take',
      'staff_stock_loss.php': 'Stock Loss',
      'offline.html': 'Offline Page'
    };
    return names[filename] || filename;
  }

  function loadCacheStats() {
    if (!('serviceWorker' in navigator) || !navigator.serviceWorker.controller) {
      document.getElementById('cacheMessage').innerHTML = '<strong>Service worker not active.</strong> Refresh the page to enable offline support.';
      return;
    }

    var msgChannel = new MessageChannel();
    msgChannel.port1.onmessage = function(event) {
      var stats = event.data;
      if (!stats) return;

      document.getElementById('statPages').textContent = stats.pages;
      document.getElementById('statAssets').textContent = stats.assets;
      document.getElementById('statSize').textContent = formatSize(stats.totalSize);

      // Show page list
      if (stats.pageList && stats.pageList.length > 0) {
        var tagsEl = document.getElementById('pageTags');
        var html = '';
        var seen = {};
        for (var i = 0; i < stats.pageList.length; i++) {
          var p = stats.pageList[i];
          if (seen[p]) continue;
          seen[p] = true;
          html += '<span class="page-tag">' + escHtml(friendlyPageName(p)) + '</span>';
        }
        tagsEl.innerHTML = html;
        document.getElementById('cachePageList').style.display = '';
      }

      // Update message
      var msg = '';
      if (stats.pages > 0) {
        msg = '<strong>' + stats.pages + ' page' + (stats.pages > 1 ? 's' : '') + '</strong> and <strong>' + stats.assets + ' asset' + (stats.assets > 1 ? 's' : '') + '</strong> (' + formatSize(stats.totalSize) + ') downloaded for offline use.';
        if (stats.pages <= 2) {
          msg += ' Visit more pages while online to make them available offline.';
        }
      } else {
        msg = 'No pages cached yet. Browse the app while online to download pages for offline use.';
      }
      document.getElementById('cacheMessage').innerHTML = msg;
    };

    navigator.serviceWorker.controller.postMessage({ type: 'GET_CACHE_STATS' }, [msgChannel.port2]);
  }

  // Listen for cache updates from SW
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', function(event) {
      if (event.data && event.data.type === 'cache-updated') {
        loadCacheStats();
      }
    });
  }

  // Load stats on page load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { setTimeout(loadCacheStats, 500); });
  } else {
    setTimeout(loadCacheStats, 500);
  }

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
      var statusCls = r.status === 'synced' ? 'synced' : (r.error ? 'error' : 'pending');
      var statusText = r.status === 'synced' ? 'Synced' : (r.error ? 'Error' : 'Pending');
      var timeStr = r.status === 'synced' ? formatSyncDate(r.synced_at) : formatSyncDate(r.created_at);

      html += '<div class="sync-item">';
      html += '<div class="sync-icon ' + ti.cls + '">' + ti.icon + '</div>';
      html += '<div class="sync-info"><div class="sync-desc">' + escHtml(r.description) + '</div><div class="sync-time">' + timeStr + '</div></div>';
      html += '<span class="sync-status ' + statusCls + '">' + statusText + '</span>';
      html += '</div>';
    }

    list.innerHTML = html;
  }

  // Register sync UI callback
  if (typeof OfflineSync !== 'undefined') {
    OfflineSync.onSyncUpdate(function(state, pendingCount, allRecords) {
      renderSyncList(allRecords);
    });

    // Initial load
    OfflineSync.getAll().then(function(records) {
      renderSyncList(records);
    });
  }

  // Manual sync
  window.manualSync = function() {
    if (typeof OfflineSync === 'undefined') return;
    var btn = document.getElementById('syncNowBtn');
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    OfflineSync.syncAll().then(function() {
      btn.disabled = false;
      btn.textContent = 'Sync Now';
    }).catch(function() {
      btn.disabled = false;
      btn.textContent = 'Sync Now';
    });
  };

  function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }
})();
</script>

</body>
</html>
