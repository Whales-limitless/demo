<!-- Close page-body wrapper opened in navbar.php -->
</div>

<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<!-- MOBILE FOOTER -->
<footer class="mobile-footer">
  <div class="footer-inner">
    <a href="./" id="tabCategory">
      <svg class="tab-icon"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      Category
    </a>
    <a href="all_products.php" id="tabProducts">
      <svg class="tab-icon"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Products
    </a>
    <a href="cart.php" id="tabScan">
      <svg class="tab-icon"><path d="M1 3h4v18H1z"/><path d="M7 3h2v18H7z"/><path d="M11 3h1v18h-1z"/><path d="M14 3h2v18h-2z"/><path d="M19 3h4v18h-4z"/></svg>
      Scan
    </a>
    <button class="footer-tab" id="tabInventory" onclick="openInventoryModal()">
      <svg class="tab-icon"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
      Inventory
    </button>
    <a href="account.php" id="tabAccount">
      <svg class="tab-icon"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
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
    'index.php': 'tabCategory',
    '': 'tabCategory',
    'all_products.php': 'tabProducts',
    'cart.php': 'tabScan',
    'account.php': 'tabAccount',
    'staff_stock_take.php': 'tabInventory',
    'staff_stock_loss.php': 'tabInventory'
  };
  var activeTab = tabMap[path];
  if (activeTab) {
    var el = document.getElementById(activeTab);
    if (el) el.classList.add('tab-active');
  }
})();

function openInventoryModal() {
  document.getElementById('invModalOverlay').classList.add('active');
}

function closeInventoryModal(e) {
  if (e && e.target === document.getElementById('invModalOverlay')) {
    document.getElementById('invModalOverlay').classList.remove('active');
  }
}
</script>
