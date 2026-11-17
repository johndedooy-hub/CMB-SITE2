/* CMB service worker: nodig om de speler als app te kunnen installeren. Cachet bewust niets (live radio). */
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });
self.addEventListener('fetch', function () { /* alles gaat gewoon naar het netwerk */ });
