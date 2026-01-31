# 📱 Configuration PWA - AXONTIS

Ce guide explique comment utiliser et tirer le meilleur parti de la configuration Progressive Web App (PWA) du projet AXONTIS.

## 🎯 Qu'est-ce qu'une PWA ?

Une Progressive Web App (PWA) est une application web qui utilise les technologies modernes du navigateur pour offrir une expérience utilisateur comparable à celle des applications natives.

### Caractéristiques principales :
- ✅ **Installation** : L'utilisateur peut installer l'app directement du navigateur
- ✅ **Hors ligne** : Fonctionne sans connexion Internet grâce au Service Worker
- ✅ **Notifications** : Peut envoyer des notifications push
- ✅ **Responsive** : S'adapte à tous les appareils
- ✅ **Sécurisée** : Fonctionne uniquement en HTTPS

## 🚀 Démarrage rapide

### 1. Installation des dépendances

Les dépendances PWA sont déjà installées :
```bash
npm install -D vite-plugin-pwa workbox-build
```

### 2. Configuration

La configuration PWA se trouve dans `vite.config.js` et inclut :
- Service Worker automatique
- Manifest Web
- Caching intelligent
- Mise à jour automatique

### 3. Icônes PWA

Les icônes doivent être placées dans le dossier `public/` :

```
public/
├── favicon.ico              # Favicon standard
├── favicon-16x16.png       # Favicon petit
├── favicon-32x32.png       # Favicon moyen
├── apple-touch-icon.png    # Icône iOS
├── pwa-192x192.png         # Icône PWA (192x192)
├── pwa-512x512.png         # Icône PWA (512x512)
├── screenshot-1.png        # Capture d'écran portrait (540x720)
└── screenshot-2.png        # Capture d'écran paysage (1280x720)
```

### Générer les icônes automatiquement

Si vous avez une image source `logo.png`, générez les icônes avec :

```bash
npm install -D sharp
node generate-pwa-icons.js logo.png
```

Ou utilisez un service en ligne :
- https://www.favicon-generator.org/
- https://pwabuilder.com/

## 🔧 Architecture PWA

### Fichiers clés

1. **vite.config.js**
   - Configuration VitePWA
   - Manifest Web
   - Stratégie de caching Workbox

2. **public/sw.js**
   - Service Worker personnalisé
   - Stratégies de cache (Network First, Cache First)
   - Gestion du offline

3. **resources/js/components/PWANotification.vue**
   - Notifications de mise à jour
   - Notifications offline
   - Gestion de l'installation

4. **resources/js/composables/usePWA.js**
   - Composable Vue pour gérer la PWA
   - Enregistrement du Service Worker
   - Mise à jour des versions

## 📦 Manifest Web

Le fichier `manifest.webmanifest` est généré automatiquement et contient :

```json
{
  "name": "AXONTIS - Espace Sécurisé",
  "short_name": "AXONTIS",
  "description": "Votre espace de gestion sécurisé AXONTIS",
  "display": "standalone",
  "scope": "/",
  "start_url": "/",
  "theme_color": "#1f2937",
  "background_color": "#ffffff",
  "icons": [...]
}
```

## 🔄 Stratégies de Caching

Le projet utilise plusieurs stratégies de caching intelligentes :

### 1. **Network First (API)**
- Tentative réseau en premier
- Utilise le cache en cas d'échec
- Utile pour les données dynamiques

```
Pattern: /api/*
Timeout: 10 secondes
```

### 2. **Cache First (CDN)**
- Utilise le cache en premier
- Récupère en réseau si absent
- Cache pendant 1 an
- Idéal pour les assets statiques

```
Pattern: https://cdn.*
Cache: cdn-cache
```

### 3. **Network First (Défaut)**
- Tentative réseau en premier
- Utilise le cache si offline
- Convient pour la plupart des ressources

## 🌐 Service Worker

Le Service Worker personnalisé (`public/sw.js`) gère :

1. **Installation** : Pré-cache des ressources
2. **Activation** : Nettoyage des anciens caches
3. **Fetch** : Interception des requêtes selon les stratégies

### Stratégies par type de ressource :

```
API (/api/)           → Network First
Assets statiques      → Cache First
Autres ressources     → Network First
```

## 📲 Intégration dans Vue

### Utiliser PWANotification dans un layout

```vue
<template>
    <div>
        <PWANotification />
        <!-- Contenu de l'application -->
    </div>
</template>

<script setup>
import PWANotification from '@/components/PWANotification.vue';
</script>
```

### Utiliser le composable usePWA

```vue
<script setup>
import { usePWA } from '@/composables/usePWA';

const { offlineReady, needRefresh, updateServiceWorker, close } = usePWA();

const handleUpdate = () => {
    updateServiceWorker();
};
</script>
```

