const CACHE_NAME = 'pwstaff-v3';
const urlsToCache = [
  './',
  'index.php',
  'login.php',
  'components.css',
  'offline-sync.js',
  'offline.html',
  'manifest.json',
  'js/qr-scanner.umd.min.js',
  'del_dashboard.php',
  'del_history.php',
  'del_work.php',
  'del_vieworder.php',
  'del_sign.php',
  'del_report.php',
  'account.php',
  'category.php',
  'all_products.php',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11',
  'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap',
  'https://placehold.co/192x192/C8102E/ffffff?text=PWS',
  'https://placehold.co/512x512/C8102E/ffffff?text=PWS',
];

// Install event - cache files
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('PWSTAFF cache opened');
        return cache.addAll(urlsToCache);
      })
  );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', event => {
  // Skip non-GET requests (POST uploads handled by offline-sync.js)
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        if (response) return response;

        return fetch(event.request).then(networkResponse => {
          // Cache new successful responses
          if (networkResponse && networkResponse.ok) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, responseClone);
            });
          }
          return networkResponse;
        }).catch(() => {
          // If navigation request fails, show offline page
          if (event.request.mode === 'navigate') {
            return caches.match('offline.html');
          }
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
