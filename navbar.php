<!-- NAVBAR -->
<nav class="navbar">
  <button class="menu-btn" id="menuBtn" aria-label="Menu">
    <svg class="icon"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
  </button>
  <div class="nav-search">
    <input type="text" placeholder="<?php echo isset($searchPlaceholder) ? $searchPlaceholder : 'Search…'; ?>" id="searchInput">
    <button aria-label="Search">
      <svg class="icon icon-sm"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>
  </div>
  <a href="cart.php" class="cart-btn" aria-label="Cart">
    <svg class="icon"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    <span class="cart-badge" id="cartBadge">0</span>
  </a>
</nav>

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
})();
</script>