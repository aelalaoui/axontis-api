/**
 * Configuration PWA pour AXONTIS
 * Gestion du Service Worker et du caching
 */

// Configuration des stratégies de cache
export const cacheConfig = {
    // Version du cache
    version: 'axontis-v1',

    // Ressources prioritaires à pré-cacher
    precacheResources: [
        '/',
        '/index.html',
        '/favicon.ico',
        '/manifest.webmanifest',
    ],

    // Configuration des caches spécifiques
    caches: {
        // Cache API - Network First
        api: {
            name: 'api-cache',
            strategy: 'NetworkFirst',
            ttl: 60 * 60 * 24, // 24 heures
            maxItems: 50,
        },

        // Cache des images - Cache First
        images: {
            name: 'images-cache',
            strategy: 'CacheFirst',
            ttl: 60 * 60 * 24 * 7, // 7 jours
            maxItems: 100,
        },

        // Cache des fonts - Cache First
        fonts: {
            name: 'fonts-cache',
            strategy: 'CacheFirst',
            ttl: 60 * 60 * 24 * 30, // 30 jours
            maxItems: 30,
        },

        // Cache des assets statiques - Cache First
        assets: {
            name: 'assets-cache',
            strategy: 'CacheFirst',
            ttl: 60 * 60 * 24 * 365, // 1 année
            maxItems: 200,
        },

        // Cache dynamique - Network First
        pages: {
            name: 'pages-cache',
            strategy: 'NetworkFirst',
            ttl: 60 * 60 * 24, // 24 heures
            maxItems: 50,
        },
    },

    // Patterns d'URL
    patterns: {
        api: /^https:\/\/[^/]*\/api\//,
        image: /\.(png|jpg|jpeg|gif|webp|svg)$/i,
        font: /\.(woff|woff2|ttf|eot|otf)$/i,
        script: /\.(js)$/i,
        style: /\.(css)$/i,
    },
};

// Configuration des notifications
export const notificationConfig = {
    // Notifications de mise à jour
    update: {
        title: 'Mise à jour disponible',
        body: 'Une nouvelle version est prête à être installée',
        icon: '/pwa-192x192.png',
        badge: '/favicon-32x32.png',
    },

    // Notifications offline
    offline: {
        title: 'Application prête',
        body: 'L\'application est maintenant disponible hors ligne',
        icon: '/pwa-192x192.png',
        badge: '/favicon-32x32.png',
    },

    // Notifications online
    online: {
        title: 'Application en ligne',
        body: 'La connexion Internet a été rétablie',
        icon: '/pwa-192x192.png',
    },
};

// Configuration du Service Worker
export const swConfig = {
    // Fichier du Service Worker
    file: '/sw.js',

    // Scope
    scope: '/',

    // Type d'enregistrement
    type: 'classic',

    // Options d'installation
    updateViaCache: 'none',
};

// Configuration de l'app
export const appConfig = {
    // Nom
    name: 'AXONTIS',
    fullName: 'AXONTIS - Espace Sécurisé',

    // Description
    description: 'Votre espace de gestion sécurisé AXONTIS',

    // Couleurs
    theme_color: '#1f2937',
    background_color: '#ffffff',

    // URLs
    start_url: '/',
    scope: '/',

    // Mode d'affichage
    display: 'standalone',

    // Catégories
    categories: ['business', 'productivity'],

    // Screenshots
    screenshots: [
        {
            src: '/screenshot-1.png',
            sizes: '540x720',
            type: 'image/png',
            form_factor: 'narrow',
        },
        {
            src: '/screenshot-2.png',
            sizes: '1280x720',
            type: 'image/png',
            form_factor: 'wide',
        },
    ],
};

// Stratégies de requête
export const requestStrategies = {
    // Network First - réseau en premier, fallback au cache
    NetworkFirst: {
        name: 'NetworkFirst',
        timeout: 10000, // 10 secondes
        cacheFallback: true,
    },

    // Cache First - cache en premier, réseau en fallback
    CacheFirst: {
        name: 'CacheFirst',
        cacheFallback: true,
        networkFallback: true,
    },

    // Network Only - réseau uniquement
    NetworkOnly: {
        name: 'NetworkOnly',
        timeout: 30000,
    },

    // Cache Only - cache uniquement
    CacheOnly: {
        name: 'CacheOnly',
        cacheFallback: true,
    },

    // Stale While Revalidate - cache immédiat, mise à jour en arrière-plan
    StaleWhileRevalidate: {
        name: 'StaleWhileRevalidate',
        cacheFallback: true,
        networkUpdate: true,
    },
};

// Règles de caching personnalisées
export const customCachingRules = [
    // API - Network First avec court TTL
    {
        pattern: /^https:\/\/[^/]*\/api\//,
        strategy: 'NetworkFirst',
        cacheName: 'api-cache',
        ttl: 3600, // 1 heure
    },

    // Images - Cache First
    {
        pattern: /\.(png|jpg|jpeg|gif|webp|svg)$/i,
        strategy: 'CacheFirst',
        cacheName: 'images-cache',
        ttl: 604800, // 7 jours
    },

    // Fonts - Cache First, long TTL
    {
        pattern: /\.(woff|woff2|ttf|eot|otf)$/i,
        strategy: 'CacheFirst',
        cacheName: 'fonts-cache',
        ttl: 2592000, // 30 jours
    },

    // CSS et JS - Cache First
    {
        pattern: /\.(js|css)$/i,
        strategy: 'CacheFirst',
        cacheName: 'assets-cache',
        ttl: 31536000, // 1 année
    },

    // HTML Pages - Network First
    {
        pattern: /\.html$/i,
        strategy: 'NetworkFirst',
        cacheName: 'pages-cache',
        ttl: 86400, // 1 jour
    },
];

export default {
    cacheConfig,
    notificationConfig,
    swConfig,
    appConfig,
    requestStrategies,
    customCachingRules,
};

