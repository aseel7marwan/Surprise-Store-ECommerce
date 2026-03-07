/**
 * Surprise! Store - Service Worker v3
 * Smart caching with Offline support
 * 
 * Features:
 * - Static assets caching
 * - Offline page fallback
 * - Network-first for dynamic content
 * - Background sync ready
 */

const CACHE_NAME = 'surprise-v3';
const OFFLINE_PAGE = '/offline.html';

// Static assets to cache (essential files)
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/css/main.css',
    '/js/app.js',
    '/js/cart.js',
    '/js/wishlist.js',
    '/js/api-helper.js',
    '/images/logo.jpg',
    '/manifest.json'
];

// NEVER cache these patterns (sensitive/dynamic)
const NO_CACHE_PATTERNS = [
    /\/admin/,
    /\/api\//,
    /\/cart/,
    /\/track/,
    /\/checkout/,
    /order/,
    /submit/,
    /login/,
    /logout/,
    /backup/,
    /delete/,
    /upload/,
    /validate/,
    /\.json\?/  // JSON with query params (API responses)
];

// ============ INSTALL ============
self.addEventListener('install', (event) => {
    console.log('[SW] Installing v3...');

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            // Cache all static assets
            return Promise.allSettled(
                STATIC_ASSETS.map(url =>
                    cache.add(url).catch(err => {
                        console.warn('[SW] Failed to cache:', url, err);
                    })
                )
            );
        }).then(() => {
            console.log('[SW] Install complete');
        })
    );

    // Take control immediately
    self.skipWaiting();
});

// ============ ACTIVATE ============
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating v3...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => {
            console.log('[SW] Activation complete');
        })
    );

    // Claim all clients
    self.clients.claim();
});

// ============ FETCH ============
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only handle GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Check if URL should not be cached
    for (const pattern of NO_CACHE_PATTERNS) {
        if (pattern.test(url.pathname) || pattern.test(url.href)) {
            return; // Let browser handle normally
        }
    }

    // External requests (except fonts)
    if (url.origin !== location.origin) {
        // Allow Google Fonts - cache-first strategy
        if (url.hostname.includes('fonts.googleapis.com') ||
            url.hostname.includes('fonts.gstatic.com')) {
            event.respondWith(
                caches.match(event.request).then((cached) => {
                    if (cached) {
                        return cached;
                    }
                    return fetch(event.request).then((response) => {
                        if (response && response.ok) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(event.request, clone);
                            });
                        }
                        return response;
                    }).catch((err) => {
                        console.warn('[SW] Font fetch failed:', err);
                        // Return empty response instead of undefined
                        return new Response('', { status: 503, statusText: 'Font unavailable' });
                    });
                })
            );
            return;
        }
        return; // Other external - don't cache
    }

    // ============ NAVIGATION REQUESTS (HTML pages) ============
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Cache successful navigation responses
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Network failed - try cache, then offline page
                    return caches.match(event.request)
                        .then((cached) => {
                            if (cached) {
                                return cached;
                            }
                            // Return offline page
                            return caches.match(OFFLINE_PAGE);
                        });
                })
        );
        return;
    }

    // ============ STATIC ASSETS (CSS, JS, Images) ============
    const isStaticAsset = /\.(css|js|png|jpg|jpeg|webp|svg|gif|woff2?|ttf|eot|ico)$/i.test(url.pathname);

    if (isStaticAsset) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                // Stale-while-revalidate strategy
                const fetchPromise = fetch(event.request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(event.request, clone);
                            });
                        }
                        return response;
                    })
                    .catch(() => null);

                // Return cached immediately, update in background
                return cached || fetchPromise;
            })
        );
        return;
    }

    // ============ PRODUCT IMAGES ============
    if (url.pathname.startsWith('/images/products/')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                if (cached) {
                    return cached;
                }

                return fetch(event.request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(event.request, clone);
                            });
                        }
                        return response;
                    })
                    .catch(() => {
                        // Return placeholder image
                        return caches.match('/images/products/default.png');
                    });
            })
        );
        return;
    }

    // Everything else - network only
});

// ============ BACKGROUND SYNC (for offline orders) ============
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-orders') {
        event.waitUntil(syncOfflineOrders());
    }
});

async function syncOfflineOrders() {
    // Placeholder for future offline order sync
    console.log('[SW] Syncing offline orders...');
}

// ============ PUSH NOTIFICATIONS (optional) ============
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();

        event.waitUntil(
            self.registration.showNotification(data.title || 'Surprise!', {
                body: data.body || 'لديك إشعار جديد',
                icon: '/images/logo.jpg',
                badge: '/images/logo.jpg',
                dir: 'rtl',
                lang: 'ar',
                tag: data.tag || 'default',
                data: data.url || '/'
            })
        );
    }
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            // Focus existing window if open
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // Otherwise open new window
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

console.log('[SW] Service Worker v3 loaded');
