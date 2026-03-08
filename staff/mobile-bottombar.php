<!-- Close page-body wrapper opened in navbar.php -->
</div>

<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<!-- MOBILE FOOTER -->
<?php $footerUserType = $_SESSION['user_type'] ?? 'S'; ?>
<footer class="mobile-footer">
  <div class="footer-inner">
    <?php if ($footerUserType === 'A' || $footerUserType === 'S'): ?>
    <a href="category.php" id="tabCategory">
      <svg class="tab-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      Category
    </a>
    <a href="all_products.php" id="tabProducts">
      <svg class="tab-icon" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Products
    </a>
    <!-- Scan tab hidden per request -->
    <button class="footer-tab" id="tabScan" onclick="openScanModal()" style="display:none;">
      <svg class="tab-icon" viewBox="0 0 24 24"><path d="M1 3h4v18H1z"/><path d="M7 3h2v18H7z"/><path d="M11 3h1v18h-1z"/><path d="M14 3h2v18h-2z"/><path d="M19 3h4v18h-4z"/></svg>
      Scan
    </button>
    <button class="footer-tab" id="tabInventory" onclick="openInventoryModal()">
      <svg class="tab-icon" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
      Inventory
    </button>
    <?php endif; ?>
    <?php if ($footerUserType === 'A' || $footerUserType === 'D'): ?>
    <button class="footer-tab" id="tabDelivery" onclick="openDeliveryModal()">
      <svg class="tab-icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      Delivery
    </button>
    <?php endif; ?>
    <a href="account.php" id="tabAccount">
      <svg class="tab-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Account
    </a>
  </div>
</footer>

<!-- INVENTORY MODAL -->
<div class="inv-modal-overlay" id="invModalOverlay" onclick="closeInventoryModal(event)">
  <div class="inv-modal" onclick="event.stopPropagation()">
    <div class="inv-modal-handle"></div>
    <div class="inv-modal-title">Inventory</div>
    <div class="inv-modal-buttons">
      <a href="staff_stock_take.php" class="inv-modal-btn">
        <div class="inv-icon stock-take">
          <svg><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
        </div>
        <div class="inv-label">
          Stock Take
          <small>Count and verify inventory</small>
        </div>
      </a>
      <a href="staff_stock_loss.php" class="inv-modal-btn">
        <div class="inv-icon stock-loss">
          <svg><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="inv-label">
          Stock Loss
          <small>Record damaged or lost stock</small>
        </div>
      </a>
    </div>
  </div>
</div>

<!-- DELIVERY MODAL -->
<div class="inv-modal-overlay" id="delModalOverlay" onclick="closeDeliveryModal(event)">
  <div class="inv-modal" onclick="event.stopPropagation()">
    <div class="inv-modal-handle"></div>
    <div class="inv-modal-title">Delivery</div>
    <div class="inv-modal-buttons">
      <a href="del_dashboard.php" class="inv-modal-btn">
        <div class="inv-icon stock-take">
          <svg><rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        </div>
        <div class="inv-label">
          My Deliveries
          <small>View assigned deliveries</small>
        </div>
      </a>
      <a href="del_history.php" class="inv-modal-btn">
        <div class="inv-icon stock-loss">
          <svg><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="inv-label">
          Delivery History
          <small>View completed deliveries</small>
        </div>
      </a>
      <a href="del_report.php" class="inv-modal-btn">
        <div class="inv-icon" style="background:#dbeafe;color:#2563eb;">
          <svg><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </div>
        <div class="inv-label">
          Delivery Reports
          <small>View your delivery reports</small>
        </div>
      </a>
    </div>
  </div>
</div>

<!-- SCAN MODAL -->
<div class="scan-modal-overlay" id="scanModalOverlay">
  <div class="scan-modal-header">
    <h3>Scan QR Code</h3>
    <button class="scan-close-btn" onclick="closeScanModal()">
      <svg style="width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="scan-body">
    <div class="scan-viewfinder" id="scanViewfinder">
      <video id="scanVideo" muted playsinline></video>
      <div class="scan-line"></div>
      <div class="scan-corners-bottom"></div>
    </div>
    <div class="scan-hint" id="scanHint">Point your camera at a QR code</div>
    <div class="scan-error" id="scanError"></div>
    <div class="scan-result" id="scanResult">
      <div class="scan-result-text" id="scanResultText"></div>
      <div class="scan-result-actions">
        <button class="scan-result-btn primary" id="scanGoBtn" onclick="goToScannedUrl()">Open Link</button>
        <button class="scan-result-btn secondary" onclick="scanAgain()">Scan Again</button>
      </div>
    </div>
  </div>
</div>

<!-- QR Scanner library: Nimiq qr-scanner (self-hosted, 60KB vs 375KB html5-qrcode) -->
<script src="js/qr-scanner.umd.min.js"></script>

<script>
(function(){
  // Scroll to top
  var scrollBtn = document.getElementById('scrollTop');
  if (scrollBtn) {
    window.addEventListener('scroll', function(){
      scrollBtn.classList.toggle('visible', window.scrollY > 200);
    });
  }

  // Highlight active bottom tab based on current page
  var path = window.location.pathname.split('/').pop() || 'index.php';
  var tabMap = {
    'category.php': 'tabCategory',
    'products.php': 'tabCategory',
    'all_products.php': 'tabProducts',
    'account.php': 'tabAccount',
    'staff_stock_take.php': 'tabInventory',
    'staff_stock_loss.php': 'tabInventory',
    'del_dashboard.php': 'tabDelivery',
    'del_history.php': 'tabDelivery',
    'del_report.php': 'tabDelivery',
    'del_work.php': 'tabDelivery',
    'del_vieworder.php': 'tabDelivery',
    'del_sign.php': 'tabDelivery'
  };
  var activeTab = tabMap[path];
  if (activeTab) {
    var el = document.getElementById(activeTab);
    if (el) el.classList.add('tab-active');
  }
})();

