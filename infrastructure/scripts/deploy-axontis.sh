#!/bin/bash
# =============================================================================
# Script de déploiement Axontis
# Usage: deploy-axontis.sh [branch]
# =============================================================================

set -e

# Configuration
APP_PATH="/var/www/axontis"
BACKUP_PATH="/var/backups/axontis"
BRANCH="${1:-main}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=========================================="
echo "🚀 DÉPLOIEMENT AXONTIS"
echo "📅 $(date)"
echo "🌿 Branche: $BRANCH"
echo -e "==========================================${NC}"

cd $APP_PATH

# ============================================
# 1. VÉRIFICATION DE L'ÉTAT GIT
# ============================================
echo -e "\n${YELLOW}📋 Vérification de l'état Git...${NC}"
CURRENT_COMMIT=$(git rev-parse --short HEAD)
echo "Commit actuel: $CURRENT_COMMIT"

# Vérifier les modifications locales
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${RED}⚠️  Modifications locales détectées!${NC}"
    git status --short
    read -p "Continuer et écraser les modifications? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Déploiement annulé."
        exit 1
    fi
fi

# ============================================
# 2. BACKUP
# ============================================
echo -e "\n${YELLOW}📦 Création du backup...${NC}"
mkdir -p $BACKUP_PATH

# Backup de la base de données (utilise les credentials du .env)
echo "Backup de la base de données..."
if [ -f "$APP_PATH/.env" ]; then
    DB_USER=$(grep -E "^DB_USERNAME=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    DB_PASS=$(grep -E "^DB_PASSWORD=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    DB_NAME=$(grep -E "^DB_DATABASE=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
    mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH/db_${TIMESTAMP}.sql" 2>/dev/null || true
fi

# Backup de Redis (si utilisé)
echo "Backup de Redis..."
if systemctl is-active --quiet redis-server; then
    REDIS_PASSWORD=$(grep -E "^REDIS_PASSWORD=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')

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

# Backup des fichiers
tar -czf "$BACKUP_PATH/files_${TIMESTAMP}_${CURRENT_COMMIT}.tar.gz" \
    -C /var/www axontis \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='vendor' \
    --exclude='node_modules' 2>/dev/null || true

echo -e "${GREEN}✅ Backup créé: files_${TIMESTAMP}_${CURRENT_COMMIT}.tar.gz${NC}"

# Nettoyer les vieux backups (garder les 5 derniers)
cd $BACKUP_PATH
ls -t files_*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm --
ls -t db_*.sql 2>/dev/null | tail -n +6 | xargs -r rm --
ls -t redis_*.rdb 2>/dev/null | tail -n +6 | xargs -r rm --

# ============================================
# 3. MODE MAINTENANCE
# ============================================
echo -e "\n${YELLOW}🔧 Activation du mode maintenance...${NC}"
cd $APP_PATH
php artisan down --retry=60 --refresh=5

# ============================================
# 4. PULL DU CODE
# ============================================
echo -e "\n${YELLOW}📥 Récupération du code...${NC}"
git fetch origin
git reset --hard origin/$BRANCH
NEW_COMMIT=$(git rev-parse --short HEAD)
echo -e "${GREEN}✅ Nouveau commit: $NEW_COMMIT${NC}"

# ============================================
# 5. INSTALLATION DES DÉPENDANCES
# ============================================
echo -e "\n${YELLOW}📦 Installation des dépendances...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# ============================================
# 6. MIGRATIONS
# ============================================
echo -e "\n${YELLOW}🗄️ Exécution des migrations...${NC}"
php artisan migrate --force

# ============================================
# 7. OPTIMISATIONS
# ============================================
echo -e "\n${YELLOW}⚡ Optimisation de Laravel...${NC}"

# Vider le cache Redis si actif
if systemctl is-active --quiet redis-server; then
    echo "Vidage du cache Redis..."
    REDIS_PASSWORD=$(grep -E "^REDIS_PASSWORD=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')

    if [ -n "$REDIS_PASSWORD" ] && [ "$REDIS_PASSWORD" != "null" ]; then
        # Vider les différentes bases Redis
        redis-cli -a "$REDIS_PASSWORD" -n 1 FLUSHDB --no-auth-warning >/dev/null 2>&1 || true
        redis-cli -a "$REDIS_PASSWORD" -n 2 FLUSHDB --no-auth-warning >/dev/null 2>&1 || true
        redis-cli -a "$REDIS_PASSWORD" -n 3 FLUSHDB --no-auth-warning >/dev/null 2>&1 || true
        echo -e "${GREEN}✅ Cache Redis vidé${NC}"
    else
        echo -e "${YELLOW}⚠️ Pas de mot de passe Redis configuré dans .env - cache non vidé${NC}"
    fi
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ============================================
# 8. PERMISSIONS
# ============================================
echo -e "\n${YELLOW}🔒 Configuration des permissions...${NC}"
chown -R www-data:www-data $APP_PATH
chmod -R 755 $APP_PATH
chmod -R 775 $APP_PATH/storage
chmod -R 775 $APP_PATH/bootstrap/cache

# ============================================
# 9. REDÉMARRAGE DES SERVICES
# ============================================
echo -e "\n${YELLOW}🔄 Redémarrage des services...${NC}"
systemctl reload php8.3-fpm
php artisan queue:restart
supervisorctl restart axontis-worker:* 2>/dev/null || true

# ============================================
# 10. DÉSACTIVATION DU MODE MAINTENANCE
# ============================================
echo -e "\n${YELLOW}✅ Désactivation du mode maintenance...${NC}"
php artisan up

# ============================================
# 11. VÉRIFICATION
# ============================================
echo -e "\n${YELLOW}🔍 Vérification...${NC}"

# Test HTTP
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [[ "$HTTP_CODE" == "200" ]] || [[ "$HTTP_CODE" == "302" ]]; then
    echo -e "${GREEN}✅ Application accessible (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}⚠️ L'application répond avec HTTP $HTTP_CODE${NC}"
fi

# Vérifier Redis
if systemctl is-active --quiet redis-server; then
    REDIS_PASSWORD=$(grep -E "^REDIS_PASSWORD=" $APP_PATH/.env | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')

    if [ -n "$REDIS_PASSWORD" ] && [ "$REDIS_PASSWORD" != "null" ]; then
        REDIS_PING=$(redis-cli -a "$REDIS_PASSWORD" ping --no-auth-warning 2>/dev/null || echo "ERROR")
    else
        REDIS_PING=$(redis-cli ping 2>/dev/null || echo "ERROR")
    fi

    if [[ "$REDIS_PING" == "PONG" ]]; then
        echo -e "${GREEN}✅ Redis opérationnel${NC}"

        # Afficher les statistiques Redis
        if [ -n "$REDIS_PASSWORD" ] && [ "$REDIS_PASSWORD" != "null" ]; then
            CACHE_KEYS=$(redis-cli -a "$REDIS_PASSWORD" -n 1 DBSIZE --no-auth-warning 2>/dev/null | awk '{print $2}')
            SESSION_KEYS=$(redis-cli -a "$REDIS_PASSWORD" -n 2 DBSIZE --no-auth-warning 2>/dev/null | awk '{print $2}')
            QUEUE_KEYS=$(redis-cli -a "$REDIS_PASSWORD" -n 3 DBSIZE --no-auth-warning 2>/dev/null | awk '{print $2}')
        else
            CACHE_KEYS=$(redis-cli -n 1 DBSIZE 2>/dev/null | awk '{print $2}')
            SESSION_KEYS=$(redis-cli -n 2 DBSIZE 2>/dev/null | awk '{print $2}')
            QUEUE_KEYS=$(redis-cli -n 3 DBSIZE 2>/dev/null | awk '{print $2}')
        fi
        echo -e "   Cache: ${CACHE_KEYS} clés | Sessions: ${SESSION_KEYS} | Queues: ${QUEUE_KEYS}"
    else
        echo -e "${RED}⚠️ Redis ne répond pas correctement${NC}"
    fi
else
    echo -e "${YELLOW}ℹ️  Redis n'est pas actif (normal si non utilisé)${NC}"
fi

# Vérifier les workers Supervisor
WORKER_STATUS=$(supervisorctl status axontis-worker:* 2>/dev/null | grep -c "RUNNING" || echo "0")
if [[ "$WORKER_STATUS" -gt 0 ]]; then
    echo -e "${GREEN}✅ ${WORKER_STATUS} worker(s) actif(s)${NC}"
else
    echo -e "${YELLOW}⚠️ Aucun worker actif${NC}"
fi

# Afficher les logs récents
echo -e "\n${YELLOW}📜 Dernières lignes de log:${NC}"
tail -5 $APP_PATH/storage/logs/laravel.log 2>/dev/null || echo "Pas de logs"

echo -e "\n${GREEN}=========================================="
echo "🎉 DÉPLOIEMENT TERMINÉ!"
echo "📌 $CURRENT_COMMIT → $NEW_COMMIT"
echo -e "==========================================${NC}"
