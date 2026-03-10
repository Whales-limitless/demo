const CACHE_NAME = 'pwstaff-v6';

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
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
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

// Activate event - clean up old caches
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
    })
  );
});
