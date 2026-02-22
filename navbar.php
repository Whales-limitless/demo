<!-- NAVBAR -->
<nav class="navbar">
  <button class="menu-btn" id="menuBtn" aria-label="Menu">
    <svg class="icon"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
  </button>
  <div class="nav-search" id="navSearch">
    <input type="text" placeholder="Search product name or barcode…" id="searchInput" autocomplete="off">
    <button aria-label="Search">
      <svg class="icon icon-sm"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>
  </div>
  <a href="cart.php" class="cart-btn" aria-label="Cart">
    <svg class="icon"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    <span class="cart-badge" id="cartBadge">0</span>
  </a>
</nav>

<!-- SEARCH DROPDOWN (outside navbar to avoid stacking context issues) -->
<div class="search-dropdown" id="searchDropdown"></div>

<!-- SIDEBAR -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h3>Menu</h3>
    <button class="sidebar-close" id="sidebarClose">
      <svg class="icon"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <ul class="sidebar-nav">
    <li><a href="./">🏠 Home</a></li>
    <li><a href="./">📦 Categories</a></li>
    <li><a href="all_products.php">🛍️ Products</a></li>
    <li><a href="cart.php">🛒 Cart</a></li>
    <li><a href="staff_stock_take.php">📋 Stock Take</a></li>
    <li><a href="staff_stock_loss.php">⚠️ Stock Loss</a></li>
    <li><a href="#" style="opacity:0.7;font-size:13px;">👤 <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></a></li>
    <li><a href="logout.php" style="color:#fca5a5;">🚪 Logout</a></li>
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

  // Product search dropdown
  var searchInput = document.getElementById('searchInput');
  var navSearch = document.getElementById('navSearch');
  var dropdown = document.getElementById('searchDropdown');
  var debounceTimer = null;
  var activeXhr = null; // track in-flight request so we can abort on new keystroke

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

    // Abort any in-flight request before sending a new one
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

    searchInput.addEventListener('focus', function() {
      var q = this.value.trim();
      if (q.length >= 1 && dropdown.innerHTML) {
        positionDropdown();
        dropdown.classList.add('active');
      }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!navSearch.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
      }
    });

    // Reposition on scroll/resize
    window.addEventListener('scroll', function() { if (dropdown.classList.contains('active')) positionDropdown(); }, {passive: true});
    window.addEventListener('resize', function() { if (dropdown.classList.contains('active')) positionDropdown(); });
  }
})();
</script>
