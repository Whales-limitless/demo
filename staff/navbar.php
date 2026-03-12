<!-- NAVBAR -->
<!-- PWA Meta Tags -->
<link rel="manifest" href="/staff/manifest.json">
<meta name="theme-color" content="#C8102E">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PWSTAFF">
<link rel="apple-touch-icon" href="/staff/icons/icon-152.png">
<script src="offline-sync.js"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/staff/sw.js')
    .then(function(reg) { console.log('PWSTAFF SW registered'); })
    .catch(function(e) { console.warn('SW register failed:', e); });
}
</script>
<?php $navUserType = $_SESSION['user_type'] ?? 'S'; ?>
<nav class="navbar">
  <button class="menu-btn" id="menuBtn" aria-label="Menu">
    <svg class="icon" viewBox="0 0 24 24"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
  </button>
  <?php if ($navUserType === 'A' || $navUserType === 'S'): ?>
  <div class="nav-search" id="navSearch">
    <input type="text" placeholder="Search product name…" id="searchInput" autocomplete="off">
    <button aria-label="Search">
      <svg class="icon icon-sm" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>
  </div>
  <a href="cart.php" class="cart-btn" aria-label="Cart">
    <svg class="icon" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    <span class="cart-badge" id="cartBadge">0</span>
  </a>
  <?php endif; ?>
</nav>

<!-- SEARCH DROPDOWN (outside navbar to avoid stacking context issues) -->
<div class="search-dropdown" id="searchDropdown"></div>

<!-- SIDEBAR -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h3>Menu</h3>
    <button class="sidebar-close" id="sidebarClose">
      <svg class="icon" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <?php $userType = $_SESSION['user_type'] ?? 'S'; ?>
  <ul class="sidebar-nav">
    <li><a href="./" data-page="index.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Home
    </a></li>
    <?php if ($userType === 'A' || $userType === 'S'): ?>
    <li><a href="category.php" data-page="category.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      Categories
    </a></li>
    <li><a href="all_products.php" data-page="all_products.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Products
    </a></li>
    <li><a href="cart.php" data-page="cart.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
      Cart
    </a></li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-section-label">Procurement</div>

    <li><a href="staff_po.php" data-page="staff_po.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Purchase Orders
    </a></li>
    <li><a href="staff_grn.php" data-page="staff_grn.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
      Goods Receiving
    </a></li>

    <div class="sidebar-divider"></div>
    <div class="sidebar-section-label">Inventory</div>

    <li><a href="staff_stock_take.php" data-page="staff_stock_take.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
      Stock Take
    </a></li>
    <li><a href="staff_stock_loss.php" data-page="staff_stock_loss.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      Stock Loss
    </a></li>
    <li><a href="staff_history.php" data-page="staff_history.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Purchase History
    </a></li>
    <?php endif; ?>

    <?php if ($userType === 'A' || $userType === 'D'): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section-label">Delivery</div>

    <li><a href="del_dashboard.php" data-page="del_dashboard.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="2"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      My Deliveries
    </a></li>
    <li><a href="del_history.php" data-page="del_history.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Delivery History
    </a></li>
    <li><a href="del_report.php" data-page="del_report.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Delivery Reports
    </a></li>
    <?php endif; ?>

    <div class="sidebar-divider"></div>

    <li><a href="account.php" data-page="account.php">
      <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Account'); ?>
    </a></li>
    <li><a href="logout.php" style="color:#fca5a5;">
      <svg class="nav-icon" viewBox="0 0 24 24" style="stroke:#fca5a5;"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a></li>
  </ul>
</aside>

