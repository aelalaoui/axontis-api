# 🚨 CORRECTION : Erreur "Table 'jobs' doesn't exist"

## ❌ Problème

**Erreur dans Sentry** : `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'axontis.jobs' doesn't exist`

**Occurrences** : 39330+ fois  
**Impact** : Les workers de queue ne peuvent pas traiter les jobs  
**Environnement** : Production (axontis-prod)

### 🔍 Cause racine

Les workers Supervisor sur le serveur utilisent encore `queue:work database` au lieu de `queue:work redis`, et le fichier `.env` contient probablement `QUEUE_CONNECTION=database`.

Laravel essaie donc de lire la table `jobs` dans MySQL, mais cette table n'existe pas car nous utilisons Redis pour les queues.

---

## ✅ Solution immédiate (Sur le serveur)

### Méthode 1 : Script automatique (Recommandée)

```bash
# Se connecter au serveur
ssh -i ~/.ssh/axontis-vultr root@45.32.146.20

# Exécuter le script de correction
cd /var/www/axontis/infrastructure/scripts
bash fix-jobs-table-error.sh
```

**Ce script fait automatiquement** :
- ✅ Vérifie que Redis est actif
- ✅ Sauvegarde le `.env`
- ✅ Corrige `QUEUE_CONNECTION=redis`
- ✅ Ajoute toutes les variables Redis
- ✅ Corrige Supervisor pour utiliser Redis
- ✅ Redémarre les workers
- ✅ Vérifie que tout fonctionne

### Méthode 2 : Manuelle

```bash
# Se connecter au serveur
ssh -i ~/.ssh/axontis-vultr root@45.32.146.20

# 1. Éditer le .env
nano /var/www/axontis/.env

# Modifier ces lignes :
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis  # ← CRITIQUE

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3

# Sauvegarder : Ctrl+O, Enter, Ctrl+X

# 2. Corriger Supervisor
nano /etc/supervisor/conf.d/axontis-worker.conf

# Modifier la ligne command :
command=php /var/www/axontis/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

# Sauvegarder : Ctrl+O, Enter, Ctrl+X

# 3. Appliquer les changements
cd /var/www/axontis
php artisan config:cache
php artisan cache:clear
supervisorctl reread
supervisorctl update
supervisorctl restart axontis-worker:*
```

---

## 🔍 Vérification

```bash
# 1. Vérifier le .env
grep QUEUE_CONNECTION /var/www/axontis/.env
# Doit afficher: QUEUE_CONNECTION=redis

# 2. Vérifier Supervisor
cat /etc/supervisor/conf.d/axontis-worker.conf | grep command
# Doit contenir: queue:work redis

# 3. Vérifier les workers
supervisorctl status
# Doit afficher: RUNNING

# 4. Vérifier Redis
redis-cli ping
# Doit retourner: PONG

redis-cli -n 3 DBSIZE
# Affiche le nombre de jobs dans la queue Redis

# 5. Monitorer les logs
tail -f /var/www/axontis/storage/logs/laravel.log
# Ne doit plus afficher l'erreur "Table 'jobs' doesn't exist"
```

---

## 📊 Ce qui va changer

### Avant (❌ Erreur)
```
.env: QUEUE_CONNECTION=database
Supervisor: queue:work database
Workers: Essaient de lire la table 'jobs' → ERREUR
```

### Après (✅ Corrigé)
```
.env: QUEUE_CONNECTION=redis
Supervisor: queue:work redis
Workers: Lisent les jobs depuis Redis → OK
```

---

## 🔄 Prévention future

### Dans le workflow GitHub Actions

Le workflow a été mis à jour pour configurer automatiquement Redis lors des déploiements.

**Vérifier le secret GitHub** :

```bash
# Configurer le secret REDIS_PASSWORD
gh secret set REDIS_PASSWORD --body "null"
```

Puis le prochain déploiement configurera automatiquement :
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`
- Supervisor avec `queue:work redis`

### Migration de la table jobs (Optionnel)

Si vous aviez des jobs en attente dans la table `jobs`, vous devrez les recréer :

```bash
# Vérifier s'il y avait des jobs
mysql -u axontis -p axontis -e "SELECT COUNT(*) FROM jobs;" 2>/dev/null

