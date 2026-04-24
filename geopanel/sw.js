/* ═══════════════════════════════════════════════════════════════
   GeoPanel — Service Worker
   Estrategia: cache-first para el app shell, network-only para la API
═══════════════════════════════════════════════════════════════ */

const CACHE_NAME = 'geopanel-v1';

const APP_SHELL = [
    '/geopanel_boletas/',
    '/geopanel_boletas/index.html',
    '/geopanel_boletas/app.js',
    '/geopanel_boletas/styles.css',
    '/geopanel_boletas/manifest.json',
    '/geopanel_boletas/icon.svg',
];

const CDN_RESOURCES = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
];

/* ── INSTALL ─────────────────────────────────────────────────── */
self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE_NAME).then(cache =>
            Promise.allSettled([
                cache.addAll(APP_SHELL),
                cache.addAll(CDN_RESOURCES),
            ])
        )
    );
    self.skipWaiting();
});

/* ── ACTIVATE ────────────────────────────────────────────────── */
self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

/* ── FETCH ───────────────────────────────────────────────────── */
self.addEventListener('fetch', e => {
    const url = new URL(e.request.url);

    // API proxy: siempre red, nunca cache
    if (url.pathname.includes('api-proxy.php')) return;

    // CDN / recursos externos: cache first, luego red
    if (url.hostname !== self.location.hostname) {
        e.respondWith(
            caches.match(e.request).then(cached => {
                if (cached) return cached;
                return fetch(e.request).then(res => {
                    if (res.ok) {
                        const clone = res.clone();
                        caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
                    }
                    return res;
                }).catch(() => cached);
            })
        );
        return;
    }

    // App shell: cache first, luego red
    e.respondWith(
        caches.match(e.request).then(cached => {
            if (cached) return cached;
            return fetch(e.request).then(res => {
                if (res.ok) {
                    const clone = res.clone();
                    caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
                }
                return res;
            });
        })
    );
});
