// Minimal service worker: makes the app installable and caches the static build assets.
// Pages and API calls always go to the network (they are user-specific).
const CACHE = 'fridge-frenemy-v1';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || url.origin !== self.location.origin || !url.pathname.startsWith('/build/')) {
        return;
    }

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(event.request);
            if (cached) return cached;

            const response = await fetch(event.request);
            if (response.ok) cache.put(event.request, response.clone());
            return response;
        }),
    );
});
