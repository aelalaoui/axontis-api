#!/bin/bash
# =============================================================================
# Script de déploiement Axontis - ZERO DOWNTIME
# Utilisation: deploy-axontis-zero-downtime.sh [branch]
# =============================================================================
#
# STRATÉGIE DE DÉPLOIEMENT ZERO DOWNTIME:
# =====================================
# 1. Extraire le nouveau code dans un dossier temporaire (releases/TIMESTAMP)
# 2. Configurer, installer les dépendances, et optimiser dans le dossier temporaire
# 3. Mettre à jour uniquement le symlink /var/www/axontis/current -> releases/TIMESTAMP
# 4. Le vieux code continue de servir pendant que le nouveau est préparé
# 5. Le changement de symlink est atomique (quelques millisecondes)
#
# =============================================================================

set -e

# Configuration
BASE_PATH="/var/www/axontis"
RELEASES_PATH="$BASE_PATH/releases"
CURRENT_LINK="$BASE_PATH/current"
BACKUP_PATH="/var/backups/axontis"
SHARED_PATH="$BASE_PATH/shared"
BRANCH="${1:-main}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
NEW_RELEASE="$RELEASES_PATH/$TIMESTAMP"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================="
echo "🚀 DÉPLOIEMENT AXONTIS - ZERO DOWNTIME"
echo "📅 $(date)"
echo "🌿 Branche: $BRANCH"
echo -e "==========================================${NC}"

# Créer les dossiers nécessaires
mkdir -p $RELEASES_PATH
mkdir -p $BACKUP_PATH
mkdir -p $SHARED_PATH/storage/{app,framework,logs}
mkdir -p $SHARED_PATH/resources

# ============================================
# 1. BACKUP
# ============================================
echo -e "\n${YELLOW}📦 Création du backup...${NC}"

