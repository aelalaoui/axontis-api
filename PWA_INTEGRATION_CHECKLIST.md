# 📋 Checklist d'Intégration PWA

## Phase 1 : Configuration Initiale ✅

- [x] Installer les dépendances PWA
  ```bash
  npm install -D vite-plugin-pwa workbox-build
  ```

- [x] Configurer `vite.config.js` avec VitePWA
- [x] Créer le Service Worker personnalisé (`public/sw.js`)
- [x] Ajouter les métadonnées PWA à `app.blade.php`
- [x] Créer le middleware PWA (`app/Http/Middleware/PWAHeaders.php`)

## Phase 2 : Ressources et Icônes 🎨

- [ ] Générer les icônes PWA :
  ```bash
  node generate-pwa-icons.js votre-logo.png
  ```
  
  OU utiliser un service en ligne :
  - https://www.favicon-generator.org/
  - https://pwabuilder.com/
  
  Icônes requises dans `public/` :
  - [ ] `favicon.ico` (32x32)
  - [ ] `favicon-16x16.png`
  - [ ] `favicon-32x32.png`
  - [ ] `apple-touch-icon.png` (180x180)
  - [ ] `pwa-192x192.png`
  - [ ] `pwa-512x512.png`
  - [ ] `screenshot-1.png` (540x720 - portrait)
  - [ ] `screenshot-2.png` (1280x720 - paysage)

## Phase 3 : Intégration Laravel 🔧

### Configuration du Middleware

- [ ] Ouvrir `app/Http/Middleware/Kernel.php`
- [ ] Ajouter le middleware PWA :
  ```php
  protected $middleware = [
      // ... autres middlewares
      \App\Http\Middleware\PWAHeaders::class,
  ];
  ```

### Configuration des Routes (si nécessaire)

- [ ] Vérifier que le routing gère les SPA correctement
- [ ] S'assurer que les routes API sont protégées

## Phase 4 : Composants Vue 🎯

### Option A : Notifications Globales (Recommandé)

- [ ] Importer `PWANotification.vue` dans votre layout principal :
  ```vue
  <script setup>
  import PWANotification from '@/components/PWANotification.vue';
  </script>

  <template>
    <div>
      <PWANotification />
      <!-- Contenu de l'app -->
    </div>
  </template>
  ```

### Option B : Contrôle Manuel

- [ ] Importer le composable `usePWA` :
  ```vue
  <script setup>
  import { usePWA } from '@/composables/usePWA';
  
  const { offlineReady, needRefresh, updateServiceWorker } = usePWA();
  </script>
  ```

## Phase 5 : Build et Tests 🧪

### Build Production

- [ ] Exécuter le build :
  ```bash
  npm run build
  ```

- [ ] Vérifier que les fichiers sont générés :
  - [ ] `dist/manifest.webmanifest` existe
  - [ ] `dist/sw.js` existe
  - [ ] Les icônes sont dans `dist/`

### Tests Locaux

- [ ] Démarrer le serveur de développement :
  ```bash
  npm run dev
  ```

- [ ] Ouvrir http://localhost:5173 (ou le port utilisé)
- [ ] Vérifier que Service Worker apparaît en console
- [ ] Tester l'installation :
  - [ ] Menu ⋮ → "Installer AXONTIS"
  - [ ] Accepter l'installation
  - [ ] Vérifier que l'app est installée

### Tests Chrome DevTools

- [ ] Ouvrir DevTools (F12)
- [ ] Aller à "Application" → "Service Workers"
  - [ ] Service Worker apparaît
  - [ ] Status = "activated and running"
- [ ] Aller à "Application" → "Manifest"
  - [ ] Manifest charge correctement
  - [ ] Icons affichent les images
- [ ] Aller à "Application" → "Cache Storage"
  - [ ] Caches apparaissent
  - [ ] Ressources sont cachées

### Tests Offline

- [ ] DevTools → "Application" → "Service Workers"
  - [ ] Cocher "Offline"
- [ ] L'app doit rester fonctionnelle
  - [ ] Les pages loadent depuis le cache
  - [ ] Les formulaires fonctionnent (données locales)
- [ ] Décocher "Offline"
  - [ ] L'app synchronise les données

### Audit Lighthouse

- [ ] DevTools → "Lighthouse"
- [ ] Cliquer "Analyze page load"
- [ ] Sélectionner "Progressive Web App"
- [ ] Vérifier le score ≥ 90
- [ ] Corriger les problèmes signalés si besoin

## Phase 6 : Configuration Serveur Production 🚀

### Apache

