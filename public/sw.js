/* eslint-disable no-undef */
// Service Worker personnalisé pour AXONTIS

const CACHE_NAME = 'axontis-v1';
const urlsToCache = [
    '/',
    '/index.html',
    '/favicon.ico',
];

// Installation du Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(urlsToCache).catch(err => {
                console.log('Error during cache addAll:', err);
            });
        })
    );
    self.skipWaiting();
});

// Activation du Service Worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Gestion des requêtes
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-GET
    if (request.method !== 'GET') {
        return;
    }

    // Ignorer les requêtes internes de Vite
    if (url.pathname.includes('/@vite') || url.pathname.includes('/node_modules')) {
        return;
    }

    // Stratégie Network First pour les API
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cloner la réponse
                    const responseClone = response.clone();

                    // Mettre en cache la réponse
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone);
                    });

                    return response;
                })
                .catch(() => {
                    // En cas d'erreur, retourner depuis le cache
                    return caches.match(request);
                })
        );
        return;
    }

    // Stratégie Cache First pour les assets statiques
    if (/\.(js|css|png|jpg|jpeg|svg|gif|webp|woff|woff2|ttf|eot)$/.test(url.pathname)) {
        event.respondWith(
            caches.match(request).then(response => {
                return response || fetch(request).then(response => {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone);
                    });
                    return response;
                });
            })
        );
        return;
    }

    // Stratégie par défaut : Network First
    event.respondWith(
        fetch(request)
            .then(response => {
                const responseClone = response.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(request, responseClone);
                });
                return response;
            })
            .catch(() => {
                return caches.match(request);
            })
    );
});

// Gestion des messages du client
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