<script>
(function(){
  var menuBtn = document.getElementById('menuBtn');
  var sidebar = document.getElementById('sidebar');
  var sOverlay = document.getElementById('sidebarOverlay');
  var sClose = document.getElementById('sidebarClose');

  if (menuBtn && sidebar && sOverlay && sClose) {
    menuBtn.addEventListener('click', function(){ sidebar.classList.add('active'); sOverlay.classList.add('active'); });
    sOverlay.addEventListener('click', function(){ sidebar.classList.remove('active'); sOverlay.classList.remove('active'); });
    sClose.addEventListener('click', function(){ sidebar.classList.remove('active'); sOverlay.classList.remove('active'); });
  }

  // Highlight active sidebar link
  var currentPage = window.location.pathname.split('/').pop() || 'index.php';
  var links = document.querySelectorAll('.sidebar-nav a[data-page]');
  for (var i = 0; i < links.length; i++) {
    if (links[i].getAttribute('data-page') === currentPage) {
      links[i].classList.add('active');
      break;
    }
  }

  // Product search dropdown
  var searchInput = document.getElementById('searchInput');
  var navSearch = document.getElementById('navSearch');
  var dropdown = document.getElementById('searchDropdown');
  var debounceTimer = null;
  var activeXhr = null;

  function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function highlightMatch(text, query) {
    if (!query) return escHtml(text);
    var escaped = escHtml(text);
    var re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return escaped.replace(re, '<mark>$1</mark>');
  }

  function positionDropdown() {
    if (!navSearch) return;
    var rect = navSearch.getBoundingClientRect();
    dropdown.style.top = (rect.bottom + 6) + 'px';
    dropdown.style.left = rect.left + 'px';
    dropdown.style.width = rect.width + 'px';
  }

  function showLoading() {
    dropdown.innerHTML = '<div class="search-empty"><span class="search-spinner"></span> Searching…</div>';
    positionDropdown();
    dropdown.classList.add('active');
  }

  function doProductSearch(q) {
    if (!q || q.length < 1) {
      dropdown.classList.remove('active');
      dropdown.innerHTML = '';
      return;
    }

    if (activeXhr) { activeXhr.abort(); activeXhr = null; }

    showLoading();

    var xhr = new XMLHttpRequest();
    activeXhr = xhr;
    xhr.open('GET', 'product_search_ajax.php?q=' + encodeURIComponent(q));
    xhr.onload = function() {
      activeXhr = null;
      if (xhr.status !== 200) { dropdown.classList.remove('active'); return; }
      try {
        var data = JSON.parse(xhr.responseText);
        renderDropdown(data.products || [], q);
      } catch(e) {
        dropdown.classList.remove('active');
      }
    };
    xhr.onerror = function() { activeXhr = null; dropdown.classList.remove('active'); };
    xhr.send();
  }

  function renderDropdown(products, query) {
    if (products.length === 0) {
      dropdown.innerHTML = '<div class="search-empty">No products found</div>';
      positionDropdown();
      dropdown.classList.add('active');
      return;
    }

    var noImgSvg = '<svg style="width:18px;height:18px;stroke:#9ca3af;fill:none;stroke-width:1.5"><rect x="2" y="2" width="16" height="16" rx="2"/><circle cx="7" cy="8" r="2"/><polyline points="18 14 13 9 4 18"/></svg>';

    var html = products.map(function(p) {
      var imgHtml;
      if (p.image) {
        imgHtml = '<img class="search-result-img" src="/img/' + escHtml(p.image) + '" alt="" loading="lazy">';
      } else {
        imgHtml = '<div class="search-result-img search-no-img">' + noImgSvg + '</div>';
      }

      var stockClass = p.inStock ? 'in-stock' : 'out-of-stock';
      var stockText = p.inStock ? 'Qty: ' + p.qoh : 'Out of Stock';

      var catLink = p.cat_code ? 'products.php?cat=' + encodeURIComponent(p.cat_code) : '#';

      return '<a href="' + catLink + '" class="search-result-item">' +
        imgHtml +
        '<div class="search-result-info">' +
          '<div class="search-result-name">' + highlightMatch(p.name, query) + '</div>' +
          '<div class="search-result-meta">' +
            (p.barcode ? '<span class="search-tag barcode">' + highlightMatch(p.barcode, query) + '</span>' : '') +
            (p.category_name ? '<span class="search-tag cat">' + escHtml(p.category_name) + '</span>' : '') +
          '</div>' +
        '</div>' +
        '<span class="search-stock ' + stockClass + '">' + stockText + '</span>' +
      '</a>';
    }).join('');

    dropdown.innerHTML = html;
    positionDropdown();
    dropdown.classList.add('active');
  }

  // Navigate to all_products.php with search query
  function goToSearch(q) {
    if (q && q.length >= 1) {
      window.location.href = 'all_products.php?q=' + encodeURIComponent(q);
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      var q = this.value.trim();
      clearTimeout(debounceTimer);
      if (q.length < 1) {
        if (activeXhr) { activeXhr.abort(); activeXhr = null; }
        dropdown.classList.remove('active');
        dropdown.innerHTML = '';
        return;
      }
      debounceTimer = setTimeout(function() { doProductSearch(q); }, 250);
    });

    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        dropdown.classList.remove('active');
        goToSearch(this.value.trim());
      }
    });

    // Search button click
    var searchBtn = navSearch.querySelector('button');
    if (searchBtn) {
      searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.classList.remove('active');
        goToSearch(searchInput.value.trim());
      });
    }

    searchInput.addEventListener('focus', function() {
      var q = this.value.trim();
      if (q.length >= 1 && dropdown.innerHTML) {
        positionDropdown();
        dropdown.classList.add('active');
      }
    });

    document.addEventListener('click', function(e) {
      if (!navSearch.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
      }
    });

    window.addEventListener('scroll', function() { if (dropdown.classList.contains('active')) positionDropdown(); }, {passive: true});
    window.addEventListener('resize', function() { if (dropdown.classList.contains('active')) positionDropdown(); });
  }
})();
</script>