- [ ] `.htaccess` configuré dans `public/`
- [ ] `mod_rewrite` activé
- [ ] Headers de cache configurés
- [ ] MIME types configurés

Vérifier :
```bash
curl -I https://votre-domaine.com/sw.js | grep -i cache-control
# Doit afficher: cache-control: public, max-age=3600
```

### IIS

- [ ] `web.config` configuré dans `public/`
- [ ] URL Rewriting activé
- [ ] MIME types configurés
- [ ] Headers de cache configurés

### Nginx (si applicable)

- [ ] Configuration pour PWA :
  ```nginx
  location ~ ^/(sw\.js|manifest\.webmanifest)$ {
      expires 1h;
      add_header Cache-Control "public, no-cache";
  }

  location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|webp|woff|woff2)$ {
      expires 1y;
      add_header Cache-Control "public, immutable";
  }
  ```

### HTTPS

- [ ] Certificat SSL/TLS installé
- [ ] Redirections HTTP → HTTPS configurées
- [ ] Headers de sécurité configurés

Vérifier :
```bash
curl -I https://votre-domaine.com/ | head -20
```

## Phase 7 : Déploiement 📤

- [ ] Sauvegarder la base de données
- [ ] Exécuter les migrations si nécessaire
- [ ] Copier les fichiers build :
  ```bash
  npm run build
  # Transférer dist/* vers public/
  ```

- [ ] Vérifier les permissions des fichiers
- [ ] Redémarrer les services web (si nécessaire)

## Phase 8 : Vérification Production ✔️

- [ ] Vérifier le site : https://votre-domaine.com
- [ ] Vérifier Service Worker (DevTools → Application)
- [ ] Tester l'installation depuis le menu ⋮
- [ ] Vérifier Manifest chargeable :
  ```bash
  curl https://votre-domaine.com/manifest.webmanifest
  ```

- [ ] Tester depuis mobile :
  - [ ] Installation depuis Chrome mobile
  - [ ] L'app s'ajoute à l'écran d'accueil
  - [ ] Lancer depuis l'écran d'accueil
  - [ ] L'app s'ouvre en fullscreen

- [ ] Lighthouse Audit final :
  - [ ] Score PWA ≥ 90
  - [ ] Performance ≥ 80
  - [ ] Accessibility ≥ 90
  - [ ] Best Practices ≥ 80

## Phase 9 : Monitoring Continu 📊

- [ ] Surveiller les erreurs Service Worker
- [ ] Vérifier les stats d'installation
- [ ] Suivre les mises à jour et versions
- [ ] Analyser les performances

## Phase 10 : Améliorations Futures (Optionnel) 🚀

- [ ] Ajouter les Notifications Push
  - [ ] Configuration Firebase Cloud Messaging
  - [ ] Backend pour envoyer les notifications

- [ ] Synchronisation en Arrière-Plan
  - [ ] Background Sync API
  - [ ] Sync des données offline

- [ ] Partage de Fichiers
  - [ ] Web Share API
  - [ ] Share Target API

- [ ] Mode Appareil Photo
  - [ ] Fullscreen en production
  - [ ] Masquer les contrôles navigateur

## 📝 Notes

- Les dépendances PWA sont dans `node_modules/` (ne pas committer)
- Les icônes doivent être dans `public/`
- Le Service Worker cache les assets automatiquement
- Les mises à jour se font automatiquement (1 fois par minute)
- Utilisateurs verront notification si update disponible

## 🆘 Problèmes Courants

### Service Worker ne s'enregistre pas
- Vérifier HTTPS en production (HTTP ok en dev)
- Vérifier que `/sw.js` existe et est accessible
- Vérifier console du navigateur pour erreurs

### Installation échoue
- Vérifier les icônes (formats et tailles)
- Vérifier le manifest
- Vérifier Lighthouse pour manquements

### Cache ne fonctionne pas
- Vérifier que Service Worker est "activated"
- Vérifier DevTools → Application → Cache Storage
- Vérifier la stratégie de caching appropriée

### Mises à jour ne s'appliquent pas
- Vérifier que Service Worker vérifie les updates (défaut: 1 min)
- Forcer le rechargement : Ctrl+Shift+R
- Vérifier DevTools pour messages

## ✅ Finalisation

Une fois tous les éléments validés :

- [ ] Faire un commit avec le tag `pwa-v1.0`
- [ ] Documenter les changements
- [ ] Former l'équipe à la PWA
- [ ] Monitorer en production

---

**Pour l'aide** : Consultez `PWA_GUIDE.md` ou `PWA_IMPLEMENTATION_SUMMARY.md`