# Si la table existe et contient des jobs, ils sont perdus
# Vous devrez les redéclencher manuellement
```

**Note** : Si la table `jobs` n'existe pas (ce qui est le cas ici), il n'y a rien à migrer.

---

## 📈 Monitoring

### Dans Sentry

L'erreur `Table 'axontis.jobs' doesn't exist` devrait **disparaître complètement** dans les 5-10 minutes après la correction.

Vous pouvez vérifier sur Sentry :
- Issues → "Table 'jobs' doesn't exist"
- Le statut devrait passer à "Resolved" automatiquement

### Logs Laravel

```bash
# Surveiller les logs en temps réel
tail -f /var/www/axontis/storage/logs/laravel.log

# Rechercher des erreurs
grep "jobs" /var/www/axontis/storage/logs/laravel.log | tail -20
```

### Workers

```bash
# Statut des workers
supervisorctl status

# Logs des workers
tail -f /var/www/axontis/storage/logs/worker.log
```

---

## 🆘 Si le problème persiste

### Erreur "NOAUTH Authentication required"

Si vous voyez cette erreur, c'est que Redis a un mot de passe configuré mais le `.env` a `REDIS_PASSWORD=null`.

**Solution automatique** : Le script `fix-jobs-table-error.sh` détecte maintenant automatiquement le mot de passe Redis et le configure dans le `.env`.

**Solution manuelle** :

```bash
# 1. Trouver le mot de passe Redis
grep "^requirepass" /etc/redis/redis.conf | awk '{print $2}'
# Par exemple : Ax0nt1sR3d1s2026SecurePassword!

# 2. Mettre à jour le .env
nano /var/www/axontis/.env
# Modifier :
REDIS_PASSWORD=Ax0nt1sR3d1s2026SecurePassword!

# 3. Appliquer
php artisan config:cache
supervisorctl restart axontis-worker:*

# 4. Tester
redis-cli -a Ax0nt1sR3d1s2026SecurePassword! ping
# Doit retourner: PONG
```

### 1. Vérifier que Redis est vraiment actif

```bash
systemctl status redis-server
redis-cli ping
```

Si Redis ne répond pas :
```bash
systemctl restart redis-server
```

### 2. Vérifier la configuration en cache

```bash
cd /var/www/axontis
php artisan config:clear
php artisan config:cache
```

### 3. Vérifier les workers

```bash
supervisorctl status axontis-worker:*

# Si les workers ne sont pas RUNNING
supervisorctl restart axontis-worker:*

# Voir les logs en temps réel
supervisorctl tail -f axontis-worker:axontis-worker_00
```

### 4. Vérifier les permissions

```bash
chown -R www-data:www-data /var/www/axontis
chmod -R 775 /var/www/axontis/storage
```

---

## 📚 Documentation

- [Configuration Redis](REDIS_CONFIGURATION.md)
- [Guide de déploiement](DEPLOYMENT.md)
- [Correction AUTH Redis](REDIS_AUTH_FIX.md)
- [Workflow GitHub](GITHUB_WORKFLOW_REDIS_UPDATE.md)

---

## ✅ Checklist post-correction

- [ ] Script de correction exécuté
- [ ] `QUEUE_CONNECTION=redis` dans `.env`
- [ ] Supervisor utilise `queue:work redis`
- [ ] Workers redémarrés et RUNNING
- [ ] Redis répond au ping
- [ ] Aucune erreur dans les logs Laravel
- [ ] Erreur disparue de Sentry dans les 10 minutes
- [ ] Secret GitHub `REDIS_PASSWORD` configuré à "null"

---

**Date de correction** : 2026-02-07  
**Issue Sentry** : Table 'axontis.jobs' doesn't exist (39330+ occurrences)  
**Solution** : Migration vers Redis pour les queues  
**Statut** : ✅ Correction disponible - À appliquer sur le serveur
