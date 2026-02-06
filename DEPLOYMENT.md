# 🚀 Guide de Déploiement Axontis

Ce guide explique comment déployer l'application Axontis CRM sur le VPS Vultr.

## 📋 Informations du serveur

| Propriété | Valeur |
|-----------|--------|
| **IP** | `45.32.146.20` |
| **SSH** | `ssh -i ~/.ssh/axontis-vultr root@45.32.146.20` |
| **OS** | Ubuntu 24.04 LTS |
| **Chemin app** | `/var/www/axontis` |
| **Utilisateur web** | `www-data` |
| **Domaine** | `axontis.net` |

## 🔧 Prérequis

1. Clé SSH `axontis-vultr` configurée
2. Repository GitHub accessible
3. DNS configuré (axontis.net → 45.32.146.20)

## 🤖 Déploiement automatique (GitHub Actions)

Le déploiement se fait automatiquement à chaque push sur la branche `main`.

### Configuration des secrets GitHub

Allez dans **Settings > Secrets and variables > Actions** de votre repository et ajoutez :

| Secret | Valeur |
|--------|--------|
| `VULTR_HOST` | `45.32.146.20` |
| `VULTR_USER` | `root` |
| `VULTR_SSH_PRIVATE_KEY` | Contenu de `~/.ssh/axontis-vultr` (clé privée) |

### Déclencher un déploiement

1. **Automatique** : Push sur `main`
2. **Manuel** : Actions > Deploy to Vultr VPS > Run workflow

### Logs du déploiement

Consultez les logs sur GitHub : **Actions > Deploy to Vultr VPS**

## 📦 Premier déploiement

### 1. Se connecter au serveur

```powershell
# Depuis Windows (PowerShell)
ssh -i $env:USERPROFILE\.ssh\axontis-vultr root@45.32.146.20
```

### 2. Cloner le repository

```bash
# Sur le serveur
cd /var/www
rm -rf axontis  # Supprimer le dossier vide
git clone git@github.com:VOTRE_USERNAME/axontis-api.git axontis
cd axontis
```

### 3. Configurer les permissions

```bash
chown -R www-data:www-data /var/www/axontis
chmod -R 755 /var/www/axontis
chmod -R 775 /var/www/axontis/storage
chmod -R 775 /var/www/axontis/bootstrap/cache
```

### 4. Configurer l'environnement

```bash
# Copier le fichier d'environnement
cp .env.production.example .env

# Générer la clé d'application
php artisan key:generate

# Éditer le .env si nécessaire
nano .env
```

### 5. Installer les dépendances

```bash
# En tant que www-data pour éviter les problèmes de permissions
sudo -u www-data composer install --no-dev --optimize-autoloader
```

### 6. Configurer la base de données

```bash
php artisan migrate --force
php artisan db:seed --force  # Si nécessaire
```

### 7. Optimiser pour la production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 8. Configurer SSL (Let's Encrypt)

```bash
# Vérifier que le DNS est propagé avant !
certbot --nginx -d axontis.net -d www.axontis.net
```

### 9. Démarrer les workers

```bash
supervisorctl reread
supervisorctl update
supervisorctl start axontis-worker:*
supervisorctl start axontis-scheduler
```

## 🔄 Déploiements suivants

### Option 1: Automatique (recommandé)
Push sur la branche `main` → Le workflow GitHub Actions se déclenche automatiquement.

### Option 2: Script manuel (sur le serveur)

```bash
# Sur le serveur
deploy-axontis.sh
# ou avec une branche spécifique
deploy-axontis.sh develop
```

### Option 3: Commandes manuelles

```bash
cd /var/www/axontis
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
supervisorctl restart axontis-worker:*
php artisan up
```

## ⏪ Rollback

### Avec le script (recommandé)

```bash
# Voir les options disponibles
rollback-axontis.sh

# Rollback vers un commit
rollback-axontis.sh abc1234

# Rollback depuis un backup
rollback-axontis.sh files_20260206_120000_abc1234.tar.gz
```

### Rollback manuel

```bash
cd /var/www/axontis
php artisan down
git log --oneline -10  # Voir les commits récents
git reset --hard <commit-hash>
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

## 📊 Monitoring

### Vérifier les services

```bash
systemctl status nginx php8.3-fpm mysql supervisor
```

### Logs Laravel

```bash
tail -f /var/www/axontis/storage/logs/laravel.log
```

### Logs des workers

```bash
tail -f /var/www/axontis/storage/logs/worker.log
```

### Logs du scheduler

```bash
tail -f /var/www/axontis/storage/logs/scheduler.log
```

### Statut Supervisor

```bash
supervisorctl status
```

## 🔐 Sécurité

### Fail2ban (protection SSH)

```bash
fail2ban-client status sshd
```

### Firewall (UFW)

```bash
ufw status
```

## 🗄️ Base de données

### Connexion MySQL

```bash
mysql -u axontis -p axontis
# Mot de passe: Ax0nt1s2026SecurePwd
```

### Backup

```bash
mysqldump -u axontis -p axontis > /var/backups/axontis_$(date +%Y%m%d).sql
```

## 📧 Configuration Email (à faire)

1. Configurer un service SMTP (Mailgun, SendGrid, Amazon SES)
2. Mettre à jour les variables MAIL_* dans `.env`
3. Tester avec `php artisan tinker` :
   ```php
   Mail::raw('Test', fn($m) => $m->to('test@example.com'));
   ```

## 🔮 Évolutions futures

- [ ] Ajouter Redis pour les sessions/cache
- [ ] Configurer des backups automatiques
- [ ] Mettre en place un monitoring (Sentry déjà configuré)
- [ ] Ajouter un serveur de base de données séparé
- [ ] Configurer un CDN (Cloudflare)