## 🔐 Sécurité et HTTPS

**Important** : Une PWA ne fonctionne qu'en HTTPS (sauf en développement sur localhost).

### En production

1. Obtenir un certificat SSL/TLS
2. Configurer HTTPS sur votre serveur
3. Rediriger HTTP vers HTTPS

### En développement

```bash
npm run dev  # Fonctionne sur http://localhost
```

## 🧪 Tests

### Vérifier la PWA avec DevTools

1. Ouvrir Chrome DevTools (`F12`)
2. Aller à l'onglet "Application"
3. Voir le Service Worker et le Manifest
4. Tester le mode offline

### Test de Lighthouse

1. Ouvrir Chrome DevTools
2. Onglet "Lighthouse"
3. Auditer "Progressive Web App"
4. Corriger les problèmes signalés

### Installation manuelle

1. Ouvrir l'app dans Chrome
2. Cliquer sur le menu (⋮)
3. Cliquer "Installer AXONTIS" ou similaire
4. L'app s'ajoute à votre écran d'accueil

## 🔄 Mise à jour automatique

La PWA effectue automatiquement :

1. **Check des mises à jour** : Toutes les minutes
2. **Notification utilisateur** : "Mise à jour disponible"
3. **Mise à jour intelligente** : Actualise au prochain chargement

Utilisateurs peuvent forcer la mise à jour via la notification.

## 🚀 Build et Déploiement

### Build de production

```bash
npm run build
```

Cela génère :
- `dist/` : Tous les fichiers statiques
- `dist/manifest.webmanifest` : Manifest Web
- `dist/sw.js` : Service Worker
- Images PWA dans `dist/`

### Déployer

1. Transférer le dossier `dist/` vers le serveur
2. Configurer le serveur web :
   - Apache : `.htaccess` fourni
   - IIS : `web.config` fourni
   - Nginx : voir section Nginx ci-dessous

### Configuration Nginx

```nginx
# Cache headers pour les assets
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|webp|woff|woff2)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Court cache pour Service Worker et Manifest
location ~ ^/(sw\.js|manifest\.webmanifest)$ {
    expires 1h;
    add_header Cache-Control "public, no-cache";
}

# Rewrite pour SPA
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 🐛 Dépannage

### La PWA ne s'installe pas

1. Vérifier HTTPS (sauf localhost)
2. Vérifier le manifest dans DevTools
3. Vérifier les icônes (tailles correctes)
4. Vérifier que le Service Worker est enregistré

### Le Service Worker ne se met pas à jour

1. Vérifier la console du navigateur
2. Forcer le rechargement : Ctrl+Shift+R
3. Effacer le cache du navigateur
4. Désinscrire et réinstaller

### Offline ne fonctionne pas

1. Vérifier que le Service Worker est activé
2. Vérifier que les ressources sont cachées
3. Vérifier la stratégie de caching appropriée

## 📊 Monitoring

### Vérifier la santé PWA

```javascript
// Dans la console du navigateur
navigator.serviceWorker.getRegistrations().then(registrations => {
    console.log('Service Workers:', registrations);
});

// Vérifier l'état du cache
caches.keys().then(names => {
    console.log('Caches:', names);
});
```

## 📚 Ressources supplémentaires

- [MDN - Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Web.dev - PWA](https://web.dev/progressive-web-apps/)
- [Workbox Documentation](https://developers.google.com/web/tools/workbox)
- [PWA Builder](https://www.pwabuilder.com/)

## 🔔 Notifications Push (Optionnel)

Pour ajouter les notifications push :

1. Installer une librairie push (ex: firebase-messaging)
2. Configurer le backend pour l'authentification
3. Demander la permission utilisateur
4. Envoyer les notifications

Exemple avec Firebase Cloud Messaging :

```javascript
// Dans le Service Worker
self.addEventListener('push', event => {
    const data = event.data.json();
    self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/pwa-192x192.png',
    });
});
```

## 📝 Checklist PWA

- [ ] HTTPS configuré en production
- [ ] Icônes PWA créées et placées (192x192, 512x512)
- [ ] Manifest généré (`manifest.webmanifest`)
- [ ] Service Worker enregistré et actif
- [ ] Mode offline fonctionnel
- [ ] Notifications de mise à jour visibles
- [ ] Lighthouse audit "PWA" = ✓ Pass
- [ ] Installable sur tous les appareils
- [ ] Cache strategy appropriée pour l'app

---

**Version** : 1.0  
**Dernière mise à jour** : 2026-01-31  
**Maintenance** : Framework PWA automatisé avec Vite PWA