<!-- PWA Install Prompt -->
<div id="pwaInstallPrompt" style="display:none;position:fixed;bottom:80px;left:16px;right:16px;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,0.18);padding:20px;z-index:9999;text-align:center;">
  <p style="margin:0 0 14px;color:#1a1a1a;font-weight:600;font-size:15px;">Install PWSTAFF for a better experience!</p>
  <button id="pwaInstallBtn" style="margin:4px;padding:12px 24px;border:none;border-radius:10px;background:#C8102E;color:#fff;font-weight:600;font-size:14px;cursor:pointer;">Install App</button>
  <button id="pwaDismissBtn" style="margin:4px;padding:12px 24px;border:none;border-radius:10px;background:#e5e7eb;color:#1a1a1a;font-weight:600;font-size:14px;cursor:pointer;">Not Now</button>
</div>

<script>
// PWA Install Prompt (Android)
var pwaDeferredPrompt = null;
window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  pwaDeferredPrompt = e;
  document.getElementById('pwaInstallPrompt').style.display = 'block';
});
document.getElementById('pwaInstallBtn').addEventListener('click', function() {
  if (!pwaDeferredPrompt) return;
  document.getElementById('pwaInstallPrompt').style.display = 'none';
  pwaDeferredPrompt.prompt();
  pwaDeferredPrompt.userChoice.then(function() { pwaDeferredPrompt = null; });
});
document.getElementById('pwaDismissBtn').addEventListener('click', function() {
  document.getElementById('pwaInstallPrompt').style.display = 'none';
  pwaDeferredPrompt = null;
});
window.addEventListener('appinstalled', function() {
  document.getElementById('pwaInstallPrompt').style.display = 'none';
});

// iOS Add to Home Screen hint
(function() {
  var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
  var isStandalone = window.navigator.standalone === true;
  if (isIOS && !isStandalone) {
    setTimeout(function() {
      var prompt = document.getElementById('pwaInstallPrompt');
      if (prompt.style.display === 'none') {
        prompt.innerHTML = '<p style="margin:0 0 10px;color:#1a1a1a;font-weight:600;font-size:15px;">Install PWSTAFF on iOS:</p>' +
          '<p style="margin:0 0 6px;color:#6b7280;font-size:14px;">1. Tap the <strong>Share</strong> button <svg style="width:16px;height:16px;vertical-align:middle;" viewBox="0 0 24 24" fill="none" stroke="#C8102E" stroke-width="2"><path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg></p>' +
          '<p style="margin:0 0 14px;color:#6b7280;font-size:14px;">2. Tap <strong>"Add to Home Screen"</strong></p>' +
          '<button onclick="this.parentElement.style.display=\'none\'" style="padding:10px 24px;border:none;border-radius:10px;background:#C8102E;color:#fff;font-weight:600;font-size:14px;cursor:pointer;">Got it!</button>';
        prompt.style.display = 'block';
      }
    }, 3000);
  }
})();
</script>

<!-- Page body wrapper: opened here, closed in mobile-bottombar.php -->
<div class="page-body">
