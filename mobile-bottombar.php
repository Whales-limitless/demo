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
    <a href="#">
      <svg class="icon icon-sm"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Account
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