// ==================== DELIVERY MODAL ====================
function openDeliveryModal() {
  document.getElementById('delModalOverlay').classList.add('active');
}

function closeDeliveryModal(e) {
  if (e && e.target === document.getElementById('delModalOverlay')) {
    document.getElementById('delModalOverlay').classList.remove('active');
  }
}

// ==================== INVENTORY MODAL ====================
function openInventoryModal() {
  document.getElementById('invModalOverlay').classList.add('active');
}

function closeInventoryModal(e) {
  if (e && e.target === document.getElementById('invModalOverlay')) {
    document.getElementById('invModalOverlay').classList.remove('active');
  }
}

// ==================== QR SCAN MODAL (Nimiq qr-scanner) ====================
var qrScanner = null;
var lastScannedText = '';

function showScanError(msg) {
  document.getElementById('scanHint').style.display = 'none';
  var errorEl = document.getElementById('scanError');
  errorEl.textContent = msg;
  errorEl.classList.add('active');
}

function openScanModal() {
  // Check secure context (camera API requires HTTPS or localhost)
  if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    alert('QR scanning requires HTTPS. Please access this site over HTTPS to use the scanner.');
    return;
  }

  // Check if library loaded
  if (typeof QrScanner === 'undefined') {
    alert('QR scanner library failed to load. Please check your connection and refresh the page.');
    return;
  }

  var overlay = document.getElementById('scanModalOverlay');
  overlay.classList.add('active');
  document.getElementById('scanResult').classList.remove('active');
  document.getElementById('scanError').classList.remove('active');
  document.getElementById('scanHint').style.display = '';
  startScanner();
}

function closeScanModal() {
  document.getElementById('scanModalOverlay').classList.remove('active');
  stopScanner();
}

function startScanner() {
  var videoEl = document.getElementById('scanVideo');

  try {
    if (!qrScanner) {
      qrScanner = new QrScanner(
        videoEl,
        function(result) {
          var decodedText = result.data || result;
          lastScannedText = decodedText;

          // Pause scanner on success
          qrScanner.pause();

          // Show result
          document.getElementById('scanHint').style.display = 'none';
          document.getElementById('scanError').classList.remove('active');
          document.getElementById('scanResultText').textContent = decodedText;
          document.getElementById('scanResult').classList.add('active');

          // Update button text based on content type
          var goBtn = document.getElementById('scanGoBtn');
          goBtn.textContent = isValidUrl(decodedText) ? 'Open Link' : 'Copy';
        },
        {
          preferredCamera: 'environment',
          maxScansPerSecond: 10,
          returnDetailedScanResult: true,
          highlightScanRegion: false,
          highlightCodeOutline: false
        }
      );
    }
  } catch(e) {
    showScanError('Scanner failed to initialize. Please refresh and try again.');
    return;
  }

  qrScanner.start().catch(function(err) {
    var msg = String(err);
    if (msg.indexOf('not allowed') !== -1 || msg.indexOf('NotAllowed') !== -1 || msg.indexOf('Permission') !== -1) {
      showScanError('Camera access denied. Please allow camera permission in your browser settings and try again.');
    } else if (msg.indexOf('not found') !== -1 || msg.indexOf('NotFound') !== -1) {
      showScanError('No camera found on this device.');
    } else if (msg.indexOf('NotReadable') !== -1 || msg.indexOf('in use') !== -1) {
      showScanError('Camera is in use by another app. Please close other camera apps and try again.');
    } else {
      showScanError('Could not start camera. Please ensure camera permission is allowed.');
    }
  });
}

function stopScanner() {
  if (qrScanner) {
    try { qrScanner.stop(); } catch(e) {}
  }
}

function isValidUrl(text) {
  try {
    var url = new URL(text);
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch(e) {
    return false;
  }
}

function goToScannedUrl() {
  if (isValidUrl(lastScannedText)) {
    // Open in new tab for safety (prevents open redirect leaving the app)
    window.open(lastScannedText, '_blank', 'noopener,noreferrer');
  } else {
    // Copy non-URL text to clipboard
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(lastScannedText).then(function() {
        document.getElementById('scanGoBtn').textContent = 'Copied!';
        setTimeout(function() { document.getElementById('scanGoBtn').textContent = 'Copy'; }, 1500);
      }).catch(function() {
        fallbackCopy(lastScannedText);
      });
    } else {
      fallbackCopy(lastScannedText);
    }
  }
}

function fallbackCopy(text) {
  var ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); } catch(e) {}
  document.body.removeChild(ta);
  document.getElementById('scanGoBtn').textContent = 'Copied!';
  setTimeout(function() { document.getElementById('scanGoBtn').textContent = 'Copy'; }, 1500);
}

function scanAgain() {
  lastScannedText = '';
  document.getElementById('scanResult').classList.remove('active');
  document.getElementById('scanHint').style.display = '';

  if (qrScanner) {
    qrScanner.start().catch(function() {
      showScanError('Could not restart camera. Please close and try again.');
    });
  }
}

// Release camera on page unload or tab switch
window.addEventListener('beforeunload', function() { stopScanner(); });
</script>
