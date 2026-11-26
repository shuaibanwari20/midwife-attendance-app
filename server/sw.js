// حضور قابله — admin panel service worker (Feature 45, PWA install).
// Network-only on purpose: the panel always shows live data, nothing is cached.
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', function (e) { e.respondWith(fetch(e.request)); });
