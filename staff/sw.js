var CACHE_NAME = 'pwstaff-v2';
var OFFLINE_URL = 'offline.html';

// Core assets to cache on install
var APP_SHELL = [
  './offline.html',
  './components.css',
  './manifest.json',
  './icon-192.png',
  './icon-512.png',
  './offline-sync.js',
  './js/qr-scanner.umd.min.js',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'
];

// Pages to pre-cache after install (will cache in background)
var PRE_CACHE_PAGES = [
  './',
  './index.php',
  './login.php',
  './del_dashboard.php',
  './del_history.php',
  './del_report.php',
  './account.php',
  './category.php',
  './all_products.php'
];

// Install: cache core assets
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(APP_SHELL);
    }).then(function() {
      return self.skipWaiting();
    })
  );
});

// Activate: clean old caches, then pre-cache pages in background
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(names) {
      return Promise.all(
        names.filter(function(name) { return name !== CACHE_NAME; })
             .map(function(name) { return caches.delete(name); })
      );
    }).then(function() {
      return self.clients.claim();
    }).then(function() {
      // Pre-cache pages in background (non-blocking)
      return caches.open(CACHE_NAME).then(function(cache) {
        return Promise.all(PRE_CACHE_PAGES.map(function(url) {
          return fetch(url, { credentials: 'same-origin' }).then(function(resp) {
            if (resp.ok) return cache.put(url, resp);
          }).catch(function() { /* ignore failures */ });
        }));
      });
    })
  );
});

// Fetch strategy
self.addEventListener('fetch', function(event) {
  var url = new URL(event.request.url);

  // Skip non-GET (POST uploads handled by offline-sync.js IndexedDB)
  if (event.request.method !== 'GET') return;

  // Skip non-http
  if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

  // For navigation (page loads): network-first, fallback to cache, then offline page
  if (event.request.mode === 'navigate') {
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
          if (cached) return cached;
          // Try matching without query string
          var cleanUrl = url.origin + url.pathname;
          return caches.match(cleanUrl).then(function(cached2) {
            return cached2 || caches.match(OFFLINE_URL);
          });
        });
      })
    );
    return;
  }

  // For AJAX _ajax.php endpoints: network-first, cache fallback
  if (url.pathname.match(/_ajax\.php/)) {
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
    return;
  }

  // For CSS, JS, fonts, images: cache-first
  if (url.pathname.match(/\.(css|js|svg|png|jpg|jpeg|gif|webp|woff2?|ttf|eot)$/) ||
      url.hostname === 'fonts.googleapis.com' ||
      url.hostname === 'fonts.gstatic.com' ||
      url.hostname === 'cdn.jsdelivr.net' ||
      url.hostname === 'cdnjs.cloudflare.com') {
    event.respondWith(
      caches.match(event.request).then(function(cached) {
        var fetchPromise = fetch(event.request).then(function(response) {
          if (response.ok) {
            var clone = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
              cache.put(event.request, clone);
            });
          }
          return response;
        }).catch(function() {
          return null;
        });
        return cached || fetchPromise || new Response('', { status: 503 });
      })
    );
    return;
  }

  // For uploaded images (staff/uploads/): cache-first
  if (url.pathname.indexOf('/uploads/') !== -1) {
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

  // Everything else: network-first with cache fallback
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
        return cached || new Response('', { status: 503 });
      });
    })
  );
});

// Listen for messages from app
self.addEventListener('message', function(event) {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  // Allow app to trigger pre-caching of specific pages
  if (event.data && event.data.type === 'CACHE_PAGES') {
    var pages = event.data.urls || [];
    caches.open(CACHE_NAME).then(function(cache) {
      pages.forEach(function(pageUrl) {
        fetch(pageUrl, { credentials: 'same-origin' }).then(function(resp) {
          if (resp.ok) cache.put(pageUrl, resp);
        }).catch(function() {});
      });
    });
  }
});
