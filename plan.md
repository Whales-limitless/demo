# PWA "Add to Home Screen" Implementation Plan

## What is needed

To enable "Add to Home Screen" on iOS and the install prompt on Android, the staff app needs to become a **Progressive Web App (PWA)**. This requires 3 things:

---

### 1. Web App Manifest (`staff/manifest.json`)
A JSON file that tells the browser this is an installable app.

```json
{
  "name": "Parkway Inventory",
  "short_name": "PW",
  "start_url": "/staff/login.php",
  "display": "standalone",
  "background_color": "#C8102E",
  "theme_color": "#C8102E",
  "icons": [
    { "src": "icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

---

### 2. App Icons (`staff/icons/`)
Two PNG icon files — red background with white "PW" text:

| File | Size | Purpose |
|------|------|---------|
| `icon-192.png` | 192x192 | Android home screen / manifest requirement |
| `icon-512.png` | 512x512 | Android splash screen / manifest requirement |
| `apple-touch-icon.png` | 180x180 | iOS home screen icon |

These will be generated as simple red squares with "PW" text using PHP GD (already available on most servers), or as inline SVG-based icons.

---

### 3. Service Worker (`staff/sw.js`)
A minimal service worker is **required by Android Chrome** to show the install prompt. iOS doesn't need it but benefits from offline caching. A basic one that just caches the login page shell:

```js
self.addEventListener('install', e => self.skipWaiting());
self.addEventListener('activate', e => clients.claim());
self.addEventListener('fetch', e => e.respondWith(fetch(e.request)));
```

---

### 4. Meta Tags in `<head>` (all staff pages via login.php + navbar.php)

**For the manifest link + theme:**
```html
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#C8102E">
```

**For iOS-specific support (Safari doesn't use manifest for icons):**
```html
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PW">
<link rel="apple-touch-icon" href="icons/apple-touch-icon.png">
```

---

### 5. Service Worker Registration (in login.php)
```html
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js');
}
</script>
```

---

## Files to create/modify

| Action | File | What |
|--------|------|------|
| **Create** | `staff/manifest.json` | Web app manifest |
| **Create** | `staff/sw.js` | Minimal service worker |
| **Create** | `staff/icons/icon-192.png` | 192x192 red + "PW" icon |
| **Create** | `staff/icons/icon-512.png` | 512x512 red + "PW" icon |
| **Create** | `staff/icons/apple-touch-icon.png` | 180x180 iOS icon |
| **Modify** | `staff/login.php` | Add meta tags, manifest link, SW registration |
| **Modify** | `staff/navbar.php` | Add manifest link + meta tags so all pages are PWA-aware |

---

## How it works after implementation

- **Android (Chrome)**: Users visit the login page → browser detects manifest + service worker → shows "Add to Home Screen" banner automatically (or via browser menu → "Install app"). App opens in standalone mode (no browser chrome).
- **iOS (Safari)**: Users tap Share button → "Add to Home Screen". The `apple-touch-icon` is used as the icon. App opens in standalone mode with the red status bar.

No app store needed. No additional server configuration required.
