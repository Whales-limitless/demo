const CACHE_NAME = 'pwstaff-v7';

// Only pre-cache static assets (no PHP pages - they redirect when not logged in)
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

// Fetch event - network first for pages, cache first for assets
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  // For page navigations: network first, fall back to cache, then offline page
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).then(response => {
        // Only cache non-redirect, successful responses
        if (response.ok && !response.redirected) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, clone);
            notifyClients('cache-updated');
          });
        }
        return response;
      }).catch(() => {
        return caches.match(event.request).then(cached => {
          return cached || caches.match('/staff/offline.html');
        });
      })
    );
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

// Activate event - clean up old caches and claim clients
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Message handler - respond to cache info requests
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

      // Estimate size from content-length header or blob
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

      // Categorize
      if (pathname.endsWith('.php') || request.mode === 'navigate' || response.headers.get('content-type')?.includes('text/html')) {
        pages++;
        // Extract a friendly page name
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

// Notify all clients of cache changes
function notifyClients(type) {
  self.clients.matchAll().then(clients => {
    clients.forEach(client => {
      client.postMessage({ type: type });
    });
  });
}
