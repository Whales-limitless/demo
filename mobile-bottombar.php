<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scrollTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<!-- MOBILE FOOTER -->
<footer class="mobile-footer">
  <div class="footer-inner">
    <a href="products.php">
      <svg class="icon icon-sm"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Products
    </a>
    <a href="./">
      <div class="home-btn">
        <div class="logo-sm">PW</div>
      </div>
    </a>
    <a href="logout.php">
      <svg class="icon icon-sm"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</footer>

<script>
(function(){
  var scrollBtn = document.getElementById('scrollTop');
  if (scrollBtn) {
    window.addEventListener('scroll', function(){
      scrollBtn.classList.toggle('visible', window.scrollY > 200);
    });
  }
})();
</script>
