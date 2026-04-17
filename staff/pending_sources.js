(function () {
  function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function formatDate(d) {
    if (!d) return '';
    var parts = String(d).split('-');
    if (parts.length !== 3) return String(d);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var m = parseInt(parts[1], 10) - 1;
    return parts[2] + ' ' + (months[m] || parts[1]) + ' ' + parts[0];
  }

  function formatTime(t) {
    if (!t) return '';
    var parts = String(t).split(':');
    if (parts.length < 2) return String(t);
    var h = parseInt(parts[0], 10);
    var m = parts[1];
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12; if (h === 0) h = 12;
    return h + ':' + m + ' ' + ampm;
  }

  function ensureModal() {
    var existing = document.getElementById('pendingSourcesOverlay');
    if (existing) return existing;
    var html = ''
      + '<div class="ps-overlay" id="pendingSourcesOverlay">'
      +   '<div class="ps-modal">'
      +     '<div class="ps-header">'
      +       '<div>'
      +         '<h3 id="psTitle">Pending Purchases</h3>'
      +         '<div class="ps-subtitle" id="psSubtitle"></div>'
      +       '</div>'
      +       '<button class="ps-close" type="button" aria-label="Close" onclick="window.closePendingSources()">'
      +         '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
      +       '</button>'
      +     '</div>'
      +     '<div class="ps-body" id="psBody"></div>'
      +     '<div class="ps-footer" id="psFooter" style="display:none;"><span>Total pending</span><strong id="psTotal">0</strong></div>'
      +   '</div>'
      + '</div>';
    var wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    var node = wrapper.firstChild;
    document.body.appendChild(node);
    node.addEventListener('click', function (e) {
      if (e.target === node) window.closePendingSources();
    });
    return node;
  }

  window.closePendingSources = function () {
    var o = document.getElementById('pendingSourcesOverlay');
    if (o) o.classList.remove('active');
  };

  window.showPendingSources = function (barcode, pdesc) {
    if (!barcode) return;
    var overlay = ensureModal();
    document.getElementById('psTitle').textContent = 'Pending Purchases';
    document.getElementById('psSubtitle').textContent = pdesc || barcode;
    document.getElementById('psFooter').style.display = 'none';
    document.getElementById('psBody').innerHTML = '<div class="ps-loading">Loading...</div>';
    overlay.classList.add('active');

    fetch('pending_sources_ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'barcode=' + encodeURIComponent(barcode)
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.error) {
          document.getElementById('psBody').innerHTML = '<div class="ps-error">' + escHtml(data.error) + '</div>';
          return;
        }
        var sources = (data && data.sources) || [];
        if (sources.length === 0) {
          document.getElementById('psBody').innerHTML = '<div class="ps-empty">No pending purchases found.</div>';
          return;
        }
        var html = '';
        for (var i = 0; i < sources.length; i++) {
          var s = sources[i];
          var dateStr = formatDate(s.SDATE);
          var timeStr = formatTime(s.TTIME);
          html += '<div class="ps-row">'
            +   '<div class="ps-row-top">'
            +     '<span class="ps-salnum">' + escHtml(s.SALNUM) + '</span>'
            +     '<span class="ps-qty">+' + escHtml(s.QTY) + '</span>'
            +   '</div>'
            +   '<div class="ps-meta">'
            +     (dateStr || timeStr ? '<span>' + escHtml(dateStr) + (timeStr ? ', ' + escHtml(timeStr) : '') + '</span>' : '')
            +     (s.NAME ? '<span>By: ' + escHtml(s.NAME) + '</span>' : '')
            +     (s.TXTTO ? '<span>Remark: ' + escHtml(s.TXTTO) + '</span>' : '')
            +   '</div>'
            + '</div>';
        }
        document.getElementById('psBody').innerHTML = html;
        document.getElementById('psTotal').textContent = data.total_qty != null ? data.total_qty : '';
        document.getElementById('psFooter').style.display = 'flex';
      })
      .catch(function () {
        document.getElementById('psBody').innerHTML = '<div class="ps-error">Failed to load pending purchases.</div>';
      });
  };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.closePendingSources();
  });
})();