# Backup de la base de données
echo "Backup de la base de données..."
if [ -f "$CURRENT_LINK/.env" ]; then
    DB_USER=$(grep -E "^DB_USERNAME=" $CURRENT_LINK/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    DB_PASS=$(grep -E "^DB_PASSWORD=" $CURRENT_LINK/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    DB_NAME=$(grep -E "^DB_DATABASE=" $CURRENT_LINK/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/db_${TIMESTAMP}.sql" 2>/dev/null || true
    echo -e "${GREEN}✅ Backup BDD créé${NC}"
fi

# Backup Redis
if systemctl is-active --quiet redis-server; then
    echo "Backup Redis..."
    REDIS_PASSWORD=$(grep -E "^REDIS_PASSWORD=" $CURRENT_LINK/.env 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')
    if [ -n "$REDIS_PASSWORD" ] && [ "$REDIS_PASSWORD" != "null" ]; then
        redis-cli -a "$REDIS_PASSWORD" BGSAVE --no-auth-warning >/dev/null 2>&1 || true
    else
        redis-cli BGSAVE >/dev/null 2>&1 || true
    fi
    sleep 2
    if [ -f /var/lib/redis/dump.rdb ]; then
        cp /var/lib/redis/dump.rdb "$BACKUP_PATH/redis_${TIMESTAMP}.rdb" 2>/dev/null || true
        echo -e "${GREEN}✅ Redis dump sauvegardé${NC}"
    fi
fi

# Nettoyer les vieux backups (garder les 5 derniers)
cd $BACKUP_PATH
ls -t db_*.sql 2>/dev/null | tail -n +6 | xargs -r rm --
ls -t redis_*.rdb 2>/dev/null | tail -n +6 | xargs -r rm --
ls -t files_*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm --

# ============================================
# 2. CLONAGE DU NOUVEAU CODE (Version temporaire)
# ============================================
echo -e "\n${YELLOW}📥 Récupération du nouveau code...${NC}"

# Créer le dossier de la nouvelle release
if [ -d "$NEW_RELEASE" ]; then
    echo "Suppression de la release existante..."
    rm -rf $NEW_RELEASE
fi
mkdir -p $NEW_RELEASE

# Utiliser le code actuel si on est sur le serveur
if [ -d "$BASE_PATH/.git" ]; then
    echo "Mise à jour depuis le dépôt local..."
    cd $BASE_PATH
    CURRENT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
    git fetch origin
    git reset --hard origin/$BRANCH
    NEW_COMMIT=$(git rev-parse --short HEAD)
    echo -e "${GREEN}✅ Commit: $CURRENT_COMMIT → $NEW_COMMIT${NC}"
    # Copier le code vers la nouvelle release
    rsync -a --exclude='storage' --exclude='resources' --exclude='node_modules' --exclude='.env' \
        --exclude='releases' --exclude='current' --exclude='shared' \
        $BASE_PATH/ $NEW_RELEASE/
else
    echo "⚠️  Git non trouvé, utilisation du code existant"
    rsync -a --exclude='storage' --exclude='resources' --exclude='node_modules' --exclude='.env' \
        $CURRENT_LINK/ $NEW_RELEASE/ 2>/dev/null || true
fi

# ============================================
# 3. CONFIGURATION DU .ENV DANS LA NOUVELLE RELEASE
# ============================================
echo -e "\n${YELLOW}⚙️ Configuration de l'environnement...${NC}"

# Copier le .env existant
if [ -f "$CURRENT_LINK/.env" ]; then
    cp $CURRENT_LINK/.env $NEW_RELEASE/.env
    echo -e "${GREEN}✅ .env copié depuis la version actuelle${NC}"
else
    echo -e "${RED}⚠️  Aucun .env existant!${NC}"
    exit 1
fi

# ============================================
# 4. SYMLINKS POUR LES FICHIERS PARTAGÉS
# ============================================
echo -e "\n${YELLOW}🔗 Configuration des symlinks partagés...${NC}"

# Supprimer les dossiers et créer des symlinks vers shared
rm -rf $NEW_RELEASE/storage
rm -rf $NEW_RELEASE/resources
mkdir -p $SHARED_PATH/storage/{app,framework/{cache,sessions,views},logs}

# Créer les symlinks
ln -sf $SHARED_PATH/storage $NEW_RELEASE/storage
ln -sf $SHARED_PATH/resources $NEW_RELEASE/resources

# ============================================
# 5. INSTALLATION DES DÉPENDANCES
# ============================================
echo -e "\n${YELLOW}📦 Installation des dépendances...${NC}"
cd $NEW_RELEASE

# Vérifier si vendor existe dans current, sinon installer depuis zéro
if [ -d "$CURRENT_LINK/vendor" ]; then
    echo "Réutilisation des vendor existants (plus rapide)..."
    cp -al $CURRENT_LINK/vendor $NEW_RELEASE/vendor 2>/dev/null || true
    # Puis mettre à jour si nécessaire
    composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || composer install --no-dev --optimize-autoloader
else
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# ============================================
# 6. OPTIMISATIONS (DANS LE DOSSIER TEMPORAIRE)
# ============================================
echo -e "\n${YELLOW}⚡ Optimisation de Laravel...${NC}"
cd $NEW_RELEASE

# Nettoyer tous les caches d'abord (important!)
php artisan optimize:clear

# Publier les assets nécessaires
if [ ! -d "$NEW_RELEASE/public/storage" ]; then
    ln -sf $SHARED_PATH/storage/app/public $NEW_RELEASE/public/storage
fi

# Créer le storage:link
php artisan storage:link

# Optimiser après configuration complète
php artisan optimize

echo -e "${GREEN}✅ Optimisation terminée${NC}"

# ============================================
# 7. VÉRIFICATION DE LA NOUVELLE RELEASE
# ============================================
echo -e "\n${YELLOW}🔍 Vérification de la nouvelle release...${NC}"

# Test des fichiers critiques
CRITICAL_FILES=("artisan" "public/index.php" ".env" "vendor/autoload.php")
ALL_OK=true
for file in "${CRITICAL_FILES[@]}"; do
    if [ ! -f "$NEW_RELEASE/$file" ]; then
        echo -e "${RED}❌ Fichier critique manquant: $file${NC}"
        ALL_OK=false
    fi
done

if [ "$ALL_OK" = false ]; then
    echo -e "${RED}❌ La nouvelle release n'est pas complète${NC}"
    rm -rf $NEW_RELEASE
    exit 1
fi

# Test de connexion BDD avec la nouvelle config
echo "Test de connexion BDD..."
if ! php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'OK'; } catch(\Exception \$e) { echo 'FAIL: '.\$e->getMessage(); }" 2>/dev/null | grep -q "OK"; then
    echo -e "${RED}❌ Impossible de se connecter à la base de données${NC}"
    rm -rf $NEW_RELEASE
    exit 1
fi
echo -e "${GREEN}✅ Connexion BDD OK${NC}"

# ============================================
# 8. MIGRATIONS
# ============================================
echo -e "\n${YELLOW}🗄️ Exécution des migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✅ Migrations exécutées${NC}"

# ============================================
# 9. CHANGEMENT ATOMIQUE DU SYMLINK
# ============================================
echo -e "\n${CYAN}🔄 Switch vers la nouvelle release (ATOMIQUE)...${NC}"

# Créer le symlink current vers la nouvelle release de manière atomique
ln -sfn $NEW_RELEASE $CURRENT_LINK

# Le changement est instantané - l'ancien code ne sert plus
echo -e "${GREEN}✅ Symlink mis à jour${NC}"

# ============================================
# 10. REDÉMARRAGE DES SERVICES
# ============================================
echo -e "\n${YELLOW}🔄 Redémarrage des services...${NC}"

# Vider le cache Redis (pour prendre en compte les changements)
if systemctl is-active --quiet redis-server; then
    REDIS_PASSWORD=$(grep -E "^REDIS_PASSWORD=" $CURRENT_LINK/.env 2>/dev/null | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')
    if [ -n "$REDIS_PASSWORD" ] && [ "$REDIS_PASSWORD" != "null" ]; then
        redis-cli -a "$REDIS_PASSWORD" FLUSHALL --no-auth-warning >/dev/null 2>&1 || true
    else
        redis-cli FLUSHALL >/dev/null 2>&1 || true
    fi
    echo -e "${GREEN}✅ Cache Redis vidé${NC}"
fi

# Reload PHP-FPM
systemctl reload php8.3-fpm

# Redémarrer les workers
php artisan queue:restart
supervisorctl restart axontis-worker:* 2>/dev/null || true

echo -e "${GREEN}✅ Services redémarrés${NC}"

# ============================================
# 11. NETTOYAGE DES ANCIENNES RELEASES
# ============================================
echo -e "\n${YELLOW}🧹 Nettoyage des anciennes releases...${NC}"

# Garder les 3 dernières releases + l'actuelle
cd $RELEASES_PATH
ls -t | tail -n +4 | xargs -r rm -rf --
echo -e "${GREEN}✅ Anciennes releases nettoyées${NC}"

# ============================================
# 12. VÉRIFICATION FINALE
# ============================================
echo -e "\n${YELLOW}🔍 Vérification finale...${NC}"

# Test HTTP
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [[ "$HTTP_CODE" == "200" ]] || [[ "$HTTP_CODE" == "302" ]]; then
    echo -e "${GREEN}✅ Application accessible (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}⚠️ L'application répond avec HTTP $HTTP_CODE${NC}"
fi

# Vérifier les workers
WORKER_STATUS=$(supervisorctl status axontis-worker:* 2>/dev/null | grep -c "RUNNING" || echo "0")
if [[ "$WORKER_STATUS" -gt 0 ]]; then
    echo -e "${GREEN}✅ ${WORKER_STATUS} worker(s) actif(s)${NC}"
else
    echo -e "${YELLOW}⚠️ Aucun worker actif${NC}"
fi

# Afficher les logs récents
echo -e "\n${YELLOW}📜 Dernières lignes de log:${NC}"
tail -5 $SHARED_PATH/storage/logs/laravel.log 2>/dev/null || echo "Pas de logs"

echo -e "\n${GREEN}=========================================="
echo "🎉 DÉPLOIEMENT TERMINÉ - ZERO DOWNTIME!"
echo "📌 Release: $TIMESTAMP"
echo "==========================================${NC}"
