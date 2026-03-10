var CACHE_NAME = 'pwstaff-v1';
var OFFLINE_URL = 'offline.html';

// Core app shell to cache
var APP_SHELL = [
  './',
  './index.php',
  './login.php',
  './components.css',
  './offline.html',
  './manifest.json',
  './icon-192.svg',
  './icon-512.svg',
  './offline-sync.js',
  './js/qr-scanner.umd.min.js',
  'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// Pages to cache when visited
var DYNAMIC_PAGES = [
  'category.php', 'products.php', 'all_products.php', 'cart.php',
  'del_dashboard.php', 'del_history.php', 'del_work.php', 'del_vieworder.php',
  'del_sign.php', 'del_report.php', 'account.php',
  'staff_stock_take.php', 'staff_stock_loss.php', 'confirm.php'
];

// Install: cache app shell
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(APP_SHELL);
    }).then(function() {
      return self.skipWaiting();
    })
  );
});

// Activate: clean old caches
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(names) {
      return Promise.all(
        names.filter(function(name) { return name !== CACHE_NAME; })
             .map(function(name) { return caches.delete(name); })
      );
    }).then(function() {
      return self.clients.claim();
    })
  );
});

// Fetch strategy
self.addEventListener('fetch', function(event) {
  var url = new URL(event.request.url);

  // Skip non-GET requests (POST uploads handled by offline-sync.js)
  if (event.request.method !== 'GET') return;

  // Skip chrome-extension and other non-http
  if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

  // For navigation requests: network-first, fallback to cache, then offline page
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).then(function(response) {
        // Cache successful page loads
        if (response.ok) {
          var clone = response.clone();
          caches.open(CACHE_NAME).then(function(cache) {
            cache.put(event.request, clone);
          });
        }
        return response;
      }).catch(function() {
        return caches.match(event.request).then(function(cached) {
          return cached || caches.match(OFFLINE_URL);
        });
      })
    );
    return;
  }

  // For CSS, JS, fonts, images: cache-first, fallback to network
  if (url.pathname.match(/\.(css|js|svg|png|jpg|jpeg|gif|webp|woff2?|ttf|eot)$/) ||
      url.hostname === 'fonts.googleapis.com' ||
      url.hostname === 'fonts.gstatic.com' ||
      url.hostname === 'cdn.jsdelivr.net' ||
      url.hostname === 'cdnjs.cloudflare.com') {
    event.respondWith(
      caches.match(event.request).then(function(cached) {
        if (cached) return cached;
        return fetch(event.request).then(function(response) {
          if (response.ok) {
            var clone = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
              cache.put(event.request, clone);
            });
          }
          return response;
        }).catch(function() {
          return new Response('', { status: 503 });
        });
      })
    );
    return;
  }

  // For AJAX/API requests: network-first
  event.respondWith(
    fetch(event.request).then(function(response) {
      if (response.ok) {
        var clone = response.clone();
        caches.open(CACHE_NAME).then(function(cache) {
          cache.put(event.request, clone);
        });
      }
      return response;
    }).catch(function() {
      return caches.match(event.request).then(function(cached) {
        return cached || new Response(JSON.stringify({ error: 'You are offline.' }), {
          headers: { 'Content-Type': 'application/json' }
        });
      });
    })
  );
});

// Listen for sync events from offline-sync.js
self.addEventListener('message', function(event) {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
