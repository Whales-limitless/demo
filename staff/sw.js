const CACHE_NAME = 'pwstaff-v10';

// Pre-cache static assets
const urlsToCache = [
  '/staff/components.css',
  '/staff/offline-sync.js',
  '/staff/offline.html',
  '/staff/manifest.json',
  '/staff/js/qr-scanner.umd.min.js',
  '/staff/icons/icon-192.png',
  '/staff/icons/icon-512.png',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11',
  'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap',
];

// Install event - cache static assets only
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('PWSTAFF cache opened');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Fetch event
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  // Skip prefetch requests - let them through without SW interference
  // so that offline-sync.js can cache them directly
  if (event.request.headers.get('X-Prefetch') === 'true') {
    return; // Don't call respondWith - let the fetch go to network directly
  }

  // For page navigations: network first, fall back to cache, then offline page
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).then(response => {
        if (response.ok && !response.redirected) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            // Cache by URL string to ensure consistent matching
            cache.put(event.request.url, clone);
            notifyClients('cache-updated');
          });
        }
        return response;
      }).catch(() => {
        // Offline - try multiple lookup strategies before giving up
        return findCachedPage(event.request.url).then(cached => {
          return cached || caches.match('/staff/offline.html');
        });
      })
    );
    return;
  }

  // For AJAX endpoints: always go to network for fresh data (never serve from cache)
  const url = new URL(event.request.url);
  if (url.pathname.endsWith('_ajax.php')) {
    return;
  }

  // For assets: cache first, then network
  event.respondWith(
    caches.match(event.request).then(response => {
      if (response) return response;
      return fetch(event.request).then(networkResponse => {
        if (networkResponse && networkResponse.ok) {
          const clone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return networkResponse;
      }).catch(() => {
        return new Response('', { status: 503 });
      });
    })
  );
});

// Robust page lookup across all caches with multiple strategies
async function findCachedPage(urlStr) {
  try {
    const url = new URL(urlStr);
    const cache = await caches.open(CACHE_NAME);

    // Strategy 1: Exact URL match in our cache
    let cached = await cache.match(urlStr);
    if (cached) return cached;

    // Strategy 2: Try without query string (base page may have been prefetched)
    if (url.search) {
      cached = await cache.match(url.origin + url.pathname);
      if (cached) return cached;
    }

    // Strategy 3: Search ALL caches for exact URL (covers race conditions with cache migration)
    cached = await caches.match(urlStr);
    if (cached) return cached;

    // Strategy 4: Search ALL caches without query string
    if (url.search) {
      cached = await caches.match(url.origin + url.pathname);
      if (cached) return cached;
    }

    // Strategy 5: Search all caches by iterating keys for partial pathname match
    const allCacheNames = await caches.keys();
    for (const name of allCacheNames) {
      const c = await caches.open(name);
      const keys = await c.keys();
      for (const request of keys) {
        const cachedUrl = new URL(request.url);
        // Match on pathname (ignoring query string differences and origin)
        if (cachedUrl.pathname === url.pathname) {
          const match = await c.match(request);
          if (match) return match;
        }
      }
    }

    return null;
  } catch(e) {
    console.error('findCachedPage error:', e);
    return null;
  }
}

// Activate event - migrate old caches then clean up, and claim clients
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      // Find old pwstaff caches to migrate from (including the fallback 'pwstaff-cache')
      const oldCaches = cacheNames.filter(n => n !== CACHE_NAME && n.indexOf('pwstaff') === 0);
      if (oldCaches.length === 0) return Promise.resolve();

      // Migrate cached pages from old caches into the new one
      return caches.open(CACHE_NAME).then(newCache => {
        return Promise.all(oldCaches.map(oldName => {
          return caches.open(oldName).then(oldCache => {
            return oldCache.keys().then(requests => {
              return Promise.all(requests.map(request => {
                return oldCache.match(request).then(response => {
                  if (response) return newCache.put(request, response);
                });
              }));
            });
          });
        }));
      });
    }).then(() => {
      // Now delete all old caches (including non-pwstaff ones)
      return caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      });
    }).then(() => self.clients.claim())
  );
});

// Message handler
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'GET_CACHE_STATS') {
    getCacheStats().then(stats => {
      event.ports[0].postMessage(stats);
    });
  }
});

// Get cache statistics
async function getCacheStats() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const keys = await cache.keys();

    let pages = 0;
    let assets = 0;
    let totalSize = 0;
    const pageList = [];

    for (const request of keys) {
      const response = await cache.match(request);
      const url = new URL(request.url);
      const pathname = url.pathname;

      let size = 0;
      const cl = response.headers.get('content-length');
      if (cl) {
        size = parseInt(cl, 10);
      } else {
        try {
          const blob = await response.clone().blob();
          size = blob.size;
        } catch(e) { /* ignore */ }
      }
      totalSize += size;

      if (pathname.endsWith('.php') || pathname.endsWith('.html') || response.headers.get('content-type')?.includes('text/html')) {
        pages++;
        const name = pathname.split('/').pop() || 'index';
        pageList.push(name);
      } else {
        assets++;
      }
    }

    return { pages, assets, totalSize, total: keys.length, pageList };
  } catch(e) {
    return { pages: 0, assets: 0, totalSize: 0, total: 0, pageList: [] };
  }
}

function notifyClients(type) {
  self.clients.matchAll().then(clients => {
    clients.forEach(client => {
      client.postMessage({ type: type });
    });
  });
}
